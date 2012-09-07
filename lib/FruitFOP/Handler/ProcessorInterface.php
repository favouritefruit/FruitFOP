<?php

namespace FruitFOP\Handler;

use Gaufrette\File;

interface ProcessorInterface
{
    public function generate(File $xml, File $xsl, File $target, $type);
}
