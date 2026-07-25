<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    /**
     * Obtener las últimas noticias (público)
     */
    public function latest()
    {
        $news = News::with([
            'user',
            'newable',
            'comments.user'
        ])
        ->latest('created_at')
        ->limit(15)
        ->get();

        return response()->json($news);
    }

    /**
     * Obtener noticias para el home (público)
     */
    public function home()
    {
        $news = News::with(['user', 'newable', 'comments.user'])
            ->latest('created_at')
            ->take(6)
            ->get();

        return response()->json($news);
    }

    /**
     * Listar todas las noticias con filtros
     */
    public function index(Request $request)
    {
        $query = News::with(['user', 'newable', 'comments.user']);

        if ($request->has('type')) {
            $typeMap = [
                'doctor'      => 'App\\Models\\Doctor',
                'lawyer'      => 'App\\Models\\Lawyer',
                'shop'        => 'App\\Models\\Shop',
                'association' => 'App\\Models\\Association',
            ];
            if (isset($typeMap[$request->type])) {
                $query->where('newable_type', $typeMap[$request->type]);
            }
        }

        if ($request->has('search')) {
            $query->where('titulo', 'LIKE', '%' . $request->search . '%');
        }

        $perPage = $request->get('per_page', 10);
        $news = $query->latest('created_at')->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Crear una nueva noticia - CUALQUIER USUARIO PUEDE CREAR
     */
// app/Http/Controllers/API/NewsController.php - store modificado

// app/Http/Controllers/API/NewsController.php - store corregido

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'titulo' => 'required|string|max:191',
        'descripcion' => 'nullable|string',
        'url' => 'nullable|url|max:191',
        'image' => 'nullable|string|max:191',
        'fecha_publicacion' => 'nullable|date',
        'newable_type' => 'nullable|string|in:App\\Models\\Doctor,App\\Models\\Lawyer,App\\Models\\Shop,App\\Models\\Association',
        'newable_id' => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $user = Auth::user();

    // Datos base
    $data = [
        'user_id' => $user->id,
        'titulo' => $request->titulo,
        'descripcion' => $request->descripcion,
        'url' => $request->url,
        'image' => $request->image,
        'fecha_publicacion' => $request->fecha_publicacion ?? now(),
    ];

    // ✅ Si se proporciona newable_type y newable_id, verificar permisos
    if ($request->newable_type && $request->newable_id) {
        $newable = $this->getNewableModel($request->newable_type, $request->newable_id);
        
        if (!$newable) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil no encontrado'
            ], 404);
        }

        // Verificar que el usuario sea dueño del perfil o admin
        if ($newable->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para crear noticias en este perfil'
            ], 403);
        }

        $data['newable_type'] = $request->newable_type;
        $data['newable_id'] = $request->newable_id;
    }
    // ✅ Si no se proporciona, newable_type y newable_id serán null en BD

    // Subir imagen si es un archivo
    if ($request->hasFile('image')) {
        $imageUrl = $this->uploadImage($request->file('image'));
        $data['image'] = $imageUrl;
    }

    $news = News::create($data);

    $news->load(['user', 'newable', 'comments.user']);

    return response()->json([
        'success' => true,
        'message' => 'Noticia creada exitosamente',
        'data' => $news
    ], 201);
}
    /**
     * Mostrar una noticia específica
     */
    public function show($id)
    {
        $news = News::with(['user', 'newable', 'comments.user'])->find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        return response()->json($news);
    }

    /**
     * Actualizar una noticia
     */
    public function update(Request $request, $id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        $user = Auth::user();

        // Verificar que el usuario sea el dueño o admin
        if ($news->user_id !== $user->id && !$user->hasRole('admin')) {
            // Verificar si es dueño del perfil asociado
            if ($news->newable && isset($news->newable->user_id) && $news->newable->user_id === $user->id) {
                // El usuario es dueño del perfil
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta noticia'
                ], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|required|string|max:191',
            'descripcion' => 'nullable|string',
            'url' => 'nullable|url|max:191',
            'image' => 'nullable|string|max:191',
            'fecha_publicacion' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Actualizar imagen si se envía un archivo
        if ($request->hasFile('image')) {
            if ($news->image) {
                $this->deleteImage($news->image);
            }
            $imageUrl = $this->uploadImage($request->file('image'));
            $news->image = $imageUrl;
        }

        // Actualizar campos
        $news->update($request->only([
            'titulo',
            'descripcion',
            'url',
            'image',
            'fecha_publicacion'
        ]));

        $news->load(['user', 'newable', 'comments.user']);

        return response()->json([
            'success' => true,
            'message' => 'Noticia actualizada exitosamente',
            'data' => $news
        ]);
    }

    /**
     * Eliminar una noticia
     */
    public function destroy($id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        $user = Auth::user();

        // Verificar que el usuario sea el dueño o admin
        if ($news->user_id !== $user->id && !$user->hasRole('admin')) {
            if ($news->newable && isset($news->newable->user_id) && $news->newable->user_id === $user->id) {
                // El usuario es dueño del perfil
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta noticia'
                ], 403);
            }
        }

        if ($news->image) {
            $this->deleteImage($news->image);
        }

        $news->comments()->delete();
        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'Noticia eliminada exitosamente'
        ]);
    }

    /**
     * Agregar un comentario a una noticia
     */
    public function addComment(Request $request, $id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'commentable_type' => 'App\\Models\\News',
            'commentable_id' => $news->id,
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
     * Obtener las últimas noticias del usuario autenticado
     */
    public function myLatestNews()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $user->load(['doctor', 'lawyer', 'shop', 'association']);

        $news = News::with(['user', 'newable', 'comments.user'])
            ->where(function ($query) use ($user) {
                // Noticias creadas por el usuario
                $query->where('user_id', $user->id);
                
                // Noticias de los perfiles del usuario
                if ($user->doctor) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Doctor')
                          ->where('newable_id', $user->doctor->id);
                    });
                }
                if ($user->lawyer) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Lawyer')
                          ->where('newable_id', $user->lawyer->id);
                    });
                }
                if ($user->shop) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Shop')
                          ->where('newable_id', $user->shop->id);
                    });
                }
                if ($user->association) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Association')
                          ->where('newable_id', $user->association->id);
                    });
                }
            })
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json($news);
    }

    /**
     * Obtener TODAS las noticias del usuario autenticado
     */
    public function myAllNews()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $user->load(['doctor', 'lawyer', 'shop', 'association']);

        $news = News::with(['user', 'newable', 'comments.user'])
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id);
                
                if ($user->doctor) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Doctor')
                          ->where('newable_id', $user->doctor->id);
                    });
                }
                if ($user->lawyer) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Lawyer')
                          ->where('newable_id', $user->lawyer->id);
                    });
                }
                if ($user->shop) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Shop')
                          ->where('newable_id', $user->shop->id);
                    });
                }
                if ($user->association) {
                    $query->orWhere(function ($q) use ($user) {
                        $q->where('newable_type', 'App\\Models\\Association')
                          ->where('newable_id', $user->association->id);
                    });
                }
            })
            ->latest('created_at')
            ->get();

        return response()->json($news);
    }

    /**
     * Dar like o quitar like a una noticia (toggle)
     */
    public function toggleLike($id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        $existingLike = Like::where([
            'user_id' => $user->id,
            'likeable_type' => 'App\\Models\\News',
            'likeable_id' => $news->id
        ])->first();

        if ($existingLike) {
            $existingLike->delete();
            
            $likesCount = Like::where([
                'likeable_type' => 'App\\Models\\News',
                'likeable_id' => $news->id
            ])->count();

            return response()->json([
                'success' => true,
                'message' => 'Like eliminado',
                'data' => [
                    'liked' => false,
                    'likes_count' => $likesCount,
                    'news_id' => $news->id
                ]
            ]);
        } else {
            $like = Like::create([
                'user_id' => $user->id,
                'likeable_type' => 'App\\Models\\News',
                'likeable_id' => $news->id
            ]);

            $likesCount = Like::where([
                'likeable_type' => 'App\\Models\\News',
                'likeable_id' => $news->id
            ])->count();

            return response()->json([
                'success' => true,
                'message' => 'Like agregado',
                'data' => [
                    'liked' => true,
                    'likes_count' => $likesCount,
                    'news_id' => $news->id,
                    'like' => $like
                ]
            ], 201);
        }
    }

    /**
     * Obtener todas las noticias que le gustan al usuario
     */
    public function myLikedNews()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $likedNewsIds = Like::where([
            'user_id' => $user->id,
            'likeable_type' => 'App\\Models\\News'
        ])->pluck('likeable_id');

        $news = News::with(['user', 'newable', 'comments.user'])
            ->whereIn('id', $likedNewsIds)
            ->latest('created_at')
            ->get();

        return response()->json($news);
    }

    /**
     * Buscar noticias por título
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $news = News::with(['user', 'newable', 'comments.user'])
            ->where('titulo', 'LIKE', "%{$query}%")
            ->orWhere('descripcion', 'LIKE', "%{$query}%")
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Verificar si el usuario ha dado like a una noticia
     */
    public function checkLike($id)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }

        $liked = Like::where([
            'user_id' => $user->id,
            'likeable_type' => 'App\\Models\\News',
            'likeable_id' => $news->id
        ])->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'liked' => $liked,
                'news_id' => $news->id
            ]
        ]);
    }

    /**
     * Obtener noticias por tipo (doctor, lawyer, shop, association)
     */
    public function byType($type)
    {
        $typeMap = [
            'doctor'      => 'App\\Models\\Doctor',
            'lawyer'      => 'App\\Models\\Lawyer',
            'shop'        => 'App\\Models\\Shop',
            'association' => 'App\\Models\\Association',
        ];
        
        if (!isset($typeMap[$type])) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo no válido'
            ], 400);
        }
        
        $news = News::with(['user', 'newable', 'comments.user'])
            ->where('newable_type', $typeMap[$type])
            ->latest('created_at')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Obtener noticias destacadas (con más likes)
     */
    public function featured()
    {
        $news = News::with(['user', 'newable', 'comments.user'])
            ->withCount('likes')
            ->having('likes_count', '>', 0)
            ->orderBy('likes_count', 'desc')
            ->take(5)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $news
        ]);
    }

    /**
     * Método auxiliar para obtener el modelo del perfil
     */
    private function getNewableModel($type, $id)
    {
        $modelClass = $type;
        if (class_exists($modelClass)) {
            return $modelClass::find($id);
        }
        return null;
    }

    /**
     * Subir imagen
     */
    private function uploadImage($file)
    {
        try {
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("public/news", $fileName);
            
            return asset('storage/news/' . $fileName);
        } catch (\Exception $e) {
            Log::error('Error uploading news image: ' . $e->getMessage());
            throw new \Exception('Error al subir la imagen');
        }
    }

    /**
     * Eliminar imagen
     */
    private function deleteImage($imagePath)
    {
        try {
            if ($imagePath) {
                $path = str_replace('/storage/', 'public/', $imagePath);
                $path = str_replace(asset('/storage/'), 'public/', $imagePath);
                
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error deleting news image: ' . $e->getMessage());
        }
    }

    /**
     * Método de depuración
     */
    public function debugUserNews($userId)
    {
        $user = \App\Models\User::with([
            'doctor.news', 
            'lawyer.news', 
            'shop.news', 
            'association.news'
        ])->find($userId);
        
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $debug = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'email' => $user->email,
            'perfiles' => []
        ];

        if ($user->doctor) {
            $debug['perfiles']['doctor'] = [
                'id' => $user->doctor->id,
                'nombre' => $user->doctor->first_name . ' ' . $user->doctor->last_name,
                'noticias_count' => $user->doctor->news->count(),
                'noticias' => $user->doctor->news->toArray()
            ];
        }

        if ($user->lawyer) {
            $debug['perfiles']['lawyer'] = [
                'id' => $user->lawyer->id,
                'nombre' => $user->lawyer->first_name . ' ' . $user->lawyer->last_name,
                'noticias_count' => $user->lawyer->news->count(),
                'noticias' => $user->lawyer->news->toArray()
            ];
        }

        if ($user->shop) {
            $debug['perfiles']['shop'] = [
                'id' => $user->shop->id,
                'nombre' => $user->shop->name,
                'noticias_count' => $user->shop->news->count(),
                'noticias' => $user->shop->news->toArray()
            ];
        }

        if ($user->association) {
            $debug['perfiles']['association'] = [
                'id' => $user->association->id,
                'nombre' => $user->association->name,
                'noticias_count' => $user->association->news->count(),
                'noticias' => $user->association->news->toArray()
            ];
        }

        return response()->json($debug);
    }
}