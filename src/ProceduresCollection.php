<?php

namespace Alcedo\Rml\JsonRpc;

use Alcedo\Rml\JsonRpc\Exception\InvalidRemoteProcedureException;
use Alcedo\Rml\JsonRpc\Exception\ProcedureNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Represents a collection of procedures that implements the ContainerInterface.
 * This class allows adding, retrieving, and managing remote procedure definitions.
 *
 * Each procedure is defined as an array of `[serviceName]` or `[serviceName, methodName]`.
 * The service is looked up lazily in the underlying provider container and, if a method
 * name is given, resolved into a `[service, methodName]` callable. Resolved callables are
 * cached so subsequent lookups do not hit the provider again.
 */
class ProceduresCollection implements ContainerInterface
{
    private array $resolved = [];

    /**
     * Constructor method.
     *
     * @param ContainerInterface $provider A container used to resolve the underlying services.
     * @param array $procedures Map of procedure id to `[serviceName]` or `[serviceName, methodName]`.
     *
     * @return void
     */
    public function __construct(
        private readonly ContainerInterface $provider,
        private array $procedures
    ) {
        // ...
    }

    /**
     * Registers a procedure definition under the given id.
     *
     * @param string $name The procedure id.
     * @param array $procedure `[serviceName]` or `[serviceName, methodName]`.
     *
     * @return void
     */
    public function add(string $name, array $procedure): void
    {
        $this->procedures[$name] = $procedure;
    }

    /**
     * Resolves and returns the callable registered for the given procedure id.
     *
     * @param string $id The procedure id to resolve.
     *
     * @return callable The resolved callable procedure.
     *
     * @throws ProcedureNotFoundException If the id is not registered, or its underlying service is missing.
     * @throws InvalidRemoteProcedureException If the resolved entry is not callable.
     * @throws ContainerExceptionInterface If the provider fails to resolve the underlying service.
     */
    public function get(string $id): callable
    {
        if (!$this->has($id)) {
            throw new ProcedureNotFoundException('Remote procedure not found');
        }

        if (!array_key_exists($id, $this->resolved)) {
            $procedure = $this->procedures[$id];
            $name = array_shift($procedure);
            if (!$this->provider->has($name)) {
                throw new ProcedureNotFoundException(sprintf('System service %s not found', $name));
            }
            $service = $this->provider->get($name);
            if (count($procedure)) {
                $service = [$service, array_shift($procedure)];
            }

            if (!is_callable($service)) {
                throw new InvalidRemoteProcedureException(sprintf('Invalid procedure %s', $name));
            }

            $this->resolved[$id] = $service;
        }

        return $this->resolved[$id];
    }

    /**
     * Determines whether a procedure is registered under the given id.
     *
     * @param string $id The procedure id to check.
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->procedures);
    }

    /**
     * @return ContainerInterface
     */
    public function getProvider(): ContainerInterface
    {
        return $this->provider;
    }

    /**
     * @return array
     */
    public function getResolved(): array
    {
        return $this->resolved;
    }

    /**
     * @return array
     */
    public function getJsonRemoteCallProcedures(): array
    {
        return $this->procedures;
    }
}
