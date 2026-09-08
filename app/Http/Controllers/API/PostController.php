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
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    use UploadImage;

    /**
     * GET /api/posts
     * Feed completo
     */
    public function index()
    {
        try {
            $posts = Post::with([
                'user',
                'postable',
                'comments.user'
            ])
            ->withCount('likes as likes_count')
            ->latest()
            ->get();

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

        } catch (\Exception $e) {
            Log::error('❌ Error en index posts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener posts'
            ], 500);
        }
    }

    /**
     * POST /api/posts
     * Crear post - CORREGIDO CON LOGS
     */
    public function store(Request $request)
    {
        try {
            Log::info('📝 POST STORE INICIADO', [
                'user_id' => Auth::id(),
                'title' => $request->title,
                'has_file' => $request->hasFile('image'),
                'all_files' => $request->allFiles(),
            ]);

            // ✅ VALIDACIÓN
            $validated = $request->validate([
                'title'    => 'required|string|max:255',
                'content'  => 'required|string',
                'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
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

            Log::info('📝 DATOS DEL POST', [
                'postable_type' => $postableType,
                'postable_id' => $postableId,
            ]);

            // ✅ CREAR POST
            $post = Post::create([
                'user_id'       => $user->id,
                'title'         => $validated['title'],
                'content'       => $validated['content'],
                'category'      => $validated['category'] ?? null,
                'postable_type' => $postableType,
                'postable_id'   => $postableId,
            ]);

            Log::info('✅ POST CREADO', ['post_id' => $post->id]);

            // ✅ SUBIR IMAGEN SI EXISTE
            if ($request->hasFile('image')) {
                Log::info('📤 SUBIENDO IMAGEN...');
                $this->uploadImageToProduction($request, $post, 'posts', 'image');
                Log::info('✅ IMAGEN SUBIDA', ['image' => $post->image]);
            }

            return response()->json([
                'message' => 'Post creado correctamente',
                'data'    => $post->load([
                    'user',
                    'postable',
                    'comments.user'
                ])
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('❌ Error al crear post: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el post: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/posts/{id}
     * Ver un post
     */
    public function show($id)
    {
        try {
            $post = Post::with([
                'user',
                'postable',
                'comments.user'
            ])
            ->withCount('likes as likes_count')
            ->findOrFail($id);

            $user = Auth::user();
            if ($user) {
                $post->liked = Like::where([
                    'user_id' => $user->id,
                    'likeable_type' => 'App\\Models\\Post',
                    'likeable_id' => $post->id
                ])->exists();
            }

            return response()->json($post, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al mostrar post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Post no encontrado'
            ], 404);
        }
    }

    /**
     * GET /api/posts/home
     * Últimos posts para home
     */
    public function home()
    {
        try {
            $posts = Post::with([
                'user',
                'postable',
                'comments.user'
            ])
            ->withCount('likes as likes_count')
            ->latest()
            ->take(10)
            ->get();

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

        } catch (\Exception $e) {
            Log::error('❌ Error en home posts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener posts'
            ], 500);
        }
    }

    /**
     * PUT /api/posts/{id}
     * Actualizar post
     */
    public function update(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            $user = Auth::user();
            $isOwner = $post->user_id === $user->id;
            $isAdmin = $user->hasRole('admin');
            $isProfileOwner = false;

            if ($post->postable && isset($post->postable->user_id)) {
                $isProfileOwner = $post->postable->user_id === $user->id;
            }

            if (!$isOwner && !$isAdmin && !$isProfileOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para editar este post'
                ], 403);
            }

            $request->validate([
                'title'    => 'sometimes|required|string|max:255',
                'content'  => 'sometimes|required|string',
                'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                'category' => 'nullable|string|max:100',
            ]);

            // ✅ ACTUALIZAR DATOS
            $post->update($request->only([
                'title',
                'content',
                'category'
            ]));

            // ✅ SUBIR NUEVA IMAGEN SI EXISTE
            if ($request->hasFile('image')) {
                $this->uploadImageToProduction($request, $post, 'posts', 'image');
            }

            return response()->json([
                'success' => true,
                'message' => 'Post actualizado correctamente',
                'data'    => $post->load([
                    'user',
                    'postable',
                    'comments.user'
                ])
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/posts/{id}
     * Eliminar post
     */
    public function destroy($id)
    {
        try {
            $post = Post::findOrFail($id);

            $user = Auth::user();
            $isOwner = $post->user_id === $user->id;
            $isAdmin = $user->hasRole('admin');
            $isProfileOwner = false;

            if ($post->postable && isset($post->postable->user_id)) {
                $isProfileOwner = $post->postable->user_id === $user->id;
            }

            if (!$isOwner && !$isAdmin && !$isProfileOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para eliminar este post'
                ], 403);
            }

            // ✅ ELIMINAR IMAGEN
            if ($post->image) {
                $this->deleteImageFromProduction($post->image);
            }
            
            $post->comments()->delete();
            $post->likes()->delete();
            $post->delete();

            return response()->json([
                'success' => true,
                'message' => 'Post eliminado correctamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el post',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/posts/my/latest
     * Mis posts
     */
    public function myLatestPosts()
    {
        try {
            $posts = Auth::user()
                ->posts()
                ->with([
                    'user',
                    'postable'
                ])
                ->latest()
                ->get();

            return response()->json($posts, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en myLatestPosts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus posts'
            ], 500);
        }
    }

    /**
     * POST /api/posts/{id}/comments
     * Agregar comentario a un post
     */
    public function addComment(Request $request, $id)
    {
        try {
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

        } catch (\Exception $e) {
            Log::error('❌ Error al agregar comentario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar comentario'
            ], 500);
        }
    }

    /**
     * DELETE /api/posts/comments/{id}
     * Eliminar comentario
     */
    public function deleteComment($id)
    {
        try {
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comentario no encontrado'
                ], 404);
            }

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

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar comentario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar comentario'
            ], 500);
        }
    }

    /**
     * POST /api/posts/{id}/like
     * Dar like o quitar like (toggle)
     */
    public function toggleLike($id)
    {
        try {
            $user = Auth::user();
            $post = Post::find($id);

            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post no encontrado'
                ], 404);
            }

            $existingLike = Like::where([
                'user_id' => $user->id,
                'likeable_type' => 'App\\Models\\Post',
                'likeable_id' => $post->id
            ])->first();

            if ($existingLike) {
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

        } catch (\Exception $e) {
            Log::error('❌ Error al toggle like: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el like'
            ], 500);
        }
    }

    /**
     * GET /api/posts/{id}/likes
     * Obtener likes de un post
     */
    public function getLikes($id)
    {
        try {
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

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener likes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener likes'
            ], 500);
        }
    }

    /**
     * GET /api/posts/search
     * Buscar posts
     */
    public function search(Request $request)
    {
        try {
            $query = $request->get('q', '');

            if (empty($query)) {
                return response()->json([]);
            }

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

        } catch (\Exception $e) {
            Log::error('❌ Error en search posts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar posts'
            ], 500);
        }
    }

    /**
     * POST /api/posts/{id}/image
     * Actualizar solo la imagen del post
     */
    public function updateImage(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            // Verificar permisos
            if ($post->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 403);
            }

            $request->validate([
                'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            // ✅ USAR EL TRAIT PARA SUBIR LA IMAGEN
            return $this->uploadImageToProduction($request, $post, 'posts', 'image');

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar imagen del post: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}