<?php

namespace App\Models\Traits_Interfaces;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\MissingAttributeException;

/**
 * Alternative solutions:
 *  - query all models individual (requires dependency tree, very likely to work)
 *  - use the with keyword (see https://stackoverflow.com/questions/25628754/laravel-nested-relationships)
 *    requires dependency tree, not tested to be working
 */

/**
 * Implementation for identifying an model using implicit values
 * 
 * Problem: Relationships defined by lumen can't be used effectively, so non standard ones
 *          will break that code
 */
trait ImplicitIdentificationTrait
{
    /**
     * Set of attributes in the model, excluding it's primary id that fully describe it
     */
    protected $implicitIdentifiers = [];

    // see corresponding interface
    public function getImplicitIdentifiers(): array
    {
        return self::$implicitIdentifiers;
    }

    /**
     * guesses The column a certain relation takes place.
     * 
     * The different guesses are
     * 1. append '_id' to the relation name
     * 2. the relation name itself
     * 
     * @throws Exception in case no relation has been found at the guessed locations
     * 
     * @return string the name of the column the relation takes place
     */
    protected function guessRelationColumn(string $name): string
    {
        // first guess append _id
        if ($this->isRelation($name . "_id"))
            return $name . "_id";

        // second guess just the name
        if ($this->isRelation($name))
            return $name;

        // we failed
        throw new Exception("\"" . $name . "\" doesn't hint to a relation in the model \"" . get_class($this) . "\"");
    }

    /**
     * Checks wether all implicit identifiers of this model are contained in the given object
     * 
     * @throws Exception in case the object lacks some identifiers
     * 
     * @param object $IdentifierAndValue The object containing the required identifiers
     */
    protected function checkForImplicitIdentifierCompleteness(object $IdentifierAndValue): void
    {
        // check wether all implicit identifiers hav been provided
        // 1. put keys of $IdentifierAndValue into an separate array
        // 2. get all the values in $this->implicitIdentifiers that are not present in the keys of $IdentifierAndValue
        //    The resulting array must be empty. (Note: additional keys get ignored, here and later on as well)
        //    If the array isn't empty an Exception is thrown
        if (!empty($diff = array_diff($this->getImplicitIdentifiers(), array_keys(get_object_vars($IdentifierAndValue))))) {
            throw new MissingAttributeException(new $this, json_encode($diff));
        }
    }

    // see corresponding interface
    public function generateImplicitQuery(Builder &$query, object $IdentifierAndValue, string $childJoinColumn = null): void
    {
        // we might be first in line, in that case we wont be joining
        if ($childJoinColumn !== null)
            // adds the required join to the query
            $query->join($this->getTable(), $childJoinColumn, '=', $this->getTable() . $this->primaryKey);

        // add the required values to the arguments list
        $this->checkForImplicitIdentifierCompleteness($IdentifierAndValue);

        /** @var array<array<int>> Storing information about foreign Identifiers [[name of relation, column name in this model], ...]*/
        $foreignIdentifiers = [];

        // filter for implicitIdentifiers containing additional options and add the required arguments to the list
        foreach ($this->getImplicitIdentifiers() as $key => $implicitIdentifier) {
            // filter for special identifiers (those with options, stored as an array)
            if (is_array($implicitIdentifier)) {
                // check which option the implicit identifier contained
                switch ($implicitIdentifier[1]) {
                    case 'foreign':
                        // if the user provides a column name we can skip the guessing game
                        if (!isset($implicitIdentifier[2]))
                            // add auto detection of corresponding field!!!
                            $foreignIdentifiers[] = [$implicitIdentifier[0], $implicitIdentifier[2]];
                        else
                            // else we need to guess the name of the column the relation exists in
                            $foreignIdentifiers[] = [$implicitIdentifier[0], $this->guessRelationColumn($implicitIdentifier[0])];
                        break;

                    default:
                        throw new Exception("invalid option " . strval($implicitIdentifier[1]) . " for implicit identifier");
                        break;
                }
            } else {
                // a 'normal' identifier whose value should be added to the query
                $query->where($this->getTable() . $implicitIdentifier, "=", $IdentifierAndValue->{$implicitIdentifier});
            }
        }

        // now we need treat the foreign identifiers
        foreach ($foreignIdentifiers as $foreignIdentifier) {
            if (!is_object($IdentifierAndValue->{$foreignIdentifier[0]})) {
                // check if provided value is not of type object (therefor gets treated as an explicit identifier in form of an primary Key)
                $query->where($this->getTable() . $foreignIdentifier[1], "=", $IdentifierAndValue->{$foreignIdentifier[0]});
            } else {
                // get the parent class of the relation
                $parent = $this->getRelation($foreignIdentifier[1])->getParent();

                // in case the parent doesn't support implicit identification throw an error
                if (in_array(getImplicitInterface::class, class_implements($parent)))
                    throw new Exception("Call to parent \"" . get_class($parent) . "\" that is not supporting implicit identification");

                // now move up one step in the ladder
                $parent->generateImplicitQuery($query, $this->getTable() . $foreignIdentifier[1], $IdentifierAndValue->{$foreignIdentifier[0]});
            }
        }
    }

    // see corresponding interface
    public static function getImplicit(object $IdentifierAndValue, bool $multiple = false): Collection|self|static|null
    {
        // required for using non static variables
        $self = new static;

        // get a new query builder
        $query = self::query();

        // join all the dependencies together
        $self->generateImplicitQuery($query, $IdentifierAndValue);

        // only return the first element
        if ($multiple)
            return $query->first();

        // return the entire collection
        return $query->get();
    }
}
