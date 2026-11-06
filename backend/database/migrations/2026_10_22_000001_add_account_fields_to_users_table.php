<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Migration additionnelle : conserver les comptes et la colonne password existants.
            $table->string('phone', 32)->nullable();
            $table->enum('status', ['active', 'suspended'])->default('active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['phone', 'status']);
        });
    }
};
