$(function () {
    var packTag = function (count) {
        return '<span id="count-items-in-pack" style="width: auto">' + count + ' шт.' + '</span>';
    };
    var inputTag = function (count) {
        return '<input type="text" class="cart_quantity_input form-control grey" name="pack" value="' + count + ' уп.' + '" />'
    };

    $('tbody').find('tr').each(function () {
        var $tbody = $(this);

        var itemsInPack = parseInt($(this).attr('id').match(/\d+/g)[4]);
        var count = parseInt($(this).find('.cart_quantity input[type="hidden"]').val());
        var pack = count / itemsInPack;

        $(this).find('.cart_quantity_input').hide();
        $(this).find('.cart_quantity_input').after(inputTag(pack));

        $(this).find('[name="pack"]').prop('readonly', true);
        $(this).find('[name="pack"]').after(packTag(pack * itemsInPack));

        $(this).find('.cart_quantity_up').off('click').on('click', function(e){
            e.preventDefault();
            upQuantity($(this).attr('id').replace('cart_quantity_up_', ''), itemsInPack);

            $tbody.find('[name=pack]').val(parseInt($tbody.find('[name=pack]').val()) + 1 + ' уп.');

            var totalItems = parseInt($tbody.find('[name=pack]').val()) * itemsInPack;

            $tbody.find('#count-items-in-pack').remove();
            $tbody.find('[name="pack"]').after(packTag(totalItems));
        });

        $(this).find('.cart_quantity_down').off('click').on('click', function(e){
            e.preventDefault();
            downQuantity($(this).attr('id').replace('cart_quantity_down_', ''), itemsInPack);

            $tbody.find('[name=pack]').val(parseInt($tbody.find('[name=pack]').val()) - 1 + ' уп.');

            var totalItems = parseInt($tbody.find('[name=pack]').val()) * itemsInPack;

            $tbody.find('#count-items-in-pack').remove();
            $tbody.find('[name="pack"]').after(packTag(totalItems));
        });
    });
});
