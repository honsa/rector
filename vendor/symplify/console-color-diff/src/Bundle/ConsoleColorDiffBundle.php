<?php

declare (strict_types=1);
namespace RectorPrefix20211021\Symplify\ConsoleColorDiff\Bundle;

use RectorPrefix20211021\Symfony\Component\HttpKernel\Bundle\Bundle;
use RectorPrefix20211021\Symplify\ConsoleColorDiff\DependencyInjection\Extension\ConsoleColorDiffExtension;
final class ConsoleColorDiffBundle extends \RectorPrefix20211021\Symfony\Component\HttpKernel\Bundle\Bundle
{
    protected function createContainerExtension() : ?\RectorPrefix20211021\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
    {
        return new \RectorPrefix20211021\Symplify\ConsoleColorDiff\DependencyInjection\Extension\ConsoleColorDiffExtension();
    }
}
