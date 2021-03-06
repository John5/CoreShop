<?php
/**
 * CoreShop.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (http://www.pfaffenbauer.at)
 * @license    http://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Tool;

use CoreShop\Model\Configuration;
use Pimcore\Model\Object\AbstractObject;
use CoreShop\Model\Product;
use CoreShop\Model\BrickVariant;
use CoreShop\Exception;

/**
 * Class Service
 * @package CoreShop\Tool
 */
class Service
{
    /**
     * allowed elements to display in variants.
     *
     * @var array
     */
    private static $allowedVariationTypes = array('input', 'numeric', 'checkbox', 'select', 'slider', 'href', 'objects');

    /**
     * @param \CoreShop\Model\Product $master
     * @param \CoreShop\Model\Product $currentProduct
     * @param string                  $language
     *
     * @return array
     *
     * @throws Exception
     */
    public static function getProductVariations(Product $master, Product $currentProduct, $language = 'en')
    {
        $productVariants = self::getAllChildren($master);

        $projectId = $currentProduct->getId();

        $variantsAndMaster = array_merge(array($master), $productVariants);

        //we do have some dimension entries!
        $variantData = $master->getVariants();

        $dimensionInfo = array();
        $variantUrls = array();

        if (!is_null($variantData)) {
            $brickGetters = $variantData->getBrickGetters();

            if (!empty($brickGetters)) {
                foreach ($brickGetters as $brickGetter) {
                    $getter = $variantData->{$brickGetter}();

                    if (!is_null($getter)) {
                        $dimensionMethodData = self::getProductValidMethods($getter);
                        $dimensionInfo[$brickGetter] = $dimensionMethodData;
                    }
                }
            }
        }

        $compareValues = array();

        foreach ($variantsAndMaster as $productVariant) {
            $productId = $productVariant->getId();

            if (!empty($dimensionInfo)) {
                foreach ($dimensionInfo as $dimensionGetter => $dimensionMethodData) {
                    if (empty($dimensionMethodData)) {
                        continue;
                    }

                    $getter = $productVariant->getVariants()->{$dimensionGetter}();

                    //Getter must be an instance of Variant Model
                    if (!$getter instanceof BrickVariant) {
                        throw new Exception('Objectbrick "'.$dimensionGetter.'" needs to be a instance of \CoreShop\Model\BrickVariant"');
                    } elseif (!method_exists($getter, 'getValueForVariant')) {
                        throw new Exception('Variant Class needs a implemented "getValueForVariant" Method.');
                    } else {
                        foreach ($dimensionMethodData as $dMethod) {
                            $variantValue = $getter->getValueForVariant($dMethod, $language);
                            $variantName = $getter->getNameForVariant($dMethod);

                            if ($variantValue === false) {
                                continue;
                            }

                            if (!is_string($variantValue) && !is_numeric($variantValue)) {
                                throw new Exception('Variant return value needs to be string or numeric, '.gettype($variantValue).' given.');
                            }

                            //Add a namespace, so fields from different blocks can have same name!
                            $secureNameSpace = '__'.$getter->getType().'__';

                            $compareValues[ $secureNameSpace.$variantName ][ $productId ] = $variantValue;
                            $variantUrls[ $productVariant->getId() ] = $productVariant->getName();
                        }
                    }
                }
            }
        }

        $filtered = $compareValues;

        foreach ($compareValues as $variantName => $variantValues) {
            $currentVariantName = isset($variantValues[ $projectId ]) ? $variantValues[ $projectId ] : null;

            if (is_null($currentVariantName)) {
                continue;
            }

            $tmpArray = $compareValues;
            unset($tmpArray[ $variantName ]);

            $available = self::findProjectIDsInVariant($currentVariantName, $variantValues);

            $filtered = self::findInOthers($tmpArray, $available, $filtered);
        }

        $orderedData = array();

        if (!empty($filtered)) {
            foreach ($filtered as $variantName => $variantValues) {
                $currentVariantName = isset($variantValues[ $projectId ]) ? $variantValues[ $projectId ] : null;

                $variantSelections = array(

                    'variantName' => preg_replace('/__(.*?)__/', '', $variantName),
                    'variantValues' => array(),

                );

                $variantValues = array_unique($variantValues);

                if (!empty($variantValues)) {
                    foreach ($variantValues as $pid => $variantValue) {
                        $variantSelections['variantValues'][] = array(

                            'productId' => $pid,
                            'productName' => isset($variantUrls[ $pid ]) ?  $variantUrls[ $pid ] : null,
                            'selected' => $currentVariantName === $variantValue,
                            'variantName' => $variantValue,

                        );
                    }
                }

                if (!empty($variantSelections['variantValues'])) {
                    $orderedData[ strtolower($variantName) ] = $variantSelections;
                }
            }
        }

        return $orderedData;
    }

    /**
     * @param $tmpArray
     * @param $allowedProductIds
     * @param $filtered
     *
     * @return mixed
     */
    private static function findInOthers($tmpArray, $allowedProductIds, $filtered)
    {
        foreach ($tmpArray as $variantName => $variantValues) {
            foreach ($variantValues as $productId => $variantValue) {
                if (!in_array($productId, $allowedProductIds)) {
                    if (isset($filtered[ $variantName ])) {
                        unset($filtered[ $variantName ][ $productId ]);
                    }
                }
            }
        }

        return $filtered;
    }

    /**
     * @param $value
     * @param $array
     *
     * @return array
     */
    private static function findProjectIDsInVariant($value, $array)
    {
        $v = array();

        foreach ($array as $projectID => $variantName) {
            if ($variantName == $value) {
                $v[] = $projectID;
            }
        }

        return $v;
    }

    /**
     * @param      $getter
     * @param bool $restrictTypes
     *
     * @return array
     */
    private static function getProductValidMethods($getter, $restrictTypes = true)
    {
        $fields = $getter->getDefinition()->getFieldDefinitions();

        if (empty($fields)) {
            return array();
        }

        $validValues = array();

        foreach ($fields as $field) {
            $isValid = false;

            if ($restrictTypes == true) {
                if (in_array($field->getFieldType(), self::$allowedVariationTypes)) {
                    $isValid = true;
                }
            } else {
                $isValid = true;
            }

            if ($isValid) {
                $validValues[] = array(
                    'name' => $field->getName(),
                    'type' => $field->getPhpdocType(),
                    'title' => $field->getTitle(),
                );
            }
        }

        return $validValues;
    }

    /**
     * @param Product $object
     *
     * @return mixed
     */
    private static function getAllChildren(Product $object)
    {
        $list = Product::getList();

        $condition = 'o_path LIKE ?';
        $conditionParams = [$object->getFullPath() . '/%'];
        
        if(Configuration::multiShopEnabled()) {
            $shopParams = [];

            foreach($object->getShops() as $shop) {
                $shopParams[] = "shops LIKE '%,".$shop.",%'";
            }

            $condition .= " AND (" . implode(" OR ", $shopParams) . ")";
        }
        
        $list->setCondition($condition, $conditionParams);
        $list->setOrderKey('o_key');
        $list->setOrder('asc');
        $list->setObjectTypes(array(AbstractObject::OBJECT_TYPE_VARIANT));

        return $list->load();
    }
}
