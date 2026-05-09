import js from '@eslint/js';
import globals from 'globals';

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
            },
        },
        rules: {
            'semi': ['error', 'always'],
            'quotes': ['error', 'single', { avoidEscape: true }],
            'indent': ['error', 4, { SwitchCase: 1 }],
            'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            'no-console': 'warn',
            'eqeqeq': ['error', 'always'],
            'curly': ['error', 'all'],
            'comma-dangle': ['error', 'always-multiline'],
            'no-var': 'error',
            'prefer-const': 'error',
            'sort-imports': ['error', { ignoreDeclarationSort: true }],
        },
    },
    {
        ignores: ['assets/vendor/', 'assets/translations.js'],
    },
];
