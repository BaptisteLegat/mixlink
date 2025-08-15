import autoprefixer from 'autoprefixer'
import cssnano from 'cssnano'

export default (ctx) => {
  const isProduction = ctx.env === 'production'

  const plugins = [
    autoprefixer({
      overrideBrowserslist: ['>= 0.5%', 'last 2 versions', 'Firefox ESR', 'not dead']
    })
  ]

  if (isProduction) {
    plugins.push(
      cssnano({
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
      })
    )
  }

  return {
    plugins
  }
}
