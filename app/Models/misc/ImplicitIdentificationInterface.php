<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Allows the model to be identified by a set of attributes not containing its primary key
 */
interface ImplicitIdentificationInterface
{
    /**
     * gets the identifiers that uniquely define this model (except the primary key)
     * 
     * @return array The implicit identifiers
     */
    public function getImplicitIdentifiers(): array;

    /**
     * Adds the required 'join' and 'where' statements to the query
     * 
     * @param Builder &$query The query used for the implicit detection of this element (might be joined in a dependency tree)
     * @param object $IdentifierAndValue The identifiers and there values
     * @param string $childJoinColumn The column the relation between child and parent takes place (in the child's table)
     */
    public function generateImplicitQuery(Builder &$query, object $IdentifierAndValue, string $childJoinColumn = null): void;

    /**
     * Get a model form the database using a minimal set of values (not including the primary key) that identifies it uniquely.
     * 
     * @param object $IdentifierAndValue the pairs of identifiers and their values uniquely describing the object
     * @param bool $multiple Decides wether multiple elements or just the first one should be returned (might be required due to duplicates in the database)
     */
    public static function getImplicit(object $IdentifierAndValue, bool $multiple = false): Collection|self|static|null;
}
