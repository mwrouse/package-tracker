{$css}
<div class="order-tracking-container text-center">
    <h1 class="noselect" style="font-size: 3em; margin-top: 10px; margin-bottom: 10px; color: {$shipment->StatusLabelColor()}">
        {$shipment->StatusLabel()}
    </h1>

    {if $shipment->IsOutForDeliveryOrBefore()}
        <div class="estimated-delivery text-center" style="margin-top: 5px">
            <span class="label small">{l s='Estimated Delivery'}</span>
            <div class="estimated-date">
                {$shipment->EstimatedDelivery()}
            </div>
        </div>
    {/if}

    <div class="shipment-details text-center">
        {$shipment->LatestMessage()}
    </div>

    <div class="shipment-progress noselect">
        <div class="row justify-content-center">
            <div class="col-12">
                <ul id="progressbar" class="text-center">
                    <li class="step0{if $shipment->IsPreTransitOrAfter()} active{/if}"></li>
                    <li class="step0{if $shipment->IsInTransitOrAfter()} active{/if}"></li>
                    <li class="step0{if $shipment->IsOutForDeliveryOrAfter()} active{/if}"></li>
                    <li class="step0{if $shipment->IsDelivered()} active{/if}"></li>
                </ul>
            </div>
        </div>
        <div class="row justify-content-between d-flex text-center" style="margin-top: -10px">
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center" >
                    <p class="font-weight-bold" style="padding-left: 30px;">{l s='Pre-Transit'}</p>
                </div>
            </div>
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center">
                    <p class="font-weight-bold">{l s='In Transit'}</p>
                </div>
            </div>
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center">
                    <p class="font-weight-bold" style="margin-left: -10px">{l s='Out for Delivery'}</p>
                </div>
            </div>
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center">
                    <p class="font-weight-bold" style="margin-left: -30px">{l s='Delivered'}</p>
                </div>
            </div>
        </div>
    </div>

    {if $is_my_order}
        <div class="order-details">
            <span>{l s='Order'} <b>{$order['reference']}</b></span>
            <span style="margin-top: 5px; margin-bottom: 5px;">{l s='Placed on'} <b>{$order['date_add']|date_format:"%B %e, %Y"}</b> for <b>{Tools::displayPrice($order['total_paid_tax_incl'])}</b></span>
            <div>
                <a href="{$order_link}" target="_blank" class="btn btn-success three-quarters-width-mobile" style="margin-bottom: 20px; ">
                    {l s='View Your Order'}
                </a>
            </div>
        </div>
    {/if}

    <div class="alert alert-info text-left" style="margin-bottom: 20px;">
        {l s='If you have any problems with your order please contact us using our'} <a href="{$link->getPageLink('contact', true)}" target="_blank" class="textlink accent-color">{l s='Contact Page'}</a>.
    </div>
</div>

</main>
<div class="clearfix col-xs-12 bg-color-dark order-tracking-container">
    <div class="wrapper slightly-smaller center shipment-history">
        <h2 style="margin-bottom: 5px">{l s='Tracking History'}</h2>

        <div class="tracking-number">
            <a href="{$carrier_link}" class="textlink external-link" {if $carrier_link[0] != '#'}target="_blank"{/if}>{$shipment->tracking_number}</a>
        </div>


        <div class="tracking-list">
            {foreach from=$history item=event}
                <div class="tracking-item">
                    <div class="date">{$event->Time()}</div>

                    <div class="details">
                        {$event->Message()}
                    </div>

                    <div class="location">
                        {$event->Location()}
                    </div>
                </div>
            {/foreach}

            <div class="tracking-item">
                <div class="date">
                    {if count($history) > 0}
                        {$history[count($history) -1]->Time()|date_format:"%B %e, %Y"}
                    {else}
                        {$oder['delivery_date']|date_format:"%B %e, %Y %I:%M %p"}
                    {/if}
                </div>
                <div class="details">
                    {$shop_name} {l s='finished your order and prepped it for shipping.'}
                </div>
            </div>
        </div>
    </div>
</div>
<main class="wrapper slightly-smaller main-container">
    <span class="small">Last Updated at <b>{$shipment->LastUpdateTime()}</b></span>