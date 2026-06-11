<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->unsignedBigInteger('performed_by');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('performed_at');
            $table->timestamps();

            $table->index(['type', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_reviews');
    }
};
