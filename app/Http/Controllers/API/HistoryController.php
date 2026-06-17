<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\History;
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

class HistoryController extends Controller
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
     * Registrar vista
     */
    public function store(Request $request)
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

        $history = History::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'historyable_type' => $modelClass,
                'historyable_id' => $request->id,
            ],
            [
                'views' => 0,
                'last_viewed_at' => now(),
            ]
        );

        $history->increment('views');

        $history->update([
            'last_viewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Vista registrada'
        ]);
    }

    /**
     * Todo mi historial
     */
    public function myHistory()
    {
        $history = History::with('historyable')
            ->where('user_id', Auth::id())
            ->latest('last_viewed_at')
            ->get();

        return response()->json($history);
    }

    /**
     * Historial por tipo
     */
    public function byType($type)
    {
        $modelClass = $this->getModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo inválido'
            ], 422);
        }

        $history = History::with('historyable')
            ->where('user_id', Auth::id())
            ->where('historyable_type', $modelClass)
            ->latest('last_viewed_at')
            ->get();

        return response()->json($history);
    }

    /**
     * Más vistos globalmente
     */
    public function mostViewed($type)
    {
        $modelClass = $this->getModelClass($type);

        if (!$modelClass) {
            return response()->json([
                'message' => 'Tipo inválido'
            ], 422);
        }

        $history = History::with('historyable')
            ->where('historyable_type', $modelClass)
            ->orderByDesc('views')
            ->take(10)
            ->get();

        return response()->json($history);
    }

    /**
     * Limpiar historial usuario
     */
    public function clear()
    {
        History::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'Historial eliminado'
        ]);
    }
}