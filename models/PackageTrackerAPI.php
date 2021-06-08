<?php


class PackageTrackerAPI
{
    public static function GetTrackingInfo($tracking_number, $carrier)
    {
        \EasyPost\EasyPost::setApiKey(PackageTrackerConfig::Get(PackageTrackerConfig::API_KEY, 1));

        $tracker = \EasyPost\Tracker::create([
            'tracking_code' => $tracking_number,
            'carrier' => $carrier
        ]);

        return $tracker;
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