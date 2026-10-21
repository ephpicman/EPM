<?php

declare(strict_types=1);

/**
 * WordPress role query argument builder.
 *
 * @package EPM
 * @version 1.0.0
 * @license MIT
 * @since 1.0.0
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for the role-related arguments accepted by WP_User_Query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */
class RoleQuery extends Query
{
    /**
     * Required role or roles.
     *
     * @var string|string[]|null
     */
    private string|array|null $role = null;

    /**
     * Roles accepted by role__in.
     *
     * @var string[]
     */
    private array $roleIn = [];

    /**
     * Roles excluded by role__not_in.
     *
     * @var string[]
     */
    private array $roleNotIn = [];

    /**
     * Create a role-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     */
    public function __construct(array $queryArray = [])
    {
        if (isset($queryArray['role'])) {
            $this->role = $queryArray['role'];
        }

        if (isset($queryArray['role__in'])) {
            $this->roleIn = (array) $queryArray['role__in'];
        }

        if (isset($queryArray['role__not_in'])) {
            $this->roleNotIn = (array) $queryArray['role__not_in'];
        }
    }

    /**
     * Create a new role-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     * @return static
     */
    public static function make(array $queryArray = []): static
    {
        return new static($queryArray);
    }

    /**
     * Require users to have one role.
     *
     * Replaces the existing role requirement.
     *
     * @param string $role WordPress role slug.
     * @return $this
     */
    public function byRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Require users to have all specified roles.
     *
     * Replaces the existing role requirement.
     *
     * @param string[] $roles WordPress role slugs.
     * @return $this
     */
    public function byRoles(array $roles): self
    {
        $this->role = array_values(array_unique($roles));

        return $this;
    }

    /**
     * Add role requirements without replacing existing requirements.
     *
     * WordPress requires users to have every role in the role argument.
     *
     * @param string[] $roles Additional required role slugs.
     * @return $this
     */
    public function andInRoles(array $roles): self
    {
        $current = null === $this->role
            ? []
            : (is_array($this->role) ? $this->role : [$this->role]);

        $this->role = array_values(array_unique(array_merge($current, $roles)));

        return $this;
    }

    /**
     * Include users who have at least one of the specified roles.
     *
     * Replaces the existing role__in list.
     *
     * @param string[] $roles Role slugs.
     * @return $this
     */
    public function inRoles(array $roles): self
    {
        $this->roleIn = array_values(array_unique($roles));

        return $this;
    }

    /**
     * Exclude users with one role.
     *
     * Replaces the existing role__not_in list.
     *
     * @param string $role Role slug to exclude.
     * @return $this
     */
    public function avoidRole(string $role): self
    {
        $this->roleNotIn = [$role];

        return $this;
    }

    /**
     * Exclude users with any of the specified roles.
     *
     * Replaces the existing role__not_in list.
     *
     * @param string[] $roles Role slugs to exclude.
     * @return $this
     */
    public function avoidRoles(array $roles): self
    {
        $this->roleNotIn = array_values(array_unique($roles));

        return $this;
    }

    /**
     * Return the WordPress role-related query arguments.
     *
     * @return array<string, string|string[]>
     */
    public function toArray(): array
    {
        $args = [];

        if (null !== $this->role) {
            $args['role'] = $this->role;
        }

        if ([] !== $this->roleIn) {
            $args['role__in'] = $this->roleIn;
        }

        if ([] !== $this->roleNotIn) {
            $args['role__not_in'] = $this->roleNotIn;
        }

        return $args;
    }
}
