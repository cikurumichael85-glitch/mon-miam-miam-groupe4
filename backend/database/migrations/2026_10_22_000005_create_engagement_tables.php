<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->loyalty();
        $this->referrals();
        $this->community();
    }

    private function loyalty(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_key', 80)->unique();
            $table->foreignId('restaurant_id')->nullable()->constrained()->restrictOnDelete();
            $table->boolean('active')->default(false);
            $table->json('rules');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('approved_at');
            $table->timestampsTz();
        });
        Schema::create('loyalty_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->unique(['program_id', 'user_id']);
            $table->timestampsTz();
        });
        Schema::create('loyalty_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_id')->constrained('loyalty_accounts')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event_key', 100);
            $table->string('kind', 30);
            $table->integer('delta');
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('occurred_at');
            $table->unique(['account_id', 'event_key']);
        });
        Schema::create('loyalty_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('credit_id')->constrained('loyalty_entries')->restrictOnDelete();
            $table->foreignId('debit_id')->constrained('loyalty_entries')->restrictOnDelete();
            $table->unsignedInteger('points');
            $table->unique(['credit_id', 'debit_id']);
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('loyalty_program_id')->nullable()->constrained('loyalty_programs')->restrictOnDelete();
            $table->unsignedInteger('points_used')->default(0);
        });
    }

    private function referrals(): void
    {
        Schema::create('referral_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->restrictOnDelete();
            $table->foreignId('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('code', 40)->unique();
            $table->unique(['program_id', 'owner_id']);
        });
        Schema::create('referrals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained('loyalty_programs')->restrictOnDelete();
            $table->foreignId('code_id')->constrained('referral_codes')->restrictOnDelete();
            $table->foreignId('referred_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('qualifying_order_id')->nullable()->constrained('orders')->restrictOnDelete();
            $table->timestampTz('created_at');
            $table->timestampTz('rewarded_at')->nullable();
            $table->unique(['program_id', 'referred_user_id']);
        });
    }

    private function community(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->foreign(['order_id', 'restaurant_id'])->references(['id', 'restaurant_id'])->on('orders')->restrictOnDelete();
            $table->text('comment');
            $table->boolean('visible')->default(true);
            $table->timestampsTz();
        });
        Schema::create('campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->restrictOnDelete();
            $table->enum('kind', ['promotion', 'event', 'quiz']);
            $table->string('title', 150);
            $table->text('description');
            $table->string('poster_url', 2048)->nullable();
            $table->json('rules');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->boolean('active')->default(false);
            $table->timestampsTz();
        });
        Schema::create('campaign_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->boolean('won')->default(false);
            $table->timestampTz('created_at');
            $table->unique(['campaign_id', 'user_id']);
        });
        Schema::create('privacy_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kind', 30);
            $table->string('status', 30)->default('pending');
            $table->text('resolution')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['privacy_requests', 'campaign_entries', 'campaigns', 'reviews', 'referrals', 'referral_codes'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('loyalty_program_id');
            $table->dropColumn('points_used');
        });
        foreach (['loyalty_allocations', 'loyalty_entries', 'loyalty_accounts', 'loyalty_programs'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
