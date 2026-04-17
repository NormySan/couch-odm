<?php

declare(strict_types=1);

namespace SmrtSystems\Couch\Client\Data;

enum DesignDocumentLanguage: string
{
    case Erlang = 'erlang';
    case JavaScript = 'javascript';
}