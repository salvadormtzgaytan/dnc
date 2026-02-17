<?php

namespace App\Policies;

use App\Models\User;

class SeoSettingsPolicy
{
    /**
     * Determina si el usuario puede ver la página de configuración SEO.
     */
    public function view(User $user): bool
    {
        return $user->can('view SeoSettings');
    }

    /**
     * Determina si el usuario puede actualizar la configuración SEO.
     */
    public function update(User $user): bool
    {
        return $user->can('update SeoSettings');
    }
}
