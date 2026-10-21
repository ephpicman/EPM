<?php

declare(strict_types=1);

/**
 * WordPress user inclusion and exclusion query argument builder.
 *
 * @package EPM
 * @version 1.0.0
 * @license MIT
 * @since 1.0.0
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for include and exclude arguments accepted by WP_User_Query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */
class IncludeExcludeQuery extends Query
{
    /**
     * User IDs included in the query.
     *
     * @var int[]
     */
    private array $include = [];

    /**
     * User IDs excluded from the query.
     *
     * @var int[]
     */
    private array $exclude = [];

    /**
     * Create an inclusion and exclusion query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     */
    public function __construct(array $queryArray = [])
    {
        if (isset($queryArray['include'])) {
            $this->include = array_map('absint', (array) $queryArray['include']);
        }

        if (isset($queryArray['exclude'])) {
            $this->exclude = array_map('absint', (array) $queryArray['exclude']);
        }
    }

    /**
     * Create a new inclusion and exclusion query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     * @return static
     */
    public static function make(array $queryArray = []): static
    {
        return new static($queryArray);
    }

    /**
     * Include one user by ID.
     *
     * Replaces the existing include list.
     *
     * @param int $id User ID.
     * @return $this
     */
    public function byId(int $id): self
    {
        $this->include = [absint($id)];

        return $this;
    }

    /**
     * Include users by their IDs.
     *
     * Replaces the existing include list.
     *
     * @param int[] $ids User IDs.
     * @return $this
     */
    public function byIds(array $ids): self
    {
        $this->include = array_values(array_unique(array_map('absint', $ids)));

        return $this;
    }

    /**
     * Exclude one user by ID.
     *
     * Replaces the existing exclude list.
     *
     * @param int $id User ID.
     * @return $this
     */
    public function avoidId(int $id): self
    {
        $this->exclude = [absint($id)];

        return $this;
    }

    /**
     * Exclude users by their IDs.
     *
     * Replaces the existing exclude list.
     *
     * @param int[] $ids User IDs.
     * @return $this
     */
    public function avoidIds(array $ids): self
    {
        $this->exclude = array_values(array_unique(array_map('absint', $ids)));

        return $this;
    }

    /**
     * Return the WordPress inclusion and exclusion query arguments.
     *
     * @return array<string, int[]>
     */
    public function toArray(): array
    {
        $args = [];

        if ([] !== $this->include) {
            $args['include'] = $this->include;
        }

        if ([] !== $this->exclude) {
            $args['exclude'] = $this->exclude;
        }

        return $args;
    }
}
