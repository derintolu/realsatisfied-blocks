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

    registerBlockType('realsatisfied-blocks/agent-testimonials', {
        title: __('Agent Testimonials', 'realsatisfied-blocks'),
        icon: 'format-quote',
        category: 'realsatisfied',
        keywords: [
            __('realsatisfied', 'realsatisfied-blocks'),
            __('testimonials', 'realsatisfied-blocks'),
            __('reviews', 'realsatisfied-blocks'),
            __('agent', 'realsatisfied-blocks'),
            __('customers', 'realsatisfied-blocks')
        ],
        description: __('Display customer testimonials for a specific agent with layout options (slider, grid, list) and filtering capabilities.', 'realsatisfied-blocks'),
        
        attributes: {
            useCustomField: {
                type: 'boolean',
                default: true
            },
            customFieldName: {
                type: 'string',
                default: 'realsatified-agent-vanity'
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
            showSatisfactionRating: {
                type: 'boolean',
                default: true
            },
            showRecommendationRating: {
                type: 'boolean',
                default: true
            },
            showPerformanceRating: {
                type: 'boolean',
                default: true
            },
            showRatingValues: {
                type: 'boolean',
                default: true
            },
            showQuotationMarks: {
                type: 'boolean',
                default: true
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
                            help: __('Get agent vanity key from ACF custom field.', 'realsatisfied-blocks'),
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
                            help: __('The ACF field name containing the agent vanity key.', 'realsatisfied-blocks'),
                            value: attributes.customFieldName,
                            onChange: function(value) {
                                setAttributes({ customFieldName: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        TextControl,
                        {
                            label: __('Manual Vanity Key', 'realsatisfied-blocks'),
                            help: __('Override the custom field with a manual agent vanity key.', 'realsatisfied-blocks'),
                            value: attributes.manualVanityKey,
                            onChange: function(value) {
                                setAttributes({ manualVanityKey: value });
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Layout Options', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        SelectControl,
                        {
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
                            help: __('Enable pagination for large sets of testimonials.', 'realsatisfied-blocks'),
                            checked: attributes.enablePagination,
                            onChange: function(value) {
                                setAttributes({ enablePagination: value });
                            }
                        }
                    ),
                    attributes.enablePagination && wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Items Per Page', 'realsatisfied-blocks'),
                            value: attributes.itemsPerPage,
                            onChange: function(value) {
                                setAttributes({ itemsPerPage: value });
                            },
                            min: 1,
                            max: 20
                        }
                    ),
                    !attributes.enablePagination && wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Testimonial Count', 'realsatisfied-blocks'),
                            help: __('Maximum number of testimonials to display (0 for all).', 'realsatisfied-blocks'),
                            value: attributes.testimonialCount,
                            onChange: function(value) {
                                setAttributes({ testimonialCount: value });
                            },
                            min: 0,
                            max: 50
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
                            label: __('Show Ratings', 'realsatisfied-blocks'),
                            checked: attributes.showRatings,
                            onChange: function(value) {
                                setAttributes({ showRatings: value });
                            }
                        }
                    ),
                    attributes.showRatings && wp.element.createElement(
                        'div',
                        { style: { marginLeft: '20px', paddingLeft: '15px', borderLeft: '3px solid #ccc' } },
                        wp.element.createElement(
                            ToggleControl,
                            {
                                label: __('Show Satisfaction Rating', 'realsatisfied-blocks'),
                                checked: attributes.showSatisfactionRating,
                                onChange: function(value) {
                                    setAttributes({ showSatisfactionRating: value });
                                }
                            }
                        ),
                        wp.element.createElement(
                            ToggleControl,
                            {
                                label: __('Show Recommendation Rating', 'realsatisfied-blocks'),
                                checked: attributes.showRecommendationRating,
                                onChange: function(value) {
                                    setAttributes({ showRecommendationRating: value });
                                }
                            }
                        ),
                        wp.element.createElement(
                            ToggleControl,
                            {
                                label: __('Show Performance Rating', 'realsatisfied-blocks'),
                                checked: attributes.showPerformanceRating,
                                onChange: function(value) {
                                    setAttributes({ showPerformanceRating: value });
                                }
                            }
                        ),
                        wp.element.createElement(
                            ToggleControl,
                            {
                                label: __('Show Rating Percentages', 'realsatisfied-blocks'),
                                help: __('Display percentage values next to star ratings.', 'realsatisfied-blocks'),
                                checked: attributes.showRatingValues,
                                onChange: function(value) {
                                    setAttributes({ showRatingValues: value });
                                }
                            }
                        )
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Quotation Marks', 'realsatisfied-blocks'),
                            help: __('Display quotation marks around testimonial text.', 'realsatisfied-blocks'),
                            checked: attributes.showQuotationMarks,
                            onChange: function(value) {
                                setAttributes({ showQuotationMarks: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Show Customer Type', 'realsatisfied-blocks'),
                            checked: attributes.showCustomerType,
                            onChange: function(value) {
                                setAttributes({ showCustomerType: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Excerpt Length', 'realsatisfied-blocks'),
                            help: __('Maximum characters to display for testimonial content (0 for full text).', 'realsatisfied-blocks'),
                            value: attributes.excerptLength,
                            onChange: function(value) {
                                setAttributes({ excerptLength: value });
                            },
                            min: 0,
                            max: 500
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Sorting Options', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        SelectControl,
                        {
                            label: __('Sort By', 'realsatisfied-blocks'),
                            value: attributes.sortBy,
                            options: [
                                { label: __('Date', 'realsatisfied-blocks'), value: 'date' },
                                { label: __('Rating', 'realsatisfied-blocks'), value: 'rating' }
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
                                { label: __('Descending (Newest First)', 'realsatisfied-blocks'), value: 'desc' },
                                { label: __('Ascending (Oldest First)', 'realsatisfied-blocks'), value: 'asc' }
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
                        title: __('Style Options', 'realsatisfied-blocks'),
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
                            color: attributes.textColor || '#333333',
                            onChangeComplete: function(color) {
                                setAttributes({ textColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Border Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.borderColor || '#e5e5e5',
                            onChangeComplete: function(color) {
                                setAttributes({ borderColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement(
                        TextControl,
                        {
                            label: __('Border Radius', 'realsatisfied-blocks'),
                            help: __('CSS border radius (e.g., "5px", "0.25rem").', 'realsatisfied-blocks'),
                            value: attributes.borderRadius,
                            placeholder: __('5px', 'realsatisfied-blocks'),
                            onChange: function(value) {
                                setAttributes({ borderRadius: value });
                            }
                        }
                    )
                ),
                attributes.enablePagination && wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Pagination Style', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement('p', {}, __('Button Background Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.paginationBackgroundColor || '#007cba',
                            onChangeComplete: function(color) {
                                setAttributes({ paginationBackgroundColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Button Text Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.paginationTextColor || '#ffffff',
                            onChangeComplete: function(color) {
                                setAttributes({ paginationTextColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Button Hover Background Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.paginationHoverBackgroundColor || '#005a87',
                            onChangeComplete: function(color) {
                                setAttributes({ paginationHoverBackgroundColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement(
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
                            block: 'realsatisfied-blocks/agent-testimonials',
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
