<?php

declare(strict_types=1);

namespace EPM\WordPress;

abstract class Query
{
    abstract public static function make(array $queryArray = [], array $args = []): static;
    abstract public function toArray(): array;
}