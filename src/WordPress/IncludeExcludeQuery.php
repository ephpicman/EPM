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
 * Builds inclusion and exclusion lists as part of EPM's internal query structure.
 *
 * Extends IncludeQuery with an exclusion list. Values added to one list are removed from the other list. A separate
 * layer translates the internal 'included' and 'excluded' keys for the target WordPress API.
 */
class IncludeExcludeQuery extends IncludeQuery
{
    /**
     * The values to exclude from the query.
     *
     * @var array<int, int|float|string>
     * @lock-type
     * @lock-value
     */
    protected array $excluded = [];

    /**
     * Creates the query and optionally initializes its included and excluded values.
     *
     * @param array $queryArray   Initial query data; 'included' is handled by the parent constructor and 'excluded'
     *                            initializes the exclusion list.
     * @param bool  $isIdentifier Whether values are normalized as non-negative integers or strings.
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
     * Values are normalized according to the configured mode, deduplicated, and removed from the exclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to include.
     * @return static                       The current query instance for method chaining.
     *
     * @lock-signature
     */
    public function include(int|float|string|array $value): static
    {
        $values = ArrayUtil::castValues($value, fn($item) => $this->cast($item));

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
     * Values are normalized according to the configured mode, deduplicated, and removed from the inclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to exclude.
     * @return static                       The current query instance for method chaining.
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
     * Returns the inclusion and exclusion lists in EPM's internal query structure.
     *
     * @return array<string, mixed>
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