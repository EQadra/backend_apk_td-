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
        $query = News::query();

        if ($request->has('type')) {
            $type = match($request->type) {
                'doctor' => 'App\Models\Doctor',
                'lawyer' => 'App\Models\Lawyer',
                'shop' => 'App\Models\Shop',
                'association' => 'App\Models\Association',
                default => null
            };
            if ($type) $query->where('newable_type', $type);
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
        'image' => 'nullable|image|max:2048',
    ]);

    $user = Auth::user();

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('news', 'public');
    }

    $news = News::create([
        'titulo' => $request->titulo,
        'descripcion' => $request->descripcion,
        'image' => $imagePath,
        'fecha_publicacion' => now(),
        'newable_type' => $user->role_to_model(),
        'newable_id' => $user->model()->id,
    ]);

    return response()->json($news, 201);
}


    /**
     * Show a single news item
     */
    public function show(News $news)
    {
        return response()->json($news);
    }

    /**
     * Home controller for home news item
     */
    public function home()
{
    return response()->json(
        News::latest()->take(4)->get()
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
        ]);

        $news->update([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'url' => $request->url ?? $news->url,
        ]);

        return response()->json($news);
    }

    /**
     * Delete news
     */
    public function destroy(News $news)
    {
        $news->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // GET /api/news/latest
public function latest()
{
    $news = News::with([
        'newable',       // doctor | lawyer | shop | association
        'newable.user'   // usuario dueño
    ])
    ->latest()
    ->limit(3)
    ->get();

    return response()->json($news);
}

}
