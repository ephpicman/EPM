<?php

declare(strict_types=1);

namespace EPM\WordPress;

\defined('ABSPATH') || exit;

interface QueryArg
{
    public static function make(array $queryArray = [], array $args = []): static;

    public function toArray(): array;
}
