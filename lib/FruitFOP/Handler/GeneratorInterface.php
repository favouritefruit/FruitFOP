<?php

namespace FruitFOP\Handler;

use FruitFOP\Entity\SourceInterface;

interface GeneratorInterface
{
    function generate(SourceInterface $source, $target, $type);
}
