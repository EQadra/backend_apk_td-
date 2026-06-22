<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Like; // 👈 Cambiar a Like
use App\Models\Product;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;
use App\Models\Association;
use App\Models\Post;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    private function getModelClass(string $type)
    {
        return match ($type) {
            'product' => Product::class,
            'service' => Service::class,
            'doctor' => Doctor::class,
            'lawyer' => Lawyer::class,
            'shop' => Shop::class,
            'association' => Association::class,
            'post' => Post::class,
            'news' => News::class,
            default => null,
        };
    }

    /**
     * Agregar / quitar favorito (usando tabla likes)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'id' => 'required|integer',
        ]);

        $modelClass = $this->getModelClass($request->type);

        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo inválido'
            ], 422);
        }

        // 👇 Usar Like en lugar de Favorite
        $favorite = Like::where([
            'user_id' => Auth::id(),
            'likeable_type' => $modelClass,
            'likeable_id' => $request->id,
        ])->first();

        if ($favorite) {
            $favorite->delete();

            // Obtener el conteo actualizado
            $count = Like::where([
                'likeable_type' => $modelClass,
                'likeable_id' => $request->id,
            ])->count();

            return response()->json([
                'favorited' => false,
                'likes_count' => $count,
                'message' => 'Eliminado de favoritos'
            ]);
        }

        Like::create([
            'user_id' => Auth::id(),
            'likeable_type' => $modelClass,
            'likeable_id' => $request->id,
        ]);

        // Obtener el conteo actualizado
        $count = Like::where([
            'likeable_type' => $modelClass,
            'likeable_id' => $request->id,
        ])->count();

        return response()->json([
            'favorited' => true,
            'likes_count' => $count,
            'message' => 'Agregado a favoritos'
        ]);
    }

    /**
     * Mis favoritos (usando tabla likes)
     */
    public function myFavorites()
    {
        // 👇 Usar Like en lugar de Favorite
        $favorites = Like::with('likeable') // 👈 Cambiar a likeable
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        // Transformar para que coincida con el frontend
        $transformed = $favorites->map(function ($like) {
            return [
                'id' => $like->id,
                'user_id' => $like->user_id,
                'favoritable_type' => $like->likeable_type,
                'favoritable_id' => $like->likeable_id,
                'favoritable' => $like->likeable,
                'created_at' => $like->created_at,
                'updated_at' => $like->updated_at,
            ];
        });

        return response()->json($transformed);
    }

    /**
     * Favoritos por tipo (usando tabla likes)
     */
    public function byType($type)
    {
        $modelClass = $this->getModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo inválido'
            ], 422);
        }

        // 👇 Usar Like en lugar de Favorite
        $favorites = Like::with('likeable') // 👈 Cambiar a likeable
            ->where('user_id', Auth::id())
            ->where('likeable_type', $modelClass)
            ->latest()
            ->get();

        // Transformar para que coincida con el frontend
        $transformed = $favorites->map(function ($like) {
            return [
                'id' => $like->id,
                'user_id' => $like->user_id,
                'favoritable_type' => $like->likeable_type,
                'favoritable_id' => $like->likeable_id,
                'favoritable' => $like->likeable,
                'created_at' => $like->created_at,
                'updated_at' => $like->updated_at,
            ];
        });

        return response()->json($transformed);
    }

    /**
     * Verificar si es favorito (usando tabla likes)
     */
    public function check($type, $id)
    {
        $modelClass = $this->getModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo inválido'
            ], 422);
        }

        // 👇 Usar Like en lugar de Favorite
        $exists = Like::where([
            'user_id' => Auth::id(),
            'likeable_type' => $modelClass,
            'likeable_id' => $id,
        ])->exists();

        $count = Like::where([
            'likeable_type' => $modelClass,
            'likeable_id' => $id,
        ])->count();

        return response()->json([
            'favorited' => $exists,
            'likes_count' => $count
        ]);
    }

    /**
     * Obtener TODOS los favoritos del usuario agrupados (productos + noticias)
     */
    public function getAllFavorites()
    {
        try {
            $user = Auth::user();

            // Obtener todos los likes del usuario
            $likes = Like::where('user_id', $user->id)
                ->with('likeable')
                ->get();

            // Transformar para que coincida con el frontend
            $transformed = $likes->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user_id' => $like->user_id,
                    'favoritable_type' => $like->likeable_type,
                    'favoritable_id' => $like->likeable_id,
                    'favoritable' => $like->likeable,
                    'created_at' => $like->created_at,
                    'updated_at' => $like->updated_at,
                ];
            });

            return response()->json($transformed, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener favoritos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}