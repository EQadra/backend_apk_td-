<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Like;
use App\Models\News;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    use UploadImage;

    /**
     * Tipos de perfiles permitidos para una noticia.
     */
    private function allowedNewableTypes(): array
    {
        return [
            'App\\Models\\Doctor',
            'App\\Models\\Lawyer',
            'App\\Models\\Shop',
            'App\\Models\\Association',
        ];
    }

    /**
     * GET /api/news/latest
     * Obtener las últimas noticias.
     */
    public function latest()
    {
        try {
            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->withCount('likes as likes_count')
            ->latest('created_at')
            ->limit(10)
            ->get();

            $user = Auth::user();
            if ($user) {
                $news->each(function ($item) use ($user) {
                    $item->liked = Like::where([
                        'user_id' => $user->id,
                        'likeable_type' => 'App\\Models\\News',
                        'likeable_id' => $item->id
                    ])->exists();
                });
            }

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error en latest news: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener noticias'
            ], 500);
        }
    }

    /**
     * GET /api/news/home
     * Obtener noticias para el home.
     */
    public function home()
    {
        try {
            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->withCount('likes as likes_count')
            ->latest('created_at')
            ->take(10)
            ->get();

            $user = Auth::user();
            if ($user) {
                $news->each(function ($item) use ($user) {
                    $item->liked = Like::where([
                        'user_id' => $user->id,
                        'likeable_type' => 'App\\Models\\News',
                        'likeable_id' => $item->id
                    ])->exists();
                });
            }

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error en home news: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener noticias'
            ], 500);
        }
    }

    /**
     * GET /api/news
     * Listar todas las noticias con filtros.
     */
    public function index(Request $request)
    {
        try {
            $query = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->withCount('likes as likes_count');

            if ($request->filled('type')) {
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

            if ($request->filled('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('titulo', 'LIKE', '%' . $request->search . '%')
                      ->orWhere('descripcion', 'LIKE', '%' . $request->search . '%');
                });
            }

            $perPage = min(max((int) $request->get('per_page', 10), 1), 100);

            $news = $query
                ->latest('created_at')
                ->paginate($perPage);

            $user = Auth::user();
            if ($user) {
                $news->getCollection()->each(function ($item) use ($user) {
                    $item->liked = Like::where([
                        'user_id' => $user->id,
                        'likeable_type' => 'App\\Models\\News',
                        'likeable_id' => $item->id
                    ])->exists();
                });
            }

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error en index news: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener noticias'
            ], 500);
        }
    }

    /**
     * POST /api/news
     * Crear una noticia - CORREGIDO (SIN /storage/ EN URL)
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'titulo' => 'required|string|max:191',
                    'descripcion' => 'nullable|string',
                    'url' => 'nullable|url|max:191',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                    'fecha_publicacion' => 'nullable|date',
                    'newable_type' => 'nullable|string|in:' . implode(',', $this->allowedNewableTypes()),
                    'newable_id' => 'nullable|integer|min:1',
                ],
                [
                    'image.image' => 'El archivo debe ser una imagen válida.',
                    'image.max' => 'La imagen no debe pesar más de 5MB.',
                    'image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg o webp.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $newableType = $request->input('newable_type');
            $newableId = $request->input('newable_id');

            if (($newableType && !$newableId) || (!$newableType && $newableId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'newable_type y newable_id deben enviarse juntos.',
                ], 422);
            }

            $newable = null;

            if ($newableType && $newableId) {
                $newable = $this->getNewableModel($newableType, $newableId);

                if (!$newable) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Perfil no encontrado.',
                    ], 404);
                }

                $isAdmin = $user->hasRole('admin');

                if (!$isAdmin && isset($newable->user_id) && (int) $newable->user_id !== (int) $user->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permiso para crear noticias en este perfil.',
                    ], 403);
                }
            }

            // ✅ CREAR NOTICIA PRIMERO
            $news = News::create([
                'user_id' => $user->id,
                'titulo' => $request->input('titulo'),
                'descripcion' => $request->input('descripcion'),
                'url' => $request->input('url'),
                'fecha_publicacion' => $request->input('fecha_publicacion') ?? now(),
                'newable_type' => $newableType,
                'newable_id' => $newableId,
            ]);

            // ✅ SUBIR IMAGEN - SIN /storage/ (IGUAL QUE POSTS)
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                
                // ✅ USAR LA MISMA RUTA QUE POSTS (SIN /storage/)
                $destinationPath = public_path('imagenes_app/news');
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $file->move($destinationPath, $filename);
                
                // ✅ USAR LA IP CORRECTA
                $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
                $baseUrl = $isDevelopment 
                    ? 'http://10.23.248.82:8000'  // ✅ TU IP CORRECTA
                    : 'https://apiapk.tudealer.app';
                
                // ✅ SIN /storage/
                $imageUrl = $baseUrl . '/imagenes_app/news/' . $filename;
                $news->update(['image' => $imageUrl]);
                
                Log::info('✅ Imagen subida para noticia', [
                    'news_id' => $news->id,
                    'image_url' => $imageUrl
                ]);
            }

            $news->load(['user', 'newable', 'comments.user']);

            return response()->json([
                'success' => true,
                'message' => 'Noticia creada exitosamente.',
                'data' => $news,
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear noticia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la noticia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/news/{id}
     * Mostrar una noticia.
     */
    public function show($id)
    {
        try {
            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->withCount('likes as likes_count')
            ->find($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $user = Auth::user();
            if ($user) {
                $news->liked = Like::where([
                    'user_id' => $user->id,
                    'likeable_type' => 'App\\Models\\News',
                    'likeable_id' => $news->id
                ])->exists();
            }

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error al mostrar noticia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada'
            ], 404);
        }
    }

    /**
     * PUT /api/news/{id}
     * Actualizar una noticia.
     */
    public function update(Request $request, $id)
    {
        try {
            $news = News::with('newable')->find($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $isOwner = (int) $news->user_id === (int) $user->id;
            $isAdmin = $user->hasRole('admin');
            $isProfileOwner = false;

            if ($news->newable && isset($news->newable->user_id)) {
                $isProfileOwner = (int) $news->newable->user_id === (int) $user->id;
            }

            if (!$isOwner && !$isAdmin && !$isProfileOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta noticia.',
                ], 403);
            }

            $validator = Validator::make(
                $request->all(),
                [
                    'titulo' => 'sometimes|required|string|max:191',
                    'descripcion' => 'nullable|string',
                    'url' => 'nullable|url|max:191',
                    'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                    'fecha_publicacion' => 'nullable|date',
                    'newable_type' => 'nullable|string|in:' . implode(',', $this->allowedNewableTypes()),
                    'newable_id' => 'nullable|integer|min:1',
                ],
                [
                    'image.image' => 'El archivo debe ser una imagen válida.',
                    'image.max' => 'La imagen no debe pesar más de 5MB.',
                    'image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg o webp.',
                ]
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            // ✅ Actualizar newable
            if ($request->has('newable_type') || $request->has('newable_id')) {
                $newableType = $request->input('newable_type');
                $newableId = $request->input('newable_id');

                if (($newableType && !$newableId) || (!$newableType && $newableId)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'newable_type y newable_id deben enviarse juntos.',
                    ], 422);
                }

                if ($newableType && $newableId) {
                    $newable = $this->getNewableModel($newableType, $newableId);

                    if (!$newable) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Perfil no encontrado.',
                        ], 404);
                    }

                    if (!$isAdmin && isset($newable->user_id) && (int) $newable->user_id !== (int) $user->id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No tienes permiso para asociar esta noticia a ese perfil.',
                        ], 403);
                    }
                }

                $news->newable_type = $newableType;
                $news->newable_id = $newableId;
            }

            // ✅ Actualizar campos
            $fields = ['titulo', 'descripcion', 'url', 'fecha_publicacion'];
            foreach ($fields as $field) {
                if ($request->has($field)) {
                    $news->{$field} = $request->input($field);
                }
            }

            // ✅ Actualizar imagen - SIN /storage/
            if ($request->hasFile('image')) {
                // Eliminar imagen anterior
                if ($news->image) {
                    $this->deleteImageFromProduction($news->image);
                }

                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $destinationPath = public_path('imagenes_app/news');
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $file->move($destinationPath, $filename);
                
                $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
                $baseUrl = $isDevelopment 
                    ? 'http://10.23.248.82:8000'  // ✅ TU IP CORRECTA
                    : 'https://apiapk.tudealer.app';
                
                $imageUrl = $baseUrl . '/imagenes_app/news/' . $filename;
                $news->image = $imageUrl;
            } elseif ($request->has('image')) {
                $news->image = $request->input('image');
            }

            $news->save();
            $news->load(['user', 'newable', 'comments.user']);

            return response()->json([
                'success' => true,
                'message' => 'Noticia actualizada exitosamente.',
                'data' => $news,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar noticia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la noticia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/news/{id}
     * Eliminar una noticia.
     */
    public function destroy($id)
    {
        try {
            $news = News::with('newable')->find($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $isOwner = (int) $news->user_id === (int) $user->id;
            $isAdmin = $user->hasRole('admin');
            $isProfileOwner = false;

            if ($news->newable && isset($news->newable->user_id)) {
                $isProfileOwner = (int) $news->newable->user_id === (int) $user->id;
            }

            if (!$isOwner && !$isAdmin && !$isProfileOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para eliminar esta noticia.',
                ], 403);
            }

            // ✅ Eliminar imagen
            if ($news->image) {
                $this->deleteImageFromProduction($news->image);
            }

            $news->comments()->delete();
            $news->likes()->delete();
            $news->delete();

            return response()->json([
                'success' => true,
                'message' => 'Noticia eliminada exitosamente.',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar noticia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la noticia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/news/{id}/comments
     * Agregar comentario a una noticia.
     */
    public function addComment(Request $request, $id)
    {
        try {
            $news = News::find($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $validator = Validator::make(
                $request->all(),
                ['content' => 'required|string|min:1|max:500']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $comment = Comment::create([
                'user_id' => $user->id,
                'commentable_type' => News::class,
                'commentable_id' => $news->id,
                'content' => $request->input('content'),
            ]);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'message' => 'Comentario agregado exitosamente.',
                'data' => $comment,
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
     * GET /api/news/my/latest
     * Últimas noticias del usuario.
     */
    public function myLatestNews()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $user->load(['doctor', 'lawyer', 'shop', 'association']);

            $news = $this->userNewsQuery($user)
                ->with(['user', 'newable', 'comments.user'])
                ->latest('created_at')
                ->take(5)
                ->get();

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error en myLatestNews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus noticias'
            ], 500);
        }
    }

    /**
     * GET /api/news/my/all
     * Todas las noticias del usuario.
     */
    public function myAllNews()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $user->load(['doctor', 'lawyer', 'shop', 'association']);

            $news = $this->userNewsQuery($user)
                ->with(['user', 'newable', 'comments.user'])
                ->latest('created_at')
                ->get();

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error en myAllNews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus noticias'
            ], 500);
        }
    }

    /**
     * GET /api/news/my/liked
     * Noticias que le gustan al usuario.
     */
    public function myLikedNews()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $likedNewsIds = Like::where([
                'user_id' => $user->id,
                'likeable_type' => News::class,
            ])->pluck('likeable_id');

            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->whereIn('id', $likedNewsIds)
            ->latest('created_at')
            ->get();

            return response()->json($news);

        } catch (\Exception $e) {
            Log::error('❌ Error en myLikedNews: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener noticias favoritas'
            ], 500);
        }
    }

    /**
     * GET /api/news/search
     * Buscar noticias.
     */
    public function search(Request $request)
    {
        try {
            $query = trim($request->get('q', ''));

            if ($query === '') {
                return response()->json([
                    'success' => true,
                    'data' => [],
                ]);
            }

            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->where(function ($q) use ($query) {
                $q->where('titulo', 'LIKE', "%{$query}%")
                  ->orWhere('descripcion', 'LIKE', "%{$query}%");
            })
            ->latest('created_at')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $news,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en search news: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar noticias'
            ], 500);
        }
    }

    /**
     * POST /api/news/{id}/like
     * Toggle like en noticia.
     */
    public function toggleLike($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $news = News::find($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $existingLike = Like::where([
                'user_id' => $user->id,
                'likeable_type' => News::class,
                'likeable_id' => $news->id,
            ])->first();

            if ($existingLike) {
                $existingLike->delete();

                $likesCount = Like::where([
                    'likeable_type' => News::class,
                    'likeable_id' => $news->id,
                ])->count();

                return response()->json([
                    'success' => true,
                    'message' => 'Like eliminado.',
                    'data' => [
                        'liked' => false,
                        'likes_count' => $likesCount,
                        'news_id' => $news->id,
                    ],
                ]);
            }

            $like = Like::create([
                'user_id' => $user->id,
                'likeable_type' => News::class,
                'likeable_id' => $news->id,
            ]);

            $likesCount = Like::where([
                'likeable_type' => News::class,
                'likeable_id' => $news->id,
            ])->count();

            return response()->json([
                'success' => true,
                'message' => 'Like agregado.',
                'data' => [
                    'liked' => true,
                    'likes_count' => $likesCount,
                    'news_id' => $news->id,
                    'like' => $like,
                ],
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error en toggleLike: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el like'
            ], 500);
        }
    }

    /**
     * GET /api/news/{id}/check-like
     * Verificar si el usuario dio like.
     */
    public function checkLike($id)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $news = News::find($id);

            if (!$news) {
                return response()->json([
                    'success' => false,
                    'message' => 'Noticia no encontrada.',
                ], 404);
            }

            $liked = Like::where([
                'user_id' => $user->id,
                'likeable_type' => News::class,
                'likeable_id' => $news->id,
            ])->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'liked' => $liked,
                    'news_id' => $news->id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en checkLike: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar like'
            ], 500);
        }
    }

    /**
     * GET /api/news/type/{type}
     * Noticias por tipo de perfil.
     */
    public function byType($type)
    {
        try {
            $typeMap = [
                'doctor' => 'App\\Models\\Doctor',
                'lawyer' => 'App\\Models\\Lawyer',
                'shop' => 'App\\Models\\Shop',
                'association' => 'App\\Models\\Association',
            ];

            if (!isset($typeMap[$type])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo no válido.',
                ], 400);
            }

            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->where('newable_type', $typeMap[$type])
            ->latest('created_at')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $news,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en byType: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener noticias por tipo'
            ], 500);
        }
    }

    /**
     * GET /api/news/featured
     * Noticias destacadas (con más likes).
     */
    public function featured()
    {
        try {
            $news = News::with([
                'user',
                'newable',
                'comments.user',
            ])
            ->withCount('likes')
            ->having('likes_count', '>', 0)
            ->orderByDesc('likes_count')
            ->take(5)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $news,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en featured: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener noticias destacadas'
            ], 500);
        }
    }

    /**
     * Query de noticias del usuario (helper).
     */
    private function userNewsQuery($user)
    {
        return News::where(function ($query) use ($user) {
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
        });
    }

    /**
     * Obtener modelo del perfil (helper).
     */
    private function getNewableModel(string $type, int $id)
    {
        if (!in_array($type, $this->allowedNewableTypes(), true)) {
            return null;
        }

        if (!class_exists($type)) {
            return null;
        }

        return $type::find($id);
    }

    /**
     * GET /api/news/debug/{userId}
     * Debug de noticias de usuario (solo admin).
     */
    public function debugUserNews($userId)
    {
        try {
            $user = \App\Models\User::with([
                'doctor.news',
                'lawyer.news',
                'shop.news',
                'association.news',
            ])->find($userId);

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no encontrado',
                ], 404);
            }

            $debug = [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
                'perfiles' => [],
            ];

            if ($user->doctor) {
                $debug['perfiles']['doctor'] = [
                    'id' => $user->doctor->id,
                    'nombre' => $user->doctor->first_name . ' ' . $user->doctor->last_name,
                    'noticias_count' => $user->doctor->news->count(),
                    'noticias' => $user->doctor->news->toArray(),
                ];
            }

            if ($user->lawyer) {
                $debug['perfiles']['lawyer'] = [
                    'id' => $user->lawyer->id,
                    'nombre' => $user->lawyer->first_name . ' ' . $user->lawyer->last_name,
                    'noticias_count' => $user->lawyer->news->count(),
                    'noticias' => $user->lawyer->news->toArray(),
                ];
            }

            if ($user->shop) {
                $debug['perfiles']['shop'] = [
                    'id' => $user->shop->id,
                    'nombre' => $user->shop->name,
                    'noticias_count' => $user->shop->news->count(),
                    'noticias' => $user->shop->news->toArray(),
                ];
            }

            if ($user->association) {
                $debug['perfiles']['association'] = [
                    'id' => $user->association->id,
                    'nombre' => $user->association->name,
                    'noticias_count' => $user->association->news->count(),
                    'noticias' => $user->association->news->toArray(),
                ];
            }

            return response()->json($debug);

        } catch (\Exception $e) {
            Log::error('❌ Error en debugUserNews: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener debug'
            ], 500);
        }
    }
}