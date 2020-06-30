<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 23.11.2018
 * Time: 15:32
 */

namespace Roadinfo\Repositories;

class Hydrator
{
    private $reflectionClassMap;

    public function hydrate($class, array $data)
    {
        $reflection = $this->getReflectionClass($class);
        $target = $reflection->newInstanceWithoutConstructor();
        foreach ($data as $name => $value) {
            $property = $reflection->getProperty($name);
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }
            $property->setValue($target, $value);
        }
        return $target;
    }

    private function getReflectionClass($className)
    {
        if (!isset($this->reflectionClassMap[$className])) {
            $this->reflectionClassMap[$className] = new \ReflectionClass($className);
        }
        return $this->reflectionClassMap[$className];
    }
}