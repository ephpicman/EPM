<?php

declare(strict_types=1);

namespace EPM\WordPress;

use EPM\Utils\ArrayUtil;

\defined('ABSPATH') || exit;

class IncludeQuery extends Query
{
    public const INTEGER = 1;
    public const STRING = 2;

    protected int $type = self::INTEGER;
    protected array $included = [];

    protected function cast(int|float|string $value): int|string
    {
        return self::INTEGER === $this->type
            ? \absint($value)
            : (string) $value;
    }

    public function __construct(array $queryArray = [], int $type = self::INTEGER)
    {
        if (!\in_array($type, [self::INTEGER, self::STRING], true)) {
            throw new \InvalidArgumentException('Invalid include type.');
        }

        $this->type = $type;

        if (isset($queryArray['included'])) {
            $this->include((array) $queryArray['included']);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray, $args['type'] ?? self::INTEGER);
    }

    public function include(int|float|string|array $value): self
    {
        $this->mergeIncluded(
            ArrayUtil::castValues($value, [$this, 'cast'])
        );

        return $this;
    }
    protected function mergeIncluded(array $values): void
    {
        $this->included = \array_values(
            \array_unique(
                \array_merge($this->included, $values),
                SORT_REGULAR
            )
        );
    }

    public function toArray(): array
    {
        if ([] === $this->included) {
            return [];
        }

        return [
            'included' => $this->included,
        ];
    }
}
