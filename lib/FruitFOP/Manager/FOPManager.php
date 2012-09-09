<?php

namespace FruitFOP\Manager;

use FruitFOP\Entity\SourceInterface;
use FruitFOP\Handler\ProcessorInterface;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Symfony\Component\Yaml\Parser;

class FOPManager
{
    protected $adapters;
    protected $processor;
    protected $sourceClass;
    protected $targetFileSystem;
    protected $tempFileSystem;

    public function __construct(ProcessorInterface $processor, Filesystem $targetFileSystem, Filesystem $tempFileSystem, $sourceClass)
    {
        $this->processor = $processor;
        $this->sourceClass = $sourceClass;
        $this->targetFileSystem = $targetFileSystem;
        $this->tempFileSystem = $tempFileSystem;
    }

    /**
     * Create a new Source class from some data. The data can be an array or an object. If there is a map passed in
     * only properties in the object that are specifically defined in the map are loaded into the new Source.
     *
     * @param $data
     * @param null $map
     *
     * @return \FruitFOP\Entity\SourceInterface
     */
    public function createSource($data, $map = null, $root = 'root')
    {
        if ($map) {
            $yaml = new Parser();
            $map = $yaml->parse(file_get_contents($map));
        }

        if (is_object($data)) {
            $dataClass = get_class($data);
            $rootName = isset($map[$dataClass]['root']) ? $map[$dataClass]['root'] : $dataClass;
        } else {
            $rootName = $root;
        }
        $root = sprintf('<%s/>', strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $rootName)));

        $mappedData = $this->extractData($data, $map);

        $source = new $this->sourceClass($root);
        $this->addDataToSource($mappedData, $source);

        return $source;
    }

    /**
     * @param \FruitFOP\Entity\SourceInterface $source
     * @param null $targetLocation
     * @param string $type
     *
     * @return \Gaufrette\File
     */
    public function generateDocument(SourceInterface $source, $targetName = null, $type = 'pdf')
    {
        $fileName = $targetName ? $targetName : ($source->getTargetName());
        if (strlen($fileName) === 0) {
            $fileName = uniqid(gethostname(), true);
        }
        $fileName .= '.' . $type;
        $xml = $this->tempFileSystem->createFile($fileName . '.xml');
        $xml->setContent($source->getXml());
        $xsl = $source->getXsl();
        if (!$xsl instanceof File) {
            $content = $xsl;
            $xsl = $this->tempFileSystem->createFile($fileName . '.xsl');
            $xsl->setContent($content);
        }
        $document = $this->targetFileSystem->createFile($fileName);
        $this->processor->generate($xml, $xsl, $document, $type);

        return $document;
    }

    protected function addDataToSource(array $data, $source)
    {
        foreach($data as $key => $value) {
            if (is_array($value)) {
                $child = $source->addChild($key);
                $this->addDataToSource($value, $child);
            } else {
                $source->addChild($key, $value);
            }
        }
    }

    protected function extractData($data, $map = null)
    {
        if (is_scalar($data)) {
            return (string)$data;
        }

        if (is_object($data)) {
            $dataClass = get_class($data);
            $extracted = $this->objectToArray($data);
            if (isset($map[$dataClass])) {
                $extracted = $this->remapData($extracted, $map[$dataClass]);
            }
        } else {
            $extracted = $data;
        }

        $mapped = array();
        foreach ($extracted as $attr => $value) {
            $key = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $attr));
            $mapped[$key] = $this->extractData($value, $map);
        }

        return $mapped;
    }

    protected function remapData(array $data, array $map)
    {
        $remapped = array();
        foreach ($data as $attr => $value) {
            if (isset($map['fields'][$attr])) {
                $mappedName = $map['fields'][$attr];
                $remapped[$mappedName] = $value;
            }
        }

        return $remapped;
    }

    protected function objectToArray($data)
    {
        $extracted = array();
        $rc = new \ReflectionClass($data);
        $properties = $rc->getProperties();
        foreach ($properties as $property) {
            $key = $property->getName();
            $property->setAccessible(true);
            $extracted[$key] = $property->getValue($data);
            $property->setAccessible(false);
        }

        return $extracted;
    }
}
