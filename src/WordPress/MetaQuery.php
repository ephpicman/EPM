<?php

declare(strict_types=1);

/**
 * EphpicMan's metadata query builder.
 *
 * @package   EPM
 * @version   1.0.0
 * @author    Sina Kuhestani <sinakuhestani@gmail.com>
 * @license   MIT
 * @copyright 2026 Sina Kuhestani
 * @since     1.0.0
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for the meta_query argument accepted by WordPress query APIs.
 *
 * The query property always contains a valid WordPress meta-query structure.
 * The builder creates query arguments only; it does not execute a query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_meta_query/
 */
class MetaQuery extends Query
{

	/**
	 * WordPress-compatible metadata query structure.
	 *
	 * @var array<string|int, mixed>
	 */
	private array $query = [];

	/**
	 * Create an empty metadata-query builder.
	 */
	public function __construct(array $queryArray = [])
	{
		$this->query = $queryArray;
	}

	/**
	 * Create a new empty metadata-query builder.
	 *
	 * @return static
	 */
	public static function make(array $queryArray = []): static
	{
		return new static($queryArray);
	}

	/**
	 * Add a metadata condition joined with AND.
	 *
	 * Usage:
	 * - where( 'age', '>=', 18, 'NUMERIC' )
	 * - where( 'status', 'active' )
	 *
	 * @param string $key      Metadata key.
	 * @param mixed  $operator Comparison operator, or value in shorthand form.
	 * @param mixed  $value    Value to compare.
	 * @param string $type     WordPress metadata value type.
	 * @return $this
	 */
	public function where(string $key, $operator, $value = null, string $type = 'CHAR'): self
	{
		if (2 === \func_num_args()) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->addClause(
			[
				'key'     => $key,
				'value'   => $value,
				'compare' => $operator,
				'type'    => $type,
			],
			'AND'
		);
	}

	/**
	 * Add a metadata condition joined with OR.
	 *
	 * @param string $key      Metadata key.
	 * @param mixed  $operator Comparison operator, or value in shorthand form.
	 * @param mixed  $value    Value to compare.
	 * @param string $type     WordPress metadata value type.
	 * @return $this
	 */
	public function orWhere(string $key, $operator, $value = null, string $type = 'CHAR'): self
	{
		if (2 === \func_num_args()) {
			$value    = $operator;
			$operator = '=';
		}

		return $this->addClause(
			[
				'key'     => $key,
				'value'   => $value,
				'compare' => $operator,
				'type'    => $type,
			],
			'OR'
		);
	}

