<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('gym_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('owner')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->timestamp('last_seen_at')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gym_id']);
            $table->dropColumn(['gym_id', 'username', 'role', 'is_active', 'last_login_at', 'last_seen_at']);
        });
    }
};
