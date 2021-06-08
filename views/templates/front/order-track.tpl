{$css}
<div class="order-tracking-container">
    <h1 class="noselect" style="margin-bottom: 10px; color: {$shipment->StatusLabelColor()}">
        {$shipment->StatusLabel()}
    </h1>

    <div class="tracking-number">
        <span class="label">{l s='Tracking Number:'}</span> <a href="{$carrier_link}" class="textlink external-link" {if $carrier_link[0] != '#'}target="_blank"{/if}>{$shipment->tracking_number}</a>
    </div>

    {if $shipment->IsOutForDeliveryOrBefore()}
        <div class="estimated-delivery" style="margin-top: 5px">
            <span class="label">Estimated Delivery:</span> {$shipment->EstimatedDelivery()}
        </div>
    {/if}

    <div class="shipment-details">
        {$shipment->LatestMessage()}
    </div>


    <div class="shipment-progress noselect">
        <div class="row justify-content-center">
            <div class="col-12">
                <ul id="progressbar" class="text-center">
                    <li class="step0{if $shipment->IsPreTransitOrBefore()} active{/if}"></li>
                    <li class="step0{if $shipment->IsInTransitOrBefore()} active{/if}"></li>
                    <li class="step0{if $shipment->IsOutForDeliveryOrBefore()} active{/if}"></li>
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
</div>

</main>
<div class="clearfix col-xs-12 bg-color-dark order-tracking-container">
    <div class="wrapper slightly-smaller center shipment-history">
        <h2>{l s='Tracking History'}</h2>

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
                    {$order['delivery_date']|date_format:"%B %e, %Y %I:%M %p"}
                </div>
                <div class="details">
                    {$shop_name} {l s='finished your order and prepped it for shipping.'}
                </div>
            </div>
        </div>
    </div>
</div>
<main class="wrapper slightly-smaller main-container">
    <span class="small">Last Updated {$shipment->LastUpdateTime()}</span>