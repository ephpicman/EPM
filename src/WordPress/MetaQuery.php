<?php
/**
 * WordPress metadata query builder.
 *
 * @package EPM
 */

namespace EPM\\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * Fluent builder for the meta_query argument accepted by WordPress query APIs.
 *
 * The builder only creates a meta-query array; it does not execute a query.
 * Conditions are translated to the structure expected by WP_Meta_Query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_meta_query/
 */
class MetaQuery {

	/**
	 * Metadata conditions in insertion order.
	 *
	 * Each item contains a boolean operator and either a metadata clause or
	 * a nested group. Keeping the boolean on each item allows mixed AND/OR
	 * expressions to be compiled into WordPress's nested relation format.
	 *
	 * @var array<int, array{boolean: string, query: array<string|int, mixed>}>
	 */
	private array $query = array();

	/**
	 * Create an empty metadata-query builder.
	 */
	public function __construct() {}

	/**
	 * Create a new empty metadata-query builder.
	 *
	 * @return static
	 */
	public static function make(): self {
		return new self();
	}

	/**
	 * Add a metadata condition joined with AND.
	 *
	 * Usage:
	 * - where( 'age', '>=', 18, 'NUMERIC' )
	 * - where( 'status', 'active' ) // defaults to equality
	 *
	 * The operator may be any operator supported by WP_Meta_Query.
	 *
	 * @param string $key      Metadata key.
	 * @param mixed  $operator Comparison operator, or the value when only two
	 *                          arguments are supplied.
	 * @param mixed  $value    Value to compare.
	 * @param string $type     WordPress metadata value type.
	 * @return $this
	 */
	public function where( string $key, $operator, $value = null, string $type = 'CHAR' ): self {
		if ( 2 === func_num_args() ) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->addClause(
			array(
				'key'     => $key,
				'value'   => $value,
				'compare' => $operator,
				'type'    => $type,
			),
			'AND'
		);
	}

	/**
	 * Add a metadata condition joined with OR.
	 *
	 * @param string $key      Metadata key.
	 * @param mixed  $operator Comparison operator, or the value when only two
	 *                          arguments are supplied.
	 * @param mixed  $value    Value to compare.
	 * @param string $type     WordPress metadata value type.
	 * @return $this
	 */
	public function orWhere( string $key, $operator, $value = null, string $type = 'CHAR' ): self {
		if ( 2 === func_num_args() ) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->addClause(
			array(
				'key'     => $key,
				'value'   => $value,
				'compare' => $operator,
				'type'    => $type,
			),
			'OR'
		);
	}

	/**
	 * Add an IN metadata condition.
	 *
	 * @param string   $key   Metadata key.
	 * @param array    $values Values to match.
	 * @param string   $type  WordPress metadata value type.
	 * @return $this
	 */
	public function whereIn( string $key, array $values, string $type = 'CHAR' ): self {
		return $this->where( $key, 'IN', $values, $type );
	}

	/**
	 * Add a NOT IN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Values to exclude.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereNotIn( string $key, array $values, string $type = 'CHAR' ): self {
		return $this->where( $key, 'NOT IN', $values, $type );
	}

	/**
	 * Add a BETWEEN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Two boundary values.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereBetween( string $key, array $values, string $type = 'CHAR' ): self {
		return $this->where( $key, 'BETWEEN', $values, $type );
	}

	/**
	 * Add a NOT BETWEEN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Two boundary values.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereNotBetween( string $key, array $values, string $type = 'CHAR' ): self {
		return $this->where( $key, 'NOT BETWEEN', $values, $type );
	}

	/**
	 * Add a LIKE metadata condition.
	 *
	 * @param string $key   Metadata key.
	 * @param string $value Value or pattern to search for.
	 * @return $this
	 */
	public function whereLike( string $key, string $value ): self {
		return $this->where( $key, 'LIKE', $value );
	}

	/**
	 * Add a NOT LIKE metadata condition.
	 *
	 * @param string $key   Metadata key.
	 * @param string $value Value or pattern to exclude.
	 * @return $this
	 */
	public function whereNotLike( string $key, string $value ): self {
		return $this->where( $key, 'NOT LIKE', $value );
	}

	/**
	 * Add a REGEXP metadata condition.
	 *
	 * @param string $key     Metadata key.
	 * @param string $pattern Regular expression.
	 * @return $this
	 */
	public function whereRegexp( string $key, string $pattern ): self {
		return $this->where( $key, 'REGEXP', $pattern );
	}

	/**
	 * Add a NOT REGEXP metadata condition.
	 *
	 * @param string $key     Metadata key.
	 * @param string $pattern Regular expression.
	 * @return $this
	 */
	public function whereNotRegexp( string $key, string $pattern ): self {
		return $this->where( $key, 'NOT REGEXP', $pattern );
	}

	/**
	 * Match records where the specified metadata key exists.
	 *
	 * @param string $key Metadata key.
	 * @return $this
	 */
	public function whereExists( string $key ): self {
		return $this->addClause(
			array(
				'key'     => $key,
				'compare' => 'EXISTS',
			),
			'AND'
		);
	}

	/**
	 * Match records where the specified metadata key does not exist.
	 *
	 * @param string $key Metadata key.
	 * @return $this
	 */
	public function whereNotExists( string $key ): self {
		return $this->addClause(
			array(
				'key'     => $key,
				'compare' => 'NOT EXISTS',
			),
			'AND'
		);
	}

