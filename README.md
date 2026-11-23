# Trôofê — plateforme Laravel

Site complet de la gastronomie, de la culture et des savoir-faire africains, bilingue **français / anglais** :
restaurants avec commande en ligne, encyclopédie des plats, actualités, agenda et billetterie à QR code,
groupes de danse, prestations artistiques et artisans (réservations, devis, commandes, visites d'atelier),
abonnements pro, régie publicitaire, lettre d'information, administration et espace pro.

## Pile technique

| Élément | Version / choix |
|---|---|
| Langage | PHP 8.2 minimum (8.3 recommandé) |
| Framework | Laravel 12 |
| Administration et espace pro | Filament 3.3 (`/admin` et `/pro`) |
| Base de données | MySQL 8 / MariaDB 10.6 (SQLite pour les tests) |
| Front | Blade + Bootstrap 5.3 + CSS de la maquette, sans étape de compilation |
| Paiement | CinetPay (Orange Money, MTN MoMo, Moov, Wave, cartes) + pilote « simulation » |
| PDF / QR codes | barryvdh/laravel-dompdf, simplesoftwareio/simple-qrcode |
| E-mails | Notifications Laravel en file d'attente, thème aux couleurs Trôofê |

## Installation locale

```bash
composer install
cp .env.example .env            # puis renseigner la base de données
php artisan key:generate
php artisan migrate --seed      # schéma + données de démonstration de la maquette
php artisan storage:link
php artisan serve               # http://127.0.0.1:8000
php artisan queue:work          # dans un second terminal : envoi des e-mails
```

Pour voir les e-mails sans serveur SMTP : `MAIL_MAILER=log` (ils sont écrits dans `storage/logs`).

Comptes de démonstration (mot de passe `demo1234`) :

| Compte | Rôle |
|---|---|
| admin@troofe.ci | Administrateur (`/admin`) |
| chef@troofe.ci | Restaurateur, formule Pro arrivant à échéance dans 6 jours (rappel testable) |
| event@troofe.ci | Organisateur et artiste (troupe Adowa, quartet), une demande de réservation reçue |
| aya@troofe.ci | Membre |
| guest.en@troofe.ci | Membre anglophone (e-mails en anglais) |

Tests automatisés : `php artisan test` (réservations, cycle d'abonnement, double opt-in).

## Mise en ligne sur LWS (hébergement mutualisé)

1. Créer la base MySQL et l'adresse `notifications@votredomaine` dans le panneau LWS.
2. Envoyer le projet **avec le dossier `vendor`** (faire `composer install --no-dev --optimize-autoloader` en local si Composer n'est pas disponible en SSH).
3. Faire pointer le domaine sur le dossier `public/`. Si ce n'est pas possible, le `.htaccess` à la racine redirige vers `public/`.
4. Renseigner `.env` : `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, base de données, SMTP LWS (`MAIL_HOST=mail.votredomaine`, port 465, `MAIL_SCHEME=smtps`).
5. En SSH : `php artisan migrate --force`, `php artisan db:seed --class=PlanSeeder --force`, `php artisan storage:link`, `php artisan optimize`.
   Si `storage:link` est refusé, créer le lien symbolique depuis le gestionnaire de fichiers ou copier `storage/app/public` vers `public/storage`.
6. **Tâche cron unique (indispensable)**, toutes les minutes :
   `* * * * * cd /home/.../troofe && php artisan schedule:run >> /dev/null 2>&1`
   Elle envoie les e-mails en attente et lance les traitements automatiques ci-dessous. Sans elle, aucun e-mail ne part.
7. Paiements réels : créer un compte marchand CinetPay, puis `PAYMENT_DRIVER=cinetpay` et les clés `CINETPAY_*`.
   L'URL de notification est envoyée automatiquement : `https://votredomaine/paiement/notification/cinetpay`.

## Langues

- Interface : `lang/en.json` (1 160+ chaînes) et `lang/fr/*.php`. Le français est la langue source.
- Choix de la langue : sélecteur FR/EN, `?lang=en`, profil utilisateur, sinon langue du navigateur. Balises `hreflang` et plan du site bilingue.
- Contenus : chaque fiche a des champs anglais facultatifs (onglet « English » dans l'admin et l'espace pro). Sans traduction, le texte français s'affiche.
- E-mails : envoyés dans la langue du destinataire (préférence du compte ou de l'abonné à la lettre).

## Notifications et abonnements

Toutes les notifications sont enregistrées dans la cloche du site **et** envoyées par e-mail, en file d'attente.
Chaque membre règle ses e-mails dans *Mon compte → Notifications*. Les catégories marquées « obligatoire » partent toujours.

| Catégorie | Déclencheurs |
|---|---|
| Compte (obligatoire) | Vérification de l'e-mail, bienvenue, mot de passe oublié / modifié, ouverture de l'espace pro |
| Facturation (obligatoire) | Abonnement activé / renouvelé, rappels J-7, J-3, J-1, échéance dépassée, expiration, résiliation, paiement reçu (avec facture PDF), paiement échoué |
| Modération (obligatoire) | Fiche soumise (admins), fiche publiée / refusée / suspendue (propriétaire), campagne à valider |
| Commandes | Commande payée (client et restaurateur), changement d'étape, billets émis avec QR code, vente de billets (organisateur), rappel la veille de l'événement |
| Réservations | Nouvelle demande (prestataire), accusé de réception (client), devis reçu, devis accepté, refus, annulation, prestation réalisée (invitation à laisser un avis) |
| Abonnements aux fiches | Nouvel événement ou nouvelle offre d'un restaurant / artiste suivi |
| Avis | Nouvel avis reçu, réponse du professionnel |
| Publicité | Campagne validée (lien de paiement), en diffusion, fin proche, bilan de fin |

**Lettre d'information** : double opt-in, thèmes au choix, fréquence hebdomadaire ou mensuelle, langue, désinscription en un clic (lien et en-tête `List-Unsubscribe`). Envoi le jeudi à 9 h.

**Formules pro** (modifiables dans l'admin) : Découverte (gratuit, 1 fiche), Essentiel, Pro, Premium. À l'expiration, les fiches au-delà de la limite sont masquées puis republiées automatiquement au renouvellement ; rien n'est supprimé.

## Tâches automatiques (`routes/console.php`)

| Commande | Fréquence | Rôle |
|---|---|---|
| `queue:work --stop-when-empty` | chaque minute | Envoi des e-mails |
| `troofe:subscriptions` | 7 h | Rappels, retards, expirations |
| `troofe:event-reminders` | toutes les heures | Rappel la veille aux détenteurs de billets |
| `troofe:campaigns` | 6 h 30 | Fin de campagne, bilans, alertes |
| `troofe:cleanup-orders` | 30 min | Annule les commandes impayées après 2 h et libère les billets |
| `troofe:newsletter` | jeudi 9 h / 1er du mois | Lettre hebdomadaire et mensuelle |

## Organisation du code

```
app/Models                 Modèles (Restaurant, Talent, Event, Order, Booking, Subscription, Payment…)
app/Services               Paiements (CinetPay, simulation), commandes, réservations, modération, quotas
app/Notifications          Toutes les notifications (base commune TroofeNotification)
app/Filament/Admin         Administration : 17 sections
app/Filament/Pro           Espace pro : fiches, commandes, réservations, campagnes, avis
app/Console/Commands       Tâches planifiées
resources/views            Pages publiques, espace membre, e-mails (vendor/mail)
public/assets              CSS et logos de la maquette, JavaScript du site
database/seeders/data      Contenu de démonstration exporté de la maquette
```

## À faire avant l'ouverture au public

- Rédiger les pages *Mentions légales*, *Confidentialité* et *Conditions* (`resources/views/pages/legal/`) avec votre conseil juridique.
- Remplacer les données de démonstration par les vrais restaurants et artistes (`php artisan migrate:fresh --seed` les recharge ; ne pas lancer en production).
- Relire les traductions anglaises des contenus.
