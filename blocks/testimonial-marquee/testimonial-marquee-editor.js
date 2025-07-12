/**
 * RealSatisfied Testimonial Marquee Block - Editor Script
 */

(function() {
    'use strict';

    // Debug logging
    console.log('RealSatisfied Testimonial Marquee: Editor script loading...');
    
    // Test basic WordPress objects
    console.log('wp.blocks available:', typeof wp.blocks);
    console.log('wp.blocks.registerBlockType available:', typeof wp.blocks.registerBlockType);
    console.log('wp.serverSideRender available:', typeof wp.serverSideRender);

    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var RangeControl = wp.components.RangeControl;
    var ToggleControl = wp.components.ToggleControl;
    var ColorPicker = wp.components.ColorPicker;

    console.log('RealSatisfied Testimonial Marquee: About to register block...');

    registerBlockType('realsatisfied-blocks/testimonial-marquee', {
        title: __('RealSatisfied Testimonial Marquee', 'realsatisfied-blocks'),
        description: __('Display a scrolling marquee of testimonials from across your brokerage with two rows moving in opposite directions.', 'realsatisfied-blocks'),
        icon: 'format-quote',
        category: 'common',
        keywords: [
            __('testimonials', 'realsatisfied-blocks'),
            __('marquee', 'realsatisfied-blocks'),
            __('realsatisfied', 'realsatisfied-blocks'),
            __('reviews', 'realsatisfied-blocks'),
            __('scrolling', 'realsatisfied-blocks')
        ],
        
        attributes: {
            companyId: {
                type: 'string',
                default: ''
            },
            maxTestimonials: {
                type: 'number',
                default: 100
            },
            animationSpeed: {
                type: 'number',
                default: 60
            },
            pauseOnHover: {
                type: 'boolean',
                default: false
            },
            showAgentAvatar: {
                type: 'boolean',
                default: true
            },
            showAgentName: {
                type: 'boolean',
                default: true
            },
            showCustomerLocation: {
                type: 'boolean',
                default: true
            },
            showCustomerType: {
                type: 'boolean',
                default: false
            },
            maxTestimonialLength: {
                type: 'number',
                default: 150
            },
            filterByRating: {
                type: 'boolean',
                default: true
            },
            backgroundColor: {
                type: 'string',
                default: '#ffffff'
            },
            textColor: {
                type: 'string',
                default: '#333333'
            },
            cardBackgroundColor: {
                type: 'string',
                default: '#f8f9fa'
            },
            borderColor: {
                type: 'string',
                default: '#e9ecef'
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
                        TextControl,
                        {
                            label: __('Company ID', 'realsatisfied-blocks'),
                            help: __('Enter the RealSatisfied company ID (e.g., "c21masters" or your company identifier)', 'realsatisfied-blocks'),
                            value: attributes.companyId,
                            onChange: function(value) {
                                setAttributes({ companyId: value });
                            }
                        }
                    ),
                    wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Max Testimonials', 'realsatisfied-blocks'),
                            help: __('Maximum number of testimonials to display.', 'realsatisfied-blocks'),
                            value: attributes.maxTestimonials,
                            onChange: function(value) {
                                setAttributes({ maxTestimonials: value });
                            },
                            min: 20,
                            max: 200
                        }
                    ),
                    wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Max Text Length', 'realsatisfied-blocks'),
                            help: __('Maximum characters to show in each testimonial.', 'realsatisfied-blocks'),
                            value: attributes.maxTestimonialLength,
                            onChange: function(value) {
                                setAttributes({ maxTestimonialLength: value });
                            },
                            min: 50,
                            max: 300
                        }
                    ),
                    wp.element.createElement(
                        ToggleControl,
                        {
                            label: __('Filter by Rating', 'realsatisfied-blocks'),
                            help: __('Only show positive testimonials (4+ stars).', 'realsatisfied-blocks'),
                            checked: attributes.filterByRating,
                            onChange: function(value) {
                                setAttributes({ filterByRating: value });
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Animation Settings', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement(
                        RangeControl,
                        {
                            label: __('Animation Speed (seconds)', 'realsatisfied-blocks'),
                            help: __('Time for one complete scroll cycle.', 'realsatisfied-blocks'),
                            value: attributes.animationSpeed,
                            onChange: function(value) {
                                setAttributes({ animationSpeed: value });
                            },
                            min: 10,
                            max: 120
                        }
                    ),
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
                            label: __('Show Agent Avatar', 'realsatisfied-blocks'),
                            checked: attributes.showAgentAvatar,
                            onChange: function(value) {
                                setAttributes({ showAgentAvatar: value });
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
                            label: __('Show Customer Location', 'realsatisfied-blocks'),
                            checked: attributes.showCustomerLocation,
                            onChange: function(value) {
                                setAttributes({ showCustomerLocation: value });
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
                    )
                ),
                wp.element.createElement(
                    PanelBody,
                    {
                        title: __('Colors', 'realsatisfied-blocks'),
                        initialOpen: false
                    },
                    wp.element.createElement('p', {}, __('Background Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.backgroundColor,
                            onChangeComplete: function(color) {
                                setAttributes({ backgroundColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Text Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.textColor,
                            onChangeComplete: function(color) {
                                setAttributes({ textColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Card Background Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.cardBackgroundColor,
                            onChangeComplete: function(color) {
                                setAttributes({ cardBackgroundColor: color.hex });
                            }
                        }
                    ),
                    wp.element.createElement('p', {}, __('Border Color', 'realsatisfied-blocks')),
                    wp.element.createElement(
                        ColorPicker,
                        {
                            color: attributes.borderColor,
                            onChangeComplete: function(color) {
                                setAttributes({ borderColor: color.hex });
                            }
                        }
                    )
                )
            );

            // Main editor content - Use ServerSideRender for real preview
            var previewContent;
            
            if (!attributes.companyId || attributes.companyId.trim() === '') {
                // Show setup message when no company ID
                previewContent = wp.element.createElement(
                    'div',
                    {
                        style: {
                            padding: '40px 20px',
                            textAlign: 'center',
                            backgroundColor: attributes.backgroundColor,
                            border: '2px dashed ' + attributes.borderColor,
                            borderRadius: '8px',
                            color: attributes.textColor
                        }
                    },
                    wp.element.createElement('h3', { style: { margin: '0 0 10px 0' } }, __('Testimonial Marquee', 'realsatisfied-blocks')),
                    wp.element.createElement('p', { style: { margin: 0, opacity: 0.7 } }, __('Enter a Company ID in the sidebar to display testimonials.', 'realsatisfied-blocks'))
                );
            } else {
                // Use ServerSideRender for real preview
                previewContent = wp.element.createElement(
                    ServerSideRender,
                    {
                        block: 'realsatisfied-blocks/testimonial-marquee',
                        attributes: attributes
                    }
                );
            }
            
            return wp.element.createElement(
                Fragment,
                {},
                inspectorControls,
                wp.element.createElement('div', blockProps, previewContent)
            );
        },

        save: function() {
            return null; // Dynamic block, rendered on server
        }
    });

    console.log('RealSatisfied Testimonial Marquee: Block registration completed!');

})();
