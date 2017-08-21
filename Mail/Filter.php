<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Sailthru\MageSail\Mail;

/**
 * Override of core Email Template Filter Model
 * 
 * TODO: override method for custom template variables.
 */
class Filter extends \Magento\Email\Model\Template\Filter
{
    /**
     * List of the template variables.
     * 
     * @var array
     */
    protected $templateVariables = [];

    /**
     * List of the template variable directives.
     * @var array
     */
    protected $templateDirectives = [];

    /**
     * To get list of the template variables.
     * 
     * @return array
     */
    public function getTemplateVariables()
    {
        return $this->templateVariables;
    }

    /**
     * To set template directives.
     * 
     * @param array $directives
     */
    public function setDirectives(array $directives)
    {
        $this->templateDirectives = $directives;
    }

    /**
     * Trans directive for localized strings support
     *
     * Usage:
     *
     *   {{trans "string to translate"}}
     *   {{trans "string to %var" var="$variable"}}
     *
     * The |escape modifier is applied by default, use |raw to override
     *
     * @param string[] $construction
     * @return string
     */
    public function transDirective($construction)
    {
        list($directive, $modifiers) = $this->explodeModifiers($construction[2], 'escape');

        list($text, $params) = $this->getTransParameters($directive);
        if (empty($text)) {
            return '';
        }

        # add params to template variable list
        if ($params) {
            foreach ($params as $paramKey => $paramValue) {
                $this->templateVariables[$paramKey] = $paramValue;
            }
        }

        $text = __($text, $params)->render();
        return $this->applyModifiers($text, $modifiers);
    }

    /**
     * Var directive with modifiers support
     *
     * The |escape modifier is applied by default, use |raw to override
     *
     * @param string[] $construction
     * @return string
     */
    public function varDirective($construction)
    {
        // just return the escaped value if no template vars exist to process
        if (count($this->templateVars) == 0) {
            return $construction[0];
        }

        list($directive, $modifiers) = $this->explodeModifiers($construction[2], 'escape');

        # add parsed directive value to template variable list
        if ($this->templateDirectives) {
            foreach ($this->templateDirectives as $key => $value) {
                if (strstr($directive, $value)) {
                    $this->templateVariables[$key] = $this->getVariable($directive, '');
                }
            }
        }

        return $this->applyModifiers($this->getVariable($directive, ''), $modifiers);
    }
}
