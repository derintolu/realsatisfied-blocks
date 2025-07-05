(function() {
    'use strict';

    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var PanelRow = wp.components.PanelRow;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var ServerSideRender = wp.serverSideRender;
    var useBlockProps = wp.blockEditor.useBlockProps;

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
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;
            var blockProps = useBlockProps();

            function updateAttribute(attributeName, value) {
                var newAttributes = {};
                newAttributes[attributeName] = value;
                setAttributes(newAttributes);
            }

            var inspectorControls = wp.element.createElement(
                InspectorControls,
                {},
                // Data Source Panel
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
                                updateAttribute('useCustomField', value);
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
                                updateAttribute('customFieldName', value);
                            }
                        }
                    ),
                    !attributes.useCustomField && wp.element.createElement(
                        TextControl,
                        {
                            label: __('Manual Vanity Key', 'realsatisfied-blocks'),
                            help: __('Enter vanity key manually (e.g., "CENTURY21-Masters-11").', 'realsatisfied-blocks'),
                            value: attributes.manualVanityKey,
                            onChange: function(value) {
                                updateAttribute('manualVanityKey', value);
                            }
                        }
                    )
                ),
                // Display Options Panel
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Display Options', 'realsatisfied-blocks'),
                        initialOpen: true
                    },
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Office Name', 'realsatisfied-blocks'),
                            checked: attributes.showOfficeName,
                            onChange: function(value) {
                                updateAttribute('showOfficeName', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Office Logo', 'realsatisfied-blocks'),
                            checked: attributes.showPhoto,
                            onChange: function(value) {
                                updateAttribute('showPhoto', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Overall Rating', 'realsatisfied-blocks'),
                            checked: attributes.showOverallRating,
                            onChange: function(value) {
                                updateAttribute('showOverallRating', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Star Rating', 'realsatisfied-blocks'),
                            checked: attributes.showStars,
                            onChange: function(value) {
                                updateAttribute('showStars', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Review Count', 'realsatisfied-blocks'),
                            checked: attributes.showReviewCount,
                            onChange: function(value) {
                                updateAttribute('showReviewCount', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Detailed Ratings', 'realsatisfied-blocks'),
                            help: __('Display individual satisfaction, recommendation, and performance ratings.', 'realsatisfied-blocks'),
                            checked: attributes.showDetailedRatings,
                            onChange: function(value) {
                                updateAttribute('showDetailedRatings', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Link to Profile', 'realsatisfied-blocks'),
                            help: __('Make review count clickable to link to RealSatisfied profile.', 'realsatisfied-blocks'),
                            checked: attributes.linkToProfile,
                            onChange: function(value) {
                                updateAttribute('linkToProfile', value);
                            }
                        }
                    )
                )
            );

            var serverSideRender = wp.element.createElement(
                ServerSideRender,
                {
                    block: 'realsatisfied-blocks/office-ratings',
                    attributes: attributes
                }
            );

            return wp.element.createElement(
                Fragment,
                {},
                inspectorControls,
                wp.element.createElement(
                    'div',
                    blockProps,
                    serverSideRender
                )
            );
        },

        save: function() {
            // Server-side rendering
            return null;
        }
    });
})(); 