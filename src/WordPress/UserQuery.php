<?php
/**
 * WordPress user query argument builder.
 *
 * @package EPM
 */

namespace EPM\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent builder for arguments accepted by WP_User_Query.
 *
 * This class only builds the query-argument array. It does not execute a
 * query, return users, or represent a user entity. It is independent of
 * EPM\Models\UsersModel.
 *
 * Each regular setter replaces the value previously assigned to its
 * corresponding argument key. Methods explicitly prefixed with "and" add
 * constraints instead of replacing existing constraints.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */
class UserQuery {

	/**
	 * Native columns available in the WordPress {$wpdb->users} table.
	 *
	 * These are database column names, not all properties exposed by WP_User
	 * and not user-meta keys.
	 *
	 * @var string[]
	 */
	public const USER_COLUMNS = array(
		'ID',
		'user_login',
		'user_pass',
		'user_nicename',
		'user_email',
		'user_url',
		'user_registered',
		'user_activation_key',
		'user_status',
		'display_name',
	);

	private RoleQuery $roleQuery;
	private IncludeExcludeQuery $includeExcludeQuery;
	private LoginQuery $loginQuery;
	private NameQuery $nameQuery;
	private SearchQuery $searchQuery;

	/** @var string Order-by field. */
	private ?string $orderBy = null;
	/** @var string Sort direction. */
	private ?string $order = null;
	/** @var int Maximum result count. */
	private ?int $number = null;
	/** @var int Result offset. */
	private ?int $offset = null;
	/** @var mixed Selected result fields. */
	private $fields = null;
	/** @var bool Whether WordPress should calculate the total result count. */
	private ?bool $countTotal = null;

	/** @var MetaQuery Builder for user metadata conditions. */
	private MetaQuery $metaQuery;
	/**
	 * Create a user-query builder from existing WordPress query arguments.
	 *
	 * @param array<string, mixed> $queryArray Existing WordPress query arguments.
	 */
	public function __construct(array $queryArray = []) {
		$this->roleQuery = RoleQuery::make($queryArray);
		$this->includeExcludeQuery = IncludeExcludeQuery::make($queryArray);
		$this->loginQuery = LoginQuery::make($queryArray);
		$this->nameQuery = NameQuery::make($queryArray);
		$this->searchQuery = SearchQuery::make($queryArray);
		$this->metaQuery = MetaQuery::make();

		if (isset($queryArray['orderby'])) { $this->orderBy = $queryArray['orderby']; }
		if (isset($queryArray['order'])) { $this->order = $queryArray['order']; }
		if (isset($queryArray['number'])) { $this->number = (int) $queryArray['number']; }
		if (isset($queryArray['offset'])) { $this->offset = (int) $queryArray['offset']; }
		if (array_key_exists('fields', $queryArray)) { $this->fields = $queryArray['fields']; }
		if (isset($queryArray['count_total'])) { $this->countTotal = (bool) $queryArray['count_total']; }
	}

	/**
	 * Create a new empty argument builder.
	 *
	 * @return static
	 */
	public static function make(array $queryArray = []): self {
		return new self($queryArray);
	}

	/** Filter users by one role. @return $this */
	public function byRole(string $role): self { $this->roleQuery->byRole($role); return $this; }
	/** Require all specified roles. @return $this */
	public function byRoles(array $roles): self { $this->roleQuery->byRoles($roles); return $this; }
	/** Add AND-style role requirements. @return $this */
	public function andInRoles(array $roles): self { $this->roleQuery->andInRoles($roles); return $this; }
	/** Match any specified role. @return $this */
	public function inRoles(array $roles): self { $this->roleQuery->inRoles($roles); return $this; }
	/** Exclude one role. @return $this */
	public function avoidRole(string $role): self { $this->roleQuery->avoidRole($role); return $this; }
	/** Exclude specified roles. @return $this */
	public function avoidRoles(array $roles): self { $this->roleQuery->avoidRoles($roles); return $this; }

	/** Include one user ID. @return $this */
	public function byId(int $id): self { $this->includeExcludeQuery->byId($id); return $this; }
	/** Include user IDs. @return $this */
	public function byIds(array $ids): self { $this->includeExcludeQuery->byIds($ids); return $this; }
	/** Exclude one user ID. @return $this */
	public function avoidId(int $id): self { $this->includeExcludeQuery->avoidId($id); return $this; }
	/** Exclude user IDs. @return $this */
	public function avoidIds(array $ids): self { $this->includeExcludeQuery->avoidIds($ids); return $this; }

	/** Filter by exact login. @return $this */
	public function byLogin(string $login): self { $this->loginQuery->byLogin($login); return $this; }
	/** Filter by any specified login. @return $this */
	public function byLogins(array $logins): self { $this->loginQuery->inLogins($logins); return $this; }
	/** Exclude one login. @return $this */
	public function avoidLogin(string $login): self { $this->loginQuery->avoidLogin($login); return $this; }
	/** Exclude specified logins. @return $this */
	public function avoidLogins(array $logins): self { $this->loginQuery->avoidLogins($logins); return $this; }

