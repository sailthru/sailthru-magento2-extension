<?php
/**
 * Copyright © 2015 Sailthru. All rights reserved.
 */

namespace Sailthru\MageSail\Controller\Adminhtml\Items;

class NewAction extends \Sailthru\MageSail\Controller\Adminhtml\Items
{

    public function execute()
    {
        $this->_forward('edit');
    }
}
