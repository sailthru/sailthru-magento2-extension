<?php

namespace Sailthru\MageSail\Controller\Adminhtml\Segments;

use Magento\Customer\Model\Customer;
use Magento\Framework\View\Result\PageFactory;
use Sailthru\MageSail\Helper\Api;

class Index extends \Magento\Backend\App\Action
{

    const ADMIN_RESOURCE = "Sailthru_MageSail::segments";

    protected $resultPageFactory;
    protected $sailthru;
    protected $customerModel;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $criteria,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Backend\App\Action\Context $context,
        PageFactory $resultPageFactory,
        Api $sailthru,
        Customer $customerModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->sailthru = $sailthru;
        $this->customerModel = $customerModel;
        $this->customerRepo = $customerRepository;
        $this->searchCriteria = $criteria;
        $this->filterGroup = $filterGroup;
        $this->filterBuilder = $filterBuilder;
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
        
        $sailthruList = $this->getRequest()->getPost('sailthruList');
        $groupId = $this->getRequest()->getPost('groupId');
        $this->sailthru->logger($this->getRequest()->getPost());
        if ($sailthruList and $groupId){
            $this->sailthru->logger('trying to build!');
            $job_id = $this->startListExport($sailthruList);
            $url = $this->getUrlForExport($job_id);
            $sids = $this->getUsersFromFile($url);
            $added_users = $this->importToGroup($sids, $groupId);
            $this->sailthru->logger("completed! added $added_users to Group");
            $this->messageManager->addSuccess("completed! added $added_users to Group " . $groupId);
        }
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

    /**
     * Create json response
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function jsonResponse($response = '')
    {
        return $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($response)
        );
    }

    /**
    * Start list export job
    * @return String jobId
    */

    protected function startListExport($list){
        if (!$list) throw new \Exception("No List Provided");
        $jobData = [
            'job' => 'export_list_data',
            'list' => $list,
            'fields' => [ "a" => 1],
        ];
        $response = $this->sailthru->client->apiPost('job', $jobData);
        return $response["job_id"];
    }

    /**
    * Open the file and get users
    *
    * @param String jobId
    * @param int attempts
    * @return String[] Sid
    *
    */

    protected function getUrlForExport($jobId, $attempts=0){
        $response = $this->sailthru->client->apiGet("job", ["job_id"=> $jobId]);
        if (array_key_exists("status", $response) and array_key_exists("export_url", $response)){
            return $response["export_url"];
        } elseif ($attempts > 5){
            throw new \Exception("Job took too long");
        } else {
            sleep(2);
            return $this->getUrlForExport($jobId, $attempts++);
        }
    }

    protected function getUsersFromFile($url){
        $data = file_get_contents($url);
        $sids = array();
        $users = explode("\n", $data);
        array_shift($users); // get rid of CSV headers
        foreach($users as $user){
            $userdata = explode(",", $user);
            $sids[] = array_shift($userdata);
        }
        return $sids;
    }

    protected function importToGroup($sids, $group_id){
        $total = 0;
        $collection = $this->customerModel->getCollection();
        foreach($sids as $sid){
            $this->sailthru->logger("SID $sid");
            $result = $this->getUsersBySid($sid);
            if (count($result)){
                $this->sailthru->logger("found a customer!");
                $user = array_shift($result);
                $user->setGroupId($group_id);
                $this->customerModel->updateData($user);
                $total++;
            }
        }
        return $total;
    }

    protected function getUsersBySid($sid){
        $this->filterGroup->setFilters(
            [
                $this->filterBuilder
                    ->setField('sailthru_id')
                    ->setConditionType('eq')
                    ->setValue($sid)
                    ->create()
            ]
        );
        $this->searchCriteria->setFilterGroups([$this->filterGroup]);
        $customersList = $this->customerRepo->getList($this->searchCriteria);
        $customers = $customersList->getItems();
        return $customers;
    }
}