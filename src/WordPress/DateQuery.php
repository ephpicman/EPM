<?php

/*
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ EPM — EphpicMan Framework                                                                                            │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│ Modification Restrictions:                                                                                           │
│                                                                                                                      │
│ @lock-name        The member's name must not change.                                                                 │
│ @lock-body        The method implementation must not change.                                                         │
│ @lock-params      Existing parameters must not change. Additional optional parameters may be appended at the end,    │
│                   provided backward compatibility is preserved.                                                      │
│ @lock-signature   The method signature, including parameters and return type, must not change.                       │
│ @lock-type        The declared type must not change. For methods, this refers to the return type.                    │
│ @lock-value       Constant values and property default values must not change.                                       │
│ @lock-access      The member's visibility must not change.                                                           │
│ @lock-static      The member's static status must not change.                                                        │
│ @lock-final       The member's final status must not change.                                                         │
│ @lock-abstract    The class or method's abstract status must not change.                                             │
│ @lock             The member must not be modified, renamed or removed.                                               │
│                                                                                                                      │
│ - Apply restriction tags to the class or individual members as appropriate.                                          │
│ - Unspecified modifications are allowed.                                                                             │
│ - When multiple tags apply, all restrictions must be respected.                                                      │
│ - @lock overrides all other tags and prohibits every modification to the target.                                     │
│                                                                                                                      │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│ This file is part of EPM, the EphpicMan Framework.                                                                   │
│                                                                                                                      │
│ Copyright (c) Sina Kuhestani (EphpicMan).                                                                            │
│ Licensed under the MIT License. See the LICENSE file for details.                                                    │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
*/

declare(strict_types=1);

namespace EPM\WordPress;

/**
 * Fluent builder for the date_query argument used by WordPress query APIs.
 *
 * Conditions are stored as a WordPress-compatible date-query array and returned by toArray(). The builder supports
 * date boundaries, date parts, boolean relations, and nested groups. It does not execute the query or validate every
 * clause against WordPress.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_date_query/
 */
class DateQuery implements Query
{
    /**
     * The current WordPress-compatible date-query structure.
     *
     * May contain individual clauses, a relation key, or nested clause groups. The array is returned as-is by toArray().
     *
     * @var array<string|int, mixed>
     *
     * @lock-type
     * @lock-value
     */
    private array $query = [];

    /**
     * Initialise the builder with an optional date-query array.
     *
     * The supplied array is stored without normalisation or validation.
     *
     * @param array<string|int, mixed> $queryArray Existing date-query structure.
     *
     * @lock-signature
     */
    public function __construct(array $queryArray = [])
    {
        $this->query = $queryArray;
    }

    /**
     * Create a builder from an optional date-query array.
     *
     * The args parameter is accepted to satisfy the shared Query factory signature; this implementation does not use it.
     *
     * @param array<string|int, mixed> $queryArray Initial date-query structure.
     * @param array<string, mixed>     $args       Unused additional arguments.
     * @return static                              A new builder instance.
     *
     * @lock-signature
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray);
    }

    /**
     * Add a date clause joined to the current expression with AND.
     *
     * The clause is supplied in WordPress WP_Date_Query format.
     *
     * @param array<string|int, mixed> $clause WordPress-compatible date clause.
     * @return $this                           The current builder instance.
     *
     * @lock-signature
     */
    public function where(array $clause): self
    {
        return $this->addClause($clause, 'AND');
    }

    /**
     * Add a date clause joined to the current expression with OR.
     *
     * @param array<string|int, mixed> $clause WordPress-compatible date clause.
     * @return $this                           The current builder instance.
     *
     * @lock-signature
     */
    public function orWhere(array $clause): self
    {
        return $this->addClause($clause, 'OR');
    }

