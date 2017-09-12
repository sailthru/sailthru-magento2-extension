<?php

namespace Sailthru\MageSail\Mail;

use Magento\Framework\App\Filesystem\DirectoryList;

class Template extends \Magento\Email\Model\Template
{
    /**
     * List of the templeta variable directives.
     * 
     * @var array
     */
    public $templateDirectives = [];

    /**
     * List of the template variables.
     */
    public $templateVariables;

    /**
     * Load default email template
     *
     * @param string $templateId
     * @return $this
     */
    public function loadDefault($templateId)
    {
        $designParams = $this->getDesignParams();
        $templateFile = $this->emailConfig->getTemplateFilename($templateId, $designParams);
        $templateType = $this->emailConfig->getTemplateType($templateId);
        $templateTypeCode = $templateType == 'html' ? self::TYPE_HTML : self::TYPE_TEXT;
        $this->setTemplateType($templateTypeCode);

        $rootDirectory = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
        $templateText = $rootDirectory->readFile($rootDirectory->getRelativePath($templateFile));

        /**
         * trim copyright message
         */
        if (preg_match('/^<!--[\w\W]+?-->/m', $templateText, $matches) && strpos($matches[0], 'Copyright') > 0) {
            $templateText = str_replace($matches[0], '', $templateText);
        }

        if (preg_match('/<!--@subject\s*(.*?)\s*@-->/u', $templateText, $matches)) {
            $this->setTemplateSubject($matches[1]);
            $templateText = str_replace($matches[0], '', $templateText);
        }

        if (preg_match('/<!--@vars\s*((?:.)*?)\s*@-->/us', $templateText, $matches)) {
            $this->setData('orig_template_variables', str_replace("\n", '', $matches[1]));
            $templateText = str_replace($matches[0], '', $templateText);

            # add variable key => value directives to templateDirectives
            $parsedVars = explode('",', $matches[1]);
            if ($parsedVars &&
                !strstr($templateId, 'header') &&
                !strstr($templateId, 'footer')
            ) {
                foreach ($parsedVars as $directiveString) {
                    $directiveString = explode(
                        '":"',
                        trim(preg_replace('/(\s\s+|var|{|})/', '', $directiveString))
                    );

                    $directiveString[0] = trim(preg_replace('/(]\)|")/', '', $directiveString[0]));

                    $this->templateDirectives[strtolower(str_replace(' ', '_', $directiveString[1]))] = str_replace(
                        ' ',
                        '',
                        $directiveString[0]
                    );
                }
            }
        }

        if (preg_match('/<!--@styles\s*(.*?)\s*@-->/s', $templateText, $matches)) {
            $this->setTemplateStyles($matches[1]);
            $templateText = str_replace($matches[0], '', $templateText);
        }

        // Remove comment lines and extra spaces
        $templateText = trim(preg_replace('#\{\*.*\*\}#suU', '', $templateText));

        $this->setTemplateText($templateText);
        $this->setId($templateId);

        return $this;
    }

    /**
     * Process email template code
     *
     * @param array $variables
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function getProcessedTemplate(array $variables = [])
    {
        $processor = $this->getTemplateFilter()
            ->setUseSessionInUrl(false)
            ->setPlainTemplateMode($this->isPlain())
            ->setIsChildTemplate($this->isChildTemplate())
            ->setTemplateProcessor([$this, 'getTemplateContent']);

        $variables['this'] = $this;

        $isDesignApplied = $this->applyDesignConfig();

        // Set design params so that CSS will be loaded from the proper theme
        $processor->setDesignParams($this->getDesignParams());

        $storeId = isset($variables['subscriber'])
            ? $variables['subscriber']->getStoreId()
            : $this->getDesignConfig()->getStore();

        $processor->setStoreId($storeId);

        // Populate the variables array with store, store info, logo, etc. variables
        $variables = $this->addEmailVariables($variables, $storeId);
        $processor->setVariables($variables);
        # Was added to set template directives.
        $processor->setDirectives($this->templateDirectives);

        try {
            $result = $processor->filter($this->getTemplateText());
            # Was added to set template variables.
            $templateVariables = $processor->getTemplateVariables();
            if ($templateVariables) {
                $this->templateVariables = $templateVariables;
            }
        } catch (\Exception $e) {
            $this->cancelDesignConfig();
            throw new \LogicException(__($e->getMessage()), $e);
        }
        if ($isDesignApplied) {
            $this->cancelDesignConfig();
        }
        return $result;
    }
}
