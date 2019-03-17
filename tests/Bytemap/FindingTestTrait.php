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

namespace Bytemap;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
trait FindingTestTrait
{
    abstract public static function assertSame($expected, $actual, string $message = ''): void;
    
    abstract protected static function seekableInstanceProvider(): \Generator;
    
    public static function seekableProvider(): \Generator
    {
        foreach ([
            [[], [], false, true, -1, null, []],
            [[], [], false, true, 0, null, []],
            [[], [], false, false, 1, null, []],
            [[], [], true, false, 1, null, []],
            
            [[1], [], false, true, \PHP_INT_MAX, null, []],
            [[1], [], false, false, 0, null, []],
            [[1], [], false, false, 1, null, [1]],
            [[1], [], false, false, \PHP_INT_MAX, null, [1]],
            
            [[1], [1], false, false, \PHP_INT_MAX, null, []],
            [[1], [1], false, true, -2, null, [1]],
            [[1], [1], false, true, -1, null, [1]],
            [[1], [1], false, true, 0, null, []],
            [[1], [1], false, true, 1, null, [1]],
            [[1], [1], false, true, 2, null, [1]],
            
            [[1], [2], false, false, \PHP_INT_MAX, null, [1]],
            [[1], [2], false, true, -2, null, []],
            [[1], [2], false, true, -1, null, []],
            [[1], [2], false, true, 0, null, []],
            [[1], [2], false, true, 1, null, []],
            [[1], [2], false, true, 2, null, []],
            
            [[1, 1], [1], false, true, -2, null, [1 => 1, 0 => 1]],
            [[1, 1], [1], false, true, -1, null, [1 => 1]],
            [[1, 1], [1], false, true, 0, null, []],
            [[1, 1], [1], false, true, 1, null, [1]],
            [[1, 1], [1], false, true, 2, null, [1, 1]],
            
            [[1, 1], [1, 1], false, true, -2, null, [1 => 1, 0 => 1]],
            [[1, 1], [1, 1], false, true, -1, null, [1 => 1]],
            [[1, 1], [1, 1], false, true, 0, null, []],
            [[1, 1], [1, 1], false, true, 1, null, [1]],
            [[1, 1], [1, 1], false, true, 2, null, [1, 1]],
            
            [[1, 1], [2, 1, 3], false, true, -2, null, [1 => 1, 0 => 1]],
            [[1, 1], [2, 1, 3], false, true, -1, null, [1 => 1]],
            [[1, 1], [2, 1, 3], false, true, 0, null, []],
            [[1, 1], [2, 1, 3], false, true, 1, null, [1]],
            [[1, 1], [2, 1, 3], false, true, 2, null, [1, 1]],
            
            [[4, 1, 4, 1], [2, 1, 3], false, false, -2, null, [2 => 4, 0 => 4]],
            [[4, 1, 4, 1], [2, 1, 3], false, false, -1, null, [2 => 4]],
            [[4, 1, 4, 1], [2, 1, 3], false, false, 0, null, []],
            [[4, 1, 4, 1], [2, 1, 3], false, false, 1, null, [0 => 4]],
            [[4, 1, 4, 1], [2, 1, 3], false, false, 2, null, [0 => 4, 2 => 4]],
            
            [[4, 1, 4, 1], [2, 1, 3], false, true, -2, null, [3 => 1, 1 => 1]],
            [[4, 1, 4, 1], [2, 1, 3], false, true, -1, null, [3 => 1]],
            [[4, 1, 4, 1], [2, 1, 3], false, true, 0, null, []],
            [[4, 1, 4, 1], [2, 1, 3], false, true, 1, null, [1 => 1]],
            [[4, 1, 4, 1], [2, 1, 3], false, true, 2, null, [1 => 1, 3 => 1]],
            
            [[4, 1, 4, 1], [2, 1, 3], true, true, 2, null, [1 => 1, 3 => 1]],
            
            [[4, null, 4, 1], [2, 0, 1], false, true, 2, null, [1 => 0, 3 => 1]],
            
            [[4, null, 0, 1], null, false, false, 2, null, [1 => 0, 2 => 0]],
            [[4, null, 0, 1], null, false, true, 2, null, [0 => 4, 3 => 1]],
            
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, null, [1, 2, 3, 4, 5, 1, 2]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 0, [1 => 2, 3, 4, 5, 1, 2]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 2, [3 => 4, 5, 1, 2]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, -2, [6 => 2]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, -7, [1 => 2, 3, 4, 5, 1, 2]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 6, []],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, 42, []],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, 7, -42, [1, 2, 3, 4, 5, 1, 2]],
            
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, null, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 0, []],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 2, [1 => 2, 0 => 1]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, -2, [4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, -7, []],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 6, [5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, 42, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
            [[1, 2, 3, 4, 5, 1, 2], [0], false, false, -7, -42, []],
        ] as [$subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected]) {
            foreach (self::seekableInstanceProvider() as [$emptyBytemap, $elements]) {
                yield [$emptyBytemap, $elements, $subject, $query, $generator, $whitelist, $howMany, $startAfter, $expected];
            }
        }
    }
    
    /**
     * @covers \Bytemap\AbstractBytemap::find
     * @covers \Bytemap\Bitmap::find
     * @dataProvider seekableProvider
     */
    public function testFinding(
        BytemapInterface $bytemap,
        array $elements,
        array $subject,
        ?array $query,
        bool $generator,
        bool $whitelist,
        int $howMany,
        ?int $startAfter,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $index => $key) {
            $expectedSequence[$index] = $elements[$key];
        }
        foreach ($subject as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $elements[$key];
            }
        }
        if (null !== $query) {
            $queryIndices = $query;
            $query = (static function () use ($elements, $queryIndices) {
                foreach ($queryIndices as $key) {
                    yield $elements[$key];
                }
            })();
            if (!$generator) {
                $query = \iterator_to_array($query);
            }
        }
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->find($query, $whitelist, $howMany, $startAfter)));
    }
    
    public static function implementationDirectionProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([true, false] as $forward) {
                yield [$impl, $forward];
            }
        }
    }
    
    /**
     * @covers \Bytemap\AbstractBytemap::find
     * @depends testFinding
     * @dataProvider implementationDirectionProvider
     */
    public function FIXMEtestFindingCloning(string $impl, bool $forward): void
    {
        $bytemap = self::instantiate($impl, "\x0");
        self::pushElements($bytemap, 'a', 'b', 'c', 'a', 'b', 'c');
        
        $matchCount = 0;
        foreach ($bytemap->find(['a', 'c'], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX) as $element) {
            ++$matchCount;
            if (1 === $matchCount) {
                $bytemap[1] = 'a';
            }
        }
        self::assertSame(4, $matchCount);
    }
}