<?php
/**
 * Copyright © 2015 Sailthru. All rights reserved.
 */

namespace Sailthru\MageSail\Model\ResourceModel;

class Items extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sailthru_magesail_items', 'id');
    }
}
