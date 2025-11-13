/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './**/*.html',
    './assets/js/**/*.js',
    './woocommerce/**/*.php',
    './inc/**/*.php',
    './template-parts/**/*.php',
    // Bezpośrednie ścieżki do plików
    './functions.php',
    './style.css',
    './index.php',
    './header.php',
    './footer.php',
    './sidebar.php'
  ],
  theme: {
    extend: {
      colors: {
        primary: 'var(--theme-primary)',
        secondary: 'var(--theme-secondary)',
        accent: 'var(--theme-accent)',
        'text-dark': 'var(--theme-text-dark)',
        'text-light': 'var(--theme-text-light)',
        background: 'var(--theme-background)',
        'background-alt': 'var(--theme-background-alt)',
      },
      fontFamily: {
        primary: 'var(--theme-font-primary)',
        secondary: 'var(--theme-font-secondary)',
      },
      maxWidth: {
        container: 'var(--theme-container-width)',
      },
      borderRadius: {
        theme: 'var(--theme-border-radius)',
      },
      boxShadow: {
        theme: 'var(--theme-box-shadow)',
      },
      spacing: {
        'xs': 'var(--theme-spacing-xs)',
        'sm': 'var(--theme-spacing-sm)', 
        'md': 'var(--theme-spacing-md)',
        'lg': 'var(--theme-spacing-lg)',
        'xl': 'var(--theme-spacing-xl)',
        'xxl': 'var(--theme-spacing-xxl)',
      },
      // Responsive breakpoints zsynchronizowane z naszym systemem
      screens: {
        'mobile': '0px',
        'tablet': '640px',
        'desktop': '1024px', 
        'large': '1536px',
      }
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}

