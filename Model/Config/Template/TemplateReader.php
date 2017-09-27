<?php

namespace Sailthru\MageSail\Model\Config\Template;

use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\ValidationStateInterface;
use Sailthru\MageSail\Model\Config\Template\Converter;
use Sailthru\MageSail\Model\Config\Template\SchemaLocator;

class TemplateReader extends Filesystem
{
    function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState
    ){
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            'template_config.xml'
        );
    }
}
