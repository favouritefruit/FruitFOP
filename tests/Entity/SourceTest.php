<?php

use FruitFOP\Entity\Source;

class SourceTest extends \PHPUnit_Framework_TestCase
{
    public function testXml()
    {
        $source = new Source('custom');
        $expected =
'<?xml version="1.0"?>
<custom/>';
        $this->assertSame($expected, trim($source->getXml()));

        $source = new Source();
        $expected =
'<?xml version="1.0"?>
<root/>';
        $this->assertSame($expected, trim($source->getXml()));

        $child = $source->addChild('child', 'little one');
        $this->assertInstanceOf('\SimpleXMLElement', $child);

        $expected =
'<?xml version="1.0"?>
<root><child>little one</child></root>';
        $this->assertSame($expected, trim($source->getXml()));
    }
}
