<style>
    .invoice__header {
        padding: 8px 0 0;
    }

    .invoice__amount {
        display: flex;
        align-items: center;
        justify-content: flex-end;
    }
    .invoice .invoice__progress {
        position: relative;
        height: 2rem;
        background: #229ac8;
    }

    .invoice__progressBar {
        will-change: width;
        transition: width 1s linear !important;
    }

    .invoice__progressHint {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        text-align: center;
        text-shadow: 0 0 1px #000;
        color: #fff;
    }

    .invoice__content {
        padding: 16px 0 40px;
    }

    .invoice__currencyRow {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        width: 100%;
    }

    .invoice__currencyItem {
        width: 100%;
        max-width: 50%;
        min-width: 200px;
        padding: 0 8px 16px;
    }

    .invoice__input_radioLabel {
        position: relative;
        display: block;
        margin: 0;
        cursor: pointer;
    }
    .invoice__input_radioLabel._disabled {
        opacity: .7;
        cursor: not-allowed;
    }

    .invoice__input_radio {
        position: absolute;
        width: 0;
        height: 0;
        opacity: 0;
        visibility: hidden;
    }

    .invoice__input_radioBtn {
        display: block;
    }

    .invoice__currencyContent {
        display: flex;
        align-items: center;
        padding: .75em .5em .75em 1em;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        transition: all .5s;
    }

    .invoice__currencyContent:hover .invoice__currencyName ,
    .invoice__currencyContent:focus .invoice__currencyName ,
    .invoice__currencyContent._checked .invoice__currencyName {
        color: #229ac8;
    }

    .invoice__currencyIcon {
        max-width: 100%;
        width: 32px !important;
        height: 32px !important;
    }

    .invoice__currencyHint {
        margin-left: .5em;
        text-align: left;
    }

    .invoice__currencyName {
        font-size: 1.125em;
        font-weight: 700;
        transition: color .5s;
    }

    .invoice__currencyAmount {
        display: block;
        margin-top: .25em;
        font-size: 1em;
        font-weight: 400;
    }

    .invoice__qr {
        margin: 2rem auto;
    }

    .invoice__btn_copy {
        cursor: pointer;
    }

    .invoice .input-group {
        width: 100%;
    }

    .invoice .input-group-addon {
        width: 55px;
    }

    .invoice__icon_btn {
        width: 1rem;
        height: 1rem;
        fill: #fff;
    }

    .invoice .form-control {
        width: 100%;
        text-align: center;
    }

    .invoice a {
        display: inline-flex;
        align-items: center;
    }

    .invoice a > .invoice__icon_btn {
        width: 1em;
        height: 1em;
        margin-right: .5em;
        fill: #23a1d1; /*bootstrap primary-color*/
    }

    .invoice__icon_status {
        display: block;
        margin: 0 auto 2rem;
        width: 80px;
        height: 80px;
        fill: #23a1d1; /*bootstrap primary-color*/
    }

    @keyframes rotate {
        100% {
            transform: rotate(360deg);
        }
    }

    @-webkit-keyframes rotate {
        100% {
            transform: rotate(360deg);
        }
    }

    .invoice__icon_status._loader {
        width: 80px;
        height: 80px;
        animation: rotate infinite 1s linear;
        transform-origin: 50% 50%;
    }
</style>

