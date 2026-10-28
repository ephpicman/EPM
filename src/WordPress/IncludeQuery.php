<?php

/*
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ EPM — EphpicMan Framework                                                                                            │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│ Modification Restrictions:                                                                                           │
│                                                                                                                      │
│ @lock-name        The member's name must not change.                                                                 │
│ @lock-body        The method implementation must not change.                                                         │
│ @lock-params      Existing parameters must not change. Additional optional parameters may be appended at the end,    │
│                   provided backward compatibility is preserved.                                                      │
│ @lock-signature   The method signature, including parameters and return type, must not change.                       │
│ @lock-type        The declared type must not change. For methods, this refers to the return type.                    │
│ @lock-value       Constant values and property default values must not change.                                       │
│ @lock-access      The member's visibility must not change.                                                           │
│ @lock-static      The member's static status must not change.                                                        │
│ @lock-final       The member's final status must not change.                                                         │
│ @lock-abstract    The class or method's abstract status must not change.                                             │
│ @lock             The member must not be modified, renamed or removed.                                               │
│                                                                                                                      │
│ - Apply restriction tags to the class or individual members as appropriate.                                          │
│ - Unspecified modifications are allowed.                                                                             │
│ - When multiple tags apply, all restrictions must be respected.                                                      │
│ - @lock overrides all other tags and prohibits every modification to the target.                                     │
│                                                                                                                      │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│ This file is part of EPM, the EphpicMan Framework.                                                                   │
│                                                                                                                      │
│ Copyright (c) Sina Kuhestani (EphpicMan).                                                                            │
│ Licensed under the MIT License. See the LICENSE file for details.                                                    │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
*/

declare(strict_types=1);

namespace EPM\WordPress;

use EPM\Utils\ArrayUtil;
use EPM\Utils\NumberUtil;

/**
 * Builds an inclusion list as part of EPM's internal query structure.
 *
 * Values are normalized as non-negative integers or strings, depending on whether identifier mode is enabled.
 * Duplicate values are removed while preserving their order. The internal 'included' key is translated by a separate
 * layer when preparing arguments for a specific WordPress API.
 */
class IncludeQuery implements Query
{
    /**
     * Indicates whether values are normalized as non-negative integers or strings.
     * @var bool
     */
    protected bool $isIdentifier = true;

    /**
     * The normalized, unique values to include in the query.
     *
     * @var array<int, int|string>
     * @lock-type
     * @lock-value
     */
    protected array $included = [];

    /**
     * Creates an include query.
     *
     * @param array $queryArray   Initial query data; the 'included' key is read when present.
     * @param bool  $isIdentifier true to normalize values as non-negative integers, otherwise as strings.
     *
     * @lock-signature
     */
    public function __construct(array $queryArray = [], bool $isIdentifier = true)
    {
        $this->isIdentifier = $isIdentifier;

        if (isset($queryArray['included'])) {
            $this->include((array) $queryArray['included']);
        }
    }

    /**
     * Creates an instance from query data and optional configuration.
     * The `$args['isIdentifier']` value determines whether values are normalized as non-negative integers or strings;
     * it defaults to `true`.
     *
     * @param array $queryArray Initial query data.
     * @param array $args       Optional configuration, including 'isIdentifier'.
     * @return static           A new instance of the called class.
     *
     * @lock-signature
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray, $args['isIdentifier'] ?? true);
    }

    /**
     * Cast a value according to the configured mode.
     *
     * Identifier mode normalizes the value as a non-negative integer; otherwise, the value is converted to a string.
     *
     * @param int|float|string $value Value to normalize.
     * @return int|string             The normalized value.
     *
     * @lock-signature
     */
    protected function cast(int|float|string $value): int|string
    {
        return true === $this->isIdentifier ? NumberUtil::absint($value) : (string) $value;
    }

    /**
     * Add one or more values to the include list.
     *
     * Each value is normalized according to the configured mode. Duplicate values are removed, while existing values
     * retain their order.
     *
     * @param int|float|string|array $value A single value or a list of values.
     * @return static                       The current instance for method chaining.
     *
     * @lock-signature
     */
    public function include(int|float|string|array $value): static
    {
        $this->mergeIncluded(
            ArrayUtil::castValues($value, fn($item) => $this->cast($item))
        );

        return $this;
    }

    /**
     * Returns the normalized inclusion list in EPM's internal query structure.
     *
     * @return array<string, mixed>
     *
     * @lock-signature
     */
    public function toArray(): array
    {
        if ([] === $this->included) {
            return [];
        }

        return [
            'included' => $this->included,
        ];
    }

    /**
     * Merge normalized values into the current include list, removing duplicates.
     *
     * @param array $values Values that have already been normalized.
     * @return void
     *
     * @lock-signature
     */
    protected function mergeIncluded(array $values): void
    {
        $this->included = ArrayUtil::mergeUnique($this->included, $values);
    }
}
