import { installMiddleware } from './lib/registry';
import { registerPromptFeatures } from './prompt-features';

installMiddleware();
registerPromptFeatures();
