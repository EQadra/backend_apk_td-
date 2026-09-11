<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class ProductController extends Controller
{
    use UploadImage; // ✅ USAR EL TRAIT

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
     * Crear un nuevo producto - CORREGIDO (USANDO TRAIT)
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

            // ✅ CREAR PRODUCTO PRIMERO (sin imagen)
            $product = Product::create([
                'productable_type' => $productableType,
                'productable_id'   => $productableId,
                'name'             => $request->name,
                'description'      => $request->description,
                'price'            => $request->price,
                'stock'            => $request->stock,
            ]);

            // ✅ SUBIR IMAGEN USANDO EL TRAIT (AUTOMÁTICO EN DESARROLLO Y PRODUCCIÓN)
            if ($request->hasFile('image')) {
                $result = $this->uploadImageToProduction($request, $product, 'productos', 'image');
                
                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    $data = $result->getData();
                    if (!$data->success) {
                        return $result;
                    }
                }
            }

            $product->load(['productable.user', 'comments.user']);

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
     * Actualizar un producto - CORREGIDO (USANDO TRAIT)
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            // ✅ VERIFICACIÓN DE PERMISOS
            $user = Auth::user();
            $isAdmin = $user->hasRole('admin');
            
            $isOwner = false;
            if ($product->productable) {
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

            // ✅ ACTUALIZAR DATOS
            $product->update([
                'name'        => $request->name ?? $product->name,
                'description' => $request->description ?? $product->description,
                'price'       => $request->price ?? $product->price,
                'stock'       => $request->stock ?? $product->stock,
            ]);

            // ✅ SUBIR IMAGEN USANDO EL TRAIT
            if ($request->hasFile('image')) {
                $result = $this->uploadImageToProduction($request, $product, 'productos', 'image');
                
                if ($result instanceof \Illuminate\Http\JsonResponse) {
                    $data = $result->getData();
                    if (!$data->success) {
                        return $result;
                    }
                }
            }

            $product->load(['productable.user', 'comments.user']);

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
     * Eliminar un producto - CORREGIDO (USANDO TRAIT)
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            $user = Auth::user();
            $isAdmin = $user->hasRole('admin');
            
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

            // ✅ ELIMINAR IMAGEN USANDO EL TRAIT
            if ($product->image) {
                $this->deleteImageFromProduction($product->image);
            }

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
     * Obtener los últimos productos
     */
    public function latest()
    {
        $products = Product::with([
            'productable.user',
            'comments.user'
        ])->latest()->limit(10)->get();

        return response()->json($products);
    }
}