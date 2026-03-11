<?php

namespace App\Models\Traits;


use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasImageUpload
{
    /**
     * Guarda una imagen y devuelve la ruta.
     */
    public function storeImage(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public'
    ): string {
        return $file->store($directory, $disk);
    }

    /**
     * Reemplaza la imagen actual por una nueva.
     */
    public function updateImage(
        UploadedFile $file,
        string $directory = 'uploads',
        string $disk = 'public',
        string $column = 'image'
    ): string {
        // Eliminar imagen anterior si existe
        if ($this->{$column}) {
            Storage::disk($disk)->delete($this->{$column});
        }

        $path = $file->store($directory, $disk);

        // Actualizar el campo en el modelo
        $this->update([
            $column => $path
        ]);

        return $path;
    }

    /**
     * Elimina la imagen del servidor.
     */
    public function deleteImage(
        string $disk = 'public',
        string $column = 'image'
    ): void {
        if ($this->{$column}) {
            Storage::disk($disk)->delete($this->{$column});
        }
    }
}
