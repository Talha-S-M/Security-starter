<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('requester_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('access_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('requester_id')->nullable(false)->change();
        });
    }
};
