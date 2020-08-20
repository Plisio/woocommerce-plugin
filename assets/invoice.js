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
        stringToRender = Object.values(this._dateToRender).join(' : ');
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
    if (!elInvoice){
        return false;
    }
    var elProgressBar = elInvoice.querySelector('.invoice__progressBar');
    if (!elProgressBar){
        return false;
    }
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


    function getTxUrl(tx_urls) {
        var txUrl = '';
        if (tx_urls) {
            try {
                txUrl = JSON.parse(tx_urls);
                if (txUrl) {
                    txUrl = typeof txUrl === 'string' ? txUrl : txUrl[txUrl.length - 1];
                }
            } catch (error) {
                console.error('Failed to parse tx_urls: ', error);
            }
        }
        return txUrl;
    }

    function finInvoiceChecking() {
        clearTimeout(window.checkInvoiceInterval);
        window.invoiceTimer.fin();
    }

    if (!window.checkInvoiceInterval) {
        window.checkInvoiceInterval = setInterval(function () {
            jQuery.ajax({
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
                            || ['pending'].includes(response.status) && response.pending_amount > 0) {
                            if (response.pending_amount < response.amount) {
                                elInvoice.querySelector('.invoice__pendingAmount').value = response.pending_amount;
                                elInvoice.querySelector('.invoice__qr').setAttribute('src', response.qr_code);
                            }
                            return;
                        }
                        var resultContent = '';
                        var txUrl = getTxUrl(response.tx_urls);
                        if (['pending'].includes(response.status)) {
                            var confirmString = response.expected_confirmations > 1 ? 'confirmations' : 'confirmation';
                            resultContent += '<div class="invoice__result invoice__row_center_vertical">' +
                                '<svg class="invoice__icon_status _loader" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_loader"></use></svg>' +
                                '<h3>Waiting for ' + (Number(response.expected_confirmations) - Number(response.confirmations)) + ' of ' + response.expected_confirmations + ' ' + confirmString + '</h3>' +
                                '<p>Please, wait until network confirms your payment. It usually takes 15-60 minutes.</p>' +
                                '<a href="' + txUrl + '" title="Check my transaction" target="_blank" rel="noopener">' +
                                '<svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_link_external"></use></svg>' +
                                'Check my transaction</a>' +
                                '</div>';
                        } else if (['finish', 'completed'].includes(response.status)) {
                            resultContent += '<div class="invoice__result invoice__row_center_vertical">' +
                                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_check"></use></svg>' +
                                '<h3>Payment complete</h3>' +
                                '<a href="' + txUrl + '" title="Check my transaction" target="_blank" rel="noopener">' +
                                '<svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_link_external"></use></svg>' +
                                'Check my transaction</a>' +
                                '</div>';
                        } else if (['mismatch'].includes(response.status)) {
                            resultContent += '<div class="invoice__result invoice__row_center_vertical">' +
                                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_overpaid"></use></svg>' +
                                '<h3>The order has been overpaid</h3>' +
                                '<p>You have payed ' + (Math.abs(response.pending_amount) + Number(response.amount)).toFixed(8) + ' ' + response.currency + ', ' +
                                'it is more than required sum. In case of inconvenience, please, contact support.</p>' +
                                '<a href="' + txUrl + '" title="Check my transaction" target="_blank" rel="noopener">' +
                                '<svg class="invoice__icon_btn" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_link_external"></use></svg>' +
                                'Check my transaction</a>' +
                                '</div>';
                        } else if (['expired', 'cancelled'].includes(response.status)) {
                            if (response.pending_amount < response.amount) {
                                resultContent += '<div class="invoice__result invoice__row_center_vertical">' +
                                    '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_expired"></use></svg>' +
                                    '<h3>The order has not been fully paid</h3>' +
                                    '<p>We have received ' + (response.amount - response.pending_amount).toFixed(8) + ' ' + response.currency + ' of ' + elInvoice.dataset.invoiceAmount + ' '
                                    + elInvoice.dataset.invoiceCurrency + ' required. To get your payment back, please, contact support.</p>' +
                                    '</div>';
                            } else {
                                resultContent += '<div class="invoice__result invoice__row_center_vertical">' +
                                    '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_expired"></use></svg>' +
                                    '<h3>The order has expired</h3>' +
                                    '<p>Please, <a href="/" title="go back">go back</a> and create a new one.</p>' +
                                    '</div>';
                            }
                        } else if (['error'].includes(response.status)) {
                            resultContent += '<div class="invoice__result invoice__row_center_vertical">' +
                                '<svg class="invoice__icon_status" xmlns="http://www.w3.org/2000/svg"><use xlink:href="#icon_invoice_exclamation"></use></svg>' +
                                '<h3>Ooops...</h3>' +
                                '<p>Something went wrong with this operation. Please, contact support, so we could figure this out.</p>' +
                                '</div>';
                            console.error('error');
                        }
                        if (['pending'].includes(response.status)) {
                            elInvoice.querySelector('.invoice__content').innerHTML = resultContent;
                        } else {
                            elInvoice.innerHTML = resultContent;
                        }
                    } catch (error) {
                        console.error('Failed to parse server response ', error);
                    }
                }
            });
        }, 15 * 1000);
    }
});

function copyInvoiceValue(el) {
    var textCopyTo = el.parentElement.querySelector('input');
    try {
        textCopyTo.select();
        textCopyTo.focus();
        document.execCommand('copy');
        // setTimeout(function () {
            // jQuery(el).tooltip('hide');
        // }, 500)
    } catch (err) {
        console.log(err)
    }
}
