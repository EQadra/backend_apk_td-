<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Association;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * ===============================
     * GET /api/posts
     * Feed completo
     * ===============================
     */
    public function index()
    {
        $posts = Post::with([
            'user',
            'postable',
            'comments.user'
        ])
        ->latest()
        ->get();

        return response()->json($posts, 200);
    }

    /**
     * ===============================
     * POST /api/posts
     * Crear post
     * ===============================
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'content'  => 'required|string',
            'image' => 'nullable|image|max:4096',
            'category' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();

        $postableType = User::class;


$postableType = User::class;
$postableId   = $user->id;

if ($user->association) {
    $postableType = Association::class;
    $postableId   = $user->association->id;

} elseif ($user->doctor) {
    $postableType = Doctor::class;
    $postableId   = $user->doctor->id;

} elseif ($user->lawyer) {
    $postableType = Lawyer::class;
    $postableId   = $user->lawyer->id;

} elseif ($user->shop) {
    $postableType = Shop::class;
    $postableId   = $user->shop->id;
}
        $imagePath = null;

if ($request->hasFile('image')) {
    $imagePath = $request
        ->file('image')
        ->store('posts', 'public');
}

$post = Post::create([
    'user_id'       => $user->id,
    'title'         => $request->title,
    'content'       => $request->content,
    'image'         => $imagePath,
    'category'      => $request->category,
    'postable_type' => $postableType,
    'postable_id'   => $postableId,
]);

        return response()->json([
            'message' => 'Post creado correctamente',
            'data'    => $post->load([
                'user',
                'postable',
                'comments.user'
            ])
        ], 201);
    }

    /**
     * ===============================
     * GET /api/posts/{id}
     * Ver un post
     * ===============================
     */
    public function show($id)
    {
        $post = Post::with([
            'user',
            'postable',
            'comments.user'
        ])->findOrFail($id);

        return response()->json($post, 200);
    }

    /**
     * ===============================
     * GET /api/posts/home
     * Últimos posts para home
     * ===============================
     */
    public function home()
    {
        $posts = Post::with([
            'user',
            'postable'
        ])
        ->latestForHome()
        ->get();

        return response()->json($posts, 200);
    }

    /**
     * ===============================
     * DELETE /api/posts/{id}
     * Eliminar post
     * ===============================
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        if (
            $post->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post eliminado correctamente'
        ], 200);
    }


   public function myLatestPosts()
{
    return response()->json(
        Auth::user()
            ->posts()
            ->with([
                'user',
                'postable'
            ])
            ->latest()
            ->get()
    );
}
}