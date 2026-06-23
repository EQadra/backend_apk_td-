<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ShopController extends Controller
{
    use UploadImage;

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
            ->get();

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
            'category'    => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'schedule'    => 'nullable|string|max:191',
        ]);

        $exists = Shop::where('user_id', Auth::id())->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You already have a shop'
            ], 409);
        }

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/shops';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/shops/' . $filename;
        }

        $shop = Shop::create([
            'user_id'     => Auth::id(),
            'name'        => $request->name,
            'category'    => $request->category,
            'description' => $request->description,
            'address'     => $request->address,
            'city'        => $request->city,
            'phone'       => $request->phone,
            'image'       => $imageUrl,
            'schedule'    => $request->schedule,
        ]);

        return response()->json([
            'message' => 'Shop created',
            'data' => $shop
        ], 201);
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

        if ($shop->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'category'    => 'nullable|string|max:191',
            'description' => 'nullable|string',
            'address'     => 'nullable|string|max:255',
            'city'        => 'nullable|string|max:100',
            'phone'       => 'nullable|string|max:20',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'schedule'    => 'nullable|string|max:191',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImageFromProduction($shop->image);
            
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/shops';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/shops/' . $filename;
            
            $shop->image = $imageUrl;
        }

        if ($request->has('image') && is_string($request->image)) {
            $shop->image = $request->image;
        }

        $shop->update($request->only([
            'name',
            'category',
            'description',
            'address',
            'city',
            'phone',
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

        if ($shop->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->deleteImageFromProduction($shop->image);

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
        $shop = Shop::where('user_id', Auth::id())->firstOrFail();

        return $this->uploadImageToProduction(
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
              ->orWhere('address', 'LIKE', "%{$query}%")
              ->orWhere('city', 'LIKE', "%{$query}%")
              ->orWhere('phone', 'LIKE', "%{$query}%")
              ->orWhere('category', 'LIKE', "%{$query}%");
        })
        ->latest()
        ->get();

        return response()->json($shops);
    }
}