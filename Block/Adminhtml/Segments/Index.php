<?php

namespace Sailthru\MageSail\Block\Adminhtml\Segments;

class Index extends \Magento\Backend\Block\Template 
{
	public function __construct(
		\Magento\Backend\Block\Template\Context $context, 
		\Magento\Customer\Api\GroupRepositoryInterface $customerGroupRepo,
		\Magento\Framework\Api\SearchCriteriaInterface $customerGroupCriteria,
		\Magento\Framework\Api\Search\FilterGroupBuilder $FilterGroupBuilder,
		\Sailthru\MageSail\Helper\Api $sailthru,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->sailthru = $sailthru;
		$this->customerGroupRepo = $customerGroupRepo;
		$this->assign("lists", $this->getLists());
		$customerGroupCriteria->setPageSize(50);
		$this->assign("customer_groups", $this->getCustomerGroups($customerGroupCriteria)->getItems());
		$this->assign("import_url", $this->getImportUrl());
		$this->assign("form_key", $this->getFormKey());
	}

	protected function getLists(){
	    $data = $this->sailthru->client->getLists();
        $lists_data = $data["lists"];
        $lists = array();
        foreach ($lists_data as $list) {
            $lists[] = [
                'value' => $list['name'],
                'label' => __("{$list['name']} ({$list['email_count']} Emails)")
            ];
        }
		return $lists;
	}	

	protected function getCustomerGroups($criteria){
		$groups = $this->customerGroupRepo->getList($criteria);
		return $groups;
	}

	protected function getImportUrl(){
		return $this->getUrl('*/*/import', ['isAjax' => true]);
    }

    /**
     * Add data to view
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $originalData = $element->getOriginalData();

        $this->$data['lists'] = $this->getLists();
        $data['customer_groups'] = $block->getCustomerGroups();
        $data['original_data'] = $originalData;
        $this->addData($data);
        return $this->_toHtml();
    }

}