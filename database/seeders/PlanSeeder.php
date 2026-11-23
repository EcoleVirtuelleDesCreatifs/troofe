<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/** Formules d'abonnement pro (modifiables ensuite dans l'administration) */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['slug' => 'decouverte', 'name' => 'Découverte', 'name_en' => 'Starter', 'tagline' => 'Pour se lancer', 'tagline_en' => 'To get started',
                'price_monthly' => 0, 'price_yearly' => 0, 'max_listings' => 1, 'commission_rate' => 10, 'position' => 1,
                'features' => ['1 fiche active', 'Commandes et réservations en ligne', 'Notifications par e-mail', 'Commission de 10 %'],
                'features_en' => ['1 active listing', 'Online orders and bookings', 'Email notifications', '10% commission']],
            ['slug' => 'essentiel', 'name' => 'Essentiel', 'name_en' => 'Essential', 'tagline' => 'Pour les indépendants', 'tagline_en' => 'For independents',
                'price_monthly' => 9900, 'price_yearly' => 99000, 'max_listings' => 2, 'commission_rate' => 8, 'has_stats' => true, 'position' => 2,
                'features' => ['2 fiches actives', 'Statistiques de visites', 'Badge « Profil vérifié » prioritaire', 'Commission de 8 %'],
                'features_en' => ['2 active listings', 'Visit statistics', 'Priority "Verified" badge', '8% commission']],
            ['slug' => 'pro', 'name' => 'Pro', 'name_en' => 'Pro', 'tagline' => 'Le plus choisi', 'tagline_en' => 'Most popular',
                'price_monthly' => 24900, 'price_yearly' => 249000, 'max_listings' => 5, 'commission_rate' => 6, 'has_stats' => true, 'can_feature' => true, 'position' => 3,
                'features' => ['5 fiches actives', 'Mise en avant dans les annuaires', 'Statistiques détaillées', 'Commission de 6 %'],
                'features_en' => ['5 active listings', 'Featured in directories', 'Detailed statistics', '6% commission']],
            ['slug' => 'premium', 'name' => 'Premium', 'name_en' => 'Premium', 'tagline' => 'Groupes et chaînes', 'tagline_en' => 'Groups and chains',
                'price_monthly' => 59900, 'price_yearly' => 599000, 'max_listings' => 30, 'commission_rate' => 4, 'has_stats' => true, 'can_feature' => true, 'priority_support' => true, 'position' => 4,
                'features' => ['30 fiches actives', 'Mise en avant permanente', 'Support prioritaire', 'Commission de 4 %'],
                'features_en' => ['30 active listings', 'Permanent featuring', 'Priority support', '4% commission']],
        ];
        foreach ($plans as $p) {
            Plan::updateOrCreate(['slug' => $p['slug']], $p + ['is_active' => true]);
        }
    }
}
