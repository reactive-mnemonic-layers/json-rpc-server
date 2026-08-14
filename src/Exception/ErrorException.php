<?php

namespace Alcedo\Rml\JsonRpc\Exception;

use Alcedo\Rml\JsonRpc\DTO\Error;
use Alcedo\Rml\JsonRpc\DTO\ErrorCodes;
use Exception;
use Throwable;

/**
 * Represents an error exception which extends the base Exception class.
 * Provides methods for creating an instance from error codes
 * and converting the exception to an Error object.
 */
class ErrorException extends Exception
{
    private array $data;

    public function __construct(string $message, int $code = 0, ?Throwable $previous = null, array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Creates a new ErrorException instance based on the provided error code.
     *
     * @param ErrorCodes $errorCode The error code from which the exception will be created.
     * @param Throwable|null $prev Optional previous throwable for exception chaining.
     * @param array $data Optional data to pass to the constructor of the ErrorException class.
     *
     * @return ErrorException Returns a new instance of ErrorException.
     */
    public static function fromErrorCode(ErrorCodes $errorCode, ?Throwable $prev = null, array $data = []): ErrorException
    {
        return new self($errorCode->message(), $errorCode->value, $prev, $data);
    }

    /**
     * Converts the current object state into an Error instance.
     *
     * @param mixed $data Optional additional data to include in the error.
     *
     * @return Error The generated Error object.
     *
     * @throws InvalidErrorException If the error code is not valid.
     */
    public function toError(mixed $data = null): Error
    {
        return new Error($this->code, $this->message, array_merge($this->data, $data));
    }
}
