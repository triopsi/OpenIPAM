import { appendFileSync } from 'node:fs';
import semanticRelease from 'semantic-release';

const result = await semanticRelease();
appendFileSync(process.env.GITHUB_OUTPUT, `published=${Boolean(result)}\n`);
if (result) {
    appendFileSync(process.env.GITHUB_OUTPUT, `version=${result.nextRelease.version}\n`);
}
