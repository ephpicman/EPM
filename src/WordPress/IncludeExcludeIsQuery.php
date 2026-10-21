<?php

declare(strict_types=1);

namespace EPM\\WordPress;

/**
 * Builds a query value with optional include/exclude lists and an additional
 * string value or list of string values under the 'is' key.
 *
 * The 'is' value can be configured for single-value or multi-value mode.
 * Include/exclude behavior is inherited from IncludeExcludeQuery.
 */
\\defined('ABSPATH') || exit;

class IncludeExcludeIsQuery extends IncludeExcludeQuery
{
    /**
     * Whether the 'is' value accepts a list of strings instead of one string.
     */
    protected bool $multiValue = false;

    /**
     * The optional value or values associated with the 'is' query key.
     *
     * @var string|array<int, string>|null
     */
    protected string|array|null $is = null;

    /**
     * Creates the query and initializes its configuration and supplied values.
     *
     * @param array $queryArray Initial query data. The 'is' key initializes the
     *                          additional value; include/exclude keys are handled
     *                          by the parent class.
     * @param int   $type       Casting mode used by the inherited include/exclude
     *                          lists, such as INTEGER or STRING.
     * @param bool  $multiValue Whether the 'is' value must be an array of strings.
     */
    public function __construct(
        array $queryArray = [],
        int $type = self::INTEGER,
        bool $multiValue = false
    ) {
        $this->multiValue = $multiValue;

        parent::__construct($queryArray, $type);

        if (\\array_key_exists('is', $queryArray)) {
            $this->is($queryArray['is']);
        }
    }

    /**
     * Creates a new instance using query data and optional configuration arguments.
     *
     * Supported arguments are 'type' for the inherited value-casting mode and
     * 'multiValue' to configure whether the 'is' value accepts multiple strings.
     *
     * @param array $queryArray Initial query data.
     * @param array $args       Optional configuration; supports 'type' and 'multiValue'.
     *
     * @return static A new instance of the called class.
     */
    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['type'] ?? self::INTEGER,
            $args['multiValue'] ?? false
        );
    }

    /**
     * Sets the additional 'is' value.
     *
     * In single-value mode, only a string is accepted. In multi-value mode,
     * only an array containing strings is accepted. Empty strings are discarded;
     * multi-value input is also deduplicated and reindexed. If no values remain,
     * the stored value becomes null and is omitted from the serialized query.
     *
     * @param string|array<int, string> $value A string or a list of strings,
     *                                         according to the configured mode.
     *
     * @return self The current query instance for method chaining.
     *
     * @throws \\InvalidArgumentException If the value does not match the configured
     *                                     mode or contains a non-string item.
     */
    public function is(string|array $value): self
    {
        if (false === $this->multiValue) {
            if (!\\is_string($value)) {
                throw new \\InvalidArgumentException(
                    'Is value must be a string in single-value mode.'
                );
            }

            $this->is = '' === $value ? null : $value;

            return $this;
        }

        if (!\\is_array($value)) {
            throw new \\InvalidArgumentException(
                'Is value must be an array in multi-value mode.'
            );
        }

        $value = \\array_values(
            \\array_unique(
                \\array_filter(
                    $value,
                    static function ($item): bool {
                        if (!\\is_string($item)) {
                            throw new \\InvalidArgumentException(
                                'Every is value must be a string.'
                            );
                        }

                        return '' !== $item;
                    }
                ),
                SORT_STRING
            )
        );

        $this->is = [] === $value ? null : $value;

        return $this;
    }

    /**
     * Returns the query state as an array.
     *
     * Includes the inherited include/exclude data and adds the 'is' key only
     * when a non-empty value has been set.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $output = parent::toArray();

        if (null !== $this->is) {
            $output['is'] = $this->is;
        }

        return $output;
    }
}