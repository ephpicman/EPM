<?php

declare(strict_types=1);

namespace EPM\WordPress;

use EPM\Utils\ArrayUtil;

\defined('ABSPATH') || exit;

class IncludeExcludeQuery extends IncludeQuery
{
    protected array $excluded = [];

    public function __construct(array $queryArray = [], int $type = self::INTEGER)
    {
        parent::__construct($queryArray, $type);

        if (isset($queryArray['excluded'])) {
            $this->exclude((array) $queryArray['excluded']);
        }
    }

    public function include(int|float|string|array $value): self
    {
        $values = ArrayUtil::castValues($value, [$this, 'cast']);

        $this->mergeIncluded($values);

        $this->excluded = ArrayUtil::removeValues(
            $this->excluded,
            $values
        );

        return $this;
    }

    public function exclude(int|float|string|array $value): self
    {
        $values = ArrayUtil::castValues($value, [$this, 'cast']);

        $this->excluded = \array_values(
            \array_unique(
                \array_merge($this->excluded, $values),
                SORT_REGULAR
            )
        );

        $this->included = ArrayUtil::removeValues(
            $this->included,
            $values
        );

        return $this;
    }

    public function toArray(): array
    {
        $output = parent::toArray();

        if ([] !== $this->excluded) {
            $output['excluded'] = $this->excluded;
        }

        return $output;
    }
}