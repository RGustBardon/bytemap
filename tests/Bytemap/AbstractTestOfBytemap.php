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

use PHPUnit\Framework\TestCase;

/**
 * @author Robert Gust-Bardon <robert@gust-bardon.org>
 *
 * @internal
 */
abstract class AbstractTestOfBytemap extends TestCase
{
    use ArrayAccessTestTrait;
    use CloneableTestTrait;
    use CountableTestTrait;
    use DeletionTestTrait;
    use FindingTestTrait;
    use InsertionTestTrait;
    use InvalidElementsGeneratorTrait;
    use InvalidTypeGeneratorsTrait;
    use IterableTestTrait;
    use JsonSerializableTestTrait;
    use JsonStreamTestTrait;
    use SerializableTestTrait;

    protected static function assertSequence(array $sequence, BytemapInterface $bytemap): void
    {
        self::assertCount(\count($sequence), $bytemap);
        $i = 0;
        foreach ($bytemap as $index => $element) {
            self::assertSame($i, $index);
            self::assertSame($sequence[$index], $element);
            ++$i;
        }
    }
}
