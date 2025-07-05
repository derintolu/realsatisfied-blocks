(function() {
    // Check if WordPress block editor is available
    if (!window.wp || !window.wp.blocks) {
        console.error('WordPress block editor not available');
        return;
    }

    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var __ = wp.i18n.__;

    registerBlockType('realsatisfied-office-blocks/office-ratings', {
        title: __('Office Overall Ratings', 'realsatisfied-office-blocks'),
        icon: 'star-filled',
        category: 'widgets',
        keywords: [
            __('realsatisfied', 'realsatisfied-office-blocks'),
            __('ratings', 'realsatisfied-office-blocks'),
            __('reviews', 'realsatisfied-office-blocks'),
            __('office', 'realsatisfied-office-blocks')
        ],
        description: __('Display office-wide satisfaction, recommendation, and performance ratings with review count.', 'realsatisfied-office-blocks'),
        
        attributes: {
            useCustomField: {
                type: 'boolean',
                default: true
            },
            customFieldName: {
                type: 'string',
                default: 'realsatisfied_feed'
            },
            manualVanityKey: {
                type: 'string',
                default: ''
            },
            showOfficeName: {
                type: 'boolean',
                default: true
            },
            showPhoto: {
                type: 'boolean',
                default: true
            },
            showOverallRating: {
                type: 'boolean',
                default: true
            },
            showStars: {
                type: 'boolean',
                default: true
            },
            showReviewCount: {
                type: 'boolean',
                default: true
            },
            showDetailedRatings: {
                type: 'boolean',
                default: false
            },
            linkToProfile: {
                type: 'boolean',
                default: true
            },
            displaySize: {
                type: 'string',
                default: 'medium'
            },
            displayStyle: {
                type: 'string',
                default: 'minimal'
            },
            textAlignment: {
                type: 'string',
                default: 'center'
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            var customFieldsState = useState([]);
            var customFields = customFieldsState[0];
            var setCustomFields = customFieldsState[1];
            
            var loadingState = useState(false);
            var loading = loadingState[0];
            var setLoading = loadingState[1];

            // Load custom fields when component mounts or when toggle changes
            useEffect(function() {
                if (attributes.useCustomField) {
                    loadCustomFields();
                }
            }, [attributes.useCustomField]);

            function loadCustomFields() {
                setLoading(true);
                
                var formData = new FormData();
                formData.append('action', 'get_office_custom_fields');
                formData.append('nonce', window.realsatisfiedOfficeBlocks ? window.realsatisfiedOfficeBlocks.nonce : '');
                
                fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { 
                    return response.json(); 
                })
                .then(function(data) {
                    if (data.success && data.data) {
                        var options = [{ label: __('Select a custom field...', 'realsatisfied-office-blocks'), value: '' }];
                        if (Array.isArray(data.data)) {
                            options = options.concat(data.data);
                        }
                        setCustomFields(options);
                    } else {
                        // Add fallback options
                        setCustomFields([
                            { label: __('Select a custom field...', 'realsatisfied-office-blocks'), value: '' },
                            { label: 'realsatisfied_feed', value: 'realsatisfied_feed' }
                        ]);
                    }
                    setLoading(false);
                })
                .catch(function(error) {
                    console.error('Error loading custom fields:', error);
                    setCustomFields([
                        { label: __('Select a custom field...', 'realsatisfied-office-blocks'), value: '' },
                        { label: 'realsatisfied_feed', value: 'realsatisfied_feed' }
                    ]);
                    setLoading(false);
                });
            }

            function getVanityKeyValue() {
                if (attributes.useCustomField && attributes.customFieldName) {
                    return __('Custom Field: ', 'realsatisfied-office-blocks') + attributes.customFieldName;
                } else if (attributes.manualVanityKey) {
                    return attributes.manualVanityKey;
                }
                return __('No vanity key set', 'realsatisfied-office-blocks');
            }

            return el('div', null,
                el(InspectorControls, null,
                    // Vanity Key Settings
                    el(PanelBody, { title: __('Vanity Key Settings', 'realsatisfied-office-blocks'), initialOpen: true },
                        el(ToggleControl, {
                            label: __('Use Custom Field', 'realsatisfied-office-blocks'),
                            checked: attributes.useCustomField,
                            onChange: function(value) {
                                setAttributes({ useCustomField: value });
                            },
                            help: __('Use a custom field value as the vanity key', 'realsatisfied-office-blocks')
                        }),

                        attributes.useCustomField ? 
                            el(SelectControl, {
                                label: __('Custom Field', 'realsatisfied-office-blocks'),
                                value: attributes.customFieldName,
                                options: customFields,
                                onChange: function(value) {
                                    setAttributes({ customFieldName: value });
                                },
                                disabled: loading
                            }) :
                            el(TextControl, {
                                label: __('Manual Vanity Key', 'realsatisfied-office-blocks'),
                                value: attributes.manualVanityKey,
                                onChange: function(value) {
                                    setAttributes({ manualVanityKey: value });
                                },
                                placeholder: __('e.g., CENTURY21-Masters-11', 'realsatisfied-office-blocks')
                            })
                    ),

                    // Style Settings
                    el(PanelBody, { title: __('Style Settings', 'realsatisfied-office-blocks'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Display Style', 'realsatisfied-office-blocks'),
                            value: attributes.displayStyle,
                            options: [
                                { label: __('Minimal (Transparent)', 'realsatisfied-office-blocks'), value: 'minimal' },
                                { label: __('Card (White Background)', 'realsatisfied-office-blocks'), value: 'card' },
                                { label: __('Bordered', 'realsatisfied-office-blocks'), value: 'bordered' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayStyle: value });
                            },
                            help: __('Choose how the widget appears on your page', 'realsatisfied-office-blocks')
                        }),

                        el(SelectControl, {
                            label: __('Text Alignment', 'realsatisfied-office-blocks'),
                            value: attributes.textAlignment,
                            options: [
                                { label: __('Left', 'realsatisfied-office-blocks'), value: 'left' },
                                { label: __('Center', 'realsatisfied-office-blocks'), value: 'center' },
                                { label: __('Right', 'realsatisfied-office-blocks'), value: 'right' }
                            ],
                            onChange: function(value) {
                                setAttributes({ textAlignment: value });
                            }
                        }),

                        el(SelectControl, {
                            label: __('Display Size', 'realsatisfied-office-blocks'),
                            value: attributes.displaySize,
                            options: [
                                { label: __('Small', 'realsatisfied-office-blocks'), value: 'small' },
                                { label: __('Medium', 'realsatisfied-office-blocks'), value: 'medium' },
                                { label: __('Large', 'realsatisfied-office-blocks'), value: 'large' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displaySize: value });
                            }
                        })
                    ),

                    // Content Settings
                    el(PanelBody, { title: __('Content Settings', 'realsatisfied-office-blocks'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Office Name', 'realsatisfied-office-blocks'),
                            checked: attributes.showOfficeName,
                            onChange: function(value) {
                                setAttributes({ showOfficeName: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Office Logo', 'realsatisfied-office-blocks'),
                            checked: attributes.showPhoto,
                            onChange: function(value) {
                                setAttributes({ showPhoto: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Overall Rating', 'realsatisfied-office-blocks'),
                            checked: attributes.showOverallRating,
                            onChange: function(value) {
                                setAttributes({ showOverallRating: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Star Ratings', 'realsatisfied-office-blocks'),
                            checked: attributes.showStars,
                            onChange: function(value) {
                                setAttributes({ showStars: value });
                            },
                            help: __('Show/hide the star rating display', 'realsatisfied-office-blocks')
                        }),

                        el(ToggleControl, {
                            label: __('Show Review Count', 'realsatisfied-office-blocks'),
                            checked: attributes.showReviewCount,
                            onChange: function(value) {
                                setAttributes({ showReviewCount: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Detailed Ratings', 'realsatisfied-office-blocks'),
                            checked: attributes.showDetailedRatings,
                            onChange: function(value) {
                                setAttributes({ showDetailedRatings: value });
                            },
                            help: __('Show individual satisfaction, recommendation, and performance ratings', 'realsatisfied-office-blocks')
                        }),

                        el(ToggleControl, {
                            label: __('Link to RealSatisfied Profile', 'realsatisfied-office-blocks'),
                            checked: attributes.linkToProfile,
                            onChange: function(value) {
                                setAttributes({ linkToProfile: value });
                            }
                        })
                    )
                ),

                // Block preview
                el('div', { 
                    className: 'realsatisfied-office-ratings-editor ' + attributes.displaySize + ' style-' + attributes.displayStyle + ' align-' + attributes.textAlignment,
                    style: {
                        border: '2px dashed #ddd',
                        padding: '30px',
                        textAlign: attributes.textAlignment,
                        background: attributes.displayStyle === 'card' ? '#fff' : 
                                   attributes.displayStyle === 'bordered' ? 'transparent' : '#fafafa',
                        borderRadius: '8px'
                    }
                },
                    el('div', { className: 'block-icon', style: { marginBottom: '20px' } },
                        el('span', { 
                            className: 'dashicons dashicons-star-filled',
                            style: { fontSize: '48px', color: '#007cba', display: 'block', marginBottom: '10px' }
                        }),
                        el('h3', { style: { margin: 0, color: '#333' } }, __('Office Overall Ratings', 'realsatisfied-office-blocks'))
                    ),

                    el('div', { 
                        className: 'block-info',
                        style: {
                            background: 'white',
                            border: '1px solid #ddd',
                            borderRadius: '4px',
                            padding: '15px',
                            margin: '15px 0',
                            textAlign: 'left'
                        }
                    },
                        el('p', { style: { margin: '5px 0' } },
                            el('strong', null, __('Vanity Key: ', 'realsatisfied-office-blocks')),
                            getVanityKeyValue()
                        ),
                        el('p', { style: { margin: '5px 0' } },
                            el('strong', null, __('Style: ', 'realsatisfied-office-blocks')),
                            attributes.displayStyle + ' | ' + attributes.textAlignment + ' | ' + attributes.displaySize
                        ),
                        el('p', { style: { margin: '5px 0' } },
                            el('strong', null, __('Show Elements: ', 'realsatisfied-office-blocks')),
                            [
                                attributes.showOfficeName && __('Name', 'realsatisfied-office-blocks'),
                                attributes.showPhoto && __('Logo', 'realsatisfied-office-blocks'),
                                attributes.showOverallRating && __('Rating', 'realsatisfied-office-blocks'),
                                attributes.showStars && __('Stars', 'realsatisfied-office-blocks'),
                                attributes.showReviewCount && __('Count', 'realsatisfied-office-blocks'),
                                attributes.showDetailedRatings && __('Details', 'realsatisfied-office-blocks')
                            ].filter(Boolean).join(', ') || __('None', 'realsatisfied-office-blocks')
                        )
                    ),

                    el('div', { 
                        className: 'block-preview',
                        style: { color: '#666', fontStyle: 'italic' }
                    },
                        __('Office ratings will be displayed here on the frontend', 'realsatisfied-office-blocks')
                    )
                )
            );
        },

        save: function() {
            // Return null because this is a dynamic block (server-side rendered)
            return null;
        }
    });

})(); 