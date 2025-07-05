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
    var SelectControl = wp.components.SelectControl;
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
            showTrustBadge: {
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

            function renderStarsSvg(rating) {
                var width = Math.min(Math.max(rating * 20, 0), 100);
                
                return wp.element.createElement(
                    'div',
                    {
                        style: {
                            position: 'relative',
                            display: 'inline-block',
                            width: '80px',
                            height: '16px'
                        }
                    },
                    // Background (empty) stars
                    wp.element.createElement(
                        'svg',
                        {
                            viewBox: '0 0 80 16',
                            style: {
                                width: '80px',
                                height: '16px',
                                position: 'absolute',
                                top: 0,
                                left: 0
                            }
                        },
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#A0A0A0',
                                d: 'M6.00682 0.92556C6.25927 0.16202 7.35844 0.162021 7.61088 0.92556L8.51285 3.65365C8.62575 3.99511 8.94954 4.2263 9.31488 4.2263H12.2337C13.0506 4.2263 13.3903 5.25364 12.7294 5.72553L10.368 7.41159C10.0724 7.62262 9.94877 7.99669 10.0617 8.33816L10.9636 11.0663C11.2161 11.8298 10.3268 12.4647 9.66592 11.9928L7.30453 10.3068C7.00897 10.0957 6.60874 10.0957 6.31317 10.3068L3.95178 11.9928C3.29087 12.4647 2.40162 11.8298 2.65407 11.0662L3.55604 8.33816C3.66894 7.99669 3.54526 7.62262 3.24969 7.41159L0.888301 5.72553C0.227393 5.25364 0.567055 4.2263 1.38398 4.2263H4.30282C4.66816 4.2263 4.99195 3.99511 5.10485 3.65365L6.00682 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#A0A0A0',
                                d: 'M22.7822 0.92556C23.0347 0.16202 24.1338 0.162021 24.3863 0.92556L25.2882 3.65365C25.4011 3.99511 25.7249 4.2263 26.0903 4.2263H29.0091C29.826 4.2263 30.1657 5.25364 29.5048 5.72553L27.1434 7.41159C26.8478 7.62262 26.7242 7.99669 26.8371 8.33816L27.739 11.0662C27.9915 11.8298 27.1022 12.4647 26.4413 11.9928L24.0799 10.3068C23.7844 10.0957 23.3841 10.0957 23.0886 10.3068L20.7272 11.9928C20.0663 12.4647 19.177 11.8298 19.4295 11.0662L20.3314 8.33816C20.4443 7.99669 20.3206 7.62262 20.0251 7.41159L17.6637 5.72553C17.0028 5.25364 17.3424 4.2263 18.1594 4.2263H21.0782C21.4436 4.2263 21.7673 3.99511 21.8802 3.65365L22.7822 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#A0A0A0',
                                d: 'M39.5575 0.92556C39.81 0.16202 40.9091 0.162021 41.1616 0.92556L42.0635 3.65365C42.1764 3.99511 42.5002 4.2263 42.8656 4.2263H45.7844C46.6014 4.2263 46.941 5.25364 46.2801 5.72553L43.9187 7.41159C43.6232 7.62262 43.4995 7.99669 43.6124 8.33816L44.5144 11.0662C44.7668 11.8298 43.8775 12.4647 43.2166 11.9928L40.8552 10.3068C40.5597 10.0957 40.1594 10.0957 39.8639 10.3068L37.5025 11.9928C36.8416 12.4647 35.9523 11.8298 36.2048 11.0662L37.1067 8.33816C37.2196 7.99669 37.0959 7.62262 36.8004 7.41159L34.439 5.72553C33.7781 5.25364 34.1177 4.2263 34.9347 4.2263H37.8535C38.2189 4.2263 38.5426 3.99511 38.6555 3.65365L39.5575 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#A0A0A0',
                                d: 'M56.3329 0.92556C56.5853 0.16202 57.6845 0.162021 57.9369 0.92556L58.8389 3.65365C58.9518 3.99511 59.2756 4.2263 59.6409 4.2263H62.5597C63.3767 4.2263 63.7163 5.25364 63.0554 5.72553L60.694 7.41159C60.3985 7.62262 60.2748 7.99669 60.3877 8.33816L61.2897 11.0662C61.5421 11.8298 60.6528 12.4647 59.9919 11.9928L57.6305 10.3068C57.335 10.0957 56.9347 10.0957 56.6392 10.3068L54.2778 11.9928C53.6169 12.4647 52.7276 11.8298 52.9801 11.0662L53.882 8.33816C53.9949 7.99669 53.8712 7.62262 53.5757 7.41159L51.2143 5.72553C50.5534 5.25364 50.893 4.2263 51.71 4.2263H54.6288C54.9942 4.2263 55.3179 3.99511 55.4308 3.65365L56.3329 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#A0A0A0',
                                d: 'M73.1103 0.92556C73.3628 0.16202 74.462 0.162021 74.7144 0.92556L75.6164 3.65365C75.7293 3.99511 76.0531 4.2263 76.4184 4.2263H79.3372C80.1542 4.2263 80.4938 5.25364 79.8329 5.72553L77.4715 7.41159C77.176 7.62262 77.0523 7.99669 77.1652 8.33816L78.0672 11.0662C78.3196 11.8298 77.4303 12.4647 76.7694 11.9928L74.408 10.3068C74.1125 10.0957 73.7123 10.0957 73.4167 10.3068L71.0553 11.9928C70.3944 12.4647 69.5051 11.8298 69.7576 11.0662L70.6596 8.33816C70.7725 7.99669 70.6488 7.62262 70.3532 7.41159L67.9918 5.72553C67.3309 5.25364 67.6706 4.2263 68.4875 4.2263H71.4063C71.7717 4.2263 72.0955 3.99511 72.2084 3.65365L73.1103 0.92556Z'
                            }
                        )
                    ),
                    // Filled (gold) stars with proper clipping
                    wp.element.createElement(
                        'svg',
                        {
                            viewBox: '0 0 80 16',
                            style: {
                                width: width + '%',
                                height: '16px',
                                position: 'absolute',
                                top: 0,
                                left: 0,
                                overflow: 'hidden'
                            }
                        },
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#F0B64F',
                                d: 'M6.00682 0.92556C6.25927 0.16202 7.35844 0.162021 7.61088 0.92556L8.51285 3.65365C8.62575 3.99511 8.94954 4.2263 9.31488 4.2263H12.2337C13.0506 4.2263 13.3903 5.25364 12.7294 5.72553L10.368 7.41159C10.0724 7.62262 9.94877 7.99669 10.0617 8.33816L10.9636 11.0663C11.2161 11.8298 10.3268 12.4647 9.66592 11.9928L7.30453 10.3068C7.00897 10.0957 6.60874 10.0957 6.31317 10.3068L3.95178 11.9928C3.29087 12.4647 2.40162 11.8298 2.65407 11.0662L3.55604 8.33816C3.66894 7.99669 3.54526 7.62262 3.24969 7.41159L0.888301 5.72553C0.227393 5.25364 0.567055 4.2263 1.38398 4.2263H4.30282C4.66816 4.2263 4.99195 3.99511 5.10485 3.65365L6.00682 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#F0B64F',
                                d: 'M22.7822 0.92556C23.0347 0.16202 24.1338 0.162021 24.3863 0.92556L25.2882 3.65365C25.4011 3.99511 25.7249 4.2263 26.0903 4.2263H29.0091C29.826 4.2263 30.1657 5.25364 29.5048 5.72553L27.1434 7.41159C26.8478 7.62262 26.7242 7.99669 26.8371 8.33816L27.739 11.0662C27.9915 11.8298 27.1022 12.4647 26.4413 11.9928L24.0799 10.3068C23.7844 10.0957 23.3841 10.0957 23.0886 10.3068L20.7272 11.9928C20.0663 12.4647 19.177 11.8298 19.4295 11.0662L20.3314 8.33816C20.4443 7.99669 20.3206 7.62262 20.0251 7.41159L17.6637 5.72553C17.0028 5.25364 17.3424 4.2263 18.1594 4.2263H21.0782C21.4436 4.2263 21.7673 3.99511 21.8802 3.65365L22.7822 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#F0B64F',
                                d: 'M39.5575 0.92556C39.81 0.16202 40.9091 0.162021 41.1616 0.92556L42.0635 3.65365C42.1764 3.99511 42.5002 4.2263 42.8656 4.2263H45.7844C46.6014 4.2263 46.941 5.25364 46.2801 5.72553L43.9187 7.41159C43.6232 7.62262 43.4995 7.99669 43.6124 8.33816L44.5144 11.0662C44.7668 11.8298 43.8775 12.4647 43.2166 11.9928L40.8552 10.3068C40.5597 10.0957 40.1594 10.0957 39.8639 10.3068L37.5025 11.9928C36.8416 12.4647 35.9523 11.8298 36.2048 11.0662L37.1067 8.33816C37.2196 7.99669 37.0959 7.62262 36.8004 7.41159L34.439 5.72553C33.7781 5.25364 34.1177 4.2263 34.9347 4.2263H37.8535C38.2189 4.2263 38.5426 3.99511 38.6555 3.65365L39.5575 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#F0B64F',
                                d: 'M56.3329 0.92556C56.5853 0.16202 57.6845 0.162021 57.9369 0.92556L58.8389 3.65365C58.9518 3.99511 59.2756 4.2263 59.6409 4.2263H62.5597C63.3767 4.2263 63.7163 5.25364 63.0554 5.72553L60.694 7.41159C60.3985 7.62262 60.2748 7.99669 60.3877 8.33816L61.2897 11.0662C61.5421 11.8298 60.6528 12.4647 59.9919 11.9928L57.6305 10.3068C57.335 10.0957 56.9347 10.0957 56.6392 10.3068L54.2778 11.9928C53.6169 12.4647 52.7276 11.8298 52.9801 11.0662L53.882 8.33816C53.9949 7.99669 53.8712 7.62262 53.5757 7.41159L51.2143 5.72553C50.5534 5.25364 50.893 4.2263 51.71 4.2263H54.6288C54.9942 4.2263 55.3179 3.99511 55.4308 3.65365L56.3329 0.92556Z'
                            }
                        ),
                        wp.element.createElement(
                            'path',
                            {
                                fill: '#F0B64F',
                                d: 'M73.1103 0.92556C73.3628 0.16202 74.462 0.162021 74.7144 0.92556L75.6164 3.65365C75.7293 3.99511 76.0531 4.2263 76.4184 4.2263H79.3372C80.1542 4.2263 80.4938 5.25364 79.8329 5.72553L77.4715 7.41159C77.176 7.62262 77.0523 7.99669 77.1652 8.33816L78.0672 11.0662C78.3196 11.8298 77.4303 12.4647 76.7694 11.9928L74.408 10.3068C74.1125 10.0957 73.7123 10.0957 73.4167 10.3068L71.0553 11.9928C70.3944 12.4647 69.5051 11.8298 69.7576 11.0662L70.6596 8.33816C70.7725 7.99669 70.6488 7.62262 70.3532 7.41159L67.9918 5.72553C67.3309 5.25364 67.6706 4.2263 68.4875 4.2263H71.4063C71.7717 4.2263 72.0955 3.99511 72.2084 3.65365L73.1103 0.92556Z'
                            }
                        )
                    )
                );
            }

            function renderRealSatisfiedTrustBadge() {
                return wp.element.createElement(
                    'a',
                    {
                        href: 'https://www.realsatisfied.com/',
                        target: '_blank',
                        rel: 'noopener noreferrer',
                        style: {
                            display: 'inline-block',
                            marginTop: '16px'
                        }
                    },
                    wp.element.createElement(
                        'img',
                        {
                            src: realsatisfiedBlocks.pluginUrl + 'assets/images/realsatisfied-trust-badge.svg',
                            alt: __('Verified with RealSatisfied', 'realsatisfied-blocks'),
                            style: {
                                height: '22px',
                                width: 'auto'
                            }
                        }
                    )
                );
            }

            function renderMiniStars(rating) {
                var stars = [];
                for (var i = 1; i <= 5; i++) {
                    var filled = rating >= i;
                    var halfFilled = !filled && rating >= (i - 0.5);
                    
                    stars.push(
                        wp.element.createElement(
                            'div',
                            {
                                key: i,
                                style: {
                                    width: '9px',
                                    height: '8.5px',
                                    position: 'relative'
                                }
                            },
                            wp.element.createElement(
                                'div',
                                {
                                    style: {
                                        width: filled ? '9px' : (halfFilled ? '4.5px' : '9px'),
                                        height: '8.5px',
                                        position: 'absolute',
                                        background: filled || halfFilled ? '#FFCB45' : '#E5E5E5'
                                    }
                                }
                            )
                        )
                    );
                }
                return stars;
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
                                padding: '40px 20px',
                                color: '#6c757d'
                            }
                        },
                        wp.element.createElement(Spinner, {}),
                        wp.element.createElement(
                            'p',
                            { style: { marginTop: '16px', fontSize: '14px' } },
                            __('Loading office data...', 'realsatisfied-blocks')
                        )
                    );
                }

                if (error) {
                    return wp.element.createElement(
                        'div',
                        {
                            style: {
                                padding: '20px',
                                backgroundColor: '#fff5f5',
                                border: '1px solid #fed7d7',
                                borderRadius: '8px',
                                color: '#742a2a'
                            }
                        },
                        wp.element.createElement(
                            'h4',
                            { style: { margin: '0 0 8px 0', fontSize: '14px', fontWeight: '600' } },
                            __('Error loading office data', 'realsatisfied-blocks')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { margin: 0, fontSize: '13px' } },
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
                                padding: '40px 20px',
                                border: '2px dashed #d1d5db',
                                borderRadius: '8px',
                                textAlign: 'center',
                                color: '#6b7280'
                            }
                        },
                        wp.element.createElement(
                            'div',
                            { 
                                style: { 
                                    fontSize: '32px', 
                                    marginBottom: '16px'
                                } 
                            },
                            '⭐'
                        ),
                        wp.element.createElement(
                            'h3',
                            { style: { margin: '0 0 8px 0', fontSize: '16px', fontWeight: '600', color: '#374151' } },
                            __('Office Overall Ratings', 'realsatisfied-blocks')
                        ),
                        wp.element.createElement(
                            'p',
                            { style: { margin: 0, fontSize: '14px', lineHeight: '1.5' } },
                            __('Configure your data source to see office ratings', 'realsatisfied-blocks')
                        )
                    );
                }

                // Render compact design to match user's desired layout
                return wp.element.createElement(
                    'div',
                    {
                        style: {
                            width: '100%',
                            padding: '10px',
                            overflow: 'hidden',
                            flexDirection: 'column',
                            justifyContent: 'flex-start',
                            alignItems: 'flex-start',
                            gap: '16px',
                            display: 'inline-flex'
                        }
                    },
                    
                    // Header Section
                    wp.element.createElement(
                        'div',
                        {
                            style: {
                                alignSelf: 'stretch',
                                height: '46px',
                                padding: '4px',
                                backdropFilter: 'blur(50px)',
                                justifyContent: 'flex-start',
                                alignItems: 'center',
                                gap: '6px',
                                display: 'inline-flex'
                            }
                        },
                        wp.element.createElement(
                            'div',
                            {
                                style: {
                                    width: '218px',
                                    flexDirection: 'column',
                                    justifyContent: 'flex-start',
                                    alignItems: 'flex-start',
                                    gap: '3px',
                                    display: 'inline-flex'
                                }
                            },
                            (attributes.showOverallRating || attributes.showOfficeName) && wp.element.createElement(
                                'div',
                                {
                                    style: {
                                        color: 'black',
                                        fontSize: '12px',
                                        fontFamily: 'Roboto',
                                        fontWeight: '500',
                                        wordWrap: 'break-word'
                                    }
                                },
                                (attributes.showOverallRating ? officeData.overall_rating + '  ' : '') +
                                (attributes.showOfficeName ? 'Office Customer Satisfaction' : '')
                            )
                        ),
                        
                        // Trust Badge
                        attributes.showTrustBadge && wp.element.createElement(
                            'a',
                            {
                                href: 'https://www.realsatisfied.com/',
                                target: '_blank',
                                rel: 'noopener noreferrer'
                            },
                            wp.element.createElement(
                                'img',
                                {
                                    src: realsatisfiedBlocks.pluginUrl + 'assets/images/realsatisfied-trust-badge.svg',
                                    alt: __('Verified with RealSatisfied', 'realsatisfied-blocks'),
                                    style: {
                                        width: '41px',
                                        height: '40px'
                                    }
                                }
                            )
                        )
                    ),

                    // Detailed Ratings Section
                    attributes.showDetailedRatings && wp.element.createElement(
                        'div',
                        {
                            style: {
                                alignSelf: 'stretch',
                                flexDirection: 'column',
                                justifyContent: 'flex-start',
                                alignItems: 'flex-start',
                                gap: '9px',
                                display: 'flex'
                            }
                        },
                        [
                            { label: __('Satisfaction', 'realsatisfied-blocks'), value: officeData.satisfaction },
                            { label: __('Recommendation', 'realsatisfied-blocks'), value: officeData.recommendation },
                            { label: __('Performance', 'realsatisfied-blocks'), value: officeData.performance }
                        ].map(function(item, index) {
                            return wp.element.createElement(
                                'div',
                                {
                                    key: index,
                                    style: {
                                        alignSelf: 'stretch',
                                        justifyContent: 'flex-start',
                                        alignItems: 'center',
                                        gap: '8px',
                                        display: 'inline-flex'
                                    }
                                },
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            width: '180px',
                                            color: 'black',
                                            fontSize: '10px',
                                            fontFamily: 'Roboto',
                                            fontWeight: '400',
                                            wordWrap: 'break-word'
                                        }
                                    },
                                    item.label
                                ),
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            width: '15px',
                                            color: 'black',
                                            fontSize: '10px',
                                            fontFamily: 'Roboto',
                                            fontWeight: '400',
                                            wordWrap: 'break-word'
                                        }
                                    },
                                    item.value
                                ),
                                wp.element.createElement(
                                    'div',
                                    {
                                        style: {
                                            justifyContent: 'flex-start',
                                            alignItems: 'flex-start',
                                            gap: '3px',
                                            display: 'flex'
                                        }
                                    },
                                    renderMiniStars(parseFloat(item.value))
                                )
                            );
                        })
                    ),

                    // Review Count
                    attributes.showReviewCount && wp.element.createElement(
                        'div',
                        {
                            style: {
                                color: 'black',
                                fontSize: '10px',
                                fontFamily: 'Roboto',
                                fontWeight: '400'
                            }
                        },
                        attributes.linkToProfile ? wp.element.createElement(
                            'a',
                            {
                                href: officeData.profile_link,
                                target: '_blank',
                                rel: 'noopener noreferrer',
                                style: {
                                    color: 'inherit',
                                    textDecoration: 'none'
                                }
                            },
                            officeData.review_count + ' ' + __('reviews', 'realsatisfied-blocks')
                        ) : (officeData.review_count + ' ' + __('reviews', 'realsatisfied-blocks'))
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
                        'div',
                        { 
                            style: { 
                                padding: '12px', 
                                backgroundColor: '#f0f6ff', 
                                border: '1px solid #bfdbfe',
                                borderRadius: '6px', 
                                marginTop: '16px' 
                            } 
                        },
                        wp.element.createElement(
                            'p',
                            { style: { margin: 0, fontSize: '13px', color: '#1e40af' } },
                            '💡 ' + __('Tip: Try "demo" as vanity key to see sample data', 'realsatisfied-blocks')
                        )
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
                            label: __('Show RealSatisfied Trust Badge', 'realsatisfied-blocks'),
                            help: __('Display the official RealSatisfied verification badge.', 'realsatisfied-blocks'),
                            checked: attributes.showTrustBadge,
                            onChange: function(value) {
                                updateAttribute('showTrustBadge', value);
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