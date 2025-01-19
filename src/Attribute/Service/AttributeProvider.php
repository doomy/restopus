<?php

namespace Doomy\Restopus\Attribute\Service;

use Doomy\Restopus\Attribute\Exception\AttributeNotFoundException;
use Doomy\Restopus\Request\Attribute\AbstractRestMethodAttribute;
use Doomy\Restopus\Request\Attribute\Route;

final class AttributeProvider
{
    /**
     * @var array<string, AbstractRestMethodAttribute[]>
     */
    private array $cached = [];

    /**
     * @return AbstractRestMethodAttribute[]
     */
    public function getMethodAttributes(\ReflectionMethod $reflectionMethod): array
    {
        $cacheKey = $reflectionMethod->getDeclaringClass()->getName() . '::' . $reflectionMethod->getName();
        if (isset($this->cached[$cacheKey])) {
            return $this->cached[$cacheKey];
        }

        $attributes = $reflectionMethod->getAttributes();
        $requestAttributes = [];
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof AbstractRestMethodAttribute) {
                $requestAttributes[] = $instance;
            }
        }
        return $this->cached[$cacheKey] = $requestAttributes;
    }

    /**
     * @throws AttributeNotFoundException
     */
    public function getRoute(\ReflectionMethod $reflectionMethod): Route
    {
        $attributes = $this->getMethodAttributes($reflectionMethod);
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Route) {
                return $attribute;
            }
        }

        throw new AttributeNotFoundException('Route attribute not found');
    }
}