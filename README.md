# JSON-RPC Server

A lightweight, PSR-friendly JSON-RPC 2.0 server for executing functions or objects remotely. It supports single and batch requests, notifications (no response), PSR-7 requests, and error handling compliant with the JSON-RPC 2.0 specification.

- PHP 8.2+
- PSR-7 `RequestInterface` support
- PSR Container for procedure lookups
- Strict DTOs for Requests/Responses/Errors
- Batch requests and notifications


## Installation

Install via Composer:

```
composer require rml/json-rpc-server
```

Requirements:
- PHP >= 8.2
- psr/http-message ^2.0
- psr/container ^2.0


## Quick start

### 1) Register procedures in a PSR Container
Map method names to callables.

```php
use Alcedo\Rml\JsonRpc\Server;
use Alcedo\Rml\JsonRpc\Factory\RequestFactory;
use Alcedo\Rml\JsonRpc\DTO\Response;
use Psr\Container\ContainerInterface;

$map = [
    // Callable procedure: parameters will be passed from the JSON-RPC params array
    'sum' => function (int $a, int $b): int { return $a + $b; },

    // Class with __invoke():
    'remote.ok' => new class {
        public function __invoke(): string { return 'ok'; }
    },
];

// Example minimal container wrapper for the static map
$container = new class($map) implements ContainerInterface {
    public function __construct(private array $map) {}
    public function has(string $id): bool { return array_key_exists($id, $this->map); }
    public function get(string $id) { return $this->map[$id]; }
};

$server = new Server(new RequestFactory(), $container);
```

### 2) Execute a single request from an array
```php
$response = $server->executeArrayRequest([
    'jsonrpc' => '2.0',
    'method' => 'sum',
    'id' => 1,
    'params' => [2, 3],
]);

// $response is Rml\JsonRpc\DTO\Response
json_encode($response); // {"jsonrpc":"2.0","result":5,"id":1}
```

### 3) Execute a PSR-7 request (single or batch)
`Server::executePsrRequest()` will parse the PSR-7 body (JSON) and handle single or batch automatically.

```php
use Psr\Http\Message\RequestInterface;

/** @var RequestInterface $psrRequest */
$rpcResponse = $server->executePsrRequest($psrRequest);

// Single request -> Response|null
// Batch request  -> BatchResponse
```

### 4) Notifications (no id)
Requests without `id` are treated as notifications and return `null`, though the procedure is executed.

```php
$result = $server->executeArrayRequest([
    'jsonrpc' => '2.0',
    'method' => 'notify',
    // no id -> notification
]);
// $result === null
```

### 5) Batch requests
Provide an array of requests; notifications are omitted from the resulting `BatchResponse`.

```php
$rpcResponse = $server->executePsrRequest($psrRequest); // body contains JSON array
// $rpcResponse is Rml\JsonRpc\DTO\BatchResponse and is countable
```


## How it works

Core types under `Rml\JsonRpc\DTO`:
- `Request` — JSON-RPC request with method, params, optional id. Validates method names do not start with the reserved `rpc.` prefix.
- `Response` — JSON-RPC response carrying either `result` or `error` (never both). Provides helpers `isError()`/`isSuccess()`.
- `Error` — JSON-RPC error with `code`, `message`, and optional `data`.
- `BatchRequest` — Array-like collection of `Request` or `Error` items. Validates element types.
- `BatchResponse` — Array-like collection of `Response` items. Validates element types.
- `ErrorCodes` — Enum for standard JSON-RPC error codes and server error range.
- `JsonRpcTrait` — Provides `jsonRpc()` returning protocol version `2.0`.

Factories:
- `RequestFactory` — Builds `Request`/`BatchRequest` from PSR-7 request body or arrays. Maps invalid items within a batch to `Error` entries.
- `ErrorFactory` — Convenience constructors for errors: parse, invalid request, method not found, invalid params, internal error, server error.

Server:
- `Server` — Executes requests using a PSR Container to resolve procedures by method name. Supports:
  - `executeArrayRequest(array $request): Response|BatchResponse|null`
  - `executePsrRequest(RequestInterface $request): Response|BatchResponse|null`
  - `execute(Request|BatchRequest $request): Response|BatchResponse|null`

Procedures:
- Callables — Any PHP callable is allowed; its return value becomes `result` (unless it already returns a `Response` object) and exceptions are converted to `internal error`.

`ProceduresCollection`:
- `ProceduresCollection` — A `ContainerInterface` implementation that lets you register procedures as `[serviceName]` or `[serviceName, methodName]` pairs instead of resolving them upfront. Services are looked up lazily in an underlying `provider` container (e.g. a DI container/service locator) the first time a procedure is requested, then cached for subsequent calls. Useful when procedures map to services or service methods that should not be instantiated until they are actually invoked.


