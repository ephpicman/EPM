<?php

declare(strict_types=1);

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

class UserQuery
{
    public const USER_COLUMNS = [
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
    ];

    private IncludeExcludeQuery $idQuery;

    private IncludeExcludeIsQuery $roleQuery;

    private IncludeExcludeIsQuery $loginQuery;

    private IncludeExcludeIsQuery $nicenameQuery;

    private SearchQuery $searchQuery;

    private MetaQuery $metaQuery;

    private string|array|null $orderBy = null;

    private ?string $order = null;

    private ?int $number = null;

    private ?int $offset = null;

    private string|array|null $fields = null;

    private ?bool $countTotal = null;

    public function __construct(array $queryArray = [])
    {
        $this->idQuery = IncludeExcludeQuery::make(
            [
                'included' => $queryArray['include'] ?? [],
                'excluded' => $queryArray['exclude'] ?? [],
            ]
        );

        $this->roleQuery = IncludeExcludeIsQuery::make(
            [
                'included' => $queryArray['role__in'] ?? [],
                'excluded' => $queryArray['role__not_in'] ?? [],
                'is' => isset($queryArray['role'])
                    ? (array) $queryArray['role']
                    : (isset($queryArray['role__and'])
                        ? (array) $queryArray['role__and']
                        : null),
            ],
            [
                'isIdentifier' => false,
                'multiValue' => true,
            ]
        );

        if (isset($queryArray['role'])) {
            $this->roleQuery->is(
                (array) $queryArray['role']
            );
        }

        $this->loginQuery = IncludeExcludeIsQuery::make(
            [
                'included' => $queryArray['login__in'] ?? [],
                'excluded' => $queryArray['login__not_in'] ?? [],
                'is' => $queryArray['login'] ?? null,
            ],
            [
                'isIdentifier' => false,
            ]
        );

        $this->nicenameQuery = IncludeExcludeIsQuery::make(
            [
                'included' => $queryArray['nicename__in'] ?? [],
                'excluded' => $queryArray['nicename__not_in'] ?? [],
                'is' => $queryArray['nicename'] ?? null,
            ],
            [
                'isIdentifier' => false,
            ]
        );

        $this->searchQuery = SearchQuery::make(
            [
                'search' => $queryArray['search'] ?? '',
                'searchColumns' => $queryArray['search_columns'] ?? [],
            ],
            [
                'allowedSearchColumns' => [
                    'ID',
                    'user_login',
                    'user_email',
                    'user_url',
                    'user_nicename',
                    'display_name',
                ],
            ]
        );

        $this->metaQuery = MetaQuery::make(
            $queryArray['meta_query'] ?? []
        );

        if (isset($queryArray['orderby'])) {
            $this->orderBy = $queryArray['orderby'];
        }

        if (isset($queryArray['order'])) {
            $this->order = 'DESC' === \strtoupper($queryArray['order'])
                ? 'DESC'
                : 'ASC';
        }

        if (isset($queryArray['number'])) {
            $number = (int) $queryArray['number'];
            $this->number = -1 === $number ? -1 : \max(0, $number);
        }

        if (isset($queryArray['offset'])) {
            $this->offset = \max(0, (int) $queryArray['offset']);
        }

        if (\array_key_exists('fields', $queryArray)) {
            $this->fields = $queryArray['fields'];
        }

        if (isset($queryArray['count_total'])) {
            $this->countTotal = (bool) $queryArray['count_total'];
        }
    }

    public static function make(array $queryArray = []): static
    {
        return new static($queryArray);
    }

    public function includeIds(int|float|string|array $ids): self
    {
        $this->idQuery->include($ids);

        return $this;
    }

    public function excludeIds(int|float|string|array $ids): self
    {
        $this->idQuery->exclude($ids);

        return $this;
    }

    public function role(string|array $roles): self
    {
        $this->roleQuery->is(
            \is_array($roles) ? $roles : [$roles]
        );

        return $this;
    }

    public function rolesIn(string|array $roles): self
    {
        $this->roleQuery->include($roles);

        return $this;
    }

    public function rolesNotIn(string|array $roles): self
    {
        $this->roleQuery->exclude($roles);

        return $this;
    }

    public function rolesAnd(string|array $roles): self
    {
        $this->roleQuery->is(
            \is_array($roles) ? $roles : [$roles]
        );

        return $this;
    }

    public function login(string $login): self
    {
        $this->loginQuery->is($login);

        return $this;
    }

