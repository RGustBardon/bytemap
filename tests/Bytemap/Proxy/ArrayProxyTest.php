<?php

declare(strict_types=1);

/*
 * This file is part of the Bytemap package.
 *
 * (c) Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bytemap\Proxy;

use Bytemap\Bytemap;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 * @covers \Bytemap\Proxy\ArrayProxy
 */
final class ArrayProxyTest extends AbstractTestOfProxy
{
    public function testConstructor(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        self::assertSame($values, self::instantiate(...$values)->exportArray());
    }

    public function testClone(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        $clone = clone $arrayProxy;
        self::assertNotSame($arrayProxy, $clone);
        $clone[] = 'bb';
        self::assertSame($values, $arrayProxy->exportArray());
        self::assertSame(\array_merge($values, ['bb']), $clone->exportArray());
    }

    public function testWrapUnwrap(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $bytemap = new Bytemap('ab');
        $bytemap->insert($values);
        $arrayProxy = self::instantiate()::wrap($bytemap);
        self::assertSame($values, $arrayProxy->exportArray());

        $arrayProxy[] = 'bb';
        self::assertSame($bytemap, $arrayProxy->unwrap());
    }

    public function testExportArray(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        self::assertSame($values, $arrayProxy->exportArray());
    }

    public function testImport(): void
    {
        $arrayProxy = self::instantiate()::import('cd', ['xy', 2 => 'ef']);
        self::assertSame(['xy', 'ef'], $arrayProxy->exportArray());
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage Size parameter expected to be greater than 0
     */
    public function testChunkSizeNotPositive(): void
    {
        self::instantiate('cd', 'xy', 'ef', 'ef')->chunk(0, false)->rewind();
    }

    public function testChunk(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef', 'bb');
        self::assertSame([
            ['cd', 'xy'],
            ['ef', 'ef'],
            ['bb'],
        ], \iterator_to_array($arrayProxy->chunk(2, false)));
        self::assertSame([
            [0 => 'cd', 1 => 'xy'],
            [2 => 'ef', 3 => 'ef'],
            [4 => 'bb'],
        ], \iterator_to_array($arrayProxy->chunk(2, true)));
    }

    public function testCountValues(): void
    {
        self::assertSame([], self::instantiate()->countValues());
        self::assertSame([
            'cd' => 1,
            'xy' => 2,
            'ef' => 2,
        ], self::instantiate('cd', 'xy', 'ef', 'ef', 'xy')->countValues());
    }

    public function testInArray(): void
    {
        self::assertFalse(self::instantiate()->inArray('ab'));
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->inArray('ab'));
        self::assertTrue($arrayProxy->inArray('ef'));
    }

