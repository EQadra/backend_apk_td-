<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    public function latest()
    {
        $news = News::with([
            'newable',           // Doctor, Lawyer, etc.
            'comments.user'
        ])
        ->latest('created_at')
        ->limit(15)
        ->get();

        return response()->json($news);
    }

    public function home()
    {
        $news = News::with(['newable', 'comments.user'])
            ->latest('created_at')
            ->take(6)
            ->get();

        return response()->json($news);
    }

    public function index(Request $request)
    {
        $query = News::with(['newable', 'comments.user']);

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

        $news = $query->latest('created_at')->get();

        return response()->json($news);
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

        // Cargar los perfiles del usuario
        $user->load(['doctor', 'lawyer', 'shop', 'association']);

        // Colección para almacenar todas las noticias
        $allNews = collect();

        // Obtener noticias de cada perfil
        if ($user->doctor) {
            $allNews = $allNews->merge($user->doctor->news);
        }

        if ($user->lawyer) {
            $allNews = $allNews->merge($user->lawyer->news);
        }

        if ($user->shop) {
            $allNews = $allNews->merge($user->shop->news);
        }

        if ($user->association) {
            $allNews = $allNews->merge($user->association->news);
        }

        // Ordenar por fecha de creación (más recientes primero) y tomar solo 5
        $news = $allNews
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        // Cargar las relaciones necesarias
        $news->load(['newable', 'comments.user']);

        return response()->json($news);
    }
}