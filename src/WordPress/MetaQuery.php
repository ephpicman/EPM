<?php

declare(strict_types=1);

/**
 * Builds WordPress-compatible metadata query arguments.
 *
 * Provides a fluent interface for adding metadata conditions, combining them
 * with AND/OR relations, and creating nested condition groups. The builder
 * constructs the meta_query array only; it does not execute a WordPress query.
 *
 * @package EPM
 * @since   1.0.0
 *
 * @see https://developer.wordpress.org/reference/classes/wp_meta_query/
 * @see https://developer.wordpress.org/reference/classes/wp_query/
 * @see https://developer.wordpress.org/reference/classes/wp_user_query/
 */

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

/**
 * Fluent builder for the meta_query argument used by WordPress query APIs.
 *
 * Conditions are stored as a WordPress-compatible metadata-query array and
 * returned by toArray(). Methods are chainable. This class does not validate
 * every clause against WordPress or execute the resulting query.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_meta_query/
 */
class MetaQuery extends Query
{

	/**
	 * The current WordPress-compatible metadata-query structure.
	 *
	 * May contain individual clauses, a relation key, or nested clause groups.
	 * The array is returned as-is by toArray().
	 *
	 * @var array<string|int, mixed>
	 */
	private array $query = [];

	/**
	 * Initialise the builder with an optional metadata-query array.
	 *
	 * The supplied array is stored without normalisation or validation.
	 *
	 * @param array<string|int, mixed> $queryArray Existing metadata-query structure.
	 */
	public function __construct(array $queryArray = [])
	{
		$this->query = $queryArray;
	}

	/**
	 * Create a builder from an optional metadata-query array.
	 *
	 * The args parameter is accepted to satisfy the shared Query factory
	 * signature; this implementation does not use it.
	 *
	 * @param array<string|int, mixed> $queryArray Initial metadata-query structure.
	 * @param array<string, mixed> $args Unused additional arguments.
	 *
	 * @return static A new builder instance.
	 */
	public static function make(array $queryArray = [], array $args = []): static
	{
		return new static($queryArray);
	}

	/**
	 * Add a metadata comparison joined to the current expression with AND.
	 *
	 * In the two-argument shorthand, the second argument is treated as the
	 * value and the comparison operator defaults to equals. Otherwise, the
	 * arguments map to WordPress clause keys key, compare, value, and type.
	 *
	 * Examples:
	 * - where('age', '>=', 18, 'NUMERIC')
	 * - where('status', 'active')
	 *
	 * @param string $key Metadata key.
	 * @param mixed $operator Comparison operator, or value in shorthand form.
	 * @param mixed $value Value to compare when an operator is supplied.
	 * @param string $type WordPress metadata value type; defaults to CHAR.
	 *
	 * @return $this The current builder instance.
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
	 * Add a metadata comparison joined to the current expression with OR.
	 *
	 * Supports the same two-argument shorthand as where(), where the second
	 * argument becomes the value and the comparison operator defaults to equals.
	 *
	 * @param string $key Metadata key.
	 * @param mixed $operator Comparison operator, or value in shorthand form.
	 * @param mixed $value Value to compare when an operator is supplied.
	 * @param string $type WordPress metadata value type; defaults to CHAR.
	 *
	 * @return $this The current builder instance.
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
	 * Add a metadata condition matching any value in the supplied list.
	 *
	 * Creates a clause using WordPress's IN comparison operator.
	 *
	 * @param string $key Metadata key.
	 * @param array<int|string, mixed> $values Values to match.
	 * @param string $type WordPress metadata value type; defaults to CHAR.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereIn(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'IN', $values, $type);
	}

	/**
	 * Add a metadata condition excluding values in the supplied list.
	 *
	 * Creates a clause using WordPress's NOT IN comparison operator.
	 *
	 * @param string $key Metadata key.
	 * @param array<int|string, mixed> $values Values to exclude.
	 * @param string $type WordPress metadata value type; defaults to CHAR.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereNotIn(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'NOT IN', $values, $type);
	}

	/**
	 * Add a metadata condition matching values within a range.
	 *
	 * Creates a clause using WordPress's BETWEEN comparison operator.
	 *
	 * @param string $key Metadata key.
	 * @param array<int|string, mixed> $values Range boundary values.
	 * @param string $type WordPress metadata value type; defaults to CHAR.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereBetween(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'BETWEEN', $values, $type);
	}

	/**
	 * Add a metadata condition matching values outside a range.
	 *
	 * Creates a clause using WordPress's NOT BETWEEN comparison operator.
	 *
	 * @param string $key Metadata key.
	 * @param array<int|string, mixed> $values Range boundary values.
	 * @param string $type WordPress metadata value type; defaults to CHAR.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereNotBetween(string $key, array $values, string $type = 'CHAR'): self
	{
		return $this->where($key, 'NOT BETWEEN', $values, $type);
	}

	/**
	 * Add a metadata condition using WordPress's LIKE comparison operator.
	 *
	 * Wildcard handling follows WordPress's metadata-query behaviour.
	 *
	 * @param string $key Metadata key.
	 * @param string $value Value or pattern to match.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereLike(string $key, string $value): self
	{
		return $this->where($key, 'LIKE', $value);
	}

	/**
	 * Add a metadata condition using WordPress's NOT LIKE comparison operator.
	 *
	 * @param string $key Metadata key.
	 * @param string $value Value or pattern to exclude.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereNotLike(string $key, string $value): self
	{
		return $this->where($key, 'NOT LIKE', $value);
	}

	/**
	 * Add a metadata condition using WordPress's REGEXP operator.
	 *
	 * @param string $key Metadata key.
	 * @param string $pattern Regular-expression pattern to match.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereRegexp(string $key, string $pattern): self
	{
		return $this->where($key, 'REGEXP', $pattern);
	}

	/**
	 * Add a metadata condition using WordPress's NOT REGEXP operator.
	 *
	 * @param string $key Metadata key.
	 * @param string $pattern Regular-expression pattern to exclude.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereNotRegexp(string $key, string $pattern): self
	{
		return $this->where($key, 'NOT REGEXP', $pattern);
	}

	/**
	 * Add a condition requiring the metadata key to exist.
	 *
	 * Creates a clause with the EXISTS comparison operator and no value.
	 *
	 * @param string $key Metadata key that must exist.
	 *
	 * @return $this The current builder instance.
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
	 * Add a condition requiring the metadata key not to exist.
	 *
	 * Creates a clause with the NOT EXISTS comparison operator.
	 *
	 * @param string $key Metadata key that must not exist.
	 *
	 * @return $this The current builder instance.
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
	 * Add a clause that compares the metadata key rather than its value.
	 *
	 * Uses WordPress's compare_key argument and optionally type_key. The clause
	 * also sets compare to EXISTS; support for these options depends on the
	 * WordPress version and query context.
	 *
	 * @param string $key Metadata key or key pattern.
	 * @param string $comparison Key comparison operator; defaults to equals.
	 * @param string $type Optional key type passed as type_key.
	 *
	 * @return $this The current builder instance.
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
	 * Add a nested group joined to the current expression with AND.
	 *
	 * The callback receives a fresh MetaQuery builder. The relation argument
	 * controls how conditions inside the group are combined; values other than
	 * OR default to AND.
	 *
	 * @param callable $callback Receives the nested builder.
	 * @param string $relation Relation inside the group: AND or OR.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereGroup(callable $callback, string $relation = 'AND'): self
	{
		return $this->addGroup($callback, $relation, 'AND');
	}

	/**
	 * Add a nested group joined to the current expression with OR.
	 *
	 * The callback receives a fresh MetaQuery builder. The relation argument
	 * controls how conditions inside the group are combined; values other than
	 * OR default to AND.
	 *
	 * @param callable $callback Receives the nested builder.
	 * @param string $relation Relation inside the group: AND or OR.
	 *
	 * @return $this The current builder instance.
	 */
	public function orWhereGroup(callable $callback, string $relation = 'AND'): self
	{
		return $this->addGroup($callback, $relation, 'OR');
	}

