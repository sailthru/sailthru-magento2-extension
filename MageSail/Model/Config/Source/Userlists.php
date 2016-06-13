<?php
 
/**
 * Sailthru_MageSail Email List Source Model for Magento 2 Dropdowns
 *
 * @category    Sailthru
 * @package     Sailthru_MageSail
 * @author      Sailthru Integrations Team <integrations@sailthru.com>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Sailthru\MageSail\Model\Config\Source;

class Userlists implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
 
        return [
            ['value' => 0, 'label' => __('Zero')],
            ['value' => 1, 'label' => __('One')],
            ['value' => 2, 'label' => __('Two')],
        ];
    }

    public function toArray()
    {
        return [
            0 => __('Zero'), 
            1 => __('One'),
            2 => __('Two'),
        ];
    }
}

