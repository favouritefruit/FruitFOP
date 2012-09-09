<?php

namespace FruitFOP\Entity;

interface SourceInterface
{
    function setAdapter($adapter);

    function getAdapter();

    function setTargetName($targetName);

    function getTargetName();

    function setXsl($xsl);

    function getXml();

    function getXsl();
}
