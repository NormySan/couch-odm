<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Mapping;

/**
 * Interface for metadata factory implementations.
 */
interface MetadataFactoryInterface
{
    /**
     * Returns the class metadata for the given class.
     *
     * @param class-string $className
     */
    public function getMetadataFor(string $className): ClassMetadata;

    /**
     * Checks if the given class has valid document mapping.
     *
     * @param class-string $className
     */
    public function hasMetadataFor(string $className): bool;
}
