<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date');

            // restrictOnDelete: staf/sumber/kategori yang sudah punya riwayat tidak bisa
            // dihapus permanen, hanya dinonaktifkan (sesuai aturan Master Data di prototype).
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            // Jumlah prospek per status. Unsigned, jadi angka negatif ditolak database.
            $table->unsignedSmallInteger('cold')->default(0);
            $table->unsignedSmallInteger('warm')->default(0);
            $table->unsignedSmallInteger('potential')->default(0);
            $table->unsignedSmallInteger('closing')->default(0);
            $table->unsignedSmallInteger('cancel')->default(0);

            $table->softDeletes();
            $table->timestamps();

            // Kolom virtual: bernilai 1 selama baris belum dihapus, NULL kalau sudah dihapus.
            // Dipakai di unique index supaya "satu baris per tanggal + admin + sumber + kategori"
            // hanya berlaku untuk entri aktif. Entri yang sudah dihapus (soft delete) tidak
            // menghalangi input ulang, persis seperti prototype. NULL dianggap berbeda di unique index.
            $table->tinyInteger('active_flag')->nullable()
                ->virtualAs('CASE WHEN deleted_at IS NULL THEN 1 ELSE NULL END');

            $table->unique(
                ['entry_date', 'user_id', 'source_id', 'category_id', 'active_flag'],
                'leads_unique_active_entry'
            );
            $table->index(['category_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
