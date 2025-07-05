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
    var RangeControl = wp.components.RangeControl;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var __ = wp.i18n.__;

    // Wrap in try-catch to prevent conflicts
    try {

    registerBlockType('realsatisfied/block', {
        title: __('RealSatisfied Reviews'),
        icon: 'star-filled',
        category: 'widgets',
        attributes: {
            useCustomField: {
                type: 'boolean',
                default: false
            },
            customFieldName: {
                type: 'string',
                default: ''
            },
            manualVanityKey: {
                type: 'string',
                default: ''
            },
            mode: {
                type: 'string',
                default: 'Office'
            },
            displayPhoto: {
                type: 'boolean',
                default: true
            },
            showOverallRatings: {
                type: 'boolean',
                default: true
            },
            showRsBanner: {
                type: 'boolean',
                default: true
            },
            displayRatings: {
                type: 'boolean',
                default: true
            },
            showDates: {
                type: 'boolean',
                default: true
            },
            autoAnimate: {
                type: 'boolean',
                default: true
            },
            displayArrows: {
                type: 'boolean',
                default: true
            },
            speed: {
                type: 'number',
                default: 2
            },
            animationType: {
                type: 'string',
                default: 'slide'
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            
            // Use useState correctly
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
                
                // Try to get post ID, with fallback
                var postId;
                try {
                    postId = wp.data.select('core/editor').getCurrentPostId();
                } catch(e) {
                    // Fallback for older WordPress versions
                    postId = window.wp && window.wp.data && window.wp.data.select('core/editor') ? 
                        window.wp.data.select('core/editor').getCurrentPostId() : 
                        (window.pagenow === 'post' ? window.typenow : null);
                }
                
                if (!postId) {
                    console.error('Could not get post ID');
                    setLoading(false);
                    return;
                }
                
                var formData = new FormData();
                formData.append('action', 'get_custom_fields');
                formData.append('post_id', postId);
                formData.append('nonce', realsatisfiedBlock.nonce);
                
                fetch(realsatisfiedBlock.ajaxUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(function(response) { 
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json(); 
                })
                .then(function(data) {
                    console.log('AJAX Response:', data);
                    if (data.success && data.data) {
                        var options = [{ label: __('Select a custom field...'), value: '' }];
                        if (Array.isArray(data.data)) {
                            options = options.concat(data.data);
                        }
                        setCustomFields(options);
                    } else {
                        console.error('AJAX Error:', data);
                        // Add some default fields as fallback
                        setCustomFields([
                            { label: __('Select a custom field...'), value: '' },
                            { label: 'realsatisfied_feed', value: 'realsatisfied_feed' }
                        ]);
                    }
                    setLoading(false);
                })
                .catch(function(error) {
                    console.error('Error loading custom fields:', error);
                    // Add fallback options
                    setCustomFields([
                        { label: __('Select a custom field...'), value: '' },
                        { label: 'realsatisfied_feed', value: 'realsatisfied_feed' }
                    ]);
                    setLoading(false);
                });
            }

            function getVanityKeyValue() {
                if (attributes.useCustomField && attributes.customFieldName) {
                    return 'Custom Field: ' + attributes.customFieldName;
                } else if (attributes.manualVanityKey) {
                    return attributes.manualVanityKey;
                }
                return __('No vanity key set');
            }

            return el('div', null,
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Vanity Key Settings'), initialOpen: true },
                        el(ToggleControl, {
                            label: __('Use Custom Field'),
                            checked: attributes.useCustomField,
                            onChange: function(value) {
                                setAttributes({ useCustomField: value });
                            },
                            help: __('Use a custom field value as the vanity key')
                        }),

                        attributes.useCustomField ? 
                            el(SelectControl, {
                                label: __('Custom Field'),
                                value: attributes.customFieldName,
                                options: customFields,
                                onChange: function(value) {
                                    setAttributes({ customFieldName: value });
                                },
                                disabled: loading
                            }) :
                            el(TextControl, {
                                label: __('Manual Vanity Key'),
                                value: attributes.manualVanityKey,
                                onChange: function(value) {
                                    setAttributes({ manualVanityKey: value });
                                },
                                placeholder: 'e.g., CENTURY21-Masters-11'
                            })
                    ),

                    el(PanelBody, { title: __('Widget Settings'), initialOpen: false },
                        el(SelectControl, {
                            label: __('Mode'),
                            value: attributes.mode,
                            options: [
                                { label: 'Office', value: 'Office' },
                                { label: 'Agent', value: 'Agent' }
                            ],
                            onChange: function(value) {
                                setAttributes({ mode: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Display Photo'),
                            checked: attributes.displayPhoto,
                            onChange: function(value) {
                                setAttributes({ displayPhoto: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Overall Ratings'),
                            checked: attributes.showOverallRatings,
                            onChange: function(value) {
                                setAttributes({ showOverallRatings: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show RealSatisfied Banner'),
                            checked: attributes.showRsBanner,
                            onChange: function(value) {
                                setAttributes({ showRsBanner: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Display Ratings'),
                            checked: attributes.displayRatings,
                            onChange: function(value) {
                                setAttributes({ displayRatings: value });
                            }
                        }),

                        el(ToggleControl, {
                            label: __('Show Dates'),
                            checked: attributes.showDates,
                            onChange: function(value) {
                                setAttributes({ showDates: value });
                            }
                        })
                    ),

                    el(PanelBody, { title: __('Animation Settings'), initialOpen: false },
                        el(ToggleControl, {
                            label: __('Auto Animate'),
                            checked: attributes.autoAnimate,
                            onChange: function(value) {
                                setAttributes({ autoAnimate: value });
                            }
                        }),

                        attributes.autoAnimate ? [
                            el(RangeControl, {
                                label: __('Animation Speed (seconds)'),
                                value: attributes.speed,
                                onChange: function(value) {
                                    setAttributes({ speed: value });
                                },
                                min: 1,
                                max: 10
                            }),

                            el(SelectControl, {
                                label: __('Animation Type'),
                                value: attributes.animationType,
                                options: [
                                    { label: 'Slide', value: 'slide' },
                                    { label: 'Fade', value: 'fade' }
                                ],
                                onChange: function(value) {
                                    setAttributes({ animationType: value });
                                }
                            })
                        ] : null,

                        el(ToggleControl, {
                            label: __('Display Arrows'),
                            checked: attributes.displayArrows,
                            onChange: function(value) {
                                setAttributes({ displayArrows: value });
                            }
                        })
                    )
                ),

                el('div', { className: 'realsatisfied-block-editor' },
                    el('div', { className: 'realsatisfied-block-icon' },
                        el('span', { className: 'dashicons dashicons-star-filled' }),
                        el('h3', null, __('RealSatisfied Reviews'))
                    ),
                    
                    el('div', { className: 'realsatisfied-block-info' },
                        el('p', null, el('strong', null, __('Mode:')), ' ' + attributes.mode),
                        el('p', null, el('strong', null, __('Vanity Key:')), ' ' + getVanityKeyValue()),
                        attributes.autoAnimate ? 
                            el('p', null, el('strong', null, __('Animation:')), ' ' + attributes.animationType + ' (' + attributes.speed + 's)') 
                            : null
                    ),

                    el('div', { className: 'realsatisfied-block-preview' },
                        el('p', null, __('RealSatisfied widget will display here on the frontend.')),
                        el('p', null, el('em', null, __('Configure settings in the sidebar panel.')))
                    )
                )
            );
        },

        save: function() {
            // Return null since this is a dynamic block
            return null;
        }
    });

    } catch (error) {
        console.error('Error registering RealSatisfied block:', error);
    }
})();