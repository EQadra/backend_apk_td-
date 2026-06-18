<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Association;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    /**
     * ===============================
     * GET /api/posts
     * Feed completo
     * ===============================
     */
    public function index()
    {
        $posts = Post::with([
            'user',
            'postable',
            'comments.user'
        ])
        ->withCount('likes as likes_count')
        ->latest()
        ->get();

        // Verificar si el usuario autenticado dio like
        $user = Auth::user();
        if ($user) {
            $posts->each(function ($post) use ($user) {
                $post->liked = Like::where([
                    'user_id' => $user->id,
                    'likeable_type' => 'App\\Models\\Post',
                    'likeable_id' => $post->id
                ])->exists();
            });
        }

        return response()->json($posts, 200);
    }

    /**
     * ===============================
     * POST /api/posts
     * Crear post
     * ===============================
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'image' => 'nullable|image|max:4096',
            'category' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();

        $postableType = User::class;
        $postableId   = $user->id;

        if ($user->association) {
            $postableType = Association::class;
            $postableId   = $user->association->id;
        } elseif ($user->doctor) {
            $postableType = Doctor::class;
            $postableId   = $user->doctor->id;
        } elseif ($user->lawyer) {
            $postableType = Lawyer::class;
            $postableId   = $user->lawyer->id;
        } elseif ($user->shop) {
            $postableType = Shop::class;
            $postableId   = $user->shop->id;
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request
                ->file('image')
                ->store('posts', 'public');
        }

        $post = Post::create([
            'user_id'       => $user->id,
            'title'         => $request->title,
            'content'       => $request->content,
            'image'         => $imagePath,
            'category'      => $request->category,
            'postable_type' => $postableType,
            'postable_id'   => $postableId,
        ]);

        return response()->json([
            'message' => 'Post creado correctamente',
            'data'    => $post->load([
                'user',
                'postable',
                'comments.user'
            ])
        ], 201);
    }

    /**
     * ===============================
     * GET /api/posts/{id}
     * Ver un post
     * ===============================
     */
    public function show($id)
    {
        $post = Post::with([
            'user',
            'postable',
            'comments.user'
        ])
        ->withCount('likes as likes_count')
        ->findOrFail($id);

        // Verificar si el usuario autenticado dio like
        $user = Auth::user();
        if ($user) {
            $post->liked = Like::where([
                'user_id' => $user->id,
                'likeable_type' => 'App\\Models\\Post',
                'likeable_id' => $post->id
            ])->exists();
        }

        return response()->json($post, 200);
    }

    /**
     * ===============================
     * GET /api/posts/home
     * Últimos posts para home
     * ===============================
     */
    public function home()
    {
        $posts = Post::with([
            'user',
            'postable'
        ])
        ->latestForHome()
        ->get();

        return response()->json($posts, 200);
    }

    /**
     * ===============================
     * DELETE /api/posts/{id}
     * Eliminar post
     * ===============================
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        if (
            $post->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        // Eliminar comentarios y likes asociados
        $post->comments()->delete();
        $post->likes()->delete();
        $post->delete();

        return response()->json([
            'message' => 'Post eliminado correctamente'
        ], 200);
    }

    /**
     * ===============================
     * GET /api/posts/my
     * Mis posts
     * ===============================
     */
    public function myLatestPosts()
    {
        return response()->json(
            Auth::user()
                ->posts()
                ->with([
                    'user',
                    'postable'
                ])
                ->latest()
                ->get()
        );
    }

    /**
     * ===============================
     * POST /api/posts/{id}/comments
     * Agregar comentario a un post
     * ===============================
     */
    public function addComment(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post no encontrado'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'commentable_type' => 'App\\Models\\Post',
            'commentable_id' => $post->id,
            'content' => $request->content,
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Comentario agregado exitosamente',
            'data' => $comment
        ], 201);
    }

    /**
     * ===============================
     * DELETE /api/posts/comments/{id}
     * Eliminar comentario
     * ===============================
     */
    public function deleteComment($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comentario no encontrado'
            ], 404);
        }

        // Verificar que el usuario sea el dueño del comentario
        if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para eliminar este comentario'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comentario eliminado correctamente'
        ]);
    }

    /**
     * ===============================
     * POST /api/posts/{id}/like
     * Dar like o quitar like (toggle)
     * ===============================
     */
    public function toggleLike($id)
    {
        $user = Auth::user();
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post no encontrado'
            ], 404);
        }

        // Verificar si ya existe el like
        $existingLike = Like::where([
            'user_id' => $user->id,
            'likeable_type' => 'App\\Models\\Post',
            'likeable_id' => $post->id
        ])->first();

        if ($existingLike) {
            // Si ya existe, lo eliminamos (quitamos like)
            $existingLike->delete();
            $likesCount = $post->likes()->count();

            return response()->json([
                'success' => true,
                'message' => 'Like eliminado',
                'data' => [
                    'liked' => false,
                    'likes_count' => $likesCount,
                    'post_id' => $post->id
                ]
            ]);
        } else {
            // Si no existe, lo creamos (damos like)
            $like = Like::create([
                'user_id' => $user->id,
                'likeable_type' => 'App\\Models\\Post',
                'likeable_id' => $post->id
            ]);

            $likesCount = $post->likes()->count();

            return response()->json([
                'success' => true,
                'message' => 'Like agregado',
                'data' => [
                    'liked' => true,
                    'likes_count' => $likesCount,
                    'post_id' => $post->id,
                    'like' => $like
                ]
            ], 201);
        }
    }

    /**
     * ===============================
     * GET /api/posts/{id}/likes
     * Obtener likes de un post
     * ===============================
     */
    public function getLikes($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post no encontrado'
            ], 404);
        }

        $likesCount = $post->likes()->count();
        $likedByUser = false;

        if (Auth::check()) {
            $likedByUser = Like::where([
                'user_id' => Auth::id(),
                'likeable_type' => 'App\\Models\\Post',
                'likeable_id' => $post->id
            ])->exists();
        }

        return response()->json([
            'likes_count' => $likesCount,
            'liked_by_user' => $likedByUser
        ]);
    }

    /**
     * ===============================
     * GET /api/posts/search
     * Buscar posts
     * ===============================
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $posts = Post::with([
            'user',
            'postable',
            'comments.user'
        ])
        ->where('title', 'LIKE', "%{$query}%")
        ->orWhere('content', 'LIKE', "%{$query}%")
        ->orWhere('category', 'LIKE', "%{$query}%")
        ->latest()
        ->get();

        return response()->json($posts);
    }
}