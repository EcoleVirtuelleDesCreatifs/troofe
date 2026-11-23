<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Titre et description SEO personnalisables (FR / EN) sur toutes les fiches publiques */
return new class extends Migration
{
    private array $tables = ['restaurants', 'talents', 'events', 'articles', 'dishes'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('seo_title', 70)->nullable();
                $table->string('seo_title_en', 70)->nullable();
                $table->string('seo_description', 170)->nullable();
                $table->string('seo_description_en', 170)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            Schema::table($t, fn (Blueprint $table) => $table->dropColumn(['seo_title', 'seo_title_en', 'seo_description', 'seo_description_en']));
        }
    }
};
