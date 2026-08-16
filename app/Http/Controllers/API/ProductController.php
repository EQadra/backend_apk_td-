<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductController extends Controller
{
    use UploadImage;

    /** 
     * GET /api/products
     * Listar todos los productos
     */
    public function index()
    {
        $products = Product::with([
            'productable.user',
            'comments.user'
        ])->latest()->get();

        return response()->json($products);
    }

    /**
     * POST /api/products
     * Crear un nuevo producto
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'           => 'required|string|max:255',
                'description'    => 'nullable|string',
                'price'          => 'required|numeric',
                'stock'          => 'nullable|integer',
                'image'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
                'store_id'       => 'nullable|integer',
                'association_id' => 'nullable|integer',
            ]);

            $imageUrl = null;

            // 🔥 SUBIR IMAGEN usando el método del trait
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/productos';
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                
                $file->move($destinationPath, $filename);
                $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/productos/' . $filename;
            }

            // Detectar dueño del producto
            if ($request->store_id) {
                $productableType = 'App\Models\Shop';
                $productableId   = $request->store_id;
            } elseif ($request->association_id) {
                $productableType = 'App\Models\Association';
                $productableId   = $request->association_id;
            } else {
                return response()->json([
                    'error' => 'Debe enviar store_id o association_id'
                ], 422);
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
                'error'   => 'Error al crear producto',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/products/{id}
     * Mostrar un producto específico
     */
    public function show($id)
    {
        $product = Product::with([
            'productable.user',
            'comments.user'
        ])->findOrFail($id);

        return response()->json($product);
    }

    /**
     * PUT /api/products/{id}
     * Actualizar un producto
     */
public function update(Request $request, $id)
{
    try {
        $product = Product::findOrFail($id);

        // ✅ VERIFICACIÓN DE PERMISOS
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        
        // Obtener el dueño del producto (tienda o asociación)
        $isOwner = false;
        if ($product->productable) {
            // Verificar si el usuario es dueño del perfil (shop o association)
            if (isset($product->productable->user_id)) {
                $isOwner = $product->productable->user_id === $user->id;
            }
        }

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'message' => 'No autorizado para editar este producto'
            ], 403);
        }

        $request->validate([
            'name'        => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric',
            'stock'       => 'nullable|integer',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        // Si sube nueva imagen: borrar antigua + guardar nueva
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior
            $this->deleteImageFromProduction($product->image);
            
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/productos';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/productos/' . $filename;
            
            $product->image = $imageUrl;
        }

        // Si viene imagen como string (base64 o URL)
        if ($request->has('image') && is_string($request->image)) {
            $product->image = $request->image;
        }

        $product->update([
            'name'        => $request->name ?? $product->name,
            'description' => $request->description ?? $product->description,
            'price'       => $request->price ?? $product->price,
            'stock'       => $request->stock ?? $product->stock,
        ]);

        return response()->json([
            'message' => 'Producto actualizado',
            'data'    => $product
        ]);

    } catch (Exception $e) {
        Log::error('Product update error: ' . $e->getMessage());
        return response()->json([
            'error'   => 'Error al actualizar producto',
            'message' => $e->getMessage()
        ], 500);
    }
}
    /**
     * DELETE /api/products/{id}
     * Eliminar un producto
     */
public function destroy($id)
{
    try {
        $product = Product::findOrFail($id);

        // ✅ VERIFICACIÓN DE PERMISOS
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        
        // Obtener el dueño del producto (tienda o asociación)
        $isOwner = false;
        if ($product->productable) {
            if (isset($product->productable->user_id)) {
                $isOwner = $product->productable->user_id === $user->id;
            }
        }

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'message' => 'No autorizado para eliminar este producto'
            ], 403);
        }

        // Eliminar imagen
        $this->deleteImageFromProduction($product->image);

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente'
        ]);

    } catch (Exception $e) {
        Log::error('Product delete error: ' . $e->getMessage());
        return response()->json([
            'error'   => 'Error al eliminar producto',
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * GET /api/products/latest
     * Obtener los últimos 5 productos
     */
    public function latest()
    {
        $products = Product::with([
            'productable.user',
            'comments.user'
        ])->latest()->limit(5)->get();

        return response()->json($products);
    }
}