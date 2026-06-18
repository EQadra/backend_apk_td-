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
            'newable',
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
     * ✅ VERSIÓN CORREGIDA - SIN load()
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

        // Array para almacenar las condiciones
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

        // Si no tiene perfiles, retornar array vacío
        if (empty($conditions)) {
            return response()->json([]);
        }

        // ✅ CONSTRUIR CONSULTA CON QUERY BUILDER - SIN load()
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

        // ✅ Obtener resultados - esto devuelve una Eloquent Collection
        $news = $query->latest('created_at')->take(5)->get();

        // ✅ No necesitas load() porque ya usaste with() arriba
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
}