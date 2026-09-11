<?php

namespace App\Models\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

trait UploadAvatar
{
    /**
     * Subir avatar al servidor (SOLO PARA USER)
     */
    protected function uploadAvatarToProduction(Request $request, $user, string $folder = 'avatars', string $fieldName = 'avatar')
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
                $destinationPath = public_path('imagenes_app/' . $folder);
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $baseUrl = 'http://10.23.248.82:8000';
                $avatarUrl = $baseUrl . '/imagenes_app/' . $folder . '/' . $filename;
            } else {
                $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/' . $folder;
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $file->move($destinationPath, $filename);
                $baseUrl = 'https://apiapk.tudealer.app';
                $avatarUrl = $baseUrl . '/imagenes_app/' . $folder . '/' . $filename;
            }

            // ✅ Eliminar avatar anterior
            if ($user->$fieldName) {
                $this->deleteAvatarFromProduction($user->$fieldName);
            }

            // ✅ ACTUALIZAR AVATAR DEL USUARIO
            $user->update([$fieldName => $avatarUrl]);

            // ✅ TAMBIÉN ACTUALIZAR LA IMAGEN DEL PERFIL (doctor, lawyer, shop, association)
            $this->updateProfileImage($user, $avatarUrl);

            Log::info('✅ Avatar subido correctamente', [
                'user_id' => $user->id,
                'url' => $avatarUrl,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Avatar actualizado correctamente',
                'data' => [
                    'avatar_url' => $avatarUrl,
                    'avatar' => $avatarUrl,
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('❌ Error al subir avatar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al subir el avatar',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ACTUALIZAR LA IMAGEN DEL PERFIL CUANDO SE ACTUALIZA EL AVATAR
     */
    private function updateProfileImage($user, $imageUrl)
    {
        if ($user->doctor) {
            $user->doctor->update(['image' => $imageUrl]);
            Log::info('🔄 Imagen de doctor sincronizada', ['doctor_id' => $user->doctor->id]);
        }

        if ($user->lawyer) {
            $user->lawyer->update(['image' => $imageUrl]);
            Log::info('🔄 Imagen de abogado sincronizada', ['lawyer_id' => $user->lawyer->id]);
        }

        if ($user->shop) {
            $user->shop->update(['image' => $imageUrl]);
            Log::info('🔄 Imagen de tienda sincronizada', ['shop_id' => $user->shop->id]);
        }

        if ($user->association) {
            $user->association->update(['image' => $imageUrl]);
            Log::info('🔄 Imagen de asociación sincronizada', ['association_id' => $user->association->id]);
        }
    }

    /**
     * Eliminar avatar del servidor
     */
    protected function deleteAvatarFromProduction(?string $avatarUrl)
    {
        if (!$avatarUrl) return;

        try {
            $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
            $cleanUrl = explode('?', $avatarUrl)[0];

            if ($isDevelopment) {
                $baseUrl = 'http://10.23.248.82:8000';
                $relativePath = str_replace($baseUrl, '', $cleanUrl);
                $relativePath = ltrim($relativePath, '/');
                $fullPath = public_path($relativePath);
            } else {
                $baseUrl = 'https://apiapk.tudealer.app';
                $relativePath = str_replace($baseUrl, '', $cleanUrl);
                $relativePath = ltrim($relativePath, '/');
                $fullPath = '/home1/icjmeomy/apiapk.tudealer.app/public/' . $relativePath;
            }

            if (file_exists($fullPath)) {
                unlink($fullPath);
                Log::info('✅ Avatar eliminado: ' . $fullPath);
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('❌ Error al eliminar avatar: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener URL completa del avatar
     */
    protected function getFullAvatarUrl(?string $avatarPath): ?string
    {
        if (!$avatarPath) return null;
        if (filter_var($avatarPath, FILTER_VALIDATE_URL)) {
            return $avatarPath;
        }

        $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
        $baseUrl = $isDevelopment
            ? 'http://10.23.248.82:8000'
            : 'https://apiapk.tudealer.app';

        return $baseUrl . '/' . ltrim($avatarPath, '/');
    }
}