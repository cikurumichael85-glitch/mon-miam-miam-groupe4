<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            // Un seul encaissement par commande : aucun nouvel essai aveugle après un timeout.
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreign(['order_id', 'restaurant_id'])->references(['id', 'restaurant_id'])->on('orders')->restrictOnDelete();
            $table->string('merchant_order_ref', 40)->unique();
            $table->string('external_reference', 150)->nullable()->unique();
            $table->string('merchant_fingerprint', 64);
            $table->string('mode', 10);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('CDF');
            $table->string('state', 30)->default('initializing');
            $table->timestampTz('verified_at')->nullable();
            $table->timestampsTz();
        });
        Schema::create('payment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('digest', 64);
            $table->string('outcome', 30);
            $table->timestampTz('verified_at');
            $table->unique(['payment_id', 'digest']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
    }
};
