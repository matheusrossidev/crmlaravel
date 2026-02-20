#!/bin/bash
# ============================================================
# deploy.sh — Script de deploy na VPS
# Uso: bash docker/deploy.sh
# ============================================================
set -euo pipefail

IMAGE_NAME="${APP_IMAGE:-crm-app:latest}"
DOMAIN="syncro.matheusrossi.com.br"

echo ""
echo "=================================="
echo "  CRM — Deploy (${DOMAIN})"
echo "=================================="
echo ""

# 1. Verificar .env
if [ ! -f ".env" ]; then
    echo "[ERROR] Arquivo .env não encontrado!"
    echo "        cp .env.production .env && nano .env"
    exit 1
fi

# 2. Verificar redes externas necessárias
echo "[1/5] Verificando redes Docker..."

for NET in traefik_public shared_network; do
    if ! docker network inspect "${NET}" &>/dev/null; then
        echo "      Criando rede '${NET}'..."
        docker network create "${NET}"
    else
        echo "      ✓ ${NET}"
    fi
done

echo ""
echo "      IMPORTANTE: Se WAHA não está na rede shared_network:"
echo "      docker network connect shared_network <container_waha>"
echo ""

# 3. Build da imagem
echo "[2/5] Building imagem ${IMAGE_NAME}..."
docker build -t "${IMAGE_NAME}" .
echo "      ✓ Build concluído"

# 4. Subir a stack
echo ""
echo "[3/5] Iniciando stack..."
docker compose up -d --remove-orphans

# 5. Aguardar e mostrar logs do app
echo ""
echo "[4/5] Aguardando inicialização (30s)..."
sleep 30
docker compose logs --tail=40 app

# 6. Status
echo ""
echo "[5/5] Status:"
docker compose ps

echo ""
echo "=================================="
echo "  ✓ Deploy concluído!"
echo "  URL: https://${DOMAIN}"
echo "=================================="
echo ""
echo "Comandos úteis:"
echo "  Logs        → docker compose logs -f app queue"
echo "  Migrations  → docker compose exec app php artisan migrate"
echo "  Reiniciar   → docker compose restart app queue"
echo "  Tinker      → docker compose exec app php artisan tinker"
echo "  Rebuild     → docker build -t ${IMAGE_NAME} . && docker compose up -d app queue"
