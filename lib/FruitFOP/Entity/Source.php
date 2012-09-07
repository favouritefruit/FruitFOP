<?php

namespace FruitFOP\Entity;

use FruitFOP\Entity\SourceInterface;

class Source extends \SimpleXMLElement implements SourceInterface
{
    protected $adapter;
    protected $targetName;
    protected $xsl;

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
        return $this->asXML();
    }

    public function getXsl()
    {
        return $this->xsl;
    }
}
