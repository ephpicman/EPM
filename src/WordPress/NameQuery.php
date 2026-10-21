<?php

declare(strict_types=1);

/**
 * WordPress user nicename query argument builder.
 *
 * @package EPM
 * @version 1.0.0
 * @license MIT
 * @since 1.0.0
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for nicename-related arguments accepted by WP_User_Query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */
class NameQuery extends Query
{
    /**
     * Exact user nicename.
     */
    private ?string $nicename = null;

    /**
     * User nicenames accepted by nicename__in.
     *
     * @var string[]
     */
    private array $nicenameIn = [];

    /**
     * User nicenames excluded by nicename__not_in.
     *
     * @var string[]
     */
    private array $nicenameNotIn = [];

    /**
     * Create a name-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     */
    public function __construct(array $queryArray = [])
    {
        if (isset($queryArray['nicename'])) {
            $this->nicename = $queryArray['nicename'];
        }

        if (isset($queryArray['nicename__in'])) {
            $this->nicenameIn = (array) $queryArray['nicename__in'];
        }

        if (isset($queryArray['nicename__not_in'])) {
            $this->nicenameNotIn = (array) $queryArray['nicename__not_in'];
        }
    }

    /**
     * Create a new name-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     * @return static
     */
    public static function make(array $queryArray = []): static
    {
        return new static($queryArray);
    }

    /**
     * Filter users by an exact nicename.
     *
     * Replaces the existing nicename argument.
     *
     * @param string $nicename User nicename.
     * @return $this
     */
    public function byNicename(string $nicename): self
    {
        $this->nicename = $nicename;

        return $this;
    }

    /**
     * Filter users by any of the specified nicenames.
     *
     * Replaces the existing nicename__in list.
     *
     * @param string[] $nicenames User nicenames.
     * @return $this
     */
    public function inNicenames(array $nicenames): self
    {
        $this->nicenameIn = array_values(array_unique($nicenames));

        return $this;
    }

    /**
     * Exclude users with one nicename.
     *
     * Replaces the existing nicename__not_in list.
     *
     * @param string $nicename User nicename to exclude.
     * @return $this
     */
    public function avoidNicename(string $nicename): self
    {
        $this->nicenameNotIn = [$nicename];

        return $this;
    }

    /**
     * Exclude users with any of the specified nicenames.
     *
     * Replaces the existing nicename__not_in list.
     *
     * @param string[] $nicenames User nicenames to exclude.
     * @return $this
     */
    public function avoidNicenames(array $nicenames): self
    {
        $this->nicenameNotIn = array_values(array_unique($nicenames));

        return $this;
    }

    /**
     * Return the WordPress nicename-related query arguments.
     *
     * @return array<string, string|string[]>
     */
    public function toArray(): array
    {
        $args = [];

        if (null !== $this->nicename) {
            $args['nicename'] = $this->nicename;
        }

        if ([] !== $this->nicenameIn) {
            $args['nicename__in'] = $this->nicenameIn;
        }

        if ([] !== $this->nicenameNotIn) {
            $args['nicename__not_in'] = $this->nicenameNotIn;
        }

        return $args;
    }
}
