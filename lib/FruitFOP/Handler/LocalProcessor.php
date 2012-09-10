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
        $toDelete = array($targetFile);

        // if the xml or xsl are not on host, move them here
        $xmlAdapter = $xml->getFilesystem()->getAdapter();
        if ($xmlAdapter instanceof Local) {
            $xmlFile = $xmlAdapter->computePath($xml->getKey());
        } else {
            $xmlFile = $targetFile . '.xml';
            file_put_contents($xmlFile, $xml->getContent());
            $toDelete[] = $xmlFile;
        }        
        
        $xslAdapter = $xsl->getFilesystem()->getAdapter();
        if ($xslAdapter instanceof Local) {
            $xslFile = $xslAdapter->computePath($xsl->getKey());
        } else {
            $xslFile = $targetFile . '.xsl';
            file_put_contents($xslFile, $xsl->getContent());
            $toDelete[] = $xslFile;
        }

        $fopCmd = __DIR__ . '/../Resources/fop-1.0/fop';

        $cmd = sprintf("%s -xml %s -xsl %s -%s %s", $fopCmd, $xmlFile, $xslFile, $type, $targetFile);

        $process = new Process($cmd);
        $process->run();

        if ($process->getExitCode() !== 0) {
            if ($this->purge) {
                foreach ($toDelete as $fileName) {
                    try {
                        unlink($fileName);
                    } catch (\Exception $e) {
                        // if a file hasn't been created, continue on
                    }
                }
            }
            throw new \RuntimeException($process->getErrorOutput());
        }

        $handle = fopen($targetFile, 'r');
        $documentContents = stream_get_contents($handle);
        fclose($handle);
        $target->setContent($documentContents);

        if ($this->purge) {
            foreach ($toDelete as $fileName) {
                unlink($fileName);
            }
        }
    }
}
