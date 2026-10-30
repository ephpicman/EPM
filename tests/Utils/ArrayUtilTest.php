<?php

declare(strict_types=1);

use EphpicMan\Framework\Utils\ArrayUtil;
use PHPUnit\Framework\TestCase;

final class ArrayUtilTest extends TestCase
{
    public function testCastValuesCastsDeduplicatesAndReindexes(): void
    {
        self::assertSame(
            [1, 2, 3],
            ArrayUtil::castValues([-1, '2', 3.8, -1], static fn ($value): int => (int) abs((float) $value))
        );
    }

    public function testCastValuesAcceptsASingleScalar(): void
    {
        self::assertSame(
            ['hello'],
            ArrayUtil::castValues('hello', static fn ($value): string => (string) $value)
        );
    }

    public function testMergeCastedPreservesExistingOrderAndAddsUniqueCastedValues(): void
    {
        self::assertSame(
            [1, 2, 3, 4],
            ArrayUtil::mergeCasted([1, 2], ['3', 2, '4'], static fn ($value): int => (int) $value)
        );
    }

    public function testNormalizeUniqueStringsRemovesEmptyAndDuplicateValues(): void
    {
        self::assertSame(
            ['alpha', 'beta'],
            ArrayUtil::normalizeUniqueStrings([
                'alpha',
                '',
                'beta',
                'alpha',
                '',
            ])
        );
    }

    public function testNormalizeUniqueStringsReindexesValues(): void
    {
        self::assertSame(
            ['alpha', 'beta'],
            ArrayUtil::normalizeUniqueStrings([
                10 => 'alpha',
                30 => 'beta',
            ])
        );
    }

    public function testNormalizeUniqueStringsRejectsNonStringValues(): void
    {
        foreach ([['alpha', 1], ['alpha', null], ['alpha', false]] as $values) {
            try {
                ArrayUtil::normalizeUniqueStrings($values);
                self::fail('Expected InvalidArgumentException for non-string values.');
            } catch (InvalidArgumentException $exception) {
                self::assertInstanceOf(InvalidArgumentException::class, $exception);
            }
        }
    }

    public function testMergeUniqueMergesMultipleArraysAndRemovesDuplicates(): void
    {
        self::assertSame(
            [1, 2, 3],
            ArrayUtil::mergeUnique([1, 2], [2, 1], [3, 2])
        );
    }

    public function testRemoveValuesCanUseStrictComparison(): void
    {
        self::assertSame(
            [1, '1', 2],
            ArrayUtil::removeValues([1, '1', 2, 3], [3], true)
        );

        self::assertSame(
            [2],
            ArrayUtil::removeValues([1, '1', 2, 3], [1, 3])
        );
    }

    public function testRemoveValuesReindexesSparseInput(): void
    {
        self::assertSame(
            ['a', 'c'],
            ArrayUtil::removeValues([4 => 'a', 8 => 'b', 10 => 'c'], ['b'], true)
        );
    }
}
