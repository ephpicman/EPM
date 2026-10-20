<?php
/**
 * WordPress user model.
 *
 * Provides a lightweight, Laravel-inspired API while delegating persistence
 * and querying to WordPress core APIs.
 *
 * @package EPM
 */

namespace EPM\Models;

use EPM\WordPress\UserQuery;

defined( 'ABSPATH' ) || exit;

/**
 * User query and entity wrapper.
 *
 * This class intentionally keeps query-building and entity operations together
 * for now. They can be separated later without changing the public API.
 */
class UsersModel {

	/**
	 * Supported WP_User_Query fields.
	 *
	 * @var array<int, string>
	 */
	private const QUERY_FIELDS = array(
		'ID',
		'user_login',
		'user_nicename',
		'user_email',
		'user_url',
		'user_registered',
		'user_status',
		'display_name',
		'role',
	);

	/**
	 * Query arguments passed to WP_User_Query.
	 *
	 * @var array<string, mixed>
	 */
	private array $query_args = array(
		'number' => -1,
	);

	/**
	 * Whether this instance represents a query builder.
	 *
	 * @var bool
	 */
	private bool $is_query = true;

	/**
	 * The wrapped WordPress user, when this instance represents a user.
	 *
	 * @var \WP_User|null
	 */
	private ?\WP_User $user = null;

	/**
	 * Create a new query or entity wrapper.
	 *
	 * @param \WP_User|null $user Optional WordPress user.
	 */
	private function __construct( ?\WP_User $user = null ) {
		if ( $user instanceof \WP_User ) {
			$this->user     = $user;
			$this->is_query = false;
		}
	}

	/**
	 * Find a user by ID.
	 *
	 * @param int $id User ID.
	 * @return static|null
	 */
	public static function find( int $id ): ?self {
		$user = get_userdata( $id );

		return $user instanceof \WP_User ? new self( $user ) : null;
	}

	/**
	 * Begin a user query with an equality condition.
	 *
	 * Supported fields are listed in QUERY_FIELDS. Use whereMeta() for user
	 * metadata instead of passing arbitrary meta keys to this method.
	 *
	 * @param string $field User query field.
	 * @param mixed  $value Field value.
	 * @return static
	 */
	private function addWhere( string $field, $value ): self {
		if ( ! in_array( $field, self::QUERY_FIELDS, true ) ) {
			_doing_it_wrong(
				__METHOD__,
				'Unsupported user field. Use a supported WP_User_Query field or whereMeta() for metadata.',
				'1.0.0'
			);

			$this->query_args['include'] = array( 0 );

			return $this;
		}

		if ( 'ID' === $field ) {
			$field = 'include';
			$value = array( absint( $value ) );
		} elseif ( 'role' === $field ) {
			$field = 'role';
		}

		if ( 'include' === $field ) {
			$this->query_args['include'] = $value;
		} else {
			$this->query_args[ $field ] = $value;
		}

		return $this;
	}

	/**
	 * Support Laravel-inspired static and chained query syntax.
	 *
	 * @param string $name      Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	public static function __callStatic( string $name, array $arguments ) {
		if ( 'where' === $name && 2 <= count( $arguments ) ) {
			return ( new self() )->addWhere( (string) $arguments[0], $arguments[1] );
		}

		if ( method_exists( UserQuery::class, $name ) ) {
			return UserQuery::make()->$name( ...$arguments );
		}

		throw new \BadMethodCallException( sprintf( 'Undefined static method %s::%s().', self::class, $name ) );
	}

	/**
	 * Support chained where() calls on the query builder.
	 *
	 * @param string $name      Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	public function __call( string $name, array $arguments ) {
		if ( 'where' === $name && 2 <= count( $arguments ) ) {
			return $this->addWhere( (string) $arguments[0], $arguments[1] );
		}

		throw new \BadMethodCallException( sprintf( 'Undefined method %s::%s().', self::class, $name ) );
	}

	/**
	 * Add a user-meta equality condition to the query.
	 *
	 * @param string $key   Metadata key.
	 * @param mixed  $value Metadata value.
	 * @return static
	 */
	public function whereMeta( string $key, $value ): self {
		$meta_query   = $this->query_args['meta_query'] ?? array();
		$meta_query[] = array(
			'key'     => $key,
			'value'   => $value,
			'compare' => '=',
		);

		$this->query_args['meta_query'] = $meta_query;

		return $this;
	}

	/**
	 * Set the maximum number of users returned.
	 *
	 * @param int $number Maximum number of users; -1 means no limit.
	 * @return static
	 */
	public function limit( int $number ): self {
		$this->query_args['number'] = -1 === $number ? -1 : max( 1, $number );

		return $this;
	}

	/**
	 * Set the query offset.
	 *
	 * @param int $offset Number of users to skip.
	 * @return static
	 */
	public function offset( int $offset ): self {
		$this->query_args['offset'] = max( 0, $offset );

		return $this;
	}

