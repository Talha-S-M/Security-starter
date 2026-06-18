<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('security.user.table', 'users');

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (! Schema::hasColumn($table, 'phone')) {
                $blueprint->string('phone', 30)->nullable();
            }

            if (! Schema::hasColumn($table, 'mfa_methods')) {
                $blueprint->json('mfa_methods')->nullable();
            }
            if (! Schema::hasColumn($table, 'mfa_email')) {
                $blueprint->string('mfa_email')->nullable()->after('email');
            }

            if (! Schema::hasColumn($table, 'mfa_configured_at')) {
                $blueprint->timestamp('mfa_configured_at')->nullable()->after('mfa_email');
            }
        });
    }

    public function down(): void
    {
        $table = config('security.user.table', 'users');

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            foreach (['phone', 'mfa_methods', 'mfa_email', 'mfa_configured_at'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });
    }
};
