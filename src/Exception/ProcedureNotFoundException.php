<?php

namespace Alcedo\Rml\JsonRpc\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Represents an exception that is thrown when a requested procedure
 * cannot be found in a ProceduresCollection or its underlying provider.
 *
 * Extends the base Exception class and implements the PSR-11
 * NotFoundExceptionInterface so it can be handled as a container exception.
 */
class ProcedureNotFoundException extends \Exception implements NotFoundExceptionInterface
{
    // ...
}
