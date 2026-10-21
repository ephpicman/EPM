<?php

declare(strict_types=1);

namespace EPM\\WordPress;

\\defined('ABSPATH') || exit;

/**
 * Builds search-related arguments for a WordPress query.
 *
 * Encapsulates a search term and the columns in which the search should be
 * performed. Requested columns are validated against an explicitly supplied
 * allowlist before the configuration is converted to WordPress query arguments.
 *
 * @see WP_Query
 * @see WP_User_Query
 */
class SearchQuery extends Query
{
    /**
     * The search term.
     *
     * A null value indicates that no search term is configured. Passing an
     * empty string to search() resets this property to null.
     *
     * @var string|null
     */
    protected ?string $search = null;

    /**
     * The validated columns to search.
     *
     * Contains unique column names accepted by the configured allowlist.
     * An empty array indicates that no search columns are configured.
     *
     * @var array<int, string>
     */
    protected array $searchColumns = [];

    /**
     * The column names permitted for searching.
     *
     * This allowlist is supplied during construction and is used to validate
     * values passed to searchColumns(). Duplicate entries are removed while
     * preserving the order of their first occurrence.
     *
     * @var array<int, string>
     */
    protected array $allowedSearchColumns;

    /**
     * Initialise the search query.
     *
     * Recognised query-array keys are search and searchColumns. Initial values
     * are applied through the corresponding setter methods; unrecognised keys
     * are ignored.
     *
     * @param array<int, string> $allowedSearchColumns Column names permitted for searching.
     * @param array<string, mixed> $queryArray Initial search configuration.
     */
    public function __construct(array $allowedSearchColumns, array $queryArray = [])
    {
        $this->allowedSearchColumns = \\array_values(
            \\array_unique($allowedSearchColumns, SORT_STRING)
        );

        if (isset($queryArray['search'])) {
            $this->search($queryArray['search']);
        }

        if (isset($queryArray['searchColumns'])) {
            $this->searchColumns($queryArray['searchColumns']);
        }
    }

    /**
     * Create an instance from query values and construction arguments.
     *
     * The $args array must contain an allowedSearchColumns entry defining the
     * columns that may be selected.
     *
     * @param array<string, mixed> $queryArray Initial search configuration.
     * @param array<string, mixed> $args Additional construction arguments.
     *
     * @return static A new search query instance.
     *
     * @throws \\InvalidArgumentException If allowedSearchColumns is missing.
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        if (!isset($args['allowedSearchColumns'])) {
            throw new \\InvalidArgumentException(
                'Allowed search columns must be provided.'
            );
        }

        return new static(
            $args['allowedSearchColumns'],
            $queryArray
        );
    }

    /**
     * Set the search term.
     *
     * Passing an empty string clears the configured search term.
     *
     * @param string $value The search term.
     *
     * @return self The current instance for method chaining.
     */
    public function search(string $value): self
    {
        $this->search = '' === $value ? null : $value;

        return $this;
    }

    /**
     * Set the columns in which the search should be performed.
     *
     * Accepts a single column name or an array of column names. Every value
     * must be a string present in the configured allowlist. Duplicate values
     * are removed while preserving their first occurrence.
     *
     * @param string|array<int, string> $value A column name or an array of names.
     *
     * @return self The current instance for method chaining.
     *
     * @throws \\InvalidArgumentException If a value is not a string or is not allowed.
     */
    public function searchColumns(string|array $value): self
    {
        $values = \\is_array($value) ? $value : [$value];

        $values = \\array_values(
            \\array_unique(
                \\array_filter(
                    $values,
                    function ($item): bool {
                        if (!\\is_string($item)) {
                            throw new \\InvalidArgumentException(
                                'Every search column must be a string.'
                            );
                        }

                        if (!\\in_array($item, $this->allowedSearchColumns, true)) {
                            throw new \\InvalidArgumentException(
                                'Invalid search column: ' . $item
                            );
                        }

                        return '' !== $item;
                    }
                ),
                SORT_STRING
            )
        );

        $this->searchColumns = $values;

        return $this;
    }

    /**
     * Convert the configured search values to WordPress query arguments.
     *
     * The internal searchColumns property is exported using the WordPress
     * argument name search_columns. Properties that are not configured are
     * omitted from the resulting array.
     *
     * @return array<string, string|array<int, string>> The configured WordPress search arguments.
     */
    public function toArray(): array
    {
        $output = [];

        if (null !== $this->search) {
            $output['search'] = $this->search;
        }

        if ([] !== $this->searchColumns) {
            $output['search_columns'] = $this->searchColumns;
        }

        return $output;
    }
}
