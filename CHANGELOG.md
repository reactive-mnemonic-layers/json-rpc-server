# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [v2.2.0] - 2026-08-02

### Added
- `ProceduresCollection`: A `ContainerInterface` implementation for registering procedures as `[serviceName]` or `[serviceName, methodName]` pairs and resolving them lazily from a provider container, with resolved callables cached after first use.
- `ProcedureNotFoundException` and `InvalidRemoteProcedureException`, implementing the PSR-11 `NotFoundExceptionInterface`/`ContainerExceptionInterface`, thrown by `ProceduresCollection::get()`.

## [v1.4.0] - 2026-07-31

### Removed
- `RemoteProcedureInterface`: The server now works directly with any callable procedure.

### Changed
- `Server`: Simplified procedure execution. Procedures can now return any value, which will be automatically wrapped in a `Response` object if needed.

## [v1.3.0] - 2025-11-04

### Changed
- The request object is added to the response.
- The request factory now creates error responses if there is a problem creating the request instead of throwing exceptions.
- The request and response object are now instances of the common JsonRpcMessageInterface.
- More validations of the request are added in the request factory.

## [v1.2.0] - 2025-11-04

### Changed
- The original thrown exception is added to the Error object
- The ability to use the exception message as the error message is added
- The ability to use the exception trace as the error data is added
- The ability to use the exception as the error data is added
- The ability to nest previous exceptions in the error data is added

## [v1.1.0] - 2025-11-03

### Changed
- Github actions are added.
- RequestFactory::fromArray() now creates a batch request if the input array contains multiple items.
- Server::executePsrRequest() now returns null if the request is a notification.
- Server::executeArrayRequest() now returns null if the request is a notification.

## [v1.0.2] - 2025-10-30

### Changed
- Remove the generated static files for documentation and coverage.

## [v1.0.1] - 2025-10-30

### Changed
- Response DTO: make `id` non-readonly and set it after executing the procedure to ensure response contains the correct request id.

### Documentation
- Update generated coverage and docs.
- Add `.phpdoc/` to `.gitignore`.

## [v1.0.0] - 2025-10-28
- Initial tagged release.

[v2.2.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v2.2.0
[v1.4.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.4.0
[v1.3.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.3.0
[v1.2.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.2.0
[v1.1.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.1.0
[v1.0.2]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.0.2
[v1.0.1]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.0.1
[v1.0.0]: https://github.com/alcedo-bg/json-rpc-server/releases/tag/v1.0.0
