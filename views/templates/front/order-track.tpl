{$css}
<div class="order-tracking-container">
    <h1 style="margin-bottom: 10px;" class="noselect {if $is_delivered}label-success{/if}{if $is_transit}color-accent{/if}">
        {if $is_delivered}
            {l s='Order Delivered'}
        {/if}
        {if $is_transit}
            {l s='Order In Transit'}
        {/if}
        {if $is_pretransit}
            {l s='Order Shipped'}
        {/if}
    </h1>

    <div class="tracking-number">
        <span class="label">{l s='Tracking Number:'}</span> <a href="{$carrier_link}" class="textlink external-link" {if $carrier_link[0] != '#'}target="_blank"{/if}>{$tracking_number}</a>

        {if $is_transit}
            <br/>
            <span class="label">Estimated Delivery:</span> {$shipment['eta']}
        {/if}
    </div>

    <div class="shipment-details">
        {$shipment['tracking_status']['status_details']}
    </div>


    <div class="shipment-progress noselect">
        <div class="row justify-content-center">
            <div class="col-12">
                <ul id="progressbar" class="text-center">
                    <li class="step0 active"></li>
                    <li class="step0 active"></li>
                    <li class="step0{if $is_transit || $is_delivered} active{/if}"></li>
                    <li class="step0{if $is_delivered} active{/if}"></li>
                </ul>
            </div>
        </div>
        <div class="row justify-content-between d-flex text-center" style="margin-top: -10px">
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center" >
                    <p class="font-weight-bold" style="padding-left: 30px;">{l s='Processed'}</p>
                </div>
            </div>
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center">
                    <p class="font-weight-bold">{l s='Shipped'}</p>
                </div>
            </div>
            <div class="row d-flex icon-content">
                <div class="d-flex flex-column center">
                    <p class="font-weight-bold" style="margin-left: -10px">{l s='In Transit'}</p>
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
            {foreach from=array_reverse($history) item=event}
                <div class="tracking-item">
                    <div class="date">{$event['status_date']|date_format:"%B %e, %Y %I:%M %p"}</div>

                    <div class="details">
                        {$event['status_details']}
                    </div>

                    <div class="location">
                        {$event['location']['city']}{if !empty($event['location']['city']) && !empty($event['location']['state'])}, {/if}{$event['location']['state']} {$event['location']['zip']}
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