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
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('update-avatar', [AuthController::class, 'updateAvatar']);
        Route::delete('delete-avatar', [AuthController::class, 'deleteAvatar']);
    });
});

/*
|--------------------------------------------------------------------------
| 🔥 RUTAS PÚBLICAS (LECTURA - SIN TOKEN)
|--------------------------------------------------------------------------
*/

// BÚSQUEDAS
Route::get('lawyers/search', [LawyerController::class, 'search']);
Route::get('doctors/search', [DoctorController::class, 'search']);
Route::get('associations/search', [AssociationController::class, 'search']);
Route::get('shops/search', [ShopController::class, 'search']);
Route::get('news/search', [NewsController::class, 'search']);
Route::get('services/search', [ServiceController::class, 'search']);
Route::get('products/search', [ProductController::class, 'search']);

// LATEST
Route::get('associations/latest', [AssociationController::class, 'latest']);
Route::get('doctors/latest', [DoctorController::class, 'latest']);
Route::get('lawyers/latest', [LawyerController::class, 'latest']);
Route::get('shops/latest', [ShopController::class, 'latest']);
Route::get('products/latest', [ProductController::class, 'latest']);
Route::get('services/latest', [ServiceController::class, 'latest']);
Route::get('news/latest', [NewsController::class, 'latest']);
Route::get('feedbacks/latest', [FeedbackController::class, 'latest']);

// HOME
Route::get('services/home', [ServiceController::class, 'home']);
Route::get('feedbacks/home', [FeedbackController::class, 'home']);
Route::get('news/home', [NewsController::class, 'home']);
Route::get('posts/home', [PostController::class, 'home']);

// BY SPECIALTY
Route::get('doctors/specialty/{specialty}', [DoctorController::class, 'bySpecialty']);
Route::get('lawyers/specialty/{specialty}', [LawyerController::class, 'bySpecialty']);

// NEWS - LECTURA
Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('latest', [NewsController::class, 'latest']);
    Route::get('home', [NewsController::class, 'home']);
    Route::get('{id}', [NewsController::class, 'show']);
});

// POSTS - LECTURA
Route::prefix('posts')->group(function () {
    Route::get('/', [PostController::class, 'index']);
    Route::get('/home', [PostController::class, 'home']);
    Route::get('/search', [PostController::class, 'search']);
    Route::get('/{id}', [PostController::class, 'show']);
    Route::get('/{id}/likes', [PostController::class, 'getLikes']);
    Route::get('/{id}/comments', [CommentController::class, 'getPostComments']);
});

// SERVICES - LECTURA
Route::prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('home', [ServiceController::class, 'home']);
    Route::get('latest', [ServiceController::class, 'latest']);
    Route::get('{id}', [ServiceController::class, 'show']);
    Route::get('{serviceId}/comments', [CommentController::class, 'getServiceComments']);
});

// FEEDBACKS - LECTURA
Route::prefix('feedbacks')->group(function () {
    Route::get('/', [FeedbackController::class, 'index']);
    Route::get('home', [FeedbackController::class, 'home']);
    Route::get('latest', [FeedbackController::class, 'latest']);
    Route::get('{id}', [FeedbackController::class, 'show']);
});

// PRODUCTS - LECTURA
Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('latest', [ProductController::class, 'latest']);
    Route::get('{id}', [ProductController::class, 'show']);
    Route::get('{productId}/comments', [CommentController::class, 'getProductComments']);
});

// SHOP - LECTURA
Route::prefix('shops')->group(function () {
    Route::get('/', [ShopController::class, 'index']);
    Route::get('latest', [ShopController::class, 'latest']);
    Route::get('{id}', [ShopController::class, 'show']);
});

// DOCTORS - LECTURA
Route::prefix('doctors')->group(function () {
    Route::get('/', [DoctorController::class, 'index']);
    Route::get('latest', [DoctorController::class, 'latest']);
    Route::get('{id}', [DoctorController::class, 'show']);
});

// LAWYERS - LECTURA
Route::prefix('lawyers')->group(function () {
    Route::get('/', [LawyerController::class, 'index']);
    Route::get('latest', [LawyerController::class, 'latest']);
    Route::get('{id}', [LawyerController::class, 'show']);
});

