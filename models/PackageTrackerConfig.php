<?php


class PackageTrackerConfig
{
    const PREFIX = 'PACKAGETRACKER_';

    const API_KEY = PackageTrackerConfig::PREFIX.'API_TOKEN';

    const COLOR_UNKNOWN = PackageTrackerConfig::PREFIX.'COLOR_UNKNOWN';
    const COLOR_PRETRANSIT = PackageTrackerConfig::PREFIX.'COLOR_PRETRANSIT';
    const COLOR_TRANSIT = PackageTrackerConfig::PREFIX.'COLOR_TRANSIT';
    const COLOR_OFD = PackageTrackerConfig::PREFIX.'COLOR_OUTFORDELIVERY';
    const COLOR_DELIVERED = PackageTrackerConfig::PREFIX.'COLOR_DELIVERED';
    const COLOR_PICKUPREADY = PackageTrackerConfig::PREFIX.'COLOR_PICKUPREADY';
    const COLOR_RETURNED = PackageTrackerConfig::PREFIX.'COLOR_RETURNED';
    const COLOR_FAILURE = PackageTrackerConfig::PREFIX.'COLOR_FAILURE';
    const COLOR_CANCELLED = PackageTrackerConfig::PREFIX.'COLOR_CANCELLED';
    const COLOR_ERROR = PackageTrackerConfig::PREFIX.'COLOR_ERROR';

    const STATE_WHENSHIPPED = PackageTrackerConfig::PREFIX.'STATE_SHIPPED';
    const STATE_WHENDELIVERED = PackageTrackerConfig::PREFIX.'STATE_DELIVERED';


    public static function Get($key, $lang = null)
    {
        if ($lang)
            return Configuration::get($key, $lang);
        else
            return Configuration::get($key);
    }

    public static function Save($key, $value)
    {
        //error_log('Saving ' . $key . ' => ' . print_r($value, true));
        Configuration::updateValue($key, $value);
    }

    public static function BulkSave($arr)
    {
        foreach ($arr as $key => $value)
        {
            PackageTrackerConfig::Save($key, $value);
        }
    }


    /**
     * Returns all the values
     */
    public static function GetValues()
    {
        $languages = Language::getLanguages(false);
        $fields = [];

        foreach ($languages as $lang) {
            try {
                $fields = static::_get($fields, static::API_KEY, $lang);
            } catch (Exception $e) {
                Logger::addLog("Package Tracker hook error: {$e->getMessage()}");

            }
        }


        $fields = static::_get($fields, static::COLOR_UNKNOWN);
        $fields = static::_get($fields, static::COLOR_PRETRANSIT);
        $fields = static::_get($fields, static::COLOR_TRANSIT);
        $fields = static::_get($fields, static::COLOR_OFD);
        $fields = static::_get($fields, static::COLOR_DELIVERED);
        $fields = static::_get($fields, static::COLOR_PICKUPREADY);
        $fields = static::_get($fields, static::COLOR_RETURNED);
        $fields = static::_get($fields, static::COLOR_FAILURE);
        $fields = static::_get($fields, static::COLOR_CANCELLED);
        $fields = static::_get($fields, static::COLOR_ERROR);

        $fields = static::_get($fields, static::STATE_WHENSHIPPED);
        $fields = static::_get($fields, static::STATE_WHENDELIVERED);

        return $fields;
    }


    /**
     * Saves all the values
     */
    public static function PostProcess()
    {
        $values = [];
        $updateImagesValues = false;

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $values = static::_pp($values, static::API_KEY, $lang);

        }

        $values = static::_pp($values, static::COLOR_UNKNOWN);
        $values = static::_pp($values, static::COLOR_PRETRANSIT);
        $values = static::_pp($values, static::COLOR_TRANSIT);
        $values = static::_pp($values, static::COLOR_OFD);
        $values = static::_pp($values, static::COLOR_DELIVERED);
        $values = static::_pp($values, static::COLOR_PICKUPREADY);
        $values = static::_pp($values, static::COLOR_RETURNED);
        $values = static::_pp($values, static::COLOR_FAILURE);
        $values = static::_pp($values, static::COLOR_CANCELLED);
        $values = static::_pp($values, static::COLOR_ERROR);

