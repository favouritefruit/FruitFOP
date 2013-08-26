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
        if (!file_exists($tempFolder)) {
            mkdir($tempFolder, 0777, true);
        }
        $localFileSystem = new Filesystem(new Local($tempFolder));
        $memoryFileSystem = new Filesystem(new InMemory());
        $xml = $localFileSystem->createFile('test.xml');
        $xml->setContent($xmlContent);
        $xsl = $localFileSystem->createFile('test.xsl');
        $xsl->setContent($xslContent);
        $document = $memoryFileSystem->createFile('test.pdf');
        $processor = new LocalProcessor($tempFolder, false);

        $processor->generate($xml, $xsl, $document, 'pdf');
        $this->assertTrue($this->isPdf($document->getContent()));
        $scanTemp = scandir($tempFolder);
        $this->assertContains('test.pdf', $scanTemp, 'the purge is turned off so the generated file should be there');
        $this->assertContains('test.xml', $scanTemp, 'only files created by processor should be removed');
        $this->assertContains('test.xsl', $scanTemp, 'only files created by processor should be removed');

        // flush temp for next test
        unlink($tempFolder . '/test.pdf');
        unlink($tempFolder . '/test.xml');

        // test non-local Files
        $xml = $memoryFileSystem->createFile('test.xml');
        $xml->setContent($xmlContent);
        $processor = new LocalProcessor($tempFolder);

        $processor->generate($xml, $xsl, $document, 'pdf');
        $this->assertTrue($this->isPdf($document->getContent()));
        $scanTemp = scandir($tempFolder);
        $this->assertNotContains('test.pdf', $scanTemp, 'the purge is on by default, temp file should be removed');
        $this->assertNotContains('test.xml', $scanTemp, 'xml was not local, so a temp was created and then removed during purge');
        $this->assertContains('test.xsl', $scanTemp, 'only files created by processor should be removed');

        // flush temp for next test
        unlink($tempFolder . '/test.xsl');

        $xsl = $memoryFileSystem->createFile('test.xsl');
        $xsl->setContent($xslContent);

        $processor->generate($xml, $xsl, $document, 'pdf');
        $this->assertTrue($this->isPdf($document->getContent()));
        $this->assertNotContains('test.xsl', scandir($tempFolder), 'xsl was not local, so a temp was created and then removed during purge');

        $xsl->setContent('fail!');
        $this->setExpectedException(
            'RuntimeException'
        );
        $processor->generate($xml, $xsl, $document, 'pdf');

        rmdir($tempFolder);
    }

    protected function isPdf($pdf)
    {
        $pdfHeader = "\x25\x50\x44\x46\x2D";

        return strncmp($pdf, $pdfHeader, strlen($pdfHeader)) === 0 ? true : false;
    }
}
