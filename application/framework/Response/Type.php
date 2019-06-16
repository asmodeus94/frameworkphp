<?php

namespace App\Response;


use App\Helper\Traits\ConstantExportTrait;

class Type
{
    use ConstantExportTrait;

    const APPLICATION_JAVASCRIPT = 'application/javascript';
    const APPLICATION_OCTET_STREAM = 'application/octet-stream';
    const APPLICATION_PDF = 'application/pdf';
    const APPLICATION_JSON = 'application/json';
    const APPLICATION_XML = 'application/xml';
    const APPLICATION_ZIP = 'application/zip';

    const TEXT_CSS = 'text/css';
    const TEXT_CSV = 'text/csv';
    const TEXT_HTML = 'text/html';
    const TEXT_PLAIN = 'text/plain';
    const TEXT_XML = 'text/xml';

    const DEFAULT_TYPE = self::TEXT_HTML;
}
