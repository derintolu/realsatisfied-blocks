(function() {
    'use strict';

    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var Fragment = wp.element.Fragment;
    var useState = wp.element.useState;
    var useEffect = wp.element.useEffect;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var TextControl = wp.components.TextControl;
    var Spinner = wp.components.Spinner;
    var Notice = wp.components.Notice;
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

            // State for data loading
            var loadingState = useState(false);
            var isLoading = loadingState[0];
            var setIsLoading = loadingState[1];

            var dataState = useState(null);
            var officeData = dataState[0];
            var setOfficeData = dataState[1];

            var errorState = useState(null);
            var error = errorState[0];
            var setError = errorState[1];

            // Load data when vanity key changes
            useEffect(function() {
                var vanityKey = getVanityKey();
                if (vanityKey) {
                    loadOfficeData(vanityKey);
                } else {
                    setOfficeData(null);
                    setError(null);
                }
            }, [attributes.useCustomField, attributes.customFieldName, attributes.manualVanityKey]);

            function getVanityKey() {
                if (attributes.useCustomField) {
                    // In a real implementation, this would get from custom field
                    // For now, we'll use a sample key for preview
                    return 'CENTURY21-Masters-11';
                } else {
                    return attributes.manualVanityKey;
                }
            }

            function loadOfficeData(vanityKey) {
                setIsLoading(true);
                setError(null);
                
                // Simulate API call - in real implementation, this would call WordPress REST API
                setTimeout(function() {
                    if (vanityKey === 'CENTURY21-Masters-11' || vanityKey === 'demo') {
                        setOfficeData({
                            office: 'CENTURY 21 Masters',
                            logo: 'https://via.placeholder.com/120x40/0066cc/ffffff?text=C21',
                            overall_rating: 4.9,
                            satisfaction: 4.9,
                            recommendation: 5.0,
                            performance: 5.0,
                            review_count: 465,
                            profile_link: 'https://www.realsatisfied.com/CENTURY21-Masters-11'
                        });
                    } else {
                        setError(__('Could not load office data. Please check your vanity key.', 'realsatisfied-blocks'));
                    }
                    setIsLoading(false);
                }, 1000);
            }

            function updateAttribute(attributeName, value) {
                var newAttributes = {};
                newAttributes[attributeName] = value;
                setAttributes(newAttributes);
            }

            function renderStars(rating) {
                var stars = [];
                var fullStars = Math.floor(rating);
                var hasPartial = rating % 1 !== 0;
                
                for (var i = 0; i < fullStars; i++) {
                    stars.push('★');
                }
                
                if (hasPartial) {
                    stars.push('☆');
                }
                
                while (stars.length < 5) {
                    stars.push('☆');
                }
                
                return stars.join('');
            }

            function renderPreview() {
                if (isLoading) {
                    return wp.element.createElement(
                        'div',
                        {
                            style: {
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                padding: '2rem',
                                border: '2px dashed #8b949e',
                                borderRadius: '8px',
                                backgroundColor: '#f6f8fa'
                            }
                        },
                        wp.element.createElement(Spinner, {}),
                        wp.element.createElement(
                            'p',
                            { style: { marginTop: '1rem', color: '#8b949e' } },
                            __('Loading office data...', 'realsatisfied-blocks')
                        )
                    );
                }

                if (error) {
                    return wp.element.createElement(
                        'div',
                        {
                            style: {
                                padding: '1rem',
                                border: '1px solid #dc3545',
                                borderRadius: '4px',
                                backgroundColor: '#f8d7da',
                                color: '#721c24'
                            }
                        },
                        wp.element.createElement(
                            'p',
                            { style: { margin: 0, fontWeight: 'bold' } },
                            __('Error loading office data', 'realsatisfied-blocks')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { margin: '0.5rem 0 0 0', fontSize: '0.9rem' } },
                            error
                        )
                    );
                }

                if (!officeData) {
                    return wp.element.createElement(
                        'div',
                        {
                            style: {
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                padding: '2rem',
                                border: '2px dashed #8b949e',
                                borderRadius: '8px',
                                backgroundColor: '#f6f8fa',
                                textAlign: 'center'
                            }
                        },
                        wp.element.createElement(
                            'div',
                            { style: { fontSize: '48px', marginBottom: '1rem', color: '#8b949e' } },
                            '⭐'
                        ),
                        wp.element.createElement(
                            'h3',
                            { style: { margin: '0 0 0.5rem 0', color: '#333' } },
                            __('Office Overall Ratings', 'realsatisfied-blocks')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { margin: 0, color: '#666' } },
                            __('Configure your data source to see office ratings', 'realsatisfied-blocks')
                        )
                    );
                }

                // Render actual data preview
                var containerStyle = {
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    gap: '1rem',
                    padding: '1.5rem',
                    border: '1px solid #e0e0e0',
                    borderRadius: '8px',
                    backgroundColor: '#ffffff',
                    boxShadow: '0 2px 4px rgba(0,0,0,0.1)',
                    maxWidth: '400px',
                    margin: '0 auto'
                };

                return wp.element.createElement(
                    'div',
                    { style: containerStyle },
                    
                    // Office Logo
                    attributes.showPhoto && wp.element.createElement(
                        'div',
                        { style: { marginBottom: '0.5rem' } },
                        wp.element.createElement(
                            'img',
                            {
                                src: officeData.logo,
                                alt: officeData.office + ' Logo',
                                style: {
                                    height: '40px',
                                    maxWidth: '120px',
                                    objectFit: 'contain'
                                }
                            }
                        )
                    ),

                    // Office Name
                    attributes.showOfficeName && wp.element.createElement(
                        'h3',
                        {
                            style: {
                                margin: '0 0 1rem 0',
                                fontSize: '1.25rem',
                                fontWeight: 'bold',
                                textAlign: 'center'
                            }
                        },
                        officeData.office
                    ),

                    // Overall Rating
                    attributes.showOverallRating && wp.element.createElement(
                        'div',
                        {
                            style: {
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                gap: '0.5rem'
                            }
                        },
                        wp.element.createElement(
                            'span',
                            {
                                style: {
                                    fontSize: '2rem',
                                    fontWeight: 'bold',
                                    color: '#333'
                                }
                            },
                            officeData.overall_rating
                        ),
                        attributes.showStars && wp.element.createElement(
                            'div',
                            {
                                style: {
                                    fontSize: '1.25rem',
                                    color: '#ffc107'
                                }
                            },
                            renderStars(officeData.overall_rating)
                        )
                    ),

                    // Review Count
                    attributes.showReviewCount && wp.element.createElement(
                        'div',
                        {
                            style: {
                                fontSize: '0.9rem',
                                opacity: 0.8
                            }
                        },
                        attributes.linkToProfile ? wp.element.createElement(
                            'a',
                            {
                                href: officeData.profile_link,
                                target: '_blank',
                                style: {
                                    color: 'inherit',
                                    textDecoration: 'none'
                                }
                            },
                            officeData.review_count + ' reviews'
                        ) : wp.element.createElement(
                            'span',
                            {},
                            officeData.review_count + ' reviews'
                        )
                    ),

                    // Detailed Ratings
                    attributes.showDetailedRatings && wp.element.createElement(
                        'div',
                        {
                            style: {
                                width: '100%',
                                display: 'flex',
                                flexDirection: 'column',
                                gap: '0.5rem',
                                paddingTop: '1rem',
                                borderTop: '1px solid #e0e0e0'
                            }
                        },
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center'
                                }
                            },
                            wp.element.createElement(
                                'span',
                                { style: { fontWeight: '500' } },
                                __('Satisfaction', 'realsatisfied-blocks')
                            ),
                            wp.element.createElement(
                                'span',
                                { style: { fontWeight: 'bold' } },
                                officeData.satisfaction
                            ),
                            wp.element.createElement(
                                'span',
                                { style: { fontSize: '0.8rem', color: '#ffc107' } },
                                renderStars(officeData.satisfaction)
                            )
                        ),
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center'
                                }
                            },
                            wp.element.createElement(
                                'span',
                                { style: { fontWeight: '500' } },
                                __('Recommendation', 'realsatisfied-blocks')
                            ),
                            wp.element.createElement(
                                'span',
                                { style: { fontWeight: 'bold' } },
                                officeData.recommendation
                            ),
                            wp.element.createElement(
                                'span',
                                { style: { fontSize: '0.8rem', color: '#ffc107' } },
                                renderStars(officeData.recommendation)
                            )
                        ),
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center'
                                }
                            },
                            wp.element.createElement(
                                'span',
                                { style: { fontWeight: '500' } },
                                __('Performance', 'realsatisfied-blocks')
                            ),
                            wp.element.createElement(
                                'span',
                                { style: { fontWeight: 'bold' } },
                                officeData.performance
                            ),
                            wp.element.createElement(
                                'span',
                                { style: { fontSize: '0.8rem', color: '#ffc107' } },
                                renderStars(officeData.performance)
                            )
                        )
                    )
                );
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
                            placeholder: __('Enter vanity key...', 'realsatisfied-blocks'),
                            onChange: function(value) {
                                updateAttribute('manualVanityKey', value);
                            }
                        }
                    ),
                    wp.element.createElement(
                        'p',
                        { style: { fontSize: '0.9rem', color: '#666', marginTop: '1rem' } },
                        __('💡 Tip: Try "demo" as vanity key to see sample data', 'realsatisfied-blocks')
                    )
                ),
                // Display Options Panel
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

            return wp.element.createElement(
                Fragment,
                {},
                inspectorControls,
                wp.element.createElement(
                    'div',
                    blockProps,
                    renderPreview()
                )
            );
        },

        save: function() {
            // Server-side rendering
            return null;
        }
    });
})(); 