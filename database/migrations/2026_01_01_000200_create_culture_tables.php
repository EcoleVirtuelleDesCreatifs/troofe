<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Groupes de danse, prestations artistiques et artisans
        Schema::create('talents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // troupe, artiste, artisan
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline');
            $table->string('tagline_en')->nullable();
            $table->string('category', 80)->index();
            $table->string('visual', 30)->nullable(); // motif de l'illustration par défaut
            $table->json('tags')->nullable();         // styles, disciplines ou techniques
            $table->json('materials')->nullable();
            $table->json('repertoire')->nullable();
            $table->json('languages')->nullable();
            $table->json('audiences')->nullable();    // occasions
            $table->json('zones')->nullable();
            $table->string('maker_name')->nullable(); // artisan : nom de la personne ou coopérative
            $table->unsignedSmallInteger('members')->nullable();
            $table->unsignedSmallInteger('musicians')->nullable();
            $table->unsignedSmallInteger('since_year')->nullable();
            $table->unsignedSmallInteger('min_notice_days')->default(7);
            $table->string('city', 80)->index();
            $table->string('country', 80);
            $table->string('district', 120)->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->text('pro_offer')->nullable();
            $table->text('pro_offer_en')->nullable();
            $table->unsignedInteger('visit_price')->nullable();
            $table->string('visit_duration', 40)->nullable();
            $table->string('visit_details')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('cover')->nullable();
            $table->json('gallery')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('moderation_note')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Formules (prestations) ou créations (catalogue artisan)
        Schema::create('talent_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->string('duration', 60)->nullable();
            $table->string('lead_time', 60)->nullable();
            $table->unsignedInteger('price');
            $table->text('details')->nullable();
            $table->text('details_en')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('talent_unavailabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('note')->nullable();
            $table->unique(['talent_id', 'date']);
        });

        Schema::create('talent_event', function (Blueprint $table) {
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->primary(['talent_id', 'event_id']);
        });

        Schema::create('restaurant_talent', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->primary(['restaurant_id', 'talent_id']);
        });

        Schema::create('dish_talent', function (Blueprint $table) {
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->primary(['dish_id', 'talent_id']);
        });

        // Demandes de réservation, commandes et visites d'atelier
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('talent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('talent_offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->default('prestation'); // prestation, commande, visite
            $table->date('event_date')->nullable()->index();
            $table->string('city', 80)->nullable();
            $table->string('venue')->nullable();
            $table->string('occasion', 60)->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('delivery_mode', 20)->nullable();
            $table->string('contact_phone', 40);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('nouvelle')->index(); // nouvelle, devis_envoye, confirmee, realisee, refusee, annulee
            $table->unsignedInteger('amount')->default(0);
            $table->text('quote_note')->nullable();
            $table->json('history')->nullable();
            $table->string('group_reference', 20)->nullable()->index(); // demandes « Composer ma soirée »
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['bookings', 'dish_talent', 'restaurant_talent', 'talent_event', 'talent_unavailabilities', 'talent_offers', 'talents'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
