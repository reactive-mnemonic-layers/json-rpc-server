<?php

namespace Alcedo\Rml\JsonRpc\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Represents an exception that is thrown when a resolved remote procedure
 * entry is not callable.
 *
 * Extends the base Exception class and implements the PSR-11
 * ContainerExceptionInterface so it can be handled as a container exception.
 */
class InvalidRemoteProcedureException extends \Exception implements ContainerExceptionInterface
{
    // ...
}
