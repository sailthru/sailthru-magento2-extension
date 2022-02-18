<?php

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

        /**
         * Add params to template variable list
         *
         * @customization START
         */
        if ($params) {
            foreach ($params as $paramKey => $paramValue) {
                $this->templateVariables[$paramKey] = $paramValue;
            }
        }
        /** @customization END */

        $text = __($text, $params)->render();

        $pattern = '/{{.*?}}/';
        do {
            $text = preg_replace($pattern, '', (string)$text);
        } while (preg_match($pattern, $text));

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

        list($directive, $modifiers) = $this->explodeModifiers(
            $construction[2] . ($construction['filters'] ?? ''),
            'escape'
        );

        /**
         * Add parsed directive value to template variable list
         *
         * @customization START
         */
        if ($this->templateDirectives) {
            foreach ($this->templateDirectives as $key => $value) {
                if (strstr($directive, $value)) {
                    $formattedKey = str_replace('"', "", $key);
                    $this->templateVariables[$formattedKey] = $this->getVariable($directive, '');
                }
            }
        }
        /** @customization END */

        return $this->applyModifiers($this->getVariable($directive, ''), $modifiers);
    }
}
