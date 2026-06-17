<?php

use Illuminate\Support\Facades\Route;
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
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

/*
|--------------------------------------------------------------------------
| PROTECTED API
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
    | ADMIN
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('roles', RoleController::class)->only(['index', 'store']);
        Route::apiResource('permissions', PermissionController::class);

        Route::prefix('roles')->group(function () {
            Route::get('{role}/permissions', [RolePermissionController::class, 'permissions']);
            Route::put('{role}/permissions', [RolePermissionController::class, 'update']);
            Route::post('{role}/permissions/assign', [RolePermissionController::class, 'assignPermission']);
            Route::post('{role}/permissions/revoke', [RolePermissionController::class, 'revokePermission']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
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
    | IMAGE UPLOAD (TRAIT ENDPOINTS)
    |--------------------------------------------------------------------------
    | 👇 AQUÍ ESTÁ LO QUE TE FALTABA
    |--------------------------------------------------------------------------
    */
    Route::post('doctors/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
        $doctor = \App\Models\Doctor::findOrFail($id);
        return app(\App\Http\Controllers\Controller::class)
            ->uploadImageByRole($request, $doctor);
    });

    Route::post('lawyers/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
        $lawyer = \App\Models\Lawyer::findOrFail($id);
        return app(\App\Http\Controllers\Controller::class)
            ->uploadImageByRole($request, $lawyer);
    });

    Route::post('shops/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
        $shop = \App\Models\Shop::findOrFail($id);
        return app(\App\Http\Controllers\Controller::class)
            ->uploadImageByRole($request, $shop);
    });

    Route::post('associations/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
        $association = \App\Models\Association::findOrFail($id);
        return app(\App\Http\Controllers\Controller::class)
            ->uploadImageByRole($request, $association);
    });

    /*
    |--------------------------------------------------------------------------
    | CUSTOM ROUTES
    |--------------------------------------------------------------------------
    */

    // latest
    Route::get('associations/latest', [AssociationController::class, 'latest']);
    Route::get('doctors/latest', [DoctorController::class, 'latest']);
    Route::get('lawyers/latest', [LawyerController::class, 'latest']);
    Route::get('shops/latest', [ShopController::class, 'latest']);
    Route::get('products/latest', [ProductController::class, 'latest']);
    Route::get('services/latest', [ServiceController::class, 'latest']);

    // me
    Route::get('associations/me', [AssociationController::class, 'me']);
    Route::get('doctors/me', [DoctorController::class, 'me']);
    Route::get('lawyers/me', [LawyerController::class, 'me']);
    Route::get('shops/me', [ShopController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | RESOURCES
    |--------------------------------------------------------------------------
    */
    Route::apiResource('associations', AssociationController::class);
    Route::apiResource('doctors', DoctorController::class);
    Route::apiResource('lawyers', LawyerController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('shops', ShopController::class);

    Route::get('news/home', [NewsController::class, 'home']);
    Route::get('news/latest', [NewsController::class, 'latest']);
    Route::post('news/{id}/comments', [NewsController::class, 'addComment']);
    Route::apiResource('news', NewsController::class);

    Route::apiResource('comments', CommentController::class)->only(['index','store','show','destroy']);
    Route::apiResource('posts', PostController::class)->only(['index','store','show','destroy']);
    Route::apiResource('feedbacks', FeedbackController::class)->only(['index','store','show','destroy']);


    

/*
|--------------------------------------------------------------------------
| actualización de imágenes (si quieres usar los endpoints específicos en cada controlador, puedes eliminar esta sección)
|--------------------------------------------------------------------------
*/
    Route::post(
        '/doctor/image',
        [DoctorController::class, 'updateImage']
    );

    Route::post(
        '/lawyer/image',
        [LawyerController::class, 'updateImage']
    );

    Route::post(
        '/association/image',
        [AssociationController::class, 'updateImage']
    );

    Route::post(
        '/shop/image',
        [ShopController::class, 'updateImage']
    );


    /*
|--------------------------------------------------------------------------
| 
|--------------------------------------------------------------------------
*/
   /*
    |--------------------------------------------------------------------------
    | FAVORITES
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/favorites/toggle',
        [FavoriteController::class, 'toggle']
    );

    Route::get(
        '/favorites/my',
        [FavoriteController::class, 'myFavorites']
    );

    Route::get(
        '/favorites/type/{type}',
        [FavoriteController::class, 'byType']
    );

    Route::get(
        '/favorites/check/{type}/{id}',
        [FavoriteController::class, 'check']
    );

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/history/store',
        [HistoryController::class, 'store']
    );

    Route::get(
        '/history/my',
        [HistoryController::class, 'myHistory']
    );

    Route::get(
        '/history/type/{type}',
        [HistoryController::class, 'byType']
    );

    Route::get(
        '/history/most-viewed/{type}',
        [HistoryController::class, 'mostViewed']
    );

    Route::delete(
        '/history/clear',
        [HistoryController::class, 'clear']
    );

       /*
    |--------------------------------------------------------------------------
    | Lates
    |--------------------------------------------------------------------------
    */
        Route::get('/my-news/latestNews', [NewsController::class, 'myLatestNews']);
        Route::get('/my-posts/latestPosts', [PostController  ::class, 'myLatestPosts']);
        Route::get('/my-services/latestServices', [ServiceController::class, 'myLatestServices']);
});

/*
|--------------------------------------------------------------------------
| TEST
|--------------------------------------------------------------------------
*/
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API funcionando'
    ]);
});