	/**
	 * Add an IN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Values to match.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereIn(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'IN', $values, $type);
	}

	/**
	 * Add a NOT IN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Values to exclude.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereNotIn(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'NOT IN', $values, $type);
	}

	/**
	 * Add a BETWEEN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Two boundary values.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereBetween(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'BETWEEN', $values, $type);
	}

	/**
	 * Add a NOT BETWEEN metadata condition.
	 *
	 * @param string $key    Metadata key.
	 * @param array  $values Two boundary values.
	 * @param string $type   WordPress metadata value type.
	 * @return $this
	 */
	public function whereNotBetween(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'NOT BETWEEN', $values, $type);
	}

	/**
	 * Add a LIKE metadata condition.
	 *
	 * @param string $key   Metadata key.
	 * @param string $value Value or pattern to search for.
	 * @return $this
	 */
	public function whereLike(string $key, string $value): self
	{
		return $this->where($key, 'LIKE', $value);
	}

	/**
	 * Add a NOT LIKE metadata condition.
	 *
	 * @param string $key   Metadata key.
	 * @param string $value Value or pattern to exclude.
	 * @return $this
	 */
	public function whereNotLike(string $key, string $value): self
	{
		return $this->where($key, 'NOT LIKE', $value);
	}

	/**
	 * Add a REGEXP metadata condition.
	 *
	 * @param string $key     Metadata key.
	 * @param string $pattern Regular expression.
	 * @return $this
	 */
	public function whereRegexp(string $key, string $pattern): self
	{
		return $this->where($key, 'REGEXP', $pattern);
	}

	/**
	 * Add a NOT REGEXP metadata condition.
	 *
	 * @param string $key     Metadata key.
	 * @param string $pattern Regular expression.
	 * @return $this
	 */
	public function whereNotRegexp(string $key, string $pattern): self
	{
		return $this->where($key, 'NOT REGEXP', $pattern);
	}

	/**
	 * Match records where the specified metadata key exists.
	 *
	 * @param string $key Metadata key.
	 * @return $this
	 */
	public function whereExists(string $key): self
	{
		return $this->addClause(
			[
				'key'     => $key,
				'compare' => 'EXISTS',
			],
			'AND'
		);
	}

	/**
	 * Match records where the specified metadata key does not exist.
	 *
	 * @param string $key Metadata key.
	 * @return $this
	 */
	public function whereNotExists(string $key): self
	{
		return $this->addClause(
			[
				'key'     => $key,
				'compare' => 'NOT EXISTS',
			],
			'AND'
		);
	}

	/**
	 * Match records where the metadata key itself satisfies a comparison.
	 *
	 * Uses WP_Meta_Query's compare_key and type_key arguments.
	 *
	 * @param string $key        Metadata key or key pattern.
	 * @param string $comparison Key comparison operator.
	 * @param string $type       Key type, where supported by WordPress.
	 * @return $this
	 */
	public function whereMetaKey(string $key, string $comparison = '=', string $type = ''): self
	{
		$clause = [
			'key'         => $key,
			'compare'     => 'EXISTS',
			'compare_key' => $comparison,
		];

		if ('' !== $type) {
			$clause['type_key'] = $type;
		}

		return $this->addClause($clause, 'AND');
	}

	/**
	 * Add a nested condition group.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @param string   $relation Relation inside the group: AND or OR.
	 * @return $this
	 */
	public function whereGroup(callable $callback, string $relation = 'AND'): self
	{
		return $this->addGroup($callback, $relation, 'AND');
	}

	/**
	 * Add a nested condition group joined to the current expression with OR.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @param string   $relation Relation inside the group: AND or OR.
	 * @return $this
	 */
	public function orWhereGroup(callable $callback, string $relation = 'AND'): self
	{
		return $this->addGroup($callback, $relation, 'OR');
	}

	/**
	 * Add a group whose conditions are joined with OR, joined to this expression with AND.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @return $this
	 */
	public function whereAny(callable $callback): self
	{
		return $this->whereGroup($callback, 'OR');
	}

	/**
	 * Add a group whose conditions are joined with AND, joined to this expression with AND.
	 *
	 * @param callable $callback Receives the nested MetaQuery builder.
	 * @return $this
	 */
	public function whereAll(callable $callback): self
	{
		return $this->whereGroup($callback, 'AND');
	}

	/**
	 * Add a complete WordPress metadata clause.
	 *
	 * The clause is passed through unchanged, supporting WP_Meta_Query arguments not otherwise exposed by dedicated
	 * methods.
	 *
	 * @param array<string, mixed> $clause  WordPress metadata clause.
	 * @param string               $boolean Boolean joining this clause: AND/OR.
	 * @return $this
	 */
	public function whereClause(array $clause, string $boolean = 'AND'): self
	{
		return $this->addClause($clause, $boolean);
	}

	/**
	 * Add a clause to the WordPress-compatible query structure.
	 *
	 * AND conditions are added to the current final AND branch. OR conditions start a new branch. The property remains
	 * in WP_Meta_Query array format after every call.
	 *
	 * @param array<string|int, mixed> $clause  Clause or nested group.
	 * @param string                   $boolean Boolean joining this clause.
	 * @return $this
	 */
	private function addClause(array $clause, string $boolean): self
	{
		$boolean = 'OR' === strtoupper($boolean) ? 'OR' : 'AND';

		if (empty($this->query)) {
			$this->query = [$clause];
			return $this;
		}

		if ('OR' === $boolean) {
			if (isset($this->query['relation']) && 'OR' === $this->query['relation']) {
				$this->query[] = $clause;
			} else {
				$this->query = [
					'relation' => 'OR',
					$this->query,
					$clause,
				];
			}

			return $this;
		}

		if (isset($this->query['relation']) && 'OR' === $this->query['relation']) {
			$last_key = array_key_last($this->query);
			$last = $this->query[$last_key];

			if (isset($last['relation']) && 'AND' === $last['relation']) {
				$this->query[$last_key][] = $clause;
			} else {
				$this->query[$last_key] = [
					'relation' => 'AND',
					$last,
					$clause,
				];
			}

			return $this;
		}

		if (isset($this->query['relation']) && 'AND' === $this->query['relation']) {
			$this->query[] = $clause;
		} else {
			$this->query = [
				'relation' => 'AND',
				$this->query,
				$clause,
			];
		}

		return $this;
	}

	/**
	 * Build and add a nested group using a child builder.
	 *
	 * @param callable $callback Callback receiving the child builder.
	 * @param string   $relation Relation inside the group.
	 * @param string   $boolean  Relation between this group and its siblings.
	 * @return $this
	 */
	private function addGroup(callable $callback, string $relation, string $boolean): self
	{
		$nested = new self();
		$callback($nested);

		$conditions = $nested->toArray();

		if (empty($conditions)) {
			return $this;
		}

		$relation = 'OR' === strtoupper($relation) ? 'OR' : 'AND';

		if (isset($conditions['relation'])) {
			$conditions['relation'] = $relation;
		} else {
			$conditions = [
				'relation' => $relation,
				$conditions,
			];
		}

		return $this->addClause($conditions, $boolean);
	}

	/**
	 * Return the WordPress-compatible meta_query array.
	 *
	 * The query property is already kept in its final structure.
	 *
	 * @return array<string|int, mixed>
	 */
	public function toArray(): array
	{
		return $this->query;
	}
}
