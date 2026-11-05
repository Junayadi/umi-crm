<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Source;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    private const PER_PAGE = 50;

    /** Angka di atas ini memicu pertanyaan "Lanjutkan menyimpan?" (seperti prototype). */
    private const HIGH_THRESHOLD = 10;

    public function index(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        // ?edit=ID mengisi form dengan data entri itu
        $editing = null;
        if ($request->filled('edit')) {
            $editing = $category->leads()->findOrFail($request->integer('edit'));
            Gate::authorize('update', $editing);
        }

        // Dropdown berisi yang aktif, ditambah milik entri yang sedang diedit
        // (supaya entri milik staf/sumber yang sudah nonaktif tetap bisa dibuka).
        $admins = User::customerServices()
            ->where(fn ($q) => $q->where('is_active', true)
                ->when($editing, fn ($q) => $q->orWhere('id', $editing->user_id)))
            ->orderBy('name')
            ->get();

        $sources = Source::where(fn ($q) => $q->where('is_active', true)
            ->when($editing, fn ($q) => $q->orWhere('id', $editing->source_id)))
            ->orderBy('id')
            ->get();

        $leads = $category->leads()
            ->with(['user:id,name', 'source:id,name'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->simplePaginate(self::PER_PAGE);

        // Total semua entri kategori ini (bukan hanya halaman yang tampil)
        $sumColumns = collect(array_keys(Lead::STATUSES))
            ->map(fn (string $key) => "coalesce(sum($key), 0) as $key")
            ->implode(', ');
        $totals = Lead::query()
            ->where('category_id', $category->id)
            ->selectRaw("count(*) as entries, $sumColumns")
            ->first();

        $duplicate = session('duplicate_id')
            ? $category->leads()->find(session('duplicate_id'))
            : null;

        return view('leads.index', [
            'category' => $category,
            'statuses' => Lead::STATUSES,
            'editing' => $editing,
            'admins' => $admins,
            'sources' => $sources,
            'leads' => $leads,
            'totals' => $totals,
            'duplicate' => $duplicate,
            'minDate' => today()->subDays(Lead::LOCK_DAYS)->toDateString(),
        ]);
    }

    public function store(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->is_active, 404);
        Gate::authorize('create', Lead::class);

        return $this->save($request, $category, new Lead);
    }

    public function update(Request $request, Category $category, Lead $lead): RedirectResponse
    {
        Gate::authorize('update', $lead);

        return $this->save($request, $category, $lead);
    }

    public function destroy(Request $request, Category $category, Lead $lead): RedirectResponse
    {
        Gate::authorize('delete', $lead);

        DB::transaction(function () use ($lead) {
            AuditLog::record('hapus', $lead);
            $lead->delete(); // soft delete: baris tetap ada, hanya disembunyikan
        });

        return redirect()
            ->route('leads.index', ['category' => $category])
            ->with('status', 'Data dihapus.');
    }

    /** Dipakai bersama oleh store (baris baru) dan update (baris yang sudah ada). */
    private function save(Request $request, Category $category, Lead $lead): RedirectResponse
    {
        $user = $request->user();
        $isNew = ! $lead->exists;

        $formUrl = route('leads.index', array_filter([
            'category' => $category,
            'edit' => $isNew ? null : $lead->id,
        ]));

        // --- validasi ---------------------------------------------------------
        $dateRules = ['required', 'date_format:Y-m-d', 'before_or_equal:today'];
        // Entri baru tidak boleh lebih tua dari 30 hari. CS juga tidak boleh memundurkan
        // tanggal entrinya sendiri melewati batas itu. Supervisor/Manajemen boleh saat edit.
        if ($isNew || $user->isCustomerService()) {
            $dateRules[] = 'after_or_equal:'.today()->subDays(Lead::LOCK_DAYS)->toDateString();
        }

        $rules = [
            'entry_date' => $dateRules,
            'source_id' => ['required', Rule::exists('sources', 'id')->where(
                fn ($q) => $q->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $lead->source_id))
            )],
        ];

        // CS selalu menyimpan atas nama dirinya sendiri; yang lain memilih staf CS.
        if (! $user->isCustomerService()) {
            $rules['user_id'] = ['required', Rule::exists('users', 'id')->where(
                fn ($q) => $q->where('role', Role::CustomerService->value)
                    ->where(fn ($q) => $q->where('is_active', true)->orWhere('id', $lead->user_id))
            )];
        }

        $messages = [
            'entry_date.required' => 'Tanggal wajib diisi.',
            'entry_date.date_format' => 'Format tanggal tidak valid.',
            'entry_date.before_or_equal' => 'Tanggal tidak boleh di masa depan.',
            'entry_date.after_or_equal' => 'Tanggal tidak boleh lebih dari '.Lead::LOCK_DAYS.' hari ke belakang.',
            'source_id.required' => 'Pilih sumber leads.',
            'source_id.exists' => 'Sumber leads tidak valid atau sudah nonaktif.',
            'user_id.required' => 'Pilih nama admin.',
            'user_id.exists' => 'Nama admin tidak valid atau sudah nonaktif.',
        ];
        $attributes = [];

        foreach (Lead::STATUSES as $key => $label) {
            $rules[$key] = ['nullable', 'integer', 'min:0', 'max:9999'];
            $messages["$key.integer"] = "$label harus berupa angka bulat.";
            $messages["$key.min"] = "$label tidak boleh negatif.";
            $messages["$key.max"] = "$label terlalu besar (maksimal 9999).";
            $attributes[$key] = $label;
        }

        $data = $request->validate($rules, $messages, $attributes);

        // --- susun data -------------------------------------------------------
        $counts = [];
        foreach (array_keys(Lead::STATUSES) as $key) {
            $counts[$key] = (int) ($data[$key] ?? 0); // kosong dianggap 0, seperti prototype
        }

        $attrs = [
            'entry_date' => $data['entry_date'],
            'user_id' => $user->isCustomerService() ? $user->id : (int) $data['user_id'],
            'source_id' => (int) $data['source_id'],
            'category_id' => $category->id,
        ] + $counts;

        // --- cek duplikat: satu baris per tanggal + admin + sumber + kategori ----
        $duplicate = Lead::query()
            ->where('entry_date', $attrs['entry_date'])
            ->where('user_id', $attrs['user_id'])
            ->where('source_id', $attrs['source_id'])
            ->where('category_id', $category->id)
            ->when(! $isNew, fn ($q) => $q->where('id', '!=', $lead->id))
            ->first();

        if ($duplicate) {
            return redirect($formUrl)->withInput()->with('duplicate_id', $duplicate->id);
        }

        // --- angka di atas 10: minta konfirmasi --------------------------------
        $high = collect(Lead::STATUSES)->filter(fn (string $label, string $key) => $counts[$key] > self::HIGH_THRESHOLD);

        if ($high->isNotEmpty() && ! $request->boolean('confirm_high')) {
            return redirect($formUrl)->withInput()->with(
                'high_warning',
                $high->map(fn (string $label, string $key) => $label.' '.$counts[$key])->implode(', ')
            );
        }

        // --- simpan + catat log -------------------------------------------------
        try {
            DB::transaction(function () use ($lead, $attrs, $isNew) {
                $old = $isNew ? null : $lead->only(array_keys(Lead::STATUSES));

                $lead->fill($attrs)->save();
                $lead->unsetRelations(); // supaya nama admin/sumber di log diambil ulang

                AuditLog::record($isNew ? 'tambah' : 'ubah', $lead, $old);
            });
        } catch (UniqueConstraintViolationException) {
            // dua orang menyimpan entri yang sama di detik yang sama: unique index database yang menahan
            return redirect($formUrl)->withInput()->withErrors([
                'entry_date' => 'Entri ini baru saja dibuat oleh orang lain. Muat ulang halaman, lalu edit entri tersebut.',
            ]);
        }

        return redirect()
            ->route('leads.index', ['category' => $category])
            ->with('status', $isNew ? 'Data disimpan.' : 'Perubahan disimpan.');
    }
}
