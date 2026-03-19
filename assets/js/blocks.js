(function () {
    var registry = window.wc && window.wc.wcBlocksRegistry;
    var settingsApi = window.wc && window.wc.wcSettings;
    var element = window.wp && window.wp.element;
    var htmlEntities = window.wp && window.wp.htmlEntities;

    if (!registry || !settingsApi || !element || !htmlEntities) {
        return;
    }

    var settings = settingsApi.getSetting('clevers_chilean_paypal_data', {});
    var decodeEntities = htmlEntities.decodeEntities;
    var createElement = element.createElement;

    var labelText = decodeEntities(settings.title || 'Pay with PayPal');
    var descriptionText = decodeEntities(settings.description || '');

    var Label = function (props) {
        var PaymentMethodLabel = props.components.PaymentMethodLabel;

        return createElement(PaymentMethodLabel, {
            text: labelText
        });
    };

    var Content = function () {
        return createElement('div', null, descriptionText);
    };

    registry.registerPaymentMethod({
        name: 'clevers_chilean_paypal',
        label: createElement(Label, null),
        content: createElement(Content, null),
        edit: createElement(Content, null),
        ariaLabel: labelText,
        canMakePayment: function () {
            return !!settings.isAvailable && settings.storeCurrency === 'CLP';
        },
        supports: {
            features: settings.supports || ['products']
        }
    });
}());
