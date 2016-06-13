<?php
/**
 * Copyright © 2015 Sailthru. All rights reserved.
 */

namespace Sailthru\MageSail\Model;

class Items extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Sailthru\MageSail\Model\Resource\Items');
    }
}
