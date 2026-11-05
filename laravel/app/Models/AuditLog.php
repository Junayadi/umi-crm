<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/** Audit trail untuk perubahan leads dan data pengelolaan. */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'lead_id', 'auditable_type', 'auditable_id',
        'description', 'old_values', 'new_values',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Catat perubahan entri lead.
     *
     *   AuditLog::record('tambah', $lead);
     *   AuditLog::record('hapus', $lead);
     *
     *   // ubah: ambil nilai lama SEBELUM disimpan
     *   $old = $lead->only(array_keys(Lead::STATUSES));
     *   $lead->update([...]);
     *   AuditLog::record('ubah', $lead, $old);
     *
     * @param  array<string, mixed>|null  $old  nilai status sebelum diubah (opsional)
     */
    public static function record(string $action, Lead $lead, ?array $old = null): self
    {
        $lead->loadMissing(['user', 'source', 'category']);

        $counts = collect(Lead::STATUSES)
            ->map(fn (string $label, string $key) => $label.' '.$lead->{$key})
            ->implode(', ');

        return static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'lead_id' => $lead->id,
            'auditable_type' => $lead->getMorphClass(),
            'auditable_id' => $lead->id,
            'description' => sprintf(
                '%s, %s, %s, admin %s: %s',
                $lead->category->name,
                $lead->source->name,
                $lead->entry_date->translatedFormat('j M Y'),
                $lead->user->name,
                $counts,
            ),
            'old_values' => $old ? array_intersect_key($old, Lead::STATUSES) : null,
            'new_values' => $lead->only(array_keys(Lead::STATUSES)),
        ]);
    }

    /** @param array<string, mixed>|null $old @param array<string, mixed>|null $new */
    public static function recordChange(
        string $action,
        Model $subject,
        string $description,
        ?array $old = null,
        ?array $new = null,
    ): self {
        return static::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject->getMorphClass(),
            'auditable_id' => $subject->getKey(),
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
