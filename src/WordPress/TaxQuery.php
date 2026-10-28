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
 * Fluent builder for the tax_query argument used by WordPress query APIs.
 *
 * Conditions are stored as a WordPress-compatible taxonomy-query array and returned by toArray(). The builder
 * supports taxonomy clauses, boolean relations, and nested groups. It does not execute the query or validate every
 * clause against WordPress.
 *
 * @see https://developer.wordpress.org/reference/classes/wp_tax_query/
 */
class TaxQuery implements Query
{
    /**
     * The current WordPress-compatible taxonomy-query structure.
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
     * Initialise the builder with an optional taxonomy-query array.
     *
     * The supplied array is stored without normalisation or validation.
     *
     * @param array<string|int, mixed> $queryArray Existing taxonomy-query structure.
     *
     * @lock-signature
     */
    public function __construct(array $queryArray = [])
    {
        $this->query = $queryArray;
    }

    /**
     * Create a builder from an optional taxonomy-query array.
     *
     * The args parameter is accepted to satisfy the shared Query factory signature; this implementation does not use it.
     *
     * @param array<string|int, mixed> $queryArray Initial taxonomy-query structure.
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
     * Add a taxonomy clause joined to the current expression with AND.
     *
     * The clause maps to WordPress's taxonomy, terms, field, operator, and include_children keys.
     *
     * @param string                  $taxonomy        Taxonomy name.
     * @param int|string|array        $terms           Term identifier, slug, name, or list of terms.
     * @param string                  $field           Term field used to match terms; defaults to term_id.
     * @param string                  $operator        WordPress taxonomy operator; defaults to IN.
     * @param bool                    $includeChildren Whether to include descendant terms.
     * @return $this                                   The current builder instance.
     *
     * @lock-signature
     */
    public function where(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        string $operator = 'IN',
        bool $includeChildren = true
    ): self {
        return $this->addClause(
            [
                'taxonomy'         => $taxonomy,
                'terms'            => $terms,
                'field'            => $field,
                'operator'         => $operator,
                'include_children' => $includeChildren,
            ],
            'AND'
        );
    }

    /**
     * Add a taxonomy clause joined to the current expression with OR.
     *
     * Accepts the same clause arguments as where().
     *
     * @param string                  $taxonomy        Taxonomy name.
     * @param int|string|array        $terms           Term identifier, slug, name, or list of terms.
     * @param string                  $field           Term field used to match terms; defaults to term_id.
     * @param string                  $operator        WordPress taxonomy operator; defaults to IN.
     * @param bool                    $includeChildren Whether to include descendant terms.
     * @return $this                                   The current builder instance.
     *
     * @lock-signature
     */
    public function orWhere(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        string $operator = 'IN',
        bool $includeChildren = true
    ): self {
        return $this->addClause(
            [
                'taxonomy'         => $taxonomy,
                'terms'            => $terms,
                'field'            => $field,
                'operator'         => $operator,
                'include_children' => $includeChildren,
            ],
            'OR'
        );
    }

    /**
     * Add a taxonomy clause that matches any of the supplied terms.
     *
     * Uses WordPress's IN operator.
     *
     * @param string           $taxonomy        Taxonomy name.
     * @param int|string|array $terms           Term or terms to match.
     * @param string           $field           Term field used for matching.
     * @param bool             $includeChildren Whether to include descendant terms.
     * @return $this                            The current builder instance.
     *
     * @lock-signature
     */
    public function whereIn(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        bool $includeChildren = true
    ): self {
        return $this->where(
            $taxonomy,
            $terms,
            $field,
            'IN',
            $includeChildren
        );
    }

    /**
     * Add a taxonomy clause that excludes the supplied terms.
     *
     * Uses WordPress's NOT IN operator.
     *
     * @param string           $taxonomy        Taxonomy name.
     * @param int|string|array $terms           Term or terms to exclude.
     * @param string           $field           Term field used for matching.
     * @param bool             $includeChildren Whether to include descendant terms.
     * @return $this                            The current builder instance.
     *
     * @lock-signature
     */
    public function whereNotIn(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id',
        bool $includeChildren = true
    ): self {
        return $this->where(
            $taxonomy,
            $terms,
            $field,
            'NOT IN',
            $includeChildren
        );
    }

    /**
     * Add a taxonomy clause requiring posts to match all supplied terms.
     *
     * Uses WordPress's AND operator and disables descendant-term inclusion.
     *
     * @param string           $taxonomy Taxonomy name.
     * @param int|string|array $terms    Terms that must all match.
     * @param string           $field    Term field used for matching.
     * @return $this                     The current builder instance.
     *
     * @lock-signature
     */
    public function whereAnd(
        string $taxonomy,
        int|string|array $terms,
        string $field = 'term_id'
    ): self {
        return $this->where(
            $taxonomy,
            $terms,
            $field,
            'AND',
            false
        );
    }

    /**
     * Add a clause requiring a taxonomy relationship to exist.
     *
     * Uses WordPress's EXISTS operator.
     *
     * @param string $taxonomy Taxonomy name.
     * @return $this           The current builder instance.
     *
     * @lock-signature
     */
    public function whereExists(string $taxonomy): self
    {
        return $this->addClause(
            [
                'taxonomy' => $taxonomy,
                'operator' => 'EXISTS',
            ],
            'AND'
        );
    }

    /**
     * Add a clause requiring a taxonomy relationship not to exist.
     *
     * Uses WordPress's NOT EXISTS operator.
     *
     * @param string $taxonomy Taxonomy name.
     * @return $this           The current builder instance.
     *
     * @lock-signature
     */
    public function whereNotExists(string $taxonomy): self
    {
        return $this->addClause(
            [
                'taxonomy' => $taxonomy,
                'operator' => 'NOT EXISTS',
            ],
            'AND'
        );
    }

    /**
     * Add a nested taxonomy-query group joined to the current expression with AND.
     *
     * The callback receives a fresh TaxQuery builder. The group's relation determines how its clauses are combined.
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
     * Add a nested taxonomy-query group joined to the current expression with OR.
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
     * Add a pre-built WordPress taxonomy clause or nested group.
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
     * Return the current WordPress-compatible taxonomy-query array.
     *
     * No transformation or validation is performed.
     *
     * @return array<string|int, mixed> The current taxonomy-query structure.
     *
     * @lock-signature
     */
    public function toArray(): array
    {
        return $this->query;
    }
}
