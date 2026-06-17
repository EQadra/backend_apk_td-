<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    /**
     * List all news or filter by type
     */
    public function index(Request $request)
    {
        $query = News::with(['newable.user', 'comments.user']);

        if ($request->has('type')) {
            $type = match ($request->type) {
                'doctor' => 'App\Models\Doctor',
                'lawyer' => 'App\Models\Lawyer',
                'shop' => 'App\Models\Shop',
                'association' => 'App\Models\Association',
                default => null
            };

            if ($type) {
                $query->where('newable_type', $type);
            }
        }

        return response()->json($query->latest()->get());
    }

    /**
     * Store a new news item
     */
        public function store(Request $request)
        {
            $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'url' => 'nullable|url',
            ]);

            $user = Auth::user();

            $news = $user->news()->create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'url' => $request->url,
                'fecha_publicacion' => now(),
            ]);

            return response()->json([
                'message' => 'Creado correctamente',
                'data' => $news->load(['comments.user'])
            ], 201);
        }
    /**
     * Show single news
     */
    public function show(News $news)
    {
        return response()->json(
            $news->load(['newable.user', 'comments.user'])
        );
    }

    /**
     * Home news
     */
    public function home()
    {
        return response()->json(
            News::with([
                'comments.user',
                'newable.user'
            ])
                ->latest()
                ->take(4)
                ->get()
        );
    }

    /**
     * Update news
     */
    public function update(Request $request, News $news)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'url' => 'nullable|url',
        ]);

        $news->update([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'url' => $request->url ?? $news->url,
        ]);

        return response()->json($news->load(['newable.user', 'comments.user']));
    }

    /**
     * Delete news
     */
    public function destroy(News $news)
    {
        $news->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }

    /**
     * Latest news (global feed)
     */
    public function latest()
    {
        $news = News::with([
            'newable.user',
            'comments.user'
        ])
            ->latest()
            ->limit(3)
            ->get();

        return response()->json($news);
    }

    /**
     * Add comment
     */
    public function addComment(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string|max:500'
        ]);

        $news = News::findOrFail($id);

        $comment = $news->comments()->create([
            'content' => $request->content,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Comentario agregado',
            'comment' => $comment->load('user')
        ], 201);
    }

    /**
     * My news (FIXED)
     */
        public function myLatestNews()
        {
            $user = Auth::user();

            return $user->news()
                ->with(['comments.user'])
                ->latest()
                ->take(2)
                ->get();
        }
}