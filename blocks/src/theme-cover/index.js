/**
 * WordPress dependencies
 */
import {
	InspectorControls,
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
	BlockControls,
	BlockIcon,
	__experimentalBlockAlignmentMatrixControl as BlockAlignmentMatrixControl,
} from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	RangeControl,
	FocalPointPicker,
	Placeholder,
	TextControl,
	__experimentalUnitControl as UnitControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { cover as coverIcon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';

const TAG_NAME_OPTIONS = [
	{ value: 'div', label: __('Default (<div>)', 'theme-image-block') },
	{ value: 'header', label: '<header>' },
	{ value: 'main', label: '<main>' },
	{ value: 'section', label: '<section>' },
	{ value: 'article', label: '<article>' },
	{ value: 'aside', label: '<aside>' },
	{ value: 'footer', label: '<footer>' },
];

/**
 * Map content position strings (e.g. "top left") to CSS class modifiers.
 *
 * @param {string} position Content position string.
 * @returns {string} Space-separated class modifiers.
 */
function getContentPositionClasses(position) {
	if (!position || position === 'center center') {
		return '';
	}

	const [vertical, horizontal] = position.split(' ');
	const classes = [];

	if (vertical && vertical !== 'center') {
		classes.push(`is-position-y-${vertical}`);
	}

	if (horizontal && horizontal !== 'center') {
		classes.push(`is-position-x-${horizontal}`);
	}

	return classes.join(' ');
}

/**
 * Editor component for the Theme Cover block.
 *
 * @param {object} props The component props.
 * @param {object} props.attributes The block attributes.
 * @param {Function} props.setAttributes The function to set block attributes.
 * @returns {JSX.Element} The block edit component.
 */
function Edit({ attributes, setAttributes }) {
	const {
		themeImage,
		imageSize,
		altText,
		omitAltText,
		overlayColor,
		customOverlayColor,
		dimRatio,
		focalPoint,
		hasParallax,
		isRepeated,
		minHeight,
		minHeightUnit,
		contentPosition,
		tagName,
	} = attributes;

	const registeredImages = happyprime_themeimageblock_data?.images || [];
	const themeUrl = happyprime_themeimageblock_data?.themeUrl || '';

	const themeImageOptions = [
		{ value: '', label: __('Select an image', 'theme-image-block') },
		...registeredImages.map((img) => ({
			value: img.slug,
			label: img.label,
		})),
	];

	const currentImage = registeredImages.find(
		(img) => img.slug === themeImage
	);

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

	let imagePath = currentImage?.value || '';
	if (
		currentImage &&
		imageSize !== 'original' &&
		currentImage.variations &&
		currentImage.variations[imageSize]
	) {
		imagePath = currentImage.variations[imageSize].path;
	}

	const imageUrl = imagePath && themeUrl ? `${themeUrl}/${imagePath}` : '';
	const resolvedOverlayColor = customOverlayColor || overlayColor || '';
	const positionClasses = getContentPositionClasses(contentPosition);

	const blockProps = useBlockProps({
		className: [
			'wp-block-happyprime-theme-cover',
			imageUrl ? 'has-background-image' : '',
			hasParallax ? 'has-parallax' : '',
			isRepeated ? 'is-repeated' : '',
			positionClasses,
		]
			.filter(Boolean)
			.join(' '),
		style: {
			minHeight: minHeight ? `${minHeight}${minHeightUnit}` : undefined,
		},
	});

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wp-block-happyprime-theme-cover__inner-container' },
		{
			template: [
				[
					'core/paragraph',
					{
						align: 'center',
						placeholder: __('Write title…', 'theme-image-block'),
					},
				],
			],
			templateInsertUpdatesSelection: true,
		}
	);

	const objectPosition = focalPoint
		? `${Math.round(focalPoint.x * 100)}% ${Math.round(
				focalPoint.y * 100
			)}%`
		: '50% 50%';

	const TagName = tagName || 'div';

	const hasImage = !!imageUrl;

	const backgroundImage =
		hasImage && !hasParallax ? (
			<img
				className="wp-block-happyprime-theme-cover__image-background"
				src={imageUrl}
				alt={omitAltText ? '' : altText || currentImage?.alt || ''}
				style={{
					objectPosition,
					objectFit: isRepeated ? 'none' : 'cover',
				}}
			/>
		) : null;

	const parallaxBackground =
		hasImage && hasParallax ? (
			<div
				role="img"
				aria-label={
					omitAltText ? '' : altText || currentImage?.alt || ''
				}
				className="wp-block-happyprime-theme-cover__image-background has-parallax"
				style={{
					backgroundImage: `url(${imageUrl})`,
					backgroundPosition: objectPosition,
					backgroundRepeat: isRepeated ? 'repeat' : 'no-repeat',
					backgroundSize: isRepeated ? 'auto' : 'cover',
					backgroundAttachment: 'fixed',
				}}
			/>
		) : null;

	const overlay = (
		<span
			aria-hidden="true"
			className="wp-block-happyprime-theme-cover__background"
			style={{
				backgroundColor: resolvedOverlayColor || undefined,
				opacity: dimRatio / 100,
			}}
		/>
	);

	return (
		<>
			<BlockControls group="block">
				<BlockAlignmentMatrixControl
					label={__('Change content position', 'theme-image-block')}
					value={contentPosition}
					onChange={(nextPosition) =>
						setAttributes({ contentPosition: nextPosition })
					}
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={__('Image', 'theme-image-block')}
					initialOpen={true}
				>
					<SelectControl
						label={__('Theme Image', 'theme-image-block')}
						value={themeImage}
						options={themeImageOptions}
						onChange={(value) =>
							setAttributes({
								themeImage: value,
								imageSize: 'original',
							})
						}
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

					{!omitAltText && (
						<TextControl
							label={__('Alt Text', 'theme-image-block')}
							value={altText}
							onChange={(value) =>
								setAttributes({ altText: value })
							}
							placeholder={currentImage?.alt || ''}
							help={__(
								'Alternative text for the background image. Leave empty to use the registered default.',
								'theme-image-block'
							)}
						/>
					)}

					<ToggleControl
						label={__('Omit alt text', 'theme-image-block')}
						checked={omitAltText}
						onChange={(value) =>
							setAttributes({ omitAltText: value })
						}
						help={__(
							'Treat the image as purely decorative and output empty alt text.',
							'theme-image-block'
						)}
					/>

					{hasImage && !hasParallax && (
						<FocalPointPicker
							label={__('Focal point', 'theme-image-block')}
							url={imageUrl}
							value={focalPoint || { x: 0.5, y: 0.5 }}
							onChange={(value) =>
								setAttributes({ focalPoint: value })
							}
						/>
					)}

					<ToggleControl
						label={__('Fixed background', 'theme-image-block')}
						checked={hasParallax}
						onChange={(value) =>
							setAttributes({ hasParallax: value })
						}
					/>

					<ToggleControl
						label={__('Repeated background', 'theme-image-block')}
						checked={isRepeated}
						onChange={(value) =>
							setAttributes({ isRepeated: value })
						}
					/>
				</PanelBody>

				<PanelBody
					title={__('Overlay', 'theme-image-block')}
					initialOpen={false}
				>
					<TextControl
						label={__('Overlay color', 'theme-image-block')}
						type="color"
						value={customOverlayColor}
						onChange={(value) =>
							setAttributes({ customOverlayColor: value })
						}
						help={__(
							'Hex color used for the overlay tint.',
							'theme-image-block'
						)}
					/>
					<RangeControl
						label={__('Overlay opacity', 'theme-image-block')}
						value={dimRatio}
						onChange={(value) => setAttributes({ dimRatio: value })}
						min={0}
						max={100}
						step={10}
					/>
				</PanelBody>

				<PanelBody
					title={__('Dimensions', 'theme-image-block')}
					initialOpen={false}
				>
					<UnitControl
						label={__('Minimum height', 'theme-image-block')}
						value={`${minHeight}${minHeightUnit}`}
						onChange={(next) => {
							const match =
								typeof next === 'string'
									? next.match(/^([\d.]+)([a-z%]*)$/i)
									: null;
							if (match) {
								setAttributes({
									minHeight: parseFloat(match[1]) || 0,
									minHeightUnit: match[2] || 'px',
								});
							}
						}}
						units={[
							{ value: 'px', label: 'px', default: 430 },
							{ value: 'em', label: 'em', default: 20 },
							{ value: 'rem', label: 'rem', default: 20 },
							{ value: 'vw', label: 'vw', default: 20 },
							{ value: 'vh', label: 'vh', default: 50 },
						]}
					/>
				</PanelBody>

				<PanelBody
					title={__('Advanced', 'theme-image-block')}
					initialOpen={false}
				>
					<SelectControl
						label={__('HTML element', 'theme-image-block')}
						value={tagName}
						options={TAG_NAME_OPTIONS}
						onChange={(value) => setAttributes({ tagName: value })}
					/>
				</PanelBody>
			</InspectorControls>

			<TagName {...blockProps}>
				{overlay}
				{backgroundImage}
				{parallaxBackground}
				{!hasImage && (
					<Placeholder
						icon={<BlockIcon icon={coverIcon} />}
						label={__('Theme Cover', 'theme-image-block')}
						instructions={__(
							'Select a registered theme image from the block settings to use as the cover background.',
							'theme-image-block'
						)}
					/>
				)}
				<div {...innerBlocksProps} />
			</TagName>
		</>
	);
}

/**
 * Save function — serializes inner blocks so the dynamic
 * render callback can wrap them with the cover structure.
 *
 * @returns {JSX.Element} The saved content.
 */
function Save() {
	return (
		<div className="wp-block-happyprime-theme-cover__inner-container">
			<InnerBlocks.Content />
		</div>
	);
}

registerBlockType(metadata.name, {
	edit: Edit,
	save: Save,
});
