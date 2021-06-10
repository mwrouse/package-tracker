<?php


class PackageTrackerShipment extends PackageTrackerShipmentStatus
{
    public $tracking_number;
    public $carrier;

    public $weight;
    public $est_delivery;

    public $raw;

    private $created_at;
    private $updated_at;

    private $_historyCache = null;


    /**
     * The "constructor" that you should use
     */
    public static function Track($tracking_number)
    {
        $carrier = static::DetermineCarrier($tracking_number);
        $shipment = PackageTrackerAPI::GetTrackingInfo($tracking_number, $carrier);

        return new PackageTrackerShipment($shipment);
    }


    public function __construct($shipment)
    {
        $this->raw = $shipment;

        $this->tracking_number = $shipment['tracking_code'];
        $this->carrier = $shipment['carrier'];
        $this->status = $shipment['status'];
        $this->weight = $shipment['weight'];

        $this->est_delivery = $shipment['est_delivery_date'];

        $this->created_at = $shipment['created_at'];
        $this->updated_at = $shipment['updated_at'];

        $this->_historyCache = $this->AssembleHistory();
    }



    /**
     * Returns the formatted estimated delivery date
     */
    public function EstimatedDelivery()
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('UTC'));
        $dt->setTimeStamp(strtotime($this->est_delivery));
        return $dt->format('l, F d');
    }


    /**
     * Returns the carrier link
     */
    public function CarrierLink()
    {
        $carrier = $this->carrier;
        if (is_null($carrier))
            return '#';
        $url = '#';

        switch (strtolower($carrier)) {
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

        return $url.$this->tracking_number;
    }

    /**
     * Returns the latest message
     */
    public function LatestMessage()
    {
        if ($this->IsUnknown())
            return 'This shipment has only been created, please allow 12-24 hours for tracking updates.';

        $this->AssembleHistory();

        if (count($this->_historyCache) == 0)
            return 'There are no updates about this shipment yet';

        return $this->_historyCache[0]->Message();
    }

    public function LastUpdateTime()
    {
        return date('M j, Y g:i a', strtotime($this->updated_at));
    }


    /**
     * Parses and returns the history
     */
    public function AssembleHistory()
    {
        if (!is_null($this->_historyCache))
            return $this->_historyCache;

        $final = [];

        foreach(array_reverse($this->raw['tracking_details']) as $event)
        {
            array_push($final, new PackageTrackerShipmentHistory($event));
        }

        return $final;
    }


    /**
     * Returns the history
     */
    public function History()
    {
        if (is_null($this->_historyCache))
            $this->_historyCache = $this->AssembleHistory();

        return $this->_historyCache;
    }







    /**
     * Determines the carrier based on the tracking number
     */
    private static function DetermineCarrier($tracking_number)
    {
        $matches = [];
        $carrier_code = null;

        if (preg_match('/^[0-9]{2}[0-9]{4}[0-9]{4}$/', $tracking_number, $matches)) {
            $carrier_code = 'dhl';
        } elseif (preg_match('/^[1-9]{4}[0-9]{4}[0-9]{4}$/', $tracking_number, $matches)) {
            $carrier_code = 'fedex';
        } elseif (preg_match('/^1Z[A-Z0-9]{3}[A-Z0-9]{3}[0-9]{2}[0-9]{4}[0-9]{4}$/i', $tracking_number)) {
            $carrier_code = 'ups';
        } elseif (preg_match('/^[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{2}$/', $tracking_number)) {
            $carrier_code = 'usps';
        } elseif (preg_match(
            '/^420[0-9]{5}([0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{4}[0-9]{2})$/',
            $tracking_number,
            $matches
        )) {
            //$this->tracking_number = $matches[1];

            $carrier_code = 'usps';
        }

        if (is_null($carrier_code))
        {
            return 'usps';
        }

        return $carrier_code;
    }
}



class PackageTrackerShipmentHistory extends PackageTrackerShipmentStatus
{
    public $raw;

    public $message;
    public $time;
    public $location;


