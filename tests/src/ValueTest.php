<?php declare(strict_types=1);

namespace Tests\Kirameki\Core;

final class ValueTest extends TestCase
{
    public function test_of(): void
    {
        $this->assertSame('int', Value::getType(1));
        $this->assertSame('string', Value::getType('1'));
        $this->assertSame('float', Value::getType(1.0));
        $this->assertSame('bool', Value::getType(true));
        $this->assertSame('array', Value::getType([]));
        $this->assertSame('stdClass', Value::getType(new stdClass()));
        $this->assertSame('null', Value::getType(null));
    }

    public function test_is(): void
    {
        $this->assertTrue(Value::isType(null, 'null'));
        $this->assertFalse(Value::isType(0, 'null'));
        $this->assertFalse(Value::isType('', 'null'));
        $this->assertFalse(Value::isType('null', 'null'));
        $this->assertFalse(Value::isType([], 'null'));
        $this->assertFalse(Value::isType([null], 'null'));
        $this->assertFalse(Value::isType(new stdClass(), 'null'));
        $this->assertFalse(Value::isType(new class() {}, 'null'));
        $this->assertFalse(Value::isType(fn() => null, 'null'));

        $this->assertTrue(Value::isType(true, 'bool'));
        $this->assertFalse(Value::isType(null, 'bool'));
        $this->assertFalse(Value::isType(0, 'bool'));
        $this->assertFalse(Value::isType('', 'bool'));
        $this->assertFalse(Value::isType('true', 'bool'));
        $this->assertFalse(Value::isType([], 'bool'));
        $this->assertFalse(Value::isType([true], 'int'));
        $this->assertFalse(Value::isType(fn() => true, 'bool'));
        $this->assertFalse(Value::isType(new stdClass(), 'bool'));
        $this->assertFalse(Value::isType(new class() {}, 'bool'));

        $this->assertTrue(Value::isType(1, 'int'));
        $this->assertFalse(Value::isType(null, 'int'));
        $this->assertFalse(Value::isType(1.0, 'int'));
        $this->assertFalse(Value::isType('1', 'int'));
        $this->assertFalse(Value::isType(true, 'int'));
        $this->assertFalse(Value::isType([], 'int'));
        $this->assertFalse(Value::isType([1], 'int'));
        $this->assertFalse(Value::isType(fn() => 1, 'int'));
        $this->assertFalse(Value::isType(new stdClass(), 'int'));
        $this->assertFalse(Value::isType(new class() {}, 'int'));

        $this->assertTrue(Value::isType(1.0, 'float'));
        $this->assertTrue(Value::isType(INF, 'float'));
        $this->assertTrue(Value::isType(NAN, 'float'));
        $this->assertFalse(Value::isType(null, 'float'));
        $this->assertFalse(Value::isType(1, 'float'));
        $this->assertFalse(Value::isType('', 'float'));
        $this->assertFalse(Value::isType('1.0', 'float'));
        $this->assertFalse(Value::isType([], 'float'));
        $this->assertFalse(Value::isType([1], 'float'));
        $this->assertFalse(Value::isType(fn() => 1.0, 'float'));
        $this->assertFalse(Value::isType(new stdClass(), 'float'));
        $this->assertFalse(Value::isType(new class() {}, 'float'));

        $this->assertTrue(Value::isType('1', 'string'));
        $this->assertTrue(Value::isType('', 'string'));
        $this->assertTrue(Value::isType(DateTime::class, 'string'));
        $this->assertFalse(Value::isType(null, 'string'));
        $this->assertFalse(Value::isType(1, 'string'));
        $this->assertFalse(Value::isType(1.0, 'string'));
        $this->assertFalse(Value::isType(false, 'string'));
        $this->assertFalse(Value::isType([], 'string'));
        $this->assertFalse(Value::isType([1], 'string'));
        $this->assertFalse(Value::isType(fn() => '', 'string'));
        $this->assertFalse(Value::isType(new stdClass(), 'string'));
        $this->assertFalse(Value::isType(new class() {}, 'string'));

        $this->assertTrue(Value::isType([], 'array'));
        $this->assertTrue(Value::isType([1], 'array'));
        $this->assertTrue(Value::isType(['a' => 1], 'array'));
        $this->assertFalse(Value::isType('[]', 'array'));
        $this->assertFalse(Value::isType('', 'array'));
        $this->assertFalse(Value::isType(null, 'array'));
        $this->assertFalse(Value::isType(1, 'array'));
        $this->assertFalse(Value::isType(false, 'array'));
        $this->assertFalse(Value::isType(new stdClass(), 'array'));
        $this->assertFalse(Value::isType(new class() {}, 'array'));

        $this->assertTrue(Value::isType(new stdClass(), 'object'));
        $this->assertTrue(Value::isType(new class() {}, 'object'));
        $this->assertFalse(Value::isType(DateTime::class, 'object'));
        $this->assertFalse(Value::isType(null, 'object'));
        $this->assertFalse(Value::isType(1, 'object'));
        $this->assertFalse(Value::isType(1.0, 'object'));
        $this->assertFalse(Value::isType(false, 'object'));
        $this->assertFalse(Value::isType('', 'object'));
        $this->assertFalse(Value::isType('object', 'object'));
        $this->assertFalse(Value::isType([], 'object'));
        $this->assertFalse(Value::isType([1], 'object'));
        $this->assertFalse(Value::isType(fn() => new stdClass(), 'scalar'));

        $this->assertTrue(Value::isType([], 'iterable'));
        $this->assertTrue(Value::isType([1], 'iterable'));
        $this->assertTrue(Value::isType(['a' => 1], 'iterable'));
        $this->assertTrue(Value::isType(new class() implements IteratorAggregate { public function getIterator(): Traversable { yield 1; } }, 'iterable'));
        $this->assertFalse(Value::isType('[]', 'iterable'));
        $this->assertFalse(Value::isType('', 'iterable'));
        $this->assertFalse(Value::isType(null, 'iterable'));
        $this->assertFalse(Value::isType(1, 'iterable'));
        $this->assertFalse(Value::isType(false, 'iterable'));
        $this->assertFalse(Value::isType(new stdClass(), 'iterable'));
        $this->assertFalse(Value::isType(fn() => [], 'iterable'));

        $this->assertTrue(Value::isType('strlen', 'callable'));
        $this->assertTrue(Value::isType(strlen(...), 'callable'));
        $this->assertTrue(Value::isType(fn() => true, 'callable'));
        $this->assertTrue(Value::isType([$this, 'test_is'], 'callable'));
        $this->assertFalse(Value::isType(null, 'callable'));
        $this->assertFalse(Value::isType(1, 'callable'));
        $this->assertFalse(Value::isType(false, 'callable'));
        $this->assertFalse(Value::isType('', 'callable'));
        $this->assertFalse(Value::isType('?', 'callable'));
        $this->assertFalse(Value::isType([], 'callable'));
        $this->assertFalse(Value::isType([1], 'callable'));
        $this->assertFalse(Value::isType(new stdClass(), 'callable'));

        $this->assertTrue(Value::isType(1, 'scalar'));
        $this->assertTrue(Value::isType(1.0, 'scalar'));
        $this->assertTrue(Value::isType(false, 'scalar'));
        $this->assertTrue(Value::isType('', 'scalar'));
        $this->assertTrue(Value::isType('?', 'scalar'));
        $this->assertTrue(Value::isType(DateTime::class, 'scalar'));
        $this->assertFalse(Value::isType(null, 'scalar'));
        $this->assertFalse(Value::isType([], 'scalar'));
        $this->assertFalse(Value::isType([1], 'scalar'));
        $this->assertFalse(Value::isType(new stdClass(), 'scalar'));
        $this->assertFalse(Value::isType(new class() {}, 'scalar'));
        $this->assertFalse(Value::isType(fn() => true, 'scalar'));

        // open resource
        $resource = fopen(__FILE__, 'r');
        $this->assertTrue(Value::isType($resource, 'resource'));
        if(is_resource($resource)) fclose($resource);
        // closed resource
        $resource = fopen(__FILE__, 'r');
        if(is_resource($resource)) fclose($resource);
        $this->assertTrue(Value::isType($resource, 'resource'));
        $this->assertFalse(Value::isType(null, 'resource'));
        $this->assertFalse(Value::isType(1, 'resource'));
        $this->assertFalse(Value::isType(false, 'resource'));
        $this->assertFalse(Value::isType('', 'resource'));
        $this->assertFalse(Value::isType([], 'resource'));
        $this->assertFalse(Value::isType(new stdClass(), 'resource'));
        $this->assertFalse(Value::isType(fn() => true, 'resource'));

        $this->assertTrue(Value::isType(1, 'mixed'));
        $this->assertTrue(Value::isType(1.0, 'mixed'));
        $this->assertTrue(Value::isType(false, 'mixed'));
        $this->assertTrue(Value::isType('', 'mixed'));
        $this->assertTrue(Value::isType('?', 'mixed'));
        $this->assertTrue(Value::isType(DateTime::class, 'mixed'));
        $this->assertTrue(Value::isType(null, 'mixed'));
        $this->assertTrue(Value::isType([], 'mixed'));
        $this->assertTrue(Value::isType([1], 'mixed'));
        $this->assertTrue(Value::isType(new stdClass(), 'mixed'));
        $this->assertTrue(Value::isType(new class() {}, 'mixed'));
        $this->assertTrue(Value::isType(fn() => true, 'mixed'));

        $this->assertTrue(Value::isType(new DateTime(), DateTimeInterface::class));
        $this->assertTrue(Value::isType(new DateTimeImmutable(), DateTimeInterface::class));
        $this->assertTrue(Value::isType(new ConcreteClass(), AbstractClass::class));
        $this->assertTrue(Value::isType(fn() => true, Closure::class));
        $this->assertFalse(Value::isType(1, DateTimeInterface::class));
        $this->assertFalse(Value::isType(false, DateTimeInterface::class));
        $this->assertFalse(Value::isType('', DateTimeInterface::class));
        $this->assertFalse(Value::isType([], DateTimeInterface::class));
        $this->assertFalse(Value::isType(new stdClass(), DateTimeInterface::class));
        $this->assertFalse(Value::isType(fn() => true, DateTimeInterface::class));

        // union types
        $this->assertTrue(Value::isType(1, 'int|null'));
        $this->assertTrue(Value::isType(null, 'int|null'));
        $this->assertTrue(Value::isType(1, 'int|float'));
        $this->assertTrue(Value::isType(1.0, 'int|float'));
        $this->assertTrue(Value::isType('1', 'int|float|string'));
        $this->assertTrue(Value::isType([], 'array|object'));
        $this->assertTrue(Value::isType(new stdClass(), 'array|' . stdClass::class));
        $this->assertFalse(Value::isType(false, 'int|null'));
        $this->assertFalse(Value::isType('', 'int|float'));

        // intersection types
        $this->assertTrue(Value::isType(new IntersectClass(), 'Stringable&Countable'));
        $this->assertFalse(Value::isType(1, 'int&null'));

        // mixed types
        $this->assertTrue(Value::isType(1, 'int|(Stringable&Countable)'));
        $this->assertTrue(Value::isType(new IntersectClass(), 'int|(Stringable&Countable)'));
        $this->assertFalse(Value::isType(null, 'int|(Stringable&Countable)'));
        $this->assertFalse(Value::isType(1.0, 'int|(Stringable&Countable)'));
        $this->assertFalse(Value::isType('', 'int|(Stringable&Countable)'));

        // invalid type but passes since it never gets there.
        $this->assertTrue(Value::isType(1, 'int|Stringable&Countable'));
    }

    public function test_of_with_invalid_type(): void
    {
        $this->expectExceptionMessage('Invalid type: hi');
        $this->expectException(InvalidTypeException::class);
        Value::isType(1, 'hi|none');
    }

    public function test_of_with_mixed_type_without_parentheses(): void
    {
        $this->expectExceptionMessage('Invalid Type: Stringable&Countable|int (Intersection type missing parentheses?)');
        $this->expectException(InvalidTypeException::class);
        Value::isType(1, 'Stringable&Countable|int');
    }
}
