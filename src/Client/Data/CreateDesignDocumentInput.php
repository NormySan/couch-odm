<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Client\Data;

readonly class CreateDesignDocumentInput
{
    /**
     * @param string $database
     * @param string $name
     * @param DesignDocumentLanguage $language
     * @param array<string, array<'map'|'reduce', string>>|null $views
     */
    public function __construct(
        public string $database,
        public string $name,
        public DesignDocumentLanguage $language = DesignDocumentLanguage::JavaScript,
        public ?array $views = null,
    ) {}
}
