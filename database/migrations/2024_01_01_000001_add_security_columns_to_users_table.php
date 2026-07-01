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
            if (! Schema::hasColumn($table, 'password_changed_at')) {
                $blueprint->timestamp('password_changed_at')->nullable();
            }

            if (! Schema::hasColumn($table, 'must_change_password')) {
                $blueprint->boolean('must_change_password')->default(false);
            }

            if (! Schema::hasColumn($table, 'is_active')) {
                $blueprint->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn($table, 'failed_login_attempts')) {
                $blueprint->unsignedSmallInteger('failed_login_attempts')->default(0);
            }

            if (! Schema::hasColumn($table, 'locked_until')) {
                $blueprint->timestamp('locked_until')->nullable();
            }

            if (! Schema::hasColumn($table, 'last_login_at')) {
                $blueprint->timestamp('last_login_at')->nullable();
            }

            if (! Schema::hasColumn($table, 'access_expires_at')) {
                $blueprint->timestamp('access_expires_at')->nullable();
            }
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
            $columns = [
                'password_changed_at',
                'must_change_password',
                'is_active',
                'failed_login_attempts',
                'locked_until',
                'last_login_at',
                'access_expires_at',
                'phone', 'mfa_methods', 'mfa_email', 'mfa_configured_at'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $blueprint->dropColumn($column);
                }
            }
        });
    }
};
