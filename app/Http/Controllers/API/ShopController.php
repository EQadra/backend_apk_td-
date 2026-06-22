<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

use App\Models\Traits\UploadProfileImage;

class ShopController extends Controller
{
    use UploadProfileImage;
    
    /**
     * LISTADO
     */
    public function index()
    {
        try {
            return Shop::with([
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
                'message' => 'Error loading shops'
            ], 500);
        }
    }

    /**
     * MI TIENDA
     */
    public function me()
    {
        try {
            return Shop::with([
                'user',
                'feedbacks.user',
                'posts.comments',
                'products'
            ])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Shop not found'
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
            'category'    => 'nullable|string|max:100',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|string',
            'website'     => 'nullable|string|max:255',
            'schedule'    => 'nullable|string',
        ]);

        $exists = Shop::where('user_id', Auth::id())->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You already have a shop'
            ], 409);
        }

        $shop = Shop::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'description' => $request->description,
            'category'    => $request->category,
            'address'     => $request->address,
            'city'        => $request->city,
            'phone'       => $request->phone,
            'image'       => $request->image,
            'website'     => $request->website,
            'schedule'    => $request->schedule,
        ]);

        return response()->json($shop, 201);
    }

    /**
     * VER
     */
    public function show($id)
    {
        try {
            return Shop::with([
                'user',
                'products',
                'feedbacks.user',
                'posts.comments'
            ])->findOrFail($id);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Shop not found'
            ], 404);
        }
    }

    /**
     * ACTUALIZAR
     */
    public function update(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);

        if (
            $shop->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|string',
            'website'     => 'nullable|string|max:255',
            'schedule'    => 'nullable|string',
        ]);

        $shop->update($request->only([
            'name',
            'description',
            'category',
            'address',
            'city',
            'phone',
            'image',
            'website',
            'schedule'
        ]));

        return response()->json([
            'message' => 'Shop updated',
            'data' => $shop
        ]);
    }

    /**
     * ELIMINAR
     */
    public function destroy($id)
    {
        $shop = Shop::findOrFail($id);

        if (
            $shop->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shop->delete();

        return response()->json([
            'message' => 'Shop deleted'
        ]);
    }

    /**
     * ÚLTIMOS
     */
    public function latest()
    {
        return Shop::with([
            'user',
            'products',
            'feedbacks.user',
            'posts.comments'
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
        $shop = Shop::where(
            'user_id',
            Auth::id()
        )->firstOrFail();

        return $this->uploadImage(
            $request,
            $shop,
            'shops'
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

        $shops = Shop::with([
            'user',
            'posts'
        ])
        ->where(function($q) use ($query) {
            $q->where('name', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%")
              ->orWhere('category', 'LIKE', "%{$query}%")
              ->orWhere('address', 'LIKE', "%{$query}%")
              ->orWhere('city', 'LIKE', "%{$query}%")
              ->orWhere('phone', 'LIKE', "%{$query}%");
        })
        ->latest()
        ->get();

        return response()->json($shops);
    }
}