    public function __construct($history)
    {
        $this->raw = $history;

        $this->status = $history['status'];

        $this->message = $history['message'];
        $this->time = $history['datetime'];
        $this->location = $history['tracking_location'];
    }

    public function Time()
    {
        $dt = new DateTime();
        $dt->setTimeZone(new DateTimeZone('UTC'));
        $dt->setTimeStamp(strtotime($this->time));
        $format = 'F j, Y';

        if ($dt->format('H:i') != '00:00')
            $format .= ' g:i a';

        return $dt->format($format);
    }

    public function Message()
    {
        return $this->message;
    }


    public function Location()
    {
        $location = $this->location['city'];
        if (!empty($this->location['state']))
        {
            if (!empty($location))
                $location .= ', ';
            $location .= $this->location['state'];
        }

        if (!empty($this->location['zip']))
            $location .= ' ' . $this->location['zip'];

        return $location;
    }
}



class PackageTrackerShipmentStatus extends ObjectModel
{
    public $status;


    /**
     * Returns the status label
     */
    public function StatusLabel()
    {
        if ($this->IsPreTransit())
        {
            return 'In Pre-Transit';
        }
        else if ($this->IsTransit())
        {
            return 'In Transit';
        }
        else if ($this->IsOutForDelivery())
        {
            return 'Out for Delivery';
        }
        else if ($this->IsDelivered())
        {
            return 'Delivered';
        }
        else if ($this->IsUnknown())
        {
            return 'Pending';
        }
        else if ($this->IsFailure())
        {
            return 'Delivery Failed';
        }
        else if ($this->IsReturnToSender())
        {
            return 'Returning to Sender';
        }
        else if ($this->IsReadyForPickup())
        {
            return 'Ready for Pickup';
        }
    }

    /**
     * Returns color based on status
     */
    public function StatusLabelColor()
    {
        if ($this->IsPreTransit())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_PRETRANSIT);
        }
        else if ($this->IsTransit())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_TRANSIT);
        }
        else if ($this->IsOutForDelivery())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_OFD);
        }
        else if ($this->IsDelivered())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_DELIVERED);
        }
        else if ($this->IsUnknown())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_UNKNOWN);
        }
        else if ($this->IsFailure())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_FAILURE);
        }
        else if ($this->IsReturnToSender())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_RETURNED);
        }
        else if ($this->IsReadyForPickup())
        {
            return PackageTrackerConfig::Get(PackageTrackerConfig::COLOR_PICKUPREADY);
        }
    }

    /**
     * Helper Functions
     */
    public function IsPreTransit()
    {
        return $this->status == PackageTrackerStatus::PRETRANSIT;
    }

    public function IsTransit()
    {
        return $this->status == PackageTrackerStatus::TRANSIT;
    }

    public function IsOutForDelivery()
    {
        return $this->status == PackageTrackerStatus::OUT_FOR_DELIVERY;
    }

    public function IsDelivered()
    {
        return $this->status == PackageTrackerStatus::DELIVERED;
    }

    public function IsReturnToSender()
    {
        return $this->status == PackageTrackerStatus::RETURN_TO_SENDER;
    }

    public function IsReadyForPickup()
    {
        return $this->status == PackageTrackerStatus::READY_FOR_PICKUP;
    }

    public function IsUnknown()
    {
        return $this->status == PackageTrackerStatus::UNKNOWN;
    }

    public function IsFailure()
    {
        return $this->status == PackageTrackerStatus::FAILURE;
    }


    public function IsPreTransitOrAfter()
    {
        return $this->IsPreTransit() || $this->IsInTransitOrAfter();
    }

    public function IsInTransitOrAfter()
    {
        return $this->IsTransit() || $this->IsOutForDeliveryOrAfter();
    }

    public function IsInTransitOrBefore()
    {
        return $this->IsTransit() || $this->IsPreTransit();
    }

    public function IsOutForDeliveryOrAfter()
    {
        return $this->IsOutForDelivery() || $this->IsDelivered();
    }

    public function IsOutForDeliveryOrBefore()
    {
        return $this->IsOutForDelivery() || $this->IsInTransitOrBefore();
    }
}
