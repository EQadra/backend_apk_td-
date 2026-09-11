<?php

namespace App\Models\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

trait UploadImage
{
    /**
     * Subir imagen al servidor
     */
    protected function uploadImageToProduction(Request $request, $model, string $folder, string $fieldName = 'image')
    {
        try {
            if (!$request->hasFile($fieldName)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No se encontró ninguna imagen'
                ], 422);
            }

            $file = $request->file($fieldName);
            
            if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Formato de imagen no válido. Use JPG, PNG o WEBP'
                ], 422);
            }

            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
            
            if ($isDevelopment) {
                // 📱 DESARROLLO (WAMP / Local)
                $destinationPath = public_path('imagenes_app/' . $folder);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                
                // ✅ USAR LA IP CORRECTA PARA DESARROLLO (LA QUE USA EL FRONTEND)
                $baseUrl = 'http://10.23.248.82:8000';
                $imageUrl = $baseUrl . '/imagenes_app/' . $folder . '/' . $filename;
            } else {
                // 🌐 PRODUCCIÓN
                $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/' . $folder;
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $baseUrl = 'https://apiapk.tudealer.app';
                $imageUrl = $baseUrl . '/imagenes_app/' . $folder . '/' . $filename;
            }
            
            // ✅ ELIMINAR IMAGEN ANTERIOR SI EXISTE
            if ($model->$fieldName) {
                $this->deleteImageFromProduction($model->$fieldName);
            }
            
            // ✅ ACTUALIZAR MODELO
            $model->update([$fieldName => $imageUrl]);
            
            Log::info('✅ Imagen subida correctamente', [
                'folder' => $folder,
                'filename' => $filename,
                'url' => $imageUrl,
                'environment' => $isDevelopment ? 'development' : 'production'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => [
                    'image_url' => $imageUrl,
                    'image' => $imageUrl,
                    'filename' => $filename,
                ]
            ], 200);
            
        } catch (Exception $e) {
            Log::error('❌ Error al subir imagen: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'error' => 'Error al subir la imagen',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar imagen del servidor
     */
    protected function deleteImageFromProduction(?string $imageUrl)
    {
        if (!$imageUrl) return false;
        
        try {
            $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
            $cleanUrl = explode('?', $imageUrl)[0];
            
            if ($isDevelopment) {
                // ✅ IP DE DESARROLLO
                $baseUrl = 'http://10.23.248.82:8000';
                $relativePath = str_replace($baseUrl, '', $cleanUrl);
                $relativePath = ltrim($relativePath, '/');
                $fullPath = public_path($relativePath);
            } else {
                // 🌐 PRODUCCIÓN
                $baseUrl = 'https://apiapk.tudealer.app';
                $relativePath = str_replace($baseUrl, '', $cleanUrl);
                $relativePath = ltrim($relativePath, '/');
                $fullPath = '/home1/icjmeomy/apiapk.tudealer.app/public/' . $relativePath;
            }
            
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info('✅ Imagen eliminada: ' . $fullPath);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            Log::error('❌ Error al eliminar imagen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener URL completa de la imagen
     */
    protected function getFullImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) return null;
        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }
        
        $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
        $baseUrl = $isDevelopment 
            ? 'http://10.23.248.82:8000'  // ✅ IP CORRECTA
            : 'https://apiapk.tudealer.app';
        
        return $baseUrl . '/' . ltrim($imagePath, '/');
    }
}