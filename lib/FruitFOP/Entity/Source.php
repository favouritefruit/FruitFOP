<?php

namespace FruitFOP\Entity;

use FruitFOP\Entity\SourceInterface;

class Source implements SourceInterface
{
    protected $adapter;
    protected $targetName;
    protected $xml;
    protected $xsl;

    public function __construct($root)
    {
        $this->xml = new \SimpleXMLElement($root);
    }

    public function addChild($name, $value = null, $namespace = null)
    {
        return $this->xml->addChild($name, $value, $namespace);
    }

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function setTargetName($targetName)
    {
        $this->targetName = $targetName;
    }

    public function getTargetName()
    {
        return $this->targetName;
    }

    public function setXsl($xsl)
    {
        $this->xsl = $xsl;
    }

    public function getXml()
    {
        return $this->xml->asXML();
    }

    public function getXsl()
    {
        return $this->xsl;
    }
}
