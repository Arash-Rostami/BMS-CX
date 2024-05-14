/** @type {import('tailwindcss').Config} */
export default {
  content: [
      "./resources/components/*.blade.php",
      "./resources/**/*.blade.php",
      "./resources/**/*.js",
      "./resources/**/*.vue",
      "./vendor/andrewdwallo/filament-selectify/resources/views/**/*.blade.php",
      "./vendor/filament/**/*.{php,vue,js}"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}

