<?php

declare(strict_types=1);

namespace EPM\WordPress;

/*
 * Copyright (c) Sina Kuhestani (EphpicMan).
 * SPDX-License-Identifier: MIT
 */

\defined('ABSPATH') || exit;

interface QueryArg
{

    public static function make(array $queryArray = [], array $args = []): static;

    public function toArray(): array;
}
