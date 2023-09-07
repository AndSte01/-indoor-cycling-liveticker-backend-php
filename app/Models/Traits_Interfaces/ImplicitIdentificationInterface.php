<?php

namespace App\Models\Traits_Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Allows the model to be identified by a set of attributes not containing its primary key
 */
interface ImplicitIdentificationInterface
{
    /**
     * Searches implicitly for a model
     * 
     * @param object &$data the fields that should be used for the implicit search
     * 
     * @return Model the first model found is returned
     */
    public static function getImplicit(object &$data): Model;

    /**
     * Searches implicitly for a model, in case none is found an exception is thrown
     * 
     * Layout of data is probably described in 'getImplicit()'
     * 
     * @param object &$data the fields that should be used for the implicit search
     * 
     * @throws ModelNotFoundException in case no model was found
     * 
     * @return Model the first model found is returned
     */
    public static function getImplicitOrFail(object &$data): Model;

    /**
     * Checks whether the data meets the requirements (contains all the fields required)
     * 
     * @param object &$data the data to check
     * 
     * @throws InvalidArgumentException in case the validation fails
     * 
     * @return bool true in case the data is correct else false
     */
    public static function validateImplicitQueryData(object &$data): bool;
}
