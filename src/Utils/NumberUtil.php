<?php
declare(strict_types=1);
namespace EphpicMan\Framework\Utils;

final class NumberUtil
{
    public static function absint(int|float|string $value): int
    {
        return (int) abs((float) $value);
    }
}