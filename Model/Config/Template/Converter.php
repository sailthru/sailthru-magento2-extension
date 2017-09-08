<?php

namespace Sailthru\MageSail\Model\Config\Template;

class Converter implements \Magento\Framework\Config\ConverterInterface
{
    /**
     * XML text node class name.
     */
    const XML_TXT_NODE = 'DOMText';

    /**
     * XML elemnt node class name
     */
    const XML_ELM_NODE = 'DOMElement';

    /**
     * To convert templates from config file to array.
     * 
     * @param  DOMDocument $source
     * 
     * @return array
     */
    public function convert($source)
    {
        # Templates from config file.
        $templateList = $source->getElementsByTagName('template');
        # Empty storage for templates info.
        $templatesInfo = [];

        if (!$templateList)
            return ['templates' => $templatesInfo];

        foreach ($templateList as $template) {
            $templateAttrs = [];
            foreach ($template->childNodes as $attribute) {
                if ('#text' != $attribute->nodeName) {
                    $templateAttrs[$attribute->nodeName] = $attribute->nodeValue;
                }
            }
            $templatesInfo[] = $templateAttrs;
        }

        return ['templates' => $templatesInfo];
    }
}
