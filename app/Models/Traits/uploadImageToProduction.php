// app/Models/Traits/UploadImage.php
<?php

namespace App\Models\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

trait UploadImage
{
    /**
     * Subir imagen a la carpeta de producción
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
            
            // Validar que sea una imagen
            if (!$file->isValid() || !in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])) {
                return response()->json([
                    'error' => 'Formato de imagen no válido. Use JPG, PNG o WEBP'
                ], 422);
            }

            // Generar nombre único
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Ruta de destino
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/' . $folder;
            
            // Crear carpeta si no existe
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            // Mover archivo
            $file->move($destinationPath, $filename);
            
            // URL pública
            $imageUrl = 'https://apiapk.tudealer.app/imagenes_app/' . $folder . '/' . $filename;
            
            // Actualizar modelo
            $model->update([$fieldName => $imageUrl]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => [
                    'image_url' => $imageUrl,
                    'filename' => $filename
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
     * Eliminar imagen del servidor
     */
    protected function deleteImageFromProduction(?string $imageUrl)
    {
        if (!$imageUrl) return;
        
        try {
            // Extraer la ruta relativa desde la URL
            $path = str_replace('https://apiapk.tudealer.app/', '', $imageUrl);
            $fullPath = '/home1/icjmeomy/apiapk.tudealer.app/public/' . $path;
            
            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info('Imagen eliminada: ' . $fullPath);
            }
        } catch (Exception $e) {
            Log::error('Error al eliminar imagen: ' . $e->getMessage());
        }
    }
}