// ASSOCIATIONS - LECTURA
Route::prefix('associations')->group(function () {
    Route::get('/', [AssociationController::class, 'index']);
    Route::get('latest', [AssociationController::class, 'latest']);
    Route::get('{id}', [AssociationController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| 🔒 RUTAS PROTEGIDAS (ESCRITURA Y DATOS DEL USUARIO)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {

    /*
    |----------------------------------------------------------------------
    | USERS
    |----------------------------------------------------------------------
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
    |----------------------------------------------------------------------
    | ADMIN
    |----------------------------------------------------------------------
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

    Route::middleware('role:admin')->get('admin/dashboard', fn () =>
        response()->json(['message' => 'Welcome Admin'])
    );

    Route::middleware('permission:view reports')->get('reports', fn () =>
        response()->json(['message' => 'Reports view'])
    );

    /*
    |----------------------------------------------------------------------
    | ME - Perfil del usuario
    |----------------------------------------------------------------------
    */
    Route::get('associations/me', [AssociationController::class, 'me']);
    Route::get('doctors/me', [DoctorController::class, 'me']);
    Route::get('lawyers/me', [LawyerController::class, 'me']);
    Route::get('shops/me', [ShopController::class, 'me']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - DOCTORS
    |----------------------------------------------------------------------
    */
    Route::post('doctors', [DoctorController::class, 'store']);
    Route::put('doctors/{id}', [DoctorController::class, 'update']);
    Route::delete('doctors/{id}', [DoctorController::class, 'destroy']);
    Route::post('doctors/update-image', [DoctorController::class, 'updateImage']);
    Route::get('doctors/image', [DoctorController::class, 'getImage']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - LAWYERS
    |----------------------------------------------------------------------
    */
    Route::post('lawyers', [LawyerController::class, 'store']);
    Route::put('lawyers/{id}', [LawyerController::class, 'update']);
    Route::delete('lawyers/{id}', [LawyerController::class, 'destroy']);
    Route::post('lawyers/update-image', [LawyerController::class, 'updateImage']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - ASSOCIATIONS
    |----------------------------------------------------------------------
    */
    Route::post('associations', [AssociationController::class, 'store']);
    Route::put('associations/{id}', [AssociationController::class, 'update']);
    Route::delete('associations/{id}', [AssociationController::class, 'destroy']);
    Route::post('associations/update-image', [AssociationController::class, 'updateImage']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - SHOPS
    |----------------------------------------------------------------------
    */
    Route::post('shops', [ShopController::class, 'store']);
    Route::put('shops/{id}', [ShopController::class, 'update']);
    Route::delete('shops/{id}', [ShopController::class, 'destroy']);
    Route::post('shops/update-image', [ShopController::class, 'updateImage']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - SERVICES
    |----------------------------------------------------------------------
    */
    Route::post('services', [ServiceController::class, 'store']);
    Route::put('services/{id}', [ServiceController::class, 'update']);
    Route::delete('services/{id}', [ServiceController::class, 'destroy']);
    Route::post('services/{id}/image', [ServiceController::class, 'updateImage']);
    Route::delete('services/{id}/image', [ServiceController::class, 'deleteImage']);
    Route::post('services/{serviceId}/comments', [CommentController::class, 'storeServiceComment']);
    Route::delete('service-comments/{id}', [CommentController::class, 'deleteServiceComment']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - PRODUCTS
    |----------------------------------------------------------------------
    */
    Route::post('products', [ProductController::class, 'store']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::delete('products/{id}', [ProductController::class, 'destroy']);
    Route::post('products/{productId}/comments', [CommentController::class, 'storeProductComment']);
    Route::delete('product-comments/{id}', [CommentController::class, 'deleteProductComment']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - FEEDBACKS
    |----------------------------------------------------------------------
    */
    Route::post('feedbacks', [FeedbackController::class, 'store']);
    Route::delete('feedbacks/{id}', [FeedbackController::class, 'destroy']);

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - NEWS
    |----------------------------------------------------------------------
    */
    Route::prefix('news')->group(function () {
        Route::post('/', [NewsController::class, 'store']);
        Route::put('{id}', [NewsController::class, 'update']);
        Route::delete('{id}', [NewsController::class, 'destroy']);

        Route::get('my/latest', [NewsController::class, 'myLatestNews']);
        Route::get('my/all', [NewsController::class, 'myAllNews']);
        Route::get('my/liked', [NewsController::class, 'myLikedNews']);

        Route::post('{id}/comments', [NewsController::class, 'addComment']);
        Route::post('{id}/like', [NewsController::class, 'toggleLike']);
        Route::get('{id}/check-like', [NewsController::class, 'checkLike']);

        Route::middleware('role:admin')->get('debug/{userId}', [NewsController::class, 'debugUserNews']);
    });

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - POSTS
    |----------------------------------------------------------------------
    */
    Route::prefix('posts')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::put('{id}', [PostController::class, 'update']);
        Route::delete('{id}', [PostController::class, 'destroy']);

        Route::post('{id}/image', [PostController::class, 'updateImage']);
        Route::get('my/latest', [PostController::class, 'myLatestPosts']);

        Route::post('{id}/comments', [PostController::class, 'addComment']);
        Route::delete('comments/{id}', [PostController::class, 'deleteComment']);

        Route::post('{id}/like', [PostController::class, 'toggleLike']);
    });

    /*
    |----------------------------------------------------------------------
    | ESCRITURA - COMMENTS
    |----------------------------------------------------------------------
    */
    Route::post('comments', [CommentController::class, 'store']);
    Route::delete('comments/{id}', [CommentController::class, 'destroy']);

    /*
    |----------------------------------------------------------------------
    | FAVORITES
    |----------------------------------------------------------------------
    */
    Route::prefix('favorites')->group(function () {
        Route::post('toggle', [FavoriteController::class, 'toggle']);
        Route::get('my', [FavoriteController::class, 'myFavorites']);
        Route::get('type/{type}', [FavoriteController::class, 'byType']);
        Route::get('check/{type}/{id}', [FavoriteController::class, 'check']);
    });

    /*
    |----------------------------------------------------------------------
    | HISTORY
    |----------------------------------------------------------------------
    */
    Route::prefix('history')->group(function () {
        Route::post('store', [HistoryController::class, 'store']);
        Route::get('my', [HistoryController::class, 'myHistory']);
        Route::get('type/{type}', [HistoryController::class, 'byType']);
        Route::get('most-viewed/{type}', [HistoryController::class, 'mostViewed']);
        Route::delete('clear', [HistoryController::class, 'clear']);
    });

    /*
    |----------------------------------------------------------------------
    | MIS PUBLICACIONES
    |----------------------------------------------------------------------
    */
    Route::get('my-posts/latestPosts', [PostController::class, 'myLatestPosts']);
    Route::get('my-services/latestServices', [ServiceController::class, 'myLatestServices']);
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