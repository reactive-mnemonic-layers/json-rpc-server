<?php

namespace Alcedo\Rml\JsonRpc\DTO;

use Alcedo\Rml\JsonRpc\Exception\InvalidBatchElementException;

/**
 * Represents a batch of requests and enforces that all elements within the batch are instances of the Request class.
 * Extends ArrayObject and implements the JsonSerializable interface.
 *
 * Methods in this class ensure that any addition or modification of elements in the batch validates against
 * the required type.
 */
class BatchRequest extends Batch
{
    /**
     * Validates that the provided value is an instance of Request.
     *
     * @param mixed $value The value to validate.
     *
     * @return void
     *
     * @throws InvalidBatchElementException If the value is not an instance of Request or Error.
     */
    protected function validateElement(mixed $value): void
    {
        if (!($value instanceof JsonRpcMessageInterface)) {
            throw new InvalidBatchElementException('Only Request objects are allowed.');
        }
    }
}
