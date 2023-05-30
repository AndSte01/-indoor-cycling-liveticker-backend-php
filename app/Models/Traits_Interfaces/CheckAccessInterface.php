<?php

namespace App\Models\Traits_Interfaces;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * adds requirement for method of the model to check wether a given user has access or not
 */
interface CheckAccessInterface
{
    /**
     * Check whether the given user has access to the desired resource or not
     */
    public function checkAccess(Authenticatable $user): bool;
}
