echo "🚀 Démarrage de l'audit Lighthouse multi-pages..."

URLS=(
    "http://localhost:3000/"
    "http://localhost:3000/login"
    "http://localhost:3000/profile"
    "http://localhost:3000/contact"
    "http://localhost:3000/faq"
    "http://localhost:3000/privacy"
    "http://localhost:3000/terms"
    "http://localhost:3000/session"
)

mkdir -p lighthouse-reports

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
REPORT_DIR="lighthouse-reports/$TIMESTAMP"
mkdir -p "$REPORT_DIR"

echo "📁 Les rapports seront sauvés dans: $REPORT_DIR"
echo ""

get_filename() {
    local url=$1
    local path=$(echo "$url" | sed 's|http://localhost:3000||' | sed 's|^/||' | sed 's|/$||')
    if [ -z "$path" ]; then
        echo "home"
    else
        echo "${path//\//_}"
    fi
}

for url in "${URLS[@]}"; do
    filename=$(get_filename "$url")
    echo "📊 Analyse de: $url"

    lighthouse "$url" \
        --output=html \
        --output-path="$REPORT_DIR/${filename}-report.html" \
        --chrome-flags='--headless' \
        --quiet

    if [ $? -eq 0 ]; then
        echo "✅ Rapport généré: ${filename}-report.html"
    else
        echo "❌ Erreur lors de l'analyse de $url"
    fi
    echo ""
done

echo "🎉 Audit terminé !"
echo "📁 Tous les rapports sont disponibles dans: $REPORT_DIR"
echo ""
echo "Pour visualiser les rapports, ouvrez les fichiers HTML dans votre navigateur :"
ls -la "$REPORT_DIR"/*.html
