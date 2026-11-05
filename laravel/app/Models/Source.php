<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Sumber leads: Instagram, Facebook, Linktree, WhatsApp, dst. */
class Source extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /** Yang nonaktif hilang dari form input, riwayatnya tetap ada. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
