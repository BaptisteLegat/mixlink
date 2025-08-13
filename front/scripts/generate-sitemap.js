import fs from 'fs';

const BASE_URL = 'https://mix-link.fr';
const OUTPUT_PATH = './public/sitemap.xml';

const pages = [
    {
        path: '/',
        changefreq: 'weekly',
        priority: '1.0',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/login',
        changefreq: 'monthly',
        priority: '0.8',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/contact',
        changefreq: 'monthly',
        priority: '0.7',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/faq',
        changefreq: 'monthly',
        priority: '0.6',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/privacy',
        changefreq: 'yearly',
        priority: '0.3',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/terms',
        changefreq: 'yearly',
        priority: '0.3',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/profile',
        changefreq: 'weekly',
        priority: '0.5',
        lastmod: new Date().toISOString().split('T')[0]
    },
    {
        path: '/session',
        changefreq: 'weekly',
        priority: '0.5',
        lastmod: new Date().toISOString().split('T')[0]
    }
];

function generateSitemap() {
    let xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">

`;

    pages.forEach(page => {
        xml += `    <!-- ${page.path === '/' ? 'Page d\'accueil' : page.path.replace('/', 'Page ')} -->
    <url>
        <loc>${BASE_URL}${page.path}</loc>
        <lastmod>${page.lastmod}</lastmod>
        <changefreq>${page.changefreq}</changefreq>
        <priority>${page.priority}</priority>
    </url>

`;
    });

    xml += `</urlset>`;

    fs.writeFileSync(OUTPUT_PATH, xml, 'utf8');
}

generateSitemap();
