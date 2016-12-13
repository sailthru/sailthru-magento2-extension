<?php

use Magento\Customer\Model\Customer;

namespace Sailthru\MageSail\Controller\Adminhtml\Segments;

class Import extends \Magento\Backend\App\Action
{

    protected $resultPageFactory;
    protected $jsonHelper;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context  $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        Customer $customerModel,
        \Sailthru\MageSail\Helper\Api $sailthru
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->jsonHelper = $jsonHelper;
        $this->customerModel = $customerModel;
        $this->sailthru = $sailthru;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $job_id = $this->startListExport('list');
            $url = $this->getUrlForExport($job_id);
            $sids = $this->getUsersFromFile($url);
            $added_users = $this->addUsersToCustomerGroup($sids, 'group_id');

            return $this->jsonResponse('your response');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return $this->jsonResponse($e->getMessage());
        }
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
        $response = $this->sailthru->client->apiGet("job", ["job_id"=> job_id]);
        if (array_has_key($response, "status") and array_has_key($response, "export_url")){
            return $response["export_url"];
        } elseif ($attempts > 5){
            throw new \Exception("Job took too long");
        } else {
            sleep(5);
            return getUsersFromJob($jobId, $attempts++);
        }
    }

    protected function getUsersFromFile($url){
        $data = file_get_contents($response["export_url"]);
        $users = explode("\n", $data);
        array_shift($user_array); // get rid of CSV headers
        foreach($users as $user){
            $user = array_pop(explode(",", $user));
        }
        return $users;
    }

    protected function importToGroup($sids, $group_id){
        $total = 0;
        $collection = $this->customerModel->getCollection();
        $collection->addAttributeToSelect('sailthru_id');
        foreach($sids as $sid){
            $collection->addFieldToFilter(array(
                array('attribute'=>'sailthru_id','eq'=>$sid),
            ));
            $result = $collection->getItems();
            if (count($result)){
                $user = array_shift($result);
                $user->setData('group_id', $group_id);
                $user->save();
                $total++;
            }
        }
        return $total;
    }
}