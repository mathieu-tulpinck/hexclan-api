<?php

declare(strict_types=1);

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTokenController;
use App\Http\Controllers\EventUserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PINCodeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TestGetController;
use App\Http\Controllers\TestPostController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

// Universal API routes - no auth - throttling.
Route::prefix(
    'api'
)->middleware([
    'api',
    'universal',
    InitializeTenancyByDomain::class,
    'throttle:open'
])->group(function () {
    Route::get('test', TestGetController::class); // To be commented out in production. 
    Route::post('test', TestPostController::class); // To be commented out in production.
    Route::get('welcome', function () {
        return response()->json(['data' => 'welcome'], Response::HTTP_OK);
    }); // To be commented out in production.

    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class);
    Route::put('pincode/{user}', PINCodeController::class); // Route used to update pin code
});

// Universal API routes - auth.
Route::prefix(
    'api'
)->middleware([
    'api',
    'universal',
    InitializeTenancyByDomain::class,
    'auth:sanctum'
])->group(function () {
    //Route::get('/token/refresh', [TokenController::class, 'refresh']);

    Route::get('users', [UserController::class, 'index'])->middleware('ability:*, write');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('ability:*, write');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('ability:*, write');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('ability:*');
});

// Tenant API routes - auth - actions expecting user tokens.
Route::prefix(
    'api'
)->middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'auth:sanctum'
])->group(function () {
    // This route is used to sync the user's role tokens between the server and the client.
    Route::post('token/sync', [EventTokenController::class, 'sync']);

    // This route is used to seed a new unprivileged user in the database.
    Route::post('users', [UserController::class, 'seed'])->middleware('ability:*, write');
    // This route is to activate or deactivate a user. The user's tokens are revoked upon deactivation.
    Route::post('users/{user}', [UserController::class, 'toggleIsActive'])->middleware('ability:*, write');

    Route::get('events', [EventController::class, 'index'])->middleware('ability:*, write');
    Route::post('events', [EventController::class, 'store'])->middleware('ability:*, write');
    Route::get('events/{event}', [EventController::class, 'show'])->middleware('ability:*, write');
    Route::put('events/{event}', [EventController::class, 'update'])->middleware('ability:*, write');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->middleware('ability:*');

    Route::get('bankaccounts', [BankAccountController::class, 'index'])->middleware('ability:*, write');
    Route::post('bankaccounts', [BankAccountController::class, 'store'])->middleware('ability:*, write');
    Route::get('bankaccounts/{bankaccount}', [BankAccountController::class, 'show'])->middleware('ability:*, write');
    Route::put('bankaccounts/{bankaccount}', [BankAccountController::class, 'update'])->middleware('ability:*, write');
    Route::delete('bankaccounts/{bankaccount}', [BankAccountController::class, 'destroy'])->middleware('ability:*');

    // This route is used to access the user events. Attaching, updating and detaching happen via event token authentication in order to identify the user role.
    Route::get('users/{user}/events', [UserController::class, 'events'])->middleware('ability:*, write, self');
});

// Tenant API routes - auth - actions expecting event tokens.
Route::prefix(
    'api'
)->middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    //'auth:sanctum',
])->group(function () {
    // This route should be visited prior to a sync with all the event tokens possessed by the client. 
    Route::post('token/purge', [EventTokenController::class, 'purge']);

    // This route is used to access the event users.
    Route::get('events/{event}/users', [EventController::class, 'users'])->middleware('ability:*, manager');

    // There routes are used to attach, update, and detach roles on the pivot table.
    Route::post('events/{event}/users', [EventUserController::class, 'store'])->middleware('ability:*, manager');
    Route::put('events/{event}/users/{user}', [EventUserController::class, 'update'])->middleware('ability:*, manager');
    Route::delete('events/{event}/users/{user}', [EventUserController::class, 'destroy'])->middleware('ability:*, manager'); // Detach is within scope of manager.

    // This route is used to access the event categories.
    Route::get('events/{event}/categories', [EventController::class, 'categories'])->middleware('ability:*, manager');

    Route::get('categories', [CategoryController::class, 'index'])->middleware('ability:*, manager');
    Route::post('events/{event}/categories', [CategoryController::class, 'store'])->middleware('ability:*, manager');
    Route::get('categories/{category}', [CategoryController::class, 'show'])->middleware('ability:*, manager');
    Route::put('events/{event}/categories/{category}', [CategoryController::class, 'update'])->middleware('ability:*, manager');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('ability:*');

    // This route is used to access the category items.
    Route::get('categories/{category}/items', [ItemController::class, 'items'])->middleware('ability:*, manager');

    Route::get('items', [ItemController::class, 'index'])->middleware('ability:*, manager');
    Route::post('categories/{category}/items', [ItemController::class, 'store']); //->middleware('ability:*, manager');
    Route::get('items/{item}', [ItemController::class, 'show']); //->middleware('ability:*, manager');
    Route::put('categories/{category}/items/{item}', [ItemController::class, 'update'])->middleware('ability:*, manager');
    Route::delete('items/{item}', [ItemController::class, 'destroy'])->middleware('ability:*');
});

Route::fallback(function () {
    return response()->json(['message' => 'This route does not exist.'], Response::HTTP_NOT_FOUND);
});