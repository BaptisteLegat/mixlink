export default (ctx) => {
  const isProduction = ctx.env === 'production';

  return {
    plugins: {
      autoprefixer: {
        overrideBrowserslist: ['>= 0.5%', 'last 2 versions', 'Firefox ESR', 'not dead']
      },
      ...(isProduction ? {
        cssnano: {
          preset: ['default', {
            discardComments: { removeAll: true },
            normalizeWhitespace: true,
            colormin: true,
            minifyFontValues: true,
            minifySelectors: true,
            mergeLonghand: true,
            mergeRules: true,
            reduceIdents: false,
            zindex: false,
            discardUnused: true,
            minifyGradients: true,
            normalizeUrl: true,
          }]
        },
        '@fullhuman/postcss-purgecss': {
          content: [
            './index.html',
            './src/**/*.{vue,js,ts,jsx,tsx}',
          ],
          defaultExtractor: content => content.match(/[\w-/:]+(?<!:)/g) || [],
          safelist: [
            'html', 'body',
            /^el-/,
            /^is-/,
            /^has-/,
            /^v-/,
            /^router-/,
            /dark/,
          ],
          blocklist: [],
          keyframes: true,
          variables: true,
        }
      } : {})
    }
  };
};