<svg aria-hidden="true" style="position:absolute;width:0;height:0;overflow:hidden;" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <symbol viewBox="0 0 24 24" id="icon_invoice_copy" xmlns="http://www.w3.org/2000/svg">
            <path
                    d="M6.439 5.927c.408 0 .738.33.738.739v5.078c0 .409.33.738.739.738h5.05c.41 0 .74.33.74.739v9.669c0 .613-.496 1.108-1.109 1.108H1.114l-.12-.007c-.396-.044-.73-.305-.889-.732-.064-.172-.098-.37-.098-.59V6.813c0-.392.494-.887 1.107-.887zM16.696 0c.409 0 .738.33.738.738v4.98c0 .409.33.739.739.739h5.078c.409 0 .738.33.738.738h-.002v9.767c0 .613-.495 1.108-1.108 1.108h-7.707v-7.463c0-.478-.187-.945-.539-1.297L10.323 5c-.01-.013-.022-.023-.032-.033v-3.86c0-.612.495-1.107 1.108-1.107zM9.03 5.927c.09 0 .182.032.258.109l1.002 1.002 3.306 3.308c.076.076.108.17.108.26-.002.19-.148.37-.369.37h-4.31c-.202 0-.37-.165-.367-.37v-4.31c0-.22.182-.369.372-.369zM18.94.372c0-.33.399-.493.63-.261l4.31 4.31c.234.231.07.63-.26.63h-4.31c-.205 0-.37-.165-.37-.37z"/>
        </symbol>
        <symbol viewBox="0 0 20 18" id="icon_invoice_link_external" xmlns="http://www.w3.org/2000/svg">
            <path
                    d="M878.614 566.606c.067.07.1.16.1.27v3.75c0 .93-.314 1.725-.943 2.385-.629.66-1.386.99-2.271.99h-9.286c-.885 0-1.642-.33-2.271-.99-.629-.66-.943-1.455-.943-2.385v-9.752c0-.93.314-1.724.943-2.385.629-.66 1.386-.99 2.271-.99h7.857a.34.34 0 0 1 .257.105c.067.07.1.16.1.27v.75c0 .11-.033.2-.1.27a.34.34 0 0 1-.257.105h-7.857a1.68 1.68 0 0 0-1.261.551c-.35.367-.524.808-.524 1.324v9.752c0 .515.174.957.524 1.324.35.367.77.55 1.261.55h9.286a1.68 1.68 0 0 0 1.261-.55c.35-.367.525-.809.525-1.324v-3.75c0-.11.033-.2.1-.27a.338.338 0 0 1 .257-.106h.714c.104 0 .19.035.257.106zm4.386-9.857v6c0 .204-.07.38-.212.528a.67.67 0 0 1-.502.223.67.67 0 0 1-.503-.223l-1.964-2.063-7.277 7.642a.345.345 0 0 1-.256.117.345.345 0 0 1-.257-.117l-1.272-1.336a.38.38 0 0 1 0-.54l7.276-7.64-1.964-2.064a.74.74 0 0 1-.212-.527c0-.203.07-.38.212-.527a.67.67 0 0 1 .502-.223h5.714c.194 0 .361.074.503.223a.74.74 0 0 1 .212.527z"
                    transform="translate(-863 -556)"/>
        </symbol>
        <symbol viewBox="0 0 15 15" id="icon_invoice_loader" xmlns="http://www.w3.org/2000/svg">
            <path
                    d="M795 172.75h-.004c.003.027.004.055.004.083 0 .552-.433 1-.968 1-.019 0-.038 0-.057-.002a7.138 7.138 0 0 1-1.841 3.956 6.941 6.941 0 0 1-2.213 1.603 6.831 6.831 0 0 1-5.37.115 7.094 7.094 0 0 1-2.338-1.542 7.424 7.424 0 0 1-1.604-2.367 7.665 7.665 0 0 1-.607-2.846c-.021-.98.145-1.972.495-2.894a7.642 7.642 0 0 1 1.545-2.496 7.424 7.424 0 0 1 2.37-1.712 7.301 7.301 0 0 1 5.741-.115 7.579 7.579 0 0 1 2.494 1.65 7.924 7.924 0 0 1 1.71 2.53 8.18 8.18 0 0 1 .643 3.037zm-1.072 1.077a.988.988 0 0 1-.86-1.077h-.003a6.195 6.195 0 0 0-.385-2.32 6.134 6.134 0 0 0-1.23-2.01 5.973 5.973 0 0 0-1.9-1.387 5.89 5.89 0 0 0-4.63-.115 6.128 6.128 0 0 0-2.023 1.325 6.42 6.42 0 0 0-1.395 2.044 6.71 6.71 0 0 0-.112 4.974c.3.801.756 1.544 1.336 2.172a6.458 6.458 0 0 0 2.056 1.495 6.36 6.36 0 0 0 5 .116 6.612 6.612 0 0 0 2.181-1.434 6.922 6.922 0 0 0 1.5-2.205 7.067 7.067 0 0 0 .465-1.578z"
                    transform="translate(-780 -165)"/>
        </symbol>
        <symbol viewBox="0 0 96 96" id="icon_invoice_expired" xmlns="http://www.w3.org/2000/svg">
            <path
                    d="M52.617 95.73c19.368-2.156 34.89-17.55 37.099-36.808 2.9-25.18-16.784-46.614-41.458-46.889V.737c0-.627-.789-.96-1.321-.568l-23.392 17.08c-.394.295-.394.863 0 1.158l23.392 17.08c.532.393 1.321.04 1.321-.568V23.643c17.337.274 31.222 14.943 30.038 32.475-1.006 15.08-13.372 27.317-28.539 28.278-16.074 1.02-29.742-10.394-32.188-25.494-.454-2.804-2.919-4.843-5.759-4.843-3.53 0-6.292 3.118-5.74 6.589C9.503 82.16 29.483 98.3 52.618 95.73z"/>
        </symbol>
        <symbol viewBox="0 0 70 100" id="icon_invoice_overpaid" xmlns="http://www.w3.org/2000/svg">
            <g transform="translate(-165 -116)">
                <path d="M206.55 182.32h3.158v3.158h-3.158z"/>
                <path d="M226.55 182.32h3.158v3.158h-3.158z"/>
                <path
                        d="M205.148 184.272c.028-7.361 5.975-13.305 13.284-13.276h.109c3.22.02 6.32 1.225 8.719 3.388 4.108 3.697 5.514 9.57 3.527 14.745-1.986 5.175-6.949 8.569-12.458 8.521-7.308-.028-13.21-6.019-13.181-13.378zm10.21 27.232l-3.497-4.833a1.647 1.647 0 0 0-2.667-.002l-3.51 4.833-3.497-4.833a1.714 1.714 0 0 0-1.337-.644c-.519 0-1.01.237-1.336.644l-3.495 4.823-3.496-4.816a1.709 1.709 0 0 0-2.67 0l-3.498 4.833-3.509-4.833a1.647 1.647 0 0 0-1.334-.683 1.65 1.65 0 0 0-1.333.683l-3.497 4.816-3.495-4.816a1.71 1.71 0 0 0-2.671 0l-1.708 2.357v-84.34l3.044-4.193 3.494 4.816a1.65 1.65 0 0 0 2.67 0l3.5-4.833 3.508 4.833c.31.43.807.683 1.334.683a1.65 1.65 0 0 0 1.334-.683l3.497-4.816 3.495 4.816a1.71 1.71 0 0 0 2.672 0l3.493-4.816 3.493 4.816c.312.43.808.683 1.335.683a1.65 1.65 0 0 0 1.335-.682l3.51-4.832 3.497 4.832a1.65 1.65 0 0 0 1.333.684c.528 0 1.024-.253 1.334-.682l1.746-2.395v44.738c-9.136-.036-16.57 7.396-16.607 16.6-.035 9.204 7.341 16.694 16.476 16.731h.13v6.288zm6.382-43.498v-50.173c0-.72-.46-1.36-1.14-1.585a1.649 1.649 0 0 0-1.85.6l-3.391 4.667-3.498-4.833a1.648 1.648 0 0 0-2.667 0l-3.51 4.833-3.497-4.833a1.712 1.712 0 0 0-1.337-.645c-.519 0-1.01.237-1.336.645l-3.495 4.817-3.496-4.815a1.709 1.709 0 0 0-2.67 0l-3.498 4.832-3.509-4.832a1.647 1.647 0 0 0-1.334-.684 1.65 1.65 0 0 0-1.333.684l-3.497 4.815-3.495-4.815a1.71 1.71 0 0 0-2.671 0l-4.699 6.482c-.206.285-.317.63-.317.984v89.992c0 .722.46 1.362 1.141 1.585a1.647 1.647 0 0 0 1.85-.602l3.36-4.632 3.495 4.823a1.65 1.65 0 0 0 2.671 0l3.498-4.834 3.509 4.834a1.65 1.65 0 0 0 2.668-.008l3.497-4.815 3.495 4.823a1.71 1.71 0 0 0 2.672 0l3.493-4.823 3.493 4.823c.312.428.808.682 1.335.682.527 0 1.023-.253 1.335-.68l3.51-4.834 3.497 4.833c.31.428.806.682 1.333.683a1.65 1.65 0 0 0 1.334-.681l4.732-6.5c.21-.287.323-.636.322-.993v-7.176c7.667-1.549 13.197-8.316 13.233-16.194.067-7.968-5.482-14.866-13.233-16.45z"/>
                <path d="M185.5 130.74h16.842v3.158H185.5z"/>
                <path d="M174.97 141.26h5.263v3.158h-5.263z"/>
                <path d="M183.39 141.26h28.421v3.158H183.39z"/>
                <path d="M174.97 150.74h5.263v3.158h-5.263z"/>
                <path d="M174.97 161.26h5.263v3.158h-5.263z"/>
                <path d="M183.39 150.74h28.421v3.158H183.39z"/>
                <path d="M183.39 161.26h28.421v3.158H183.39z"/>
                <path d="M174.97 170.74h5.263v3.158h-5.263z"/>
                <path d="M183.39 170.74h16.842v3.158H183.39z"/>
                <path
                        d="M216.952 182.223c-.945 0-1.71-.746-1.71-1.666 0-.92.765-1.667 1.71-1.667zm5.131 5c0 .92-.766 1.667-1.71 1.667v-3.333c.944 0 1.71.746 1.71 1.666zm0-6.666h3.421c0-2.762-2.297-5-5.131-5v-1.667h-3.421v1.667c-2.834 0-5.132 2.238-5.132 5 0 2.76 2.298 5 5.132 5v3.333c-.945 0-1.711-.746-1.711-1.667h-3.421c0 2.762 2.298 5 5.132 5v1.667h3.42v-1.667c2.835 0 5.132-2.238 5.132-5 0-2.76-2.297-5-5.131-5v-3.333c.944 0 1.71.746 1.71 1.667z"/>
            </g>
        </symbol>
        <symbol viewBox="0 0 16 16" id="icon_invoice_exclamation" xmlns="http://www.w3.org/2000/svg">
            <path
                    d="M15.86 13.116L8.84 1.474C8.661 1.18 8.343 1 8 1s-.662.18-.84.474L.14 13.116c-.182.302-.187.68-.013.988.174.308.5.498.853.498h14.04c.353 0 .68-.19.853-.498.174-.308.169-.686-.014-.988zm-7.855-8.09c.403 0 .744.228.744.631 0 1.23-.144 2.998-.144 4.228 0 .32-.352.455-.6.455-.33 0-.61-.134-.61-.455 0-1.23-.144-2.997-.144-4.228 0-.403.33-.63.754-.63zm.01 7.62c-.454 0-.795-.373-.795-.797 0-.434.34-.795.796-.795.423 0 .785.361.785.795 0 .424-.362.796-.785.796z"/>
        </symbol>
        <symbol viewBox="0 0 96 96" id="icon_invoice_check" xmlns="http://www.w3.org/2000/svg">
            <path
                    d="M1.107 51.6C.37 50.88 0 49.8 0 49.08s.37-1.8 1.107-2.52l5.17-5.04c1.477-1.44 3.692-1.44 5.17 0l.368.36 20.308 21.24c.739.72 1.846.72 2.584 0l49.478-50.04h.369c1.477-1.44 3.692-1.44 5.17 0l5.169 5.04c1.476 1.44 1.476 3.6 0 5.04L35.815 82.92c-.738.72-1.477 1.08-2.584 1.08-1.108 0-1.846-.36-2.585-1.08l-28.8-30.24-.739-1.08z"/>
        </symbol>
    </defs>
