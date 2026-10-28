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
 * Builds inclusion, exclusion, and required-value conditions for a WordPress query argument array.
 *
 * The include, exclude, and is argument keys are configurable independently. The 'is' condition represents values
 * that must all be present (AND semantics). The multiValue option determines whether 'is' holds one string or a
 * list of strings; in multi-value mode, every listed value is required. Values are deduplicated while preserving
 * order, and a value cannot remain in both the inclusion and exclusion lists.
 *
 * This class only builds the argument array; it does not execute a query. The meaning of the configured keys is
 * determined by the WordPress query API receiving the resulting arguments.
 */
class IncludeExcludeIsQueryArg extends IncludeExcludeQueryArg
{
    /**
     * The argument-array key used to read and write the required-value condition.
     *
     * @var string
     */
    protected string $isKey;

    /**
     * Whether the 'is' condition contains multiple required values.
     *
     * @var bool
     */
    protected bool $multiValue = false;

    /**
     * The required value or values associated with the 'is' condition.
     *
     * In multi-value mode, every value in the array is required (AND semantics).
     *
     * @var string|array<int, string>|null
     * @lock-value
     */
    protected string|array|null $is = null;

    /**
     * Creates an inclusion/exclusion/required-value argument builder.
     *
     * @param array  $queryArray   Initial arguments; configured include, exclude, and is keys are read when present.
     * @param bool   $isIdentifier Whether include/exclude values are normalized as non-negative integers.
     * @param bool   $multiValue   Whether the is condition contains a list of required strings instead of one string.
     * @param string $includeKey   Argument key for the inclusion list.
     * @param string $excludeKey   Argument key for the exclusion list.
     * @param string $isKey        Argument key for the required-value condition.
     */
    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        bool $multiValue = false,
        string $includeKey = 'include',
        string $excludeKey = 'exclude',
        string $isKey = 'is'
    ) {
        $this->multiValue = $multiValue;
        $this->isKey = $isKey;

        parent::__construct($queryArray, $isIdentifier, $includeKey, $excludeKey);

        if (isset($queryArray[$this->isKey])) {
            $this->is($queryArray[$this->isKey]);
        }
    }

    /**
     * Creates an instance from initial arguments and optional configuration.
     *
     * Supported options are 'isIdentifier', 'multiValue', 'includeKey', 'excludeKey', and 'isKey'.
     *
     * @param array $queryArray Initial query arguments.
     * @param array $args       Optional configuration for value normalization, cardinality, and argument keys.
     * @return static            A new instance of the called class.
     *
     * @lock-signature
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['multiValue'] ?? false,
            $args['includeKey'] ?? 'include',
            $args['excludeKey'] ?? 'exclude',
            $args['isKey'] ?? 'is'
        );
    }

    /**
     * Sets the required-value condition.
     *
     * In single-value mode, a non-empty string is stored. In multi-value mode, empty strings are discarded and
     * duplicate strings are removed while preserving order. Every remaining value in multi-value mode is required.
     * An empty value or empty list clears the condition.
     *
     * @param string|array<int, string> $value A string or list of strings, according to the configured mode.
     * @return static                         The current instance for method chaining.
     *
     * @throws InvalidArgumentException If the value does not match the configured mode or contains a non-string item.
     */
    public function is(string|array $value): static
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
     * Returns the query argument array with non-empty conditions under their configured keys.
     *
     * @return array<string, mixed>
     *
     * @lock-signature
     */
    public function toArray(): array
    {
        $output = parent::toArray();

        if (null !== $this->is) {
            $output[$this->isKey] = $this->is;
        }

        return $output;
    }
}
