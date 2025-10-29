/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	BlockControls,
	__experimentalImageURLInputUI as ImageURLInputUI,
} from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	Button,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import metadata from './block.json';

/**
 * Editor component for the Theme Image block.
 *
 * @param {object} root0 The block attributes.
 * @param {object} root0.attributes The block attributes.
 * @param {Function} root0.setAttributes The function to set the block attributes.
 * @returns {JSX.Element} The block edit component.
 */
function Edit({ attributes, setAttributes }) {
	const {
		themeImage,
		inlineSVG,
		linkUrl,
		linkTarget,
		linkRel,
		width,
		height,
	} = attributes;
	const [isCustomWidth, setIsCustomWidth] = useState(false);
	const [isCustomHeight, setIsCustomHeight] = useState(false);
	const [svgContent, setSvgContent] = useState(null);

	// Check if width/height contains custom CSS functions
	const hasCustomWidthCSS =
		width &&
		(width.includes('clamp') ||
			width.includes('calc') ||
			width.includes('min') ||
			width.includes('max'));
	const hasCustomHeightCSS =
		height &&
		(height.includes('clamp') ||
			height.includes('calc') ||
			height.includes('min') ||
			height.includes('max'));

	// Get registered theme images from localized data
	const registeredImages = happyprimeData?.images || [];

	// Build the options array for the SelectControl
	const themeImages = [
		{ value: '', label: __('Select an image', 'happyprime') },
		...registeredImages.map((image) => ({
			value: image.slug,
			label: image.label,
		})),
	];

	// Find the currently selected image data
	const currentImage = registeredImages.find(
		(img) => img.slug === themeImage
	);

	// Fetch SVG content when inline SVG is enabled
	useEffect(() => {
		if (
			inlineSVG &&
			currentImage &&
			currentImage.value.toLowerCase().endsWith('.svg')
		) {
			const imageUrl = `${happyprimeData.themeUrl}/${currentImage.value}`;
			fetch(imageUrl)
				.then((response) => response.text())
				.then((svg) => setSvgContent(svg))
				.catch(() => setSvgContent(null));
		} else {
			setSvgContent(null);
		}
	}, [inlineSVG, currentImage]);

	const blockProps = useBlockProps({
		className: inlineSVG ? 'has-inline-svg' : '',
		style: {
			...(width && { width }),
			...(height && { height }),
		},
	});

	// Get the image URL for preview
	const imageUrl =
		currentImage && currentImage.value
			? `${happyprimeData.themeUrl}/${currentImage.value}`
			: '';
	const isSVG =
		currentImage &&
		currentImage.value &&
		currentImage.value.toLowerCase().endsWith('.svg');

	// Render inline SVG or regular image
	let imagePreview;
	if (!imageUrl) {
		imagePreview = (
			<div className="theme-image-placeholder">
				{__('Select a theme image from the sidebar', 'happyprime')}
			</div>
		);
	} else if (inlineSVG && svgContent) {
		imagePreview = <div dangerouslySetInnerHTML={{ __html: svgContent }} />;
	} else {
		imagePreview = (
			<img
				src={imageUrl}
				alt={
					currentImage?.alt ||
					__('Theme image preview', 'happyprime')
				}
			/>
		);
	}

	const content = linkUrl ? (
		<a href={linkUrl} target={linkTarget} rel={linkRel}>
			{imagePreview}
		</a>
	) : (
		imagePreview
	);

	return (
		<>
			<BlockControls group="block">
				<ImageURLInputUI
					url={linkUrl}
					onChangeUrl={(value) => setAttributes({ linkUrl: value })}
					linkDestination={linkUrl ? 'custom' : undefined}
					mediaUrl={imageUrl}
					mediaLink={imageUrl}
					linkTarget={linkTarget}
					linkClass={linkRel}
					rel={linkRel}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={__('Settings', 'happyprime')}
					initialOpen={true}
				>
					<SelectControl
						label={__('Theme Image', 'happyprime')}
						value={themeImage}
						options={themeImages}
						onChange={(value) =>
							setAttributes({ themeImage: value })
						}
						help={__(
							'Select an image from the theme directory.',
							'happyprime'
						)}
					/>

					{isCustomWidth || hasCustomWidthCSS ? (
						<>
							<TextControl
								label={__('Width', 'happyprime')}
								value={width}
								onChange={(value) =>
									setAttributes({ width: value })
								}
								help={__(
									'Enter any valid CSS value',
									'happyprime'
								)}
								placeholder="clamp(10rem, 50vw, 30rem)"
							/>
							<Button
								variant="link"
								onClick={() => {
									setIsCustomWidth(false);
									setAttributes({ width: '' });
								}}
								style={{
									display: 'block',
									marginTop: '-8px',
									marginBottom: '16px',
								}}
							>
								{__('Use preset units', 'happyprime')}
							</Button>
						</>
					) : (
						<>
							<UnitControl
								label={__('Width', 'happyprime')}
								labelPosition="top"
								__unstableInputWidth="80px"
								value={width}
								onChange={(value) =>
									setAttributes({ width: value })
								}
								units={[
									{ value: 'px', label: 'px', default: 0 },
									{ value: '%', label: '%', default: 100 },
									{ value: 'em', label: 'em', default: 0 },
									{ value: 'rem', label: 'rem', default: 0 },
									{ value: 'vw', label: 'vw', default: 0 },
									{ value: 'vh', label: 'vh', default: 0 },
									{ value: 'lh', label: 'lh', default: 1 },
								]}
							/>
							<Button
								variant="link"
								onClick={() => setIsCustomWidth(true)}
								style={{
									display: 'block',
									marginTop: '-8px',
									marginBottom: '16px',
								}}
							>
								{__('Use custom CSS', 'happyprime')}
							</Button>
						</>
					)}

					{isCustomHeight || hasCustomHeightCSS ? (
						<>
							<TextControl
								label={__('Height', 'happyprime')}
								value={height}
								onChange={(value) =>
									setAttributes({ height: value })
								}
								help={__(
									'Enter any valid CSS value',
									'happyprime'
								)}
								placeholder="clamp(10rem, 50vh, 30rem)"
							/>
							<Button
								variant="link"
								onClick={() => {
									setIsCustomHeight(false);
									setAttributes({ height: '' });
								}}
								style={{
									display: 'block',
									marginTop: '-8px',
									marginBottom: '16px',
								}}
							>
								{__('Use preset units', 'happyprime')}
							</Button>
						</>
					) : (
						<>
							<UnitControl
								label={__('Height', 'happyprime')}
								labelPosition="top"
								__unstableInputWidth="80px"
								value={height}
								onChange={(value) =>
									setAttributes({ height: value })
								}
								units={[
									{ value: 'px', label: 'px', default: 0 },
									{ value: '%', label: '%', default: 100 },
									{ value: 'em', label: 'em', default: 0 },
									{ value: 'rem', label: 'rem', default: 0 },
									{ value: 'vw', label: 'vw', default: 0 },
									{ value: 'vh', label: 'vh', default: 0 },
									{ value: 'lh', label: 'lh', default: 1 },
								]}
							/>
							<Button
								variant="link"
								onClick={() => setIsCustomHeight(true)}
								style={{
									display: 'block',
									marginTop: '-8px',
									marginBottom: '16px',
								}}
							>
								{__('Use custom CSS', 'happyprime')}
							</Button>
						</>
					)}

					{isSVG && (
						<ToggleControl
							label={__('Inline SVG', 'happyprime')}
							checked={inlineSVG}
							onChange={(value) =>
								setAttributes({ inlineSVG: value })
							}
							help={__(
								'Render SVG code inline for better styling control.',
								'happyprime'
							)}
						/>
					)}
				</PanelBody>
			</InspectorControls>

			<div {...blockProps}>{content}</div>
		</>
	);
}

// Register the block.
registerBlockType(metadata.name, {
	edit: Edit,
});
