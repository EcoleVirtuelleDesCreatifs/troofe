<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reviewable'); // Restaurant, Talent, Event
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('status', 20)->default('published')->index(); // published, pending, hidden
            $table->timestamps();
            $table->unique(['user_id', 'reviewable_type', 'reviewable_id']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamps();
            $table->unique(['user_id', 'favoritable_type', 'favoritable_id']);
        });

        // « Suivre » une fiche : être notifié de ses nouveaux événements, articles et offres
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('followable'); // Restaurant, Talent, User (organisateur)
            $table->timestamps();
            $table->unique(['user_id', 'followable_type', 'followable_id']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('topics')->nullable();
            $table->string('locale', 5)->default('fr');
            $table->string('frequency', 10)->default('weekly'); // weekly, monthly
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->string('source', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('ad_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('page');
            $table->string('format', 20); // leaderboard, native, rectangle
            $table->string('dimensions', 40)->nullable();
            $table->unsignedInteger('price_per_day');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_slot_id')->constrained()->cascadeOnDelete();
            $table->string('advertiser');
            $table->string('title', 80);
            $table->string('text', 200)->nullable();
            $table->string('cta', 30)->default('Découvrir');
            $table->string('url');
            $table->string('image')->nullable();
            $table->string('color', 20)->default('brun');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedInteger('amount');
            $table->string('status', 20)->default('en_attente')->index(); // brouillon, en_attente, a_payer, active, terminee, refusee, suspendue
            $table->string('moderation_note')->nullable();
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->timestamp('ending_notice_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('nouveau');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->nullableMorphs('subject');
            $table->text('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'contact_messages', 'ad_campaigns', 'ad_slots', 'newsletter_subscribers', 'follows', 'favorites', 'reviews'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
