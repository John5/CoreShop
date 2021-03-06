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
use CoreShop\Tool;
use Pimcore\Model\Element\Note;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Object\ClassDefinition;
use Pimcore\Model\Object\ClassDefinition\Data;
use Pimcore\Model\Object\Concrete;
use Pimcore\Model\User;
use Pimcore\Tool\Authentication;

/**
 * Class Base
 * @package CoreShop\Model
 */
class Base extends Concrete
{
    /**
     * Pimcore Object Class.
     *
     * @var string
     */
    public static $pimcoreClass = null;

    /**
     * get Pimcore implementation class.
     *
     * @throws Exception
     *
     * @return string
     */
    public static function getPimcoreObjectClass()
    {
        $class = get_called_class();

        if (\Pimcore::getDiContainer()->has($class)) {
            $class = \Pimcore::getDiContainer()->get($class);
        }

        return $class::$pimcoreClass;
    }

    /**
     * Create new instance of Pimcore Object.
     *
     * @throws Exception
     *
     * @return static
     */
    public static function create()
    {
        $pimcoreClass = self::getPimcoreObjectClass();

        if (\Pimcore\Tool::classExists($pimcoreClass)) {
            return new $pimcoreClass();
        }

        throw new Exception("Class $pimcoreClass not found");
    }

    /**
     * returns the class ID of the current object class.
     *
     * @return int
     */
    public static function classId()
    {
        $v = get_class_vars(self::getPimcoreObjectClass());

        return $v['o_classId'];
    }

    /**
     * Object to Array.
     * 
     * @return array
     */
    public function toArray()
    {
        return Tool::objectToArray($this);
    }

    /**
     * Admin Element Style.
     *
     * @return \Pimcore\Model\Element\AdminStyle
     */
    public function getElementAdminStyle()
    {
        if (!$this->o_elementAdminStyle) {
            $class = get_parent_class(get_called_class());
            $class .= '\\AdminStyle';

            if (\Pimcore\Tool::classExists($class)) {
                $this->o_elementAdminStyle = new $class($this);
            } else {
                $this->o_elementAdminStyle = parent::getElementAdminStyle();
            }
        }

        return $this->o_elementAdminStyle;
    }

    /**
     * @return $this
     * @throws Exception
     * @throws \Exception
     * @throws \Pimcore\Model\Element\ValidationException
     */
    public function save()
    {
        if(!Configuration::multiShopEnabled()) {
            //Multishop is disabled, so we always set the default shop

            if(property_exists($this, "shops")) {
                $this->setShops([Shop::getDefaultShop()->getId()]);
            }
        }

        return parent::save();
    }

    /**
     * @param array $config
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function getList($config = array())
    {
        //We need to re-write this method, since pimcore uses the called_class method
        $className = self::getPimcoreObjectClass();

        if (is_array($config)) {
            if ($className) {
                $listClass = $className.'\\Listing';
                $list = null;

                if (\Pimcore::getDiContainer()->has($listClass)) {
                    $listClass = \Pimcore::getDiContainer()->get($listClass);
                    
                    $list = new $listClass();
                } elseif (\Pimcore\Tool::classExists($listClass)) {
                    $list = new $listClass();
                }

                if ($list instanceof AbstractListing) {
                    $list->setValues($config);

                    return $list;
                }
            }
        }

        throw new Exception('Unable to initiate list class - class not found or invalid configuration');
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed|null
     *
     * @throws Exception
     */
    public static function __callStatic($method, $arguments)
    {
        $pimcoreClass = self::getPimcoreObjectClass();

        if (get_called_class() === $pimcoreClass) {
            return parent::__callStatic($method, $arguments);
        }

        if (!\Pimcore\Tool::classExists($pimcoreClass)) {
            throw new Exception('Calling to unkown class '.$pimcoreClass);
        }

        return call_user_func_array(array($pimcoreClass, $method), $arguments);
    }


    /**
     * Create a note for this object.
     *
     * @param $type string
     *
     * @return Note $note
     */
    public function createNote($type)
    {
        $note = new Note();
        $note->setElement($this);
        $note->setDate(time());
        $note->setType($type);

        if (\Pimcore::inAdmin()) {
            $user = Authentication::authenticateSession();
            if ($user instanceof User) {
                $note->setUser($user->getId());
            }
        }

        return $note;
    }

    /**
     * Return Topmost Master if Object is Variant
     *
     * @return AbstractModel
     */
    public function getVariantMaster()
    {
        $master = $this;

        while ($master->getType() === 'variant') {
            $master = $master->getParent();
        }

        return $master;
    }


    /**
     * @return Data[]
     * @throws \Exception
     */
    public static function getMandatoryFields() {
        $class = self::getPimcoreObjectClass();
        $key = explode("\\", $class);
        $key = $key[count($key) - 1];

        $fieldCollectionDefinition = ClassDefinition::getByName($key);
        $fields = $fieldCollectionDefinition->getFieldDefinitions();
        $mandatoryFields = [];

        foreach($fields as $field) {
            if($field instanceof Data) {
                if($field->getMandatory()) {
                    $mandatoryFields[] = $field;
                }
            }
        }

        return $mandatoryFields;
    }

    /**
     * @param $data
     * @throws \Pimcore\Model\Element\ValidationException
     */
    public static function validate($data) {
        $mandatoryFields = self::getMandatoryFields();

        foreach($mandatoryFields as $field) {
            $field->checkValidity($data[$field->getName()]);
        }
    }
}
