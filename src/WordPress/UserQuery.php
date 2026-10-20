<?php
/**
 * WordPress user query builder.
 *
 * @package EPM
 */

namespace EPM\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent wrapper around WordPress's WP_User_Query.
 *
 * This class only builds and executes user queries. It does not represent
 * individual users and is independent of EPM\Models\UsersModel.
 */
class UserQuery {

	/**
	 * Arguments passed to WP_User_Query.
	 *
	 * @var array<string, mixed>
	 */
	private array $query_args = array(
		'fields'      => 'all',
		'count_total' => true,
	);

	/**
	 * Create a user query.
	 *
	 * @param array<string, mixed> $args Initial WP_User_Query arguments.
	 */
	public function __construct( array $args = array() ) {
		$this->query_args = array_merge( $this->query_args, $args );
	}

	/**
	 * Create a new query builder.
	 *
	 * @param array<string, mixed> $args Initial WP_User_Query arguments.
	 * @return static
	 */
	public static function make( array $args = array() ): self {
		return new self( $args );
	}

	/**
	 * Set or replace one WP_User_Query argument.
	 *
	 * This escape hatch allows callers to use any argument supported by
	 * WP_User_Query without waiting for a dedicated convenience method.
	 *
	 * @param string $key Argument name.
	 * @param mixed  $value Argument value.
	 * @return $this
	 */
	public function set( string $key, $value ): self {
		$this->query_args[ $key ] = $value;

		return $this;
	}

	/**
	 * Merge additional WP_User_Query arguments.
	 *
	 * @param array<string, mixed> $args Query arguments.
	 * @return $this
	 */
	public function args( array $args ): self {
		$this->query_args = array_merge( $this->query_args, $args );

		return $this;
	}

	/**
	 * Filter users by role.
	 *
	 * @param string|string[] $roles Role or roles.
	 * @return $this
	 */
	public function role( $roles ): self {
		return $this->set( 'role', $roles );
	}

	/**
	 * Filter users by one or more roles.
	 *
	 * @param string[] $roles Roles to include.
	 * @return $this
	 */
	public function roles( array $roles ): self {
		return $this->set( 'role__in', $roles );
	}

	/**
	 * Filter users by IDs.
	 *
	 * @param int[] $ids User IDs to include.
	 * @return $this
	 */
	public function include( array $ids ): self {
		return $this->set( 'include', array_map( 'absint', $ids ) );
	}

	/**
	 * Exclude users by IDs.
	 *
	 * @param int[] $ids User IDs to exclude.
	 * @return $this
	 */
	public function exclude( array $ids ): self {
		return $this->set( 'exclude', array_map( 'absint', $ids ) );
	}

	/**
	 * Search supported user fields.
	 *
	 * WordPress interprets this as a search term and supports partial matching.
	 *
	 * @param string $term Search term.
	 * @param string[] $columns Optional columns to search.
	 * @return $this
	 */
	public function search( string $term, array $columns = array() ): self {
		$this->query_args['search'] = $term;

		if ( ! empty( $columns ) ) {
			$this->query_args['search_columns'] = $columns;
		}

		return $this;
	}

	/**
	 * Add a user-meta condition.
	 *
	 * @param string       $key     Meta key.
	 * @param mixed        $value   Meta value.
	 * @param string       $compare Comparison operator.
	 * @param string       $type    Meta value type.
	 * @param string|null  $compare_key Optional comparison operator for the meta key.
	 * @return $this
	 */
	public function whereMeta(
		string $key,
		$value,
		string $compare = '=',
		string $type = 'CHAR',
		?string $compare_key = null
	): self {
		$clause = array(
			'key'     => $key,
			'value'   => $value,
			'compare' => $compare,
			'type'    => $type,
		);

		if ( null !== $compare_key ) {
			$clause['compare_key'] = $compare_key;
		}

		if ( ! isset( $this->query_args['meta_query'] ) || ! is_array( $this->query_args['meta_query'] ) ) {
			$this->query_args['meta_query'] = array();
		}

		$this->query_args['meta_query'][] = $clause;

		return $this;
	}

	/**
	 * Set a complete meta query, including AND/OR relations.
	 *
	 * @param array<string|int, mixed> $meta_query WP_Meta_Query-compatible clauses.
	 * @return $this
	 */
	public function metaQuery( array $meta_query ): self {
		$this->query_args['meta_query'] = $meta_query;

		return $this;
	}

	/**
	 * Set ordering.
	 *
	 * @param string $field WP_User_Query orderby value.
	 * @param string $direction ASC or DESC.
	 * @return $this
	 */
	public function orderBy( string $field, string $direction = 'ASC' ): self {
		$this->query_args['orderby'] = $field;
		$this->query_args['order']   = strtoupper( $direction ) === 'DESC' ? 'DESC' : 'ASC';

		return $this;
	}

	/**
	 * Set the maximum number of users returned.
	 *
	 * @param int $number Maximum number of users. Use -1 for all users.
	 * @return $this
	 */
	public function limit( int $number ): self {
		return $this->set( 'number', $number );
	}

	/**
	 * Set the result offset.
	 *
	 * @param int $offset Number of users to skip.
	 * @return $this
	 */
	public function offset( int $offset ): self {
		return $this->set( 'offset', max( 0, $offset ) );
	}

	/**
	 * Set the result format supported by WP_User_Query.
	 *
	 * Common values are 'all', 'all_with_meta', 'ID', and 'id=>parent'.
	 *
	 * @param string $fields Result format.
	 * @return $this
	 */
	public function fields( string $fields ): self {
		return $this->set( 'fields', $fields );
	}

	/**
	 * Execute the query and return its results.
	 *
	 * @return array<int|string, \WP_User|int|string>
	 */
	public function get(): array {
		$query = new \WP_User_Query( $this->query_args );
		$results = $query->get_results();

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Execute the query and return the first result, if any.
	 *
	 * @return \WP_User|int|string|null
	 */
	public function first() {
		$args            = $this->query_args;
		$args['number']  = 1;
		$query           = new \WP_User_Query( $args );
		$results         = $query->get_results();

		if ( ! is_array( $results ) || empty( $results ) ) {
			return null;
		}

		return reset( $results );
	}

	/**
	 * Execute the query and return the total number of matching users.
	 *
	 * The total is not limited by the current number/offset pagination.
	 *
	 * @return int
	 */
	public function count(): int {
		$args                = $this->query_args;
		$args['fields']      = 'ID';
		$args['number']      = 1;
		$args['offset']      = 0;
		$args['count_total'] = true;

		$query = new \WP_User_Query( $args );

		return (int) $query->get_total();
	}

	/**
	 * Return the underlying WP_User_Query instance.
	 *
	 * Executes the query.
	 *
	 * @return \WP_User_Query
	 */
	public function run(): \WP_User_Query {
		return new \WP_User_Query( $this->query_args );
	}

	/**
	 * Retrieve the arguments that will be passed to WP_User_Query.
	 *
	 * @return array<string, mixed>
	 */
	public function getArgs(): array {
		return $this->query_args;
	}
}
