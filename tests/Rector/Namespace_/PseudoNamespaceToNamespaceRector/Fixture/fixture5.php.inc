<?php

namespace ChangeMe_ToNamespaced;

use PHPUnit_TestCase;

class SomeTestCase
{
    /**
     * @return \ChangeMe_AnotherNamespace
     */
    public function someMethod(): \Keep_ThisThough
    {
        if ($this instanceof PHPUnit_TestCase) {
            return true;
        }
    }
}

?>
-----
<?php

namespace ChangeMe\ToNamespaced;

use PHPUnit\TestCase;

class SomeTestCase
{
    /**
     * @return \ChangeMe_AnotherNamespace
     */
    public function someMethod(): \Keep_ThisThough
    {
        if ($this instanceof \PHPUnit\TestCase) {
            return true;
        }
    }
}

?>
