<?php

declare(strict_types=1);

/**
 * WordPress user search query argument builder.
 *
 * @package EPM
 * @version 1.0.0
 * @license MIT
 * @since 1.0.0
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for search-related arguments accepted by WP_User_Query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */
class SearchQuery extends Query
{
    /**
     * Search term.
     */
    private ?string $search = null;

    /**
     * User-table columns searched by WordPress.
     *
     * @var string[]|null
     */
    private ?array $searchColumns = null;

    /**
     * Create a search-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     */
    public function __construct(array $queryArray = [])
    {
        if (isset($queryArray['search'])) {
            $this->search = $queryArray['search'];
        }

        if (isset($queryArray['search_columns'])) {
            $this->searchColumns = (array) $queryArray['search_columns'];
        }
    }

    /**
     * Create a new search-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     * @return static
     */
    public static function make(array $queryArray = []): static
    {
        return new static($queryArray);
    }

    /**
     * Set the search term.
     *
     * Replaces the existing search term.
     *
     * @param string $search Search term. WordPress supports wildcard patterns.
     * @return $this
     */
    public function search(string $search): self
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Set the columns searched by WordPress.
     *
     * Replaces the existing search_columns list.
     *
     * Supported columns include user_login, user_email, user_url,
     * user_nicename, and display_name.
     *
     * @param string[] $columns Native WP_User_Query search columns.
     * @return $this
     */
    public function searchColumns(array $columns): self
    {
        $this->searchColumns = array_values(array_unique($columns));

        return $this;
    }

    /**
     * Search users by one native user-table column.
     *
     * Sets both the search term and its search column.
     *
     * @param string $search Search term.
     * @param string $column Native WP_User_Query search column.
     * @return $this
     */
    public function byColumn(string $search, string $column): self
    {
        $this->search = $search;
        $this->searchColumns = [$column];

        return $this;
    }

    /**
     * Return the WordPress search-related query arguments.
     *
     * @return array<string, string|string[]>
     */
    public function toArray(): array
    {
        $args = [];

        if (null !== $this->search) {
            $args['search'] = $this->search;
        }

        if (null !== $this->searchColumns) {
            $args['search_columns'] = $this->searchColumns;
        }

        return $args;
    }
}
