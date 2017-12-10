<?php

class ContainerTest extends \PHPUnit\Framework\TestCase
{
    private $calculator;

    protected function setUp()
    {
        $this->calculator = new \True\DI\Container();
    }

    protected function tearDown()
    {
        $this->calculator = null;
    }

    public function testAdd()
    {
        $result = $this->calculator->add(1, 2);
        $this->assertEquals(3, $result);
    }

}
