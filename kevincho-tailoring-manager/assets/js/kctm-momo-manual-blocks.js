/**
 * MoMo Manual Block Checkout — Payment Method Registration
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

    var settings = window.kctmMomoManualData || {};
    var title = settings.title || 'Mobile Money (MoMo)';
    var description = settings.description || '';
    var momoNumber = settings.momo_number || '';
    var momoName = settings.momo_name || '';

    var Label = function () {
        return el('span', { style: { fontWeight: '600' } }, title);
    };

    var Content = function (props) {
        var eventRegistration = props.eventRegistration || {};
        var emitResponse = props.emitResponse || {};
        var onPaymentSetup = eventRegistration.onPaymentSetup;
        var billing = props.billing || {};
        var cartTotal = billing.cartTotal || {};
        var totalValue = cartTotal.value || 0;
        var currency = cartTotal.currency || 'XAF';
        var displayTotal = (currency === 'XAF' || currency === 'NGN') ? totalValue : (totalValue / 100);
        var formattedTotal = displayTotal.toLocaleString() + ' FCFA';

        var txState = useState('');
        var txId = txState[0];
        var setTxId = txState[1];
        var txRef = useRef('');

        var fileState = useState(null);
        var selectedFile = fileState[0];
        var setSelectedFile = fileState[1];
        var fileRef = useRef(null);

        useEffect(function () {
            txRef.current = txId;
        }, [txId]);

        useEffect(function () {
            fileRef.current = selectedFile;
        }, [selectedFile]);

        useEffect(function () {
            if (!onPaymentSetup) return;

            var unsubscribe = onPaymentSetup(function () {
                var val = txRef.current.trim();

                if (!val || val.length < 3) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Veuillez entrer votre ID de transaction MoMo. / Please enter your MoMo Financial Transaction ID.'
                    };
                }

                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: {
                            kctm_momo_ref: val
                        }
                    }
                };
            });

            return unsubscribe;
        }, [onPaymentSetup]);

        return el('div', { style: { padding: '8px 0' } },

            // Payment details box
            el('div', {
                style: {
                    background: '#fef9e7',
                    border: '2px solid #c9a96e',
                    borderRadius: '10px',
                    padding: '16px 18px',
                    marginBottom: '16px',
                    fontSize: '14px',
                    lineHeight: '1.6'
                }
            },
                el('div', {
                    style: {
                        fontWeight: '700',
                        fontSize: '15px',
                        color: '#402417',
                        marginBottom: '10px',
                        borderBottom: '1px solid #e8d5a3',
                        paddingBottom: '8px'
                    }
                }, 'Payment Details / Informations de paiement'),

                el('div', { style: { display: 'flex', justifyContent: 'space-between', padding: '3px 0' } },
                    el('span', { style: { color: '#666' } }, 'Send to / Envoyer au:'),
                    el('span', { style: { fontWeight: '700', color: '#402417', fontSize: '16px' } }, momoNumber)
                ),
                el('div', { style: { display: 'flex', justifyContent: 'space-between', padding: '3px 0' } },
                    el('span', { style: { color: '#666' } }, 'Name / Nom:'),
                    el('span', { style: { fontWeight: '600', color: '#402417' } }, momoName)
                ),
                el('div', { style: { display: 'flex', justifyContent: 'space-between', padding: '3px 0' } },
                    el('span', { style: { color: '#666' } }, 'Amount / Montant:'),
                    el('span', { style: { fontWeight: '700', color: '#c9a96e', fontSize: '16px' } }, formattedTotal)
                ),

                // Step by step instructions
                el('div', {
                    style: {
                        marginTop: '12px',
                        padding: '12px',
                        background: '#fff',
                        borderRadius: '6px',
                        border: '1px solid #e8d5a3'
                    }
                },
                    el('p', { style: { margin: '0 0 6px', color: '#402417', fontSize: '14px', fontWeight: '600' } },
                        'How to pay / Comment payer:'
                    ),
                    el('ol', { style: { margin: '0', paddingLeft: '20px', color: '#402417', fontSize: '13px', lineHeight: '1.8' } },
                        el('li', null,
                            'Send ',
                            el('strong', null, formattedTotal),
                            ' via MoMo or Orange Money to ',
                            el('strong', null, momoNumber),
                            ' (',
                            momoName,
                            ').',
                            el('br', null),
                            el('span', { style: { color: '#888', fontSize: '12px' } },
                                'Envoyez ' + formattedTotal + ' par MoMo ou Orange Money au ' + momoNumber + ' (' + momoName + ').'
                            )
                        ),
                        el('li', null,
                            'You will receive a ',
                            el('strong', null, 'confirmation SMS'),
                            ' from MoMo/Orange Money.',
                            el('br', null),
                            el('span', { style: { color: '#888', fontSize: '12px' } },
                                'Vous recevrez un SMS de confirmation de MoMo/Orange Money.'
                            )
                        ),
                        el('li', null,
                            'In that message, find the ',
                            el('strong', null, 'Financial Transaction ID'),
                            ' (a number).',
                            el('br', null),
                            el('span', { style: { color: '#888', fontSize: '12px' } },
                                'Dans ce message, trouvez l\'ID de transaction financi\u00e8re (un num\u00e9ro).'
                            )
                        ),
                        el('li', null,
                            'Copy and paste that ID below, then click ',
                            el('strong', null, 'Place Order'),
                            '.',
                            el('br', null),
                            el('span', { style: { color: '#888', fontSize: '12px' } },
                                'Copiez et collez cet ID ci-dessous, puis cliquez sur Commander.'
                            )
                        )
                    )
                )
            ),

            // Transaction ID input
            el('div', { style: { marginBottom: '14px' } },
                el('label', {
                    htmlFor: 'kctm-momo-manual-txid',
                    style: {
                        display: 'block',
                        fontWeight: '600',
                        color: '#402417',
                        marginBottom: '4px',
                        fontSize: '14px'
                    }
                },
                    'Financial Transaction ID / ID de transaction ',
                    el('abbr', { style: { color: '#e74c3c' } }, '*')
                ),
                el('input', {
                    type: 'text',
                    id: 'kctm-momo-manual-txid',
                    placeholder: 'e.g. 1234567890',
                    value: txId,
                    onChange: function (e) { setTxId(e.target.value); },
                    style: {
                        width: '100%',
                        padding: '10px 12px',
                        border: '2px solid #c9a96e',
                        borderRadius: '6px',
                        fontSize: '15px',
                        boxSizing: 'border-box'
                    }
                }),
                el('span', {
                    style: { fontSize: '12px', color: '#888', marginTop: '4px', display: 'block' }
                }, 'Copy this from your MoMo/Orange Money confirmation SMS. / Copiez ceci de votre SMS de confirmation.')
            ),

            // Screenshot upload
            el('div', { style: { marginBottom: '10px' } },
                el('label', {
                    htmlFor: 'kctm-momo-screenshot',
                    style: {
                        display: 'block',
                        fontWeight: '600',
                        color: '#402417',
                        marginBottom: '4px',
                        fontSize: '14px'
                    }
                }, 'Payment Screenshot / Capture d\u2019\u00e9cran (optional)'),
                el('div', {
                    style: {
                        border: '2px dashed #c9a96e',
                        borderRadius: '6px',
                        padding: '12px',
                        textAlign: 'center',
                        background: '#fef9e7',
                        cursor: 'pointer'
                    },
                    onClick: function () {
                        var input = document.getElementById('kctm-momo-screenshot');
                        if (input) input.click();
                    }
                },
                    el('input', {
                        type: 'file',
                        id: 'kctm-momo-screenshot',
                        accept: 'image/*',
                        style: { display: 'none' },
                        onChange: function (e) {
                            var file = e.target.files && e.target.files[0];
                            setSelectedFile(file ? file.name : null);
                        }
                    }),
                    selectedFile
                        ? el('span', { style: { color: '#402417', fontSize: '13px' } }, '\u2705 ' + selectedFile)
                        : el('span', { style: { color: '#888', fontSize: '13px' } },
                            '\ud83d\udcf7 Click to upload screenshot / Cliquez pour t\u00e9l\u00e9charger la capture'
                        )
                ),
                el('span', {
                    style: { fontSize: '11px', color: '#888', marginTop: '4px', display: 'block' }
                }, 'Upload a screenshot of your MoMo confirmation message. / T\u00e9l\u00e9chargez une capture de votre message de confirmation MoMo.')
            )
        );
    };

    registerPaymentMethod({
        name: 'kctm_momo_manual',
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
