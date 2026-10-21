<?php

 declare(strict_types=1);

namespace EPM\WordPress;

use EPM\Utils\ArrayUtil;

\defined('ABSPATH') || exit;

/**
 * Builds an include list for WordPress query arguments.
 *
 * Values can be normalized as absolute integers or strings, depending on the
 * configured type. Duplicate values are removed while preserving their order.
 */
class IncludeQuery extends Query
{
    /** Normalize included values as integers. */
    public const INTEGER = 1;

    /** Normalize included values as strings. */
    public const STRING = 2;

    /** The normalization type used for included values. */
    protected int $type = self::INTEGER;

    /** The normalized, unique values to include in the query. */
    protected array $included = [];

    /**
     * Cast a value according to the configured normalization type.
     *
     * Integer mode uses WordPress's absint() helper; string mode converts the
     * value to a string.
     *
     * @param int|float|string $value Value to normalize.
     * @return int|string The normalized value.
     */
    protected function cast(int|float|string $value): int|string
    {
        return self::INTEGER === $this->type
            ? \\absint($value)
            : (string) $value;
    }

    /**
     * Create an include query.
     *
     * @param array $queryArray Initial query data; the 'included' key is read when present.
     * @param int   $type       Normalization type; must be INTEGER or STRING.
     *	hrows \\InvalidArgumentException If the normalization type is invalid.
     */
    public function __construct(array $queryArray = [], int $type = self::INTEGER)
    {
        if (!\\in_array($type, [self::INTEGER, self::STRING], true)) {
            throw new \\InvalidArgumentException('Invalid include type.');
        }

        $this->type = $type;

        if (isset($queryArray['included'])) {
            $this->include((array) $queryArray['included']);
        }
    }

    /**
     * Create an instance from query data and optional configuration.
     *
     * The normalization type may be provided in $args['type']; it defaults to INTEGER.
     *
     * @param array $queryArray Initial query data.
     * @param array $args       Optional configuration, including 'type'.
     * @return static A new instance of the called class.
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static($queryArray, $args['type'] ?? self::INTEGER);
    }

    /**
     * Add one or more values to the include list.
     *
     * Each value is normalized according to the configured type. Duplicate
     * values are removed, while existing values retain their order.
     *
     * @param int|float|string|array $value A single value or a list of values.
     * @return self The current instance for method chaining.
     */
    public function include(int|float|string|array $value): self
    {
        $this->mergeIncluded(
            ArrayUtil::castValues($value, [$this, 'cast'])
        );

        return $this;
    }

    /**
     * Merge normalized values into the current include list, removing duplicates.
     *
     * @param array $values Values that have already been normalized.
     */
    protected function mergeIncluded(array $values): void
    {
        $this->included = \\array_values(
            \\array_unique(
                \\array_merge($this->included, $values),
                SORT_REGULAR
            )
        );
    }

    /**
     * Convert the include query to its array representation.
     *
     * Returns an empty array when no values have been added; otherwise returns
     * the 'included' key with the normalized values.
     *
     * @return array The query data represented by this instance.
     */
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
