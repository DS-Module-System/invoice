import 'module-invoice-css';

// import $ from 'jquery';

import '../../libs/jquery-ui-1.13.2/external/jquery/jquery.js'
import '../../libs/jquery-ui-1.13.2/jquery-ui.min.js'

import Decimal from 'decimal.js';
// import { debug } from 'console';


function invoiceItemOrderReindex() {

    $('.invoice-item-order-index').each(function (k, v) {
        $(v).val(k + 1);
    });
}

function setDefaultValue() {
    $('.invoice-item-qty').each(function (k, v) {
        if ($(v).val() == '') {
            $(v).val('0.00');
        }
    });
    $('.invoice-item-unit-price').each(function (k, v) {
        if ($(v).val() == '') {
            $(v).val('0.00');
        }
    });
    $('.invoice-item-total').each(function (k, v) {
        if ($(v).val() == '') {
            $(v).val('0.00').trigger('change');
        }
    });
}

function reCalcInvoice() {

    let subTotalPrice = new Decimal(0);

    let invoiceItems = $('.invoiceItems tr');
    invoiceItems.each(function (k, v) {
        let el = $(v);
        let qty = new Decimal(el.find('.invoice-item-qty').val());
        let unitPrice = new Decimal(el.find('.invoice-item-unit-price').val());
        let itemTotalPrice = qty.times(unitPrice).toNumber().toFixed(2);
        subTotalPrice = subTotalPrice.plus(new Decimal(itemTotalPrice));
        el.find('.invoice-item-total').val(itemTotalPrice).trigger("change");
    });

    $('.invoice-sub-total').val(subTotalPrice.toNumber().toFixed(2)).trigger("change");

    let invoicePromotionValue = new Decimal($('.invoice-promotion-value').val());
    let invoicePromotionType = $('.invoice-promotion-type').val();
    let invoicePromotionPrice = null;
    if (parseInt(invoicePromotionType) === 1) {
        invoicePromotionPrice = (new Decimal(subTotalPrice)).times(invoicePromotionValue).dividedBy(100).toNumber().toFixed(2);
    } else {
        invoicePromotionPrice = new Decimal(invoicePromotionValue).toNumber().toFixed(2);
    }

    $('.invoice-promotion-price').val(invoicePromotionPrice).trigger("change");

    let invoiceTaxBasePrice = (new Decimal(subTotalPrice)).minus(invoicePromotionPrice).toNumber().toFixed(2);

    $('.invoice-tax-base-price').val(invoiceTaxBasePrice).trigger("change");

    let invoiceDdsPercentage = new Decimal($('.invoice-dds-percentage').val());

    let invoiceDdsPrice = (new Decimal(invoiceTaxBasePrice)).times(invoiceDdsPercentage).dividedBy(100).toNumber().toFixed(2);

    $('.invoice-dds-price').val(invoiceDdsPrice).trigger("change");

    let invoiceTotalPrice = (new Decimal(invoiceTaxBasePrice)).plus(invoiceDdsPrice).toNumber().toFixed(2);

    $('.invoice-total-price').val(invoiceTotalPrice).trigger("change");
}

$(document).ready(function () {


    const addFormToCollection = (e) => {
        const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);

        const item = document.createElement('tr');

        item.innerHTML = collectionHolder
            .dataset
            .prototype
            .replace(
                /__name__/g,
                collectionHolder.dataset.index
            );

        collectionHolder.appendChild(item);

        collectionHolder.dataset.index++;
        return item
    };


    document
        .querySelectorAll('.add_item_link_invoice')
        .forEach(btn => {
            btn.addEventListener("click", function (e) {
                addFormToCollection(e);
                invoiceItemOrderReindex();
                $('.invoiceItemPrototype').sortable('refresh');
                setDefaultValue();
            })
        });

    $(document).on('click', '.invoice-item-remove-row', function (e) {
        let tr = $(this).closest('tr');
        tr.remove();
        invoiceItemOrderReindex();
        $('.invoiceItemPrototype').sortable('refresh');
        reCalcInvoice();
    });

    $(document).on('change', '.invoice-item-qty', function (e) {
        let el = $(e.target);
        let val = parseFloat(el.val().replace(/,/g, ".")).toFixed(2);
        if (isNaN(val)) {
            val = (0.00).toFixed(2);
        }
        el.val(val);
        reCalcInvoice();
    });

    $(document).on('change', '.invoice-dds-percentage', function (e) {
        let el = $(e.target);
        let val = parseInt(el.val());
        if (isNaN(val)) {
            val = 0;
        }
        el.val(val);
        reCalcInvoice();
    });

    $(document).on('change', '.invoice-item-unit-price, .invoice-promotion-value', function (e) {
        let el = $(e.target);
        let val = parseFloat(el.val().replace(/,/g, ".")).toFixed(2);
        if (isNaN(val)) {
            val = (0.00).toFixed(2);
        }
        el.val(val);
        reCalcInvoice();
    });


    $('.invoiceItemPrototype').sortable({
        handle: '.invoice-item-move-row',
        cancel: '',
        update: invoiceItemOrderReindex
    });

    $('.show-in-div').each(function (k, v) {
        let el = $(v);
        el.parent().find('.show-number').text(el.val());
    });

    $(document).on('change', '.show-in-div', function (e) {
        let el = $(e.target);
        el.parent().find('.show-number').text(el.val());
    });

    $('.invoice-promotion-type').on('change', function (e) {
        reCalcInvoice();
    });

    $('form').submit(function () {
        reCalcInvoice();
        return true;
    });

    $('.invoice-dds-option').on('change', function (e) {
        if ($(this).val() > 1) {
            $('.invoice-dds-percentage').val(0).trigger('change');
        } else {
            $('.invoice-dds-percentage').val(20).trigger('change');
        }
    });

$(document).on('click', '#getInvoiceClientDataBtn', function (e) {
    // const btn = e.target.closest("#getInvoiceClientDataBtn");
    // if (!btn) return;

    // The <select> for client in your invoice form
    const clientSelect = document.querySelector("#invoice_form_client");
    const clientId = clientSelect ? clientSelect.value : null;

    if (!clientId) {
        // alert("Моля изберете клиент първо.");
        return;
    }

    // Build final URL
    const url = e.target.getAttribute("data-form-url").replace("__CLIENT_ID__", clientId);

    fetch(url)
        .then(r => r.json())
        .then(data => {
            document.querySelector("#invoice_form_name").value = data.name ?? "";
            document.querySelector("#invoice_form_countryCode").value = data.countryCode ?? "";
            document.querySelector("#invoice_form_eek").value = data.eek ?? "";
            document.querySelector("#invoice_form_vat").value = data.vat ?? "";
            document.querySelector("#invoice_form_responsiblePerson").value = data.responsiblePerson ?? "";
            document.querySelector("#invoice_form_address").value = data.address ?? "";
        })
        .catch(() => console.log("Error loading data"));
});


    function deleteElementById(jsonArray, id) {
        // console.log('jsonArray', jsonArray);
        // Намираме индекса на елемента в масива с даденото id
        const index = jsonArray.findIndex(item => item.id == id);

        // Ако елементът съществува, го премахваме
        if (index !== -1) {
            jsonArray.splice(index, 1);  // Изтриваме елемента по индекс
            console.log(`Елемент с id ${id} беше изтрит.`);
        } else {
            console.log(`Елемент с id ${id} не беше намерен.`);
        }

        return jsonArray;  // Връщаме актуализирания масив
    }



});
