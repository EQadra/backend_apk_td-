<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Post;

class HomeController extends Controller
{
    /**
     * Home data
     * - Últimas noticias
     * - Últimos posts
     * - Productos estáticos
     */
    public function index()
    {
        // 🔹 Últimas 4 noticias
        $news = News::with('newable')
            ->latest()
            ->take(4)
            ->get();

        // 🔹 Últimos 4 posts
        $posts = Post::with(['postable', 'user'])
            ->latest()
            ->take(4)
            ->get();

        // 🔹 Productos estáticos (mock)
        $products = [
            [
                'id' => 1,
                'name' => 'Consulta Médica',
                'price' => 50,
                'icon' => 'medical',
            ],
            [
                'id' => 2,
                'name' => 'Asesoría Legal',
                'price' => 80,
                'icon' => 'law',
            ],
        ];

        return response()->json([
            'news' => $news,
            'posts' => $posts,
            'products' => $products,
        ]);
    }
}
