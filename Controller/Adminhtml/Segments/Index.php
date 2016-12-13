<?php


namespace Sailthru\MageSail\Controller\Adminhtml\Segments;

class Index extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = "Sailthru_MageSail::segments";

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Sailthru\MageSail\Helper\Api $sailthru
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->sailthru = $sailthru;

    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->getConfig()->getTitle()->prepend(__('Sailthru-powered Customer Groups'));
        return $resultPage;
    }

    /**
     * Is the user allowed to view the resource.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}