    public function testKeyExists(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->keyExists(-1));
        self::assertTrue($arrayProxy->keyExists(0));
        self::assertTrue($arrayProxy->keyExists(3));
        self::assertFalse($arrayProxy->keyExists(4));
    }

    public function testKeyFirst(): void
    {
        self::assertNull(self::instantiate()->keyFirst());
        self::assertSame(0, self::instantiate('cd', 'xy', 'ef', 'ef')->keyFirst());
    }

    public function testKeyLast(): void
    {
        self::assertNull(self::instantiate()->keyLast());
        self::assertSame(3, self::instantiate('cd', 'xy', 'ef', 'ef')->keyLast());
    }

    public function testKeys(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertSame([0, 1, 2, 3], \iterator_to_array($arrayProxy->keys()));
        self::assertSame([], \iterator_to_array($arrayProxy->keys('ab')));
        self::assertSame([2, 3], \iterator_to_array($arrayProxy->keys('ef')));
    }

    public function testMerge(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        $array = ['a1', 'a2', 'a3'];
        $bytemap = new Bytemap('cd');
        $bytemap->insert(['b1', 'b2']);
        $generator = function (): \Generator {
            yield from ['g1', 'g2', 'g3'];
        };
        self::assertSame([
            'cd', 'xy', 'ef', 'ef',
            'a1', 'a2', 'a3',
            'b1', 'b2',
            'g1', 'g2', 'g3',
        ], $arrayProxy->merge($array, $bytemap, $generator())->exportArray());
        self::assertSame($values, $arrayProxy->exportArray());
    }

    public function testPad(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        $arrayProxy = self::instantiate(...$values);
        self::assertSame($values, $arrayProxy->pad(2, 'bb')->exportArray());
        self::assertSame($values + [4 => 'bb', 5 => 'bb'], $arrayProxy->pad(6, 'bb')->exportArray());
        self::assertSame(\array_merge(['bb', 'bb'], $values), $arrayProxy->pad(-6, 'bb')->exportArray());
        self::assertSame($values, $arrayProxy->exportArray());
    }

    public function testPop(): void
    {
        $arrayProxy = self::instantiate();
        self::assertNull($arrayProxy->pop());

        $arrayProxy = self::instantiate('ef', 'cd', 'xy');
        self::assertSame('xy', $arrayProxy->pop());
        self::assertSame(['ef', 'cd'], $arrayProxy->exportArray());
    }

    public function testPush(): void
    {
        $arrayProxy = self::instantiate();
        self::assertSame(2, $arrayProxy->push('cd', 'xy'));
        self::assertSame(['cd', 'xy'], $arrayProxy->exportArray());
        self::assertSame(4, $arrayProxy->push('ef', 'ef'));
        self::assertSame(['cd', 'xy', 'ef', 'ef'], $arrayProxy->exportArray());
    }

    public function testReduce(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertSame('barcd2xy2ef2ef2', $arrayProxy->reduce(function (string $initial, string $value): string {
            return $initial.$value.\strlen($value);
        }, 'bar'));
    }

    public function testReverse(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef', 'bb'];
        $arrayProxy = self::instantiate(...$values);
        self::assertSame(['bb', 'ef', 'ef', 'xy', 'cd'], $arrayProxy->reverse()->exportArray());
        self::assertSame($values, $arrayProxy->exportArray());
    }

    public function testSearch(): void
    {
        $arrayProxy = self::instantiate('cd', 'xy', 'ef', 'ef');
        self::assertFalse($arrayProxy->search('ab'));
        self::assertSame(1, $arrayProxy->search('xy'));
        self::assertSame(2, $arrayProxy->search('ef'));
    }

    public function testShift(): void
    {
        $arrayProxy = self::instantiate();
        self::assertNull($arrayProxy->shift());

        $arrayProxy = self::instantiate('ef', 'cd', 'xy');
        self::assertSame('ef', $arrayProxy->shift());
        self::assertSame(['cd', 'xy'], $arrayProxy->exportArray());
    }

    public static function sliceProvider(): \Generator
    {
        foreach ([
            [[], 0, 0, []],
            [['cd', 'xy', 'ef', 'ef'], -3, null, ['xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, null, ['cd', 'xy', 'ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 2, null, ['ef', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 2, 0, []],
            [['cd', 'xy', 'ef', 'ef'], -2, 1, ['ef']],
            [['cd', 'xy', 'ef', 'ef'], 0, 1, ['cd']],
            [['cd', 'xy', 'ef', 'ef'], 0, 3, ['cd', 'xy', 'ef']],
            [['cd', 'xy', 'ef', 'ef'], 1, 1, ['xy']],
            [['cd', 'xy', 'ef', 'ef'], 1, -1, ['xy', 'ef']],
        ] as [$items, $offset, $length, $expected]) {
            yield [$items, $offset, $length, $expected];
        }
    }

    /**
     * @dataProvider sliceProvider
     */
    public function testSlice(array $items, int $offset, ?int $length, array $expected): void
    {
        $arrayProxy = self::instantiate(...$items);
        self::assertSame($expected, $arrayProxy->slice($offset, $length)->exportArray());
        self::assertSame($items, $arrayProxy->exportArray());
    }

    public function testUnshift(): void
    {
        $arrayProxy = self::instantiate('ef', 'cd');
        self::assertSame(4, $arrayProxy->unshift('xy', 'cc'));
        self::assertSame(['xy', 'cc', 'ef', 'cd'], $arrayProxy->exportArray());
    }

    public function testValues(): void
    {
        $values = ['cd', 'xy', 'ef', 'ef'];
        self::assertSame($values, \iterator_to_array(self::instantiate(...$values)->values()));
    }

    /**
     * @expectedException \UnderflowException
     * @expectedExceptionMessage equal number of elements
     */
    public function testCombineCardinalityMismatch(): void
    {
        self::instantiate()::combine('cd', [1, 6, 3], ['ab', 'ef']);
    }

    public function testCombine(): void
    {
        $arrayProxy = self::instantiate()::combine('cd', [1, 6, 3], ['ab', 'ef', 'xy']);
        self::assertSame(['cd', 'ab', 'cd', 'xy', 'cd', 'cd', 'ef'], $arrayProxy->exportArray());
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage index can't be negative
     */
    public function testFillNegativeIndex(): void
    {
        self::instantiate()::fill('cd', -2, 3);
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage elements can't be negative
     */
    public function testFillNegativeCardinality(): void
    {
        self::instantiate()::fill('cd', 2, -3);
    }

    public function testFill(): void
    {
        $arrayProxy = self::instantiate()::fill('cd', 0, 3);
        self::assertSame(['cd', 'cd', 'cd'], $arrayProxy->exportArray());
    }

    public function testFillKeys(): void
    {
        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], null);
        self::assertSame(['cd', 'cd', 'cd', 'cd', 'cd', 'cd', 'cd'], $arrayProxy->exportArray());

        $arrayProxy = self::instantiate()::fillKeys('cd', [1, 6, 3], 'ab');
        self::assertSame(['cd', 'ab', 'cd', 'ab', 'cd', 'cd', 'ab'], $arrayProxy->exportArray());
    }

    public static function instantiate(string ...$items): ArrayProxyInterface
    {
        return new ArrayProxy('ab', ...$items);
    }
}
