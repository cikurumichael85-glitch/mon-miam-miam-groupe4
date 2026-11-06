<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

use App\Http\Controllers\Api\V1\AccountCredentialsController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CampaignController;
use App\Http\Controllers\Api\V1\CheckoutOptionsController;
use App\Http\Controllers\Api\V1\ClaimController;
use App\Http\Controllers\Api\V1\ListRestaurantsController;
use App\Http\Controllers\Api\V1\LoyaltyController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PrivacyController;
use App\Http\Controllers\Api\V1\RestaurantAdminController;
use App\Http\Controllers\Api\V1\RestaurantOnboardingController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StatisticsController;
use Illuminate\Support\Facades\Route;

// Éviter les conversions SQL/PHP invalides avant le contrôle de la ressource.
foreach (['restaurant', 'order', 'item', 'user', 'claim', 'reply', 'account', 'campaign', 'review', 'privacyRequest', 'payment'] as $parameter) {
    Route::pattern($parameter, '[1-9][0-9]{0,17}');
}

// L'IPN n'a pas de session navigateur ; sa preuve vient de la vérification serveur EasyPay.
Route::post('/v1/payments/easypay/ipn', [PaymentController::class, 'notify'])->middleware('throttle:60,1');

// Sessions natives Laravel + CSRF ; aucune clé d'API ni rôle dans le stockage du navigateur.
Route::prefix('v1')->middleware(['web', 'throttle:api'])->group(function (): void {
    Route::get('restaurants', ListRestaurantsController::class)->name('api.v1.restaurants.index');
    Route::get('restaurants/{restaurant}/menu', [MenuController::class, 'index']);
    Route::get('restaurants/{restaurant}/checkout-options', CheckoutOptionsController::class);
    Route::get('restaurants/{restaurant}/campaigns', [CampaignController::class, 'index']);
    Route::get('restaurants/{restaurant}/reviews', [ReviewController::class, 'index']);
    Route::get('restaurants/{restaurant}/ranking', [StatisticsController::class, 'ranking']);
    Route::get('auth/csrf-cookie', [AuthController::class, 'csrf']);

    Route::middleware('throttle:authentication')->group(function (): void {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/forgot-password', [AuthController::class, 'forgot']);
        Route::post('auth/reset-password', [AuthController::class, 'reset']);
    });

    Route::middleware(['auth', 'auth.session', 'active.account', 'api.boundaries'])->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::patch('me/profile', [PrivacyController::class, 'profile']);
        Route::patch('me/credentials', [AccountCredentialsController::class, 'update'])->middleware('throttle:authentication');
        Route::post('me/restaurants', [RestaurantOnboardingController::class, 'store'])->middleware('throttle:10,1');
        Route::post('me/data-export', [PrivacyController::class, 'export']);
        Route::post('me/erasure-requests', [PrivacyController::class, 'requestErasure']);
        Route::post('restaurants/{restaurant}/order-quotes', [OrderController::class, 'quote']);
        Route::post('restaurants/{restaurant}/orders', [OrderController::class, 'store']);
        Route::get('me/orders', [OrderController::class, 'mine']);
        Route::get('me/orders/{order}', [OrderController::class, 'ownDetail']);
        Route::get('restaurants/{restaurant}/orders', [OrderController::class, 'restaurant']);
        Route::get('restaurants/{restaurant}/orders/{order}', [OrderController::class, 'staffDetail']);
        Route::post('restaurants/{restaurant}/orders/{order}/transitions', [OrderController::class, 'transition']);
        Route::post('restaurants/{restaurant}/categories', [MenuController::class, 'category']);
        Route::post('restaurants/{restaurant}/items', [MenuController::class, 'store']);
        Route::patch('restaurants/{restaurant}/items/{item}', [MenuController::class, 'update']);
        Route::patch('restaurants/{restaurant}/items/{item}/availability', [MenuController::class, 'availability']);
        Route::post('restaurants/{restaurant}/items/{item}/archive', [MenuController::class, 'archive']);
        Route::patch('restaurants/{restaurant}/settings', [RestaurantAdminController::class, 'settings']);
        Route::get('restaurants/{restaurant}/settings', [RestaurantAdminController::class, 'showSettings']);
        Route::patch('restaurants/{restaurant}/hours', [RestaurantAdminController::class, 'hours']);
        Route::get('restaurants/{restaurant}/employees', [StaffController::class, 'index']);
        Route::post('restaurants/{restaurant}/managers', [StaffController::class, 'assignManager']);
        Route::post('restaurants/{restaurant}/employees', [StaffController::class, 'store']);
        Route::patch('restaurants/{restaurant}/employees/{user}', [StaffController::class, 'update']);
        Route::post('me/orders/{order}/claims', [ClaimController::class, 'store']);
        Route::get('me/claims', [ClaimController::class, 'mine']);
        Route::get('me/claims/{claim}', [ClaimController::class, 'ownDetail']);
        Route::get('restaurants/{restaurant}/claims', [ClaimController::class, 'restaurant']);
        Route::get('restaurants/{restaurant}/claims/{claim}', [ClaimController::class, 'detail']);
        Route::post('restaurants/{restaurant}/claims/{claim}/replies', [ClaimController::class, 'reply']);
        Route::post('restaurants/{restaurant}/claims/{claim}/replies/{reply}/review', [ClaimController::class, 'review']);
        Route::get('restaurants/{restaurant}/statistics', [StatisticsController::class, 'restaurant']);
        Route::post('restaurants/{restaurant}/loyalty-program', [LoyaltyController::class, 'program']);
        Route::get('restaurants/{restaurant}/loyalty-program', [LoyaltyController::class, 'showProgram']);
        Route::get('me/loyalty-accounts', [LoyaltyController::class, 'accounts']);
        Route::get('me/loyalty-accounts/{account}/entries', [LoyaltyController::class, 'entries']);
        Route::post('restaurants/{restaurant}/referral-code', [LoyaltyController::class, 'referralCode']);
        Route::post('me/referrals', [LoyaltyController::class, 'refer']);
        Route::get('me/referrals', [LoyaltyController::class, 'referrals']);
        Route::post('restaurants/{restaurant}/campaigns', [CampaignController::class, 'store']);
        Route::post('restaurants/{restaurant}/campaigns/{campaign}/deactivate', [CampaignController::class, 'deactivate']);
        Route::post('restaurants/{restaurant}/campaigns/{campaign}/participate', [CampaignController::class, 'participate']);
        Route::post('me/orders/{order}/review', [ReviewController::class, 'store']);
        Route::post('restaurants/{restaurant}/reviews/{review}/hide', [ReviewController::class, 'hide']);
        Route::post('me/orders/{order}/payment', [PaymentController::class, 'start']);
        Route::post('me/orders/{order}/payment/verify', [PaymentController::class, 'verify']);
        Route::post('platform/restaurants', [RestaurantAdminController::class, 'store']);
        Route::patch('platform/restaurants/{restaurant}', [RestaurantAdminController::class, 'platformUpdate']);
        Route::post('platform/restaurants/{restaurant}/managers', [StaffController::class, 'manager']);
        Route::get('platform/statistics', [StatisticsController::class, 'platform']);
        Route::post('platform/loyalty-program', [LoyaltyController::class, 'platformProgram']);
        Route::get('platform/privacy-requests', [PrivacyController::class, 'requests']);
        Route::patch('platform/privacy-requests/{privacyRequest}', [PrivacyController::class, 'process']);
        Route::post('platform/privacy-requests/{privacyRequest}/anonymize', [PrivacyController::class, 'anonymize']);
        Route::post('platform/payments/{payment}/reconcile', [PaymentController::class, 'reconcile']);
    });
});
