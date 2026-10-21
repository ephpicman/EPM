<?php

declare(strict_types=1);

namespace EPM\WordPress;

use EPM\Utils\ArrayUtil;

/**
 * Builds a query value containing mutually exclusive included and excluded values.
 *
 * Extends IncludeQuery with an exclusion list. Adding a value to one list
 * removes the same value from the other list.
 */
\defined('ABSPATH') || exit;

class IncludeExcludeQuery extends IncludeQuery
{
    /**
     * Values that must be excluded from the query.
     *
     * @var array<int, int|float|string>
     */
    protected array $excluded = [];

    /**
     * Creates the query and optionally initializes its included and excluded values.
     *
     * @param array $queryArray Initial query data. The 'excluded' key initializes
     *                          the exclusion list; other supported data is handled
     *                          by the parent constructor.
     * @param int   $type       Casting mode used for query values, such as INTEGER
     *                          or STRING.
     */
    public function __construct(array $queryArray = [], int $type = self::INTEGER)
    {
        parent::__construct($queryArray, $type);

        if (isset($queryArray['excluded'])) {
            $this->exclude((array) $queryArray['excluded']);
        }
    }

    /**
     * Adds values to the inclusion list.
     *
     * Values are cast using the configured type, deduplicated by the parent
     * helper, and removed from the exclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to include.
     *
     * @return self The current query instance for method chaining.
     */
    public function include(int|float|string|array $value): self
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
     * Values are cast using the configured type, deduplicated, and removed
     * from the inclusion list.
     *
     * @param int|float|string|array $value One value or a list of values to exclude.
     *
     * @return self The current query instance for method chaining.
     */
    public function exclude(int|float|string|array $value): self
    {
        $values = ArrayUtil::castValues($value, [$this, 'cast']);

        $this->excluded = \array_values(
            \array_unique(
                \array_merge($this->excluded, $values),
                SORT_REGULAR
            )
        );

        $this->included = ArrayUtil::removeValues(
            $this->included,
            $values
        );

        return $this;
    }

    /**
     * Returns the query state as an array.
     *
     * The parent output contains the included values. The 'excluded' key is
     * added only when the exclusion list is not empty.
     *
     * @return array<string, array<int, int|float|string>|int|string>
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