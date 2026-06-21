<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Product;
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

    /**
     * NUEVO: Obtener comentarios de un producto específico
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

            return response()->json($comments, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener comentarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * NUEVO: Agregar comentario a un producto
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
                'message' => 'Comentario agregado correctamente',
                'data' => $comment
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al agregar comentario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * NUEVO: Eliminar comentario de un producto
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
}