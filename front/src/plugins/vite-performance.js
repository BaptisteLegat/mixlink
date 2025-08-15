/**
 * Plugin Vite for optimizing performance
 * Injects performance attributes and critical preloads
 */
export function performanceOptimizationPlugin() {
    return {
        name: 'performance-optimization',
        transformIndexHtml: {
            order: 'pre',
            handler(html, context) {
                if (context.bundle) {
                    const cssFiles = Object.keys(context.bundle).filter((file) => file.endsWith('.css') && context.bundle[file].isEntry);

                    let optimizedHtml = html;

                    if (cssFiles.length > 0) {
                        const preloadCSS = cssFiles
                            .map((file) => `<link rel="preload" href="/${file}" as="style" onload="this.onload=null;this.rel='stylesheet'">`)
                            .join('\n    ');
                        optimizedHtml = optimizedHtml.replace(/<head>/i, `<head>\n    ${preloadCSS}`);
                    }

                    optimizedHtml = optimizedHtml.replace(/<script([^>]*src="[^"]*\.js"[^>]*)>/gi, (match, attributes) => {
                        if (attributes.includes('defer') || attributes.includes('async')) {
                            return match;
                        }

                        if (!attributes.includes('type="module"')) {
                            return `<script${attributes} defer>`;
                        }

                        return match;
                    });

                    return optimizedHtml;
                }

                return html;
            },
        },
        generateBundle(options, bundle) {
            Object.keys(bundle).forEach((fileName) => {
                const chunk = bundle[fileName];
                if (chunk.type === 'chunk') {
                    if (chunk.name && !['index', 'main', 'app'].includes(chunk.name)) {
                        chunk.isLazy = true;
                    }
                }
            });
        },
    };
}

export function imageOptimizationPlugin() {
    return {
        name: 'image-optimization',
        generateBundle(options, bundle) {
            Object.keys(bundle).forEach((fileName) => {
                const asset = bundle[fileName];
                if (asset.type === 'asset' && /\.(png|jpe?g|webp|avif)$/i.test(fileName)) {
                    asset.needsOptimization = true;
                }
            });
        },
        transformIndexHtml: {
            order: 'post',
            handler(html) {
                return html.replace(/<img([^>]*)src="([^"]*)"([^>]*)>/gi, (match, beforeSrc, src, afterSrc) => {
                    if (match.includes('loading=') && match.includes('fetchpriority=')) {
                        return match;
                    }

                    let optimizedAttributes = beforeSrc + afterSrc;

                    if (!match.includes('loading=')) {
                        optimizedAttributes += ' loading="lazy"';
                    }

                    if (!match.includes('fetchpriority=') && src.includes('landing-page')) {
                        optimizedAttributes += ' fetchpriority="high"';
                    }

                    return `<img${optimizedAttributes} src="${src}">`;
                });
            },
        },
    };
}
