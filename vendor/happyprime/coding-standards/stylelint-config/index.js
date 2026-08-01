/**
 * This file is forked from the stylelint-config-recommended package due to
 * dependency conflicts caused by various @wordpress/* packages.
 *
 * It was last updated to stylelint-config-recommended 16.0.0. Custom rule
 * changes have inline documentation.
 */
'use strict';

module.exports = {
	rules: {
		'annotation-no-unknown': true,
		'at-rule-descriptor-no-unknown': true,
		'at-rule-descriptor-value-no-unknown': true,
		'at-rule-no-deprecated': true,
		'at-rule-no-unknown': true,
		'at-rule-prelude-no-invalid': [true, { ignoreAtRules: ['media'] }],
		'block-no-empty': true,
		'comment-no-empty': true,
		'custom-property-no-missing-var-function': true,
		'declaration-block-no-duplicate-custom-properties': true,
		'declaration-block-no-duplicate-properties': [
			true,
			{
				ignore: ['consecutive-duplicates-with-different-syntaxes'],
			},
		],
		'declaration-block-no-shorthand-property-overrides': true,
		'declaration-property-value-keyword-no-deprecated': true,
		'declaration-property-value-no-unknown': true,
		'font-family-no-duplicate-names': true,
		'font-family-no-missing-generic-family-keyword': true,
		'function-calc-no-unspaced-operator': true,
		'keyframe-block-no-duplicate-selectors': true,
		'keyframe-declaration-no-important': true,
		'media-feature-name-no-unknown': true,
		'media-feature-name-value-no-unknown': true,
		'media-query-no-invalid': true,
		'named-grid-areas-no-invalid': true,

		/**
		 * Stylelint seems to have trouble properly tracking specificity
		 * between nested blocks of CSS and this generally results in
		 * false positives.
		 */
		'no-descending-specificity': null,

		'no-duplicate-at-import-rules': true,
		'no-duplicate-selectors': true,
		'no-empty-source': true,
		'no-invalid-double-slash-comments': true,
		'no-invalid-position-at-import-rule': true,
		'no-irregular-whitespace': true,
		'property-no-unknown': true,
		'selector-anb-no-unmatchable': true,

		/**
		 * These rules don't account for WordPress's mix of `-` and `_` in
		 * class and ID names.
		 */
		'selector-class-pattern': null,
		'selector-id-pattern': null,

		'selector-pseudo-class-no-unknown': true,
		'selector-pseudo-element-no-unknown': true,
		'selector-type-no-unknown': [
			true,
			{
				ignore: ['custom-elements'],
			},
		],
		'string-no-newline': [true, { ignore: ['at-rule-preludes', 'declaration-values'] }],
		'syntax-string-no-invalid': true,
	},
};
