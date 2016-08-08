<?php

namespace Sailthru\MageSail\Frontend\Block;

use Magento\Catalog\Block\Product\View;

class Tagblock extends View
{
	protected $_sailthru;

	public function __construct(\Sailthru\MageSail\Helper\Api $sailthru){
		$this->_sailthru = $sailthru;
		parent::__construct();
	}

	
}