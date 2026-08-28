/**
 * @wordpress/api-fetch and @wordpress/url are provided by WordPress at runtime
 * (webpack externals), so they are not installed in node_modules. Mock them.
 *
 * The url mock round-trips the `input` object through the query string using
 * JSON, which is enough to exercise the middleware's GET/query-string branch.
 */
jest.mock(
	'@wordpress/api-fetch',
	() => {
		const fn = () => {};
		fn.use = (middleware) => {
			fn.__middleware = middleware;
		};
		return { __esModule: true, default: fn };
	},
	{ virtual: true }
);

jest.mock(
	'@wordpress/url',
	() => ({
		__esModule: true,
		getQueryArg: (url, key) => {
			const query = String(url).split('?')[1];
			if (!query) {
				return undefined;
			}
			const hit = query
				.split('&')
				.map((pair) => pair.split('='))
				.find(([k]) => k === key);
			return hit ? JSON.parse(decodeURIComponent(hit[1])) : undefined;
		},
		addQueryArgs: (url, args) => {
			const [base, query = ''] = String(url).split('?');
			const params = new Map();
			query
				.split('&')
				.filter(Boolean)
				.forEach((pair) => {
					const [k, v] = pair.split('=');
					params.set(k, v);
				});
			Object.entries(args).forEach(([k, v]) => {
				params.set(k, encodeURIComponent(JSON.stringify(v)));
			});
			const serialized = [...params]
				.map(([k, v]) => `${k}=${v}`)
				.join('&');
			return `${base}?${serialized}`;
		},
	}),
	{ virtual: true }
);

const RUN_PATH = '/wp-abilities/v1/abilities/ai/excerpt-generation/run';

/**
 * Load a fresh copy of the module (its `installed` flag and transformer list are
 * module-level state) and return the installed middleware plus its registrar.
 *
 * @return {{ registerAbilityInput: Function, middleware: Function }} The module surface.
 */
function loadMiddleware() {
	jest.resetModules();
	// eslint-disable-next-line global-require
	const apiFetch = require('@wordpress/api-fetch').default;
	// eslint-disable-next-line global-require
	const mod = require('./registry');
	mod.installMiddleware();
	return {
		registerAbilityInput: mod.registerAbilityInput,
		middleware: apiFetch.__middleware,
	};
}

/**
 * A transformer that injects a `context` field on requests to the run path.
 *
 * @return {{ match: Function, transform: Function }} The transformer.
 */
function contextInjector() {
	return {
		match: (path) => path.includes(RUN_PATH),
		transform: (input) => ({ ...input, context: 'STEER' }),
	};
}

describe('installMiddleware', () => {
	it('transforms input carried in the POST body', () => {
		const { registerAbilityInput, middleware } = loadMiddleware();
		registerAbilityInput(contextInjector());

		const options = {
			path: RUN_PATH,
			method: 'POST',
			data: { input: { content: 'body', context: '42' } },
		};
		const next = jest.fn((opts) => opts);

		middleware(options, next);

		expect(options.data.input).toEqual({ content: 'body', context: 'STEER' });
		expect(next).toHaveBeenCalledWith(options);
	});

	it('transforms input serialized into the GET query string', () => {
		const { registerAbilityInput, middleware } = loadMiddleware();
		registerAbilityInput(contextInjector());

		const input = { content: 'body', context: '42' };
		const options = {
			path: `${RUN_PATH}?input=${encodeURIComponent(JSON.stringify(input))}`,
			method: 'GET',
		};

		middleware(options, (opts) => opts);

		const query = options.path.split('?')[1];
		const sent = JSON.parse(
			decodeURIComponent(
				query
					.split('&')
					.map((pair) => pair.split('='))
					.find(([k]) => k === 'input')[1]
			)
		);
		expect(sent).toEqual({ content: 'body', context: 'STEER' });
	});

	it('leaves non-matching requests untouched', () => {
		const { registerAbilityInput, middleware } = loadMiddleware();
		registerAbilityInput(contextInjector());

		const options = {
			path: '/wp/v2/posts/1',
			method: 'POST',
			data: { input: { context: '42' } },
		};

		middleware(options, (opts) => opts);

		expect(options.data.input).toEqual({ context: '42' });
	});

	it('does not rewrite the GET path when no transformer changes the input', () => {
		const { middleware } = loadMiddleware();

		const original = `${RUN_PATH}?input=${encodeURIComponent(
			JSON.stringify({ context: '42' })
		)}`;
		const options = { path: original, method: 'GET' };

		middleware(options, (opts) => opts);

		expect(options.path).toBe(original);
	});
});
