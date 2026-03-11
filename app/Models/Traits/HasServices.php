<?php

namespace App\Models\Traits;

use App\Models\Service;

trait HasServices
{
    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }

    /**
     * Crear servicio
     */
    public function addService(array $data): Service
    {
        return $this->services()->create($data);
    }

    /**
     * Actualizar servicio
     */
    public function updateService(Service $service, array $data): Service
    {
        abort_if($service->serviceable_id !== $this->id, 403);

        $service->update($data);
        return $service;
    }

    /**
     * Eliminar servicio
     */
    public function deleteService(Service $service): void
    {
        abort_if($service->serviceable_id !== $this->id, 403);

        $service->delete();
    }
}
