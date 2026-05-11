/**
 * CamPay Block Checkout — Payment Method Registration
 */
(function () {
    'use strict';

    if (!window.wc || !window.wc.wcBlocksRegistry || !window.wp || !window.wp.element) {
        return;
    }

    var registerPaymentMethod = window.wc.wcBlocksRegistry.registerPaymentMethod;
    var el = window.wp.element.createElement;
    var useState = window.wp.element.useState;
    var useEffect = window.wp.element.useEffect;
    var useRef = window.wp.element.useRef;

    var settings = window.kctmCampayData || {};
    var title = settings.title || 'Mobile Money (MTN / Orange)';
    var description = settings.description || '';

    var Label = function () {
        return el('span', { style: { fontWeight: '600' } }, title);
    };

    var Content = function (props) {
        var eventRegistration = props.eventRegistration || {};
        var emitResponse = props.emitResponse || {};
        var onPaymentSetup = eventRegistration.onPaymentSetup;

        var phoneState = useState('');
        var phone = phoneState[0];
        var setPhone = phoneState[1];
        var phoneRef = useRef('');

        // Keep ref synced
        useEffect(function () {
            phoneRef.current = phone;
        }, [phone]);

        // Register payment validation
        useEffect(function () {
            if (!onPaymentSetup) return;

            var unsubscribe = onPaymentSetup(function () {
                var val = phoneRef.current.replace(/[\s\-]/g, '');

                if (!val || val.length < 9) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Please enter a valid 9-digit Mobile Money phone number.'
                    };
                }

                if (!/^[26]\d{8}$/.test(val)) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Phone number must start with 6 or 2 and be 9 digits.'
                    };
                }

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            kctm_campay_phone: val
                        }
                    }
                };
            });

            return unsubscribe;
        }, [onPaymentSetup]);

        return el('div', { style: { padding: '8px 0' } },
            description ? el('p', { style: { marginBottom: '12px', color: '#555', fontSize: '14px' } }, description) : null,
            el('label', {
                htmlFor: 'kctm-campay-phone',
                style: { display: 'block', fontWeight: '600', color: '#402417', marginBottom: '4px', fontSize: '14px' }
            }, 'Mobile Money Phone Number ', el('abbr', { style: { color: '#e74c3c' } }, '*')),
            el('input', {
                type: 'tel',
                id: 'kctm-campay-phone',
                placeholder: 'e.g. 6XXXXXXXX',
                maxLength: 9,
                value: phone,
                onChange: function (e) { setPhone(e.target.value); },
                style: {
                    width: '100%',
                    padding: '10px 12px',
                    border: '1px solid #ccc',
                    borderRadius: '4px',
                    fontSize: '15px',
                    boxSizing: 'border-box'
                }
            }),
            el('span', { style: { fontSize: '12px', color: '#888', marginTop: '4px', display: 'block' } },
                'Enter your 9-digit Cameroon phone number (without country code).'
            )
        );
    };

    registerPaymentMethod({
        name: 'kctm_campay',
        label: el(Label, null),
        content: el(Content, null),
        edit: el('div', null, title),
        canMakePayment: function () { return true; },
        ariaLabel: title,
        supports: {
            features: settings.supports || ['products']
        }
    });
})();
