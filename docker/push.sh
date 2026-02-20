#!/bin/bash
# ============================================================
# push.sh — Faz build da imagem e envia para Docker Hub + Git
#
# Pré-requisitos (RODAR UMA VEZ):
#   docker login
#   git remote add origin git@github.com:SEU_USER/crm.git
#
# Uso:
#   bash docker/push.sh                    (usa tag "latest")
#   bash docker/push.sh v1.2.3             (usa tag específica)
# ============================================================
set -euo pipefail

# ── Configurações — PREENCHA AQUI ─────────────────────────
DOCKERHUB_USER="matolado"
IMAGE_NAME="crm"
GIT_REMOTE="https://github.com/matheusrossidev/crmlaravel.git"
GIT_BRANCH="main"
# ──────────────────────────────────────────────────────────

TAG="${1:-latest}"
FULL_IMAGE="${DOCKERHUB_USER}/${IMAGE_NAME}:${TAG}"

echo ""
echo "======================================="
echo "  Push → Docker Hub + Git"
echo "  Imagem : ${FULL_IMAGE}"
echo "  Branch : ${GIT_BRANCH}"
echo "======================================="
echo ""

# ── 1. Git push ──────────────────────────────────────────
echo "[1/3] Enviando código para o Git..."
git add -A
git status --short
git commit -m "chore: deploy $(date '+%Y-%m-%d %H:%M')" 2>/dev/null || echo "      (nada novo para commitar)"
git push origin "${GIT_BRANCH}"
echo "      ✓ Git push concluído"

# ── 2. Build da imagem ───────────────────────────────────
echo ""
echo "[2/3] Building imagem Docker: ${FULL_IMAGE}..."
docker build \
    --platform linux/amd64 \
    --tag "${FULL_IMAGE}" \
    --tag "${DOCKERHUB_USER}/${IMAGE_NAME}:latest" \
    .
echo "      ✓ Build concluído"

# ── 3. Push para Docker Hub ──────────────────────────────
echo ""
echo "[3/3] Enviando para Docker Hub..."
docker push "${FULL_IMAGE}"
if [ "${TAG}" != "latest" ]; then
    docker push "${DOCKERHUB_USER}/${IMAGE_NAME}:latest"
fi
echo "      ✓ Push concluído"

echo ""
echo "======================================="
echo "  ✓ Tudo enviado!"
echo ""
echo "  Imagem: ${FULL_IMAGE}"
echo "  No Portainer, atualize a stack e"
echo "  clique em 'Redeploy' para puxar"
echo "  a nova imagem."
echo "======================================="
