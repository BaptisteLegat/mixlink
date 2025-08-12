URL=${1:-"http://localhost:3000"}
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
REPORT_FILE="lighthouse-reports/performance-monitor-$TIMESTAMP.json"

echo "🔍 Monitoring des performances pour: $URL"
echo "📊 Génération du rapport JSON..."

mkdir -p lighthouse-reports

lighthouse "$URL" \
  --output=json \
  --output-path="$REPORT_FILE" \
  --chrome-flags='--headless' \
  --quiet \
  --throttling-method=provided \
  --throttling.cpuSlowdownMultiplier=1 \
  --throttling.rttMs=40 \
  --throttling.throughputKbps=10240

if [ $? -eq 0 ]; then
    echo "✅ Rapport généré: $REPORT_FILE"

    echo ""
    echo "📈 MÉTRIQUES PRINCIPALES:"

    python3 -c "
import json
import sys

try:
    with open('$REPORT_FILE', 'r') as f:
        data = json.load(f)

    categories = data['categories']
    audits = data['audits']

    print(f'Performance: {round(categories[\"performance\"][\"score\"] * 100)}/100')
    print(f'Accessibilité: {round(categories[\"accessibility\"][\"score\"] * 100)}/100')
    print(f'Best Practices: {round(categories[\"best-practices\"][\"score\"] * 100)}/100')
    print(f'SEO: {round(categories[\"seo\"][\"score\"] * 100)}/100')
    if 'pwa' in categories and categories['pwa']['score'] is not None:
        print(f'PWA: {round(categories[\"pwa\"][\"score\"] * 100)}/100')
    else:
        print('PWA: N/A')

    print()
    print('⏱️  MÉTRIQUES TEMPORELLES:')

    # Métriques de performance
    metrics = {
        'first-contentful-paint': 'First Contentful Paint',
        'largest-contentful-paint': 'Largest Contentful Paint',
        'first-meaningful-paint': 'First Meaningful Paint',
        'speed-index': 'Speed Index',
        'total-blocking-time': 'Total Blocking Time',
        'cumulative-layout-shift': 'Cumulative Layout Shift'
    }

    for key, label in metrics.items():
        if key in audits and audits[key]['displayValue']:
            print(f'{label}: {audits[key][\"displayValue\"]}')

    print()
    print('📦 MÉTRIQUES DE TAILLE:')

    # Métriques de taille
    size_metrics = {
        'total-byte-weight': 'Taille totale',
        'unused-javascript': 'JavaScript inutilisé',
        'unused-css-rules': 'CSS inutilisé'
    }

    for key, label in size_metrics.items():
        if key in audits and audits[key].get('displayValue'):
            print(f'{label}: {audits[key][\"displayValue\"]}')

except Exception as e:
    print(f'Erreur lors du parsing: {e}')
    sys.exit(1)
"

    echo ""
    echo "💡 CONSEILS:"
    echo "• Consultez le rapport détaillé: $REPORT_FILE"
    echo "• Utilisez les outils de développement Chrome pour analyser les performances"
    echo "• Surveillez régulièrement ces métriques"

else
    echo "❌ Erreur lors de la génération du rapport"
    exit 1
fi
