<?php

namespace App\Models\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

trait UploadImage
{
    /**
     * Subir imagen usando Storage de Laravel
     * 
     * @param Request $request
     * @param mixed $model
     * @param string $folder - 'productos', 'doctors', 'lawyers', 'associations', 'shops'
     * @param string $fieldName - nombre del campo de la imagen (por defecto 'image')
     * @return \Illuminate\Http\JsonResponse
     */
    protected function uploadImageToProduction(Request $request, $model, string $folder, string $fieldName = 'image')
    {
        try {
            if (!$request->hasFile($fieldName)) {
                return response()->json([
                    'error' => 'No se encontró ninguna imagen'
                ], 422);
            }

            $file = $request->file($fieldName);
            
            if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])) {
                return response()->json([
                    'error' => 'Formato de imagen no válido. Use JPG, PNG o WEBP'
                ], 422);
            }

            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // ✅ GUARDAR EN STORAGE
            $path = $file->storeAs('imagenes_app/' . $folder, $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'error' => 'Error al guardar la imagen en el servidor'
                ], 500);
            }

            // ✅ URL CON STORAGE
            $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
            
            if ($isDevelopment) {
                $imageUrl = 'http://192.168.203.82:8000/storage/' . $path;
            } else {
                $imageUrl = 'https://apiapk.tudealer.app/storage/' . $path;
            }
            
            if ($model->$fieldName) {
                $this->deleteImageFromProduction($model->$fieldName);
            }
            
            $model->update([$fieldName => $imageUrl]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => [
                    'image_url' => $imageUrl,
                    'image' => $imageUrl,
                    'filename' => $filename,
                    'path' => $path
                ]
            ], 200);
            
        } catch (Exception $e) {
            Log::error('Error al subir imagen: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al subir la imagen',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen del servidor usando Storage
     */
    protected function deleteImageFromProduction(?string $imageUrl)
    {
        if (!$imageUrl) return;
        
        try {
            $cleanUrl = explode('?', $imageUrl)[0];
            
            if (strpos($cleanUrl, '/storage/') !== false) {
                $path = substr($cleanUrl, strpos($cleanUrl, '/storage/') + 9);
                
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                    Log::info('Imagen eliminada: ' . $path);
                }
            }
        } catch (Exception $e) {
            Log::error('Error al eliminar imagen: ' . $e->getMessage());
        }
    }
}