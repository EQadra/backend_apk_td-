<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    /**
     * Obtener las últimas noticias (público)
     */
    public function latest()
    {
        $news = News::with([
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
        $news = News::with(['newable', 'comments.user'])
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
        $query = News::with(['newable', 'comments.user']);

        // Filtro por tipo (doctor, lawyer, shop, association)
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

        // Filtro por búsqueda en título
        if ($request->has('search')) {
            $query->where('titulo', 'LIKE', '%' . $request->search . '%');
        }

        // Paginación
        $perPage = $request->get('per_page', 10);
        $news = $query->latest('created_at')->paginate($perPage);

        return response()->json($news);
    }

    /**
     * Crear una nueva noticia
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:191',
            'descripcion' => 'nullable|string',
            'url' => 'nullable|url|max:191',
            'fecha_publicacion' => 'nullable|date',
            'newable_type' => 'required|string|in:App\\Models\\Doctor,App\\Models\\Lawyer,App\\Models\\Shop,App\\Models\\Association',
            'newable_id' => 'required|integer|exists:' . $this->getTableName($request->newable_type) . ',id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar que el usuario sea propietario del perfil o admin
        $user = Auth::user();
        $newable = $this->getNewableModel($request->newable_type, $request->newable_id);
        
        if (!$newable) {
            return response()->json([
                'success' => false,
                'message' => 'Perfil no encontrado'
            ], 404);
        }

        // Verificar propiedad (solo el dueño o admin pueden crear)
        if ($newable->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para crear noticias en este perfil'
            ], 403);
        }

        $news = News::create([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'url' => $request->url,
            'fecha_publicacion' => $request->fecha_publicacion ?? now(),
            'newable_type' => $request->newable_type,
            'newable_id' => $request->newable_id,
        ]);

        // Cargar relaciones para la respuesta
        $news->load(['newable', 'comments.user']);

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
        $news = News::with(['newable', 'comments.user'])->find($id);

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

        // Verificar propiedad (solo el dueño o admin pueden editar)
        $user = Auth::user();
        $newable = $news->newable;
        
        if ($newable && $newable->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para editar esta noticia'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'sometimes|required|string|max:191',
            'descripcion' => 'nullable|string',
            'url' => 'nullable|url|max:191',
            'fecha_publicacion' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $news->update($request->only([
            'titulo',
            'descripcion',
            'url',
            'fecha_publicacion'
        ]));

        $news->load(['newable', 'comments.user']);

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

        // Verificar propiedad (solo el dueño o admin pueden eliminar)
        $user = Auth::user();
        $newable = $news->newable;
        
        if ($newable && $newable->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para eliminar esta noticia'
            ], 403);
        }

        // Eliminar comentarios asociados
        $news->comments()->delete();
        
        // Eliminar noticia
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

        $conditions = [];

        if ($user->doctor) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Doctor',
                'newable_id' => $user->doctor->id
            ];
        }

        if ($user->lawyer) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Lawyer',
                'newable_id' => $user->lawyer->id
            ];
        }

        if ($user->shop) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Shop',
                'newable_id' => $user->shop->id
            ];
        }

        if ($user->association) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Association',
                'newable_id' => $user->association->id
            ];
        }

        if (empty($conditions)) {
            return response()->json([]);
        }

        $query = News::with(['newable', 'comments.user']);

        foreach ($conditions as $index => $condition) {
            if ($index === 0) {
                $query->where('newable_type', $condition['newable_type'])
                      ->where('newable_id', $condition['newable_id']);
            } else {
                $query->orWhere(function ($q) use ($condition) {
                    $q->where('newable_type', $condition['newable_type'])
                      ->where('newable_id', $condition['newable_id']);
                });
            }
        }

        $news = $query->latest('created_at')->take(5)->get();

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

        $conditions = [];

        if ($user->doctor) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Doctor',
                'newable_id' => $user->doctor->id
            ];
        }

        if ($user->lawyer) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Lawyer',
                'newable_id' => $user->lawyer->id
            ];
        }

        if ($user->shop) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Shop',
                'newable_id' => $user->shop->id
            ];
        }

        if ($user->association) {
            $conditions[] = [
                'newable_type' => 'App\\Models\\Association',
                'newable_id' => $user->association->id
            ];
        }

        if (empty($conditions)) {
            return response()->json([]);
        }

        $query = News::with(['newable', 'comments.user']);

        foreach ($conditions as $index => $condition) {
            if ($index === 0) {
                $query->where('newable_type', $condition['newable_type'])
                      ->where('newable_id', $condition['newable_id']);
            } else {
                $query->orWhere(function ($q) use ($condition) {
                    $q->where('newable_type', $condition['newable_type'])
                      ->where('newable_id', $condition['newable_id']);
                });
            }
        }

        $news = $query->latest('created_at')->get();

        return response()->json($news);
    }

    /**
     * Método auxiliar para obtener el nombre de la tabla
     */
    private function getTableName($type)
    {
        $map = [
            'App\\Models\\Doctor' => 'doctors',
            'App\\Models\\Lawyer' => 'lawyers',
            'App\\Models\\Shop' => 'shops',
            'App\\Models\\Association' => 'associations',
        ];
        return $map[$type] ?? 'users';
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
     * Método de depuración para verificar noticias de un usuario
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

        // Verificar si ya existe el like
        $existingLike = Like::where([
            'user_id' => $user->id,
            'likeable_type' => 'App\\Models\\News',
            'likeable_id' => $news->id
        ])->first();

        if ($existingLike) {
            // Si ya existe, lo eliminamos (quitamos like)
            $existingLike->delete();
            
            // Contar likes actuales
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
            // Si no existe, lo creamos (damos like)
            $like = Like::create([
                'user_id' => $user->id,
                'likeable_type' => 'App\\Models\\News',
                'likeable_id' => $news->id
            ]);

            // Contar likes actuales
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

        // Obtener IDs de las noticias que le gustan al usuario
        $likedNewsIds = Like::where([
            'user_id' => $user->id,
            'likeable_type' => 'App\\Models\\News'
        ])->pluck('likeable_id');

        // Obtener las noticias
        $news = News::with(['newable', 'comments.user'])
            ->whereIn('id', $likedNewsIds)
            ->latest('created_at')
            ->get();

        return response()->json($news);
    }
}
