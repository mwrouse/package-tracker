<?php

if (!defined('_TB_VERSION_')) {
    exit;
}

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/models/load.php');

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
        $this->table_name = PackageTrackerConfig::TABLE_NAME;
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

    public function renderForm()
    {
        $fields = PackageTrackerConfig::GetForm();

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
            'fields_value' => PackageTrackerConfig::GetValues(),
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$fields]);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            PackageTrackerConfig::PostProcess();
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
     *    Install/Uninstall   *
     **************************/
    public function install()
    {
        if (!parent::install() || ! $this->_createDatabases()) {
            return false;
        }

        PackageTrackerConfig::SetDefaultValues();

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