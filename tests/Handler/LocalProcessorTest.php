<?php

use FruitFOP\Entity\Source;

use FruitFOP\Handler\LocalProcessor;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Gaufrette\Adapter\Local;

class LocalProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testGenerate()
    {
        $xmlContent = '<?xml version="1.0"?><data><name>test</name><description>testing the document being generated</description></data>';

        $layout = __DIR__ . '/../Resources/layout.xsl';
        $handle = fopen($layout, 'r');
        $xslContent = stream_get_contents($handle);
        fclose($handle);

        $tempFolder = __DIR__ . '/../Resources/temp';
        $localFileSystem = new Filesystem(new Local($tempFolder));
        $memoryFileSystem = new Filesystem(new InMemory());
        $xml = $localFileSystem->createFile('test.xml');
        $xml->setContent($xmlContent);
        $xsl = $localFileSystem->createFile('test.xsl');
        $xsl->setContent($xslContent);
        $document = $memoryFileSystem->createFile('test.pdf');

        $processor = new LocalProcessor($tempFolder);
        $processor->generate($xml, $xsl, $document, 'pdf');

        $this->assertTrue($this->isPdf($document->getContent()));

        // test non-local Files

        // test purge

        $xsl->setContent('fail!');
        $this->setExpectedException(
            'RuntimeException'
        );
        $processor->generate($xml, $xsl, $document, 'pdf');


    }

    protected function isPdf($pdf)
    {
        $pdfHeader = "\x25\x50\x44\x46\x2D";

        return strncmp($pdf, $pdfHeader, strlen($pdfHeader)) === 0 ? true : false;
    }
}
