<?php

namespace Rector\Tests\Rector\MethodCall\MethodNameReplacerRector\Fixture;

use Rector\Tests\Rector\MethodCall\MethodNameReplacerRector\Source\AbstractType;
use Rector\Tests\Rector\MethodCall\MethodNameReplacerRector\Source\Something;

class MyType6 extends AbstractType
{
    public function setDefaultOptions(SomeResolver $resolver)
    {
        $something = new Something();
        $something->setDefaultOptions();

        parent::setDEFAULTOptions($resolver);
    }
}

?>
-----
<?php

namespace Rector\Tests\Rector\MethodCall\MethodNameReplacerRector\Fixture;

use Rector\Tests\Rector\MethodCall\MethodNameReplacerRector\Source\AbstractType;
use Rector\Tests\Rector\MethodCall\MethodNameReplacerRector\Source\Something;

class MyType6 extends AbstractType
{
    public function configureOptions(SomeResolver $resolver)
    {
        $something = new Something();
        $something->setDefaultOptions();

        parent::configureOptions($resolver);
    }
}

?>
