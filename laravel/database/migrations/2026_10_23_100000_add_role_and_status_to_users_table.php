<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // customer_service | manajemen | supervisor (lihat App\Enums\Role)
            $table->string('role', 30)->default('customer_service')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            // password sementara dari Supervisor: wajib diganti saat login berikutnya
            $table->boolean('must_change_password')->default(false)->after('is_active');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'is_active', 'must_change_password']);
        });
    }
};
