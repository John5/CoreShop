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

namespace CoreShop\Model;

use CoreShop\Exception;
use CoreShop\Model\Plugin\TaxManager as PluginTaxManager;
use CoreShop\Model\TaxRule\Manager;
use CoreShop\Model\User\Address;
use Pimcore\Cache;

/**
 * Class TaxManagerFactory
 * @package CoreShop\Model
 */
class TaxManagerFactory
{
    /**
     * get CacheKey for Address.
     *
     * @param Address $address
     *
     * @return string
     */
    private static function getCacheKey(Address $address)
    {
        return md5($address->getCountry()->getId().
            ($address->getState() instanceof State ? $address->getState()->getId() : '').
            ($address->getName() ? $address->getName() : '').
            ($address->getVatNumber() ? $address->getVatNumber() : '').
            ($address->getStreet() ? $address->getStreet() : '').
            ($address->getCity() ? $address->getCity() : '').
            ($address->getCompany() ? $address->getCompany() : ''));
    }

    /**
     * @param Address $address
     * @param $type
     *
     * @return bool|Manager|mixed|null
     */
    public static function getTaxManager(Address $address, $type)
    {
        $cacheKey = 'coreshop_tax_manager_'.self::getCacheKey($address).'_'.$type;

        try {
            $taxManager = \Zend_Registry::get($cacheKey);

            if (!$taxManager) {
                throw new Exception('TaxManager in registry is null');
            }

            return $taxManager;
        } catch (\Exception $e) {
            try {
                if (!$taxManager = Cache::load($cacheKey)) {
                    $taxManager = self::getPluginTaxManager($address, $type);

                    if (!$taxManager instanceof PluginTaxManager) {
                        $taxManager = new Manager($address, $type);
                    }

                    \Zend_Registry::set($cacheKey, $taxManager);
                    Cache::save($taxManager, $cacheKey, array('coreshop_tax_manager'));
                } else {
                    \Zend_Registry::set($cacheKey, $taxManager);
                }

                return $taxManager;
            } catch (\Exception $e) {
                \Logger::warning($e->getMessage());
            }
        }

        return null;
    }

    /**
     * @param Address $address
     * @param $type
     *
     * @return bool
     */
    protected static function getPluginTaxManager(Address $address, $type)
    {
        $results = \Pimcore::getEventManager()->trigger('coreshop.tax.getTaxManager', null, array('address' => $address, 'type' => $type));

        foreach ($results as $result) {
            if ($result instanceof PluginTaxManager) {
                if ($result->isAvailableForThisAddress($address, $type)) {
                    return $result;
                }
            }
        }

        return false;
    }
}
