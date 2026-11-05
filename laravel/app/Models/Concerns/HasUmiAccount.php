<?php

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tambahan untuk model User: peran, status aktif, dan relasi ke leads.
 *
 * Cara pakai: di app/Models/User.php tambahkan
 *     use App\Models\Concerns\HasUmiAccount;
 *     ... lalu di dalam class: use HasUmiAccount;
 *
 * Kolom role, is_active, dan must_change_password otomatis ditambahkan
 * ke $fillable dan $casts lewat initializeHasUmiAccount().
 */
trait HasUmiAccount
{
    public function initializeHasUmiAccount(): void
    {
        $this->mergeFillable(['role', 'is_active', 'must_change_password']);
        $this->mergeCasts([
            'role' => Role::class,
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ]);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isCustomerService(): bool
    {
        return $this->role === Role::CustomerService;
    }

    public function isManajemen(): bool
    {
        return $this->role === Role::Manajemen;
    }

    public function isSupervisor(): bool
    {
        return $this->role === Role::Supervisor;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Staf Customer Service saja (isi dropdown "Nama Admin" dan filter "Staf CS"). */
    public function scopeCustomerServices(Builder $query): Builder
    {
        return $query->where('role', Role::CustomerService->value);
    }
}