	/** Filter by exact nicename. @return $this */
	public function byNicename(string $nicename): self { $this->nameQuery->byNicename($nicename); return $this; }
	/** Filter by any specified nicename. @return $this */
	public function byNicenames(array $nicenames): self { $this->nameQuery->inNicenames($nicenames); return $this; }
	/** Exclude one nicename. @return $this */
	public function avoidNicename(string $nicename): self { $this->nameQuery->avoidNicename($nicename); return $this; }
	/** Exclude specified nicenames. @return $this */
	public function avoidNicenames(array $nicenames): self { $this->nameQuery->avoidNicenames($nicenames); return $this; }

	/** Search users by login. @return $this */
	public function searchByLogin(string $term): self { return $this->byColumn($term, 'user_login'); }
	/** Search users by email. @return $this */
	public function byEmail(string $term): self { return $this->byColumn($term, 'user_email'); }
	/** Search users by URL. @return $this */
	public function byUrl(string $term): self { return $this->byColumn($term, 'user_url'); }
	/** Search users by nicename. @return $this */
	public function searchByNicename(string $term): self { return $this->byColumn($term, 'user_nicename'); }
	/** Search users by display name. @return $this */
	public function byDisplayName(string $term): self { return $this->byColumn($term, 'display_name'); }

	/** Set search term and target column. @return $this */
	private function byColumn(string $term, string $column): self {
		$this->searchQuery->byColumn($term, $column);
		return $this;
	}

	/**
	 * Set the result ordering.
	 *
	 * Replaces the "orderby" and "order" arguments. Any direction other than
	 * "DESC", case-insensitively, is normalized to "ASC".
	 *
	 * @param string $field     A WP_User_Query orderby value.
	 * @param string $direction "ASC" or "DESC".
	 * @return $this
	 */
	public function orderBy( string $field, string $direction = 'ASC' ): self {
		$this->orderBy = $field;
		$this->order   = 'DESC' === strtoupper( $direction ) ? 'DESC' : 'ASC';

		return $this;
	}

	/**
	 * Set the maximum number of users to request.
	 *
	 * Replaces the "number" argument. The accepted values follow the
	 * WP_User_Query API.
	 *
	 * @param int $number Maximum number of users; -1 requests no limit.
	 * @return $this
	 */
	public function limit( int $number ): self {
		$this->number = $number;

		return $this;
	}

	/**
	 * Set how many users to skip.
	 *
	 * Replaces the "offset" argument. Negative values are normalized to zero.
	 *
	 * @param int $number Number of users to skip.
	 * @return $this
	 */
	public function offset( int $number ): self {
		$this->offset = max( 0, $number );

		return $this;
	}

	/**
	 * Set which fields WP_User_Query should return.
	 *
	 * Replaces the "fields" argument. Supported values depend on the
	 * WP_User_Query API and may be a field selector or an array of selectors.
	 *
	 * @param string|string[] $fields Requested result fields.
	 * @return $this
	 */
	public function select( $fields ): self {
		$this->fields = $fields;

		return $this;
	}

	/**
	 * Return selected user-meta keys.
	 *
	 * Native user-table columns are excluded. When no selection was specified,
	 * no metadata fields are considered explicitly selected.
	 *
	 * @return string[]
	 */
	public function getSelectedMetaFields(): array {
		if ( null === $this->fields || 'all' === $this->fields || 'ID' === $this->fields ) {
			return array();
		}

		$fields = is_array( $this->fields ) ? $this->fields : array( $this->fields );

		return array_values(
			array_filter(
				$fields,
				static function ( $field ): bool {
					return is_string( $field ) && ! in_array( $field, self::USER_COLUMNS, true );
				}
			)
		);
	}

	/**
	 * Set whether WordPress should calculate the total matching-user count.
	 *
	 * Replaces the "count_total" argument.
	 *
	 * @param bool $count_total Whether to calculate the total count.
	 * @return $this
	 */
	public function countTotal( bool $count_total = true ): self {
		$this->countTotal = $count_total;

		return $this;
	}

