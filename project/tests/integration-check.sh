#!/bin/bash
# INTEGRATION TEST CHECKLIST
# Valida que a integração frontend-backend está funcionando

echo "=========================================="
echo "Frontend-Backend Integration Checklist"
echo "=========================================="
echo ""

API_URL="http://localhost:8000"
TESTS_PASSED=0
TESTS_FAILED=0

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para testar endpoint
test_endpoint() {
  local method=$1
  local endpoint=$2
  local data=$3
  local expected_status=$4
  local description=$5

  echo -n "Testing $description... "

  if [ -z "$data" ]; then
    response=$(curl -s -w "%{http_code}" -X $method "$API_URL$endpoint" \
      -H "Content-Type: application/json")
  else
    response=$(curl -s -w "%{http_code}" -X $method "$API_URL$endpoint" \
      -H "Content-Type: application/json" \
      -d "$data")
  fi

  status_code="${response: -3}"
  body="${response%???}"

  if [ "$status_code" = "$expected_status" ]; then
    echo -e "${GREEN}✓ PASS${NC} (HTTP $status_code)"
    TESTS_PASSED=$((TESTS_PASSED + 1))

    # Validar JSON válido
    if echo "$body" | jq . > /dev/null 2>&1; then
      echo "  └─ JSON válido"
    else
      echo "  └─ ${RED}⚠ JSON inválido${NC}"
    fi
  else
    echo -e "${RED}✗ FAIL${NC} (esperado $expected_status, recebido $status_code)"
    TESTS_FAILED=$((TESTS_FAILED + 1))
    echo "  └─ Body: $body"
  fi
  echo ""
}

# 1. Verificar se API está online
echo "1. Verificando conectividade..."
if curl -s "$API_URL" > /dev/null 2>&1; then
  echo -e "${GREEN}✓${NC} API está online em $API_URL"
else
  echo -e "${RED}✗${NC} API não está respondendo em $API_URL"
  echo "   Inicie com: php -S localhost:8000 -t backend backend/router.php"
  exit 1
fi
echo ""

# 2. Testar GET /
echo "2. Testando raiz da API..."
test_endpoint "GET" "/" "" "200" "GET / (root)"

# 3. Testar GET /pedidos (vazio inicialmente)
echo "3. Testando listagem de pedidos..."
test_endpoint "GET" "/pedidos" "" "200" "GET /pedidos (lista vazia)"

# 4. Testar POST /pedidos (criar)
echo "4. Testando criação de pedido..."
PEDIDO_JSON='{"desconto_percentual":10,"itens":[{"produto":{"id":1,"nome":"Notebook","preco":1000},"quantidade":1}]}'
test_endpoint "POST" "/pedidos" "$PEDIDO_JSON" "201" "POST /pedidos (criar pedido)"

# 5. Testar GET /pedidos (após criação)
echo "5. Verificando if pedido foi criado..."
test_endpoint "GET" "/pedidos" "" "200" "GET /pedidos (após criação)"

# 6. Testar DELETE /pedidos?id=1
echo "6. Testando remoção de pedido..."
test_endpoint "DELETE" "/pedidos?id=1" "" "200" "DELETE /pedidos?id=1"

# 7. Testar POST com dados inválidos
echo "7. Testando validação de entrada..."
INVALID_JSON='{"itens":[]}'
test_endpoint "POST" "/pedidos" "$INVALID_JSON" "422" "POST /pedidos (dados inválidos)"

# 8. Testar JSON inválido
echo "8. Testando rejeição de JSON inválido..."
response=$(curl -s -w "%{http_code}" -X POST "$API_URL/pedidos" \
  -H "Content-Type: application/json" \
  -d "invalid json{")
status_code="${response: -3}"
if [ "$status_code" = "500" ] || [ "$status_code" = "422" ]; then
  echo -e "${GREEN}✓${NC} JSON inválido rejeitado (HTTP $status_code)"
  TESTS_PASSED=$((TESTS_PASSED + 1))
else
  echo -e "${RED}✗${NC} JSON inválido não foi rejeitado"
  TESTS_FAILED=$((TESTS_FAILED + 1))
fi
echo ""

# 9. Testar CORS Headers
echo "9. Testando Headers CORS..."
headers=$(curl -s -I "$API_URL/pedidos" | grep -i access-control)
if echo "$headers" | grep -q "access-control-allow-origin"; then
  echo -e "${GREEN}✓${NC} CORS headers presentes"
  echo "  $headers"
  TESTS_PASSED=$((TESTS_PASSED + 1))
else
  echo -e "${RED}✗${NC} CORS headers ausentes"
  TESTS_FAILED=$((TESTS_FAILED + 1))
fi
echo ""

# 10. Testar OPTIONS (preflight)
echo "10. Testando preflight (OPTIONS)..."
test_endpoint "OPTIONS" "/pedidos" "" "204" "OPTIONS /pedidos (preflight)"

# Resumo final
echo "=========================================="
echo "Resumo dos Testes"
echo "=========================================="
echo -e "Passou:  ${GREEN}$TESTS_PASSED${NC}"
echo -e "Falhou:  ${RED}$TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
  echo -e "${GREEN}✓ Todos os testes passaram!${NC}"
  exit 0
else
  echo -e "${RED}✗ Alguns testes falharam${NC}"
  exit 1
fi
