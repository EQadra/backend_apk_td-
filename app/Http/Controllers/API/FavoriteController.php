<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
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
     * Agregar / quitar favorito
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

        $favorite = Favorite::where([
            'user_id' => Auth::id(),
            'favoritable_type' => $modelClass,
            'favoritable_id' => $request->id,
        ])->first();

        if ($favorite) {

            $favorite->delete();

            return response()->json([
                'favorited' => false,
                'message' => 'Eliminado de favoritos'
            ]);
        }

        Favorite::create([
            'user_id' => Auth::id(),
            'favoritable_type' => $modelClass,
            'favoritable_id' => $request->id,
        ]);

        return response()->json([
            'favorited' => true,
            'message' => 'Agregado a favoritos'
        ]);
    }

    /**
     * Mis favoritos
     */
    public function myFavorites()
    {
        $favorites = Favorite::with('favoritable')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json($favorites);
    }

    /**
     * Favoritos por tipo
     */
    public function byType($type)
    {
        $modelClass = $this->getModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo inválido'
            ], 422);
        }

        $favorites = Favorite::with('favoritable')
            ->where('user_id', Auth::id())
            ->where('favoritable_type', $modelClass)
            ->latest()
            ->get();

        return response()->json($favorites);
    }

    /**
     * Verificar si es favorito
     */
    public function check($type, $id)
    {
        $modelClass = $this->getModelClass($type);

        $exists = Favorite::where([
            'user_id' => Auth::id(),
            'favoritable_type' => $modelClass,
            'favoritable_id' => $id,
        ])->exists();

        return response()->json([
            'favorited' => $exists
        ]);
    }
}