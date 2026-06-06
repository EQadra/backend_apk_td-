<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Association;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

use App\Models\Traits\UploadProfileImage;

class AssociationController extends Controller
{
    use UploadProfileImage;
    /**
     * LISTADO
     */
    public function index()
    {
        try {
            return Association::with([
                'user',
                'feedbacks',
                'products',
                'posts.comments'
            ])
            ->latest()
            ->paginate(10);

        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'message' => 'Error loading associations'
            ], 500);
        }
    }

    /**
     * MI ASOCIACIÓN
     */
    public function me()
    {
        try {
            return Association::with([
                'user',
                'feedbacks.user',
                'posts.comments',
                'products'
            ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Association not found'
            ], 404);
        }
    }

    /**
     * CREAR
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'address'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|string',
            'website'     => 'nullable|string|max:255',
        ]);

        $exists = Association::where('user_id', Auth::id())->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You already have an association'
            ], 409);
        }

        $association = Association::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'description' => $request->description,
            'city'        => $request->city,
            'address'     => $request->address,
            'phone'       => $request->phone,
            'image'       => $request->image,
            'website'     => $request->website,
        ]);

        return response()->json($association, 201);
    }

    /**
     * VER
     */
    public function show($id)
    {
        try {
            return Association::with([
                'user',
                'products',
                'feedbacks.user',
                'posts.comments'
            ])->findOrFail($id);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Association not found'
            ], 404);
        }
    }

    /**
     * ACTUALIZAR
     */
    public function update(Request $request, $id)
    {
        $association = Association::findOrFail($id);

        if (
            $association->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'address'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|string',
            'website'     => 'nullable|string|max:255',
        ]);

        $association->update($request->only([
            'name',
            'description',
            'city',
            'address',
            'phone',
            'image',
            'website'
        ]));

        return response()->json([
            'message' => 'Association updated',
            'data' => $association
        ]);
    }

    /**
     * ELIMINAR
     */
    public function destroy($id)
    {
        $association = Association::findOrFail($id);

        if (
            $association->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $association->delete();

        return response()->json([
            'message' => 'Association deleted'
        ]);
    }

    /**
     * ÚLTIMOS
     */
    public function latest()
    {
        return Association::with([
            'user',
            'products',
            'feedbacks.user',
            'posts.comments',
            'news'
        ])
        ->latest()
        ->take(5)
        ->get();
    }

    /**
     * 🔥 SOLO IMAGEN (IMPORTANTE PARA FRONTEND)
     */


    public function updateImage(Request $request)
{
    $association = Association::where(
        'user_id',
        Auth::id()
    )->firstOrFail();

    return $this->uploadImage(
        $request,
        $association,
        'associations'
    );
}
}