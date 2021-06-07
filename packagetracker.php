<?php

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once(__DIR__ . '/vendor/autoload.php');

/**
 * Module for Tracking Packages
 */
class PackageTracker extends Module
{
    protected $hooksList = [];

    public function __construct()
    {
        $this->name = 'packagetracker';
        $this->className = 'PackageTracker';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Michael Rouse';
        $this->tb_min_version = '1.0.0';
        $this->tb_versions_compliancy = '> 1.0.0';
        $this->need_instance = 0;
        $this->table_name = 'packagetracker';
        $this->bootstrap = true;

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.6.99.99'];

        $this->hooksList = [
            'moduleRoutes',
        ];

        parent::__construct();

        $this->displayName = $this->l('Package Tracker');
        $this->description = $this->l('Adds a custom order tracking page');
    }


    /**************************
     *          Hooks         *
     **************************/

    /**
     * Register routes for this module
     */
    public function hookModuleRoutes()
    {
        $routes = [
            'order-tracking' => [
                'controller' => 'packagetracking',
                'rule' => 'track/{trackingNumber}{e:/}',
                'keywords' => [
                    'trackingNumber' => ['regexp' => '[_a-zA-Z0-9-\pL]*', 'param' => 'trackingNumber'],
                    'e' => ['regexp' => '']
                ],
                'params' => [
                    'fc' => 'module',
                    'module' => $this->name
                ]
            ]
        ];

        return $routes;
    }



     /**************************
     *          Config         *
     **************************/

    const API_KEY = 'PACKAGETRACKER_SHIPPO_API_TOKEN';

