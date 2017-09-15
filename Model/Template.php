<?php

namespace Sailthru\MageSail\Model;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Email\Model\Template as EmailTemplateModel;

class Template extends EmailTemplateModel
{
    /** @var \Magento\Email\Model\ResourceModel\Template\CollectionFactory */
    private $collectionFactory;

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
        \Magento\Email\Model\ResourceModel\Template\CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;

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
     * To get template_code by template_id.
     * 
     * @param  string $id
     * 
     * @return mixed
     */
    public function getTemplateDataById($id)
    {
        $data = [];

        $collection = $this->collectionFactory->create()->addFieldToFilter('template_id', $id);
        foreach ($collection as $template) {
            if ($templateData = $template->getData()) {
                $data = $templateData;
                break;
            }
        }

        return empty($data) ? null : $data;
    }
}
