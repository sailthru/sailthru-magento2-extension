<?php

namespace Sailthru\MageSail\Plugin;

use Magento\Newsletter\Model\Subscriber;
use Sailthru\MageSail\Helper\Api;

class SubscribeIntercept
{

	public function __construct(Api $sailthru){
		$this->sailthru = $sailthru;
	}


    /**
     * Saving customer subscription status through FrontEnd Control Panel
     *
     * @param generic Subscriber Model $subscriberModel
     * @param loaded Subscriber $subscriber
     * @return  $subscriber
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterSubscribeCustomerById(Subscriber $subscriberModel, $subscriber){
    	$this->updateSailthruSubscription($subscriber);
    	return $subscriber;
    }

    /**
     * Saving customer unsubscribe status through FrontEnd Control Panel
     *
     * @param generic Subscriber Model $subscriberModel
     * @param loaded Subscriber $subscriber
     * @return  $subscriber
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function afterUnsubscribeCustomerById(Subscriber $subscriberModel, $subscriber){
        $this->updateSailthruSubscription($subscriber);
    	return $subscriber;
    }

    protected function updateSailthruSubscription($subscriber){
        $status = $subscriber->getStatus();
        $email = $subscriber->getEmail();
        if(
            ($status === Subscriber::STATUS_UNSUBSCRIBED or $status === Subscriber::STATUS_SUBSCRIBED) and
            $this->sailthru->getSettingsVal(Api::XML_NEWSLETTER_LIST_ENABLED) and
            $newsletter_list = $this->sailthru->getSettingsVal(Api::XML_NEWSLETTER_LIST_VALUE)
            ){
            try {
                $this->sailthru->client->_eventType = 'CustomerSubscribe';
                $data = [
                        'id'     => $email,
                        'key'    => 'email',
                        'lists'  => [ $newsletter_list => ($status === Subscriber::STATUS_SUBSCRIBED ? 1 : 0) ],
                ];

                if($fullName = $subscriber->getSubscriberFullName()){
                   $data['vars'] = [
                        'firstName' => $subscriber->getFirstname(),
                        'lastName'  => $subscriber->getLastname(),
                        'name'      => $fullName,
                    ];
                }
                $response = $this->sailthru->client->apiPost('user', $data);

            } catch(\Sailthru_Email_Model_Client_Exception $e) {
                $this->sailthru->logger($e);
                throw new \Magento\Framework\Exception\LocalizedException(__('We were unable to subscribe the customer.'));
            } catch(\Exception $e) {
                $this->sailthru->logger($e);
                throw new \Magento\Framework\Exception\LocalizedException(__('We were unable to subscribe the customer.'));
            }
        }
    }



}

?>