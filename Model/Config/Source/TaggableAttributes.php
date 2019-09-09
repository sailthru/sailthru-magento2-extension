<?php

namespace Sailthru\MageSail\Model\Config\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeSetRepositoryInterface as AttributeSetRepo;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Sailthru\MageSail\Helper\Api;
use Sailthru\MageSail\Logger;

class TaggableAttributes extends AbstractSource
{
    /** @var EavConfig  */
    private $eavConfig;

    /** @var Product  */
    private $product;

    /** @var Logger  */
    private $logger;

    /** @var AttributeSetRepo  */
    private $attributeSetRepo;

    public function __construct(
        Api $apiHelper,
        EavConfig $eavConfig,
        AttributeSetRepo $attributeSetRepo,
        Product $product,
        Logger $logger
    ) {
        parent::__construct($apiHelper);
        $this->eavConfig = $eavConfig;
        $this->attributeSetRepo = $attributeSetRepo;
        $this->product = $product;
        $this->logger = $logger;
    }

    protected function getDisplayData()
    {
        $allAttributes = $this->buildArray($this->eavConfig->getEntityType(Product::ENTITY)->getAttributeCollection(), true);
        return $allAttributes;
    }

    private function buildArray(Collection $collection, $log = false)
    {
        $array = array();
        foreach ($collection as $attribute) {
            /** @var $attribute Attribute */
            if(in_array($attribute->getAttributeCode(), Api::$essentialAttributeCodes)) {
                continue;
            }
            if (!$label = $this->getAttributeLabel($attribute)) {
                continue;
            }
            $array[] = [
                "label" => $label,
                "value" => $attribute->getAttributeCode()
            ];
        }
        return $array;
    }

    private function getAttributeLabel(Attribute $attribute)
    {
        $attributeText =  $attribute->getStoreLabel() ?: $attribute->getData('frontend_label');
        if (!$attributeText) {
            return null;
        }
        $lowercaseText = str_replace(" ", "_", strtolower($attributeText));
        if (strpos($attribute->getAttributeCode(), $lowercaseText) !== false && strpos($attribute->getAttributeCode(), "_") !== false) {
            $set = str_replace($lowercaseText, "", $attribute->getAttributeCode());
            $set = str_replace("_", "", $set);
            if ($set) {
                $attributeText .= " ($set)";
            }
        }
        return $attributeText;
    }
}