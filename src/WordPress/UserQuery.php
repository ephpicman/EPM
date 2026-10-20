<?php
/**
 * WordPress user query builder.
 *
 * @package EPM
 */

namespace EPM\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent query builder that delegates query execution to WP_User_Query.
 *
 * This class builds user-query arguments only. It does not represent a user
 * entity and is independent of EPM\Models\UsersModel.
 */
class UserQuery {

	/**
	 * Arguments passed to WP_User_Query.
	 *
	 * Starts empty; WordPress applies its own defaults when the query runs.
	 *
	 * @var array<string, mixed>
	 */
	private array $args = array();

	/**
	 * Create a new user query builder.
	 */
	public function __construct() {}

	/**
	 * Create a new user query builder.
	 *
	 * @return static
	 */
	public static function make(): self {
		return new self();
	}

	/**
	 * Filter by a single role.
	 *
	 * @param string $role Role slug.
	 * @return $this
	 */
	public function byRole( string $role ): self {
		$this->args['role'] = $role;

		return $this;
	}

	/**
	 * Filter by all of the specified roles.
	 *
	 * WordPress's role argument requires users to have all specified roles.
	 *
	 * @param string[] $roles Role slugs.
	 * @return $this
	 */
	public function byRoles( array $roles ): self {
		$this->args['role'] = $roles;

		return $this;
	}

	/**
	 * Filter by users having at least one of the specified roles.
	 *
	 * @param string[] $roles Role slugs.
	 * @return $this
	 */
	public function inRoles( array $roles ): self {
		$this->args['role__in'] = $roles;

		return $this;
	}

	/**
	 * Exclude users with a specific role.
	 *
	 * @param string $role Role slug.
	 * @return $this
	 */
	public function avoidRole( string $role ): self {
		$this->args['role__not_in'] = array( $role );

		return $this;
	}

	/**
	 * Exclude users with any of the specified roles.
	 *
	 * @param string[] $roles Role slugs.
	 * @return $this
	 */
	public function avoidRoles( array $roles ): self {
		$this->args['role__not_in'] = $roles;

		return $this;
	}

	/**
	 * Filter by a single user ID.
	 *
	 * @param int $id User ID.
	 * @return $this
	 */
	public function byId( int $id ): self {
		$this->args['include'] = array( absint( $id ) );

		return $this;
	}

	/**
	 * Filter by a list of user IDs.
	 *
	 * @param int[] $ids User IDs.
	 * @return $this
	 */
	public function byIds( array $ids ): self {
		$this->args['include'] = array_map( 'absint', $ids );

		return $this;
	}

	/**
	 * Exclude a single user ID.
	 *
	 * @param int $id User ID.
	 * @return $this
	 */
	public function avoidId( int $id ): self {
		$this->args['exclude'] = array( absint( $id ) );

		return $this;
	}

	/**
	 * Exclude a list of user IDs.
	 *
	 * @param int[] $ids User IDs.
	 * @return $this
	 */
	public function avoidIds( array $ids ): self {
		$this->args['exclude'] = array_map( 'absint', $ids );

		return $this;
	}

	/**
	 * Filter by an exact user login.
	 *
	 * @param string $login User login.
	 * @return $this
	 */
	public function byLogin( string $login ): self {
		$this->args['login'] = $login;

		return $this;
	}

	/**
	 * Filter by one of the specified user logins.
	 *
	 * @param string[] $logins User logins.
	 * @return $this
	 */
	public function byLogins( array $logins ): self {
		$this->args['login__in'] = $logins;

		return $this;
	}

	/**
	 * Exclude a user login.
	 *
	 * @param string $login User login.
	 * @return $this
	 */
	public function avoidLogin( string $login ): self {
		$this->args['login__not_in'] = array( $login );

		return $this;
	}

	/**
	 * Exclude the specified user logins.
	 *
	 * @param string[] $logins User logins.
	 * @return $this
	 */
	public function avoidLogins( array $logins ): self {
		$this->args['login__not_in'] = $logins;

		return $this;
	}

