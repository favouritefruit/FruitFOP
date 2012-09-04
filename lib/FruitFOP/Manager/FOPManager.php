<?php

namespace FruitFOP\Manager;

use FruitFOP\Entity\SourceInterface;

class FOPManager
{
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

    }

    public function generateDocument(SourceInterface $source, $type = 'pdf', $targetLocation = null)
    {

    }
}
