<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductController extends Controller
{
    // GET /api/products
    public function index()
    {
        $products = Product::with([
            'productable.user',
            'comments.user'
        ])->latest()->get();

        return response()->json($products);
    }

    // POST /api/products
public function store(Request $request)
{
    try {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric',
            'stock'       => 'nullable|integer',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'store_id'    => 'nullable|integer',
            'association_id' => 'nullable|integer',
        ]);

        $imageUrl = null;

        // Subida de imagen
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('imagenes_app/productos');
            $file->move($destinationPath, $filename);

            $imageUrl = "https://tudealer.app/imagenes_app/productos/" . $filename;
        }

        // Detectar si viene de Store o Association
        if ($request->store_id) {
            $productableType = 'App\Models\Store';
            $productableId = $request->store_id;
        } elseif ($request->association_id) {
            $productableType = 'App\Models\Association';
            $productableId = $request->association_id;
        } else {
            return response()->json(['error' => 'Debe enviar store_id o association_id'], 422);
        }

        $product = Product::create([
            'productable_type' => $productableType,
            'productable_id'   => $productableId,
            'name'             => $request->name,
            'description'      => $request->description,
            'price'            => $request->price,
            'stock'            => $request->stock,
            'image'            => $imageUrl,
        ]);

        return response()->json([
            'message' => 'Producto creado correctamente',
            'data'    => $product
        ], 201);

    } catch (Exception $e) {
        Log::error('Product store error: ' . $e->getMessage());

        return response()->json([
            'error' => 'Error al crear producto',
            'message' => $e->getMessage()
        ], 500);
    }
}

    // GET /api/products/{id}
    public function show($id)
    {
        $product = Product::with([
            'productable.user',
            'comments.user'
        ])->findOrFail($id);

        return response()->json($product);
    }

    // PUT /api/products/{id}
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());

        return response()->json(['message' => 'Product updated.', 'data' => $product]);
    }

    // DELETE /api/products/{id}
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }

    // GET /api/products/latest
    public function latest()
    {
        $products = Product::with([
            'productable.user',
            'comments.user'
        ])->latest()->limit(5)->get();

        return response()->json($products);
    }
}