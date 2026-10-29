<?php
/**
 * WordPress user model.
 *
 * Provides a lightweight API for querying users and wrapping user entities.
 *
 * @package EphpicMan
 */

namespace EphpicMan\Models;

use EphpicMan\WordPress\UserQuery;

defined( 'ABSPATH' ) || exit;

/**
 * User query facade and WordPress user entity wrapper.
 *
 * Query construction is delegated to UserQuery. This class is responsible for
 * exposing that builder through the model API and wrapping WP_User entities.
 */
class UsersModel {

	/** @var UserQuery|null Query builder; null when this instance wraps a user. */
	private ?UserQuery $query = null;

	/** @var \WP_User|null Wrapped WordPress user. */
	private ?\WP_User $user = null;

	/** @var string[] User-meta keys explicitly selected by the query. */
	private array $selectedMetaFields = array();

	/**
	 * Create a query facade or wrap an existing WordPress user.
	 *
	 * @param \WP_User|null $user              Optional WordPress user entity.
	 * @param string[]       $selectedMetaFields Metadata keys selected by the query.
	 */
	private function __construct( ?\WP_User $user = null, array $selectedMetaFields = array() ) {
		if ( $user instanceof \WP_User ) {
			$this->user               = $user;
			$this->selectedMetaFields = $selectedMetaFields;
			return;
		}

		$this->query = UserQuery::make();
	}

	/**
	 * Start building a user query.
	 *
	 * @return static
	 */
	public static function make(): self {
		return new self();
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
	 * Forward static query-builder calls through a new model query facade.
	 *
	 * @param string $name      Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	public static function __callStatic( string $name, array $arguments ) {
		if ( ! method_exists( UserQuery::class, $name ) || ( new \ReflectionMethod( UserQuery::class, $name ) )->isStatic() ) {
			throw new \BadMethodCallException( sprintf( 'Undefined static method %s::%s().', self::class, $name ) );
		}

		return ( new self() )->$name( ...$arguments );
	}

	/**
	 * Forward query-builder calls to UserQuery.
	 *
	 * Fluent builder calls return this facade so subsequent calls remain on
	 * UsersModel. Terminal methods are implemented explicitly below.
	 *
	 * @param string $name      Method name.
	 * @param array  $arguments Method arguments.
	 * @return mixed
	 */
	public function __call( string $name, array $arguments ) {
		$this->assert_query();

		if ( ! method_exists( $this->query, $name ) || ( new \ReflectionMethod( $this->query, $name ) )->isStatic() ) {
			throw new \BadMethodCallException( sprintf( 'Undefined method %s::%s().', self::class, $name ) );
		}

		$result = $this->query->$name( ...$arguments );

		return $result === $this->query ? $this : $result;
	}

	/**
	 * Execute the query and return matching users or selected fields.
	 *
	 * WP_User results are wrapped in UsersModel; other result shapes selected
	 * through UserQuery::select() are passed through unchanged.
	 *
	 * @return array<int|string, mixed>
	 */
	public function get(): array {
		$this->assert_query();

		$results = $this->query->get();
		$models  = array();

		$selectedMetaFields = $this->query->getSelectedMetaFields();

		foreach ( $results as $result ) {
			$models[] = $result instanceof \WP_User
				? new self( $result, $selectedMetaFields )
				: $result;
		}

		return $models;
	}

	/**
	 * Return the first matching user or selected-field result.
	 *
	 * @return mixed
	 */
	public function first() {
		$this->assert_query();

		$args           = $this->query->toArray();
		$args['number'] = 1;
		$args['offset'] = 0;

		if ( null !== ( $args['fields'] ?? null ) ) {
			$args['fields'] = 'all';
		}

		$query  = new \WP_User_Query( $args );
		$result = $query->get_results()[0] ?? null;

		return $result instanceof \WP_User
			? new self( $result, $this->query->getSelectedMetaFields() )
			: $result;
	}

	/**
	 * Count all users matching the query.
	 *
	 * @return int
	 */
	public function count(): int {
		$this->assert_query();

		return $this->query->count();
	}

	/**
	 * Return the current WordPress query arguments without executing them.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$this->assert_query();

		return $this->query->toArray();
	}

	/**
	 * Retrieve all users as UsersModel instances.
	 *
	 * @return array<int|string, mixed>
	 */
	public static function getAll(): array {
		return self::make()->limit( -1 )->get();
	}

	/**
	 * Create a user using the native WordPress API.
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
	 * @param string $key    Metadata key.
	 * @param bool   $single Return one value when true.
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

		if ( in_array( $name, $this->selectedMetaFields, true ) ) {
			return get_user_meta( $this->user->ID, $name, true );
		}

		return $this->user->$name ?? null;
	}

	/**
	 * Check whether the wrapped user has a property.
	 *
	 * @param string $name Property name.
	 * @return bool
	 */
	public function __isset( string $name ): bool {
		if ( ! $this->user instanceof \WP_User ) {
			return false;
		}

		if ( in_array( $name, $this->selectedMetaFields, true ) ) {
			return metadata_exists( 'user', $this->user->ID, $name );
		}

		return isset( $this->user->$name );
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
	 * Ensure this instance represents a query facade.
	 *
	 * @return void
	 */
	private function assert_query(): void {
		if ( ! $this->query instanceof UserQuery ) {
			throw new \LogicException( 'This UsersModel instance represents a user, not a query.' );
		}
	}

	/**
	 * Ensure this instance represents a loaded user.
	 *
	 * @return void
	 */
	private function assert_entity(): void {
		if ( ! $this->user instanceof \WP_User ) {
			throw new \LogicException( 'This operation requires a loaded user. Call find(), first(), or create() first.' );
		}
	}
}
