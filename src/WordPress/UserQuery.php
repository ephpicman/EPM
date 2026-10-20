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

	/** @var string|array Role requirement(s). */
	private $role = array();

	/** @var string[] Roles accepted by role__in. */
	private array $roleIn = array();

	/** @var string[] Roles excluded by role__not_in. */
	private array $roleNotIn = array();

	/** @var int[] Included user IDs. */
	private array $include = array();

	/** @var int[] Excluded user IDs. */
	private array $exclude = array();

	/** @var string User login filter. */
	private string $login = '';

	/** @var string[] Included user logins. */
	private array $loginIn = array();

	/** @var string[] Excluded user logins. */
	private array $loginNotIn = array();

	/** @var string User nicename filter. */
	private string $nicename = '';

	/** @var string[] Included nicenames. */
	private array $nicenameIn = array();

	/** @var string[] Excluded nicenames. */
	private array $nicenameNotIn = array();

	/** @var string Search term. */
	private string $search = '';

	/** @var string[] Search columns. */
	private array $searchColumns = array();

	/** @var string Order-by field. */
	private string $orderBy = '';

	/** @var string Sort direction. */
	private string $order = 'ASC';

	/** @var int Maximum result count. */
	private int $number = -1;

	/** @var int Result offset. */
	private int $offset = 0;

	/** @var mixed Selected result fields. */
	private $fields = 'all';

	/** @var bool Whether WordPress should calculate the total result count. */
	private bool $countTotal = false;

	/** @var MetaQuery Builder for user metadata conditions. */
	private MetaQuery $metaQuery;
	/**
	 * Create an empty user-query argument builder.
	 *
	 * No arguments are accepted. Configure the query using the fluent
	 * methods, then retrieve the resulting arguments with getArgs().
	 */
	public function __construct() {
		$this->metaQuery = MetaQuery::make();
	}

	/**
	 * Create a new empty argument builder.
	 *
	 * @return static
	 */
	public static function make(): self {
		return new self();
	}

	/**
	 * Filter users by one role.
	 *
	 * Replaces any existing "role" argument. This is equivalent to calling
	 * byRoles() with a one-item array.
	 *
	 * @param string $role WordPress role slug.
	 * @return $this
	 */
	public function byRole( string $role ): self {
		$this->role = $role;

		return $this;
	}

	/**
	 * Require users to have all specified roles.
	 *
	 * Replaces any existing "role" argument. WordPress interprets an array
	 * supplied to "role" as roles the user must all have.
	 *
	 * @param string[] $roles WordPress role slugs.
	 * @return $this
	 */
	public function byRoles( array $roles ): self {
		$this->role = $roles;

		return $this;
	}

	/**
	 * Add role requirements without replacing existing role requirements.
	 *
	 * Roles are merged into the "role" argument and duplicate values are
	 * removed. WordPress requires users to have every role in this argument.
	 * If a "role" argument already exists, its current role or roles are
	 * preserved and the supplied roles are added.
	 *
	 * This method adds an AND-style requirement; it does not append values
	 * to "role__in". Use inRoles() when any one of several roles is enough.
	 *
	 * @param string[] $roles Additional role slugs that users must have.
	 * @return $this
	 */
	public function andInRoles( array $roles ): self {
		$current = $this->role ?? array();
		$current = is_array( $current ) ? $current : array( $current );

		$this->role = array_values(
			array_unique(
				array_merge( $current, $roles )
			)
		);

		return $this;
	}

	/**
	 * Filter users who have at least one of the specified roles.
	 *
	 * Replaces any existing "role__in" argument. WordPress treats this as an
	 * inclusion list: a user may match any one of the supplied roles.
	 *
	 * @param string[] $roles Role slugs; matching any one role is sufficient.
	 * @return $this
	 */
	public function inRoles( array $roles ): self {
		$this->roleIn = $roles;

		return $this;
	}

	/**
	 * Exclude users with one role.
	 *
	 * Replaces any existing "role__not_in" argument.
	 *
	 * @param string $role Role slug to exclude.
	 * @return $this
	 */
	public function avoidRole( string $role ): self {
		$this->roleNotIn = array( $role );

		return $this;
	}

	/**
	 * Exclude users with any of the specified roles.
	 *
	 * Replaces any existing "role__not_in" argument.
	 *
	 * @param string[] $roles Role slugs to exclude.
	 * @return $this
	 */
	public function avoidRoles( array $roles ): self {
		$this->roleNotIn = $roles;

		return $this;
	}

	/**
	 * Filter users by one user ID.
	 *
	 * Replaces any existing "include" argument.
	 *
	 * @param int $id User ID.
	 * @return $this
	 */
	public function byId( int $id ): self {
		$this->include = array( absint( $id ) );

		return $this;
	}

	/**
	 * Filter users by a list of user IDs.
	 *
	 * Replaces any existing "include" argument.
	 *
	 * @param int[] $ids User IDs.
	 * @return $this
	 */
	public function byIds( array $ids ): self {
		$this->include = array_map( 'absint', $ids );

		return $this;
	}

	/**
	 * Exclude one user ID.
	 *
	 * Replaces any existing "exclude" argument.
	 *
	 * @param int $id User ID to exclude.
	 * @return $this
	 */
	public function avoidId( int $id ): self {
		$this->exclude = array( absint( $id ) );

		return $this;
	}

	/**
	 * Exclude a list of user IDs.
	 *
	 * Replaces any existing "exclude" argument.
	 *
	 * @param int[] $ids User IDs to exclude.
	 * @return $this
	 */
	public function avoidIds( array $ids ): self {
		$this->exclude = array_map( 'absint', $ids );

		return $this;
	}

	/**
	 * Filter users by an exact user login.
	 *
	 * Replaces any existing "login" argument.
	 *
	 * @param string $login User login.
	 * @return $this
	 */
	public function byLogin( string $login ): self {
		$this->login = $login;

		return $this;
	}

	/**
	 * Filter users by one of the specified user logins.
	 *
	 * Replaces any existing "login__in" argument.
	 *
	 * @param string[] $logins User logins.
	 * @return $this
	 */
	public function byLogins( array $logins ): self {
		$this->loginIn = $logins;

		return $this;
	}

	/**
	 * Exclude one user login.
	 *
	 * Replaces any existing "login__not_in" argument.
	 *
	 * @param string $login User login to exclude.
	 * @return $this
	 */
	public function avoidLogin( string $login ): self {
		$this->loginNotIn = array( $login );

		return $this;
	}

	/**
	 * Exclude the specified user logins.
	 *
	 * Replaces any existing "login__not_in" argument.
	 *
	 * @param string[] $logins User logins to exclude.
	 * @return $this
	 */
	public function avoidLogins( array $logins ): self {
		$this->loginNotIn = $logins;

		return $this;
	}

	/**
	 * Filter users by an exact nicename.
	 *
	 * Replaces any existing "nicename" argument.
	 *
	 * @param string $nicename User nicename.
	 * @return $this
	 */
	public function byNicename( string $nicename ): self {
		$this->nicename = $nicename;

		return $this;
	}

	/**
	 * Filter users by one of the specified nicenames.
	 *
	 * Replaces any existing "nicename__in" argument.
	 *
	 * @param string[] $nicenames User nicenames.
	 * @return $this
	 */
	public function byNicenames( array $nicenames ): self {
		$this->nicenameIn = $nicenames;

		return $this;
	}

	/**
	 * Exclude one nicename.
	 *
	 * Replaces any existing "nicename__not_in" argument.
	 *
	 * @param string $nicename User nicename to exclude.
	 * @return $this
	 */
	public function avoidNicename( string $nicename ): self {
		$this->nicenameNotIn = array( $nicename );

		return $this;
	}

	/**
	 * Exclude the specified nicenames.
	 *
	 * Replaces any existing "nicename__not_in" argument.
	 *
	 * @param string[] $nicenames User nicenames to exclude.
	 * @return $this
	 */
	public function avoidNicenames( array $nicenames ): self {
		$this->nicenameNotIn = $nicenames;

		return $this;
	}

	/**
	 * Search users by their login column.
	 *
	 * Sets the native WP_User_Query search term and restricts search_columns
	 * to user_login. Replaces any previously configured search term and
	 * search columns. This searches a native users-table column, not user meta.
	 *
	 * @param string $term Search term.
	 * @return $this
	 */
	public function searchByLogin( string $term ): self {
		return $this->byColumn( $term, 'user_login' );
	}

	/**
	 * Search users by their email column.
	 *
	 * @param string $term Search term.
	 * @return $this
	 */
	public function byEmail( string $term ): self {
		return $this->byColumn( $term, 'user_email' );
	}

	/**
	 * Search users by their URL column.
	 *
	 * @param string $term Search term.
	 * @return $this
	 */
	public function byUrl( string $term ): self {
		return $this->byColumn( $term, 'user_url' );
	}

	/**
	 * Search users by their nicename column.
	 *
	 * WordPress calls this field "user_nicename"; it is not the user's
	 * display name and is not a user-meta field.
	 *
	 * @param string $term Search term.
	 * @return $this
	 */
	public function searchByNicename( string $term ): self {
		return $this->byColumn( $term, 'user_nicename' );
	}

	/**
	 * Search users by their display-name column.
	 *
	 * @param string $term Search term.
	 * @return $this
	 */
	public function byDisplayName( string $term ): self {
		return $this->byColumn( $term, 'display_name' );
	}

	/**
	 * Set the native WordPress search term and its target column.
	 *
	 * This is an internal helper for the by*() methods. Each call replaces
	 * the existing search term and search_columns setting.
	 *
	 * @param string $term   Search term.
	 * @param string $column Native WP_User_Query search column.
	 * @return $this
	 */
	private function byColumn( string $term, string $column ): self {
		$this->search         = $term;
		$this->searchColumns = array( $column );

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
		$query = new \WP_User_Query( $this->toArray() );

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

		if ( '' !== $this->role && array() !== $this->role ) {
			$args['role'] = $this->role;
		}
		if ( ! empty( $this->roleIn ) ) {
			$args['role__in'] = $this->roleIn;
		}
		if ( ! empty( $this->roleNotIn ) ) {
			$args['role__not_in'] = $this->roleNotIn;
		}
		if ( ! empty( $this->include ) ) {
			$args['include'] = $this->include;
		}
		if ( ! empty( $this->exclude ) ) {
			$args['exclude'] = $this->exclude;
		}
		if ( '' !== $this->login ) {
			$args['login'] = $this->login;
		}
		if ( ! empty( $this->loginIn ) ) {
			$args['login__in'] = $this->loginIn;
		}
		if ( ! empty( $this->loginNotIn ) ) {
			$args['login__not_in'] = $this->loginNotIn;
		}
		if ( '' !== $this->nicename ) {
			$args['nicename'] = $this->nicename;
		}
		if ( ! empty( $this->nicenameIn ) ) {
			$args['nicename__in'] = $this->nicenameIn;
		}
		if ( ! empty( $this->nicenameNotIn ) ) {
			$args['nicename__not_in'] = $this->nicenameNotIn;
		}
		if ( '' !== $this->search ) {
			$args['search'] = $this->search;
		}
		if ( ! empty( $this->searchColumns ) ) {
			$args['search_columns'] = $this->searchColumns;
		}
		if ( '' !== $this->orderBy ) {
			$args['orderby'] = $this->orderBy;
		}

		$args['order']       = $this->order;
		$args['number']      = $this->number;
		$args['offset']      = $this->offset;
		$args['fields']      = $this->fields;
		$args['count_total'] = $this->countTotal;

		$metaQuery = $this->metaQuery->toArray();
		if ( ! empty( $metaQuery ) ) {
			$args['meta_query'] = $metaQuery;
		}

		return $args;
	}
}