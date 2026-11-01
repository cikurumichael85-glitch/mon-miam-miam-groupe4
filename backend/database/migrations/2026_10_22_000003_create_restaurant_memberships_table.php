<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_memberships', function (Blueprint $table): void {
            $table->id();
            // Empêcher la suppression d'un compte ou restaurant encore référencé.
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('role', ['employee', 'manager', 'restaurant_admin']);
            $table->boolean('active')->default(false);
            $table->timestampsTz();
            // Un rôle local par couple ; plusieurs restaurants restent possibles par compte.
            $table->unique(['restaurant_id', 'user_id']);
            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_memberships');
    }
};
