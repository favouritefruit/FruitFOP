<?php

namespace FruitFOP\Handler;

use Gaufrette\File;

interface ProcessorInterface
{
    function generate(File $source, File $target, $type);
}
