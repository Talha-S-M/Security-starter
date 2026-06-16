<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('security.sms.log_table', 'sms_log'), function (Blueprint $table) {
            $table->id();
            $table->string('cnic')->nullable();
            $table->string('mobile_no');
            $table->text('message');
            $table->boolean('is_delivered')->default(false);
            $table->string('performed_action')->nullable();
            $table->json('api_response')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index(['cnic', 'source_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('security.sms.log_table', 'sms_log'));
    }
};
