<!-- Block items in pack module -->
<div class="panel product-tab">
    <h3>{l s='Items in pack' mod='itemsinpack'}</h3>

    <div class="form-group">
        <label class="control-label col-lg-3" for="pack">Items in pack</label>
        <div class="col-lg-5">
            <input id="pack" class="form-control fixed-width-sm" min="1" name="items-in-pack" type="number" value="{$itemsInPack}" />
        </div>
    </div>

    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="process-icon-cancel"></i> {l s='Cancel'}
        </a>

        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right" disabled="disabled">
            <i class="process-icon-loading"></i> {l s='Save'}
        </button>

        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" disabled="disabled">
            <i class="process-icon-loading"></i>{l s='Save and stay'}
        </button>
    </div>
</div>
<!-- /Block items in pack module -->