	/**
	 * Set query ordering.
	 *
	 * @param string $field Field to order by.
	 * @param string $direction ASC or DESC.
	 * @return static
	 */
	public function orderBy( string $field, string $direction = 'ASC' ): self {
		$allowed = array(
			'ID',
			'user_login',
			'user_nicename',
			'user_email',
			'user_registered',
			'display_name',
			'post_count',
		);

		if ( ! in_array( $field, $allowed, true ) ) {
			_doing_it_wrong(
				__METHOD__,
				'Unsupported user ordering field.',
				'1.0.0'
			);

			return $this;
		}

		$this->query_args['orderby'] = $field;
		$this->query_args['order']   = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';

		return $this;
	}

	/**
	 * Return the first matching user.
	 *
	 * @return static|null
	 */
	public function first(): ?self {
		$this->assert_query();

		$args            = $this->query_args;
		$args['number']  = 1;
		$args['offset']  = 0;
		$args['fields']  = 'all';
		$query           = new \WP_User_Query( $args );
		$users           = $query->get_results();
		$user             = $users[0] ?? null;

		return $user instanceof \WP_User ? new self( $user ) : null;
	}

	/**
	 * Return all matching users as UsersModel instances.
	 *
	 * @return array<int, static>
	 */
	public function get(): array {
		$this->assert_query();

		$args           = $this->query_args;
		$args['fields'] = 'all';
		$query          = new \WP_User_Query( $args );
		$models         = array();

		foreach ( $query->get_results() as $user ) {
			if ( $user instanceof \WP_User ) {
				$models[] = new self( $user );
			}
		}

		return $models;
	}

	/**
	 * Create a user.
	 *
	 * WordPress expects user_pass to be plain text; core hashes it before
	 * storing it. The caller remains responsible for authorization and input
	 * validation appropriate to the application.
	 *
	 * @param array<string, mixed> $data User data accepted by wp_insert_user().
	 * @return static|\WP_Error
	 */
	public static function create( array $data ) {
		$user_id = wp_insert_user( $data );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$model = self::find( (int) $user_id );

		return $model instanceof self
			? $model
			: new \WP_Error( 'epm_user_not_found', 'The user was created but could not be loaded.' );
	}

	/**
	 * Update the wrapped user.
	 *
	 * @param array<string, mixed> $data User data accepted by wp_update_user().
	 * @return bool|\WP_Error
	 */
	public function update( array $data ) {
		$this->assert_entity();

		$data['ID'] = $this->user->ID;
		$result     = wp_update_user( $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$updated_user = get_userdata( $this->user->ID );

		if ( $updated_user instanceof \WP_User ) {
			$this->user = $updated_user;
		}

		return true;
	}

	/**
	 * Delete the wrapped user.
	 *
	 * @param int|null $reassign Optional user ID to receive the deleted user's content.
	 * @return bool|\WP_Error
	 */
	public function delete( ?int $reassign = null ) {
		$this->assert_entity();

		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		return wp_delete_user( $this->user->ID, $reassign );
	}

	/**
	 * Get a user metadata value.
	 *
	 * @param string $key     Metadata key.
	 * @param bool   $single  Return one value when true.
	 * @return mixed
	 */
	public function meta( string $key, bool $single = true ) {
		$this->assert_entity();

		return get_user_meta( $this->user->ID, $key, $single );
	}

	/**
	 * Set a user metadata value.
	 *
	 * @param string $key   Metadata key.
	 * @param mixed  $value Metadata value.
	 * @return int|bool
	 */
	public function setMeta( string $key, $value ) {
		$this->assert_entity();

		return update_user_meta( $this->user->ID, $key, $value );
	}

	/**
	 * Delete a user metadata key.
	 *
	 * @param string $key   Metadata key.
	 * @param mixed  $value Optional value to delete.
	 * @return bool
	 */
	public function deleteMeta( string $key, $value = '' ): bool {
		$this->assert_entity();

		return '' === $value
			? delete_user_meta( $this->user->ID, $key )
			: delete_user_meta( $this->user->ID, $key, $value );
	}

	/**
	 * Get a property from the wrapped WP_User.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function __get( string $name ) {
		$this->assert_entity();

		return $this->user->$name ?? null;
	}

	/**
	 * Check whether the wrapped user has a property.
	 *
	 * @param string $name Property name.
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		return $this->user instanceof \WP_User && isset( $this->user->$name );
	}

	/**
	 * Return the wrapped WP_User instance.
	 *
	 * @return \WP_User
	 */
	public function toWordPressUser(): \WP_User {
		$this->assert_entity();

		return $this->user;
	}

	/**
	 * Ensure this instance is being used as a query builder.
	 *
	 * @return void
	 */
	private function assert_query(): void {
		if ( ! $this->is_query ) {
			throw new \LogicException( 'This UsersModel instance represents a user, not a query.' );
		}
	}

	/**
	 * Ensure this instance represents a user.
	 *
	 * @return void
	 */
	private function assert_entity(): void {
		if ( ! $this->user instanceof \WP_User ) {
			throw new \LogicException( 'This operation requires a loaded user. Call find(), first(), or create() first.' );
		}
	}
}
