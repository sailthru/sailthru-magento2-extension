<?php

namespace Sailthru\MageSail\Model\Config\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Helper\ProductData;

class TaggableAttributes extends AbstractSource
{
    /** @var EavConfig */
    private $eavConfig;

    public function __construct(
        Api $apiHelper,
        EavConfig $eavConfig
    ) {
        parent::__construct($apiHelper);
        $this->eavConfig = $eavConfig;
    }

    /**
     * @return array
     */
    protected function getDisplayData()
    {
        $attributeCollection = $this->buildArray(
            $this->eavConfig->getEntityType(Product::ENTITY)->getAttributeCollection()
        );
        usort($attributeCollection, function ($a, $b) {
            return $a['label'] <=> $b['label'];
        });

        return $attributeCollection;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    private function buildArray(Collection $collection)
    {
        $array = array();
        foreach ($collection as $attribute) {
            /** @var $attribute Attribute */
            if (in_array($attribute->getAttributeCode(), ProductData::$essentialAttributeCodes)) {
                continue;
            }
            if (!$label = $this->getAttributeLabel($attribute)) {
                continue;
            }
            $array[] = [
                'label' => $label,
                'value' => $attribute->getAttributeCode()
            ];
        }

        return $array;
    }

    /**
     * @param Attribute $attribute
     *
     * @return string
     */
    private function getAttributeLabel(Attribute $attribute)
    {
        $attributeLabel = $attribute->getStoreLabel() ? : $attribute->getData('frontend_label');
        if (!$attributeLabel) {
            $attributeLabel = $attribute->getAttributeCode();
        }

        $attributeLabel .= ' (' . $attribute->getAttributeCode() . ')';

        return $attributeLabel;
    }
}