<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['gym_id', 'status', 'ends_at']);
            $table->index(['member_id', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