	/**
	 * Match records where the metadata key itself satisfies a comparison.
	 *
	 * This uses WP_Meta_Query's compare_key and type_key clause arguments.
	 * The clause checks for the existence of a matching key.
	 *
	 * @param string $key        Metadata key or key pattern.
	 * @param string $comparison Key comparison operator.
	 * @param string $type       Key type, where supported by WordPress.
	 * @return $this
	 */
	public function whereMetaKey( string $key, string $comparison = '=', string $type = '' ): self {
		$clause = array(
			'key'         => $key,
			'compare'     => 'EXISTS',
			'compare_key' => $comparison,
		);

		if ( '' !== $type ) {
			$clause['type_key'] = $type;
		}

		return $this->addClause( $clause, 'AND' );
	}

	/**
	 * Add a nested condition group.
	 *
	 * The callback receives a fresh MetaQuery instance. Its conditions are
	 * grouped using the supplied relation before being added to this builder.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @param string   $relation Group relation: AND or OR.
	 * @return $this
	 */
	public function whereGroup( callable $callback, string $relation = 'AND' ): self {
		return $this->addGroup( $callback, $relation, 'AND' );
	}

	/**
	 * Add a nested condition group joined to the current expression with OR.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @param string   $relation Group relation: AND or OR.
	 * @return $this
	 */
	public function orWhereGroup( callable $callback, string $relation = 'AND' ): self {
		return $this->addGroup( $callback, $relation, 'OR' );
	}

	/**
	 * Add a group whose conditions are joined with OR, joined to this
	 * expression with AND.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @return $this
	 */
	public function whereAny( callable $callback ): self {
		return $this->whereGroup( $callback, 'OR' );
	}

	/**
	 * Add a group whose conditions are joined with AND, joined to this
	 * expression with AND.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @return $this
	 */
	public function whereAll( callable $callback ): self {
		return $this->whereGroup( $callback, 'AND' );
	}

	/**
	 * Add a complete WordPress metadata clause.
	 *
	 * This escape hatch supports all WP_Meta_Query clause arguments, including
	 * compare_key and type_key, and helps preserve compatibility with future
	 * WordPress additions. The clause is passed through without normalization.
	 *
	 * @param array<string, mixed> $clause WordPress metadata clause.
	 * @param string               $boolean Boolean joining this clause: AND/OR.
	 * @return $this
	 */
	public function whereClause( array $clause, string $boolean = 'AND' ): self {
		return $this->addClause( $clause, $boolean );
	}

	/**
	 * Add a clause or nested group to the internal expression.
	 *
	 * @param array<string|int, mixed> $query   Clause or nested group.
	 * @param string                  $boolean Boolean joining this item.
	 * @return $this
	 */
	private function addClause( array $query, string $boolean ): self {
		$this->query[] = array(
			'boolean' => 'OR' === strtoupper( $boolean ) ? 'OR' : 'AND',
			'query'   => $query,
		);

		return $this;
	}

	/**
	 * Build a nested group using a child builder.
	 *
	 * @param callable $callback Callback receiving the child builder.
	 * @param string   $relation Relation within the group.
	 * @param string   $boolean  Relation between this group and its siblings.
	 * @return $this
	 */
	private function addGroup( callable $callback, string $relation, string $boolean ): self {
		$nested = new self();
		$callback( $nested );

		$conditions = $nested->toArray();

		if ( empty( $conditions ) ) {
			return $this;
		}

		$relation = 'OR' === strtoupper( $relation ) ? 'OR' : 'AND';

		if ( isset( $conditions['relation'] ) ) {
			$conditions['relation'] = $relation;
		} else {
			$conditions = array(
				'relation' => $relation,
				$conditions,
			);
		}

		return $this->addClause( $conditions, $boolean );
	}

	/**
	 * Convert the expression into the array format accepted as meta_query.
	 *
	 * Mixed boolean conditions are compiled into nested groups. AND binds
	 * more tightly than OR, matching normal SQL boolean-expression semantics.
	 *
	 * @return array<string|int, mixed>
	 */
	public function toArray(): array {
		if ( empty( $this->query ) ) {
			return array();
		}

		$groups       = array();
		$current_group = array();

		foreach ( $this->query as $index => $item ) {
			if ( $index > 0 && 'OR' === $item['boolean'] ) {
				$groups[]      = $this->compileAndGroup( $current_group );
				$current_group = array();
			}

			$current_group[] = $item['query'];
		}

		if ( ! empty( $current_group ) ) {
			$groups[] = $this->compileAndGroup( $current_group );
		}

		if ( 1 === count( $groups ) ) {
			$compiled = $groups[0];

			if ( isset( $compiled['relation'] ) ) {
				return $compiled;
			}

			return array(
				'relation' => 'AND',
				$compiled,
			);
		}

		return array_merge(
			array( 'relation' => 'OR' ),
			$groups
		);
	}

	/**
	 * Compile an AND-connected sequence into a clause or relation group.
	 *
	 * @param array<int, array<string|int, mixed>> $items Conditions in group.
	 * @return array<string|int, mixed>
	 */
	private function compileAndGroup( array $items ): array {
		if ( 1 === count( $items ) ) {
			return $items[0];
		}

		return array_merge(
			array( 'relation' => 'AND' ),
			$items
		);
	}
}
