<?php

// Responsable de prise en main : Ryan ; conserver les anciennes tentatives sans les convertir.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['pgsql', 'sqlite'], true)) {
            throw new RuntimeException('Cette migration requiert PostgreSQL ou SQLite.');
        }
        Schema::table('payments', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true);
            $table->timestampTz('archived_at')->nullable();
            $table->dropUnique(['order_id']);
        });
        // Une seule tentative active par commande, même avec plusieurs requêtes concurrentes.
        DB::statement('CREATE UNIQUE INDEX payments_one_active_per_order ON payments (order_id) WHERE is_active');
    }

    public function down(): void
    {
        $duplicates = DB::table('payments')->select('order_id')->groupBy('order_id')->havingRaw('COUNT(*) > 1')->exists();
        if ($duplicates) {
            throw new RuntimeException('Retour impossible sans supprimer un historique : conserver les tentatives et utiliser la procédure de restauration sauvegardée.');
        }
        DB::statement('DROP INDEX payments_one_active_per_order');
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['is_active', 'archived_at']);
            $table->unique('order_id');
        });
    }
};
