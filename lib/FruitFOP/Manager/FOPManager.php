<?php

namespace FruitFOP\Manager;

use FruitFOP\Entity\SourceInterface;
use Symfony\Component\Yaml\Parser;

class FOPManager
{
    protected $sourceClass;

    public function __construct($sourceClass)
    {
        $this->sourceClass = $sourceClass;
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
    public function createSource($data, $map = null)
    {
        if ($map) {
            $yaml = new Parser();
            $map = $yaml->parse(file_get_contents($map));
        }

        if (is_object($data)) {
            $dataClass = get_class($data);
            $rootName = isset($map[$dataClass]['root']) ? $map[$dataClass]['root'] : $dataClass;
        } else {
            $rootName = 'root';
        }
        $root = sprintf('<%s/>', strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $rootName)));

        $mappedData = $this->extractData($data, $map);

        $source = new $this->sourceClass($root);
        $this->addDataToSource($mappedData, $source);

        return $source;
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

    public function generateDocument(SourceInterface $source, $targetLocation = null, $type = 'pdf')
    {

    }
}