	/** Add a metadata condition joined with AND. @return $this */
	public function where( string $key, $operator, $value = null, string $type = 'CHAR' ): self {
		if ( 2 === func_num_args() ) {
			$this->metaQuery->where( $key, $operator );
		} elseif ( 3 === func_num_args() ) {
			$this->metaQuery->where( $key, $operator, $value );
		} else {
			$this->metaQuery->where( $key, $operator, $value, $type );
		}
		return $this;
	}
	/** Add a metadata condition joined with OR. @return $this */
	public function orWhere( string $key, $operator, $value = null, string $type = 'CHAR' ): self {
		if ( 2 === func_num_args() ) {
			$this->metaQuery->orWhere( $key, $operator );
		} elseif ( 3 === func_num_args() ) {
			$this->metaQuery->orWhere( $key, $operator, $value );
		} else {
			$this->metaQuery->orWhere( $key, $operator, $value, $type );
		}
		return $this;
	}
	/** @return $this */
	public function whereIn( string $key, array $values, string $type = 'CHAR' ): self { $this->metaQuery->whereIn( $key, $values, $type ); return $this; }
	/** @return $this */
	public function whereNotIn( string $key, array $values, string $type = 'CHAR' ): self { $this->metaQuery->whereNotIn( $key, $values, $type ); return $this; }
	/** @return $this */
	public function whereBetween( string $key, array $values, string $type = 'CHAR' ): self { $this->metaQuery->whereBetween( $key, $values, $type ); return $this; }
	/** @return $this */
	public function whereNotBetween( string $key, array $values, string $type = 'CHAR' ): self { $this->metaQuery->whereNotBetween( $key, $values, $type ); return $this; }
	/** @return $this */
	public function whereLike( string $key, string $value ): self { $this->metaQuery->whereLike( $key, $value ); return $this; }
	/** @return $this */
	public function whereNotLike( string $key, string $value ): self { $this->metaQuery->whereNotLike( $key, $value ); return $this; }
	/** @return $this */
	public function whereRegexp( string $key, string $pattern ): self { $this->metaQuery->whereRegexp( $key, $pattern ); return $this; }
	/** @return $this */
	public function whereNotRegexp( string $key, string $pattern ): self { $this->metaQuery->whereNotRegexp( $key, $pattern ); return $this; }
	/** @return $this */
	public function whereExists( string $key ): self { $this->metaQuery->whereExists( $key ); return $this; }
	/** @return $this */
	public function whereNotExists( string $key ): self { $this->metaQuery->whereNotExists( $key ); return $this; }
	/** @return $this */
	public function whereMetaKey( string $key, string $comparison = '=', string $type = '' ): self { $this->metaQuery->whereMetaKey( $key, $comparison, $type ); return $this; }
	/** @return $this */
	public function whereGroup( callable $callback, string $relation = 'AND' ): self { $this->metaQuery->whereGroup( $callback, $relation ); return $this; }
	/** @return $this */
	public function orWhereGroup( callable $callback, string $relation = 'AND' ): self { $this->metaQuery->orWhereGroup( $callback, $relation ); return $this; }
	/** @return $this */
	public function whereAny( callable $callback ): self { $this->metaQuery->whereAny( $callback ); return $this; }
	/** @return $this */
	public function whereAll( callable $callback ): self { $this->metaQuery->whereAll( $callback ); return $this; }
	/** @return $this */
	public function whereClause( array $clause, string $boolean = 'AND' ): self { $this->metaQuery->whereClause( $clause, $boolean ); return $this; }

	/**
	 * Execute the query and return its results.
	 *
	 * The current arguments, including metadata conditions, are passed to
	 * WP_User_Query. The returned value follows WordPress' "fields" argument.
	 *
	 * @return array<int|string, mixed>
	 */
	public function get(): array {
		$args = $this->toArray();

		// EPM select() describes model fields, not WP_User_Query result shape.
		if ( null !== $this->fields ) {
			$args['fields'] = 'all';
		}

		$query = new \WP_User_Query( $args );

		return $query->get_results();
	}

	/**
	 * Return the total number of users matching this query.
	 *
	 * This counts all matching users, not only the users in the current page.
	 * WordPress pagination arguments such as "number" and "offset" do not
	 * limit this total.
	 *
	 * @return int
	 */
	public function count(): int {
		$args                = $this->toArray();
		$args['count_total'] = true;

		$query = new \WP_User_Query( $args );

		return (int) $query->get_total();
	}

	/**
	 * Retrieve all WordPress users.
	 *
	 * @return array<int|string, mixed>
	 */
	public static function getAll(): array {
		return self::make()
			->limit( -1 )
			->get();
	}

	/**
	 * Get the arguments built so far.
	 *
	 * Returns the current argument array without executing a query. The
	 * returned array can be passed to WP_User_Query or inspected/modified by
	 * the caller. Calling this method does not change the builder.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		$args = array();

		$args = array_merge(
			$this->roleQuery->toArray(),
			$this->includeExcludeQuery->toArray(),
			$this->loginQuery->toArray(),
			$this->nameQuery->toArray(),
			$this->searchQuery->toArray()
		);

		if ( null !== $this->orderBy ) {
			$args['orderby'] = $this->orderBy;
		}

		if ( null !== $this->order ) {
			$args['order'] = $this->order;
		}
		if ( null !== $this->number ) {
			$args['number'] = $this->number;
		}
		if ( null !== $this->offset ) {
			$args['offset'] = $this->offset;
		}
		// Preserve the selected fields as EPM query state. WP_User_Query's
		// fields argument changes the result type, so execution normalizes it
		// to 'all' in get() when EPM select() is used.
		if ( null !== $this->fields ) {
			$args['fields'] = $this->fields;
		}
		if ( null !== $this->countTotal ) {
			$args['count_total'] = $this->countTotal;
		}

		$metaQuery = $this->metaQuery->toArray();
		if ( ! empty( $metaQuery ) ) {
			$args['meta_query'] = $metaQuery;
		}

		return $args;
	}
}