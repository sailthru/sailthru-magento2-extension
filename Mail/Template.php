<?php

namespace Sailthru\MageSail\Mail;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Sailthru\MageSail\Model\Template as TemplateModel;

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

    /** @var Template */
    private $templateModel;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Model\Context                   $context
     * @param \Magento\Framework\View\DesignInterface            $design
     * @param \Magento\Framework\Registry                        $registry
     * @param \Magento\Store\Model\App\Emulation                 $appEmulation
     * @param StoreManagerInterface                              $storeManager
     * @param \Magento\Framework\View\Asset\Repository           $assetRepo
     * @param \Magento\Framework\Filesystem                      $filesystem
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Template\Config                                    $emailConfig
     * @param TemplateFactory                                    $templateFactory
     * @param \Magento\Framework\Filter\FilterManager            $filterManager
     * @param \Magento\Framework\UrlInterface                    $urlModel
     * @param Template\FilterFactory                             $filterFactory
     * @param TemplateModel                                      $templateModel
     * @param array                                              $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\App\Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Email\Model\Template\Config $emailConfig,
        \Magento\Email\Model\TemplateFactory $templateFactory,
        \Magento\Framework\Filter\FilterManager $filterManager,
        \Magento\Framework\UrlInterface $urlModel,
        \Magento\Email\Model\Template\FilterFactory $filterFactory,
        TemplateModel $templateModel,
        array $data = []
    ) {
        $this->templateModel = $templateModel;
        parent::__construct(
            $context,
            $design,
            $registry,
            $appEmulation,
            $storeManager,
            $assetRepo,
            $filesystem,
            $scopeConfig,
            $emailConfig,
            $templateFactory,
            $filterManager,
            $urlModel,
            $filterFactory,
            $data
        );
    }

    /**
     * Get processed template
     *
     * @return string
     * @throws \Magento\Framework\Exception\MailException
     */
    public function processTemplate()
    {
        // Support theme fallback for email templates
        $isDesignApplied = $this->applyDesignConfig();

        $templateId = $this->getId();
        if (is_numeric($templateId)) {
            $this->processTemplateVarDirectives($templateId);
            $this->load($templateId);
        } else {
            $this->loadDefault($templateId);
        }

        if (!$this->getId()) {
            throw new \Magento\Framework\Exception\MailException(
                __('Invalid transactional email code: %1', $templateId)
            );
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($this->_getVars());

        if ($isDesignApplied) {
            $this->cancelDesignConfig();
        }
        return $text;
    }

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
        # To set template directives.
        $processor->setDirectives($this->templateDirectives);

        try {
            $result = $processor->filter($this->getTemplateText());
            # To set template variables.
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

    /**
     * To get template directives.
     * 
     * @param int $templateId
     */
    private function processTemplateVarDirectives($templateId)
    {
        $templateData = $this->templateModel->getTemplateDataById($templateId);
        $templateText = $templateData['template_text'] ?? '';

        if (preg_match('/<!--@vars\s*((?:.)*?)\s*@-->/us', $templateText, $matches)) {
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

                    $this->templateDirectives[
                        str_replace('"', '', strtolower(str_replace(' ', '_', $directiveString[1])))
                    ] = str_replace(
                        ' ',
                        '',
                        $directiveString[0]
                    );
                }
            }
        }
    }
}
