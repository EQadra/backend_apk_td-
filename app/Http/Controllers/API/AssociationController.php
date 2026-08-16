<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AssociationController extends Controller
{
    use UploadImage;
    
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
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'website'     => 'nullable|string|max:255',
        ]);

        $exists = Association::where('user_id', Auth::id())->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You already have an association'
            ], 409);
        }

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/associations';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/associations/' . $filename;
        }

        $association = Association::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'description' => $request->description,
            'city'        => $request->city,
            'address'     => $request->address,
            'phone'       => $request->phone,
            'image'       => $imageUrl,
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

        if ($association->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'city'        => 'nullable|string|max:100',
            'address'     => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'website'     => 'nullable|string|max:255',
        ]);

        if ($request->hasFile('image')) {
            // Eliminar imagen anterior
            $this->deleteImageFromProduction($association->image);
            
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/associations';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/associations/' . $filename;
            
            $association->image = $imageUrl;
        }

        if ($request->has('image') && is_string($request->image)) {
            $association->image = $request->image;
        }

        $association->update($request->only([
            'name',
            'description',
            'city',
            'address',
            'phone',
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

        if ($association->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->deleteImageFromProduction($association->image);

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
     * ACTUALIZAR IMAGEN
     */
    public function updateImage(Request $request)
    {
        $association = Association::where('user_id', Auth::id())->firstOrFail();
        
        return $this->uploadImageToProduction(
            $request, 
            $association, 
            'associations'
        );
    }

    /**
     * BÚSQUEDA
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $associations = Association::with([
            'user',
            'posts'
        ])
        ->where(function($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%")
              ->orWhere('address', 'LIKE', "%{$query}%")
              ->orWhere('city', 'LIKE', "%{$query}%")
              ->orWhere('phone', 'LIKE', "%{$query}%");
        })
        ->latest()
        ->get();

        return response()->json($associations);
    }
}