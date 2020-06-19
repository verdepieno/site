/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'mage/url'
    ],
    function (Component, quote, url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Webgriffe_QuiPago/payment/quipago'
            },

            redirectAfterPlaceOrder: false,
            /**
             * After place order callback
             */
            afterPlaceOrder: function () {
                window.location.replace(
                    url.build('quipago/redirect/lastorder')
                );
            }
        });
    }
);
