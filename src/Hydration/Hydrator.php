<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Hydration;

use ReflectionClass;
use SmrtSystems\Couch\Exception\HydrationException;
use Throwable;

/**
 * Handles object instantiation and property access via reflection.
 */
final class Hydrator
{
    /** @var array<class-string, ReflectionClass<object>> */
    private array $reflectionCache = [];

    /**
     * Create and populate an object instance.
     *
     * @template T of object
     * @param class-string<T> $className
     * @param array<string, mixed> $values Property name => value
     * @return T
     */
    public function hydrate(string $className, array $values): object
    {
        try {
            $reflection = $this->getReflection($className);
            $instance = $reflection->newInstanceWithoutConstructor();

            foreach ($values as $propertyName => $value) {
                if (!$reflection->hasProperty($propertyName)) {
                    continue;
                }

                $property = $reflection->getProperty($propertyName);
                $property->setValue($instance, $value);
            }

            return $instance;
        } catch (HydrationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw HydrationException::cannotInstantiate($className, $e);
        }
    }

    /**
     * Extract property values from an object.
     *
     * @return array<string, mixed>
     */
    public function extract(object $document): array
    {
        $reflection = $this->getReflection($document::class);
        $values = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isInitialized($document)) {
                $values[$property->getName()] = $property->getValue($document);
            }
        }

        return $values;
    }

    /**
     * Get the value of a specific property from an object.
     */
    public function getValue(object $document, string $propertyName): mixed
    {
        $reflection = $this->getReflection($document::class);

        if (!$reflection->hasProperty($propertyName)) {
            return null;
        }

        $property = $reflection->getProperty($propertyName);

        if (!$property->isInitialized($document)) {
            return null;
        }

        return $property->getValue($document);
    }

    /**
     * Set the value of a specific property on an object.
     */
    public function setValue(object $document, string $propertyName, mixed $value): void
    {
        $reflection = $this->getReflection($document::class);

        if (!$reflection->hasProperty($propertyName)) {
            return;
        }

        $property = $reflection->getProperty($propertyName);
        $property->setValue($document, $value);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return ReflectionClass<T>
     */
    private function getReflection(string $className): ReflectionClass
    {
        if (!isset($this->reflectionCache[$className])) {
            $this->reflectionCache[$className] = new ReflectionClass($className);
        }

        /** @var ReflectionClass<T> */
        return $this->reflectionCache[$className];
    }
}
