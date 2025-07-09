/**
 * Office Agents Block - Block Editor Script (ES5 Version)
 */

(function() {
    'use strict';

    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var RangeControl = wp.components.RangeControl;
    var SelectControl = wp.components.SelectControl;
    var TextControl = wp.components.TextControl;
    var ServerSideRender = wp.serverSideRender;

    registerBlockType('realsatisfied-blocks/office-agents', {
        title: __('RealSatisfied Office Agents', 'realsatisfied-blocks'),
        description: __('Display agents for an office with customizable layout and styling options.', 'realsatisfied-blocks'),
        icon: 'groups',
        category: 'widgets',
        keywords: [
            __('agents', 'realsatisfied-blocks'),
            __('office', 'realsatisfied-blocks'),
            __('real estate', 'realsatisfied-blocks'),
            __('realsatisfied', 'realsatisfied-blocks')
        ],

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
            layout: {
                type: 'string',
                default: 'grid'
            },
            columns: {
                type: 'number',
                default: 3
            },
            agentCount: {
                type: 'number',
                default: 9
            },
            enablePagination: {
                type: 'boolean',
                default: false
            },
            itemsPerPage: {
                type: 'number',
                default: 9
            },
            showAgentPhoto: {
                type: 'boolean',
                default: true
            },
            showAgentName: {
                type: 'boolean',
                default: true
            },
            showAgentTitle: {
                type: 'boolean',
                default: true
            },
            showAgentEmail: {
                type: 'boolean',
                default: false
            },
            showAgentPhone: {
                type: 'boolean',
                default: false
            },
            showAgentRating: {
                type: 'boolean',
                default: true
            },
            showReviewCount: {
                type: 'boolean',
                default: true
            },
            sortBy: {
                type: 'string',
                default: 'name'
            },
            sortOrder: {
                type: 'string',
                default: 'asc'
            },
            cardBackgroundColor: {
                type: 'string',
                default: '#ffffff'
            },
            cardBorderColor: {
                type: 'string',
                default: '#e0e0e0'
            },
            cardBorderRadius: {
                type: 'string',
                default: '8px'
            },
            buttonColor: {
                type: 'string',
                default: '#007cba'
            },
            buttonTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            buttonHoverColor: {
                type: 'string',
                default: '#005a87'
            }
        },

        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return Fragment({},
                InspectorControls({},
                    PanelBody({
                        title: __('Data Source', 'realsatisfied-blocks'),
                        initialOpen: true
                    },
                        ToggleControl({
                            label: __('Use Custom Field', 'realsatisfied-blocks'),
                            checked: attributes.useCustomField,
                            onChange: function(value) {
                                setAttributes({ useCustomField: value });
                            }
                        }),
                        
                        attributes.useCustomField && TextControl({
                            label: __('Custom Field Name', 'realsatisfied-blocks'),
                            value: attributes.customFieldName,
                            onChange: function(value) {
                                setAttributes({ customFieldName: value });
                            }
                        }),
                        
                        !attributes.useCustomField && TextControl({
                            label: __('Manual Vanity Key', 'realsatisfied-blocks'),
                            value: attributes.manualVanityKey,
                            onChange: function(value) {
                                setAttributes({ manualVanityKey: value });
                            }
                        })
                    ),
                    
                    PanelBody({
                        title: __('Layout Settings', 'realsatisfied-blocks'),
                        initialOpen: true
                    },
                        SelectControl({
                            label: __('Layout', 'realsatisfied-blocks'),
                            value: attributes.layout,
                            options: [
                                { label: __('Grid', 'realsatisfied-blocks'), value: 'grid' },
                                { label: __('List', 'realsatisfied-blocks'), value: 'list' },
                                { label: __('Slider', 'realsatisfied-blocks'), value: 'slider' }
                            ],
                            onChange: function(value) {
                                setAttributes({ layout: value });
                            }
                        }),
                        
                        attributes.layout === 'grid' && RangeControl({
                            label: __('Columns', 'realsatisfied-blocks'),
                            value: attributes.columns,
                            onChange: function(value) {
                                setAttributes({ columns: value });
                            },
                            min: 1,
                            max: 6
                        }),
                        
                        ToggleControl({
                            label: __('Enable Pagination', 'realsatisfied-blocks'),
                            checked: attributes.enablePagination,
                            onChange: function(value) {
                                setAttributes({ enablePagination: value });
                            }
                        }),
                        
                        attributes.enablePagination ? RangeControl({
                            label: __('Items Per Page', 'realsatisfied-blocks'),
                            value: attributes.itemsPerPage,
                            onChange: function(value) {
                                setAttributes({ itemsPerPage: value });
                            },
                            min: 1,
                            max: 50
                        }) : RangeControl({
                            label: __('Number of Agents', 'realsatisfied-blocks'),
                            value: attributes.agentCount,
                            onChange: function(value) {
                                setAttributes({ agentCount: value });
                            },
                            min: 1,
                            max: 50
                        })
                    ),
                    
                    PanelBody({
                        title: __('Display Options', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                        ToggleControl({
                            label: __('Show Agent Photo', 'realsatisfied-blocks'),
                            checked: attributes.showAgentPhoto,
                            onChange: function(value) {
                                setAttributes({ showAgentPhoto: value });
                            }
                        }),
                        
                        ToggleControl({
                            label: __('Show Agent Name', 'realsatisfied-blocks'),
                            checked: attributes.showAgentName,
                            onChange: function(value) {
                                setAttributes({ showAgentName: value });
                            }
                        }),
                        
                        ToggleControl({
                            label: __('Show Agent Title', 'realsatisfied-blocks'),
                            checked: attributes.showAgentTitle,
                            onChange: function(value) {
                                setAttributes({ showAgentTitle: value });
                            }
                        }),
                        
                        ToggleControl({
                            label: __('Show Email', 'realsatisfied-blocks'),
                            checked: attributes.showAgentEmail,
                            onChange: function(value) {
                                setAttributes({ showAgentEmail: value });
                            }
                        }),
                        
                        ToggleControl({
                            label: __('Show Phone', 'realsatisfied-blocks'),
                            checked: attributes.showAgentPhone,
                            onChange: function(value) {
                                setAttributes({ showAgentPhone: value });
                            }
                        }),
                        
                        ToggleControl({
                            label: __('Show Rating', 'realsatisfied-blocks'),
                            checked: attributes.showAgentRating,
                            onChange: function(value) {
                                setAttributes({ showAgentRating: value });
                            }
                        }),
                        
                        ToggleControl({
                            label: __('Show Review Count', 'realsatisfied-blocks'),
                            checked: attributes.showReviewCount,
                            onChange: function(value) {
                                setAttributes({ showReviewCount: value });
                            }
                        })
                    ),
                    
                    PanelBody({
                        title: __('Sorting', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                        SelectControl({
                            label: __('Sort By', 'realsatisfied-blocks'),
                            value: attributes.sortBy,
                            options: [
                                { label: __('Name', 'realsatisfied-blocks'), value: 'name' },
                                { label: __('Rating', 'realsatisfied-blocks'), value: 'rating' },
                                { label: __('Review Count', 'realsatisfied-blocks'), value: 'reviews' }
                            ],
                            onChange: function(value) {
                                setAttributes({ sortBy: value });
                            }
                        }),
                        
                        SelectControl({
                            label: __('Sort Order', 'realsatisfied-blocks'),
                            value: attributes.sortOrder,
                            options: [
                                { label: __('Ascending', 'realsatisfied-blocks'), value: 'asc' },
                                { label: __('Descending', 'realsatisfied-blocks'), value: 'desc' }
                            ],
                            onChange: function(value) {
                                setAttributes({ sortOrder: value });
                            }
                        })
                    )
                ),
                
                ServerSideRender({
                    block: 'realsatisfied-blocks/office-agents',
                    attributes: attributes
                })
            );
        },

        save: function() {
            // Server-side rendering
            return null;
        }
    });

})();
