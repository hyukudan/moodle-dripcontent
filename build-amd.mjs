#!/usr/bin/env node
/**
 * AMD build script for moodle-dripcontent
 * Compiles ES6 modules to AMD format for Moodle
 */

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
import { createRequire } from 'module';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Moodle's node_modules path
const MOODLE_ROOT = '/home/preparaoposiciones/formacion51';
const NODE_MODULES = join(MOODLE_ROOT, 'node_modules');

// Use require for CommonJS modules
const require = createRequire(import.meta.url);
const babel = require(join(NODE_MODULES, '@babel/core'));
const terser = require(join(NODE_MODULES, 'terser'));

const COMPONENT = 'availability_dripcontent';
const SRC_DIR = join(__dirname, 'amd', 'src');
const BUILD_DIR = join(__dirname, 'amd', 'build');

// Ensure build directory exists
if (!existsSync(BUILD_DIR)) {
    mkdirSync(BUILD_DIR, { recursive: true });
}

// Files to compile
const files = ['form.js'];

for (const file of files) {
    const srcPath = join(SRC_DIR, file);
    const buildPath = join(BUILD_DIR, file.replace('.js', '.min.js'));
    const moduleName = `${COMPONENT}/${file.replace('.js', '')}`;

    console.log(`Compiling ${file}...`);

    const source = readFileSync(srcPath, 'utf8');

    // Babel transform to AMD
    const babelResult = await babel.transformAsync(source, {
        presets: [
            [join(NODE_MODULES, '@babel/preset-env'), {
                targets: { browsers: ['last 2 versions', 'ie >= 11'] },
                modules: false
            }]
        ],
        plugins: [
            [join(NODE_MODULES, 'babel-plugin-transform-es2015-modules-amd-lazy'), {
                noInterop: true
            }]
        ],
        filename: file,
        sourceMaps: false
    });

    // Add module name to define call
    // The space after 'define' prevents Moodle's PHP from modifying it
    let code = babelResult.code.replace(
        /^define\s*\(\s*\[/m,
        `define ("${moduleName}", [`
    );

    // Minify with Terser
    const minified = await terser.minify(code, {
        compress: {
            drop_console: false
        },
        mangle: true,
        output: {
            comments: false
        }
    });

    writeFileSync(buildPath, minified.code);
    console.log(`  -> ${buildPath}`);
}

console.log('Done!');