    public function loginsIn(string|array $logins): self
    {
        $this->loginQuery->include($logins);

        return $this;
    }

    public function loginsNotIn(string|array $logins): self
    {
        $this->loginQuery->exclude($logins);

        return $this;
    }

    public function nicename(string $nicename): self
    {
        $this->nicenameQuery->is($nicename);

        return $this;
    }

    public function nicenamesIn(string|array $nicenames): self
    {
        $this->nicenameQuery->include($nicenames);

        return $this;
    }

    public function nicenamesNotIn(string|array $nicenames): self
    {
        $this->nicenameQuery->exclude($nicenames);

        return $this;
    }

    public function search(string $value): self
    {
        $this->searchQuery->search($value);

        return $this;
    }

    public function searchColumns(string|array $columns): self
    {
        $this->searchQuery->searchColumns($columns);

        return $this;
    }

    public function searchByLogin(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_login');
    }

    public function searchByEmail(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_email');
    }

    public function searchByUrl(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_url');
    }

    public function searchByNicename(string $value): self
    {
        return $this->search($value)
            ->searchColumns('user_nicename');
    }

    public function searchByDisplayName(string $value): self
    {
        return $this->search($value)
            ->searchColumns('display_name');
    }

    public function orderBy(string|array $field, string $direction = 'ASC'): self
    {
        $this->orderBy = $field;
        $this->order = 'DESC' === \strtoupper($direction)
            ? 'DESC'
            : 'ASC';

        return $this;
    }

    public function limit(int $number): self
    {
        $this->number = -1 === $number ? -1 : \max(0, $number);

        return $this;
    }

    public function offset(int $number): self
    {
        $this->offset = \max(0, $number);

        return $this;
    }

    public function select(string|array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function getSelectedMetaFields(): array
    {
        if (
            null === $this->fields
            || 'all' === $this->fields
            || 'ID' === $this->fields
        ) {
            return [];
        }

        $fields = \is_array($this->fields)
            ? $this->fields
            : [$this->fields];

        return \array_values(
            \array_filter(
                $fields,
                static fn ($field): bool =>
                    \is_string($field)
                    && !\in_array($field, self::USER_COLUMNS, true)
            )
        );
    }

    public function countTotal(bool $count = true): self
    {
        $this->countTotal = $count;

        return $this;
    }

    public function where(
        string $key,
        mixed $operator,
        mixed $value = null,
        string $type = 'CHAR'
    ): self {
        if (2 === \func_num_args()) {
            $this->metaQuery->where($key, $operator);
        } elseif (3 === \func_num_args()) {
            $this->metaQuery->where($key, $operator, $value);
        } else {
            $this->metaQuery->where($key, $operator, $value, $type);
        }

        return $this;
    }

    public function orWhere(
        string $key,
        mixed $operator,
        mixed $value = null,
        string $type = 'CHAR'
    ): self {
        if (2 === \func_num_args()) {
            $this->metaQuery->orWhere($key, $operator);
        } elseif (3 === \func_num_args()) {
            $this->metaQuery->orWhere($key, $operator, $value);
        } else {
            $this->metaQuery->orWhere($key, $operator, $value, $type);
        }

        return $this;
    }

    public function whereIn(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereIn($key, $values, $type);

        return $this;
    }

    public function whereNotIn(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereNotIn($key, $values, $type);

        return $this;
    }

    public function whereBetween(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereBetween($key, $values, $type);

        return $this;
    }

    public function whereNotBetween(
        string $key,
        array $values,
        string $type = 'CHAR'
    ): self {
        $this->metaQuery->whereNotBetween($key, $values, $type);

        return $this;
    }

    public function whereLike(string $key, string $value): self
    {
        $this->metaQuery->whereLike($key, $value);

        return $this;
    }

    public function whereNotLike(string $key, string $value): self
    {
        $this->metaQuery->whereNotLike($key, $value);

        return $this;
    }

    public function whereRegexp(string $key, string $pattern): self
    {
        $this->metaQuery->whereRegexp($key, $pattern);

        return $this;
    }

    public function whereNotRegexp(string $key, string $pattern): self
    {
        $this->metaQuery->whereNotRegexp($key, $pattern);

        return $this;
    }

    public function whereExists(string $key): self
    {
        $this->metaQuery->whereExists($key);

        return $this;
    }

    public function whereNotExists(string $key): self
    {
        $this->metaQuery->whereNotExists($key);

        return $this;
    }

    public function whereMetaKey(
        string $key,
        string $comparison = '=',
        string $type = ''
    ): self {
        $this->metaQuery->whereMetaKey($key, $comparison, $type);

        return $this;
    }

    public function whereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        $this->metaQuery->whereGroup($callback, $relation);

        return $this;
    }

