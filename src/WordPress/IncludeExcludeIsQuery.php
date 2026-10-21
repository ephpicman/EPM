<?php

declare(strict_types=1);

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

class IncludeExcludeIsQuery extends IncludeExcludeQuery
{
    protected bool $multiValue = false;

    protected string|array|null $is = null;

    public function __construct(
        array $queryArray = [],
        int $type = self::INTEGER,
        bool $multiValue = false
    ) {
        $this->multiValue = $multiValue;

        parent::__construct($queryArray, $type);

        if (\array_key_exists('is', $queryArray)) {
            $this->is($queryArray['is']);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['type'] ?? self::INTEGER,
            $args['multiValue'] ?? false
        );
    }

    public function is(string|array $value): self
    {
        if (false === $this->multiValue) {
            if (!\is_string($value)) {
                throw new \InvalidArgumentException(
                    'Is value must be a string in single-value mode.'
                );
            }

            $this->is = '' === $value ? null : $value;

            return $this;
        }

        if (!\is_array($value)) {
            throw new \InvalidArgumentException(
                'Is value must be an array in multi-value mode.'
            );
        }

        $value = \array_values(
            \array_unique(
                \array_filter(
                    $value,
                    static function ($item): bool {
                        if (!\is_string($item)) {
                            throw new \InvalidArgumentException(
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

    public function toArray(): array
    {
        $output = parent::toArray();

        if (null !== $this->is) {
            $output['is'] = $this->is;
        }

        return $output;
    }
}