    public function renderForm()
    {
        $formFields = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => $this->l('Shippo API Token'),
                        'name'  => static::API_KEY,
                        'desc'  => $this->l('Enter your Shippo API Token'),
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG')
            : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$formFields]);
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            try {
                $fields[static::API_KEY][$lang['id_lang']] = Tools::getValue(
                    static::API_KEY.'_'.$lang['id_lang'],
                    Configuration::get(static::API_KEY, $lang['id_lang'])
                );
            } catch (Exception $e) {
                Logger::addLog("Package Tracker hook error: {$e->getMessage()}");
                $fields[static::API_KEY][$lang['id_lang']] = '';
            }
        }

        return $fields;
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $languages = Language::getLanguages(false);
            $values = [];
            $updateImagesValues = false;

            foreach ($languages as $lang) {

                $idLang = (int)$lang['id_lang'];

                $values[static::API_KEY][$idLang] = Tools::getValue(static::API_KEY . '_'. $idLang);

            }


            Configuration::updateValue(static::API_KEY, $values[static::API_KEY]);

            return $this->displayConfirmation($this->l('The settings have been updated.'));
        }

        return '';
    }


     /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getContent()
    {
        try {
            return $this->postProcess().$this->renderForm();
        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();

            return '';
        }
    }


    /**************************
     *    Package Tracking    *
     **************************/

    /**
     * Returns tracking info for a tracking number
     */
    public function getTrackingInfo($tracking_number)
    {
        $cache = $this->getCacheForTrackingInfo($tracking_number);
        if (!is_null($cache) && !is_null($cache['cache']))
        {
            $cacheData = unserialize($cache['cache']);
            if ($cacheData['tracking_status']['status'] == 'DELIVERED')
                return $cacheData; // Don't update delivered data

            $cacheDate = strtotime($cache['last_update']);
            $currentDate = strtotime(date('Y-m-d H:i:s'));

            $minutesDiff = (time() - $cacheDate) / 60;

            if ($minutesDiff < 60)
            {
                return $cacheData;
            }
        }

        // Fetch new data from shippo
        $carrier = is_null($cache) ? $this->getCarrier($tracking_number) : $cache['carrier'];
        if (is_null($carrier)) {
            return null;
        }

        $latest = $this->getLatestTracking($tracking_number, $carrier);

        if ($latest == null)
            return null;

        if (is_null($cache))
            $this->saveCacheForTrackingInfo($tracking_number, $carrier, $latest);
        else
            $this->updateCacheForTrackingInfo($cache['id'], $tracking_number, $latest);

        return $latest;
    }



    /**
     * Returns the last cache for a tracking number
     */
    private function getCacheForTrackingInfo($tracking_number)
    {
        $sql = (new DbQuery())
                ->select('*')
                ->from($this->table_name)
                ->where('tracking_number="'.$tracking_number.'"');

        $result = Db::getInstance()->ExecuteS($sql);
        if (!$result || count($result) == 0)
            return null;

        return $result[0];
    }


    /**
     * Updates cache stored in the database
     */
    private function updateCacheForTrackingInfo($id, $tracking_number, $data)
    {
        Db::getInstance()->update(
            $this->table_name,
            [
                'tracking_number' => $tracking_number,
                'cache' => serialize($data),
                'last_update' => date('Y-m-d H:i:s')
            ],
            'id='.$id
        );
    }


    /**
     * Saves cache for the first time
     */
    private function saveCacheForTrackingInfo($tracking_number, $carrier, $data)
    {
        Db::getInstance()->insert(
            $this->table_name,
            [
                'tracking_number' => $tracking_number,
                'carrier' => $carrier,
                'cache' => serialize($data),
                'last_update' => date('Y-m-d H:i:s')
            ]
        );
    }


    /**
     * Returns entire tracking object
     */
    private function getLatestTracking($trackingNumber, $carrier)
    {
        Shippo::setApiKey(Configuration::get(static::API_KEY, $this->context->language->id));

        $status_params = array(
            'id' => $trackingNumber,
            'carrier' => $carrier
        );

        $status = Shippo_Track::get_status($status_params);
        return $status;
    }


    /**
     * Returns the carrier code based on the tracking number
     */
    private function getCarrier($tracking_code)
    {
        $matches = [];
        $carrier_code = null;

        if (preg_match('/^[0-9]{2}[0-9]{4}[0-9]{4}$/', $tracking_code, $matches)) {
            $carrier_code = 'dhl';
        } elseif (preg_match('/^[1-9]{4}[0-9]{4}[0-9]{4}$/', $tracking_code, $matches)) {
            $carrier_code = 'fedex';
        } elseif (preg_match('/^1Z[A-Z0-9]{3}[A-Z0-9]{3}[0-9]{2}[0-9]{4}[0-9]{4}$/i', $tracking_code)) {
            $carrier_code = 'ups';
        } elseif (preg_match('/^[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{2}$/', $tracking_code)) {
            $carrier_code = 'usps';
        } elseif (preg_match(
            '/^420[0-9]{5}([0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{2})$/',
            $tracking_code,
            $matches
        )) {
            $this->tracking_code = $matches[1];

            $carrier_code = 'usps';
        }
        if (is_null($carrier_code))
        {
            return 'usps';
        }
        return $carrier_code;
    }


    public function getCarrierLink($tracking_number)
    {
        $carrier = $this->getCarrier($tracking_number);
        if (is_null($carrier))
            return '#';
        $url = '#';

        switch ($carrier) {
            case 'usps':
                $url = 'https://tools.usps.com/go/TrackConfirmAction?qtc_tLabels1=';
                break;

            case 'fedex':
                $url = 'http://www.fedex.com/Tracking?tracknumbers=';
                break;

            case 'ups':
                $url = 'https://www.ups.com/track?loc=en_US&tracknum=';
                break;

            case 'dhl':
                $url = 'http://webtrack.dhlglobalmail.com/?trackingnumber=';
                break;
        }

        return $url.$tracking_number;
    }


    /**************************
     *    Install/Uninstall   *
     **************************/
    public function install()
    {
        if (!parent::install() || ! $this->_createDatabases()) {
            return false;
        }

        $this->installFixture();

        return $this->_registerHooks();
    }

    private function _registerHooks()
    {
        foreach ($this->hooksList as $hook) {
            if ( ! $this->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }


    public function uninstall()
    {
        if (!parent::uninstall() || ! $this->_eraseDatabases()) {
            return false;
        }

        return true;
    }


     /**
     * Create Database Tables
     */
    private function _createDatabases()
    {
        $sql = 'CREATE TABLE  `'._DB_PREFIX_.$this->table_name.'` (
                    `id` INT( 12 ) AUTO_INCREMENT,
                    `tracking_number` VARCHAR(255) NOT NULL,
                    `carrier` VARCHAR(255) NOT NULL,
                    `cache` LONGTEXT DEFAULT NULL,
                    `last_update` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (  `id` )
                ) ENGINE =' ._MYSQL_ENGINE_;


        if (!Db::getInstance()->Execute($sql))
        {
            return false;
        }

        return true;
    }

    /**
     * Remove Database Tables
     */
    private function _eraseDatabases()
    {
        if ( ! Db::getInstance()->Execute('DROP TABLE `'._DB_PREFIX_.$this->table_name.'`'))
        {
            return false;
        }

        return true;
    }

}