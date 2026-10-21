<?php

declare(strict_types=1);

/**
 * WordPress user-login query argument builder.
 *
 * @package EPM
 * @version 1.0.0
 * @license MIT
 * @since 1.0.0
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for login-related arguments accepted by WP_User_Query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */
class LoginQuery extends Query
{
    /**
     * Exact user login.
     */
    private ?string $login = null;

    /**
     * User logins accepted by login__in.
     *
     * @var string[]
     */
    private array $loginIn = [];

    /**
     * User logins excluded by login__not_in.
     *
     * @var string[]
     */
    private array $loginNotIn = [];

    /**
     * Create a login-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     */
    public function __construct(array $queryArray = [])
    {
        if (isset($queryArray['login'])) {
            $this->login = $queryArray['login'];
        }

        if (isset($queryArray['login__in'])) {
            $this->loginIn = (array) $queryArray['login__in'];
        }

        if (isset($queryArray['login__not_in'])) {
            $this->loginNotIn = (array) $queryArray['login__not_in'];
        }
    }

    /**
     * Create a new login-query builder.
     *
     * @param array<string, mixed> $queryArray Existing WordPress query arguments.
     * @return static
     */
    public static function make(array $queryArray = []): static
    {
        return new static($queryArray);
    }

    /**
     * Filter users by an exact login.
     *
     * Replaces the existing login argument.
     *
     * @param string $login User login.
     * @return $this
     */
    public function byLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Filter users by any of the specified logins.
     *
     * Replaces the existing login__in list.
     *
     * @param string[] $logins User logins.
     * @return $this
     */
    public function inLogins(array $logins): self
    {
        $this->loginIn = array_values(array_unique($logins));

        return $this;
    }

    /**
     * Exclude users with one login.
     *
     * Replaces the existing login__not_in list.
     *
     * @param string $login User login to exclude.
     * @return $this
     */
    public function avoidLogin(string $login): self
    {
        $this->loginNotIn = [$login];

        return $this;
    }

    /**
     * Exclude users with any of the specified logins.
     *
     * Replaces the existing login__not_in list.
     *
     * @param string[] $logins User logins to exclude.
     * @return $this
     */
    public function avoidLogins(array $logins): self
    {
        $this->loginNotIn = array_values(array_unique($logins));

        return $this;
    }

    /**
     * Return the WordPress login-related query arguments.
     *
     * @return array<string, string|string[]>
     */
    public function toArray(): array
    {
        $args = [];

        if (null !== $this->login) {
            $args['login'] = $this->login;
        }

        if ([] !== $this->loginIn) {
            $args['login__in'] = $this->loginIn;
        }

        if ([] !== $this->loginNotIn) {
            $args['login__not_in'] = $this->loginNotIn;
        }

        return $args;
    }
}