	/**
	 * Add a nested group whose conditions are joined with OR.
	 *
	 * The group itself is joined to the current expression with AND.
	 *
	 * @param callable $callback Receives the nested builder.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereAny(callable $callback): self
	{
		return $this->whereGroup($callback, 'OR');
	}

	/**
	 * Add a nested group whose conditions are joined with AND.
	 *
	 * The group itself is also joined to the current expression with AND.
	 *
	 * @param callable $callback Receives the nested builder.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereAll(callable $callback): self
	{
		return $this->whereGroup($callback, 'AND');
	}

	/**
	 * Add a raw WordPress metadata clause.
	 *
	 * The clause is passed to the clause-composition logic without validating
	 * or rewriting its keys. Use this for WordPress options not exposed by a
	 * dedicated builder method.
	 *
	 * @param array<string, mixed> $clause WordPress metadata clause.
	 * @param string $boolean Relation to this clause: AND or OR.
	 *
	 * @return $this The current builder instance.
	 */
	public function whereClause(array $clause, string $boolean = 'AND'): self
	{
		return $this->addClause($clause, $boolean);
	}

	/**
	 * Compose a clause into the current query structure.
	 *
	 * The boolean is normalised to OR only when its upper-case value is exactly
	 * OR; all other values are treated as AND. OR clauses may wrap the existing
	 * expression in an OR group, while AND clauses are appended to or grouped
	 * with the current structure.
	 *
	 * @param array<string|int, mixed> $clause Clause or nested group to add.
	 * @param string $boolean Relation joining the clause.
	 *
	 * @return $this The current builder instance.
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
	 * Build a nested group and add it to the current query.
	 *
	 * A fresh builder is passed to the callback. Empty groups are ignored. The
	 * group's relation defaults to AND unless OR is requested. The boolean
	 * argument controls how the resulting group joins the existing expression.
	 *
	 * @param callable $callback Callback that configures the child builder.
	 * @param string $relation Relation between conditions inside the group.
	 * @param string $boolean Relation joining the group to the current query.
	 *
	 * @return $this The current builder instance.
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
	 * Return the current WordPress-compatible metadata-query array.
	 *
	 * Returns the internal structure directly, without copying, normalising,
	 * or validating it. The result can be assigned to a WordPress query's
	 * meta_query argument.
	 *
	 * @return array<string|int, mixed> The metadata-query structure.
	 */
	public function toArray(): array
	{
		return $this->query;
	}
}
