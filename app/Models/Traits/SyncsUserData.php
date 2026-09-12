<?php

namespace App\Models\Traits;

use Illuminate\Http\Request;

trait SyncsUserData
{
    /**
     * Sincroniza campos del perfil con la tabla users.
     *
     * @param mixed   $profile  Modelo del perfil (association, shop, doctor, lawyer)
     * @param Request $request  Request con los campos editados
     * @param array   $mapping  Mapeo: campo_perfil => campo_user
     */
    protected function syncUserData($profile, Request $request, array $mapping)
    {
        $user = $profile->user ?? null;
        if (!$user) return;

        $userData = [];

        foreach ($mapping as $profileField => $userField) {
            if ($request->filled($profileField)) {
                $userData[$userField] = $request->input($profileField);
            }
        }

        if (!empty($userData)) {
            $user->update($userData);
        }
    }
}