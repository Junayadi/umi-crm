<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Satu baris input harian: per tanggal + admin + sumber + kategori,
 * berisi jumlah prospek untuk tiap status.
 */
class Lead extends Model
{
    use SoftDeletes;

    /** CS tidak bisa menambah atau mengubah entri yang lebih lama dari ini (hari). */
    public const LOCK_DAYS = 30;

    /** Kolom status => label tampilan. Urutan sama dengan prototype. */
    public const STATUSES = [
        'cold' => 'Cold',
        'warm' => 'Warm',
        'potential' => 'Potensial',
        'closing' => 'Closing',
        'cancel' => 'Cancel',
    ];

    protected $fillable = [
        'entry_date', 'user_id', 'source_id', 'category_id',
        'cold', 'warm', 'potential', 'closing', 'cancel',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date:Y-m-d',
            'cold' => 'integer',
            'warm' => 'integer',
            'potential' => 'integer',
            'closing' => 'integer',
            'cancel' => 'integer',
        ];
    }

    /** Admin (staf CS) pemilik entri. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeBetweenDates(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }

    public function isLocked(): bool
    {
        return $this->entry_date->lt(today()->subDays(self::LOCK_DAYS));
    }

    /**
     * Aturan prototype: Supervisor dan Manajemen boleh ubah/hapus semua entri;
     * CS hanya entri miliknya sendiri yang belum terkunci.
     */
    public function isEditableBy(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isCustomerService()) {
            return $this->user_id === $user->id && ! $this->isLocked();
        }

        return true;
    }
}
