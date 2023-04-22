<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception that contains an array of multiple exceptions
 */
class MultiException extends Exception
{
    /** @var array The exceptions that are stored inside this exception */
    protected array $exceptions;

    /**
     * New Exception containing multiple other exceptions (that should be independent from one another)
     * 
     * @param array $exceptions the exceptions that should be stored inside this exception
     */
    public function __construct(array $exceptions)
    {
        $this->exceptions = $exceptions;
        parent::__construct("Multiple exceptions occurred", 500, null);
    }

    /**
     * Get the exceptions stored inside of this exception
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    // not needed Handler does the magic
    /*public function render(Request $request): Response
    {
        $status = 500;
        $type = "MULTIPLE_ERRORS";
        $error = "Multiple exceptions occurred";
        $help = "Contact the sales team to verify";

        return response(["error" => $error, "help" => $help], $status);
    }*/
}
