<?php

namespace Alcedo\Rml\JsonRpc;

use Alcedo\Rml\JsonRpc\DTO\Response;

/**
 * Defines an interface for remote procedure calls.
 *
 * @deprecated Use regular callables instead
 */
interface RemoteProcedureInterface
{
    /**
     * Executes the call and returns the response.
     *
     * @param mixed ...$param Parameters for the procedure to call with
     *
     * @return Response The response object resulting from the call.
     */
    public function call(...$param): Response;
}
