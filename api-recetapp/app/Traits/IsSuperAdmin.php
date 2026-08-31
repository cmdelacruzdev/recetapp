<?php

namespace App\Traits;

trait IsSuperAdmin
{
    private function isSuperAdmin(?string $username): bool
    {
        $superadmin = config('recetapp.superadmin_email');
        return $superadmin !== null && $superadmin !== '' && $username === $superadmin;
    }

    private function resolveRole(?string $username, string $dbRole): string
    {
        return $this->isSuperAdmin($username) ? 'superadmin' : $dbRole;
    }
}
