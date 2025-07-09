(function() {
    'use strict';

    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var RangeControl = wp.components.RangeControl;
    var ColorPicker = wp.components.ColorPicker;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var ServerSideRender = wp.serverSideRender;

    registerBlockType('realsatisfied-blocks/office-testimonials', {
        title: __('Office Testimonials', 'realsatisfied-blocks'),
        icon: 'format-quote',
        category: 'widgets',
        keywords: [
            __('realsatisfied', 'realsatisfied-blocks'),
            __('testimonials', 'realsatisfied-blocks'),
            __('reviews', 'realsatisfied-blocks'),
            __('office', 'realsatisfied-blocks'),
            __('customers', 'realsatisfied-blocks')
        ],
        description: __('Display customer testimonials with layout options (slider, grid, list) and filtering capabilities.', 'realsatisfied-blocks'),
        
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
                default: 2
            },
            testimonialCount: {
                type: 'number',
                default: 6
            },
            enablePagination: {
                type: 'boolean',
                default: false
            },
            itemsPerPage: {
                type: 'number',
                default: 6
            },
            showAgentPhoto: {
                type: 'boolean',
                default: true
            },
            showAgentName: {
                type: 'boolean',
                default: true
            },
            showCustomerName: {
                type: 'boolean',
                default: true
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
            filterByAgent: {
                type: 'string',
                default: ''
            },
            sortBy: {
                type: 'string',
                default: 'date'
            },
            sortOrder: {
                type: 'string',
                default: 'desc'
            },
            backgroundColor: {
                type: 'string',
                default: ''
            },
            textColor: {
                type: 'string',
                default: ''
            },
            borderColor: {
                type: 'string',
                default: ''
            },
            borderRadius: {
                type: 'string',
                default: ''
            },
            paginationBackgroundColor: {
                type: 'string',
                default: '#007cba'
            },
            paginationTextColor: {
                type: 'string',
                default: '#ffffff'
            },
            paginationHoverBackgroundColor: {
                type: 'string',
                default: '#005a87'
            },
            paginationBorderRadius: {
                type: 'string',
                default: '5px'
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
                            help: __('Enter vanity key manually.', 'realsatisfied-blocks'),
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
                        title: __('Layout & Display', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        SelectControl,
                        {
                            label: __('Layout Style', 'realsatisfied-blocks'),
                            value: attributes.layout,
                            options: [
                                { label: __('Grid', 'realsatisfied-blocks'), value: 'grid' },
                                { label: __('List', 'realsatisfied-blocks'), value: 'list' },
                                { label: __('Slider', 'realsatisfied-blocks'), value: 'slider' }
                            ],
                            onChange: function(value) {
                                setAttributes({ layout: value });
                            }
                        }
                    ),
                    attributes.layout === 'grid' && wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Columns', 'realsatisfied-blocks'),
                            value: attributes.columns,
                            onChange: function(value) {
                                setAttributes({ columns: value });
                            },
                            min: 1,
                            max: 4
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Enable Pagination', 'realsatisfied-blocks'),
                            help: __('Add pagination controls to navigate through testimonials.', 'realsatisfied-blocks'),
                            checked: attributes.enablePagination,
                            onChange: function(value) {
                                setAttributes({ enablePagination: value });
                            }
                        }
                    ),
                    !attributes.enablePagination && wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Number of Testimonials', 'realsatisfied-blocks'),
                            help: __('Set to 0 to show all testimonials.', 'realsatisfied-blocks'),
                            value: attributes.testimonialCount,
                            onChange: function(value) {
                                setAttributes({ testimonialCount: value });
                            },
                            min: 0,
                            max: 20
                        }
                    ),
                    attributes.enablePagination && wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Items per Page', 'realsatisfied-blocks'),
                            help: __('Number of testimonials to show per page.', 'realsatisfied-blocks'),
                            value: attributes.itemsPerPage,
                            onChange: function(value) {
                                setAttributes({ itemsPerPage: value });
                            },
                            min: 1,
                            max: 20
                        }
                    ),
                    wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Excerpt Length', 'realsatisfied-blocks'),
                            help: __('Set to 0 to show full testimonials.', 'realsatisfied-blocks'),
                            value: attributes.excerptLength,
                            onChange: function(value) {
                                setAttributes({ excerptLength: value });
                            },
                            min: 0,
                            max: 500,
                            step: 10
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Content Options', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Agent Photo', 'realsatisfied-blocks'),
                            checked: attributes.showAgentPhoto,
                            onChange: function(value) {
                                setAttributes({ showAgentPhoto: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Agent Name', 'realsatisfied-blocks'),
                            checked: attributes.showAgentName,
                            onChange: function(value) {
                                setAttributes({ showAgentName: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Customer Name', 'realsatisfied-blocks'),
                            checked: attributes.showCustomerName,
                            onChange: function(value) {
                                setAttributes({ showCustomerName: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Customer Type', 'realsatisfied-blocks'),
                            help: __('Show buyer/seller designation.', 'realsatisfied-blocks'),
                            checked: attributes.showCustomerType,
                            onChange: function(value) {
                                setAttributes({ showCustomerType: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Date', 'realsatisfied-blocks'),
                            checked: attributes.showDate,
                            onChange: function(value) {
                                setAttributes({ showDate: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Individual Ratings', 'realsatisfied-blocks'),
                            help: __('Show satisfaction, recommendation, and performance ratings for each testimonial.', 'realsatisfied-blocks'),
                            checked: attributes.showRatings,
                            onChange: function(value) {
                                setAttributes({ showRatings: value });
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Filtering & Sorting', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        TextControl,
                        {
                            label: __('Filter by Agent', 'realsatisfied-blocks'),
                            help: __('Show only testimonials from specific agent (partial name match).', 'realsatisfied-blocks'),
                            value: attributes.filterByAgent,
                            placeholder: __('Enter agent name...', 'realsatisfied-blocks'),
                            onChange: function(value) {
                                setAttributes({ filterByAgent: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        SelectControl,
                        {
                            label: __('Sort By', 'realsatisfied-blocks'),
                            value: attributes.sortBy,
                            options: [
                                { label: __('Date', 'realsatisfied-blocks'), value: 'date' },
                                { label: __('Rating', 'realsatisfied-blocks'), value: 'rating' },
                                { label: __('Agent Name', 'realsatisfied-blocks'), value: 'agent' }
                            ],
                            onChange: function(value) {
                                setAttributes({ sortBy: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        SelectControl,
                        {
                            label: __('Sort Order', 'realsatisfied-blocks'),
                            value: attributes.sortOrder,
                            options: [
                                { label: __('Newest First', 'realsatisfied-blocks'), value: 'desc' },
                                { label: __('Oldest First', 'realsatisfied-blocks'), value: 'asc' }
                            ],
                            onChange: function(value) {
                                setAttributes({ sortOrder: value });
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Styling', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement('p', {}, __('Background Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.backgroundColor || '#ffffff',
                            onChangeComplete: function(color) {
                                setAttributes({ backgroundColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Text Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.textColor || '#000000',
                            onChangeComplete: function(color) {
                                setAttributes({ textColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Border Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.borderColor || '#e0e0e0',
                            onChangeComplete: function(color) {
                                setAttributes({ borderColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement(
                        TextControl,
                        {
                            label: __('Border Radius', 'realsatisfied-blocks'),
                            help: __('CSS border radius (e.g., "8px", "0.5rem").', 'realsatisfied-blocks'),
                            value: attributes.borderRadius,
                            placeholder: __('8px', 'realsatisfied-blocks'),
                            onChange: function(value) {
                                setAttributes({ borderRadius: value });
                            }
                        }
                    ),
                    attributes.enablePagination && wp.element.createElement('hr', {}),
                    attributes.enablePagination && wp.element.createElement('h3', {}, __('Pagination Styling', 'realsatisfied-blocks')),
                    attributes.enablePagination && wp.element.createElement('p', {}, __('Button Background Color', 'realsatisfied-blocks')),
                    attributes.enablePagination && wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.paginationBackgroundColor || '#007cba',
                            onChangeComplete: function(color) {
                                setAttributes({ paginationBackgroundColor: color.hex });
                            }
                        }
                    ),
                    attributes.enablePagination && wp.element.createElement('p', {}, __('Button Text Color', 'realsatisfied-blocks')),
                    attributes.enablePagination && wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.paginationTextColor || '#ffffff',
                            onChangeComplete: function(color) {
                                setAttributes({ paginationTextColor: color.hex });
                            }
                        }
                    ),
                    attributes.enablePagination && wp.element.createElement('p', {}, __('Button Hover Background Color', 'realsatisfied-blocks')),
                    attributes.enablePagination && wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.paginationHoverBackgroundColor || '#005a87',
                            onChangeComplete: function(color) {
                                setAttributes({ paginationHoverBackgroundColor: color.hex });
                            }
                        }
                    ),
                    attributes.enablePagination && wp.element.createElement(
                        TextControl,
                        {
                            label: __('Button Border Radius', 'realsatisfied-blocks'),
                            help: __('CSS border radius for pagination buttons (e.g., "5px", "0.25rem").', 'realsatisfied-blocks'),
                            value: attributes.paginationBorderRadius,
                            placeholder: __('5px', 'realsatisfied-blocks'),
                            onChange: function(value) {
                                setAttributes({ paginationBorderRadius: value });
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
                            block: 'realsatisfied-blocks/office-testimonials',
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