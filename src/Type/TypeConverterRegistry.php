<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Type;

/**
 * Registry for type converters.
 *
 * The registry allows registering converters for custom PHP types
 * and provides lookup functionality by class name.
 */
final class TypeConverterRegistry
{
    /** @var array<class-string, TypeConverterInterface<object>> */
    private array $converters = [];

    /**
     * @param iterable<TypeConverterInterface<object>> $converters Initial converters to register
     */
    public function __construct(iterable $converters = [])
    {
        foreach ($converters as $converter) {
            $this->register($converter);
        }
    }

    /**
     * Register a type converter.
     *
     * @param TypeConverterInterface<object> $converter
     */
    public function register(TypeConverterInterface $converter): void
    {
        $this->converters[$converter->getPhpType()] = $converter;
    }

    /**
     * Check if a converter exists for the given PHP type.
     *
     * @param class-string $phpType
     */
    public function has(string $phpType): bool
    {
        return isset($this->converters[$phpType]);
    }

    /**
     * Get the converter for the given PHP type.
     *
     * @param class-string $phpType
     * @return TypeConverterInterface<object>|null
     */
    public function get(string $phpType): ?TypeConverterInterface
    {
        return $this->converters[$phpType] ?? null;
    }

    /**
     * Get all registered converters.
     *
     * @return array<class-string, TypeConverterInterface<object>>
     */
    public function all(): array
    {
        return $this->converters;
    }

    /**
     * Get all registered PHP types.
     *
     * @return array<class-string>
     */
    public function getRegisteredTypes(): array
    {
        return array_keys($this->converters);
    }
}
