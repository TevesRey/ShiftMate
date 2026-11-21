<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('teams')) {
            Schema::create('teams', function (Blueprint $table) {
                $table->id(); $table->string('name'); $table->string('slug')->unique();
                $table->boolean('is_personal')->default(false); $table->timestamps(); $table->softDeletes();
            });
        }
        if (! Schema::hasTable('team_members')) {
            Schema::create('team_members', function (Blueprint $table) {
                $table->id(); $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('member'); $table->timestamps(); $table->unique(['team_id', 'user_id']);
            });
        }
        if (! Schema::hasTable('team_invitations')) {
            Schema::create('team_invitations', function (Blueprint $table) {
                $table->id(); $table->string('code', 64)->unique(); $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
                $table->string('email'); $table->string('role')->default('member');
                $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
                $table->timestamp('expires_at')->nullable(); $table->timestamp('accepted_at')->nullable(); $table->timestamps();
            });
        }
    }
    public function down(): void { /* Keep operational team data during rollback. */ }
};