    /**
     * Add a lower date boundary.
     *
     * The date may be a string accepted by WordPress or an array describing date components.
     *
     * @param string|array $date     Lower date boundary.
     * @param bool         $inclusive Whether the boundary is inclusive.
     * @param string       $column   Database column to query; defaults to post_date.
     * @return $this                  The current builder instance.
     *
     * @lock-signature
     */
    public function after(
        string|array $date,
        bool $inclusive = false,
        string $column = 'post_date'
    ): self {
        return $this->where(
            [
                'after'     => $date,
                'inclusive' => $inclusive,
                'column'    => $column,
            ]
        );
    }

    /**
     * Add an upper date boundary.
     *
     * The date may be a string accepted by WordPress or an array describing date components.
     *
     * @param string|array $date     Upper date boundary.
     * @param bool         $inclusive Whether the boundary is inclusive.
     * @param string       $column   Database column to query; defaults to post_date.
     * @return $this                  The current builder instance.
     *
     * @lock-signature
     */
    public function before(
        string|array $date,
        bool $inclusive = false,
        string $column = 'post_date'
    ): self {
        return $this->where(
            [
                'before'    => $date,
                'inclusive' => $inclusive,
                'column'    => $column,
            ]
        );
    }

    /**
     * Add a date range using both lower and upper boundaries.
     *
     * @param string|array $after    Lower date boundary.
     * @param string|array $before   Upper date boundary.
     * @param bool         $inclusive Whether both boundaries are inclusive; defaults to true.
     * @param string       $column   Database column to query; defaults to post_date.
     * @return $this                  The current builder instance.
     *
     * @lock-signature
     */
    public function between(
        string|array $after,
        string|array $before,
        bool $inclusive = true,
        string $column = 'post_date'
    ): self {
        return $this->where(
            [
                'after'     => $after,
                'before'    => $before,
                'inclusive' => $inclusive,
                'column'    => $column,
            ]
        );
    }

    /**
     * Add a condition matching a calendar year.
     *
     * @param int    $year   Year to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this          The current builder instance.
     *
     * @lock-signature
     */
    public function year(int $year, string $column = 'post_date'): self
    {
        return $this->where([
            'year'   => $year,
            'column' => $column,
        ]);
    }

    /**
     * Add a condition matching a month.
     *
     * @param int    $month  Month number to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this          The current builder instance.
     *
     * @lock-signature
     */
    public function month(int $month, string $column = 'post_date'): self
    {
        return $this->where([
            'month'  => $month,
            'column' => $column,
        ]);
    }

    /**
     * Add a condition matching a week number.
     *
     * @param int    $week   Week number to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this          The current builder instance.
     *
     * @lock-signature
     */
    public function week(int $week, string $column = 'post_date'): self
    {
        return $this->where([
            'week'   => $week,
            'column' => $column,
        ]);
    }

    /**
     * Add a condition matching a day of the month.
     *
     * @param int    $day    Day number to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this          The current builder instance.
     *
     * @lock-signature
     */
    public function day(int $day, string $column = 'post_date'): self
    {
        return $this->where([
            'day'    => $day,
            'column' => $column,
        ]);
    }

    /**
     * Add a condition matching an hour.
     *
     * @param int    $hour   Hour to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this          The current builder instance.
     *
     * @lock-signature
     */
    public function hour(int $hour, string $column = 'post_date'): self
    {
        return $this->where([
            'hour'   => $hour,
            'column' => $column,
        ]);
    }

    /**
     * Add a condition matching a minute.
     *
     * @param int    $minute Minute to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this           The current builder instance.
     *
     * @lock-signature
     */
    public function minute(int $minute, string $column = 'post_date'): self
    {
        return $this->where([
            'minute' => $minute,
            'column' => $column,
        ]);
    }

    /**
     * Add a condition matching a second.
     *
     * @param int    $second Second to match.
     * @param string $column Database column to query; defaults to post_date.
     * @return $this           The current builder instance.
     *
     * @lock-signature
     */
    public function second(int $second, string $column = 'post_date'): self
    {
        return $this->where([
            'second' => $second,
            'column' => $column,
        ]);
    }

