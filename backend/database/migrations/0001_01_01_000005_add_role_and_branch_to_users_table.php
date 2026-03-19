<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->after('role_id')->constrained('branches')->nullOnDelete();
            $table->index(['branch_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['branch_id', 'role_id']);
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('role_id');
        });
    }
};

