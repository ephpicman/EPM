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
 * Builds an argument array containing an inclusion list for a WordPress query.
 *
 * Semantically, the list represents values connected by OR: a query matches when its target field matches any one
 * of the listed values. This is intended for WordPress query arguments such as 'role__in', 'post__in', and similar
 * *_in parameters.
 *
 * The output is an argument array to be passed to the relevant WordPress query API; this class does not execute
 * the query. Values are normalized as non-negative integers when identifier mode is enabled, or as strings
 * otherwise. Duplicate values are removed while preserving their original order. The configured include key is
 * used to read the initial input and as the key in the resulting argument array.
 */
class IncludeQueryArg implements QueryArg
{
    /**
     * Indicates whether values are normalized as non-negative integers or strings.
     *
     * @var bool
     */
    protected bool $isIdentifier = true;

    /**
     * The argument-array key used to read and write the inclusion list, such as 'role__in' or 'post__in'.
     *
     * @var string
     */
    protected string $includeKey;

    /**
     * The normalized, unique values in the inclusion list.
     *
     * @var array<int, int|string>
     * @lock-type
     * @lock-value
     */
    protected array $included = [];

    /**
     * Creates an inclusion-list argument builder.
     *
     * The resulting array is intended to be passed as arguments to a WordPress query API. Values in the list have
     * OR semantics: matching any listed value is sufficient.
     *
     * @param array  $queryArray   Initial query arguments; the configured key is read when present.
     * @param bool   $isIdentifier true to normalize values as non-negative integers, false to normalize as strings.
     * @param string $includeKey   Argument key for the list, for example 'role__in'; defaults to 'included'.
     */
    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        string $includeKey = 'included'
    ) {
        $this->isIdentifier = $isIdentifier;
        $this->includeKey = $includeKey;

        if (isset($queryArray[$this->includeKey])) {
            $this->include((array) $queryArray[$this->includeKey]);
        }
    }

    /**
     * Creates an instance from initial query arguments and optional configuration.
     *
     * The `$args['isIdentifier']` option controls value normalization and defaults to `true`.
     * The `$args['includeKey']` option selects the argument-array key and defaults to `'included'`.
     *
     * @param array $queryArray Initial query arguments.
     * @param array $args       Optional configuration, including 'isIdentifier' and 'includeKey'.
     * @return static           A new instance of the called class.
     *
     * @lock-signature
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['includeKey'] ?? 'included'
        );
    }

    /**
     * Normalizes a value according to the configured mode.
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
     * Adds one or more values to the inclusion list.
     *
     * The values are normalized according to the configured mode. Duplicates are removed, and the order of existing
     * values is preserved. Semantically, the values are alternatives (OR), not conditions that must all match.
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
     * Returns the query argument array containing the inclusion list under the configured key.
     *
     * Returns an empty array when no values have been added.
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
            $this->includeKey => $this->included,
        ];
    }

    /**
     * Merges normalized values into the inclusion list, removing duplicates.
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
