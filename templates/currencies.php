<style>
    .plisio-list-currencies table td {
        padding: 5px;
    }
</style>
<tr valign="top" class="plisio-list-currencies">
    <th scope="row" class="titledesc">Cryptocurrency:</th>
    <td>
        <!-- any -->
        <table cellspacing="0">
            <tr>
                <td>
                    <?php if (empty($plisio_receive_currencies) || count($plisio_receive_currencies) === count($supported_currencies)): ?>
                        <input type="checkbox" value="" id="entry_currency_0" checked="checked"/>
                    <?php else: ?>
                        <input type="checkbox" value="" id="entry_currency_0"/>
                    <?php endif; ?>
                    <label for="entry_currency_0">Any</label></td>
            </tr>
        </table>
        <hr>
        <!-- choose some -->
        <table class="wc_input_table sortable" cellspacing="0" style="max-width: 400px;">
            <tbody class="ui-sortable">
            <?php if (empty($plisio_receive_currencies) || count($plisio_receive_currencies) === count($supported_currencies)): ?>
                <?php foreach ($supported_currencies as $key => $currency): ?>
                    <tr class="ui-sortable-handle">
                        <td>
                            <input type="checkbox" name="woocommerce_plisio_receive_currencies[]"
                                   value="<?= $currency['cid'] ?>"
                                   id="entry_currency_<?= ++$key ?>" checked="checked"
                            />
                            <label for="entry_currency_<?= $key ?>"><?= $currency['name'] ?> (<?= $currency['currency'] ?>
                                )</label>
                        </td>
                        <td class="sort" style="width: 15px;"></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($supported_currencies as $key => $currency): ?>
                    <tr class="ui-sortable-handle">
                        <td>
                            <?php $isChecked = (is_array($plisio_receive_currencies) && in_array($currency['cid'], $plisio_receive_currencies)); ?>
                            <input type="checkbox" name="woocommerce_plisio_receive_currencies[]"
                                   value="<?= $currency['cid'] ?>"
                                   id="entry_currency_<?= ++$key ?>"
                                <?= $isChecked ? 'checked="checked"' : '' ?>
                            />
                            <label for="entry_currency_<?= $key ?>"><?= $currency['name'] ?> (<?= $currency['currency'] ?>
                                )</label>
                        </td>
                        <td class="sort" style="width: 15px;"></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <p class="description">Drag and drop items to set order.</p>
    </td>
</tr>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var checkAny = document.getElementById('entry_currency_0');
        var checkSome = document.querySelectorAll('[name="woocommerce_plisio_receive_currencies[]"]');

        checkAny.addEventListener('click', function (event) {
            for (var i = 0; i < checkSome.length; i++) {
                checkSome[i].checked = event.target.checked;
            }
        });

        checkSome.forEach(function (element) {
            element.addEventListener('click', function () {
                var values = 0;
                for (var i = 0; i < checkSome.length; i++) {
                    if (checkSome[i].checked) {
                        values++;
                    }
                }
                checkAny.checked = (values === checkSome.length);
            });
        });

        document.querySelector('form').addEventListener('submit', function(event) {
            var checked = 0;
            Array.prototype.forEach.call(checkSome, function (element) {
                if (element.checked) checked += 1;
            });
            if (!checked) {
                event.preventDefault();
                alert('You must check at least one cryptocurrency.')
            }
        });
    });
</script>