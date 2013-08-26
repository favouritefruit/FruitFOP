<?php

use FruitFOP\Handler\LocalProcessor;
use FruitFOP\Manager\FOPManager;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Gaufrette\Adapter\Local;

class FOPManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $mgr;
    protected $targetFileSystem;
    protected $tempFileSystem;

    public function testCreateSource()
    {
        $data = new DataClass('alpha', new Beta(), array('delta' => 'gamma'));

        // test no mapping
        $source = $this->mgr->createSource($data);
        $expected =
'<?xml version="1.0"?>
<data-class><alpha>alpha</alpha><beta><bravo>bravo</bravo><charlie>charlie</charlie></beta><gamma><delta>gamma</delta></gamma><not-used>ever</not-used></data-class>';

        $this->assertSame($expected, trim($source->getXML()));

        // test mapping
        $mapping = __DIR__ . '/../Resources/DataClass.yml';
        $source = $this->mgr->createSource($data, $mapping);
        $expected =
'<?xml version="1.0"?>
<data><name>alpha</name><description><title>bravo</title><body>charlie</body></description><notes><delta>gamma</delta></notes></data>';
        $this->assertSame($expected, trim($source->getXML()));

        // test mapping with array
        $mapping = array(
            'DataClass' => array(
                'root' => 'base',
                'fields' => array(
                    'alpha' => 'group',
                    'beta'  => 'sets',
                    'gamma' => 'extra',
                ),
            ),
            'Beta' => array(
                'fields' => array(
                    'bravo'   => 'first',
                    'charlie' => 'second',
                ),
            ),
        );
        $source = $this->mgr->createSource($data, $mapping);
        $expected =
            '<?xml version="1.0"?>
<base><group>alpha</group><sets><first>bravo</first><second>charlie</second></sets><extra><delta>gamma</delta></extra></base>';
        $this->assertSame($expected, trim($source->getXML()));
    }

    public function testGenerateDocument()
    {
        $data = array(
            'name' => 'test',
            'description' => 'testing the document being generated'
        );
        $source = $this->mgr->createSource($data, null, 'data');

        $layout = __DIR__ . '/../Resources/layout.xsl';
        $handle = fopen($layout, 'r');
        $xsl = stream_get_contents($handle);
        fclose($handle);

        $source->setXsl($xsl);
        $source->setTargetName('my-target');

        $document = $this->mgr->generateDocument($source);

        $this->assertInstanceOf('Gaufrette\File', $document);
        $this->assertSame('my-target.pdf', $document->getKey());
        $this->assertTrue($this->isPdf($document->getContent()));

        $document = $this->mgr->generateDocument($source, 'different');
        $this->assertSame('different.pdf', $document->getKey(), 'targetName in parameters overrides targetName in Source');

        $xslFile = $this->tempFileSystem->createFile('my-target.xsl');
        $xslFile->setContent($xsl);
        $source->setXsl($xslFile);

        $document = $this->mgr->generateDocument($source);
        $this->assertInstanceOf('Gaufrette\File', $document, ' xsl can be string or Gaufrette\File');

        $source->setTargetName(null);
        $document1 = $this->mgr->generateDocument($source);
        $document2 = $this->mgr->generateDocument($source);
        $this->assertNotSame($document1->getKey(), $document2->getKey(), 'no targetName in parameters and no targetName in Source generate id unique to host computer');
    }

    public function setUp()
    {
        $this->targetFileSystem = new Gaufrette\Filesystem(new Gaufrette\Adapter\InMemory());
        $this->tempFileSystem = new Gaufrette\Filesystem(new Gaufrette\Adapter\InMemory());
        $tempFolder = __DIR__ . '/../Resources/temp';
        $processor = new LocalProcessor($tempFolder);

        $this->mgr = new FOPManager($processor, $this->targetFileSystem, $this->tempFileSystem, '\FruitFOP\Entity\Source');
    }

    protected function isPdf($pdf)
    {
        $pdfHeader = "\x25\x50\x44\x46\x2D";

        return strncmp($pdf, $pdfHeader, strlen($pdfHeader)) === 0 ? true : false;
    }
}

class DataClass
{
    protected $alpha;
    public $beta;
    private $gamma;
    private $notUsed = 'ever';

    public function __construct($alpha, $beta, array $gamma)
    {
        $this->alpha = $alpha;
        $this->beta = $beta;
        $this->gamma = $gamma;
    }
}

class Beta
{
    protected $bravo;
    protected $charlie;

    public function __construct()
    {
        $this->bravo = 'bravo';
        $this->charlie = 'charlie';
    }
}
