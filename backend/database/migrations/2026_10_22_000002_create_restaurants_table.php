<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            // La publication doit être volontaire ; aucun restaurant n'est actif par défaut.
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            // Pas de fuseau unique imposé : la RDC comporte plusieurs zones horaires.
            $table->string('timezone', 64);
            $table->text('address');
            $table->boolean('delivery_enabled')->default(false);
            $table->unsignedInteger('settings_version')->default(1);
            $table->timestampsTz();
            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
