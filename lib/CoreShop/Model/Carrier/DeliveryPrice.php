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

namespace CoreShop\Model\Carrier;

use CoreShop\Exception;
use CoreShop\Model\Carrier;
use CoreShop\Model\Zone;
use CoreShop\Model\AbstractModel;

/**
 * Class DeliveryPrice
 * @package CoreShop\Model\Carrier
 */
class DeliveryPrice extends AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var AbstractRange
     */
    public $range;

    /**
     * @var int
     */
    public $rangeId;

    /**
     * @var string
     */
    public $rangeType;

    /**
     * @var int
     */
    public $zoneId;

    /**
     * @var Zone
     */
    public $zone;

    /**
     * @var int
     */
    public $carrierId;

    /**
     * @var Carrier
     */
    public $carrier;

    /**
     * @var float
     */
    public $price;

    /**
     * Get all prices for this carrier in this range.
     *
     * @param Carrier       $carrier
     * @param AbstractRange $range
     *
     * @return DeliveryPrice|null
     */
    public static function getByCarrierAndRange(Carrier $carrier, AbstractRange $range)
    {
        try {
            $obj = new self();
            $obj->getDao()->getByCarrierAndRange($carrier->getId(), $range->getId());

            return $obj;
        } catch (\Exception $ex) {
        }

        return null;
    }

    /**
     * Get price for carrier in range in zone.
     *
     * @param Carrier       $carrier
     * @param AbstractRange $range
     * @param Zone          $zone
     *
     * @return DeliveryPrice|null
     */
    public static function getForCarrierInZone(Carrier $carrier, AbstractRange $range, Zone $zone)
    {
        try {
            $obj = new self();
            $obj->getDao()->getForCarrierInZone($carrier->getId(), $range->getId(), $zone->getId());

            return $obj;
        } catch (\Exception $ex) {
        }

        return null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getRangeId()
    {
        return $this->rangeId;
    }

    /**
     * @param int $rangeId
     */
    public function setRangeId($rangeId)
    {
        $this->rangeId = $rangeId;
    }

    /**
     * @return AbstractRange
     */
    public function getRange()
    {
        if (!$this->range instanceof AbstractRange) {
            $this->range = AbstractRange::getById($this->rangeId, $this->getRangeType());
        }

        return $this->range;
    }

    /**
     * @param $range
     *
     * @throws Exception
     */
    public function setRange($range)
    {
        if (!$range instanceof AbstractRange) {
            throw new Exception('$zone must be instance of Zone');
        }

        $this->range = $range;
        $this->rangeId = $range->getId();
    }

    /**
     * @return string
     */
    public function getRangeType()
    {
        return $this->rangeType;
    }

    /**
     * @param string $rangeType
     */
    public function setRangeType($rangeType)
    {
        $this->rangeType = $rangeType;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param float $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return Zone
     */
    public function getZone()
    {
        if (!$this->zone instanceof Zone) {
            $this->zone = Zone::getById($this->zoneId);
        }

        return $this->zone;
    }

    /**
     * @param $zone
     *
     * @throws Exception
     */
    public function setZone($zone)
    {
        if (!$zone instanceof Zone) {
            throw new Exception('$zone must be instance of Zone');
        }

        $this->zone = $zone;
        $this->zoneId = $zone->getId();
    }

    /**
     * @return int
     */
    public function getZoneId()
    {
        return $this->zoneId;
    }

    /**
     * @param $zoneId
     */
    public function setZoneId($zoneId)
    {
        $this->zoneId = $zoneId;
    }

    /**
     * @return Carrier
     */
    public function getCarrier()
    {
        if (!$this->carrier instanceof Carrier) {
            $this->carrier = Carrier::getById($this->carrierId);
        }

        return $this->carrier;
    }

    /**
     * @param $carrier
     *
     * @throws Exception
     */
    public function setCarrier($carrier)
    {
        if (!$carrier instanceof Carrier) {
            throw new Exception('$carrier must be instance of Carrier');
        }

        $this->carrier = $carrier;
        $this->carrierId = $carrier->getId();
    }

    /**
     * @return int
     */
    public function getCarrierId()
    {
        return $this->carrierId;
    }

    /**
     * @param $carrierId
     */
    public function setCarrierId($carrierId)
    {
        $this->carrierId = $carrierId;
    }
}
