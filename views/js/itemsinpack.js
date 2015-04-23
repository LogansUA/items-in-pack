/* Items in pack module */
$(function () {
    var itemsInPack = parseInt($('#items-in-pack').html());

    if (itemsInPack > 1) {
        var $quantityPar = $('#quantity_wanted_p');
        var $quantityInput = $quantityPar.find('[name=qty]');

        var packTag = function (count) {
            return '<span id="count-items-in-pack">' + count * itemsInPack + ' шт.' + '</span>';
        };

        var inputTag = function (count) {
            return '<input type="text" class="text" name="pack" value="' + count + ' уп.' + '" />'
        };

        var count = $quantityInput.val();

        // Hide quantity real input
        $quantityInput.hide();

        // Added fake quantity input after label
        $quantityPar.find('label').after(inputTag(count));

        // Added span with full count in quantity paragraph
        $quantityPar.append(packTag(count));

        // On span click
        $quantityPar.find('span').click(function () {
            if ($(this).parent().hasClass('button-minus')) {
                if (parseInt($quantityInput.val()) > 1) {
                    count = parseInt($quantityInput.val()) - 1;
                }
            } else if ($(this).parent().hasClass('button-plus')) {
                count = parseInt($quantityInput.val()) + 1;
            }

            $('#count-items-in-pack').html(count * itemsInPack + ' шт.');
            $('[name=pack]').val(count + ' уп.');
        });
    }
});
