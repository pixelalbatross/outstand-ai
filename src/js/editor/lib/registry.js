/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { getQueryArg, addQueryArgs } from '@wordpress/url';

/**
 * Registered ability-input transformers.
 *
 * Each transformer: { match( path, options ): boolean, transform( input, options ): input }
 *
 * @type {Array<{ match: Function, transform: Function }>}
 */
const transformers = [];

/**
 * Register a transformer that can mutate the input of an AI plugin Ability run
 * request before it is sent.
 *
 * @param {{ match: Function, transform: Function }} transformer The transformer.
 */
export function registerAbilityInput(transformer) {
	transformers.push(transformer);
}

/**
 * Run every matching transformer against an input, threading the result of one
 * into the next.
 *
 * @param {string} path    The request path.
 * @param {Object} options The api-fetch options.
 * @param {Object} input   The ability input to transform.
 * @return {Object} The transformed input.
 */
function applyInputTransforms(path, options, input) {
	return transformers.reduce((current, transformer) => {
		if (transformer.match(path, options)) {
			return transformer.transform(current, options);
		}
		return current;
	}, input);
}

let installed = false;

/**
 * Install the api-fetch middleware that applies registered transformers to
 * matching `/wp-abilities/v1/abilities/{name}/run` requests. Safe to call once.
 *
 * The core ability client sends the input two ways: as a POST body
 * (`options.data.input`, the default for our generation abilities) and — for
 * abilities annotated `readonly` — as a `GET` with the input serialized into the
 * query string. We cover both so a future annotation change on the AI plugin's
 * side cannot silently disable prompt injection.
 */
export function installMiddleware() {
	if (installed) {
		return;
	}

	installed = true;

	apiFetch.use((options, next) => {
		const path = options.path || options.url || '';

		// POST/PUT body form: input lives in options.data.input.
		if (options.data && options.data.input) {
			options.data.input = applyInputTransforms(
				path,
				options,
				options.data.input
			);
			return next(options);
		}

		// GET/DELETE form: input is serialized into the query string.
		const queryInput = getQueryArg(path, 'input');

		if (queryInput) {
			const transformed = applyInputTransforms(path, options, queryInput);

			// Only rewrite the path when a transformer actually changed the input;
			// a no-op transform returns the same reference.
			if (transformed !== queryInput) {
				const rewritten = addQueryArgs(path, { input: transformed });

				if (options.path) {
					options.path = rewritten;
				} else {
					options.url = rewritten;
				}
			}

			return next(options);
		}

		return next(options);
	});
}
