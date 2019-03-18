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
 * @covers \Bytemap\AbstractBytemap
 * @covers \Bytemap\Benchmark\AbstractDsBytemap
 * @covers \Bytemap\Benchmark\ArrayBytemap
 * @covers \Bytemap\Benchmark\DsDequeBytemap
 * @covers \Bytemap\Benchmark\DsVectorBytemap
 * @covers \Bytemap\Benchmark\SplBytemap
 * @covers \Bytemap\Bitmap
 * @covers \Bytemap\Bytemap
 */
final class SearchTest extends AbstractTestOfBytemap
{
    use FindingTestTrait;

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @dataProvider implementationProvider
     */
    public function testGreppingInvalidPatterns(string $impl): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Empty regular expression');
        self::instantiate($impl, "\x0")->grep(['~x~', '', '~y~'])->rewind();
    }

    public static function greppingProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            $elements = ['z', 'x', 'y', 'w', 'u', 't'];
            foreach ([
                [[], ['~~'], false, 1, null, []],
                [[], ['~~'], true, 1, null, []],
                [[], ['~x~'], false, 1, null, []],
                [[], ['~x~'], true, 1, null, []],

                [[1], [], false, 1, null, [1]],
                [[1], [], true, 1, null, []],
                [[1], ['~x~'], false, 1, null, []],
                [[1], ['~x~'], true, 1, null, [1]],
                [[1], ['~x~', '~X~'], false, 1, null, []],
                [[1], ['~x~', '~X~'], true, 1, null, [1]],
                [[1], ['~X~', '~z~'], false, 1, null, [1]],
                [[1], ['~X~', '~z~'], true, 1, null, []],
                [[1], ['~X~'], true, 1, null, []],
                [[1], ['~X~i'], true, 1, null, [1]],
                [[1], ['~z~'], false, 1, null, [1]],
                [[1], ['~z~'], true, 1, null, []],

                [[1, 2, 3, 1], ['~x~'], false, -2, null, [2 => 3, 1 => 2]],
                [[1, 2, 3, 1], ['~x~'], false, -1, null, [2 => 3]],
                [[1, 2, 3, 1], ['~x~'], false, 0, null, []],
                [[1, 2, 3, 1], ['~x~'], false, 1, null, [1 => 2]],
                [[1, 2, 3, 1], ['~x~'], false, 2, null, [1 => 2, 2 => 3]],

                [[1, 2, 3, 1], ['~x~'], true, -2, null, [3 => 1, 0 => 1]],
                [[1, 2, 3, 1], ['~x~'], true, -1, null, [3 => 1]],
                [[1, 2, 3, 1], ['~x~'], true, 0, null, []],
                [[1, 2, 3, 1], ['~x~'], true, 1, null, [0 => 1]],
                [[1, 2, 3, 1], ['~x~'], true, 2, null, [0 => 1, 3 => 1]],

                [[1, 2, 3, null, 1], ['~[axz]~'], false, \PHP_INT_MAX, null, [1 => 2, 2 => 3]],
                [[1, 2, 3, null, 1], ['~[axz]~'], true, \PHP_INT_MAX, null, [0 => 1, 3 => 0, 4 => 1]],
                [[1, 2, 3, null, 1], ['~[^axz]~'], true, \PHP_INT_MAX, null, [1 => 2, 2 => 3]],

                [[1, 2, 3, null, 1], ['~xy~'], true, \PHP_INT_MAX, null, []],

                [[1, 2, 3, null, 1], ['~^y|z$~'], true, \PHP_INT_MAX, null, [1 => 2, 3 => 0]],

                [[1, 2, 3, null, 1], ['~(?<=z)x~'], true, \PHP_INT_MAX, null, []],
                [[1, 2, 3, null, 1], ['~x(?=y)~'], true, \PHP_INT_MAX, null, []],

                [[1, 2, 3, null, 1], ['~~'], true, \PHP_INT_MAX, null, [1, 2, 3, 0, 1]],

                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, null, [1, 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 0, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 2, [3 => 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, -2, [6 => 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, -7, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 6, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, 42, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, 7, -42, [1, 2, 3, 4, 5, 1, 2]],

                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, null, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 0, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 2, [1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, -2, [4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, -7, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 6, [5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, 42, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~z~'], false, -7, -42, []],
            ] as [$subject, $patterns, $whitelist, $howMany, $startAfter, $expected]) {
                yield [$impl, $elements, $subject, $patterns, $whitelist, $howMany, $startAfter, $expected];
            }

            $elements = ['zx', 'xy', 'yy', 'wy', 'wx', 'tu'];
            foreach ([
                [[], ['~~'], false, 1, null, []],
                [[], ['~~'], true, 1, null, []],
                [[], ['~x~'], false, 1, null, []],
                [[], ['~x~'], true, 1, null, []],

                [[1], [], false, 1, null, [1]],
                [[1], [], true, 1, null, []],
                [[1], ['~xy~'], false, 1, null, []],
                [[1], ['~xy~'], true, 1, null, [1]],
                [[1], ['~xy~', '~Xy~'], false, 1, null, []],
                [[1], ['~xy~', '~Xy~'], true, 1, null, [1]],
                [[1], ['~Xy~', '~zx~'], false, 1, null, [1]],
                [[1], ['~Xy~', '~zx~'], true, 1, null, []],
                [[1], ['~Xy~'], true, 1, null, []],
                [[1], ['~Xy~i'], true, 1, null, [1]],
                [[1], ['~zx~'], false, 1, null, [1]],
                [[1], ['~zx~'], true, 1, null, []],

                [[1, 2, 3, 1], ['~xy~'], false, -2, null, [2 => 3, 1 => 2]],
                [[1, 2, 3, 1], ['~xy~'], false, -1, null, [2 => 3]],
                [[1, 2, 3, 1], ['~xy~'], false, 0, null, []],
                [[1, 2, 3, 1], ['~xy~'], false, 1, null, [1 => 2]],
                [[1, 2, 3, 1], ['~xy~'], false, 2, null, [1 => 2, 2 => 3]],

                [[1, 2, 3, 1], ['~xy~'], true, -2, null, [3 => 1, 0 => 1]],
                [[1, 2, 3, 1], ['~xy~'], true, -1, null, [3 => 1]],
                [[1, 2, 3, 1], ['~xy~'], true, 0, null, []],
                [[1, 2, 3, 1], ['~xy~'], true, 1, null, [0 => 1]],
                [[1, 2, 3, 1], ['~xy~'], true, 2, null, [0 => 1, 3 => 1]],

                [[1, 2, 3, null, 1], ['~[axz]~'], false, \PHP_INT_MAX, null, [1 => 2, 2 => 3]],
                [[1, 2, 3, null, 1], ['~[axz]~'], true, \PHP_INT_MAX, null, [0 => 1, 3 => 0, 4 => 1]],
                [[1, 2, 3, null, 1], ['~[^axz]~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],

                [[1, 2, 3, null, 1], ['~xyy~'], true, \PHP_INT_MAX, null, []],

                [[1, 2, 3, null, 1], ['~^yy|xy$~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 4 => 1]],
                [[1, 2, 3, null, 1], ['~y$~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],

                [[1, 2, 3, null, 1], ['~(?<=z)x~'], true, \PHP_INT_MAX, null, [3 => 0]],
                [[1, 2, 3, null, 1], ['~(?!<x)y~'], true, \PHP_INT_MAX, null, [0 => 1, 1 => 2, 2 => 3, 4 => 1]],
                [[1, 2, 3, null, 1], ['~x(?=y)~'], true, \PHP_INT_MAX, null, [0 => 1, 4 => 1]],
                [[1, 2, 3, 4, 1], ['~w(?!y)~'], true, \PHP_INT_MAX, null, [3 => 4]],

                [[1, 2, 3, null, 1], ['~yy|zx~'], true, \PHP_INT_MAX, null, [1 => 2, 3 => 0]],

                [[1, 2, 3, null, 1], ['~~'], true, \PHP_INT_MAX, null, [1, 2, 3, 0, 1]],

                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, null, [1, 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 0, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 2, [3 => 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, -2, [6 => 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, -7, [1 => 2, 3, 4, 5, 1, 2]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 6, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, 42, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, 7, -42, [1, 2, 3, 4, 5, 1, 2]],

                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, null, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 0, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 2, [1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, -2, [4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, -7, []],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 6, [5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, 42, [6 => 2, 5 => 1, 4 => 5, 3 => 4, 2 => 3, 1 => 2, 0 => 1]],
                [[1, 2, 3, 4, 5, 1, 2], ['~zx~'], false, -7, -42, []],

                [[1, 2, 3, 4, 5, 1, 2], (static function (): \Generator {
                    yield from ['~x~', '~z~'];
                })(), true, 7, null, [1, 3 => 4, 5 => 1]],
            ] as [$subject, $patterns, $whitelist, $howMany, $startAfter, $expected]) {
                yield [$impl, $elements, $subject, $patterns, $whitelist, $howMany, $startAfter, $expected];
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::grep
     * @dataProvider greppingProvider
     */
    public function testGrepping(
        string $impl,
        array $elements,
        array $subject,
        iterable $patterns,
        bool $whitelist,
        int $howMany,
        ?int $startAfter,
        array $expected
    ): void {
        $expectedSequence = [];
        foreach ($expected as $index => $key) {
            $expectedSequence[$index] = $elements[$key];
        }
        $bytemap = self::instantiate($impl, $elements[0]);
        foreach ($subject as $index => $key) {
            if (null !== $key) {
                $bytemap[$index] = $elements[$key];
            }
        }
        self::assertSame($expectedSequence, \iterator_to_array($bytemap->grep($patterns, $whitelist, $howMany, $startAfter)));
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
     * @covers \Bytemap\AbstractBytemap::grep
     * @depends testGrepping
     * @dataProvider implementationDirectionProvider
     */
    public function testGreppingCircularLookup(string $impl, bool $forward): void
    {
        $bytemap = self::instantiate($impl, "\x0\x0\x0");

        // The number of unique elements should exceed `AbstractBytemap::GREP_MAXIMUM_LOOKUP_SIZE`.
        for ($element = 'aaa'; $element <= 'pzz'; ++$element) {  // 16 * 26 * 26 = 10816 elements.
            $bytemap[] = $element;
        }
        $bytemap[0] = 'akk';
        $bytemap[] = 'akk';
        $pattern = '~(?<![c-d])(?<=[a-f])([k-p])(?=\\1)(?![m-n])~';  // [abef](kk|ll|oo|pp)
        $matchCount = 0;
        foreach ($bytemap->grep([$pattern], true, $forward ? \PHP_INT_MAX : -\PHP_INT_MAX) as $element) {
            ++$matchCount;
            if (1 === $matchCount) {
                $bytemap[1] = 'akk';
            }
        }
        self::assertSame(18, $matchCount);
    }

    // `FindingTestTrait`
    protected static function seekableInstanceProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x', 'y', 'w', 'u', 't'],
                ['zx', 'xy', 'yy', 'wy', 'ut', 'tu'],
            ] as $elements) {
                yield [self::instantiate($impl, $elements[0]), $elements];
            }
        }
    }

    protected static function seekableWithDefaultFirstProvider(): \Generator
    {
        foreach (self::implementationProvider() as [$impl]) {
            foreach ([
                ['z', 'x'],
                ['zx', 'xy'],
            ] as $elements) {
                yield [self::instantiate($impl, $elements[0]), $elements];
            }
        }
    }
}