    /**
     * Add a nested date-query group joined to the current expression with AND.
     *
     * The callback receives a fresh DateQuery builder. The group's relation determines how its clauses are combined.
     *
     * @param callable $callback Callback used to define the nested group.
     * @param string   $relation Relation between clauses inside the group; AND or OR.
     * @return $this              The current builder instance.
     *
     * @lock-signature
     */
    public function whereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        return $this->addGroup($callback, $relation, 'AND');
    }

    /**
     * Add a nested date-query group joined to the current expression with OR.
     *
     * @param callable $callback Callback used to define the nested group.
     * @param string   $relation Relation between clauses inside the group; AND or OR.
     * @return $this              The current builder instance.
     *
     * @lock-signature
     */
    public function orWhereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        return $this->addGroup($callback, $relation, 'OR');
    }

    /**
     * Add a nested group whose clauses are combined with OR, joined to the current expression with AND.
     *
     * @param callable $callback Callback used to define the nested group.
     * @return $this              The current builder instance.
     *
     * @lock-signature
     */
    public function whereAny(callable $callback): self
    {
        return $this->whereGroup($callback, 'OR');
    }

    /**
     * Add a nested group whose clauses are combined with AND, joined to the current expression with AND.
     *
     * @param callable $callback Callback used to define the nested group.
     * @return $this              The current builder instance.
     *
     * @lock-signature
     */
    public function whereAll(callable $callback): self
    {
        return $this->whereGroup($callback, 'AND');
    }

    /**
     * Add a pre-built WordPress date clause or nested group.
     *
     * The clause is stored without validation. The boolean argument controls how it joins the current expression.
     *
     * @param array<string|int, mixed> $clause  WordPress-compatible clause or group.
     * @param string                  $boolean AND or OR.
     * @return $this                           The current builder instance.
     *
     * @lock-signature
     */
    public function whereClause(
        array $clause,
        string $boolean = 'AND'
    ): self {
        return $this->addClause($clause, $boolean);
    }

    /**
     * Append a clause using the requested boolean relation.
     *
     * When the requested relation differs from the current top-level relation, the existing query is nested as a group.
     *
     * @param array<string|int, mixed> $clause  Clause to append.
     * @param string                  $boolean Boolean relation to use.
     * @return $this                           The current builder instance.
     *
     * @lock-signature
     */
    private function addClause(array $clause, string $boolean): self
    {
        $boolean = 'OR' === strtoupper($boolean) ? 'OR' : 'AND';

        if ([] === $this->query) {
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

        if (!isset($this->query['relation']) || 'AND' === $this->query['relation']) {
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
     * Build a nested group and append it to the current expression.
     *
     * Empty groups are ignored. The relation is normalised to AND or OR before the group is added.
     *
     * @param callable $callback Callback used to build the group.
     * @param string   $relation Relation inside the group.
     * @param string   $boolean  Relation between the group and the current expression.
     * @return $this              The current builder instance.
     *
     * @lock-signature
     */
    private function addGroup(
        callable $callback,
        string $relation,
        string $boolean
    ): self {
        $group = new static();

        $callback($group);

        $query = $group->toArray();

        if ([] === $query) {
            return $this;
        }

        $relation = 'OR' === strtoupper($relation) ? 'OR' : 'AND';

        if (isset($query['relation'])) {
            $query['relation'] = $relation;
        } else {
            $query = [
                'relation' => $relation,
                $query,
            ];
        }

        return $this->addClause($query, $boolean);
    }

    /**
     * Return the current WordPress-compatible date-query array.
     *
     * No transformation or validation is performed.
     *
     * @return array<string|int, mixed> The current date-query structure.
     *
     * @lock-signature
     */
    public function toArray(): array
    {
        return $this->query;
    }
}
