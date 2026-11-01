<?php

// Responsable de prise en main : Ryan ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createAdministration();
        $this->createCatalogue();
        $this->createOrders();
        $this->createClaims();
    }

    private function createAdministration(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->text('delivery_address')->nullable();
            $table->boolean('ranking_opt_in')->default(false);
            $table->string('public_alias', 50)->nullable();
            $table->boolean('analytics_consent')->default(false);
        });
        Schema::table('restaurants', function (Blueprint $table): void {
            // Aucun tarif de livraison inventé : l'administrateur doit le renseigner.
            $table->decimal('delivery_fee', 18, 2)->nullable();
        });
        Schema::create('platform_grants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->boolean('active')->default(true);
            $table->timestampsTz();
        });
        Schema::create('restaurant_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->string('opens_at', 5);
            $table->string('closes_at', 5);
            $table->unique(['restaurant_id', 'weekday', 'opens_at']);
        });
        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('action', 80);
            $table->string('target', 100);
            $table->json('metadata');
            $table->timestampTz('occurred_at');
        });
    }

    private function createCatalogue(): void
    {
        Schema::create('menu_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->unsignedInteger('position')->default(0);
            $table->timestampsTz();
            $table->unique(['id', 'restaurant_id']);
        });
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('category_id');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 18, 2);
            $table->boolean('available')->default(true);
            $table->boolean('is_daily')->default(false);
            $table->timestampTz('archived_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
            // Le SGBD interdit aussi les liens entre deux restaurants différents.
            $table->foreign(['category_id', 'restaurant_id'])->references(['id', 'restaurant_id'])->on('menu_categories')->restrictOnDelete();
            $table->unique(['id', 'restaurant_id']);
            $table->index(['restaurant_id', 'archived_at']);
        });
    }

    private function createOrders(): void
    {
        Schema::create('order_quotes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->json('payload');
            $table->timestampTz('expires_at');
            $table->timestampsTz();
            $table->unique(['id', 'restaurant_id', 'customer_id']);
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->uuid('quote_id')->unique();
            $table->uuid('idempotency_key');
            $table->string('reference', 40)->unique();
            $table->enum('service_mode', ['on_site', 'delivery']);
            $table->timestampTz('arrival_at')->nullable();
            $table->json('delivery_snapshot')->nullable();
            $table->enum('status', ['pending', 'accepted', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled', 'rejected'])->default('pending');
            $table->enum('payment_state', ['unrecorded', 'paid', 'refunded'])->default('unrecorded');
            $table->decimal('subtotal', 18, 2);
            $table->decimal('discount', 18, 2);
            $table->decimal('delivery_fee', 18, 2);
            $table->decimal('total', 18, 2);
            $table->string('currency', 3)->default('CDF');
            $table->unsignedInteger('version')->default(1);
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->foreign(['quote_id', 'restaurant_id', 'customer_id'])->references(['id', 'restaurant_id', 'customer_id'])->on('order_quotes')->restrictOnDelete();
            $table->unique(['customer_id', 'idempotency_key']);
            $table->unique(['id', 'restaurant_id']);
            $table->index(['restaurant_id', 'status', 'created_at']);
        });
        Schema::create('order_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('menu_item_id');
            $table->string('name', 150);
            $table->decimal('unit_price', 18, 2);
            $table->unsignedSmallInteger('quantity');
            $table->decimal('total', 18, 2);
            $table->foreign(['order_id', 'restaurant_id'])->references(['id', 'restaurant_id'])->on('orders')->restrictOnDelete();
            $table->foreign(['menu_item_id', 'restaurant_id'])->references(['id', 'restaurant_id'])->on('menu_items')->restrictOnDelete();
            $table->unique(['order_id', 'menu_item_id']);
        });
        Schema::create('order_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('reason')->nullable();
            $table->unsignedInteger('version');
            $table->timestampTz('occurred_at');
            $table->unique(['order_id', 'version']);
        });
    }

    private function createClaims(): void
    {
        Schema::create('claims', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('order_id');
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->string('subject', 150);
            $table->text('message');
            $table->string('status', 30)->default('open');
            $table->timestampsTz();
            $table->foreign(['order_id', 'restaurant_id'])->references(['id', 'restaurant_id'])->on('orders')->restrictOnDelete();
        });
        Schema::create('claim_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('claim_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->string('state', 20)->default('submitted');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['claim_replies', 'claims', 'order_events', 'order_lines', 'orders', 'order_quotes', 'menu_items', 'menu_categories', 'audit_events', 'restaurant_hours', 'platform_grants'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropColumn('delivery_fee');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['delivery_address', 'ranking_opt_in', 'public_alias', 'analytics_consent']);
        });
    }
};
