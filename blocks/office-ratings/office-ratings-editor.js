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

    registerBlockType('realsatisfied-blocks/office-ratings', {
        title: __('Office Overall Ratings', 'realsatisfied-blocks'),
        icon: 'star-filled',
        category: 'widgets',
        keywords: [
            __('realsatisfied', 'realsatisfied-blocks'),
            __('ratings', 'realsatisfied-blocks'),
            __('reviews', 'realsatisfied-blocks'),
            __('office', 'realsatisfied-blocks')
        ],
        description: __('Display office-wide satisfaction, recommendation, and performance ratings with review count.', 'realsatisfied-blocks'),
        
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
                        var options = [{ label: __('Select a custom field...', 'realsatisfied-blocks'), value: '' }];
                        if (Array.isArray(data.data)) {
                            options = options.concat(data.data);
                        }
                        setCustomFields(options);
                    } else {
                        // Add fallback options
                        setCustomFields([
                            { label: __('Select a custom field...', 'realsatisfied-blocks'), value: '' },
                            { label: 'realsatisfied_feed', value: 'realsatisfied_feed' }
                        ]);
                    }
                    setLoading(false);
                })
                .catch(function(error) {
                    console.error('Error loading custom fields:', error);
                    setCustomFields([
                        { label: __('Select a custom field...', 'realsatisfied-blocks'), value: '' },
                        { label: 'realsatisfied_feed', value: 'realsatisfied_feed' }
                    ]);
                    setLoading(false);
                });
            }

            function getVanityKeyValue() {
                if (attributes.useCustomField && attributes.customFieldName) {
                    return __('Custom Field: ', 'realsatisfied-blocks') + attributes.customFieldName;
                } else if (attributes.manualVanityKey) {
                    return attributes.manualVanityKey;
                }
                return __('No vanity key set', 'realsatisfied-blocks');
            }

            return el('div', null,
                el(InspectorControls, null,
                    // Vanity Key Settings
                    el(PanelBody, { title: __('Vanity Key Settings', 'realsatisfied-blocks'), initialOpen: true },
                        el(ToggleControl, {
                            label: __('Use Custom Field', 'realsatisfied-blocks'),
                            checked: attributes.useCustomField,
                            onChange: function(value) {
                                setAttributes({ useCustomField: value });
                            },
                            help: __('Use a custom field value as the vanity key', 'realsatisfied-blocks')
                        }),

                        attributes.useCustomField ? 
                            el(SelectControl, {
                                label: __('Custom Field', 'realsatisfied-blocks'),
                                value: attributes.customFieldName,
                                options: customFields,
                                onChange: function(value) {
                                    setAttributes({ customFieldName: value });
                                },
                                disabled: loading
                            }) :
                            el(TextControl, {
                                label: __('Manual Vanity Key', 'realsatisfied-blocks'),
                                value: attributes.manualVanityKey,
                                onChange: function(value) {
                                    setAttributes({ manualVanityKey: value });
                                },
                                placeholder: __('e.g., CENTURY21-Masters-11', 'realsatisfied-blocks')
                            })
                    ),

                    // Style Settings
                    el(PanelBody, { title: __('Style Settings', 'realsatisfied-blocks'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Display Style', 'realsatisfied-blocks'),
                            value: attributes.displayStyle,
                            options: [
                                { label: __('Minimal (Transparent)', 'realsatisfied-blocks'), value: 'minimal' },
                                { label: __('Card (White Background)', 'realsatisfied-blocks'), value: 'card' },
                                { label: __('Bordered', 'realsatisfied-blocks'), value: 'bordered' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displayStyle: value });
                            },
                            help: __('Choose how the widget appears on your page', 'realsatisfied-blocks')
                        }),

                        el(SelectControl, {
                            label: __('Text Alignment', 'realsatisfied-blocks'),
                            value: attributes.textAlignment,
                            options: [
                                { label: __('Left', 'realsatisfied-blocks'), value: 'left' },
                                { label: __('Center', 'realsatisfied-blocks'), value: 'center' },
                                { label: __('Right', 'realsatisfied-blocks'), value: 'right' }
                            ],
                            onChange: function(value) {
                                setAttributes({ textAlignment: value });
                            }
                        }),

                        el(SelectControl, {
                            label: __('Display Size', 'realsatisfied-blocks'),
                            value: attributes.displaySize,
                            options: [
                                { label: __('Small', 'realsatisfied-blocks'), value: 'small' },
                                { label: __('Medium', 'realsatisfied-blocks'), value: 'medium' },
                                { label: __('Large', 'realsatisfied-blocks'), value: 'large' }
                            ],
                            onChange: function(value) {
                                setAttributes({ displaySize: value });
                            }
                        })
                    ),

                    // Content Settings
                    el(PanelBody, { title: __('Content Settings', 'realsatisfied-blocks'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Show Office Name', 'realsatisfied-blocks'),
                            checked: attributes.showOfficeName,
                            onChange: function(value) {
                                setAttributes({ showOfficeName: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Office Logo', 'realsatisfied-blocks'),
                            checked: attributes.showPhoto,
                            onChange: function(value) {
                                setAttributes({ showPhoto: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Overall Rating', 'realsatisfied-blocks'),
                            checked: attributes.showOverallRating,
                            onChange: function(value) {
                                setAttributes({ showOverallRating: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Star Ratings', 'realsatisfied-blocks'),
                            checked: attributes.showStars,
                            onChange: function(value) {
                                setAttributes({ showStars: value });
                            },
                            help: __('Show/hide the star rating display', 'realsatisfied-blocks')
                        }),

                        el(ToggleControl, {
                            label: __('Show Review Count', 'realsatisfied-blocks'),
                            checked: attributes.showReviewCount,
                            onChange: function(value) {
                                setAttributes({ showReviewCount: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Detailed Ratings', 'realsatisfied-blocks'),
                            checked: attributes.showDetailedRatings,
                            onChange: function(value) {
                                setAttributes({ showDetailedRatings: value });
                            },
                            help: __('Show individual satisfaction, recommendation, and performance ratings', 'realsatisfied-blocks')
                        }),

                        el(ToggleControl, {
                            label: __('Link to RealSatisfied Profile', 'realsatisfied-blocks'),
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
                        el('h3', { style: { margin: 0, color: '#333' } }, __('Office Overall Ratings', 'realsatisfied-blocks'))
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
                            el('strong', null, __('Vanity Key: ', 'realsatisfied-blocks')),
                            getVanityKeyValue()
                        ),
                        el('p', { style: { margin: '5px 0' } },
                            el('strong', null, __('Style: ', 'realsatisfied-blocks')),
                            attributes.displayStyle + ' | ' + attributes.textAlignment + ' | ' + attributes.displaySize
                        ),
                        el('p', { style: { margin: '5px 0' } },
                            el('strong', null, __('Show Elements: ', 'realsatisfied-blocks')),
                            [
                                attributes.showOfficeName && __('Name', 'realsatisfied-blocks'),
                                attributes.showPhoto && __('Logo', 'realsatisfied-blocks'),
                                attributes.showOverallRating && __('Rating', 'realsatisfied-blocks'),
                                attributes.showStars && __('Stars', 'realsatisfied-blocks'),
                                attributes.showReviewCount && __('Count', 'realsatisfied-blocks'),
                                attributes.showDetailedRatings && __('Details', 'realsatisfied-blocks')
                            ].filter(Boolean).join(', ') || __('None', 'realsatisfied-blocks')
                        )
                    ),

                    el('div', { 
                        className: 'block-preview',
                        style: { color: '#666', fontStyle: 'italic' }
                    },
                        __('Office ratings will be displayed here on the frontend', 'realsatisfied-blocks')
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