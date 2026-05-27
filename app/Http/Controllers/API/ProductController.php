<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    /** 
     * GET /api/products
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

            /**
             * SUBIR IMAGEN
             * Guarda en:
             * /public/imagenes_app/productos
             */
         if ($request->hasFile('image')) {
        
            $file = $request->file('image');
        
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/productos';
        
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
        
            $file->move($destinationPath, $filename);
        
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/productos/'.$filename;
        }

            /**
             * Detectar dueño del producto
             */
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
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);

            $request->validate([
                'name'        => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'price'       => 'nullable|numeric',
                'stock'       => 'nullable|integer',
                'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            /**
             * Si sube nueva imagen:
             * borrar antigua + guardar nueva
             */
            if ($request->hasFile('image')) {

                if ($product->image) {

                    $oldPath = str_replace(
                        env('APP_URL') . '/',
                        '',
                        $product->image
                    );

                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                }

                $file = $request->file('image');

                $filename =
                    time() . '_' .
                    uniqid() . '.' .
                    $file->getClientOriginalExtension();

                $path = $file->storeAs(
                    'imagenes_app/productos',
                    $filename,
                    'public'
                );

                $product->image = env('APP_URL') . '/' . $path;
            }

            $product->update([
                'name'        => $request->name ?? $product->name,
                'description' => $request->description ?? $product->description,
                'price'       => $request->price ?? $product->price,
                'stock'       => $request->stock ?? $product->stock,
                'image'       => $product->image,
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
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            /**
             * borrar imagen física
             */
            if ($product->image) {

                $path = str_replace(
                    env('APP_URL') . '/',
                    '',
                    $product->image
                );

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
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