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

/**
 * Builds a query value containing mutually exclusive included and excluded values.
 *
 * Extends IncludeQuery with an exclusion list. Adding a value to one list removes the same value from the other list.
 */
class IncludeExcludeQuery extends IncludeQuery
{
    /**
     * Values that must be excluded from the query.
     *
     * @var array<int, int|float|string>
     * 
     * @lock-type
     * @lock-value
     */
    protected array $excluded = [];

    /**
     * Creates the query and optionally initializes its included and excluded values.
     *
     * @param array $queryArray   Initial query data. The 'excluded' key initializes the exclusion list; other supported
     *                            data is handled by the parent constructor.
     * @param bool  $isIdentifier Indicates if current query is subject to an identifier.
     * 
     * @lock-signature
     */
    public function __construct(array $queryArray = [], bool $isIdentifier = true)
    {
        parent::__construct($queryArray, $isIdentifier);

        if (isset($queryArray['excluded'])) {
            $this->exclude((array) $queryArray['excluded']);
        }
    }

    /**
     * Adds values to the inclusion list.
     *
     * Values are cast using the correct type, deduplicated by the parent helper, and removed from the exclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to include.
     * @return static                       The current query instance for method chaining.
     * 
     * @lock-signature
     */
    public function include(int|float|string|array $value): static
    {
        $values = ArrayUtil::castValues($value, [$this, 'cast']);

        $this->mergeIncluded($values);

        $this->excluded = ArrayUtil::removeValues(
            $this->excluded,
            $values
        );

        return $this;
    }

    /**
     * Adds values to the exclusion list.
     *
     * Values are cast using the configured type, deduplicated, and removed from the inclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to exclude.
     * @return static                       The current query instance for method chaining.
     * 
     * @lock-signature
     */
    public function exclude(int|float|string|array $value): static
    {
        $values = ArrayUtil::castValues($value, [$this, 'cast']);

        $this->excluded = ArrayUtil::mergeUnique($this->excluded, $values);
        $this->included = ArrayUtil::removeValues($this->included, $values);

        return $this;
    }

    /**
     * Returns the query state as an array.
     *
     * The parent output contains the included values. The 'excluded' key is added only when the exclusion list is not
     * empty.
     *
     * @return array<string, array<int, int|float|string>|int|string>
     * 
     * @lock-signature
     */
    public function toArray(): array
    {
        $output = parent::toArray();

        if ([] !== $this->excluded) {
            $output['excluded'] = $this->excluded;
        }

        return $output;
    }
}