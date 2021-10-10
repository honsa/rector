<?php

declare (strict_types=1);
namespace RectorPrefix20211010\Symplify\PackageBuilder\Configuration;

final class StaticEolConfiguration
{
    public static function getEolChar() : string
    {
        return "\n";
    }
}
