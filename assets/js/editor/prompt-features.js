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
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { registerAbilityInput } from './lib/registry';
import { aiIcon } from './icon';

const SIDEBAR_NAME = 'outstand-ai-prompts';
const SIDEBAR_ICON = aiIcon;

/**
 * Resolve the prompt for a feature: per-post override else global default.
 * Defers to a native site guideline when one is active.
 *
 * @param {Object} feature The feature config.
 * @return {string} The resolved prompt, or '' to leave the plugin default.
 */
function resolvePrompt(feature) {
	const meta = select(editorStore).getEditedPostAttribute('meta') || {};
	const perPost = meta[feature.metaKey];

	// Per-post override always wins.
	if (perPost && perPost.trim()) {
		return perPost;
	}

	// When a native site guideline already covers the global default, defer to it.
	if (feature.nativeGlobalActive) {
		return '';
	}

	return feature.global && feature.global.trim() ? feature.global : '';
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

	return (
		<PanelBody title={feature.label} initialOpen>
			<TextareaControl
				label={feature.label}
				hideLabelFromVision
				help={feature.description}
				placeholder={feature.global || feature.example || ''}
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
