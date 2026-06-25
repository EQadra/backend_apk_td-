<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UserRoleController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\RolePermissionController;
use App\Http\Controllers\API\UserPermissionController;

use App\Http\Controllers\API\AssociationController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\FeedbackController;
use App\Http\Controllers\API\LawyerController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\ShopController;
use App\Http\Controllers\API\NewsController;

use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\HistoryController;

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
| 🔥 RUTAS PÚBLICAS (NO REQUIEREN AUTENTICACIÓN)
|--------------------------------------------------------------------------
*/

// 🔥 BÚSQUEDAS - PÚBLICAS
Route::get('lawyers/search', [LawyerController::class, 'search']);
Route::get('doctors/search', [DoctorController::class, 'search']);
Route::get('associations/search', [AssociationController::class, 'search']);
Route::get('shops/search', [ShopController::class, 'search']);

// 🔥 ÚLTIMOS (LATEST) - PÚBLICOS
Route::get('associations/latest', [AssociationController::class, 'latest']);
Route::get('doctors/latest', [DoctorController::class, 'latest']);
Route::get('lawyers/latest', [LawyerController::class, 'latest']);
Route::get('shops/latest', [ShopController::class, 'latest']);
Route::get('products/latest', [ProductController::class, 'latest']);
Route::get('services/latest', [ServiceController::class, 'latest']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (REQUIEREN AUTENTICACIÓN)
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
    | IMAGE UPLOAD (TRAIT ENDPOINTS) - 🔥 ESTAS RUTAS YA NO SE USAN
    |--------------------------------------------------------------------------
    | Estas rutas usaban el método uploadImageByRole del Controller base.
    | Ahora usamos uploadImageToProduction en cada controlador.
    | Puedes ELIMINAR estas rutas o dejarlas comentadas.
    */
    // Route::post('doctors/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
    //     $doctor = \App\Models\Doctor::findOrFail($id);
    //     return app(\App\Http\Controllers\Controller::class)
    //         ->uploadImageByRole($request, $doctor);
    // });

    // Route::post('lawyers/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
    //     $lawyer = \App\Models\Lawyer::findOrFail($id);
    //     return app(\App\Http\Controllers\Controller::class)
    //         ->uploadImageByRole($request, $lawyer);
    // });

    // Route::post('shops/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
    //     $shop = \App\Models\Shop::findOrFail($id);
    //     return app(\App\Http\Controllers\Controller::class)
    //         ->uploadImageByRole($request, $shop);
    // });

    // Route::post('associations/upload-image/{id}', function (\Illuminate\Http\Request $request, $id) {
    //     $association = \App\Models\Association::findOrFail($id);
    //     return app(\App\Http\Controllers\Controller::class)
    //         ->uploadImageByRole($request, $association);
    // });

    /*
    |--------------------------------------------------------------------------
    | CUSTOM ROUTES (PROTEGIDAS)
    |--------------------------------------------------------------------------
    */
    Route::get('associations/me', [AssociationController::class, 'me']);
    Route::get('doctors/me', [DoctorController::class, 'me']);
    Route::get('lawyers/me', [LawyerController::class, 'me']);
    Route::get('shops/me', [ShopController::class, 'me']);

    /*
    |--------------------------------------------------------------------------
    | RESOURCES (PROTEGIDAS)
    |--------------------------------------------------------------------------
    */
    Route::apiResource('associations', AssociationController::class);
    Route::apiResource('doctors', DoctorController::class);
    Route::apiResource('lawyers', LawyerController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('shops', ShopController::class);

    /*
    |--------------------------------------------------------------------------
    | NEWS ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('news')->group(function () {
        Route::get('/', [NewsController::class, 'index']);
        Route::get('latest', [NewsController::class, 'latest']);
        Route::get('home', [NewsController::class, 'home']);
        Route::get('index', [NewsController::class, 'index']);
        Route::get('{id}', [NewsController::class, 'show']);
    });

    Route::middleware('auth:api')->prefix('news')->group(function () {
        Route::get('my/latest', [NewsController::class, 'myLatestNews']);
        Route::get('my/all', [NewsController::class, 'myAllNews']);
        Route::get('my/liked', [NewsController::class, 'myLikedNews']);
        
        Route::post('/', [NewsController::class, 'store']);
        Route::put('{id}', [NewsController::class, 'update']);
        Route::delete('{id}', [NewsController::class, 'destroy']);
        
        Route::post('{id}/comments', [NewsController::class, 'addComment']);
        
        Route::post('{id}/like', [NewsController::class, 'toggleLike']);
        Route::get('{id}/check-like', [NewsController::class, 'checkLike']);
        
        Route::get('debug/{userId}', [NewsController::class, 'debugUserNews']);


        // Dentro del grupo 'auth:api'
Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);
    });

    /*
    |--------------------------------------------------------------------------
    | COMMENTS, POSTS, FEEDBACKS
    |--------------------------------------------------------------------------
    */
    Route::apiResource('comments', CommentController::class)->only(['index', 'store', 'show', 'destroy']);
    Route::apiResource('feedbacks', FeedbackController::class)->only(['index', 'store', 'show', 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | POSTS ROUTES
    |--------------------------------------------------------------------------
    */
    Route::prefix('posts')->group(function () {
        Route::get('/', [PostController::class, 'index']);
        Route::get('/home', [PostController::class, 'home']);
        Route::get('/search', [PostController::class, 'search']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::get('/{id}/likes', [PostController::class, 'getLikes']);
        Route::get('/{id}/comments', [CommentController::class, 'getPostComments']);
    });

    Route::middleware('auth:api')->prefix('posts')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::put('/{id}', [PostController::class, 'update']);
        Route::delete('/{id}', [PostController::class, 'destroy']);
        
        Route::post('/{id}/comments', [PostController::class, 'addComment']);
        Route::delete('/comments/{id}', [PostController::class, 'deleteComment']);
        
        Route::post('/{id}/like', [PostController::class, 'toggleLike']);
    });

    /*
    |--------------------------------------------------------------------------
    | PRODUCTS ROUTES - COMENTARIOS
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('products')->group(function () {
        Route::get('/{productId}/comments', [CommentController::class, 'getProductComments']);
        Route::post('/{productId}/comments', [CommentController::class, 'storeProductComment']);
    });

    Route::delete('/product-comments/{id}', [CommentController::class, 'deleteProductComment']);

    /*
    |--------------------------------------------------------------------------
    | SERVICES ROUTES - COMENTARIOS ✅
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('services')->group(function () {
        Route::get('/{serviceId}/comments', [CommentController::class, 'getServiceComments']);
        Route::post('/{serviceId}/comments', [CommentController::class, 'storeServiceComment']);
    });

    Route::delete('/service-comments/{id}', [CommentController::class, 'deleteServiceComment']);

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZACIÓN DE IMÁGENES - 🔥 RUTAS CORRECTAS
    |--------------------------------------------------------------------------
    | Estas rutas usan los métodos updateImage de cada controlador
    | que ahora usan el trait UploadImage
    */
    Route::post('/doctor/image', [DoctorController::class, 'updateImage']);
    Route::post('/lawyer/image', [LawyerController::class, 'updateImage']);
    Route::post('/association/image', [AssociationController::class, 'updateImage']);
    Route::post('/shop/image', [ShopController::class, 'updateImage']);
    /*
    |--------------------------------------------------------------------------
    | 🔥 AVATAR DE USUARIO (DEBE ESTAR AQUÍ, NO DENTRO DE NEWS)
    |--------------------------------------------------------------------------
    */
    Route::post('/user/avatar', [AuthController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [AuthController::class, 'deleteAvatar']);
    /*
    |--------------------------------------------------------------------------
    | FAVORITES
    |--------------------------------------------------------------------------
    */
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
    Route::get('/favorites/my', [FavoriteController::class, 'myFavorites']);
    Route::get('/favorites/type/{type}', [FavoriteController::class, 'byType']);
    Route::get('/favorites/check/{type}/{id}', [FavoriteController::class, 'check']);

    /*
    |--------------------------------------------------------------------------
    | HISTORY
    |--------------------------------------------------------------------------
    */
    Route::post('/history/store', [HistoryController::class, 'store']);
    Route::get('/history/my', [HistoryController::class, 'myHistory']);
    Route::get('/history/type/{type}', [HistoryController::class, 'byType']);
    Route::get('/history/most-viewed/{type}', [HistoryController::class, 'mostViewed']);
    Route::delete('/history/clear', [HistoryController::class, 'clear']);

    /*
    |--------------------------------------------------------------------------
    | LATEST POSTS Y SERVICES
    |--------------------------------------------------------------------------
    */
    Route::get('/my-posts/latestPosts', [PostController::class, 'myLatestPosts']);
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