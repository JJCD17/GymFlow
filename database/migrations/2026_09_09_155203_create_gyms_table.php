<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gyms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('phone')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('America/Mexico_City');
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedSmallInteger('inactivity_days')->default(7);
            $table->json('closed_weekdays')->nullable();
            $table->text('message_expiring')->nullable();
            $table->text('message_expired')->nullable();
            $table->text('message_inactive')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gyms');
    }
};
