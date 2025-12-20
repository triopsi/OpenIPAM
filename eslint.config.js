// eslint.config.js
import js from "@eslint/js";

export default [
  // Basis-Empfehlungen von ESLint
  js.configs.recommended,

  {
    files: ["resources/js/**/*.js"],

    languageOptions: {
      ecmaVersion: "latest",
      sourceType: "module",
      globals: {
        window: "readonly",
        document: "readonly",
        console: "readonly",
      },
    },

    rules: {
      // 🧹 Sauberkeit
      "no-unused-vars": ["error", { argsIgnorePattern: "^_" }],
      "no-undef": "error",

      // ⚠️ Sicherheit & Logik
      "eqeqeq": ["error", "always"],
      "no-eval": "error",

      // 🧠 Moderne JS-Regeln
      "prefer-const": "error",
      "no-var": "error",

      // 🐛 Debugging
      "no-console": "off", // in Laravel-Projekten meist ok
    },
  },
];