	/**
	 * Filter by an exact user nicename.
	 *
	 * @param string $nicename User nicename.
	 * @return $this
	 */
	public function byNicename( string $nicename ): self {
		$this->args['nicename'] = $nicename;

		return $this;
	}

	/**
	 * Filter by one of the specified user nicenames.
	 *
	 * @param string[] $nicenames User nicenames.
	 * @return $this
	 */
	public function byNicenames( array $nicenames ): self {
		$this->args['nicename__in'] = $nicenames;

		return $this;
	}

	/**
	 * Exclude a user nicename.
	 *
	 * @param string $nicename User nicename.
	 * @return $this
	 */
	public function avoidNicename( string $nicename ): self {
		$this->args['nicename__not_in'] = array( $nicename );

		return $this;
	}

	/**
	 * Exclude the specified user nicenames.
	 *
	 * @param string[] $nicenames User nicenames.
	 * @return $this
	 */
	public function avoidNicenames( array $nicenames ): self {
		$this->args['nicename__not_in'] = $nicenames;

		return $this;
	}

	/**
	 * Search user fields using WordPress's search behavior.
	 *
	 * @param string   $term Search term.
	 * @param string[] $columns Optional search columns.
	 * @return $this
	 */
	public function search( string $term, array $columns = array() ): self {
		$this->args['search'] = $term;

		if ( ! empty( $columns ) ) {
			$this->args['search_columns'] = $columns;
		}

		return $this;
	}

	/**
	 * Set the result order.
	 *
	 * @param string $field WP_User_Query orderby value.
	 * @param string $direction ASC or DESC.
	 * @return $this
	 */
	public function orderBy( string $field, string $direction = 'ASC' ): self {
		$this->args['orderby'] = $field;
		$this->args['order']   = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';

		return $this;
	}

	/**
	 * Limit the number of returned users.
	 *
	 * @param int $number Maximum number of users; -1 means no limit.
	 * @return $this
	 */
	public function limit( int $number ): self {
		$this->args['number'] = $number;

		return $this;
	}

	/**
	 * Skip a number of users.
	 *
	 * @param int $number Number of users to skip.
	 * @return $this
	 */
	public function offset( int $number ): self {
		$this->args['offset'] = max( 0, $number );

		return $this;
	}

	/**
	 * Set the requested result fields.
	 *
	 * @param string|string[] $fields WP_User_Query fields value.
	 * @return $this
	 */
	public function fields( $fields ): self {
		$this->args['fields'] = $fields;

		return $this;
	}

	/**
	 * Set whether WordPress should calculate the total result count.
	 *
	 * @param bool $count_total Whether to calculate the total.
	 * @return $this
	 */
	public function countTotal( bool $count_total = true ): self {
		$this->args['count_total'] = $count_total;

		return $this;
	}

	/**
	 * Retrieve the arguments accumulated by this builder.
	 *
	 * @return array<string, mixed>
	 */
	public function getArgs(): array {
		return $this->args;
	}

	/**
	 * Execute the query and return the WP_User_Query instance.
	 *
	 * @return \WP_User_Query
	 */
	public function run(): \WP_User_Query {
		return new \WP_User_Query( $this->args );
	}

	/**
	 * Execute the query and return its results.
	 *
	 * @return array<int|string, \WP_User|int|string>
	 */
	public function get(): array {
		$results = $this->run()->get_results();

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Execute the query and return its first result, if any.
	 *
	 * @return \WP_User|int|string|null
	 */
	public function first() {
		$args           = $this->args;
		$args['number'] = 1;
		$results        = ( new \WP_User_Query( $args ) )->get_results();

		if ( ! is_array( $results ) || empty( $results ) ) {
			return null;
		}

		return reset( $results );
	}

	/**
	 * Execute the query and return the total number of matching users.
	 *
	 * @return int
	 */
	public function count(): int {
		$args                = $this->args;
		$args['fields']      = 'ID';
		$args['number']      = 1;
		$args['offset']      = 0;
		$args['count_total'] = true;

		return (int) ( new \WP_User_Query( $args ) )->get_total();
	}
}
