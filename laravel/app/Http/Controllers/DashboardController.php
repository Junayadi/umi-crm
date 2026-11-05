<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Exports\DashboardXlsxExport;
use App\Models\Category;
use App\Models\Lead;
use App\Models\Source;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    private const STATUSES = Lead::STATUSES;

    public function index(Request $request): View
    {
        $data = $this->dashboardData($request);
        /** @var Builder<Lead> $query */
        $query = $data['query'];
        $data['leads'] = (clone $query)
            ->with(['user:id,name', 'category:id,name', 'source:id,name'])
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->simplePaginate(50)
            ->withQueryString();

        return view('dashboard', $data);
    }

    public function export(Request $request, DashboardXlsxExport $export): StreamedResponse
    {
        $data = $this->dashboardData($request);
        /** @var Builder<Lead> $query */
        $query = $data['query'];
        $path = $export->create($query);
        $filename = 'umi-crm-'.$data['from'].'-sd-'.$data['to'].'.xlsx';

        return response()->streamDownload(function () use ($path): void {
            $stream = false;

            try {
                $stream = fopen($path, 'rb');

                if ($stream === false) {
                    throw new \RuntimeException('File ekspor Excel tidak dapat dibaca.');
                }

                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }

                @unlink($path);
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** @return array<string, mixed> */
    private function dashboardData(Request $request): array
    {
        $isCustomerService = $request->user()->isCustomerService();
        $rules = [
            'from' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'source' => ['nullable', 'integer', Rule::exists('sources', 'id')],
        ];

        if (! $isCustomerService) {
            $rules['staff'] = [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', Role::CustomerService->value),
            ];
        }

        $validator = Validator::make($request->query(), $rules, [
            'from.required' => 'Isi tanggal mulai.',
            'to.required' => 'Isi tanggal akhir.',
            'from.before_or_equal' => 'Tanggal mulai tidak boleh di masa depan.',
            'to.before_or_equal' => 'Tanggal akhir tidak boleh di masa depan.',
            'staff.exists' => 'Staf Customer Service tidak valid.',
            'staff.integer' => 'Staf Customer Service tidak valid.',
            'category.integer' => 'Kategori layanan tidak valid.',
            'source.integer' => 'Sumber leads tidak valid.',
            'category.exists' => 'Kategori layanan tidak valid.',
            'source.exists' => 'Sumber leads tidak valid.',
        ]);

        $validator->after(function (ValidatorInstance $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $input = $validator->getData();
            $from = $input['from'] ?? today()->startOfMonth()->toDateString();
            $to = $input['to'] ?? today()->toDateString();

            if ($from > $to) {
                $validator->errors()->add('to', 'Tanggal akhir harus sama atau setelah tanggal mulai.');

                return;
            }

            $rangeDays = (int) CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1;

            if ($rangeDays > 31) {
                $validator->errors()->add('to', 'Rentang tanggal maksimal 31 hari.');
            }
        });

        $filters = $validator->fails() ? [] : $validator->validated();
        $filterErrors = $validator->errors();
        $from = $filters['from'] ?? today()->startOfMonth()->toDateString();
        $to = $filters['to'] ?? today()->toDateString();
        $selectedStaff = $isCustomerService ? null : ($filters['staff'] ?? null);
        $selectedCategory = $filters['category'] ?? null;
        $selectedSource = $filters['source'] ?? null;

        $query = Lead::query()->whereBetween('entry_date', [$from, $to]);

        if ($isCustomerService) {
            $query->where('user_id', $request->user()->id);
        } elseif ($selectedStaff) {
            $query->where('user_id', $selectedStaff);
        }

        if ($selectedCategory) {
            $query->where('category_id', $selectedCategory);
        }

        if ($selectedSource) {
            $query->where('source_id', $selectedSource);
        }

        $sumColumns = collect(array_keys(self::STATUSES))
            ->map(fn (string $key): string => "COALESCE(SUM(leads.$key), 0) AS $key")
            ->implode(', ');
        $totals = (clone $query)
            ->selectRaw("COUNT(*) AS entries, $sumColumns")
            ->first();

        $categories = Category::query()->orderBy('id')->get(['id', 'name']);
        $sources = Source::query()->orderBy('id')->get(['id', 'name']);
        $staff = $isCustomerService
            ? collect()
            : User::customerServices()->orderBy('name')->get(['id', 'name']);

        $groupedSources = (clone $query)
            ->join('sources', 'sources.id', '=', 'leads.source_id')
            ->select('sources.id AS group_id', 'sources.name AS name')
            ->selectRaw($sumColumns)
            ->groupBy('sources.id', 'sources.name')
            ->get()
            ->keyBy('group_id');
        $sourceChart = $sources->map(fn (Source $source): array => $this->chartGroup($source->name, $groupedSources->get($source->id)))->values()->all();

        $chartGroups = $sourceChart;
        $closingByCategory = [];

        if (! $isCustomerService) {
            $groupedStaff = (clone $query)
                ->join('users', 'users.id', '=', 'leads.user_id')
                ->select('users.id AS group_id', 'users.name AS name')
                ->selectRaw($sumColumns)
                ->groupBy('users.id', 'users.name')
                ->get()
                ->keyBy('group_id');
            $chartStaff = $selectedStaff ? $staff->where('id', (int) $selectedStaff) : $staff;
            $chartGroups = $chartStaff->map(fn (User $user): array => $this->chartGroup($user->name, $groupedStaff->get($user->id)))->values()->all();

            $groupedCategories = (clone $query)
                ->join('categories', 'categories.id', '=', 'leads.category_id')
                ->select('categories.id AS group_id', 'categories.name AS name')
                ->selectRaw($sumColumns)
                ->groupBy('categories.id', 'categories.name')
                ->get()
                ->keyBy('group_id');
            $closingByCategory = $categories->map(fn (Category $category): array => [
                'name' => $category->name,
                'value' => (int) ($groupedCategories->get($category->id)->closing ?? 0),
            ])->all();
        }

        $chartValues = collect($chartGroups)->flatMap(fn (array $group): array => array_values($group['values']))->all();
        $maxValue = max([1, ...$chartValues]);
        $chartStep = $this->chartStep($maxValue / 4);

        return [
            'isCustomerService' => $isCustomerService,
            'title' => $isCustomerService ? 'Diagram Saya' : 'Dashboard Analitik',
            'filterErrors' => $filterErrors,
            'from' => $from,
            'to' => $to,
            'periodLabel' => CarbonImmutable::parse($from)->locale('id')->translatedFormat('j M').' sampai '.CarbonImmutable::parse($to)->locale('id')->translatedFormat('j M Y'),
            'selectedStaff' => $selectedStaff,
            'selectedCategory' => $selectedCategory,
            'selectedSource' => $selectedSource,
            'categories' => $categories,
            'sources' => $sources,
            'staff' => $staff,
            'totals' => $totals,
            'chartGroups' => $chartGroups,
            'closingBySource' => $sources->map(fn (Source $source): array => [
                'name' => $source->name,
                'value' => (int) ($groupedSources->get($source->id)->closing ?? 0),
            ])->all(),
            'closingByCategory' => $closingByCategory,
            'chartStep' => $chartStep,
            'chartTop' => $chartStep * 4,
            'statuses' => self::STATUSES,
            'query' => $query,
        ];
    }

    /** @return array{name: string, values: array<string, int>} */
    private function chartGroup(string $name, ?object $totals): array
    {
        return [
            'name' => $name,
            'values' => collect(array_keys(self::STATUSES))
                ->mapWithKeys(fn (string $key): array => [$key => (int) ($totals->{$key} ?? 0)])
                ->all(),
        ];
    }

    private function chartStep(float $value): float
    {
        $power = 10 ** floor(log10($value));

        foreach ([1, 2, 5, 10] as $multiplier) {
            if ($multiplier * $power >= $value) {
                return (float) ($multiplier * $power);
            }
        }

        return (float) (10 * $power);
    }
}
