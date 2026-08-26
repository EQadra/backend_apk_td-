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

class PostController extends Controller
{
    use UploadImage;

    /**
     * GET /api/posts
     * Feed completo
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
     * POST /api/posts
     * Crear post
     */
    public function store(Request $request)
    {
        $request->validate([
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

        $imageUrl = null;

        // 🔥 SUBIR IMAGEN usando el método del trait
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/posts';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/posts/' . $filename;
        }

        $post = Post::create([
            'user_id'       => $user->id,
            'title'         => $request->title,
            'content'       => $request->content,
            'image'         => $imageUrl,
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
     * GET /api/posts/{id}
     * Ver un post
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
     * GET /api/posts/home
     * Últimos posts para home
     */
    public function home()
    {
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
    }

    /**
     * PUT /api/posts/{id}
     * Actualizar post
     */
    // public function update(Request $request, $id)
    // {
    //     $post = Post::findOrFail($id);

    //     // Verificar permisos
    //     if ($post->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
    //         return response()->json([
    //             'message' => 'No autorizado para editar este post'
    //         ], 403);
    //     }

    //     $request->validate([
    //         'title'    => 'sometimes|required|string|max:255',
    //         'content'  => 'sometimes|required|string',
    //         'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
    //         'category' => 'nullable|string|max:100',
    //     ]);

    //     // Actualizar imagen si se envía una nueva
    //     if ($request->hasFile('image')) {
    //         // Eliminar imagen anterior si existe
    //         if ($post->image) {
    //             $this->deleteImageFromProduction($post->image);
    //         }

    //         $file = $request->file('image');
    //         $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
    //         $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/posts';
            
    //         if (!file_exists($destinationPath)) {
    //             mkdir($destinationPath, 0755, true);
    //         }
            
    //         $file->move($destinationPath, $filename);
    //         $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/posts/' . $filename;
            
    //         $post->image = $imageUrl;
    //     }

    //     // Si se envía una URL de imagen directamente
    //     if ($request->has('image') && is_string($request->image) && !$request->hasFile('image')) {
    //         $post->image = $request->image;
    //     }

    //     // Actualizar campos
    //     $post->update($request->only([
    //         'title',
    //         'content',
    //         'category'
    //     ]));

    //     return response()->json([
    //         'message' => 'Post actualizado correctamente',
    //         'data'    => $post->load([
    //             'user',
    //             'postable',
    //             'comments.user'
    //         ])
    //     ], 200);
    // }

    public function update(Request $request, $id)
{
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
            'message' => 'No autorizado para editar este post'
        ], 403);
    }

    $request->validate([
        'title'    => 'sometimes|required|string|max:255',
        'content'  => 'sometimes|required|string',
        'image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        'category' => 'nullable|string|max:100',
    ]);

    // Actualizar imagen si se envía una nueva
    if ($request->hasFile('image')) {
        // Eliminar imagen anterior si existe
        if ($post->image) {
            $this->deleteImageFromProduction($post->image);
        }

        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/posts';
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
        $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/posts/' . $filename;
        
        $post->image = $imageUrl;
    }

    // Si se envía una URL de imagen directamente
    if ($request->has('image') && is_string($request->image) && !$request->hasFile('image')) {
        $post->image = $request->image;
    }

    // Actualizar campos
    $post->update($request->only([
        'title',
        'content',
        'category'
    ]));

    return response()->json([
        'message' => 'Post actualizado correctamente',
        'data'    => $post->load([
            'user',
            'postable',
            'comments.user'
        ])
    ], 200);
}

    /**
     * DELETE /api/posts/{id}
     * Eliminar post
     */
public function destroy($id)
{
    $post = Post::findOrFail($id);

    $user = Auth::user();
    $isOwner = $post->user_id === $user->id;
    $isAdmin = $user->hasRole('admin');
    $isProfileOwner = false;

    // Verificar si el usuario es dueño del perfil asociado
    if ($post->postable && isset($post->postable->user_id)) {
        $isProfileOwner = $post->postable->user_id === $user->id;
    }

    if (!$isOwner && !$isAdmin && !$isProfileOwner) {
        return response()->json([
            'message' => 'No autorizado para eliminar este post'
        ], 403);
    }

    // Eliminar imagen y el post
    $this->deleteImageFromProduction($post->image);
    $post->comments()->delete();
    $post->likes()->delete();
    $post->delete();

    return response()->json([
        'message' => 'Post eliminado correctamente'
    ], 200);
}
    /**
     * GET /api/posts/my
     * Mis posts
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
     * POST /api/posts/{id}/comments
     * Agregar comentario a un post
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
     * DELETE /api/posts/comments/{id}
     * Eliminar comentario
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
     * POST /api/posts/{id}/like
     * Dar like o quitar like (toggle)
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
    }

    /**
     * GET /api/posts/{id}/likes
     * Obtener likes de un post
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
     * GET /api/posts/search
     * Buscar posts
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

    /**
     * POST /api/posts/{id}/image
     * Actualizar solo la imagen del post
     */
    public function updateImage(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        // Verificar permisos
        if ($post->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        // Eliminar imagen anterior
        if ($post->image) {
            $this->deleteImageFromProduction($post->image);
        }

        // Subir nueva imagen
        $file = $request->file('image');
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/posts';
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        
        $file->move($destinationPath, $filename);
        $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/posts/' . $filename;

        $post->image = $imageUrl;
        $post->save();

        return response()->json([
            'message' => 'Imagen actualizada correctamente',
            'data' => $post
        ], 200);
    }


    
}