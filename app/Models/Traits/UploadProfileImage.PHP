<?php

namespace App\Models\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait UploadProfileImage
{
    public function uploadImage(Request $request, $model, $folder = 'profiles')
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // eliminar imagen anterior
        if ($model->image && Storage::disk('public')->exists($model->image)) {
            Storage::disk('public')->delete($model->image);
        }

        $path = $request->file('image')->store($folder, 'public');

        $model->update([
            'image' => $path
        ]);

        return response()->json([
            'message' => 'Imagen actualizada correctamente',
            'image' => asset('storage/' . $path)
        ]);
    }
}