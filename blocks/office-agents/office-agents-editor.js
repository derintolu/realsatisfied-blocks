/**
 * Office Agents Block - Block Editor Script
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { 
    useBlockProps, 
    InspectorControls,
    PanelColorSettings
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    RangeControl,
    SelectControl,
    TextControl,
    __experimentalNumberControl as NumberControl
} from '@wordpress/components';

registerBlockType('realsatisfied-blocks/office-agents', {
    title: __('RealSatisfied Office Agents', 'realsatisfied-blocks'),
    description: __('Display agents for an office with customizable layout and styling options.', 'realsatisfied-blocks'),
    icon: 'groups',
    category: 'realsatisfied',
    keywords: [
        __('agents', 'realsatisfied-blocks'),
        __('office', 'realsatisfied-blocks'),
        __('real estate', 'realsatisfied-blocks'),
        __('realsatisfied', 'realsatisfied-blocks')
    ],
    supports: {
        html: false,
        align: ['left', 'center', 'right', 'wide', 'full'],
        alignWide: true,
        anchor: true,
        customClassName: true,
        interactivity: true,
        color: {
            gradients: true,
            link: true,
            text: true,
            background: true
        },
        typography: {
            fontSize: true,
            lineHeight: true,
            fontFamily: true,
            fontWeight: true,
            fontStyle: true,
            textTransform: true,
            textDecoration: true,
            letterSpacing: true
        },
        spacing: {
            margin: true,
            padding: true,
            blockGap: true
        },
        border: {
            color: true,
            radius: true,
            style: true,
            width: true
        },
        dimensions: {
            minHeight: true
        }
    },
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
        const { attributes, setAttributes } = props;
        const {
            useCustomField,
            customFieldName,
            manualVanityKey,
            layout,
            columns,
            agentCount,
            enablePagination,
            itemsPerPage,
            showAgentPhoto,
            showAgentName,
            showAgentTitle,
            showAgentEmail,
            showAgentPhone,
            showAgentRating,
            showReviewCount,
            sortBy,
            sortOrder,
            cardBackgroundColor,
            cardBorderColor,
            cardBorderRadius,
            buttonColor,
            buttonTextColor,
            buttonHoverColor
        } = attributes;

        const blockProps = useBlockProps({
            className: `realsatisfied-office-agents layout-${layout}`
        });

        return (
            <>
                <InspectorControls>
                    <PanelBody title={__('Data Source', 'realsatisfied-blocks')} initialOpen={true}>
                        <ToggleControl
                            label={__('Use Custom Field', 'realsatisfied-blocks')}
                            checked={useCustomField}
                            onChange={(value) => setAttributes({ useCustomField: value })}
                        />
                        {useCustomField && (
                            <TextControl
                                label={__('Custom Field Name', 'realsatisfied-blocks')}
                                value={customFieldName}
                                onChange={(value) => setAttributes({ customFieldName: value })}
                                help={__('The name of the ACF field containing the vanity key.', 'realsatisfied-blocks')}
                            />
                        )}
                        {!useCustomField && (
                            <TextControl
                                label={__('Manual Vanity Key', 'realsatisfied-blocks')}
                                value={manualVanityKey}
                                onChange={(value) => setAttributes({ manualVanityKey: value })}
                                help={__('Enter the office vanity key manually.', 'realsatisfied-blocks')}
                            />
                        )}
                    </PanelBody>

                    <PanelBody title={__('Layout Settings', 'realsatisfied-blocks')} initialOpen={true}>
                        <SelectControl
                            label={__('Layout', 'realsatisfied-blocks')}
                            value={layout}
                            options={[
                                { label: __('Grid', 'realsatisfied-blocks'), value: 'grid' },
                                { label: __('List', 'realsatisfied-blocks'), value: 'list' },
                                { label: __('Slider', 'realsatisfied-blocks'), value: 'slider' }
                            ]}
                            onChange={(value) => setAttributes({ layout: value })}
                        />
                        {layout === 'grid' && (
                            <RangeControl
                                label={__('Columns', 'realsatisfied-blocks')}
                                value={columns}
                                onChange={(value) => setAttributes({ columns: value })}
                                min={1}
                                max={6}
                            />
                        )}
                        <ToggleControl
                            label={__('Enable Pagination', 'realsatisfied-blocks')}
                            checked={enablePagination}
                            onChange={(value) => setAttributes({ enablePagination: value })}
                        />
                        {enablePagination ? (
                            <NumberControl
                                label={__('Items Per Page', 'realsatisfied-blocks')}
                                value={itemsPerPage}
                                onChange={(value) => setAttributes({ itemsPerPage: parseInt(value) || 9 })}
                                min={1}
                                max={50}
                            />
                        ) : (
                            <NumberControl
                                label={__('Agent Count', 'realsatisfied-blocks')}
                                value={agentCount}
                                onChange={(value) => setAttributes({ agentCount: parseInt(value) || 9 })}
                                min={1}
                                max={50}
                                help={__('Maximum number of agents to display.', 'realsatisfied-blocks')}
                            />
                        )}
                    </PanelBody>

                    <PanelBody title={__('Display Options', 'realsatisfied-blocks')} initialOpen={false}>
                        <ToggleControl
                            label={__('Show Agent Photo', 'realsatisfied-blocks')}
                            checked={showAgentPhoto}
                            onChange={(value) => setAttributes({ showAgentPhoto: value })}
                        />
                        <ToggleControl
                            label={__('Show Agent Name', 'realsatisfied-blocks')}
                            checked={showAgentName}
                            onChange={(value) => setAttributes({ showAgentName: value })}
                        />
                        <ToggleControl
                            label={__('Show Agent Title', 'realsatisfied-blocks')}
                            checked={showAgentTitle}
                            onChange={(value) => setAttributes({ showAgentTitle: value })}
                        />
                        <ToggleControl
                            label={__('Show Agent Email', 'realsatisfied-blocks')}
                            checked={showAgentEmail}
                            onChange={(value) => setAttributes({ showAgentEmail: value })}
                        />
                        <ToggleControl
                            label={__('Show Agent Phone', 'realsatisfied-blocks')}
                            checked={showAgentPhone}
                            onChange={(value) => setAttributes({ showAgentPhone: value })}
                        />
                        <ToggleControl
                            label={__('Show Agent Rating', 'realsatisfied-blocks')}
                            checked={showAgentRating}
                            onChange={(value) => setAttributes({ showAgentRating: value })}
                        />
                        <ToggleControl
                            label={__('Show Review Count', 'realsatisfied-blocks')}
                            checked={showReviewCount}
                            onChange={(value) => setAttributes({ showReviewCount: value })}
                        />
                    </PanelBody>

                    <PanelBody title={__('Sorting', 'realsatisfied-blocks')} initialOpen={false}>
                        <SelectControl
                            label={__('Sort By', 'realsatisfied-blocks')}
                            value={sortBy}
                            options={[
                                { label: __('Name', 'realsatisfied-blocks'), value: 'name' },
                                { label: __('Rating', 'realsatisfied-blocks'), value: 'rating' },
                                { label: __('Review Count', 'realsatisfied-blocks'), value: 'reviews' }
                            ]}
                            onChange={(value) => setAttributes({ sortBy: value })}
                        />
                        <SelectControl
                            label={__('Sort Order', 'realsatisfied-blocks')}
                            value={sortOrder}
                            options={[
                                { label: __('Ascending', 'realsatisfied-blocks'), value: 'asc' },
                                { label: __('Descending', 'realsatisfied-blocks'), value: 'desc' }
                            ]}
                            onChange={(value) => setAttributes({ sortOrder: value })}
                        />
                    </PanelBody>

                    <PanelColorSettings
                        title={__('Card Colors', 'realsatisfied-blocks')}
                        initialOpen={false}
                        colorSettings={[
                            {
                                value: cardBackgroundColor,
                                onChange: (value) => setAttributes({ cardBackgroundColor: value }),
                                label: __('Card Background Color', 'realsatisfied-blocks')
                            },
                            {
                                value: cardBorderColor,
                                onChange: (value) => setAttributes({ cardBorderColor: value }),
                                label: __('Card Border Color', 'realsatisfied-blocks')
                            }
                        ]}
                    />

                    <PanelColorSettings
                        title={__('Button Colors', 'realsatisfied-blocks')}
                        initialOpen={false}
                        colorSettings={[
                            {
                                value: buttonColor,
                                onChange: (value) => setAttributes({ buttonColor: value }),
                                label: __('Button Color', 'realsatisfied-blocks')
                            },
                            {
                                value: buttonTextColor,
                                onChange: (value) => setAttributes({ buttonTextColor: value }),
                                label: __('Button Text Color', 'realsatisfied-blocks')
                            },
                            {
                                value: buttonHoverColor,
                                onChange: (value) => setAttributes({ buttonHoverColor: value }),
                                label: __('Button Hover Color', 'realsatisfied-blocks')
                            }
                        ]}
                    />

                    <PanelBody title={__('Card Border Radius', 'realsatisfied-blocks')} initialOpen={false}>
                        <TextControl
                            label={__('Border Radius', 'realsatisfied-blocks')}
                            value={cardBorderRadius}
                            onChange={(value) => setAttributes({ cardBorderRadius: value })}
                            help={__('e.g., 8px, 1rem, 0', 'realsatisfied-blocks')}
                        />
                    </PanelBody>
                </InspectorControls>

                <div {...blockProps}>
                    <div className="realsatisfied-editor-preview">
                        <div className="editor-preview-header">
                            <h3>{__('RealSatisfied Office Agents', 'realsatisfied-blocks')}</h3>
                            <div className="preview-info">
                                <span>{__('Layout:', 'realsatisfied-blocks')} {layout}</span>
                                {layout === 'grid' && <span>{__('Columns:', 'realsatisfied-blocks')} {columns}</span>}
                                <span>{__('Sort by:', 'realsatisfied-blocks')} {sortBy}</span>
                            </div>
                        </div>
                        
                        <div className={`agents-preview layout-${layout}`}>
                            {layout === 'grid' && (
                                <div className={`agents-grid columns-${columns}`} style={{
                                    display: 'grid',
                                    gridTemplateColumns: `repeat(${columns}, 1fr)`,
                                    gap: '1rem'
                                }}>
                                    {[...Array(Math.min(agentCount, 6))].map((_, index) => (
                                        <div key={index} className="agent-card-preview" style={{
                                            backgroundColor: cardBackgroundColor,
                                            borderColor: cardBorderColor,
                                            borderRadius: cardBorderRadius,
                                            border: '1px solid',
                                            padding: '1rem',
                                            textAlign: 'center'
                                        }}>
                                            {showAgentPhoto && (
                                                <div className="agent-photo-preview" style={{
                                                    width: '80px',
                                                    height: '80px',
                                                    backgroundColor: '#ddd',
                                                    borderRadius: '50%',
                                                    margin: '0 auto 0.5rem'
                                                }}></div>
                                            )}
                                            {showAgentName && (
                                                <h4 style={{ margin: '0.5rem 0', fontSize: '1rem' }}>
                                                    {__('Agent Name', 'realsatisfied-blocks')}
                                                </h4>
                                            )}
                                            {showAgentTitle && (
                                                <p style={{ margin: '0.25rem 0', fontSize: '0.9rem', color: '#666' }}>
                                                    {__('Agent Title', 'realsatisfied-blocks')}
                                                </p>
                                            )}
                                            {showAgentRating && (
                                                <div style={{ margin: '0.5rem 0' }}>
                                                    <span>★★★★★</span> <span>4.8</span>
                                                </div>
                                            )}
                                            {showReviewCount && (
                                                <p style={{ margin: '0.25rem 0', fontSize: '0.8rem' }}>
                                                    {__('25 reviews', 'realsatisfied-blocks')}
                                                </p>
                                            )}
                                            <button style={{
                                                backgroundColor: buttonColor,
                                                color: buttonTextColor,
                                                border: 'none',
                                                padding: '0.5rem 1rem',
                                                borderRadius: '4px',
                                                marginTop: '0.5rem'
                                            }}>
                                                {__('View Profile', 'realsatisfied-blocks')}
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                            
                            {layout === 'list' && (
                                <div className="agents-list">
                                    {[...Array(Math.min(agentCount, 3))].map((_, index) => (
                                        <div key={index} className="agent-item-preview" style={{
                                            backgroundColor: cardBackgroundColor,
                                            borderColor: cardBorderColor,
                                            borderRadius: cardBorderRadius,
                                            border: '1px solid',
                                            padding: '1rem',
                                            marginBottom: '1rem',
                                            display: 'flex',
                                            gap: '1rem'
                                        }}>
                                            {showAgentPhoto && (
                                                <div style={{
                                                    width: '60px',
                                                    height: '60px',
                                                    backgroundColor: '#ddd',
                                                    borderRadius: '50%',
                                                    flexShrink: 0
                                                }}></div>
                                            )}
                                            <div style={{ flex: 1 }}>
                                                {showAgentName && (
                                                    <h4 style={{ margin: '0 0 0.5rem', fontSize: '1rem' }}>
                                                        {__('Agent Name', 'realsatisfied-blocks')}
                                                    </h4>
                                                )}
                                                {showAgentTitle && (
                                                    <p style={{ margin: '0 0 0.5rem', fontSize: '0.9rem', color: '#666' }}>
                                                        {__('Agent Title', 'realsatisfied-blocks')}
                                                    </p>
                                                )}
                                                {showAgentRating && (
                                                    <div style={{ margin: '0.5rem 0' }}>
                                                        <span>★★★★★</span> <span>4.8</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                            
                            {layout === 'slider' && (
                                <div className="agents-slider-preview" style={{
                                    backgroundColor: cardBackgroundColor,
                                    borderColor: cardBorderColor,
                                    borderRadius: cardBorderRadius,
                                    border: '1px solid',
                                    padding: '2rem',
                                    textAlign: 'center'
                                }}>
                                    <p style={{ margin: 0, color: '#666' }}>
                                        {__('Slider preview - will show agents in a carousel format', 'realsatisfied-blocks')}
                                    </p>
                                </div>
                            )}
                        </div>
                        
                        {enablePagination && (
                            <div className="pagination-preview" style={{
                                marginTop: '1rem',
                                textAlign: 'center'
                            }}>
                                <button style={{
                                    backgroundColor: buttonColor,
                                    color: buttonTextColor,
                                    border: 'none',
                                    padding: '0.5rem 1rem',
                                    margin: '0 0.5rem',
                                    borderRadius: '4px'
                                }}>
                                    {__('← Previous', 'realsatisfied-blocks')}
                                </button>
                                <span style={{ margin: '0 1rem' }}>
                                    {__('Page 1 of 3', 'realsatisfied-blocks')}
                                </span>
                                <button style={{
                                    backgroundColor: buttonColor,
                                    color: buttonTextColor,
                                    border: 'none',
                                    padding: '0.5rem 1rem',
                                    margin: '0 0.5rem',
                                    borderRadius: '4px'
                                }}>
                                    {__('Next →', 'realsatisfied-blocks')}
                                </button>
                            </div>
                        )}
                    </div>
                </div>
            </>
        );
    },

    save: function() {
        // Server-side rendering
        return null;
    }
});
