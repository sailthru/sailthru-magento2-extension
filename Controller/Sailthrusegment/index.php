<?php
namespace Sailthru\MageSail\Controller\Adminhtml\Sailthrusegment;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Sailthru\MageSail\Helper\Api;

class Index extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = 'Sailthru_MageSail::segment';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Sailthru Api Helper
     */
    protected $sailthru;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Api $sailthru
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->sailthru = $sailthru;
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Sailthru-powered Customer Groups'));

        return $resultPage;
    }

    /**
     * Is the user allowed to view the blog post grid.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }


}