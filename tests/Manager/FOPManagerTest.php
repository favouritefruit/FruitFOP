<?php

use FruitFOP\Manager\FOPManager;

class FOPManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateSource()
    {
        $mgr = $this->createFOPManager();
        $data = new DataClass('alpha', new Beta(), array('delta' => 'gamma'));

        // test no mapping
        $source = $mgr->createSource($data);
        $expected =
'<?xml version="1.0"?>
<data-class><alpha>alpha</alpha><beta><bravo>bravo</bravo><charlie>charlie</charlie></beta><gamma><delta>gamma</delta></gamma><not-used>ever</not-used></data-class>';

        $this->assertSame($expected, trim($source->asXML()));

        // test mapping
        $mapping = __DIR__ . '/../Resources/DataClass.yml';
        $source = $mgr->createSource($data, $mapping);
        $expected = <<<'EXPECTED'
<?xml version="1.0"?>
<root><name>alpha
    </name>
    <description>
        <title>
            bravo
        </title>
        <body>
            charlie
        </body>
    </description>
    <notes>
        <delta>
            gamma
        </delta>
    </notes>
</root>
EXPECTED;
        $this->assertSame($expected, $source->asXML());
    }

    protected function createFOPManager()
    {
        return new FOPManager('\FruitFOP\Entity\Source');
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
