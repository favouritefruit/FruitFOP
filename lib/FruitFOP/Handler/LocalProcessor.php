<?php

namespace FruitFOP\Handler;

use Gaufrette\Adapter\Local;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Symfony\Component\Process\Process;

class LocalProcessor implements ProcessorInterface
{
    protected $purge;
    protected $tempFolder;

    public function __construct($tempFolder, $purge = true)
    {
        $this->purge = $purge;
        $this->tempFolder = $tempFolder;
    }

    public function generate(File $xml, File $xsl, File $target, $type)
    {
        $targetFile = rtrim($this->tempFolder, '/ ') . '/' . $target->getKey();

        // if the xml or xsl are not on host, move them here
        $adapter = $xml->getFilesystem()->getAdapter();

        $xmlFile = $adapter->computePath($xml->getKey());
        $xslFile = $adapter->computePath($xsl->getKey());

        $fopCmd = __DIR__ . '/../Resources/fop-1.0/fop';

        $cmd = sprintf("%s -xml %s -xsl %s -%s %s", $fopCmd, $xmlFile, $xslFile, $type, $targetFile);

        $process = new Process($cmd);
        $process->run();

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        $handle = fopen($targetFile, 'r');
        $documentContents = stream_get_contents($handle);
        fclose($handle);
        $target->setContent($documentContents);
    }
}
