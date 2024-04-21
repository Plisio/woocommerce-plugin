<tr valign="top">
    <th scope="row" class="titledesc">Order Statuses:</th>
    <td class="forminp" id="plisio_order_statuses">
        <table cellspacing="0">
            <?php
            foreach ($plisioStatuses as $status => $statusTitle) {
                if (!isset($selectedStatuses[$status])) {
                    $currentStatus = $defaultStatuses[$status];
                } else {
                    $currentStatus = $selectedStatuses[$status];
                }
                ?>
                <tr>
                    <th><?php echo $statusTitle; ?></th>
                    <td>
                        <select name="woocommerce_plisio_order_statuses[<?php echo $status; ?>]">
                            <?php
                            foreach ($wcStatuses as $wcStatus => $wcStatusTitle) {
                                if ($currentStatus == $wcStatus)
                                    echo "<option value=\"$wcStatus\" selected>$wcStatusTitle</option>";
                                else
                                    echo "<option value=\"$wcStatus\">$wcStatusTitle</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
    </td>
</tr>
