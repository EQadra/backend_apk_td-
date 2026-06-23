<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Post;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * List all comments (admin or public).
     */
    public function index()
    {
        $comments = Comment::with(['user', 'commentable'])->latest()->get();
        return response()->json($comments, 200);
    }

    /**
     * Store a new comment for a Post, Product, or Service.
     */
    public function store(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Comment successfully added.',
            'data' => $comment
        ], 201);
    }

    /**
     * Show a single comment.
     */
    public function show($id)
    {
        $comment = Comment::with(['user'])->findOrFail($id);
        return response()->json($comment, 200);
    }

    /**
     * Delete comment (only owner or admin).
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.'], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | POST COMMENTS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener comentarios de un post específico
     * GET /api/posts/{postId}/comments
     */
    public function getPostComments($postId)
    {
        try {
            // Verificar que el post existe
            $post = Post::findOrFail($postId);
            
            // Obtener comentarios del post con relaciones
            $comments = Comment::where([
                'commentable_type' => 'App\Models\Post',
                'commentable_id' => $postId
            ])
            ->with(['user'])
            ->latest()
            ->get();

            return response()->json([
                'data' => $comments,
                'message' => 'Comentarios obtenidos correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener comentarios del post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar comentario a un post (usado por PostController)
     * POST /api/posts/{postId}/comments
     */
    public function storePostComment(Request $request, $postId)
    {
        try {
            // Verificar que el post existe
            $post = Post::findOrFail($postId);

            // Validar el contenido del comentario
            $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            // Crear el comentario
            $comment = Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => 'App\Models\Post',
                'commentable_id' => $postId,
                'content' => $request->content,
            ]);

            // Cargar la relación user para la respuesta
            $comment->load('user');

            return response()->json([
                'message' => 'Comentario agregado correctamente al post',
                'data' => $comment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al agregar comentario al post',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar comentario de un post
     * DELETE /api/post-comments/{id}
     */
    public function deletePostComment($id)
    {
        try {
            $comment = Comment::findOrFail($id);

            // Verificar que el comentario pertenece a un post
            if ($comment->commentable_type !== 'App\Models\Post') {
                return response()->json([
                    'error' => 'Este comentario no pertenece a un post'
                ], 400);
            }

            // Verificar autorización: solo el dueño o admin pueden eliminar
            if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'error' => 'No autorizado para eliminar este comentario'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'message' => 'Comentario eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar comentario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PRODUCT COMMENTS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener comentarios de un producto específico
     * GET /api/products/{productId}/comments
     */
    public function getProductComments($productId)
    {
        try {
            // Verificar que el producto existe
            $product = Product::findOrFail($productId);
            
            // Obtener comentarios del producto con relaciones
            $comments = Comment::where([
                'commentable_type' => 'App\Models\Product',
                'commentable_id' => $productId
            ])
            ->with(['user'])
            ->latest()
            ->get();

            return response()->json([
                'data' => $comments,
                'message' => 'Comentarios obtenidos correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener comentarios del producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar comentario a un producto
     * POST /api/products/{productId}/comments
     */
    public function storeProductComment(Request $request, $productId)
    {
        try {
            // Verificar que el producto existe
            $product = Product::findOrFail($productId);

            // Validar el contenido del comentario
            $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            // Crear el comentario
            $comment = Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => 'App\Models\Product',
                'commentable_id' => $productId,
                'content' => $request->content,
            ]);

            // Cargar la relación user para la respuesta
            $comment->load('user');

            return response()->json([
                'message' => 'Comentario agregado correctamente al producto',
                'data' => $comment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al agregar comentario al producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar comentario de un producto
     * DELETE /api/product-comments/{id}
     */
    public function deleteProductComment($id)
    {
        try {
            $comment = Comment::findOrFail($id);

            // Verificar que el comentario pertenece a un producto
            if ($comment->commentable_type !== 'App\Models\Product') {
                return response()->json([
                    'error' => 'Este comentario no pertenece a un producto'
                ], 400);
            }

            // Verificar autorización: solo el dueño o admin pueden eliminar
            if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'error' => 'No autorizado para eliminar este comentario'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'message' => 'Comentario eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar comentario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SERVICE COMMENTS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtener comentarios de un servicio específico
     * GET /api/services/{serviceId}/comments
     */
    public function getServiceComments($serviceId)
    {
        try {
            // Verificar que el servicio existe
            $service = Service::findOrFail($serviceId);
            
            // Obtener comentarios del servicio con relaciones
            $comments = Comment::where([
                'commentable_type' => 'App\Models\Service',
                'commentable_id' => $serviceId
            ])
            ->with(['user'])
            ->latest()
            ->get();

            return response()->json([
                'data' => $comments,
                'message' => 'Comentarios obtenidos correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener comentarios del servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar comentario a un servicio
     * POST /api/services/{serviceId}/comments
     */
    public function storeServiceComment(Request $request, $serviceId)
    {
        try {
            // Verificar que el servicio existe
            $service = Service::findOrFail($serviceId);

            // Validar el contenido del comentario
            $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            // Crear el comentario
            $comment = Comment::create([
                'user_id' => Auth::id(),
                'commentable_type' => 'App\Models\Service',
                'commentable_id' => $serviceId,
                'content' => $request->content,
            ]);

            // Cargar la relación user para la respuesta
            $comment->load('user');

            return response()->json([
                'message' => 'Comentario agregado correctamente al servicio',
                'data' => $comment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al agregar comentario al servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar comentario de un servicio
     * DELETE /api/service-comments/{id}
     */
    public function deleteServiceComment($id)
    {
        try {
            $comment = Comment::findOrFail($id);

            // Verificar que el comentario pertenece a un servicio
            if ($comment->commentable_type !== 'App\Models\Service') {
                return response()->json([
                    'error' => 'Este comentario no pertenece a un servicio'
                ], 400);
            }

            // Verificar autorización: solo el dueño o admin pueden eliminar
            if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'error' => 'No autorizado para eliminar este comentario'
                ], 403);
            }

            $comment->delete();

            return response()->json([
                'message' => 'Comentario eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar comentario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UNIVERSAL COMMENT DELETE (por tipo)
    |--------------------------------------------------------------------------
    */

    /**
     * Eliminar comentario genérico basado en el tipo
     * DELETE /api/comments/{id}?type=post|product|service
     */
    public function deleteCommentByType($id, Request $request)
    {
        try {
            $comment = Comment::findOrFail($id);
            
            // Verificar autorización
            if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'error' => 'No autorizado para eliminar este comentario'
                ], 403);
            }

            $type = $request->get('type');
            
            if ($type && $comment->commentable_type !== 'App\\Models\\' . ucfirst($type)) {
                return response()->json([
                    'error' => 'El tipo de comentario no coincide'
                ], 400);
            }

            $comment->delete();

            return response()->json([
                'message' => 'Comentario eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar comentario',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}