    public function orWhereGroup(
        callable $callback,
        string $relation = 'AND'
    ): self {
        $this->metaQuery->orWhereGroup($callback, $relation);

        return $this;
    }

    public function whereAny(callable $callback): self
    {
        $this->metaQuery->whereAny($callback);

        return $this;
    }

    public function whereAll(callable $callback): self
    {
        $this->metaQuery->whereAll($callback);

        return $this;
    }

    public function whereClause(
        array $clause,
        string $boolean = 'AND'
    ): self {
        $this->metaQuery->whereClause($clause, $boolean);

        return $this;
    }

    public function toArray(): array
    {
        $args = [];

        $idQuery = $this->idQuery->toArray();

        if (isset($idQuery['included'])) {
            $args['include'] = $idQuery['included'];
        }

        if (isset($idQuery['excluded'])) {
            $args['exclude'] = $idQuery['excluded'];
        }

        $roleQuery = $this->roleQuery->toArray();

        if (isset($roleQuery['included'])) {
            $args['role__in'] = $roleQuery['included'];
        }

        if (isset($roleQuery['excluded'])) {
            $args['role__not_in'] = $roleQuery['excluded'];
        }

        if (isset($roleQuery['is'])) {
            $args['role'] = $roleQuery['is'];
        }

        $loginQuery = $this->loginQuery->toArray();

        if (isset($loginQuery['included'])) {
            $args['login__in'] = $loginQuery['included'];
        }

        if (isset($loginQuery['excluded'])) {
            $args['login__not_in'] = $loginQuery['excluded'];
        }

        if (isset($loginQuery['is'])) {
            $args['login'] = $loginQuery['is'];
        }

        $nicenameQuery = $this->nicenameQuery->toArray();

        if (isset($nicenameQuery['included'])) {
            $args['nicename__in'] = $nicenameQuery['included'];
        }

        if (isset($nicenameQuery['excluded'])) {
            $args['nicename__not_in'] = $nicenameQuery['excluded'];
        }

        if (isset($nicenameQuery['is'])) {
            $args['nicename'] = $nicenameQuery['is'];
        }

        $searchQuery = $this->searchQuery->toArray();

        if (isset($searchQuery['search'])) {
            $args['search'] = $searchQuery['search'];
        }

        if (isset($searchQuery['searchColumns'])) {
            $args['search_columns'] = $searchQuery['searchColumns'];
        }

        if (null !== $this->orderBy) {
            $args['orderby'] = $this->orderBy;
        }

        if (null !== $this->order) {
            $args['order'] = $this->order;
        }

        if (null !== $this->number) {
            $args['number'] = $this->number;
        }

        if (null !== $this->offset) {
            $args['offset'] = $this->offset;
        }

        if (null !== $this->fields) {
            if ('all' === $this->fields) {
                $args['fields'] = 'all';
            } elseif (\is_array($this->fields)) {
                $userFields = \array_values(
                    \array_filter(
                        $this->fields,
                        static fn ($field): bool =>
                            \is_string($field)
                            && \in_array($field, self::USER_COLUMNS, true)
                    )
                );

                $args['fields'] = [] !== $userFields ? $userFields : 'ID';
            } else {
                $args['fields'] = \in_array(
                    $this->fields,
                    self::USER_COLUMNS,
                    true
                ) ? $this->fields : 'ID';
            }
        }

        if (null !== $this->countTotal) {
            $args['count_total'] = $this->countTotal;
        }

        $metaQuery = $this->metaQuery->toArray();

        if ([] !== $metaQuery) {
            $args['meta_query'] = $metaQuery;
        }

        return $args;
    }

    public function get(): array
    {
        $args = $this->toArray();

        if ([] !== $this->getSelectedMetaFields()) {
            $args['fields'] = 'all';
        }

        $query = new \WP_User_Query($args);

        return $query->get_results();
    }

    public function count(): int
    {
        $args = $this->toArray();
        $args['count_total'] = true;

        $query = new \WP_User_Query($args);

        return (int) $query->get_total();
    }

    public static function getAll(): array
    {
        return static::make()
            ->limit(-1)
            ->get();
    }
}
