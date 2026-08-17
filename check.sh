#!/bin/bash

# Routine qualité du paquet.
#
# Le script joue toutes ses étapes avant de conclure : un jeton oublié n'empêche pas de voir
# aussi un contraste insuffisant. Seul le bilan final fixe le code de sortie.
#
# Ce que le paquet livre tient dans des feuilles et un gabarit : ce sont eux que les étapes
# mesurent — les couleurs qu'ils portent, et le catalogue qu'ils montrent.
#
# Aucune étape n'exige `composer install` : tout ce que la routine emploie vit dans le dépôt,
# ou s'installe lui-même.
#
# Usage :  ./check.sh

# ═══════════════════════════════════════════════════════════════════════════════════════════
# Configuration — la seule zone à adapter.
# ═══════════════════════════════════════════════════════════════════════════════════════════

QA_DIR="qa"

# Les feuilles que le contrôle de contraste mesure. La routine du paquet ne juge que le
# paquet ; celles d'une application se passent en argument à `qa/contrast.py`.
FEUILLES_MESUREES=(public/tokens.css public/themes/*.css)

# ═══════════════════════════════════════════════════════════════════════════════════════════
# Mécanique — rien à adapter en dessous.
# ═══════════════════════════════════════════════════════════════════════════════════════════

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR" || exit 1

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

for outil in php python3; do
    if ! command -v "$outil" >/dev/null; then
        echo -e "${RED}✘ $outil est absent.${NC} La routine ne peut pas juger sans lui."
        exit 1
    fi
done

echo -e "${YELLOW}Routine qualité${NC}"

# ── 1. Composer : cohérence du manifeste ───────────────────────────────────────────────────
# `audit` ne tourne que si les versions sont figées. Un paquet ne fige pas les siennes — c'est
# l'application qui l'installe qui arbitre —, donc `composer.lock` est absent le plus souvent,
# et l'audit n'aurait alors rien de réel à lire.
echo -e "\n${YELLOW}Étape 1 : cohérence du manifeste Composer…${NC}"
if ! command -v composer >/dev/null; then
    echo -e "${YELLOW}⚠ Composer absent : manifeste non vérifié.${NC}"
    COMPOSER_EXIT=0
else
    composer validate --strict --quiet
    COMPOSER_EXIT=$?
    if [ -f composer.lock ]; then
        composer audit --no-interaction || COMPOSER_EXIT=1
    fi
    if [ $COMPOSER_EXIT -eq 0 ]; then
        echo -e "${GREEN}✔ Manifeste OK.${NC}"
    else
        echo -e "${RED}✘ Composer signale un problème dans le manifeste.${NC}"
    fi
fi

# ── 2. Syntaxe PHP ─────────────────────────────────────────────────────────────────────────
# Le paquet n'embarque pas d'analyse statique : ce contrôle-ci est le plancher, et il ne dit
# qu'une chose — aucun fichier n'est cassé au point de ne pas se charger.
echo -e "\n${YELLOW}Étape 2 : syntaxe PHP…${NC}"
SYNTAXE=$(find src -name '*.php' -exec php -l {} \; 2>&1 | grep -v '^No syntax errors' || true)
if [ -z "$SYNTAXE" ]; then
    echo -e "${GREEN}✔ Syntaxe OK.${NC}"
    SYNTAXE_EXIT=0
else
    echo -e "${RED}✘ Erreurs de syntaxe :${NC}"
    echo "$SYNTAXE"
    SYNTAXE_EXIT=1
fi

# ── 3. Contraste ───────────────────────────────────────────────────────────────────────────
# Chaque paire texte/fond atteint son seuil, sur chaque palette, et pour chacune des
# combinaisons que la grammaire rend possibles. Mesuré, jamais estimé.
echo -e "\n${YELLOW}Étape 3 : contraste des palettes…${NC}"
python3 "$QA_DIR/contrast.py" "${FEUILLES_MESUREES[@]}"
CONTRASTE_EXIT=$?
if [ $CONTRASTE_EXIT -eq 0 ]; then
    echo -e "${GREEN}✔ Toutes les paires atteignent leur seuil.${NC}"
else
    echo -e "${RED}✘ Des paires passent sous leur seuil.${NC}"
fi

# ── 4. Catalogue ───────────────────────────────────────────────────────────────────────────
# Un composant ou un jeton que la vitrine ne montre pas n'est pas cassé : il est introuvable,
# et rien d'autre que ce rapprochement ne le dit.
echo -e "\n${YELLOW}Étape 4 : le catalogue montre tout ce qu'il déclare…${NC}"
python3 "$QA_DIR/catalogue.py"
CATALOGUE_EXIT=$?
if [ $CATALOGUE_EXIT -eq 0 ]; then
    echo -e "${GREEN}✔ Catalogue complet et feuilles cohérentes.${NC}"
else
    echo -e "${RED}✘ Le catalogue ne montre pas tout, ou une feuille ne se tient pas.${NC}"
fi

# ── 5. Fonctions de mise au point oubliées ─────────────────────────────────────────────────
# Les deux langages du paquet, parce qu'un `console.log` oublié dans un contrôleur Stimulus
# part chez tous les visiteurs de toutes les applications qui l'installent.
echo -e "\n${YELLOW}Étape 5 : détection des fonctions de debug…${NC}"
DEBUG_PHP=$(grep -rE "(^|[^a-zA-Z_>])(var_dump|dump|dd)\(" src config \
    --include="*.php" 2>/dev/null || true)
DEBUG_JS=$(grep -rnE "(^|[^a-zA-Z_.])(console\.(log|debug|warn)|debugger)\b" public/controllers \
    --include="*.js" 2>/dev/null || true)

if [ -z "$DEBUG_PHP" ] && [ -z "$DEBUG_JS" ]; then
    echo -e "${GREEN}✔ Aucune fonction de debug oubliée.${NC}"
    DEBUG_EXIT=0
else
    echo -e "${RED}✘ Fonctions de debug trouvées :${NC}"
    [ -n "$DEBUG_PHP" ] && echo "$DEBUG_PHP"
    [ -n "$DEBUG_JS" ] && echo "$DEBUG_JS"
    DEBUG_EXIT=1
fi

# ── 6. Secrets ─────────────────────────────────────────────────────────────────────────────
# `detect` scanne l'historique complet, pas seulement le diff : un secret retiré d'un fichier
# reste dans les objets git.
echo -e "\n${YELLOW}Étape 6 : détection de secrets (Gitleaks)…${NC}"
GITLEAKS="$QA_DIR/bin/gitleaks"
if [ ! -x "$GITLEAKS" ]; then
    echo -e "${YELLOW}→ Installation de Gitleaks…${NC}"
    "$QA_DIR/install-gitleaks.sh" || echo -e "${RED}✘ Installation impossible.${NC}"
fi
if [ ! -x "$GITLEAKS" ]; then
    GITLEAKS_EXIT=1
elif [ -d .git ]; then
    "$GITLEAKS" detect --config "$QA_DIR/.gitleaks.toml" --no-banner --redact 2>&1 | tail -15
    GITLEAKS_EXIT=${PIPESTATUS[0]}
else
    echo -e "${YELLOW}⚠ Pas de dépôt git ici : scan indicatif de l'arborescence.${NC}"
    "$GITLEAKS" detect --no-git --source . --config "$QA_DIR/.gitleaks.toml" --no-banner --redact 2>&1 | tail -5
    GITLEAKS_EXIT=0
fi
if [ "${GITLEAKS_EXIT:-0}" -eq 0 ]; then
    echo -e "${GREEN}✔ Gitleaks OK.${NC}"
else
    echo -e "${RED}✘ Secret potentiel détecté dans l'historique.${NC}"
    echo -e "  ⚠️ Ne PAS l'ajouter à l'allowlist : le faire tourner chez son émetteur d'abord,"
    echo -e "     puis réécrire l'historique. Un secret « supprimé » reste dans les objets git."
fi

# ── Bilan ──────────────────────────────────────────────────────────────────────────────────
echo -e "\n----------------------------------------"
if [ "${COMPOSER_EXIT:-0}" -eq 0 ] && [ "${SYNTAXE_EXIT:-0}" -eq 0 ] \
   && [ "${CONTRASTE_EXIT:-0}" -eq 0 ] && [ "${CATALOGUE_EXIT:-0}" -eq 0 ] \
   && [ "${DEBUG_EXIT:-0}" -eq 0 ] && [ "${GITLEAKS_EXIT:-0}" -eq 0 ]; then
    echo -e "${GREEN}TOUT EST OK — le paquet est publiable.${NC}"
    exit 0
else
    echo -e "${RED}CERTAINES ÉTAPES ONT ÉCHOUÉ — corriger avant de pousser.${NC}"
    exit 1
fi
