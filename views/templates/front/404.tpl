{$css}
<div class="order-tracking-container text-center">
    <h1 class="text-error">{l s='Shipment Not Found'}</h1>
    <p>
        {l s='The shipment you requested was not found, please verify your Tracking Number below'}
    </p>

    <input type="text" class="form-control" id="tracking" value="{$tracking_number}" autocomplete="off"/>
    <button class="btn btn-success half-width full-width-mobile" id="track-btn" style="margin-top: 10px">
        {l s='Track Shipment'}
    </button>
</div>

<script>
$(document).on('click', '#track-btn', function(){
    window.location = '/track/'+ $('#tracking').val();
});
</script>