<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Commandes : repas (restaurant) ou billets (événement)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('type', 20)->index(); // repas, billets
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('en_attente')->index(); // en_attente, payee, confirmee, en_preparation, prete, livree, terminee, annulee
            $table->string('fulfilment', 20)->nullable(); // retrait, livraison, sur_place
            $table->string('delivery_address')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable(); // noms des porteurs de billets…
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('fees')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('commission')->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('item'); // MenuItem ou TicketType
            $table->string('name');
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('total');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('code')->unique();
            $table->string('holder_name');
            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Offres d'abonnement pour les professionnels
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('tagline')->nullable();
            $table->string('tagline_en')->nullable();
            $table->unsignedInteger('price_monthly')->default(0);
            $table->unsignedInteger('price_yearly')->default(0);
            $table->unsignedSmallInteger('max_listings')->default(1);   // fiches (restaurant, talent, événement) actives
            $table->boolean('can_feature')->default(false);              // mise en avant incluse
            $table->boolean('has_stats')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->unsignedTinyInteger('commission_rate')->nullable(); // remplace le taux global
            $table->json('features')->nullable();
            $table->json('features_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('billing_cycle', 10)->default('monthly'); // monthly, yearly
            $table->string('status', 20)->default('pending')->index(); // pending, active, past_due, cancelled, expired
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->json('reminders_sent')->nullable(); // [7,3,1] pour la période en cours
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();     // transaction_id transmis au prestataire
            $table->string('invoice_number', 30)->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('payable'); // Subscription, Order, AdCampaign
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->string('currency', 5)->default('XOF');
            $table->string('provider', 20);
            $table->string('method', 40)->nullable(); // OM, MOMO, WAVE, CARD…
            $table->string('status', 20)->default('pending')->index(); // pending, paid, failed, refunded
            $table->json('provider_payload')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payments', 'subscriptions', 'plans', 'tickets', 'order_items', 'orders'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
