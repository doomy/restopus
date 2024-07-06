<?php

namespace Doomy\Restopus\Request;

final class RequestBodyProvider
{
    /**
     * @var array<class-string, AbstractRequestEntity>
     */
    private array $cached = [];

    /**
     * @param class-string $requestBodyClass
     * @throws \InvalidArgumentException
     */
    public function getBodyEntity(mixed $requestBody, string $requestBodyClass): AbstractRequestEntity
    {
        if (! is_array($requestBody)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Request body should be an array, %s given',
                    gettype($requestBody)
                )
            );
        }

        if (isset($this->cached[$requestBodyClass])) {
            return $this->cached[$requestBodyClass];
        }

        $reflectionClass = new \ReflectionClass($requestBodyClass);
        $reflectionProperties = $reflectionClass->getProperties();

        /** @var AbstractRequestEntity $bodyEntity */
        $bodyEntity = new $requestBodyClass();

        foreach ($reflectionProperties as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            $propertyName = $reflectionProperty->getName();
            if (! array_key_exists($propertyName, $requestBody)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Property %s in request body %s is required',
                        $reflectionProperty->getName(),
                        $requestBodyClass
                    )
                );
            }

            $propertyType = $reflectionProperty->getType();

            if ($propertyType === null || ! method_exists($propertyType, 'getName')) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Property %s in request body %s should have a type',
                        $reflectionProperty->getName(),
                        $requestBodyClass
                    )
                );
            }

            $propertyValue = $requestBody[$propertyName];
            if (gettype($propertyValue) !== $propertyType->getName()) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Property %s in request body %s should be of type %s, %s given',
                        $reflectionProperty->getName(),
                        $requestBodyClass,
                        $propertyType->getName(),
                        gettype($propertyValue)
                    )
                );
            }

            $bodyEntity->{$propertyName} = $propertyValue;
        }

        $this->cached[$requestBodyClass] = $bodyEntity;
        return $bodyEntity;
    }
}