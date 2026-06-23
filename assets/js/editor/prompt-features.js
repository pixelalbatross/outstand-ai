/**
 * WordPress dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginSidebar,
	PluginSidebarMoreMenuItem,
	store as editorStore,
} from '@wordpress/editor';
import { PanelBody, TextareaControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect, select } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerAbilityInput } from './lib/registry';
import { aiIcon } from './icon';

const SIDEBAR_NAME = 'outstand-ai-prompts';
const SIDEBAR_ICON = aiIcon;
const DEFAULT_TOKEN_FALLBACK = '{DEFAULT_PROMPT}';

/**
 * Expand every occurrence of the default-prompt token in a per-post prompt,
 * then tidy the result so an empty or edge-positioned token leaves no gaps.
 *
 * @param {string} text          The per-post prompt.
 * @param {string} token         The default-prompt token to replace.
 * @param {string} defaultPrompt The global default to substitute in.
 * @return {string} The merged prompt.
 */
function expandDefaultToken(text, token, defaultPrompt) {
	return text
		.split(token)
		.join(defaultPrompt || '')
		.replace(/\n{3,}/g, '\n\n')
		.trim();
}

/**
 * Resolve the prompt for a feature: per-post override else global default.
 * A per-post prompt may embed the default-prompt token to merge the global
 * default with its own details instead of replacing it. Defers to a native
 * site guideline when one is active and no per-post prompt is set.
 *
 * @param {Object} feature The feature config.
 * @return {string} The resolved prompt, or '' to leave the plugin default.
 */
function resolvePrompt(feature) {
	const meta = select(editorStore).getEditedPostAttribute('meta') || {};
	const perPost = meta[feature.metaKey];
	const token = feature.defaultToken || DEFAULT_TOKEN_FALLBACK;
	const global =
		feature.global && feature.global.trim() ? feature.global : '';

	// Per-post override always wins. When it embeds the token, expand it inline
	// to merge the global default with the user's details; otherwise it replaces.
	if (perPost && perPost.trim()) {
		if (perPost.includes(token)) {
			return expandDefaultToken(perPost, token, global);
		}
		return perPost;
	}

	// When a native site guideline already covers the global default, defer to it.
	if (feature.nativeGlobalActive) {
		return '';
	}

	return global;
}

/**
 * A single feature's prompt field within the AI Prompts sidebar.
 *
 * @param {Object}   props         Component props.
 * @param {Object}   props.feature The feature config.
 * @param {Object}   props.meta    The current post meta.
 * @param {Function} props.setMeta Setter for the post meta.
 * @return {JSX.Element} The field.
 */
function FeatureField({ feature, meta, setMeta }) {
	const value = (meta && meta[feature.metaKey]) || '';
	const token = feature.defaultToken || DEFAULT_TOKEN_FALLBACK;
	const tip = createInterpolateElement(
		sprintf(
			/* translators: %s is the default-prompt token, e.g. {DEFAULT_PROMPT}. */
			__(
				'Tip: include <code>%s</code> to merge the default prompt with your details.',
				'outstand-ai'
			),
			token
		),
		{ code: <code /> }
	);

	return (
		<PanelBody title={feature.label} initialOpen>
			<TextareaControl
				label={feature.label}
				hideLabelFromVision
				help={
					<>
						{feature.description}
						<br />
						{tip}
					</>
				}
				placeholder={
					(feature.nativeGlobalActive ? '' : feature.global) ||
					feature.example ||
					''
				}
				value={value}
				rows={6}
				onChange={(next) =>
					setMeta({ ...meta, [feature.metaKey]: next })
				}
				__nextHasNoMarginBottom
			/>
		</PanelBody>
	);
}

/**
 * The consolidated "AI Prompts" sidebar: one place to manage every per-post AI
 * prompt that applies to the current post type.
 *
 * @param {Object} props          Component props.
 * @param {Array}  props.features The prompt features.
 * @return {JSX.Element|null} The sidebar, or null when nothing applies.
 */
function PromptsSidebar({ features }) {
	const postType = useSelect(
		(storeSelect) => storeSelect(editorStore).getCurrentPostType(),
		[]
	);
	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

	const applicable = features.filter((feature) =>
		feature.postTypes.includes(postType)
	);

	if (!applicable.length) {
		return null;
	}

	const title = __('AI Prompts', 'outstand-ai');

	return (
		<>
			<PluginSidebarMoreMenuItem
				target={SIDEBAR_NAME}
				icon={SIDEBAR_ICON}
			>
				{title}
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={SIDEBAR_NAME}
				title={title}
				icon={SIDEBAR_ICON}
			>
				{applicable.map((feature) => (
					<FeatureField
						key={feature.id}
						feature={feature}
						meta={meta}
						setMeta={setMeta}
					/>
				))}
			</PluginSidebar>
		</>
	);
}

/**
 * Register the per-feature ability-input transformers and the single,
 * consolidated AI Prompts sidebar.
 */
export function registerPromptFeatures() {
	const config = window.outstandAi || { features: {} };
	const features = Object.values(config.features || {});

	features.forEach((feature) => {
		registerAbilityInput({
			match: (path) =>
				path.includes(
					`/wp-abilities/v1/abilities/${feature.ability}/run`
				),
			transform: (input) => {
				const resolved = resolvePrompt(feature);

				if (resolved) {
					return { ...input, [feature.injectField]: resolved };
				}

				return input;
			},
		});
	});

	registerPlugin(SIDEBAR_NAME, {
		render: () => <PromptsSidebar features={features} />,
	});
}
