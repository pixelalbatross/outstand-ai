/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';

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

let installed = false;

/**
 * Install the api-fetch middleware that applies registered transformers to
 * matching `/wp-abilities/v1/abilities/{name}/run` requests. Safe to call once.
 */
export function installMiddleware() {
	if (installed) {
		return;
	}

	installed = true;

	apiFetch.use((options, next) => {
		const path = options.path || options.url || '';

		if (options.data && options.data.input) {
			transformers.forEach((transformer) => {
				if (transformer.match(path, options)) {
					options.data.input = transformer.transform(
						options.data.input,
						options
					);
				}
			});
		}

		return next(options);
	});
}
