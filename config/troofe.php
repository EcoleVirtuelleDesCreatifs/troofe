<?php

return [
    // SEO / GEO
    'social_profiles' => env('TROOFE_SOCIAL_PROFILES', ''), // URLs séparées par des virgules (Facebook, Instagram, LinkedIn…)
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_site_verification' => env('BING_SITE_VERIFICATION'),
    'indexnow_key' => env('INDEXNOW_KEY'),
    'twitter' => env('TROOFE_TWITTER'),

    'admin_email' => env('TROOFE_ADMIN_EMAIL', 'contact@troofe.net'),
    'currency' => env('TROOFE_CURRENCY', 'XOF'),
    'commission_rate' => (float) env('TROOFE_COMMISSION_RATE', 8),

    // Rappels d'échéance d'abonnement (jours avant la fin)
    'renewal_reminders' => [7, 3, 1],
    // Délai de grâce après échéance avant expiration (jours)
    'grace_days' => 3,

    // Catégories de notifications réglables par l'utilisateur.
    // Les catégories "obligatoires" partent toujours par e-mail.
    'notification_categories' => [
        'compte' => ['label' => 'Sécurité et compte', 'mandatory' => true],
        'facturation' => ['label' => 'Abonnement, paiements et factures', 'mandatory' => true],
        'commandes' => ['label' => 'Commandes et billets', 'mandatory' => false],
        'reservations' => ['label' => 'Réservations d\'artistes et d\'artisans', 'mandatory' => false],
        'abonnements' => ['label' => 'Nouveautés des fiches que je suis', 'mandatory' => false],
        'avis' => ['label' => 'Avis reçus sur mes fiches', 'mandatory' => false],
        'publicite' => ['label' => 'Campagnes publicitaires', 'mandatory' => false],
        'moderation' => ['label' => 'Validation de mes fiches', 'mandatory' => true],
    ],

    'newsletter_topics' => [
        'restaurants' => 'Nouvelles adresses',
        'evenements' => 'Agenda des événements',
        'culture' => 'Danse, artistes et artisans',
        'recettes' => 'Encyclopédie et recettes',
    ],

    'occasions' => ['Mariages', 'Anniversaires', 'Baptêmes', 'Entreprises', 'Festivals', 'Restaurants & maquis', 'Hôtels & réceptions', 'Écoles', 'Autre'],
];
