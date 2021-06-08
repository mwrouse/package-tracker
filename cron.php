<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

require_once(__DIR__.'/models/load.php');

# Get Orders that are in the 'shipped' state
$qry = (new DbQuery())
        ->select('o.*, oc.*, osl.name as order_state')
        ->from('orders', 'o')
        ->leftJoin('order_state_lang', 'osl', 'osl.`id_order_state`=o.`current_state`')
        ->leftJoin('order_carrier', 'oc', 'oc.`id_order`=o.`id_order`')
        ->where('o.`current_state`=3');

$result = Db::getInstance()->ExecuteS($qry);
if (!$result)
    exit;

foreach ($result as $order)
{
    error_log('Checking '. $order['id_order'] . ': '. $order['tracking_number']);
    $tracking_number = $order['tracking_number'];
    if (!isset($tracking_number) || empty($tracking_number))
    continue;

    $shipment = PackageTrackerShipment::Track($tracking_number);

    if ($shipment->IsDelivered() && $order->hasBeenShipped() && !$order->hasBeenDelivered()) {
        // Mark as delivered
        $o = new Order($order['id_order']);
        $o->setCurrentState(4, 1);

        error_log('Marking '. $order['id_order'] . ' as delivered');
    }
}