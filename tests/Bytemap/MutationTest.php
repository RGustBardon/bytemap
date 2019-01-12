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
 * @covers \Bytemap\Bytemap
 */
final class MutationTest extends AbstractTestOfBytemap
{
    use DeletionTestTrait;
    use InsertionTestTrait;
    use InvalidElementsGeneratorTrait;
    use InvalidLengthGeneratorTrait;
    use InvalidTypeGeneratorsTrait;

    // `MutationTest`
    public static function invalidLengthProvider(): \Generator
    {
        foreach (self::arrayAccessProvider() as [$impl, $elements]) {
            foreach (self::generateElementsOfInvalidLength(\strlen($elements[0])) as $invalidElement) {
                $emptyBytemap = self::instantiate($impl, $elements[0]);
                yield from self::generateInvalidElements($emptyBytemap, \array_fill(0, 6, $elements[0]), $invalidElement);
            }
        }
    }

    /**
     * @covers \Bytemap\AbstractBytemap::insert
     * @dataProvider invalidLengthProvider
     */
    public function testInsertionOfInvalidLength(
        BytemapInterface $bytemap,
        array $elements,
        string $invalidElement,
        bool $useGenerator,
        array $sequence,
        array $inserted,
        int $firstIndex
    ): void {
        $expectedSequence = [];
        foreach ($sequence as $index => $key) {
            $expectedSequence[$index] = $elements[$key];
        }
        $generator = (static function () use ($elements, $inserted, $invalidElement) {
            foreach ($inserted as $key) {
                yield null === $key ? $invalidElement : $elements[$key];
            }
        })();

        try {
            $bytemap->insert($useGenerator ? $generator : \iterator_to_array($generator), $firstIndex);
        } catch (\DomainException $e) {
        }
        self::assertTrue(isset($e), 'Failed asserting that exception of type "\\DomainException" is thrown.');
        self::assertSequence($expectedSequence, $bytemap);
    }

    // `InsertionTestTrait`
    protected static function insertionInstanceProvider(): \Generator
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

    // `DeletionTestTrait`
    protected static function deletionInstanceProvider(): \Generator
    {
        yield from self::insertionInstanceProvider();
    }
}
