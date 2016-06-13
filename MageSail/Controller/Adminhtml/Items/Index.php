<?php
/**
 * Copyright © 2015 Sailthru. All rights reserved.
 */

namespace Sailthru\MageSail\Controller\Adminhtml\Items;

class Index extends \Sailthru\MageSail\Controller\Adminhtml\Items
{
    /**
     * Items list.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Sailthru_MageSail::magesail');
        $resultPage->getConfig()->getTitle()->prepend(__('Sailthru Items'));
        $resultPage->addBreadcrumb(__('Sailthru'), __('Sailthru'));
        $resultPage->addBreadcrumb(__('Items'), __('Items'));
        return $resultPage;
    }
}
