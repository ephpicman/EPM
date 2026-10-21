<?php

declare(strict_types=1);

namespace EPM\\WordPress;

/**
 * Base contract for query objects that represent WordPress query arguments.
 *
 * Concrete query classes define how query data is created and how it is
 * converted into the array format expected by the relevant WordPress API.
 */
abstract class Query
{
    /**
     * Create a query object from query data and additional arguments.
     *
     * Concrete implementations determine how the supplied arrays are
     * interpreted and which values are accepted.
     *
     * @param array $queryArray Query data used to initialize the query object.
     * @param array $args       Additional arguments used by the implementation.
     *
     * @return static An instance of the concrete query class.
     */
    abstract public static function make(array $queryArray = [], array $args = []): static;

    /**
     * Convert the query object into an array of query arguments.
     *
     * The concrete implementation is responsible for mapping its properties
     * to the argument names and structure expected by the target WordPress API.
     *
     * @return array The query arguments represented by this object.
     */
    abstract public function toArray(): array;
}
