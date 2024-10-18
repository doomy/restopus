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
     * @param array<string, string> $requestBody
     * @throws \InvalidArgumentException
     */
    public function getBodyEntity(array $requestBody, string $requestBodyClass): AbstractRequestEntity
    {
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
                        'Property %s in request body is required',
                        $reflectionProperty->getName()
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

            $propertyValue = $this->translatePropertyValue($requestBody[$propertyName], $propertyType->getName());
            $propertyTypeName = $propertyType->getName();
            if (gettype($propertyValue) instanceof $propertyTypeName) {
                var_dump($propertyValue);
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
            unset($requestBody[$propertyName]);
        }

        if (count($requestBody) > 0) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unexpected property: %s',
                    array_keys($requestBody)[0]
                )
            );
        }

        $this->cached[$requestBodyClass] = $bodyEntity;
        return $bodyEntity;
    }

    private function translatePropertyValue(mixed $propertyValue, string $propertyType): mixed {
        if ($propertyType !== 'DateTimeInterface' || ! is_array($propertyValue)) {
            return $propertyValue;
        }

        return new \DateTimeImmutable($propertyValue['date'], new \DateTimeZone($propertyValue['timezone']));
    }
}