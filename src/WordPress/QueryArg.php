<?php

/*
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ EPM — EphpicMan Framework                                                                                            │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│ This file is part of EPM, the EphpicMan Framework.                                                                   │
│                                                                                                                      │
│ Copyright (c) Sina Kuhestani (EphpicMan).                                                                            │
│ Licensed under the MIT License. See the LICENSE file for details.                                                    │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
*/

declare(strict_types=1);

namespace EPM\\WordPress;

/**
 * Contract for objects whose purpose is to build the primary argument array for a WordPress query API.
 *
 * Implementations represent arguments for a particular WordPress query type, such as WP_Query,
 * WP_User_Query, or another WordPress query API. They collect and normalize the query options and
 * expose them as an argument array that can be passed to the corresponding WordPress API.
 *
 * This interface only defines argument construction. It does not create, execute, or return the
 * results of a WordPress query object.
 */
interface QueryArg
{
    /**
     * Creates an argument-builder instance from the supplied query data.
     *
     * @param array $queryArray Initial query options.
     * @param array $args       Additional arguments used by the implementation.
     * @return static           An instance of the concrete argument-builder class.
     */
    public static function make(array $queryArray = [], array $args = []): static;

    /**
     * Builds and returns the primary argument array for the corresponding WordPress query API.
     *
     * The returned array is intended to be passed to that API, for example to the constructor of
     * WP_Query or WP_User_Query. It is not a result set and this method does not execute the query.
     *
     * @return array<string, mixed> WordPress query arguments.
     */
    public function toArray(): array;
}
