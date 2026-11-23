<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CultureController;
use App\Http\Controllers\DishController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

/* ---------- Pages publiques ---------- */
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/recherche', [HomeController::class, 'search'])->name('search');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');
Route::get('/illustration/{visual}/{seed}.svg', [HomeController::class, 'placeholder'])->name('placeholder')->where(['visual' => '[a-z]+', 'seed' => '[a-z0-9]+']);
Route::get('/page/{page}', [HomeController::class, 'page'])->name('page')->whereIn('page', ['mentions-legales', 'confidentialite', 'conditions', 'a-propos']);

Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');
Route::get('/restaurants/{restaurant}', [RestaurantController::class, 'show'])->name('restaurants.show');
Route::get('/encyclopedie', [DishController::class, 'index'])->name('dishes.index');
Route::get('/encyclopedie/{dish}', [DishController::class, 'show'])->name('dishes.show');
Route::get('/actualites', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/actualites/{article}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/evenements', [EventController::class, 'index'])->name('events.index');
Route::get('/evenements/{event}', [EventController::class, 'show'])->name('events.show');

/* Culture & savoir-faire : /culture, /danse, /prestations, /artisans */
Route::get('/culture', [CultureController::class, 'hub'])->name('culture.hub');
Route::get('/{type}', [CultureController::class, 'index'])->name('culture.index')->whereIn('type', ['danse', 'prestations', 'artisans']);
Route::get('/{type}/{talent}', [CultureController::class, 'show'])->name('culture.show')->whereIn('type', ['danse', 'prestations', 'artisans']);

Route::get('/offres-pro', [SubscriptionController::class, 'pricing'])->name('pricing');
Route::get('/annonceurs', [AdController::class, 'index'])->name('advertise');
Route::get('/pub/{campaign}/clic', [AdController::class, 'click'])->name('ads.click');
Route::get('/devenir-partenaire', [ContactController::class, 'partner'])->name('partner');
Route::get('/contact', [ContactController::class, 'create'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:5,10');

/* Lettre d'information (double opt-in, désinscription en un clic) */
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:6,10');
Route::get('/newsletter/confirmer/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/preferences/{token}', [NewsletterController::class, 'manage'])->name('newsletter.manage');
Route::post('/newsletter/preferences/{token}', [NewsletterController::class, 'update'])->name('newsletter.update');
Route::match(['get', 'post'], '/newsletter/desinscription/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

/* Paiements */
Route::post('/paiement/notification/{provider}', [PaymentController::class, 'notify'])->name('payments.notify');
Route::get('/paiement/{payment}/retour', [PaymentController::class, 'return'])->name('payments.return');

/* ---------- Authentification ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/connexion', [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/inscription', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/inscription', [AuthController::class, 'register'])->middleware('throttle:5,10');
    Route::get('/mot-de-passe-oublie', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordController::class, 'sendLink'])->name('password.email')->middleware('throttle:5,10');
    Route::get('/reinitialiser/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reinitialiser', [PasswordController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/deconnexion', [AuthController::class, 'logout'])->name('logout');
    Route::get('/verification', [AuthController::class, 'verifyNotice'])->name('verification.notice');
    Route::get('/verification/{id}/{hash}', [AuthController::class, 'verify'])->name('verification.verify')->middleware(['signed', 'throttle:6,1']);
    Route::post('/verification/renvoyer', [AuthController::class, 'resendVerification'])->name('verification.send')->middleware('throttle:3,1');

    /* Favoris et suivis : possibles avant vérification de l'e-mail */
    Route::post('/favori/{type}/{id}', [EngagementController::class, 'favorite'])->name('favorites.toggle');
    Route::post('/suivre/{type}/{id}', [EngagementController::class, 'follow'])->name('follows.toggle');
});

/* Actions qui engagent : compte vérifié obligatoire */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/restaurants/{restaurant}/commander', [OrderController::class, 'storeFood'])->name('orders.food');
    Route::post('/evenements/{event}/billets', [OrderController::class, 'storeTickets'])->name('orders.tickets');
    Route::get('/billet/{code}', [OrderController::class, 'ticket'])->name('tickets.show');
    Route::get('/controle-acces', [OrderController::class, 'checkinForm'])->name('checkin')->middleware('role:organisateur');
    Route::post('/controle-acces', [OrderController::class, 'checkin'])->name('checkin.store')->middleware('role:organisateur');

    Route::post('/culture/{talent}/demande', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('/culture/soiree', [BookingController::class, 'pack'])->name('bookings.pack');
    Route::post('/reservations/{booking}/{status}', [BookingController::class, 'transition'])->name('bookings.transition')->whereIn('status', ['confirmee', 'annulee', 'devis_envoye', 'refusee', 'realisee']);

    Route::post('/avis/{type}/{id}', [EngagementController::class, 'review'])->name('reviews.store');
    Route::post('/avis/{review}/reponse', [EngagementController::class, 'reply'])->name('reviews.reply');

    Route::post('/devenir-partenaire', [ContactController::class, 'becomePartner'])->name('partner.store');
    Route::post('/offres-pro/{plan}', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
    Route::get('/abonnement/{subscription}/renouveler', [SubscriptionController::class, 'renew'])->name('subscription.renew');
    Route::post('/abonnement/{subscription}/resilier', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/abonnement/{subscription}/reactiver', [SubscriptionController::class, 'resume'])->name('subscription.resume');

    Route::post('/annonceurs', [AdController::class, 'store'])->name('campaigns.store');
    Route::get('/campagnes/{campaign}/payer', [AdController::class, 'pay'])->name('campaigns.pay');

    Route::get('/paiement/{payment}/simulation', [PaymentController::class, 'simulate'])->name('payments.simulate');
    Route::post('/paiement/{payment}/simulation', [PaymentController::class, 'decide'])->name('payments.decide');
    Route::get('/paiement/{payment}/reessayer', [PaymentController::class, 'retry'])->name('payments.retry');
});

/* ---------- Espace membre ---------- */
Route::middleware('auth')->prefix('compte')->name('account.')->controller(AccountController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/profil', 'profile')->name('profile');
    Route::put('/profil', 'updateProfile')->name('profile.update');
    Route::put('/mot-de-passe', 'updatePassword')->name('password');
    Route::delete('/', 'destroy')->name('destroy');
    Route::get('/notifications', 'notifications')->name('notifications');
    Route::put('/notifications/preferences', 'updatePreferences')->name('notifications.preferences');
    Route::post('/notifications/tout-lire', 'readAll')->name('notifications.readAll');
    Route::get('/notifications/{id}', 'openNotification')->name('notifications.open');
    Route::get('/commandes', 'orders')->name('orders');
    Route::get('/commandes/{order}', 'order')->name('orders.show');
    Route::get('/billets', 'tickets')->name('tickets');
    Route::get('/reservations', 'bookings')->name('bookings');
    Route::get('/favoris', 'favorites')->name('favorites');
    Route::get('/abonnements', 'follows')->name('follows');
    Route::get('/abonnement', 'billing')->name('billing');
    Route::get('/factures/{payment}', 'invoice')->name('invoice');
});

Route::get('/compte/connexion', fn () => redirect()->route('login'));