</svg>

<?php $invoice_is_currency_awaiting = ((count($allowed_currencies) > 1) and (in_array($order['status'], ['new']))); ?>
<?php $invoice_is_min_sum_in_error = ((count($allowed_currencies) == 1) and ($order['amount'] < reset($allowed_currencies)['min_sum_in'])); ?>
<?php $invoice_is_processing = (($invoice_is_min_sum_in_error != true) and (in_array($order['status'], ['new', 'pending']))); ?>
<?php $invoice_is_payment_waiting = ((in_array($order['status'], ['new'])) or ((in_array($order['status'], ['pending'])) and ($order['pending_amount'] > 0))); ?>
<?php $invoice_is_waiting_for_confirmations = ((in_array($order['status'], ['pending'])) and ($order['pending_amount'] <= 0)); ?>
<?php $invoice_is_overpaid = in_array($order['status'], ['mismatch']); ?>
<?php $invoice_is_finished = in_array($order['status'], ['finish', 'completed']); ?>
<?php $invoice_is_expired = in_array($order['status'], ['expired', 'cancelled']); ?>
<?php $invoice_is_underpaid = (($invoice_is_expired) and ($order['pending_amount'] < $order['amount'])); ?>
<?php $invoice_is_error = in_array($order['status'], ['error']); ?>

