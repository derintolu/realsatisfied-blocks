(function() {
    'use strict';

    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var ColorPicker = wp.components.ColorPicker;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var ServerSideRender = wp.serverSideRender;

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
            showTrustBadge: {
                type: 'boolean',
                default: false
            },
            linkToProfile: {
                type: 'boolean',
                default: true
            },
            starColorFilled: {
                type: 'string',
                default: '#FFD700'
            },
            starColorEmpty: {
                type: 'string',
                default: '#CCCCCC'
            },
            textColor: {
                type: 'string',
                default: ''
            },
            backgroundColor: {
                type: 'string',
                default: ''
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();

            // Inspector Controls
            var inspectorControls = wp.element.createElement(
                InspectorControls,
                {},
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Data Source', 'realsatisfied-blocks'),
                        initialOpen: true
                    },
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Use Custom Field', 'realsatisfied-blocks'),
                            help: __('Get vanity key from custom field or enter manually.', 'realsatisfied-blocks'),
                            checked: attributes.useCustomField,
                            onChange: function(value) {
                                setAttributes({ useCustomField: value });
                            }
                        }
                    ),
                    attributes.useCustomField && wp.element.createElement(
                        TextControl,
                        {
                            label: __('Custom Field Name', 'realsatisfied-blocks'),
                            help: __('Name of custom field containing vanity key.', 'realsatisfied-blocks'),
                            value: attributes.customFieldName,
                            onChange: function(value) {
                                setAttributes({ customFieldName: value });
                            }
                        }
                    ),
                    !attributes.useCustomField && wp.element.createElement(
                        TextControl,
                        {
                            label: __('Manual Vanity Key', 'realsatisfied-blocks'),
                            help: __('Enter vanity key manually (e.g., "CENTURY21-Masters-11").', 'realsatisfied-blocks'),
                            value: attributes.manualVanityKey,
                            placeholder: __('Enter vanity key...', 'realsatisfied-blocks'),
                            onChange: function(value) {
                                setAttributes({ manualVanityKey: value });
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Display Options', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Office Name', 'realsatisfied-blocks'),
                            checked: attributes.showOfficeName,
                            onChange: function(value) {
                                setAttributes({ showOfficeName: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Overall Rating', 'realsatisfied-blocks'),
                            checked: attributes.showOverallRating,
                            onChange: function(value) {
                                setAttributes({ showOverallRating: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Stars', 'realsatisfied-blocks'),
                            checked: attributes.showStars,
                            onChange: function(value) {
                                setAttributes({ showStars: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Review Count', 'realsatisfied-blocks'),
                            checked: attributes.showReviewCount,
                            onChange: function(value) {
                                setAttributes({ showReviewCount: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Detailed Ratings', 'realsatisfied-blocks'),
                            help: __('Show individual satisfaction, recommendation, and performance ratings.', 'realsatisfied-blocks'),
                            checked: attributes.showDetailedRatings,
                            onChange: function(value) {
                                setAttributes({ showDetailedRatings: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Trust Badge', 'realsatisfied-blocks'),
                            help: __('Display the RealSatisfied trust badge.', 'realsatisfied-blocks'),
                            checked: attributes.showTrustBadge,
                            onChange: function(value) {
                                setAttributes({ showTrustBadge: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Link to Profile', 'realsatisfied-blocks'),
                            help: __('Make review count link to RealSatisfied profile.', 'realsatisfied-blocks'),
                            checked: attributes.linkToProfile,
                            onChange: function(value) {
                                setAttributes({ linkToProfile: value });
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Color Settings', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement('p', {}, __('Star Color (Filled)', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.starColorFilled,
                            onChangeComplete: function(color) {
                                setAttributes({ starColorFilled: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Star Color (Empty)', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.starColorEmpty,
                            onChangeComplete: function(color) {
                                setAttributes({ starColorEmpty: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Text Color (Optional)', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.textColor || '#000000',
                            onChangeComplete: function(color) {
                                setAttributes({ textColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Background Color (Optional)', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.backgroundColor || '#ffffff',
                            onChangeComplete: function(color) {
                                setAttributes({ backgroundColor: color.hex });
                            }
                        }
                    )
                )
            );

            // Main editor content
            return wp.element.createElement(
                Fragment,
                {},
                inspectorControls,
                wp.element.createElement(
                    'div',
                    blockProps,
                    wp.element.createElement(
                        ServerSideRender,
                        {
                            block: 'realsatisfied-blocks/office-ratings',
                            attributes: attributes,
                            key: JSON.stringify(attributes)
                        }
                    )
                )
            );
        },

        save: function() {
            return null; // Dynamic block, rendered on server
        }
    });

})(); 