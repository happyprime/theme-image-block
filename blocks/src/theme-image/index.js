/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	useBlockProps,
	BlockControls,
	BlockIcon,
	__experimentalLinkControl as LinkControl,
	RichText,
} from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	ToolbarButton,
	Popover,
	Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { image } from '@wordpress/icons';

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
		imageStyle,
		inlineSVG,
		linkUrl,
		linkTarget,
		linkRel,
		caption,
	} = attributes;
	const [svgContent, setSvgContent] = useState(null);
	const [isEditingLink, setIsEditingLink] = useState(false);

	// Get registered theme images and styles from localized data.
	const registeredImages = happyprimeData?.images || [];
	const registeredStyles = happyprimeData?.styles || [];

	// Build the options array for the SelectControl.
	const themeImages = [
		{ value: '', label: __('Select an image', 'theme-image-block') },
		...registeredImages.map((image) => ({
			value: image.slug,
			label: image.label,
		})),
	];

	// Find the currently selected image data.
	const currentImage = registeredImages.find(
		(img) => img.slug === themeImage
	);

	// Build size options from the current image's variations.
	const sizeOptions = [
		{ value: 'original', label: __('Original', 'theme-image-block') },
	];

	if (currentImage && currentImage.variations) {
		Object.keys(currentImage.variations).forEach((sizeKey) => {
			const variation = currentImage.variations[sizeKey];
			sizeOptions.push({
				value: sizeKey,
				label: variation.name || sizeKey,
			});
		});
	}

	// Get the image path for preview based on selected size.
	let imagePath = currentImage?.value || '';
	if (
		currentImage &&
		imageSize !== 'original' &&
		currentImage.variations &&
		currentImage.variations[imageSize]
	) {
		imagePath = currentImage.variations[imageSize].path;
	}

	// Fetch SVG content when inline SVG is enabled.
	useEffect(() => {
		if (
			inlineSVG &&
			imagePath &&
			imagePath.toLowerCase().endsWith('.svg')
		) {
			const svgUrl = `${happyprimeData.themeUrl}/${imagePath}`;
			fetch(svgUrl)
				.then((response) => response.text())
				.then((svg) => {
					setSvgContent(svg);
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
	});

	const imageUrl =
		imagePath && happyprimeData?.themeUrl
			? `${happyprimeData.themeUrl}/${imagePath}`
			: '';
	const isSVG = imagePath && imagePath.toLowerCase().endsWith('.svg');

	// Get the selected style's dimensions.
	const currentStyle = registeredStyles.find(
		(style) => style.slug === imageStyle
	);
	const width = currentStyle?.width || '';
	const height = currentStyle?.height || '';

	// Process inline SVG if needed.
	let processedSvg = null;
	if (inlineSVG && svgContent) {
		// Build styles array for editor preview.
		const styles = [];
		if (width) {
			styles.push(`width: ${width}`);
		} else if (!width) {
			// Default width for editor preview when not specified.
			styles.push('width: 100%');
		}
		if (height) {
			styles.push(`height: ${height}`);
		}

		const styleAttr = styles.join('; ');

		// Insert or merge style attribute into the SVG tag.
		const svgMatch = svgContent.match(/<svg([^>]*)>/);
		if (svgMatch) {
			const existingAttrs = svgMatch[1];
			const styleMatch = existingAttrs.match(/style="([^"]*)"/);

			if (styleMatch) {
				// Merge with existing style.
				const existingStyle = styleMatch[1];
				processedSvg = svgContent.replace(/<svg([^>]*)>/, (match) =>
					match.replace(
						/style="[^"]*"/,
						`style="${existingStyle}; ${styleAttr}"`
					)
				);
			} else {
				// Add new style attribute.
				processedSvg = svgContent.replace(
					/<svg([^>]*)>/,
					`<svg$1 style="${styleAttr}">`
				);
			}
		}
	}

	let content;
	if (!imageUrl) {
		content = (
			<Placeholder
				icon={<BlockIcon icon={image} />}
				label={__('Theme Image', 'theme-image-block')}
				instructions={__(
					'Select an image from the block settings',
					'theme-image-block'
				)}
			/>
		);
	} else if (inlineSVG && processedSvg) {
		// For inline SVG, apply dangerouslySetInnerHTML to link or wrapper
		// to match server-side structure without extra figure wrapper.
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
			// Will be applied to wrapper figure via blockProps below.
			content = null;
		}
	} else {
		// Build inline styles for img element.
		const imgStyles = {};
		if (width) {
			imgStyles.width = width;
		}
		if (height) {
			imgStyles.height = height;
		}

		const img = (
			<img
				src={imageUrl}
				alt={
					currentImage?.alt || __('Theme image preview', 'theme-image-block')
				}
				style={
					Object.keys(imgStyles).length > 0 ? imgStyles : undefined
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

	// For inline SVG without link, apply HTML directly to wrapper.
	const wrapperProps =
		inlineSVG && processedSvg && !linkUrl
			? {
					...blockProps,
					dangerouslySetInnerHTML: { __html: processedSvg },
				}
			: blockProps;

	return (
		<>
			{imageUrl && (
				<BlockControls group="block">
					<ToolbarButton
						icon="admin-links"
						label={__('Link', 'theme-image-block')}
						onClick={() => setIsEditingLink(true)}
						isActive={!!linkUrl}
					/>
					{linkUrl && (
						<ToolbarButton
							icon="editor-unlink"
							label={__('Unlink', 'theme-image-block')}
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
								title: __('Open in new tab', 'theme-image-block'),
							},
							{
								id: 'nofollow',
								title: __('Mark as nofollow', 'theme-image-block'),
							},
						]}
					/>
				</Popover>
			)}

			<InspectorControls>
				<PanelBody
					title={__('Settings', 'theme-image-block')}
					initialOpen={true}
				>
					<SelectControl
						label={__('Theme Image', 'theme-image-block')}
						value={themeImage}
						options={themeImages}
						onChange={(value) => {
							setAttributes({
								themeImage: value,
								imageSize: 'original',
							});
						}}
						help={__(
							'Select a registered theme image.',
							'theme-image-block'
						)}
					/>

					{currentImage &&
						currentImage.variations &&
						Object.keys(currentImage.variations).length > 0 && (
							<SelectControl
								label={__('Variation', 'theme-image-block')}
								value={imageSize}
								options={sizeOptions}
								onChange={(value) =>
									setAttributes({ imageSize: value })
								}
								help={__(
									'Select the image variation.',
									'theme-image-block'
								)}
							/>
						)}

					<SelectControl
						label={__('Style', 'theme-image-block')}
						value={imageStyle}
						options={[
							{ value: '', label: __('Default', 'theme-image-block') },
							...registeredStyles.map((style) => ({
								value: style.slug,
								label: style.name,
							})),
						]}
						onChange={(value) =>
							setAttributes({ imageStyle: value })
						}
						help={__(
							'Select a registered style to apply.',
							'theme-image-block'
						)}
					/>

					{isSVG && (
						<ToggleControl
							label={__('Inline SVG', 'theme-image-block')}
							checked={inlineSVG}
							onChange={(value) =>
								setAttributes({ inlineSVG: value })
							}
							help={__(
								'Render SVG code inline.',
								'theme-image-block'
							)}
						/>
					)}
				</PanelBody>
			</InspectorControls>

			<figure {...wrapperProps}>
				{content}
				{imageUrl && (
					<RichText
						tagName="figcaption"
						placeholder={__('Add caption…', 'theme-image-block')}
						value={caption}
						onChange={(value) => setAttributes({ caption: value })}
						allowedFormats={[]}
					/>
				)}
			</figure>
		</>
	);
}

// Register the block.
registerBlockType(metadata.name, {
	edit: Edit,
});
