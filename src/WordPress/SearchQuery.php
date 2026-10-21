<?php

declare(strict_types=1);

namespace EPM\\WordPress;

\\defined('ABSPATH') || exit;

/**
 * Builds search-related arguments for a WordPress query.
 *
 * Stores the search term and the allowed search columns, then converts them
 * to the argument names expected by WordPress.
 */
class SearchQuery extends Query
{
    /**
     * Search term to match.
     *
     * A null value means no search term is configured.
     */
    protected ?string $search = null;

    /**
     * Validated columns to search.
     *
     * @var array<int, string>
     */
    protected array $searchColumns = [];

    /**
     * Column names permitted for searching.
     *
     * @var array<int, string>
     */
    protected array $allowedSearchColumns;

    /**
     * Create a search query and optionally initialize it from an array.
     *
     * Duplicate allowed column names are removed while preserving their order.
     *
     * @param array<int, string> $allowedSearchColumns Column names permitted for searching.
     * @param array<string, mixed> $queryArray         Initial search values.
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
     * Create an instance using the allowed columns supplied in the arguments.
     *
     * The $args array must contain an 'allowedSearchColumns' entry.
     *
     * @param array<string, mixed> $queryArray Initial search values.
     * @param array<string, mixed> $args       Additional construction arguments.
     *
     * @throws \\InvalidArgumentException If allowed search columns are not provided.
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
     * An empty string clears the search term.
     *
     * @param string $value Search term.
     *
     * @return self This query instance.
     */
    public function search(string $value): self
    {
        $this->search = '' === $value ? null : $value;

        return $this;
    }

    /**
     * Set the columns to search.
     *
     * Each value must be a string present in the allowed-column list.
     * Empty strings are filtered out; duplicate values are removed.
     *
     * @param string|array<int, string> $value One column name or a list of names.
     *
     * @return self This query instance.
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
     * Convert the configured values to WordPress query arguments.
     *
     * The internal camelCase property searchColumns is exported as the
     * WordPress argument search_columns. Unset values are omitted.
     *
     * @return array<string, string|array<int, string>> WordPress search arguments.
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