<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->string('tagline_en')->nullable();
            $table->string('cuisine', 80);
            $table->string('city', 80)->index();
            $table->string('country', 80);
            $table->string('district', 120)->nullable();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedTinyInteger('price_level')->default(2); // 1 à 4
            $table->json('opening_hours')->nullable();
            $table->boolean('accepts_orders')->default(true);
            $table->boolean('delivery')->default(false);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->string('cover')->nullable();
            $table->string('status', 20)->default('pending')->index(); // draft, pending, published, rejected, suspended
            $table->string('moderation_note')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('category', 60)->default('Plats');
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->unsignedInteger('price');
            $table->string('image')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('origin_country', 80);
            $table->string('region', 120)->nullable();
            $table->string('category', 60)->default('Plat');
            $table->string('summary')->nullable();
            $table->string('summary_en')->nullable();
            $table->longText('history')->nullable();
            $table->longText('history_en')->nullable();
            $table->json('ingredients')->nullable();
            $table->longText('recipe')->nullable();
            $table->longText('recipe_en')->nullable();
            $table->unsignedSmallInteger('prep_minutes')->nullable();
            $table->string('image')->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('dish_restaurant', function (Blueprint $table) {
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->primary(['dish_id', 'restaurant_id']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 60)->default('Actualité');
            $table->string('excerpt', 400)->nullable();
            $table->string('excerpt_en', 400)->nullable();
            $table->longText('body');
            $table->longText('body_en')->nullable();
            $table->string('cover')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->string('slug')->unique();
            $table->string('category', 60)->default('Dégustation');
            $table->string('excerpt', 400)->nullable();
            $table->string('excerpt_en', 400)->nullable();
            $table->longText('description')->nullable();
            $table->longText('description_en')->nullable();
            $table->string('venue');
            $table->string('city', 80)->index();
            $table->string('country', 80);
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->string('cover')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('moderation_note')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('sold')->default(0);
            $table->unsignedSmallInteger('max_per_order')->default(10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['ticket_types', 'events', 'articles', 'dish_restaurant', 'dishes', 'menu_items', 'restaurants'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
