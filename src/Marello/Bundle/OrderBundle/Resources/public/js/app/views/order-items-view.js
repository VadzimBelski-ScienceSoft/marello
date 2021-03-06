define(function(require) {
    'use strict';

    var OrderItemsView,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        DeleteConfirmation = require('oroui/js/delete-confirmation'),
        routing = require('routing'),
        mediator = require('oroui/js/mediator'),
        layout = require('oroui/js/layout'),
        AbstractItemsView = require('marellolayout/js/app/views/abstract-items-view');

    /**
     * @export marelloorder/js/app/views/order-items-view
     * @extends marellolayout.app.views.AbstractItemsView
     * @class marelloorder.app.views.OrderItemsView
     */
    OrderItemsView = AbstractItemsView.extend({
        /**
         * @property {Object}
         */
        options: {
            prices: {},
            pricesRoute: 'marello_pricing_price_by_channel',
        },

        /**
         * @property {jQuery}
         */
        $form: null,

        /**
         * @property {jQuery}
         */
        $salesChannel: null,

        /**
         * @property {Object}
         */
        $channelHistory: {'prev':null, 'current':null},

        /**
         * @property {Object}
         */
        $confirm: false,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            OrderItemsView.__super__.initialize.apply(this, arguments);
        },

        /**
         * Doing something after loading child components
         */
        handleLayoutInit: function() {
            OrderItemsView.__super__.handleLayoutInit.apply(this, arguments);
            this.$salesChannel = this.$form.find(':input[data-ftid="' + this.$form.attr('name') + '_salesChannel"]');

            mediator.on('order:get:line-items-prices', this.getLineItemsPrices, this);
            mediator.on('order:load:line-items-prices', this.loadLineItemsPrices, this);
            mediator.on('order:update:line-items', this.updateLineItems, this);

            this.$salesChannel.change(_.bind(function() {
                this.loadLineItemsPrices(this.getItems(), function(response) {
                    mediator.trigger('order:refresh:line-items-prices', response);
                });
                this.setChannelHistory(this._getSalesChannel());
            }, this));

            this.initChannelHistory();
        },

        /**
         * initialize channel history (current and prev selected channels)
         */
        initChannelHistory: function () {
            this.setChannelHistory(this._getSalesChannel());
        },

        /**
         * update the current and prev channels
         * @param channel
         */
        setChannelHistory: function(channel) {
            this.$channelHistory.prev = (this.$channelHistory.current === null) ? parseInt(channel) : this.$channelHistory.current;
            this.$channelHistory.current = parseInt(channel);
        },

        /**
         * update line items with a not-salable class and show which line item
         * is not salable by displaying the error element
         * @param options
         */
        updateLineItems: function (options) {
            if(null === options.salable) {
                return;
            }
            var $elm = options.elm;
            var $errorElm = $elm.find('td.order-line-item-notifications span.error');
            if(false === options.salable.value) {
                $errorElm.find('i').attr('data-content', options.salable.message);
                layout.initPopover($errorElm);
                $errorElm.show();
                if(!this.$confirm) {
                    this.handleConfirmation();
                }
            } else {
                $errorElm.hide();
                this.$confirm = false;
            }
        },

        /**
         * show confirmation that once you change the channel,
         * you will not be able to save the order. On cancel
         * change the channel back to it's previous selected channel
         */
        handleConfirmation: function() {
            var _self = this;
            _self.$confirm = true;
            var message = __('You cannot save this order, there are errors in the Order Items, please correct them before saving the order');
            var confirm = new DeleteConfirmation({
                content: message,
                okText: __('OK')
            });

            confirm.open();
            confirm.on('cancel', function(){
                _self.$confirm = false;
                _self.$salesChannel.val(_self.$channelHistory.prev).trigger('change');
            });

            confirm.on('close', function(){
                _self.$confirm = false;
            });

            confirm.on('ok', function(){
                _self.$confirm = false;
            });
        },

        /**
         * @param {Function} callback
         */
        getLineItemsPrices: function(callback) {
            callback(this.options.prices);
        },

        /**
         * @param {Array} items
         * @param {Function} callback
         */
        loadLineItemsPrices: function(items, callback) {
            var params = {
                product_ids: items
            };

            var salesChannel = this._getSalesChannel();
            if (salesChannel.length !== 0) {
                params = _.extend(params, {salesChannel: salesChannel});
            }

            $.ajax({
                url: routing.generate(this.options.pricesRoute, params),
                type: 'GET',
                success: function(response) {
                    callback(response);
                },
                error: function(response) {
                    callback();
                }
            });
        },

        /**
         * get sales channel value
         * @returns {string}
         * @private
         */
        _getSalesChannel: function() {
            return this.$salesChannel.length !== 0 ? this.$salesChannel.val() : '';
        },

        /**
         * @returns {Array} products
         */
        getItems: function() {
            var lineItems = this.$el.find('.marello-line-item');
            var items = [];

            _.each(lineItems, function(lineItem) {
                var $lineItem = $(lineItem);
                var productId = $lineItem.find('input[data-ftid$="_product"]')[0].value;
                if (productId.length === 0) {
                    return;
                }

                var quantity = $lineItem.find('input[data-ftid$="_quantity"]')[0].value;

                items.push({'product': productId, 'qty': quantity});
            });

            return items;
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            mediator.off('order:get:line-items-prices', this.getLineItemsPrices, this);
            mediator.off('order:load:line-items-prices', this.loadLineItemsPrices, this);
            mediator.off('order:update:line-items', this.updateLineItems, this);

            OrderItemsView.__super__.dispose.call(this);
        }
    });

    return OrderItemsView;
});