<div class="invoice__wrapper container">
    <div class="row">
        <div
                class="invoice col-md-offset-3 col-md-6 col-sm-12"
                data-invoice-id="<?php echo htmlspecialchars($order['plisio_invoice_id']); ?>"
                data-invoice-amount="<?php echo $order['amount']; ?>"
                data-invoice-currency="<?php echo htmlspecialchars($order['currency']); ?>"
                data-order-id="<?php echo $order_id; ?>"
        >
            <div class="invoice__header">
				<?php if ($invoice_is_processing): ?>
                    <div class="invoice__progress progress">
                        <div
                                class="invoice__progressBar progress-bar"
                                role="progressbar"
                                aria-valuenow="0"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                style="width: 0"
                                data-expire-utc="<?php echo $expire_utc; ?>"
                        >
                            <span class="sr-only">0% <?php echo $text_progress_hint ?>></span>
                        </div>
                        <span class="invoice__progressHint"></span>
                    </div>
				<?php endif; ?>

                <div class="row">
                    <div class="col-xs-4">
                        <h4><small><?php echo $text_info_order; ?></small><?php echo $order_id; ?></h4>
                    </div>
                    <div class="col-xs-8 text-right">
                        <div class="invoice__amount">
                            <div class="invoice__amountSum">
                                <strong class="invoice__amountSum_crypto"><?php echo number_format($order['amount'], 8, '.', ' '); ?> <?php echo htmlspecialchars($order['currency']); ?></strong> <br>
								<?php echo number_format($order['amount'] / $order['source_rate'], 2); ?> <?php echo htmlspecialchars($order['source_currency']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="invoice__content">
				<?php if ($invoice_is_currency_awaiting) { ?>
                    <div class="text-center">
                        <h3><?php echo $text_step_currency_title; ?></h3>
                        <p><?php echo $text_step_currency_hint; ?></p>

                        <div class="invoice__currencyRow">
							<?php foreach ($allowed_currencies as $allowed_curr) { ?>
								<?php if ($allowed_curr['cid'] == $order['currency']) { ?>
									<?php $currencyAmount = $order['amount']; ?>
								<?php  } else if ($commission_payment == 1) { ?>
									<?php $currencyAmount = $order['amount'] / $order['source_rate'] * $allowed_curr['fiat_rate']; ?>
								<?php  } else if ($commission_payment == 2) { ?>
									<?php  $currencyAmount = ($checkout_total_fiat + ($checkout_total_fiat * ($allowed_curr['invoice_commission_percentage'] + $extra_commission) / 100)) * $allowed_curr['fiat_rate']; ?>
								<?php  } ?>
                                <div class="invoice__currencyItem">
                                    <label class="invoice__input_radioLabel
                    <?php if ($currencyAmount < $allowed_curr['min_sum_in']) { ?>
                  <?php echo ' _disabled data-toggle="tooltip" title="' . $text_step_error_hint_1 . sprintf($text_step_error_hint_2, $allowed_curr['min_sum_in'] . $allowed_curr['currency']) . '"' . 'data-trigger="hover""'; ?>
                  <?php  } else { echo ''; } ?>
                    >
                    <input
                            type="radio"
                                    name="currency"
                                    class="invoice__input_radio"
									<?php echo 'value="' . $allowed_curr['cid'] . '"'; ?>"
                                    onclick="window.invoiceChooseCurrency('<?php echo ( $allowed_curr['cid'] . '\'' . ',' . $currencyAmount); ?>)"
									<?php if ($currencyAmount < $allowed_curr['min_sum_in']) { echo ' disabled'; } else { echo ''; } ?>
                                    >
                                    <span class="invoice__input_radioBtn">
                      <span <?php if ($order['currency'] == $allowed_curr['cid']) { echo 'class="invoice__currencyContent _checked"'; } else { echo 'class="invoice__currencyContent"'; } ?> >
                        <img
                                class="invoice__currencyIcon"
                          <?php echo 'src="https://plisio.net/img/psys-icon/' . $allowed_curr['currency'] . '.svg"'; ?>
	                        <?php echo 'alt="' . $allowed_curr['currency'] . '"'; ?>
                          width="32"
                                height="32"
                        >
                        <span class="invoice__currencyHint">
                          <span class="invoice__currencyName"><?php echo $allowed_curr['currency']; ?></span>
                          <span class="invoice__currencyAmount">
                            <?php echo number_format($currencyAmount, 8, '.', ' '); ?>
                          </span>
                        </span>
                      </span>
                    </span>
                                    </label>
                                </div>
							<?php } ?>
                        </div>
                    </div>

				<?php } else if ($invoice_is_min_sum_in_error) { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_exclamation"></use>
                        </svg>
                        <h3><?php echo $text_step_error_title; ?></h3>
                        <p><?php echo $text_step_error_hint_1; ?>
                            <br>
							<?php  echo sprintf($text_step_error_hint_2, $allowed_currencies[$order['currency']]['min_sum_in'] . $order['currency']); ?>
                        </p>
                    </div>

				<?php } else if ($invoice_is_payment_waiting) { ?>
                    <div class="text-center">
                        <img
                                class="invoice__qr"
                                src="<?php echo htmlspecialchars($order['qr_code']); ?>"
                                alt="invoice qr code"
                                width="160"
                                height="160"
                        >
                    </div>
                    <p class="invoice__hint text-center text-large"><?php echo $text_step_pay_hint; ?></p>
                    <div class="form-group">
                        <div class="input-group">
            <span class="input-group-addon">
              <img
                      class="invoice__psysImg"
                      src="<?php echo 'https://plisio.net/img/psys-icon/' . htmlspecialchars($order['currency']) . '.svg'; ?>"
                      alt="<?php echo htmlspecialchars($order['currency']); ?>"
                      width="16"
                      height="16"
              >
            </span>
                            <input
                                    type="text"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($order['wallet_hash']); ?>"
                                    readonly
                                    onclick="copyInvoiceValue(this)" data-toggle="tooltip" title="<?php echo $text_hash_copied; ?>" data-trigger="click"
                            >
                            <span class="input-group-addon btn btn-primary" onclick="copyInvoiceValue(this)" data-toggle="tooltip"
                                  title="<?php echo $text_hash_copied; ?>" data-trigger="click">
              <svg class="invoice__icon_btn invoice__icon_btn_copy" xmlns="http://www.w3.org/2000/svg">
                <use xlink:href="#icon_invoice_copy"></use>
              </svg>
            </span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon"><?php echo htmlspecialchars($order['currency']); ?></span>
                            <input
                                    type="text"
                                    class="form-control invoice__pendingAmount"
                                    value="<?php echo number_format($order['pending_amount'], 8, '.', ''); ?>"
                                    readonly
                                    onclick="copyInvoiceValue(this)" data-toggle="tooltip" title="<?php echo $text_amount_copied; ?>" data-trigger="click"
                            >
                            <span class="input-group-addon btn btn-primary" onclick="copyInvoiceValue(this)" data-toggle="tooltip"
                                  title="<?php echo $text_amount_copied; ?>" data-trigger="click">
              <svg class="invoice__icon_btn invoice__icon_btn_copy" xmlns="http://www.w3.org/2000/svg">
                <use xlink:href="#icon_invoice_copy"></use>
              </svg>
            </span>
                        </div>
                    </div>
				<?php } else if ($invoice_is_waiting_for_confirmations) { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status _loader" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_loader"></use>
                        </svg>
						<?php $stringConfirm = (float)$expected_confirmations > 1 ? 'confirmations' : 'confirmation' ?>
                        <h3>Waiting for <?php echo (float)$expected_confirmations - (float)$confirmations; ?>
                            of <?php echo (float)$expected_confirmations; ?> <?php echo $stringConfirm; ?></h3>
                        <p><?php echo $text_step_pending_hint; ?></p>
                        <a href="<?php echo htmlspecialchars($order['txUrl']); ?>" title="<?php echo $text_link_external; ?>" target="_blank" rel="noopener">
                            <svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg">
                                <use xlink:href="#icon_invoice_link_external"></use>
                            </svg>
							<?php echo $text_link_external; ?>
                        </a>
                    </div>
				<?php } else if ($invoice_is_finished) { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_check"></use>
                        </svg>
                        <h3><?php echo $text_step_finished_title; ?></h3>
                        <a href="<?php echo htmlspecialchars($order['txUrl']); ?>" title="<?php echo $text_link_external; ?>" target="_blank" rel="noopener">
                            <svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg">
                                <use xlink:href="#icon_invoice_link_external"></use>
                            </svg>
							<?php echo $text_link_external; ?>
                        </a>
                    </div>
				<?php } else if ($invoice_is_overpaid) { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_overpaid"></use>
                        </svg>
                        <h3><?php echo $text_step_overpaid_title; ?></h3>
                        <p><?php echo sprintf($text_step_overpaid_hint, abs($order['amount'] + $order['pending_amount']) . $order['currency']) ?></p>
                        <a href="<?php echo htmlspecialchars($order['txUrl']); ?>" title="<?php echo $text_link_external; ?>" target="_blank" rel="noopener">
                            <svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg">
                                <use xlink:href="#icon_invoice_link_external"></use>
                            </svg>
							<?php echo $text_link_external; ?>
                        </a>
                    </div>

				<?php } else if ($invoice_is_underpaid) { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_expired"></use>
                        </svg>
                        <h3><?php echo $text_step_underpaid_title; ?></h3>
                        <p><?php echo sprintf($text_step_underpaid_hint, ($order['amount'] - $order['pending_amount']) . $order['currency'], $order['amount'] . $order['currency']); ?></p>
                    </div>

				<?php } else if ($invoice_is_expired) { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_expired"></use>
                        </svg>
                        <h3><?php echo $text_step_expired_title; ?></h3>
                        <p><?php echo $text_step_expired_hint1; ?>
                            <a href="/" title="<?php echo $text_link_go_back; ?>"><?php echo $text_link_go_back; ?></a>
							<?php echo $text_step_expired_hint_2; ?>
                        </p>
                    </div>

				<?php } else { ?>
                    <div class="invoice__result text-center">
                        <svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">
                            <use xlink:href="#icon_invoice_exclamation"></use>
                        </svg>
                        <h3><?php echo $text_step_error_title; ?></h3>
                        <p><?php echo $text_step_error_hint_default; ?></p>
                    </div>
				<?php } ?>
            </div>
        </div>
    </div>
</div>

<?php if ($invoice_is_processing): ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script async defer>
        let plisio_ajax = {
            'ajaxurl': '<?php echo admin_url( 'admin-ajax.php' ) ?>',
            'order_id': <?php echo isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : null ?>
        }

        function Timer(options) {
            if (Object.keys(options).length > 0) {
                if (options.elSelector) {
                    this.el = document.querySelector(options.elSelector);
                } else {
                    throw new ReferenceError("Invalid element selector passed");
                }
                if (options.countDownTimestamp) {
                    this.countDownTimestamp = options.countDownTimestamp;
                } else {
                    throw new ReferenceError("Invalid timestamp");
                }
                if (options.callback) {
                    this.callback = options.callback;
                }
                this._dateToRender = Object.create(null);
            } else {
                throw new ReferenceError("Invalid input data");
            }

        }

        Timer.prototype.calc = function () {
            this._now = new Date().getTime();
            this._distance = this.countDownTimestamp - this._now;
            // this._dateToRender.days = Math.floor(this._distance / (1000 * 60 * 60 * 24));
            this._dateToRender.hours = Math.floor((this._distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            this._dateToRender.minutes = Math.floor((this._distance % (1000 * 60 * 60)) / (1000 * 60));
            this._dateToRender.seconds = Math.floor((this._distance % (1000 * 60)) / 1000);
            this._progress = 100 - (this._distance / this._distanceAll) * 100;
            this._progress = Math.round(this._progress * 100) / 100;
        };

        Timer.prototype.render = function () {
            this.el.style.width = this._progress + '%';
            this.el.setAttribute('aria-valuenow', this._progress);
            this.el.querySelector('.sr-only').textContent = this._progress + '% Complete';
            var stringToRender = '';
            if (this._distance <= 0) {
                var self = this;
                setTimeout(function () {
                    stringToRender += 'This order has been expired.';
                    self.el.nextElementSibling.textContent = stringToRender;
                }, 1000);
            } else {
                for (var key in this._dateToRender) {
                    this._dateToRender[key] = this._dateToRender[key] >= 10 ? this._dateToRender[key] : '0' + this._dateToRender[key]
                }
                stringToRender = Object.values(this._dateToRender).join(':');
                this.el.nextElementSibling.textContent = stringToRender;
            }
        };

        Timer.prototype.fin = function () {
            clearTimeout(this._timerId);
            if (this.callback) {
                if (typeof this.callback === 'function') {
                    this.callback();
                } else {
                    throw new ReferenceError("Callback-param must be function.");
                }
            }
            delete this;
        };

        Timer.prototype.run = function () {
            this.calc();
            this._distanceAll = this._distance;
            this.render();
            if (this._distance <= 0) {
                this.fin();
            }
            var self = this;
            this._timerId = setTimeout(function runTimer() {
                self.calc();
                self.render();
                if (self._distance < 0) {
                    self.fin();
                } else {
                    self._timerId = setTimeout(runTimer, 1000);
                }
            }, 1000);
        };


        document.addEventListener('DOMContentLoaded', function () {
            var elInvoice = document.querySelector('.invoice');
            var elProgressBar = elInvoice.querySelector('.invoice__progressBar');
            var expireUTC = new Date(parseInt(elProgressBar.dataset.expireUtc));
            var countDownTimestamp = expireUTC.getTime();

            (function () {
                try {
                    function InvoiceTimer(el, options) {
                        Timer.apply(this, arguments);
                    }
                    InvoiceTimer.prototype = Object.create(Timer.prototype);
                    InvoiceTimer.prototype.constructor = InvoiceTimer;
                    window.invoiceTimer = new InvoiceTimer(
                        {
                            elSelector: '.invoice__progressBar',
                            countDownTimestamp: countDownTimestamp,
                            callback: function () {
                                // console.info('Callback-function called after invoice timer finishes.')
                            },
                        }
                    );
                    window.invoiceTimer.run();
                } catch (error) {
                    console.error('Failed to create invoice timer: ', error);
                }
            })();


            function getTxUrl (tx_urls) {
                var txUrl = '';
                if (tx_urls) {
                    try {
                        txUrl = JSON.parse(tx_urls);
                        if (txUrl) {
                            txUrl = typeof txUrl === 'string' ? txUrl : txUrl[txUrl.length-1];
                        }
                    } catch (error) {
                        console.error('Failed to parse tx_urls: ', error);
                    }
                }
                return txUrl;
            }

            function finInvoiceChecking () {
                clearTimeout(window.checkInvoiceInterval);
                window.invoiceTimer.fin();
            }

            if (!window.checkInvoiceInterval){
                window.checkInvoiceInterval = setInterval(function () {
                    $.ajax({
                        type: 'POST',
                        url: plisio_ajax.ajaxurl,
                        dataType: 'json',
                        data: {
                            action: 'plisio_update_invoice',
                            order_id: plisio_ajax.order_id,
                        },
                        error: function (error) {
                            console.error('check invoice interval error: ', error);
                            finInvoiceChecking();
                        },
                        success: function (response) {
                            try {
                                response = response.data;
                                if (!['new', 'pending'].includes(response.status)) {
                                    finInvoiceChecking();
                                }
                                if (['new'].includes(response.status)
                                    || ['pending'].includes(response.status) && response.pending_amount > 0)
                                {
                                    if (response.pending_amount < response.amount) {
                                        elInvoice.querySelector('.invoice__pendingAmount').value = response.pending_amount;
                                        elInvoice.querySelector('.invoice__qr').setAttribute('src', response.qr_code);
                                    }
                                    return;
                                }
                                var txUrl = getTxUrl(response.tx_urls);
                                if (['pending'].includes(response.status)) {
                                    window.renderInvoiceStepPending(elInvoice, response, txUrl);
                                } else if (['finish', 'completed'].includes(response.status)) {
                                    window.renderInvoiceStepFinished(elInvoice, txUrl);
                                } else if (['mismatch'].includes(response.status)) {
                                    window.renderInvoiceStepOverpaid(elInvoice, response, txUrl);
                                } else if (['expired', 'cancelled'].includes(response.status)) {
                                    if (response.pending_amount < response.amount) {
                                        window.renderInvoiceStepUnderpaid(elInvoice, response);
                                    } else {
                                        window.renderInvoiceStepExpired(elInvoice);
                                    }
                                } else if (['error'].includes(response.status)) {
                                    console.error('error');
                                    window.renderInvoiceStepError(elInvoice);
                                }
                            } catch (error) {
                                console.error('Failed to parse server response ', error);
                                window.renderInvoiceStepError(elInvoice);
                            }
                        }
                    });
                }, 15*1000);
            }
        });

        function invoiceChooseCurrency (currency, newAmount) {
            var elInvoice = document.querySelector('.invoice');
            elInvoice.querySelector('.invoice__content').innerHTML = '<div class="invoice__result text-center">' +
                '<svg class="invoice__icon_status _loader" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_loader"></use></svg>' +
                '</div>';
            elInvoice.querySelector('.invoice__amountSum_crypto').textContent = Number(newAmount).toFixed(8) + ' ' + currency;
            jQuery.ajax({
                type: 'POST',
                url: plisio_ajax.ajaxurl,
                dataType: 'json',
                data: {
                    action: 'choose_currency',
                    invoice: elInvoice.dataset.invoiceId,
                    order_id: elInvoice.dataset.orderId,
                    currency: currency,
                    return_url: '<?php echo $return_url ?>'
                },
                error: function (error) {
                    console.error('change invoice currency failed: ', error);
                    window.renderInvoiceStepError(elInvoice);
                },
                success: function (response) {
                    if (response.data) {
                        location.href = response.data;
                    }   else {
                        console.log('Error occurred.', response);
                        window.renderInvoiceStepError(elInvoice);
                    }
                }
            });
        }

        function renderInvoiceStepPending (elInvoice, response, txUrl) {
            var confirmString = response.expected_confirmations > 1 ? 'confirmations' : 'confirmation';
            var title = '<?php echo $text_step_pending_title; ?>'
                .replace('%s', (Number(response.expected_confirmations)-Number(response.confirmations)))
                .replace('%s', response.expected_confirmations)
                .replace('%s', confirmString);
            var str = '';
            str += '<div class="invoice__result text-center">' +
                '<svg class="invoice__icon_status _loader" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_loader"></use></svg>';
            if (Number(response.expected_confirmations)-Number(response.confirmations) > 0) {
                str += '<h3>' + title + '</h3>';
            }
            str += '<p><?php echo $text_step_pending_hint; ?></p>' +
                '<a href="'+ txUrl + '" title="<?php echo $text_link_external; ?>" target="_blank" rel="noopener">' +
                '<svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_link_external"></use></svg>' +
                '<?php echo $text_link_external; ?></a>' +
                '</div>';
            elInvoice.querySelector('.invoice__content').innerHTML = str;
        }

        function renderInvoiceStepFinished (elInvoice, txUrl) {
            elInvoice.querySelector('.invoice__content').innerHTML = '<div class="invoice__result text-center">' +
                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_check"></use></svg>' +
                '<h3><?php echo $text_step_finished_title; ?></h3>' +
                '<a href="'+ txUrl + '" title="<?php echo $text_link_external; ?>" target="_blank" rel="noopener">' +
                '<svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_link_external"></use></svg>' +
                '<?php echo $text_link_external; ?></a>' +
                '</div>';
        }

        function renderInvoiceStepOverpaid (elInvoice, response, txUrl) {
            var hint = '<?php echo $text_step_overpaid_hint; ?>'
                .replace('%s', (Math.abs(response.pending_amount) + Number(response.amount)).toFixed(8) + ' ' + response.currency + ', ');
            elInvoice.querySelector('.invoice__content').innerHTML = '<div class="invoice__result text-center">' +
                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_overpaid"></use></svg>' +
                '<h3><?php echo $text_step_overpaid_title; ?></h3>' +
                '<p>' + hint + '</p>' +
                '<a href="'+ txUrl + '" title="<?php echo $text_link_external; ?>" target="_blank" rel="noopener">' +
                '<svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_link_external"></use></svg>' +
                '<?php echo $text_link_external; ?></a>' +
                '</div>';
        }

        function renderInvoiceStepUnderpaid (elInvoice, response) {
            var hint = '<?php echo $text_step_underpaid_hint; ?>'
                .replace('%s', (response.amount - response.pending_amount).toFixed(8) + ' ' + response.currency)
                .replace('%s', elInvoice.dataset.invoiceAmount + ' ' + elInvoice.dataset.invoiceCurrency);
            elInvoice.querySelector('.invoice__content').innerHTML = '<div class="invoice__result text-center">' +
                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_expired"></use></svg>' +
                '<h3><?php echo $text_step_underpaid_title; ?></h3>' +
                '<p>' + hint + '</p>' +
                '</div>';
        }

        function renderInvoiceStepExpired (elInvoice) {
            elInvoice.querySelector('.invoice__content').innerHTML = '<div class="invoice__result text-center">' +
                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">' +
                '<use xlink:href="#icon_invoice_expired"></use></svg>' +
                '<h3><?php echo $text_step_expired_title; ?></h3>' +
                '<p><?php echo $text_step_expired_hint_1; ?> <a href="/" title="<?php echo $text_link_go_back; ?>"><?php echo $text_link_go_back; ?></a> <?php echo $text_step_expired_hint_2; ?></p>' +
                '</div>';
        }

        function renderInvoiceStepError (elInvoice) {
            elInvoice.querySelector('.invoice__content').innerHTML = '<div class="invoice__result text-center">'+
                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg">' +
                '<use xlink:href="#icon_invoice_exclamation"></use>' +
                '</svg>'+
                '<h3><?php echo $text_step_error_title; ?></h3>'+
                '<p><?php echo $text_step_error_hint_default; ?></p>'+
                '</div>';
        }

        function copyInvoiceValue (el) {
            var textCopyTo = el.parentElement.querySelector('input');
            try {
                textCopyTo.select();
                textCopyTo.focus();
                document.execCommand('copy');
                setTimeout(function () {
                    $(el).tooltip('hide');
                }, 500)
            } catch(err) {
                console.log(err)
            }
        }
    </script>
<?php endif; ?>
