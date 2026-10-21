<?php

declare(strict_types=1);

namespace EPM\Utils;

/**
 * Provides small, reusable operations for normalizing and combining arrays.
 *
 * The methods in this class return new arrays; they do not modify the input
 * arrays. Value conversion is delegated to a caller-provided callback so the
 * same helpers can be used with different value types and normalization rules.
 */
final class ArrayUtil
{
    /**
     * Casts one value or a list of values, then removes duplicates.
     *
     * A scalar input is treated as a one-item list. The callback is applied to
     * each item before duplicates are removed, so values that become equal
     * after casting are reduced to a single entry. The resulting array is
     * re-indexed from zero.
     *
     * Duplicate detection uses SORT_REGULAR comparison semantics.
     *
     * @param int|float|string|array $value One value or a list of values to cast.
     * @param callable $cast Callback applied to each value.
     *
     * @return array The cast, de-duplicated, numerically indexed values.
     */
    public static function castValues(
        int|float|string|array $value,
        callable $cast
    ): array {
        $values = \is_array($value) ? $value : [$value];

        return \array_values(
            \array_unique(
                \array_map($cast, $values),
                SORT_REGULAR
            )
        );
    }

    /**
     * Appends cast values to an existing list and removes duplicates.
     *
     * The supplied values are normalized by castValues() first, then merged
     * after the existing values. Duplicate detection is applied to the entire
     * merged list, preserving the first occurrence of each value. The result
     * is re-indexed from zero.
     *
     * The existing values are not passed through the callback; callers should
     * ensure that $current already contains values in the desired format.
     *
     * @param array $current Existing values to preserve and extend.
     * @param int|float|string|array $value One value or a list of values to cast
     *                                      and append.
     * @param callable $cast Callback applied to each incoming value.
     *
     * @return array The merged, de-duplicated, numerically indexed values.
     */
    public static function mergeCasted(
        array $current,
        int|float|string|array $value,
        callable $cast
    ): array {
        return \array_values(
            \array_unique(
                \array_merge(
                    $current,
                    self::castValues($value, $cast)
                ),
                SORT_REGULAR
            )
        );
    }

    /**
     * Removes values found in a second list.
     *
     * By default, removal follows array_diff() comparison semantics. When
     * $strict is true, each item is compared with in_array() using strict
     * identity (both value and type must match). In either mode, the returned
     * array is re-indexed from zero.
     *
     * @param array $current The list from which values should be removed.
     * @param array $values Values to remove from the list.
     * @param bool $strict Whether removal requires strict value-and-type matches.
     *
     * @return array The filtered, numerically indexed list.
     */
    public static function removeValues(
        array $current,
        array $values,
        bool $strict = false
    ): array {
        if (!$strict) {
            return \array_values(
                \array_diff($current, $values)
            );
        }

        return \array_values(
            \array_filter(
                $current,
                static fn($item): bool => !\in_array($item, $values, true)
            )
        );
    }
}
