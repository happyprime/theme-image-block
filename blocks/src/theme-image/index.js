/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	BlockControls,
	__experimentalLinkControl as LinkControl,
} from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
	Button,
	ToolbarButton,
	Popover,
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
		imageSize,
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
	const [isEditingLink, setIsEditingLink] = useState(false);

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

	// Build size options from the current image's variations
	const sizeOptions = [
		{ value: 'original', label: __('Original', 'happyprime') },
	];

	if (currentImage && currentImage.variations) {
		Object.keys(currentImage.variations).forEach((sizeKey) => {
			// Convert size key to a readable label (e.g., 'large' -> 'Large')
			const label = sizeKey.charAt(0).toUpperCase() + sizeKey.slice(1);
			sizeOptions.push({ value: sizeKey, label });
		});
	}

	// Get the image path for preview based on selected size
	let imagePath = currentImage?.value || '';
	if (
		currentImage &&
		imageSize !== 'original' &&
		currentImage.variations &&
		currentImage.variations[imageSize]
	) {
		imagePath = currentImage.variations[imageSize].path;
	}

	// Fetch SVG content when inline SVG is enabled
	useEffect(() => {
		if (inlineSVG && imagePath && imagePath.toLowerCase().endsWith('.svg')) {
			const svgUrl = `${happyprimeData.themeUrl}/${imagePath}`;
			fetch(svgUrl)
				.then((response) => response.text())
				.then((svg) => {
					// Remove XML declaration to match server-side processing
					const cleanedSvg = svg.replace(/^<\?xml\s+.*?\?>\s*/s, '');
					setSvgContent(cleanedSvg);
				})
				.catch((error) => {
					console.error('Failed to fetch SVG:', error);
					setSvgContent(null);
				});
		} else {
			setSvgContent(null);
		}
	}, [inlineSVG, imagePath]);

	const blockProps = useBlockProps({
		className: inlineSVG ? 'has-inline-svg' : '',
		style: {
			// Only apply width/height to wrapper for non-inline SVGs
			// For inline SVGs, dimensions are applied directly to the SVG element
			...(!inlineSVG && width && { width }),
			...(!inlineSVG && height && { height }),
		},
	});

	const imageUrl =
		imagePath && happyprimeData?.themeUrl
			? `${happyprimeData.themeUrl}/${imagePath}`
			: '';
	const isSVG =
		imagePath && imagePath.toLowerCase().endsWith('.svg');

	// Process inline SVG if needed
	let processedSvg = null;
	if (inlineSVG && svgContent) {
		// Build styles array - use defaults if not specified for editor preview
		const styles = [];
		if (width) {
			styles.push(`width: ${width}`);
		} else {
			// Default width for editor preview when not specified
			styles.push('width: 100%');
		}
		if (height) {
			styles.push(`height: ${height}`);
		}

		const styleAttr = styles.join('; ');

		// Insert or merge style attribute into the SVG tag
		const svgMatch = svgContent.match(/<svg([^>]*)>/);
		if (svgMatch) {
			const existingAttrs = svgMatch[1];
			const styleMatch = existingAttrs.match(/style="([^"]*)"/);

			if (styleMatch) {
				// Merge with existing style
				const existingStyle = styleMatch[1];
				processedSvg = svgContent.replace(
					/<svg([^>]*)>/,
					(match) => match.replace(
						/style="[^"]*"/,
						`style="${existingStyle}; ${styleAttr}"`
					)
				);
			} else {
				// Add new style attribute
				processedSvg = svgContent.replace(
					/<svg([^>]*)>/,
					`<svg$1 style="${styleAttr}">`
				);
			}
		}
	}

	// Build content based on image type and link settings
	// Structure matches server-side: <div><a?><svg|img></a?></div>
	let content;
	if (!imageUrl) {
		content = (
			<div className="theme-image-placeholder">
				{__('Select a theme image from the sidebar', 'happyprime')}
			</div>
		);
	} else if (inlineSVG && processedSvg) {
		// For inline SVG, apply dangerouslySetInnerHTML to link or wrapper
		// to match server-side structure without extra div wrapper
		if (linkUrl) {
			content = (
				<a
					href={linkUrl}
					target={linkTarget}
					rel={linkRel}
					dangerouslySetInnerHTML={{ __html: processedSvg }}
				/>
			);
		} else {
			// Will be applied to wrapper div via blockProps below
			content = null;
		}
	} else {
		const img = (
			<img
				src={imageUrl}
				alt={
					currentImage?.alt || __('Theme image preview', 'happyprime')
				}
			/>
		);
		content = linkUrl ? (
			<a href={linkUrl} target={linkTarget} rel={linkRel}>
				{img}
			</a>
		) : (
			img
		);
	}

	// For inline SVG without link, apply HTML directly to wrapper
	const wrapperProps =
		inlineSVG && processedSvg && !linkUrl
			? { ...blockProps, dangerouslySetInnerHTML: { __html: processedSvg } }
			: blockProps;

	return (
		<>
			{imageUrl && (
				<BlockControls group="block">
					<ToolbarButton
						icon="admin-links"
						label={__('Link', 'happyprime')}
						onClick={() => setIsEditingLink(true)}
						isActive={!!linkUrl}
					/>
					{linkUrl && (
						<ToolbarButton
							icon="editor-unlink"
							label={__('Unlink', 'happyprime')}
							onClick={() => {
								setAttributes({
									linkUrl: '',
									linkTarget: '',
									linkRel: '',
								});
							}}
						/>
					)}
				</BlockControls>
			)}

			{isEditingLink && (
				<Popover
					position="bottom center"
					onClose={() => setIsEditingLink(false)}
					anchor={document.querySelector(
						'.wp-block-happyprime-theme-image'
					)}
				>
					<LinkControl
						value={{
							url: linkUrl,
							opensInNewTab: linkTarget === '_blank',
							nofollow: linkRel?.includes('nofollow'),
						}}
						onChange={(newLink) => {
							// Build rel attribute from settings
							const relParts = [];

							if (newLink?.opensInNewTab) {
								relParts.push('noopener', 'noreferrer');
							}

							if (newLink?.nofollow) {
								relParts.push('nofollow');
							}

							setAttributes({
								linkUrl: newLink?.url || '',
								linkTarget: newLink?.opensInNewTab
									? '_blank'
									: '',
								linkRel: relParts.join(' '),
							});
						}}
						onRemove={() => {
							setAttributes({
								linkUrl: '',
								linkTarget: '',
								linkRel: '',
							});
							setIsEditingLink(false);
						}}
						settings={[
							{
								id: 'opensInNewTab',
								title: __('Open in new tab', 'happyprime'),
							},
							{
								id: 'nofollow',
								title: __('Mark as nofollow', 'happyprime'),
							},
						]}
					/>
				</Popover>
			)}

			<InspectorControls>
				<PanelBody
					title={__('Settings', 'happyprime')}
					initialOpen={true}
				>
					<SelectControl
						label={__('Theme Image', 'happyprime')}
						value={themeImage}
						options={themeImages}
						onChange={(value) => {
							setAttributes({
								themeImage: value,
								imageSize: 'original',
							});
						}}
						help={__(
							'Select an image from the theme directory.',
							'happyprime'
						)}
					/>

					{currentImage &&
						currentImage.variations &&
						Object.keys(currentImage.variations).length > 0 && (
							<SelectControl
								label={__('Size', 'happyprime')}
								value={imageSize}
								options={sizeOptions}
								onChange={(value) =>
									setAttributes({ imageSize: value })
								}
								help={__(
									'Select the image size variation.',
									'happyprime'
								)}
							/>
						)}

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

			<div {...wrapperProps}>{content}</div>
		</>
	);
}

// Register the block.
registerBlockType(metadata.name, {
	edit: Edit,
});
