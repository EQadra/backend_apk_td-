<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Association;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AssociationController extends Controller
{
    /**
     * Display a listing of associations.
     */
    public function index()
    {
        try {
            $associations = Association::with([
                'user',
                'feedbacks',
                'products',
                'news',
                'posts.comments'
            ])->paginate(10);

            return response()->json($associations);

        } catch (Exception $e) {
            Log::error('Association index error: '.$e->getMessage());
            return response()->json([
                'error' => 'Failed to load associations.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/associations/me
     * Datos de la asociación del usuario logueado
     */
    public function me()
    {
        try {
            $association = Association::with([
                'user',
                'feedbacks.user',
                'posts.comments',
                'news'
            ])->where('user_id', Auth::id())->firstOrFail();

            return response()->json($association);

        } catch (Exception $e) {
            Log::error('Association me error: '.$e->getMessage());
            return response()->json([
                'error'   => 'Failed to load your association.',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store a newly created association.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'city'        => 'nullable|string|max:100',
                'address'     => 'nullable|string|max:255',
                'phone'       => 'nullable|string|max:20',
                'image'       => 'nullable|string',
                'website'     => 'nullable|string|max:255',
            ]);

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

            return response()->json([
                'message' => 'Association created successfully.',
                'data'    => $association
            ], 201);

        } catch (Exception $e) {
            Log::error('Association store error: '.$e->getMessage());
            return response()->json([
                'error'   => 'Failed to create association.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a specific association.
     */
    public function show($id)
    {
        try {
            $association = Association::with([
                'user',
                  'products',  
                'feedbacks.user',
                'posts.comments',
                'news'
            ])->findOrFail($id);

            return response()->json($association);

        } catch (Exception $e) {
            Log::error("Association show error (ID: $id): ".$e->getMessage());
            return response()->json([
                'error'   => 'Failed to load association.',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified association.
     */
    public function update(Request $request, $id)
    {
        try {
            $association = Association::findOrFail($id);

            if ($association->user_id !== Auth::id() &&
                !Auth::user()->hasRole('admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $association->update($request->only([
                'name', 'description', 'city', 'address', 'phone', 'image', 'website'
            ]));

            return response()->json([
                'message' => 'Association updated successfully.',
                'data'    => $association
            ]);

        } catch (Exception $e) {
            Log::error("Association update error (ID: $id): ".$e->getMessage());
            return response()->json([
                'error'   => 'Failed to update association.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified association.
     */
    public function destroy($id)
    {
        try {
            $association = Association::findOrFail($id);

            if ($association->user_id !== Auth::id() &&
                !Auth::user()->hasRole('admin')) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $association->delete();

            return response()->json([
                'message' => 'Association deleted successfully.'
            ]);

        } catch (Exception $e) {
            Log::error("Association delete error (ID: $id): ".$e->getMessage());
            return response()->json([
                'error'   => 'Failed to delete association.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/associations/latest
     * Home endpoint: solo 5 asociaciones más recientes
     */
    public function latest()
    {
        try {
            $associations = Association::with(['user'])
                ->latest()
                ->limit(5)
                ->get();

            return response()->json($associations);

        } catch (Exception $e) {
            Log::error('Association latest error: '.$e->getMessage());
            return response()->json([
                'error' => 'Failed to load latest associations.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
