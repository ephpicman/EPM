<?php

declare(strict_types=1);

namespace EphpicMan\Framework\WordPress;

use EphpicMan\Framework\Utils\ArrayUtil;
use EphpicMan\Framework\Utils\NumberUtil;

\defined('ABSPATH') || exit;

class IncludeQueryArg implements QueryArg
{

    protected bool $isIdentifier = true;

    protected string $includeKey;

    protected array $included = [];

    public function __construct(
        array $queryArray = [],
        bool $isIdentifier = true,
        string $includeKey = 'included'
    ) {
        $this->isIdentifier = $isIdentifier;
        $this->includeKey = $includeKey;

        if (isset($queryArray[$this->includeKey])) {
            $this->include((array) $queryArray[$this->includeKey]);
        }
    }

    public static function make(array $queryArray = [], array $args = []): static
    {
        return new static(
            $queryArray,
            $args['isIdentifier'] ?? true,
            $args['includeKey'] ?? 'included'
        );
    }

    protected function cast(int|float|string $value): int|string
    {
        return true === $this->isIdentifier ? NumberUtil::absint($value) : (string) $value;
    }

    public function include(int|float|string|array $value): static
    {
        $this->mergeIncluded(
            ArrayUtil::castValues($value, fn($item) => $this->cast($item))
        );

        return $this;
    }

    public function toArray(): array
    {
        if ([] === $this->included) {
            return [];
        }

        return [
            $this->includeKey => $this->included,
        ];
    }

    protected function mergeIncluded(array $values): void
    {
        $this->included = ArrayUtil::mergeUnique($this->included, $values);
    }
}
