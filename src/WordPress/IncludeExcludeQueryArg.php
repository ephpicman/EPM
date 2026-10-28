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

namespace EPM\\WordPress;

use EPM\\Utils\\ArrayUtil;

/**
 * Builds inclusion and exclusion lists for a WordPress query argument array.
 *
 * The argument keys for both lists are configurable independently. Values are normalized as non-negative integers
 * when identifier mode is enabled, or as strings otherwise. Duplicate values are removed while preserving order.
 * A value cannot remain in both lists: adding it to one list removes it from the other.
 *
 * This class only builds the argument array; it does not execute the query. The meaning of inclusion and exclusion
 * is determined by the WordPress query API receiving the configured keys.
 */
class IncludeExcludeQueryArg extends IncludeQueryArg
{
    /**
     * The argument-array key used to read and write the exclusion list, such as 'role__not_in' or 'post__not_in'.
     *
     * @var string
     */
    protected string $excludeKey;

    /**
     * The normalized, unique values in the exclusion list.
     *
     * @var array<int, int|string>
     * @lock-type
     * @lock-value
     */
    protected array $excluded = [];

    /**
     * Creates an inclusion/exclusion argument builder.
     *
     * @param array  $queryArray    Initial query arguments; the configured include and exclude keys are read when present.
     * @param bool   $isIdentifier  true to normalize values as non-negative integers, false to normalize as strings.
     * @param string $includeKey    Argument key for the inclusion list.
     * @param string $excludeKey    Argument key for the exclusion list.
     */
    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        string $includeKey = 'include',
        string $excludeKey = 'exclude'
    ) {
        parent::__construct($queryArray, $isIdentifier, $includeKey);

        $this->excludeKey = $excludeKey;

        if (isset($queryArray[$this->excludeKey])) {
            $this->exclude((array) $queryArray[$this->excludeKey]);
        }
    }

    /**
     * Creates an instance from initial query arguments and optional configuration.
     *
     * The 'isIdentifier' option controls value normalization. 'includeKey' and 'excludeKey' independently select
     * the argument-array keys for the inclusion and exclusion lists.
     *
     * @param array $queryArray Initial query arguments.
     * @param array $args       Optional configuration: 'isIdentifier', 'includeKey', and 'excludeKey'.
     * @return static           A new instance of the called class.
     *
     * @lock-signature
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['includeKey'] ?? 'include',
            $args['excludeKey'] ?? 'exclude'
        );
    }

    /**
     * Adds values to the inclusion list.
     *
     * Values are normalized, deduplicated, and removed from the exclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to include.
     * @return static                       The current instance for method chaining.
     *
     * @lock-signature
     */
    public function include(int|float|string|array $value): static
    {
        $values = ArrayUtil::castValues($value, fn($item) => $this->cast($item));

        $this->mergeIncluded($values);
        $this->excluded = ArrayUtil::removeValues($this->excluded, $values);

        return $this;
    }

    /**
     * Adds values to the exclusion list.
     *
     * Values are normalized, deduplicated, and removed from the inclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to exclude.
     * @return static                       The current instance for method chaining.
     *
     * @lock-signature
     */
    public function exclude(int|float|string|array $value): static
    {
        $values = ArrayUtil::castValues($value, fn($item) => $this->cast($item));

        $this->excluded = ArrayUtil::mergeUnique($this->excluded, $values);
        $this->included = ArrayUtil::removeValues($this->included, $values);

        return $this;
    }

    /**
     * Returns the query argument array with non-empty lists under their configured keys.
     *
     * @return array<string, mixed>
     *
     * @lock-signature
     */
    public function toArray(): array
    {
        $output = parent::toArray();

        if ([] !== $this->excluded) {
            $output[$this->excludeKey] = $this->excluded;
        }

        return $output;
    }
}
