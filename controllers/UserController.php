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

use CoreShop\Controller\Action;
use CoreShop\Tool;
use CoreShop\Exception;
use CoreShop\Model\Country;
use Pimcore\Model\Object;

/**
 * Class CoreShop_UserController
 */
class CoreShop_UserController extends Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        //Users are not allowed in CatalogMode
        if (\CoreShop\Model\Configuration::isCatalogMode()) {
            $this->redirect($this->view->url(array(), 'coreshop_index'));
        }

        if ($this->getParam('action') != 'login' && $this->getParam('action') != 'register') {
            if (!$this->session->user instanceof \CoreShop\Model\User) {
                $this->_redirect($this->view->url(array('lang' => $this->language), 'coreshop_index'));
                exit;
            }
        }
    }

    public function indexAction()
    {
    }

    public function profileAction()
    {
    }

    public function ordersAction()
    {
    }

    public function settingsAction()
    {
        $this->view->success = false;

        if ($this->getRequest()->isPost()) {
            try {
                $params = $this->getAllParams();

                if ($params['password']) {
                    if ($params['password'] != $params['repassword']) {
                        throw new Exception('Passwords do not match!');
                    }
                }

                CoreShop\Model\User::validate($params);

                $this->session->user->setValues($params);
                $this->session->user->save();

                $this->view->success = true;

                if (array_key_exists('_redirect', $params)) {
                    $this->_redirect($params['_redirect']);
                }
            } catch (\Exception $ex) {
                $this->view->message = $ex->getMessage();
            }
        }
    }

    public function logoutAction()
    {
        $this->session->user = null;
        $this->session->cartId = null;

        $this->_redirect('/'.$this->language.'/shop');
    }

    public function loginAction()
    {
        if ($this->session->user instanceof \CoreShop\Model\User) {
            $this->redirect($this->view->url(array('lang' => $this->language, 'act' => 'profile'), 'coreshop_user'));
        }

        $redirect = $this->getParam('_redirect', $this->view->url(array('act' => 'address'), 'coreshop_checkout'));
        $base = $this->getParam('_base');

        if ($this->getRequest()->isPost()) {
            $user = \CoreShop\Model\User::getUniqueByEmail($this->getParam('email'));

            if ($user instanceof \CoreShop\Model\User) {
                try {
                    $isAuthenticated = $user->authenticate($this->getParam('password'));

                    if ($isAuthenticated) {
                        $this->session->user = $user;

                        //Reset country
                        unset($this->session->countryId);

                        if (count($this->cart->getItems()) <= 0) {
                            $cart = $user->getLatestCart();

                            if ($cart instanceof \CoreShop\Model\Cart) {
                                $this->session->cartId = $cart->getId();
                            }
                        }

                        $this->_redirect($redirect);
                    }
                } catch (Exception $ex) {
                    $this->view->message = $this->view->translate($ex->getMessage());
                }
            } else {
                $this->view->message = $this->view->translate('User not found');
            }
        }

        if ($base) {
            $this->_redirect($base.'?message='.$this->view->message);
        }
    }

    public function registerAction()
    {
        if ($this->session->user instanceof \CoreShop\Model\User) {
            $this->redirect($this->view->url(array('lang' => $this->language, 'act' => 'profile'), 'coreshop_user'));
        }

        if ($this->getRequest()->isPost()) {
            $params = $this->getAllParams();

            $addressParams = array();
            $userParams = array();

            foreach ($params as $key => $value) {
                if (startsWith($key, 'address_')) {
                    $addressKey = str_replace('address_', '', $key);

                    $addressParams[$addressKey] = $value;
                } else {
                    $userParams[$key] = $value;
                }
            }

            try {
                $isGuest = intval($this->getParam('isGuest', 0)) === 1;

                if ($isGuest && !\CoreShop\Model\Configuration::isGuestCheckoutActivated()) {
                    throw new Exception\UnsupportedException('Guest checkout is disabled');
                }

                //Check User exists
                if (\CoreShop\Model\User::getUserByEmail($userParams['email']) instanceof \CoreShop\Model\User) {
                    throw new Exception('E-Mail already exists');
                }

                $folder = '/users/' . strtolower(substr($userParams['lastname'], 0, 1));
                $key = Pimcore\File::getValidFilename($userParams['email']);

                if ($isGuest) {
                    $folder = '/guests/' . strtolower(substr($userParams['lastname'], 0, 1));
                    $key = Pimcore\File::getValidFilename($userParams['email'] . ' ' . time());
                }

                //Check for European VAT Number
                if (\CoreShop\Model\Configuration::get('SYSTEM.BASE.CHECKVAT')) {
                    if ($addressParams['vatNumber']) {
                        if (!Tool::validateVatNumber($addressParams['vatNumber'])) {
                            throw new Exception($this->view->translate('Invalid VAT Number'));
                        }
                    }
                }

                $addresses = new Object\Fieldcollection();

                \CoreShop\Model\User::validate($userParams); //Throws Exception if failing
                \CoreShop\Model\User\Address::validate($addressParams); //Throws Exception if failing

                $address = \CoreShop\Model\User\Address::create();
                $address->setValues($addressParams);
                $address->setCountry(Country::getById($addressParams['country']));

                $addresses->add($address);

                if ($isGuest) {
                    //Set billing and shipping address in cart
                    $this->cart->setBillingAddress(clone $addresses);
                    $this->cart->setShippingAddress(clone $addresses);
                    $this->cart->save();
                }

                $user = \CoreShop\Model\User::create();
                $user->setKey($key);
                $user->setPublished(true);
                $user->setParent(Pimcore\Model\Object\Service::createFolderByPath($folder));
                $user->setValues($userParams);
                $user->setAddresses($addresses);
                $user->save();

                \Pimcore::getEventManager()->trigger('coreshop.user.postAdd', $this, array('request' => $this->getRequest(), 'user' => $user));

                $this->session->user = $user;

                if (array_key_exists('_redirect', $params)) {
                    $this->redirect($params['_redirect']);
                } else {
                    $this->redirect($this->view->url(array('lang' => $this->view->language, 'act' => 'profile'), 'coreshop_user'));
                }
            } catch (\Exception $ex) {
                if (array_key_exists('_error', $params)) {
                    $this->redirect($params['_error'].'?error='.$ex->getMessage());
                }

                $this->view->error = $ex->getMessage();
            }
        }
    }

    public function addressesAction()
    {
    }

    public function addressAction()
    {
        $this->view->redirect = $this->getParam('redirect', $this->view->url(array('lang' => $this->language, 'act' => 'addresses'), 'coreshop_user', true));
        $update = $this->getParam('address');
        $this->view->isNew = false;

        foreach ($this->session->user->getAddresses() as $address) {
            if ($address->getName() === $update) {
                $this->view->address = $address;
            }
        }

        if (!$this->view->address instanceof \CoreShop\Model\User\Address) {
            $this->view->address = \CoreShop\Model\User\Address::create();
            $this->view->isNew = true;
        }

        if ($this->getRequest()->isPost()) {
            try {
                $params = $this->getAllParams();

                $addressParams = array();

                foreach ($params as $key => $value) {
                    if (startsWith($key, 'address_')) {
                        $addressKey = str_replace('address_', '', $key);

                        $addressParams[$addressKey] = $value;
                    }
                }

                //Check for European VAT Number
                if (\CoreShop\Model\Configuration::get('SYSTEM.BASE.CHECKVAT')) {
                    if ($addressParams['vatNumber']) {
                        if (!Tool::validateVatNumber($addressParams['vatNumber'])) {
                            throw new Exception($this->view->translate('Invalid VAT Number'));
                        }
                    }
                }

                \CoreShop\Model\User\Address::validate($addressParams); //Throws Exception if failing

                $adresses = $this->session->user->getAddresses();

                if (!$adresses instanceof Object\Fieldcollection) {
                    $adresses = new Object\Fieldcollection();
                }

                if ($update) {
                    for ($i = 0; $i < count($this->session->user->getAddresses()); ++$i) {
                        if ($this->session->user->getAddresses()->get($i)->getName() == $update) {
                            //$this->session->user->getAddresses()->remove($i);
                            break;
                        }
                    }
                }

                $this->view->address->setValues($addressParams);
                //TODO: Check if country exists and is valid
                $this->view->address->setCountry(Country::getById($addressParams['country']));

                if ($this->view->isNew) {
                    $adresses->add($this->view->address);
                }

                $this->session->user->save();

                if (array_key_exists('_redirect', $params)) {
                    $this->redirect($params['_redirect']);
                } else {
                    $this->redirect('/de/shop');
                }
            } catch (Exception $ex) {
                $this->view->error = $ex->getMessage();
            }
        }
    }

    public function deleteaddressAction()
    {
        $address = $this->getParam('address');
        $i = -1;

        foreach ($this->session->user->getAddresses() as $a) {
            ++$i;

            if ($a->getName() === $address) {
                break;
            }
        }

        if ($i >= 0) {
            $this->session->user->getAddresses()->remove($i);
        }

        $this->_redirect($this->view->url(array('lang' => $this->language, 'act' => 'addresses'), 'coreshop_user', true));
    }
}