## Error handling

The server adheres to JSON-RPC 2.0 error semantics using `ErrorCodes` and `ErrorFactory`:
- `PARSE_ERROR (-32700)` — Invalid JSON in PSR-7 body.
- `INVALID_REQUEST (-32600)` — Missing or malformed fields (e.g., missing `method`).
- `METHOD_NOT_FOUND (-32601)` — Procedure missing in the container.
- `INVALID_PARAMS (-32602)` — For parameter issues (factory available, not auto-generated by server).
- `INTERNAL_ERROR (-32603)` — Exceptions thrown by callables are wrapped with the original message.
- `SERVER_ERROR (-32099…-32000)` — Generic server-side errors (e.g., non-callable procedure), produced with `ErrorFactory::serverError()`.

Transformations and exceptions:
- `ErrorException::fromErrorCode()` can be turned into an `Error` via `$exception->toError()`.
- `InvalidResponseException` — Thrown if a `Response` is constructed with both `result` and `error`.
- `InvalidBatchElementException` — Thrown when invalid items appear in batch collections.
- `InvalidMethodNameException` — Thrown when a `Request` method starts with `rpc.`.
- `ProcedureNotFoundException` (implements `Psr\Container\NotFoundExceptionInterface`) — Thrown by `ProceduresCollection::get()` when the procedure id or its underlying service is not registered.
- `InvalidRemoteProcedureException` (implements `Psr\Container\ContainerExceptionInterface`) — Thrown by `ProceduresCollection::get()` when the resolved service/method is not callable.


## Examples

### Callable procedure
```php
$server = new Server(new RequestFactory(), $container);
$response = $server->executeArrayRequest([
    'jsonrpc' => '2.0', 'method' => 'sum', 'id' => 1, 'params' => [10, 5]
]);
// Response(result: 15, id: 1)
```

### Class procedure (__invoke)
```php
class HelloProc {
    public function __invoke(): string { return 'hello'; }
}

$map = ['hello' => new HelloProc()];
$server = new Server(new RequestFactory(), new ArrayContainer($map));
$response = $server->executeArrayRequest(['jsonrpc' => '2.0', 'method' => 'hello', 'id' => 7]);
// Response(result: 'hello', id: 7)
```

### Lazy procedure resolution with ProceduresCollection
Register procedures as `[serviceName]` or `[serviceName, methodName]` pairs and let
`ProceduresCollection` resolve them from a provider container on first use.

```php
use Alcedo\Rml\JsonRpc\ProceduresCollection;
use Alcedo\Rml\JsonRpc\Server;
use Alcedo\Rml\JsonRpc\Factory\RequestFactory;
use Psr\Container\ContainerInterface;

// $provider resolves service ids to service instances, e.g. a DI container.
/** @var ContainerInterface $provider */
$procedures = new ProceduresCollection($provider, [
    'sum' => ['calculator', 'add'], // calls $provider->get('calculator')->add(...)
    'ping' => ['pingService'],      // calls $provider->get('pingService')(...)
]);

// Additional procedures can be registered at runtime.
$procedures->add('hello', ['greeterService', 'sayHello']);

$server = new Server(new RequestFactory(), $procedures);
$response = $server->executeArrayRequest([
    'jsonrpc' => '2.0', 'method' => 'sum', 'id' => 1, 'params' => [2, 3],
]);
```

### Batch via PSR-7 request
```php
$body = json_encode([
    ['jsonrpc' => '2.0', 'method' => 'sum', 'id' => 1, 'params' => [1, 2]],
    ['jsonrpc' => '2.0', 'method' => 'hello', 'id' => 2],
    ['jsonrpc' => '2.0', 'method' => 'notify'], // notification => omitted in response
]);

$psrRequest = new \GuzzleHttp\Psr7\Request('POST', '/', [], $body);
$batch = $server->executePsrRequest($psrRequest); // BatchResponse
```


## Notes and caveats
- Notifications (no id) return null but still execute the target procedure.
- Batch responses exclude notifications by design, as per JSON-RPC 2.0.
- `Request` rejects method names starting with `rpc.` to reserve the prefix for internal use.


## Development

Run tests with PHPUnit:

```
vendor/bin/phpunit
```

Coding standards:
- PHP_CodeSniffer (PSR-12) via `vendor/bin/phpcs`
- PHPMD via `vendor/bin/phpmd`


## License

MIT License. See `LICENSE` for details.