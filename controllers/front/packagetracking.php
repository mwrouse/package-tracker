<?php

if (!defined('_TB_VERSION_')) {
    exit;
}



class PackageTrackerPackageTrackingModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        try {
            $trackingNumber = Tools::getValue('trackingNumber');
            $this->context->smarty->assign([
                'tracking_number' => $trackingNumber
            ]);

            $order = $this->getOrderByTrackingNumber($trackingNumber);
            if (!isset($order['tracking_number']) || empty($order['tracking_number']))
                return $this->return404();

            $shipment = PackageTrackerShipment::Track($trackingNumber);
            if ($shipment == null) {
                return $this->return404();
            }

            $this->context->smarty->assign([
                'order' => $order,
                'shipment' => $shipment,
                'is_pretransit' => $shipment->IsPreTransit(),
                'is_transit' => $shipment->IsTransit(),
                'is_delivered' => $shipment->IsDelivered(),
                'out_for_delivery' => $shipment->IsOutForDelivery(),
                'history' => $shipment->History(),
                'carrier_link' => $shipment->CarrierLink(),
            ]);

            $this->setTitle('Order Tracking');

            return $this->setTemplate('order-track.tpl');
        }
        catch (Exception $e) {
            error_log(print_r($e, true));
            return $this->return404();
        }
    }



    /**
     * Returns an order with a certain tracking number
     */
    private function getOrderByTrackingNumber($trackingNumber)
    {
        $sql = (new DbQuery())
                ->select('o.*, oc.*, osl.name as order_state')
                ->from('orders', 'o')
                ->leftJoin('order_state_lang', 'osl', 'osl.`id_order_state`=o.`current_state`')
                ->leftJoin('order_carrier', 'oc', 'oc.`id_order`=o.`id_order`')
                ->where('oc.`tracking_number`="'.$trackingNumber.'"');

        $result = Db::getInstance()->ExecuteS($sql);
        if (!$result)
            return $this->getOrderByOrderReference($trackingNumber);

        if (!is_array($result) || count($result) == 0)
            return $this->getOrderByOrderReference($trackingNumber);

        return $result[0];
    }

    /**
     * Get order by tracking number
     */
    private function getOrderByOrderReference($reference)
    {
        $sql = (new DbQuery())
                ->select('o.*, oc.*')
                ->from('orders', 'o')
                ->leftJoin('order_carrier', 'oc', 'oc.`id_order`=o.`id_order`')
                ->where('o.`reference`="'.$reference.'"');

        return null;
    }


    /**
     * Sets the page title
     */
    private function setTitle($title)
    {

        $css = file_get_contents(__DIR__ . '/../../css/packagetracker.css');
        $css = '<style>'.$css.'</style>';
        $this->context->smarty->assign(['css' => $css]);

        $this->context->smarty->assign([
            'meta_title' => $title . ' - ' . $this->context->shop->name,
            'no_breadcrumbs' => true,
            'css' => $css
        ]);
    }


    /**
     * Returns the 404 error page
     */
    private function return404()
    {
        $this->setTitle('Not Found');
        return $this->setTemplate('404.tpl');
    }

}