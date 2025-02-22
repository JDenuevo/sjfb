module.exports = {
  content: [
    'node_modules/preline/dist/*.js',  // Preline JS files
    "./*.{html,php,js}",               // Root files
    "./**/*.{html,php,js}",            // All subdirectories and files
    "./components/**/*.{html,php,js}", // Specific 'components' folder
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('preline/plugin'),         // Preline plugin
  ],
};
