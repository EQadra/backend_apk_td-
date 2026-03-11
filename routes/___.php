<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserRoleController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\UserPermissionController;

use App\Http\Controllers\Api\AssociationController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\LawyerController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\NewsController;

/*
|--------------------------------------------------------------------------
| AUTH (JWT)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {

    // 🔓 Públicas
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    // 🔐 Protegidas JWT
    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

/*
|--------------------------------------------------------------------------
| PROTECTED API (JWT)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | USERS
    |--------------------------------------------------------------------------
    */
    Route::apiResource('users', UserController::class);

    Route::prefix('users')->group(function () {
        Route::post('{user}/roles/assign', [UserRoleController::class, 'assignRole']);
        Route::post('{user}/roles/revoke', [UserRoleController::class, 'revokeRole']);
        Route::get('{user}/roles', [UserRoleController::class, 'roles']);

        Route::post('{user}/permissions/assign', [UserPermissionController::class, 'givePermission']);
        Route::post('{user}/permissions/revoke', [UserPermissionController::class, 'revokePermission']);
        Route::get('{user}/permissions', [UserPermissionController::class, 'permissions']);
    });

    /*
    |--------------------------------------------------------------------------
    | ROLES & PERMISSIONS (ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {

        Route::apiResource('roles', RoleController::class)->only(['index', 'store']);

        Route::prefix('roles')->group(function () {
            Route::get('{role}/permissions', [RolePermissionController::class, 'permissions']);
            Route::put('{role}/permissions', [RolePermissionController::class, 'update']);
            Route::post('{role}/permissions/assign', [RolePermissionController::class, 'assignPermission']);
            Route::post('{role}/permissions/revoke', [RolePermissionController::class, 'revokePermission']);
        });

        Route::apiResource('permissions', PermissionController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD & REPORTS
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->get('admin/dashboard', fn () =>
        response()->json(['message' => 'Welcome Admin'])
    );

    Route::middleware('permission:view reports')->get('reports', fn () =>
        response()->json(['message' => 'Reports view'])
    );


    
    /*
    |--------------------------------------------------------------------------
    | DOMAIN MODULES
    |--------------------------------------------------------------------------
    */

    Route::get('/associations/latest', [AssociationController::class, 'latest']);
    Route::get('/doctors/latest', [DoctorController::class, 'latest']);
    Route::get('/lawyers/latest', [LawyerController::class, 'latest']);
    Route::get('/shops/latest', [ShopController::class, 'latest']);
    Route::get('/news/latest',[ NewsController::class, 'latest']);

    /*
    |--------------------------------------------------------------------------
    | DOMAIN MODULES
    |--------------------------------------------------------------------------
    */



    
    Route::apiResource('comments', CommentController::class)->only(['index','store','show','destroy']);
    Route::apiResource('doctors', DoctorController::class);
    Route::apiResource('lawyers', LawyerController::class);
    Route::apiResource('posts', PostController::class)->only(['index','store','show','destroy']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('shops', ShopController::class);
    Route::apiResource('news', NewsController::class);
    Route::apiResource('feedbacks', FeedbackController::class)->only(['index','store','show','destroy']);
     /*
    |--------------------------------------------------------------------------
    |  MODULES
    |--------------------------------------------------------------------------
    */
Route::get('/associations/latest', [AssociationController::class, 'latest']);
Route::get('/doctors/latest', [DoctorController::class, 'latest']);
Route::get('/lawyers/latest', [LawyerController::class, 'latest']);
Route::get('/shops/latest', [ShopController::class, 'latest']);

  /*
    |--------------------------------------------------------------------------
    |  MODULES
    |--------------------------------------------------------------------------
    */

    Route::get('/products/latest', [ProductController::class, 'latest']);
    Route::get('/services/latest', [ServiceController::class, 'latest']);

  /*
    |--------------------------------------------------------------------------
    | DOMAIN MODULES
    |--------------------------------------------------------------------------
    */


    
});


  /*
    |--------------------------------------------------------------------------
    | DOMAIN MODULES
    |--------------------------------------------------------------------------
    */


Route::middleware('auth:api')->group(function () {

    Route::get('/associations/me', [AssociationController::class, 'me']); // 👈 AGREGAR ESTO

    Route::apiResource('associations', AssociationController::class);

});