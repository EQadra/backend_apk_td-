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
     * Obtener las últimas noticias.
     */
    public function latest()
    {
        $news = News::with([
            'user',
            'newable',
            'comments.user',
        ])
            ->latest('created_at')
            ->limit(10)
            ->get();

        return response()->json($news);
    }

    /**
     * Obtener noticias para el home.
     */
    public function home()
    {
        $news = News::with([
            'user',
            'newable',
            'comments.user',
        ])
            ->latest('created_at')
            ->take(10)
            ->get();

        return response()->json($news);
    }

    /**
     * Listar todas las noticias con filtros.
     */
    public function index(Request $request)
    {
        $query = News::with([
            'user',
            'newable',
            'comments.user',
        ]);

        if ($request->filled('type')) {
            $typeMap = [
                'doctor'      => 'App\\Models\\Doctor',
                'lawyer'      => 'App\\Models\\Lawyer',
                'shop'        => 'App\\Models\\Shop',
                'association' => 'App\\Models\\Association',
            ];

            if (isset($typeMap[$request->type])) {
                $query->where(
                    'newable_type',
                    $typeMap[$request->type]
                );
            }
        }

        if ($request->filled('search')) {
            $query->where(
                'titulo',
                'LIKE',
                '%' . $request->search . '%'
            );
        }

        $perPage = min(
            max((int) $request->get('per_page', 10), 1),
            100
        );

        $news = $query
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Crear una noticia.
     *
     * Cualquier usuario autenticado puede crearla.
     * La noticia puede estar asociada opcionalmente
     * a un Doctor, Lawyer, Shop o Association.
     */
    public function store(Request $request)
    {
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
                'titulo' => [
                    'required',
                    'string',
                    'max:191',
                ],
                'descripcion' => [
                    'nullable',
                    'string',
                ],
                'url' => [
                    'nullable',
                    'url',
                    'max:191',
                ],
                'image' => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpeg,png,jpg,gif,svg,webp',
                    'max:5120',
                ],
                'fecha_publicacion' => [
                    'nullable',
                    'date',
                ],
                'newable_type' => [
                    'nullable',
                    'string',
                    'in:' . implode(
                        ',',
                        $this->allowedNewableTypes()
                    ),
                ],
                'newable_id' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
            ],
            [
                'newable_type.in' =>
                    'El tipo de perfil no es válido.',
                'newable_id.integer' =>
                    'El ID del perfil debe ser un número entero.',
                'newable_id.min' =>
                    'El ID del perfil no es válido.',
                'image.image' => 'El archivo debe ser una imagen válida.',
                'image.max' => 'La imagen no debe pesar más de 5MB.',
                'image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif, svg o webp.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validar relación newable
        |--------------------------------------------------------------------------
        */

        $newableType = $request->input('newable_type');
        $newableId = $request->input('newable_id');

        // Si viene uno, deben venir ambos.
        if (
            ($newableType && !$newableId) ||
            (!$newableType && $newableId)
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'newable_type y newable_id deben enviarse juntos.',
            ], 422);
        }

        $newable = null;

        if ($newableType && $newableId) {
            $newable = $this->getNewableModel(
                $newableType,
                $newableId
            );

            if (!$newable) {
                return response()->json([
                    'success' => false,
                    'message' => 'Perfil no encontrado.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Verificar propietario del perfil
            |--------------------------------------------------------------------------
            */

            $isAdmin = $user->hasRole('admin');

            if (
                !$isAdmin &&
                isset($newable->user_id) &&
                (int) $newable->user_id !== (int) $user->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No tienes permiso para crear noticias en este perfil.',
                ], 403);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Datos de la noticia
        |--------------------------------------------------------------------------
        */

        $data = [
            'user_id' => $user->id,
            'titulo' => $request->input('titulo'),
            'descripcion' => $request->input('descripcion'),
            'url' => $request->input('url'),
            'fecha_publicacion' =>
                $request->input('fecha_publicacion') ?? now(),
            'newable_type' => $newableType,
            'newable_id' => $newableId,
        ];

        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('image')) {
            // Crear una instancia temporal para usar el trait
            $tempModel = new News();
            $tempModel->fill($data);
            
            // Usar el método del trait para subir la imagen
            $result = $this->uploadImageToProduction(
                $request, 
                $tempModel, 
                'news',
                'image'
            );
            
            // Si el resultado es una respuesta JSON con error
            if ($result instanceof \Illuminate\Http\JsonResponse) {
                $responseData = $result->getData();
                if (isset($responseData->error) || !isset($responseData->success)) {
                    return $result;
                }
            }
            
            // Actualizar el data con la URL de la imagen
            $data['image'] = $tempModel->image;
        } elseif ($request->input('image')) {
            // Si es una URL string
            $data['image'] = $request->input('image');
        }

        /*
        |--------------------------------------------------------------------------
        | Crear noticia
        |--------------------------------------------------------------------------
        */

        $news = News::create($data);

        $news->load([
            'user',
            'newable',
            'comments.user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Noticia creada exitosamente.',
            'data' => $news,
        ], 201);
    }

    /**
     * Mostrar una noticia.
     */
    public function show($id)
    {
        $news = News::with([
            'user',
            'newable',
            'comments.user',
        ])->find($id);

        if (!$news) {
            return response()->json([
                'success' => false,
                'message' => 'Noticia no encontrada.',
            ], 404);
        }

        return response()->json($news);
    }

    /**
     * Actualizar una noticia.
     */
    public function update(Request $request, $id)
    {
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

        /*
        |--------------------------------------------------------------------------
        | Permisos
        |--------------------------------------------------------------------------
        */

        $isOwner =
            (int) $news->user_id === (int) $user->id;

        $isAdmin = $user->hasRole('admin');

        $isProfileOwner = false;

        if (
            $news->newable &&
            isset($news->newable->user_id)
        ) {
            $isProfileOwner =
                (int) $news->newable->user_id ===
                (int) $user->id;
        }

        if (
            !$isOwner &&
            !$isAdmin &&
            !$isProfileOwner
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No tienes permiso para editar esta noticia.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make(
            $request->all(),
            [
                'titulo' => 'sometimes|required|string|max:191',
                'descripcion' => 'nullable|string',
                'url' => 'nullable|url|max:191',
                'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
                'fecha_publicacion' => 'nullable|date',
                'newable_type' => [
                    'nullable',
                    'string',
                    'in:' . implode(
                        ',',
                        $this->allowedNewableTypes()
                    ),
                ],
                'newable_id' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
            ],
            [
                'image.image' => 'El archivo debe ser una imagen válida.',
                'image.max' => 'La imagen no debe pesar más de 5MB.',
                'image.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif, svg o webp.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar newable
        |--------------------------------------------------------------------------
        */

        if (
            $request->has('newable_type') ||
            $request->has('newable_id')
        ) {
            $newableType =
                $request->input('newable_type');

            $newableId =
                $request->input('newable_id');

            if (
                ($newableType && !$newableId) ||
                (!$newableType && $newableId)
            ) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'newable_type y newable_id deben enviarse juntos.',
                ], 422);
            }

            if ($newableType && $newableId) {
                $newable = $this->getNewableModel(
                    $newableType,
                    $newableId
                );

                if (!$newable) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Perfil no encontrado.',
                    ], 404);
                }

                if (
                    !$isAdmin &&
                    isset($newable->user_id) &&
                    (int) $newable->user_id !== (int) $user->id
                ) {
                    return response()->json([
                        'success' => false,
                        'message' =>
                            'No tienes permiso para asociar esta noticia a ese perfil.',
                    ], 403);
                }
            }

            $news->newable_type = $newableType;
            $news->newable_id = $newableId;
        }

        /*
        |--------------------------------------------------------------------------
        | Campos normales
        |--------------------------------------------------------------------------
        */

        $fields = [
            'titulo',
            'descripcion',
            'url',
            'fecha_publicacion',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $news->{$field} =
                    $request->input($field);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Imagen
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($news->image) {
                $this->deleteImageFromProduction($news->image);
            }

            // Subir nueva imagen usando el trait
            $result = $this->uploadImageToProduction(
                $request,
                $news,
                'news',
                'image'
            );

            if ($result instanceof \Illuminate\Http\JsonResponse) {
                $responseData = $result->getData();
                if (isset($responseData->error) || !isset($responseData->success)) {
                    return $result;
                }
            }
        } elseif ($request->has('image')) {
            // Si es una URL string
            $news->image = $request->input('image');
        }

        $news->save();

        $news->load([
            'user',
            'newable',
            'comments.user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Noticia actualizada exitosamente.',
            'data' => $news,
        ]);
    }

    /**
     * Eliminar una noticia.
     */
    public function destroy($id)
    {
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

        $isOwner =
            (int) $news->user_id === (int) $user->id;

        $isAdmin = $user->hasRole('admin');

        $isProfileOwner = false;

        if (
            $news->newable &&
            isset($news->newable->user_id)
        ) {
            $isProfileOwner =
                (int) $news->newable->user_id ===
                (int) $user->id;
        }

        if (
            !$isOwner &&
            !$isAdmin &&
            !$isProfileOwner
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No tienes permiso para eliminar esta noticia.',
            ], 403);
        }

        // Eliminar imagen usando el trait
        if ($news->image) {
            $this->deleteImageFromProduction($news->image);
        }

        $news->comments()->delete();

        $news->delete();

        return response()->json([
            'success' => true,
            'message' => 'Noticia eliminada exitosamente.',
        ]);
    }

    /**
     * Agregar comentario.
     */
    public function addComment(Request $request, $id)
    {
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
            [
                'content' =>
                    'required|string|min:1',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $comment = Comment::create([
            'user_id' => $user->id,
            'commentable_type' =>
                News::class,
            'commentable_id' =>
                $news->id,
            'content' =>
                $request->input('content'),
        ]);

        $comment->load('user');

        return response()->json([
            'success' => true,
            'message' =>
                'Comentario agregado exitosamente.',
            'data' => $comment,
        ], 201);
    }

    /**
     * Últimas noticias del usuario.
     */
    public function myLatestNews()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $user->load([
            'doctor',
            'lawyer',
            'shop',
            'association',
        ]);

        $news = $this->userNewsQuery($user)
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json($news);
    }

    /**
     * Todas las noticias del usuario.
     */
    public function myAllNews()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $user->load([
            'doctor',
            'lawyer',
            'shop',
            'association',
        ]);

        $news = $this->userNewsQuery($user)
            ->latest('created_at')
            ->get();

        return response()->json($news);
    }

    /**
     * Query de noticias del usuario.
     */
    private function userNewsQuery($user)
    {
        return News::with([
            'user',
            'newable',
            'comments.user',
        ])->where(function ($query) use ($user) {

            // Noticias creadas por el usuario
            $query->where(
                'user_id',
                $user->id
            );

            // Noticias del Doctor
            if ($user->doctor) {
                $query->orWhere(function ($q) use ($user) {
                    $q->where(
                        'newable_type',
                        'App\\Models\\Doctor'
                    )->where(
                        'newable_id',
                        $user->doctor->id
                    );
                });
            }

            // Noticias del Lawyer
            if ($user->lawyer) {
                $query->orWhere(function ($q) use ($user) {
                    $q->where(
                        'newable_type',
                        'App\\Models\\Lawyer'
                    )->where(
                        'newable_id',
                        $user->lawyer->id
                    );
                });
            }

            // Noticias de Shop
            if ($user->shop) {
                $query->orWhere(function ($q) use ($user) {
                    $q->where(
                        'newable_type',
                        'App\\Models\\Shop'
                    )->where(
                        'newable_id',
                        $user->shop->id
                    );
                });
            }

            // Noticias de Association
            if ($user->association) {
                $query->orWhere(function ($q) use ($user) {
                    $q->where(
                        'newable_type',
                        'App\\Models\\Association'
                    )->where(
                        'newable_id',
                        $user->association->id
                    );
                });
            }
        });
    }

    /**
     * Toggle like.
     */
    public function toggleLike($id)
    {
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
    }

    /**
     * Noticias que le gustan al usuario.
     */
    public function myLikedNews()
    {
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
    }

    /**
     * Buscar noticias.
     */
    public function search(Request $request)
    {
        $query = trim(
            $request->get('q', '')
        );

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
                $q->where(
                    'titulo',
                    'LIKE',
                    "%{$query}%"
                )->orWhere(
                    'descripcion',
                    'LIKE',
                    "%{$query}%"
                );
            })
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    /**
     * Verificar like.
     */
    public function checkLike($id)
    {
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
    }

    /**
     * Noticias por tipo.
     */
    public function byType($type)
    {
        $typeMap = [
            'doctor' =>
                'App\\Models\\Doctor',
            'lawyer' =>
                'App\\Models\\Lawyer',
            'shop' =>
                'App\\Models\\Shop',
            'association' =>
                'App\\Models\\Association',
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
            ->where(
                'newable_type',
                $typeMap[$type]
            )
            ->latest('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $news,
        ]);
    }

    /**
     * Noticias destacadas.
     */
    public function featured()
    {
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
    }

    /**
     * Obtener modelo del perfil.
     */
    private function getNewableModel(
        string $type,
        int $id
    ) {
        if (!in_array(
            $type,
            $this->allowedNewableTypes(),
            true
        )) {
            return null;
        }

        if (!class_exists($type)) {
            return null;
        }

        return $type::find($id);
    }

    /**
     * Debug de noticias de usuario.
     */
    public function debugUserNews($userId)
    {
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
                'id' =>
                    $user->doctor->id,
                'nombre' =>
                    $user->doctor->first_name .
                    ' ' .
                    $user->doctor->last_name,
                'noticias_count' =>
                    $user->doctor->news->count(),
                'noticias' =>
                    $user->doctor->news->toArray(),
            ];
        }

        if ($user->lawyer) {
            $debug['perfiles']['lawyer'] = [
                'id' =>
                    $user->lawyer->id,
                'nombre' =>
                    $user->lawyer->first_name .
                    ' ' .
                    $user->lawyer->last_name,
                'noticias_count' =>
                    $user->lawyer->news->count(),
                'noticias' =>
                    $user->lawyer->news->toArray(),
            ];
        }

        if ($user->shop) {
            $debug['perfiles']['shop'] = [
                'id' =>
                    $user->shop->id,
                'nombre' =>
                    $user->shop->name,
                'noticias_count' =>
                    $user->shop->news->count(),
                'noticias' =>
                    $user->shop->news->toArray(),
            ];
        }

        if ($user->association) {
            $debug['perfiles']['association'] = [
                'id' =>
                    $user->association->id,
                'nombre' =>
                    $user->association->name,
                'noticias_count' =>
                    $user->association->news->count(),
                'noticias' =>
                    $user->association->news->toArray(),
            ];
        }

        return response()->json($debug);
    }
}