        $values = static::_pp($values, static::STATE_WHENSHIPPED);
        $values = static::_PP($values, static::STATE_WHENDELIVERED);

        static::BulkSave($values);
    }


    /**
     * Returns the form for the config page
     */
    public static function GetForm()
    {
        $colors = [
            static::COLOR_PRETRANSIT => 'Pre-Transit Color',
            static::COLOR_TRANSIT => 'Transit Color',
            static::COLOR_OFD => 'Out for Delivery Color',
            static::COLOR_DELIVERED => 'Delivered Color',
            static::COLOR_PICKUPREADY => 'Ready for Pickup Color',
            static::COLOR_RETURNED => 'Return to Sender Color',
            static::COLOR_FAILURE => 'Failed to Deliver Color',
            static::COLOR_UNKNOWN => 'Unknown Color',
            static::COLOR_CANCELLED => 'Cancelled Color'
        ];

        $formFields = [
            'form' => [
                'legend' => [
                    'title' => 'Settings',
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type'  => 'text',
                        'lang'  => true,
                        'label' => 'EasyPost API Token',
                        'name'  => static::API_KEY,
                        'desc'  => 'Enter your EasyPost API Token',
                    ]
                ],
                'submit' => [
                    'title' => 'Save',
                ],
            ],
        ];

        foreach ($colors as $key => $label) {
            $formFields['form']['input'][] = [
                'type' => 'color',
                'lang' => false,
                'label' => $label,
                'name' => $key,
            ];
        }

        $languages = Language::getLanguages(false);
        $states = OrderState::getOrderStates($languages[0]['id_lang']);

        $formFields['form']['input'][] = [
            'type' => 'select',
            'lang' => false,
            'label' => 'State of Orders to Watch for Delivery',
            'name' => static::STATE_WHENSHIPPED,
            'required' => true,
            'options' => [
                'query' => $states,
                'id' => 'id_order_state',
                'name' => 'name'
            ]
        ];

        $formFields['form']['input'][] = [
            'type' => 'select',
            'lang' => false,
            'label' => 'State of Orders to Set When Delivered',
            'name' => static::STATE_WHENDELIVERED,
            'required' => true,
            'options' => [
                'query' => $states,
                'id' => 'id_order_state',
                'name' => 'name'
            ]
        ];

        return $formFields;
    }


    private static function _get(&$fields, $key, $lang = null)
    {
        if (!is_null($lang)) {
            $fields[$key][$lang['id_lang']] = Tools::getValue(
                $key.'_'.$lang['id_lang'],
                Configuration::get($key, $lang['id_lang'])
            );
        }
        else {
            $fields[$key] = Tools::getValue(
                $key,
                Configuration::get($key)
            );
            //error_log('Getting ' . $key.': '. $fields[$key]);
        }

        return $fields;
    }

    private static function _pp(&$values, $key, $lang = null)
    {
        if (!is_null($lang))
        {
            $values[$key][$lang['id_lang']] = Tools::getValue($key.'_'.$lang['id_lang']);
        }
        else {
            $values[$key] = Tools::getValue($key);
        }
        return $values;
    }



    public static function SetDefaultValues()
    {
        $defaults = [
            static::COLOR_UNKNOWN => '#ff4bbe',
            static::COLOR_PRETRANSIT => '#ffffff',
            static::COLOR_TRANSIT => '#ffbb4b',
            static::COLOR_OFD => '#fff74b',
            static::COLOR_DELIVERED => '#4eff4b',
            static::COLOR_PICKUPREADY => '#dc4bff',
            static::COLOR_RETURNED => '#4b92ff',
            static::COLOR_FAILURE => '#ff4b4b',
            static::COLOR_CANCELLED => '#ff4ba0',
            static::STATE_WHENSHIPPED => 3,
            static::STATE_WHENDELIVERED => 4,
        ];

        static::BulkSave($defaults);
    }
}
