<?php

namespace Sailthru\MageSail\Block\Adminhtml\Segments;

class Index extends \Magento\Backend\Block\Template 
{
	public function __construct(
		\Magento\Backend\Block\Template\Context $context, 
		\Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepo,
		\Sailthru\MageSail\Helper\Api $sailthru,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->sailthru = $sailthru;
		$this->customerGroupRepo = $customerGroupRepo;
	}

	protected function getLists(){
		$lists = $this->sailthru->client->getLists();
		return $lists;
	}	

	protected function getCustomerGroups(){
		$groups = $this->customerGroupRepo->getList();
		return $groups;
	}
}