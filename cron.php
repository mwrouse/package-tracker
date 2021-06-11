<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

require_once(__DIR__.'/models/load.php');

$shippedState = PackageTrackerConfig::Get(PackageTrackerConfig::STATE_WHENSHIPPED);
$deliveredState = PackageTrackerConfig::Get(PackageTrackerConfig::STATE_WHENDELIVERED);


function hasOrderBeenShippedButNotDeliveredBefore($o)
{
    global $deliveredState;
    $shipped = ($o->hasBeenShipped() > 0);
    $delivered = false;

    $history = $o->getHistory(1, $deliveredState);
    $delivered = count($history) > 0;

    return $shipped && !$delivered;
}


# Get Orders that are in the 'shipped' state
$qry = (new DbQuery())
        ->select('o.*, oc.*, osl.name as order_state')
        ->from('orders', 'o')
        ->leftJoin('order_state_lang', 'osl', 'osl.`id_order_state`=o.`current_state`')
        ->leftJoin('order_carrier', 'oc', 'oc.`id_order`=o.`id_order`')
        ->where('o.`current_state`='.$shippedState);

$result = Db::getInstance()->ExecuteS($qry);
if (!$result)
    exit;


foreach ($result as $order)
{
    $tracking_number = $order['tracking_number'];
    if (!isset($tracking_number) || empty($tracking_number))
        continue;

    $shipment = PackageTrackerShipment::Track($tracking_number);

    if ($shipment->IsDelivered() )
    {
        $o = new Order($order['id_order']);

        if (hasOrderBeenShippedButNotDeliveredBefore($o))
        {
            //error_log('Marking '. $order['id_order'] . ' as delivered');
            $o->setCurrentState($deliveredState, 1);
        }
    }
}