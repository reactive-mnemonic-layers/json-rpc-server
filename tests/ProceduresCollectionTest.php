<?php

namespace Alcedo\Rml\Tests\JsonRpc;

use Alcedo\Rml\JsonRpc\Exception\InvalidRemoteProcedureException;
use Alcedo\Rml\JsonRpc\Exception\ProcedureNotFoundException;
use Alcedo\Rml\JsonRpc\ProceduresCollection;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ProceduresCollectionTest extends TestCase
{
    public function testHasReturnsTrueForRegisteredProcedure(): void
    {
        $collection = new ProceduresCollection(
            $this->makeProvider([]),
            ['sum' => ['calculator']]
        );

        $this->assertTrue($collection->has('sum'));
        $this->assertFalse($collection->has('unknown'));
    }

    public function testGetThrowsWhenProcedureIsNotRegistered(): void
    {
        $collection = new ProceduresCollection($this->makeProvider([]), []);

        $this->expectException(ProcedureNotFoundException::class);
        $this->expectException(NotFoundExceptionInterface::class);
        $collection->get('missing');
    }

    public function testGetThrowsWhenUnderlyingServiceIsMissing(): void
    {
        $collection = new ProceduresCollection(
            $this->makeProvider([]),
            ['sum' => ['calculator']]
        );

        $this->expectException(ProcedureNotFoundException::class);
        $this->expectExceptionMessage('System service calculator not found');
        $collection->get('sum');
    }

    public function testGetResolvesServiceWithoutMethod(): void
    {
        $service = function (int $a, int $b): int {
            return $a + $b;
        };
        $collection = new ProceduresCollection(
            $this->makeProvider(['calculator' => $service]),
            ['sum' => ['calculator']]
        );

        $resolved = $collection->get('sum');

        $this->assertSame($service, $resolved);
        $this->assertSame(3, $resolved(1, 2));
    }

    public function testGetResolvesServiceMethodPair(): void
    {
        $service = new class {
            public function add(int $a, int $b): int
            {
                return $a + $b;
            }
        };
        $collection = new ProceduresCollection(
            $this->makeProvider(['calculator' => $service]),
            ['sum' => ['calculator', 'add']]
        );

        $resolved = $collection->get('sum');

        $this->assertIsCallable($resolved);
        $this->assertSame([$service, 'add'], $resolved);
        $this->assertSame(5, call_user_func($resolved, 2, 3));
    }

    public function testGetThrowsWhenResolvedServiceIsNotCallable(): void
    {
        $collection = new ProceduresCollection(
            $this->makeProvider(['calculator' => new \stdClass()]),
            ['sum' => ['calculator']]
        );

        $this->expectException(InvalidRemoteProcedureException::class);
        $this->expectException(ContainerExceptionInterface::class);
        $this->expectExceptionMessage('Invalid procedure calculator');
        $collection->get('sum');
    }

    public function testGetCachesTheResolvedProcedure(): void
    {
        $service = function (): string {
            return 'ok';
        };
        $provider = $this->createMock(ContainerInterface::class);
        $provider->method('has')->willReturn(true);
        $provider->expects($this->once())->method('get')->with('calculator')->willReturn($service);

        $collection = new ProceduresCollection($provider, ['sum' => ['calculator']]);

        $first = $collection->get('sum');
        $second = $collection->get('sum');

        $this->assertSame($first, $second);
        $this->assertSame(['sum' => $service], $collection->getResolved());
    }

    public function testAddRegistersANewProcedure(): void
    {
        $service = function (): string {
            return 'hello';
        };
        $collection = new ProceduresCollection($this->makeProvider(['greeter' => $service]), []);

        $this->assertFalse($collection->has('hello'));

        $collection->add('hello', ['greeter']);

        $this->assertTrue($collection->has('hello'));
        $this->assertSame('hello', call_user_func($collection->get('hello')));
    }

    public function testGetProviderReturnsTheConstructorProvider(): void
    {
        $provider = $this->makeProvider([]);
        $collection = new ProceduresCollection($provider, []);

        $this->assertSame($provider, $collection->getProvider());
    }

    public function testGetJsonRemoteCallProceduresReturnsTheRegisteredMap(): void
    {
        $map = ['sum' => ['calculator', 'add']];
        $collection = new ProceduresCollection($this->makeProvider([]), $map);

        $this->assertSame($map, $collection->getJsonRemoteCallProcedures());
    }

    /**
     * @param array<string, mixed> $services
     */
    private function makeProvider(array $services): ContainerInterface
    {
        $provider = $this->createMock(ContainerInterface::class);
        $provider->method('has')->willReturnCallback(
            static fn (string $id): bool => array_key_exists($id, $services)
        );
        $provider->method('get')->willReturnCallback(
            static fn (string $id) => $services[$id]
        );

        return $provider;
    }
}
