(function() {
    'use strict';

    const { registerBlockType } = wp.blocks;
    const { Fragment } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { 
        PanelBody, 
        SelectControl, 
        RangeControl, 
        ToggleControl,
        ServerSideRender 
    } = wp.components;
    const { __ } = wp.i18n;

    registerBlockType('realsatisfied-blocks/agent-testimonials', {
        title: __('Agent Testimonials', 'realsatisfied-blocks'),
        description: __('Display testimonials for a specific agent using agent vanity key from ACF field.', 'realsatisfied-blocks'),
        icon: 'format-quote',
        category: 'widgets',
        keywords: [__('testimonials'), __('agent'), __('reviews'), __('realsatisfied')],
        
        attributes: {
            agentId: {
                type: 'number',
                default: null
            },
            contextAware: {
                type: 'boolean',
                default: true
            },
            layout: {
                type: 'string',
                default: 'grid'
            },
            columns: {
                type: 'number',
                default: 2
            },
            testimonialCount: {
                type: 'number',
                default: 6
            },
            showDate: {
                type: 'boolean',
                default: true
            },
            showRatings: {
                type: 'boolean',
                default: false
            },
            showCustomerType: {
                type: 'boolean',
                default: true
            },
            excerptLength: {
                type: 'number',
                default: 150
            },
            sortBy: {
                type: 'string',
                default: 'date'
            },
            sortOrder: {
                type: 'string',
                default: 'desc'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;

            return (
                Fragment({},
                    InspectorControls({},
                        PanelBody({
                            title: __('Agent Settings', 'realsatisfied-blocks'),
                            initialOpen: true
                        },
                            ToggleControl({
                                label: __('Context Aware', 'realsatisfied-blocks'),
                                help: __('Automatically detect agent from current page context', 'realsatisfied-blocks'),
                                checked: attributes.contextAware,
                                onChange: function(value) {
                                    setAttributes({ contextAware: value });
                                }
                            })
                        ),
                        
                        PanelBody({
                            title: __('Display Settings', 'realsatisfied-blocks'),
                            initialOpen: true
                        },
                            SelectControl({
                                label: __('Layout', 'realsatisfied-blocks'),
                                value: attributes.layout,
                                options: [
                                    { label: __('Grid', 'realsatisfied-blocks'), value: 'grid' },
                                    { label: __('List', 'realsatisfied-blocks'), value: 'list' }
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
                                max: 4
                            }),
                            
                            RangeControl({
                                label: __('Number of Testimonials', 'realsatisfied-blocks'),
                                value: attributes.testimonialCount,
                                onChange: function(value) {
                                    setAttributes({ testimonialCount: value });
                                },
                                min: 1,
                                max: 20
                            }),
                            
                            RangeControl({
                                label: __('Excerpt Length', 'realsatisfied-blocks'),
                                value: attributes.excerptLength,
                                onChange: function(value) {
                                    setAttributes({ excerptLength: value });
                                },
                                min: 50,
                                max: 500
                            })
                        ),
                        
                        PanelBody({
                            title: __('Content Options', 'realsatisfied-blocks'),
                            initialOpen: false
                        },
                            ToggleControl({
                                label: __('Show Date', 'realsatisfied-blocks'),
                                checked: attributes.showDate,
                                onChange: function(value) {
                                    setAttributes({ showDate: value });
                                }
                            }),
                            
                            ToggleControl({
                                label: __('Show Ratings', 'realsatisfied-blocks'),
                                checked: attributes.showRatings,
                                onChange: function(value) {
                                    setAttributes({ showRatings: value });
                                }
                            }),
                            
                            ToggleControl({
                                label: __('Show Customer Type', 'realsatisfied-blocks'),
                                checked: attributes.showCustomerType,
                                onChange: function(value) {
                                    setAttributes({ showCustomerType: value });
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
                                    { label: __('Date', 'realsatisfied-blocks'), value: 'date' }
                                ],
                                onChange: function(value) {
                                    setAttributes({ sortBy: value });
                                }
                            }),
                            
                            SelectControl({
                                label: __('Sort Order', 'realsatisfied-blocks'),
                                value: attributes.sortOrder,
                                options: [
                                    { label: __('Newest First', 'realsatisfied-blocks'), value: 'desc' },
                                    { label: __('Oldest First', 'realsatisfied-blocks'), value: 'asc' }
                                ],
                                onChange: function(value) {
                                    setAttributes({ sortOrder: value });
                                }
                            })
                        )
                    ),
                    
                    ServerSideRender({
                        block: 'realsatisfied-blocks/agent-testimonials',
                        attributes: attributes
                    })
                )
            );
        },

        save: function() {
            return null; // Server-side rendered block
        }
    });
})();
