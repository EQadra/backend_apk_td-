<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    
    public function index()
    {
        // Cargar shop + user + comments
        $products = Product::with([
            'productable.user',   // Reemplaza store.user
            'comments.user'       // Comentarios con usuario
        ])->latest()->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer|exists:shops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string',
            'stock' => 'nullable|integer|min:0',
        ]);

        // Crear producto polimórfico
        $product = Product::create([
            'productable_id'   => $request->store_id,
            'productable_type' => \App\Models\Shop::class,
            'name'             => $request->name,
            'description'      => $request->description,
            'price'            => $request->price,
            'image'            => $request->image,
            'stock'            => $request->stock,
        ]);

        return response()->json(['message' => 'Product created.', 'data' => $product], 201);
    }

    public function show($id)
    {
        $product = Product::with([
            'productable.user', // TIENDA + USUARIO
            'comments.user'
        ])->findOrFail($id);

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());

        return response()->json(['message' => 'Product updated.', 'data' => $product]);
    }

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
        'productable.user', // tienda + usuario
        'comments.user'
    ])
    ->latest()   // ORDER BY created_at DESC
    ->limit(5)   // SOLO 5
    ->get();

    return response()->json($products);
}

}
