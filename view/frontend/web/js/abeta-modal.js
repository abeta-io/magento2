define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'Magento_Customer/js/customer-data'
], function ($, modal, customerData) {
    'use strict';

    return function (config) {
        const AbetaModal = {
            isCheckoutSuccess: false,

            html: {
                button: $('#punch-out-button'),
                popup: $('#punch-out-button-modal'),
                table: $('#punch-out-table'),
                message: $('#punch-out-popup-message > div'),
            },

            request: {
                redirect: '',
                cart: `${window.location.origin}/rest/V1/abeta/cart`,
                checkout: `${window.location.origin}/rest/V1/abeta/checkout`,
            },

            option: {
                popup: {
                    title: $.mage.__('Order data for PunchOut session'),
                    type: 'popup',
                    responsive: true,
                    innerScroll: true,
                    modalClass: 'abeta-modal',
                    buttons: [
                        {
                            text: config.buttonLabel,
                            class: 'action primary submit abeta-process',
                            click: () => AbetaModal.fetchData(
                                AbetaModal.request.checkout,
                                AbetaModal.getCheckout
                            ),
                        },
                        {
                            text: $.mage.__('Close'),
                            class: 'action primary close abeta-close',
                            click() { this.closeModal() },
                        }
                    ],
                },
            },

            init() {
                modal(this.option.popup, this.html.popup);
                this.html.button.on('click', () => this.fetchData(this.request.cart, this.setProducts.bind(this)));
                $(this.html.popup).on('modalclosed', () => this.closeModal());
            },

            fetchData(request, callback) {
                $('body').trigger('processStart');

                fetch(request)
                    .then((resp) => resp.json())
                    .then((json) => callback(json))
                    .finally(() => $('body').trigger('processStop'));
            },

            setProducts(items) {
                const box = this.html.table.find('tbody');

                box.empty();
                this.html.popup.modal('openModal');

                items[0].forEach((product) => {
                    const {name, qty} = product;
                    name && box.append(this.createProductRow(name, qty));
                });
            },

            createProductRow(name, qty) {
                return `<tr>
                            <td>${name}</td>
                            <td>${qty}</td>
                        </tr>`;
            },

            getCheckout(data) {
                if (!data[0]?.success) {
                    AbetaModal.html.message.html(data[0].message);
                    AbetaModal.html.message.parent().addClass('error').removeClass('hidden');
                }

                if (data[0][0]?.success) {
                    AbetaModal.isCheckoutSuccess = true;
                    AbetaModal.html.message.html(data[0][0].message);
                    AbetaModal.html.message.parent().addClass('success').removeClass('hidden');
                    AbetaModal.html.table.hide();
                    $('.abeta-process').hide();

                    AbetaModal.request.redirect = data[0][0]['redirect_url'];
                    customerData.invalidate(['cart']);
                    customerData.reload(['cart'], true);

                    // setTimeout(() => { AbetaModal.closeModal() }, 5000);
                    AbetaModal.closeModal()
                }
            },

            closeModal() {
                if(this.isCheckoutSuccess) window.location.href = this.request.redirect;
            },
        }

        AbetaModal.init();
    }
});
