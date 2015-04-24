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

        var items = 1;

        // Hide quantity real input
        $quantityInput.val(itemsInPack);
        $quantityInput.hide();

        // Added fake quantity input after label
        $quantityPar.find('label').after(inputTag(items));

        // Added span with full count in quantity paragraph
        $quantityPar.append(packTag(items));

        // On span click
        $quantityPar.find('span').click(function () {
            if ($(this).parent().hasClass('button-minus')) {
                if (parseInt($quantityInput.val()) > itemsInPack) {
                    items--;
                }

                $quantityInput.val(items * itemsInPack + 1);
            } else if ($(this).parent().hasClass('button-plus')) {
                items++;

                $quantityInput.val(items * itemsInPack - 1);
            }

            $('#count-items-in-pack').html(items * itemsInPack + ' шт.');
            $('[name=pack]').val(items + ' уп.');

            console.log(items);
            console.log(itemsInPack);
        });
    }
});
