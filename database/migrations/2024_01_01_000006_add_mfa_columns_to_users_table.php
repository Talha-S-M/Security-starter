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
            if (! Schema::hasColumn($table, 'mfa_method')) {
                $blueprint->string('mfa_method', 20)->default('email');
            }

            if (! Schema::hasColumn($table, 'phone')) {
                $blueprint->string('phone', 30)->nullable();
            }
        });
    }

    public function down(): void
    {
        $table = config('security.user.table', 'users');

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            foreach (['mfa_method', 'phone'] as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });
    }
};
