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
    const COLOR_ERROR = PackageTrackerConfig::PREFIX.'ERROR';


    public static function Get($key, $lang = null)
    {
        if ($lang)
            return Configuration::get($key, $lang);
        else
            return Configuration::get($key);
    }

    public static function Save($key, $value)
    {
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
                $fields = static::_get($fields, static::API_KEY, $lang, true);
                $fields = static::_get($fields, static::COLOR_UNKNOWN, $lang);
                $fields = static::_get($fields, static::COLOR_PRETRANSIT, $lang);
                $fields = static::_get($fields, static::COLOR_TRANSIT, $lang);
                $fields = static::_get($fields, static::COLOR_OFD, $lang);
                $fields = static::_get($fields, static::COLOR_DELIVERED, $lang);
                $fields = static::_get($fields, static::COLOR_PICKUPREADY, $lang);
                $fields = static::_get($fields, static::COLOR_RETURNED, $lang);
                $fields = static::_get($fields, static::COLOR_FAILURE, $lang);
                $fields = static::_get($fields, static::COLOR_CANCELLED, $lang);
                $fields = static::_get($fields, static::COLOR_ERROR, $lang);

            } catch (Exception $e) {
                Logger::addLog("Package Tracker hook error: {$e->getMessage()}");

            }
        }

        return $fields;
    }


    /**
     * Saves all the values
     */
    public static function PostProcess()
    {
        $languages = Language::getLanguages(false);
        $values = [];
        $updateImagesValues = false;

        foreach ($languages as $lang) {
            $values = static::_pp($values, static::API_KEY, $lang, true);
            $values = static::_pp($values, static::COLOR_UNKNOWN, $lang);
            $values = static::_pp($values, static::COLOR_PRETRANSIT, $lang);
            $values = static::_pp($values, static::COLOR_TRANSIT, $lang);
            $values = static::_pp($values, static::COLOR_OFD, $lang);
            $values = static::_pp($values, static::COLOR_DELIVERED, $lang);
            $values = static::_pp($values, static::COLOR_PICKUPREADY, $lang);
            $values = static::_pp($values, static::COLOR_RETURNED, $lang);
            $values = static::_pp($values, static::COLOR_FAILURE, $lang);
            $values = static::_pp($values, static::COLOR_CANCELLED, $lang);
            $values = static::_pp($values, static::COLOR_ERROR, $lang);
        }

        static::BulkSave($values);
        //Configuration::updateValue(static::API_KEY, $values[static::API_KEY]);
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
        return $formFields;
    }


    private static function _get(&$fields, $key, $lang, $useLang = false)
    {
        if ($useLang) {
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
        }

        return $fields;
    }

    private static function _pp(&$values, $key, $lang, $useLang = false)
    {
        if ($useLang)
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
        ];

        static::BulkSave($defaults);
    }
}
