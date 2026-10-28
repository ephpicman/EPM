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
use InvalidArgumentException;

/**
 * Builds an include/exclude query with an additional 'is' condition.
 *
 * The 'is' condition accepts either one string or a list of strings whose values are combined with OR semantics.
 * The returned array uses EPM's internal query structure; a separate layer translates it to WordPress arguments.
 */

class IncludeExcludeIsQuery extends IncludeExcludeQuery
{
    /**
     * Whether the 'is' condition accepts multiple values.
     *
     * @var bool
     */
    protected bool $multiValue = false;

    /**
     * The optional value or values associated with the 'is' condition.
     *
     * @var string|array<int, string>|null
     *
     * @lock-value
     */
    protected string|array|null $is = null;

    /**
     * Creates the query and initializes its configuration and supplied values.
     *
     * @param array $queryArray   Initial query data containing optional 'included', 'excluded', and 'is' keys.
     * @param bool  $isIdentifier Whether inherited include/exclude values are normalized as non-negative integers.
     * @param bool  $multiValue   Whether the 'is' condition accepts a list of strings instead of one string.
     *
     * @lock-signature
     */
    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        bool $multiValue = false
    ) {
        $this->multiValue = $multiValue;

        parent::__construct($queryArray, $isIdentifier);

        if (isset($queryArray['is'])) {
            $this->is($queryArray['is']);
        }
    }

    /**
     * Creates a new instance using query data and optional configuration arguments.
     *
     * Supported arguments are 'isIdentifier' for inherited include/exclude normalization and 'multiValue' for the
     * cardinality of the 'is' condition.
     *
     * @param array $queryArray Initial query data.
     * @param array $args       Optional configuration; supports 'isIdentifier' and 'multiValue'.
     * @return static A new instance of the called class.
     *
     * @lock-signature
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['multiValue'] ?? false
        );
    }

    /**
     * Sets the additional 'is' condition.
     *
     * In single-value mode, a non-empty string is stored. In multi-value mode, empty strings are discarded and
     * duplicate strings are removed while preserving order. An empty result clears the condition.
     *
     * @param string|array<int, string> $value A string or a list of strings, according to the configured mode.
     * @return self                            The current query instance for method chaining.
     *
     * @throws InvalidArgumentException If the value does not match the configured mode or contains a non-string item.
     */
    public function is(string|array $value): self
    {
        if (false === $this->multiValue) {
            if (!\is_string($value)) {
                throw new InvalidArgumentException('Is value must be a string in single-value mode.');
            }

            $this->is = '' === $value ? null : $value;

            return $this;
        }

        if (!\is_array($value)) {
            throw new InvalidArgumentException('Is value must be an array in multi-value mode.');
        }

        $value = ArrayUtil::normalizeUniqueStrings($value);

        $this->is = [] === $value ? null : $value;

        return $this;
    }

    /**
     * Returns the query state in EPM's internal array structure.

     * Includes the inherited 'included' and 'excluded' keys and adds 'is' only when a non-empty value is set.
     * This array is not translated to WordPress-specific argument names.
     *
     * @return array<string, mixed>
     * 
     * @lock-signature
     */
    public function toArray(): array
    {
        $output = parent::toArray();

        if (null !== $this->is) {
            $output['is'] = $this->is;
        }

        return $output;
    }
}