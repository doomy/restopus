<?php

namespace Doomy\Restopus\Response\Service;

use Doomy\Repository\Model\Entity;
use Doomy\Restopus\Response\AbstractResponseEntity;

final readonly class EntityViewResponseMapper
{
    /**
     * @param class-string<AbstractResponseEntity> $viewClass
     * @return array<string, mixed>
     */
    public function mapEntityToResponse(Entity $entity, string $viewClass): array
    {
        $reflection = new \ReflectionClass($viewClass);
        $properties = $reflection->getProperties();

        $data = [];
        foreach ($properties as $property) {
            $propertyName = $property->getName();
            $getter = 'get' . ucfirst($propertyName);
            if (method_exists($entity, $getter)) {
                $data[$propertyName] = $entity->{$getter}();
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapViewRoResponse(AbstractResponseEntity $view): array
    {
        $reflection = new \ReflectionClass($view);
        $properties = $reflection->getProperties();

        $data = [];
        foreach ($properties as $property) {
            if ($property->isPublic()) {
                $propertyName = $property->getName();
                $data[$propertyName] = $view->{$propertyName};
            }
        }

        return $data;
    }
}