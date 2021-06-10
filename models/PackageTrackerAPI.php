<?php


class PackageTrackerAPI
{
    public static function GetTrackingInfo($tracking_number, $carrier)
    {
        /*$cache = static::GetCacheIfNotExpired($tracking_number);
        if (!is_null($cache) && !static::IsCacheExpired($cache))
            return $cache['data'];
*/
        // Fetch new
        \EasyPost\EasyPost::setApiKey(PackageTrackerConfig::Get(PackageTrackerConfig::API_KEY, 1));

        $tracker = \EasyPost\Tracker::create([
            'tracking_code' => $tracking_number,
            'carrier' => $carrier
        ]);

        //static::SaveCache($tracking_number, $carrier, $tracker, $cache);
        return $tracker;
    }


    /**
     * Checks the database for cache
     */
    private static function GetCacheIfNotExpired($tracking_number)
    {
        try {
            $qry = (new DbQuery())
                    ->select('*')
                    ->from(PackageTrackerConfig::TABLE_NAME)
                    ->where('`tracking_number`="'.$tracking_number.'"');

            $result = Db::getInstance()->ExecuteS($qry);
            if (!$result || count($result) == 0)
                return null;

            $cache = $result[0];

            return [
                'id' => $cache['id'],
                'data' => unserialize($cache['cache']),
                'time' => $cache['last_update']
            ];
        }
        catch (Exception $e) {
            return null;
        }
    }

    /**
     * Checks if a cache is expired
     */
    private static function IsCacheExpired($cache)
    {
        if (is_null($cache))
            return true;

        $refreshTime = 10; //PackageTrackerConfig::Get(PackageTrackerConfig::CACHE_TIME);

        if (strtotime($cache['time']) < strtotime("-".$refreshTime." minutes"))
        {
            return true;
        }

        return false;
    }


    /**
     * Saves cache
     */
    private static function SaveCache($tracking_number, $carrier, $data, $previousCache = null)
    {
        if (!is_null($previousCache))
        {
            error_log('howdy.'.$previousCache['id']);
            Db::getInstance()->update(
                PackageTrackerConfig::TABLE_NAME,
                [
                    'cache' => serialize($data),
                    'last_update' => (new DateTime())->format('Y-m-d H-i-s')
                ],
                'id='.$previousCache['id']
            );
        }
        else {
            Db::getInstance()->delete(PackageTrackerConfig::TABLE_NAME, 'tracking_number="'.$tracking_number.'"');

            Db::getInstance()->insert(
                PackageTrackerConfig::TABLE_NAME,
                [
                    'tracking_number' => $tracking_number,
                    'cache' => serialize($data),
                    'carrier' => $carrier,
                ]
            );
        }
    }
}

/**
 * Status specific to the api
 */
class PackageTrackerStatus
{
    const PRETRANSIT = 'pre_transit';
    const TRANSIT = 'in_transit';
    const OUT_FOR_DELIVERY = 'out_for_delivery';
    const DELIVERED = 'delivered';

    const UNKNOWN = 'unknown';
    const READY_FOR_PICKUP = 'available_for_pickup';
    const RETURN_TO_SENDER = 'return_to_sender';

    const FAILURE = 'failure';
    const CANCELLED = 'cancelled';
    const ERROR = 'error';
}