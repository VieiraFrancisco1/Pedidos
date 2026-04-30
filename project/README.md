# Sistema de pedidos em PHP puro

Projeto didático com arquitetura em camadas, persistência em JSON e frontend simples consumindo uma API REST sem frameworks.

## 🏗 Arquitetura

### Camadas de negócio

- **Models** (`backend/models`) - Entidades do domínio (Produto, ItemPedido, Pedido)
- **Repositories** (`backend/repositories`) - Persistência em JSON (PedidoRepository)
- **Services** (`backend/services`) - Regras de negócio (PedidoService, PedidoEventDispatcher)
- **Controllers** (`backend/controllers`) - Camada de entrada da API (PedidoController)

### Padrões de projeto aplicados

Veja [PATTERNS.md](PATTERNS.md) para documentação detalhada com exemplos:

- **Factory** - `PedidoFactory`, `DiscountStrategyFactory`
  - Encapsula lógica de criação com validação
  - Garante objetos sempre em estado válido

- **Strategy** - `DescontoStrategy` com 3 implementações
  - `SemDescontoStrategy` - sem desconto (0%)
  - `DescontoPercentualStrategy` - desconto fixo
  - `DescontoProgressivoStrategy` - desconto baseado no total
  - Permite trocar comportamento em tempo de execução

- **Singleton** - `PedidoRepository::getInstance()`
  - Garante uma única instância do repositório
  - Evita múltiplas conexões

- **Repository** - `PedidoRepository`
  - Abstrai persistência (JSON, MySQL, etc)
  - Interface agnóstica ao armazenamento (4 métodos: listar, salvar, deletar, buscarPorId)

- **Observer** - `PedidoEventDispatcher` + `PedidoLogObserver`
  - Notificação desacoplada de eventos
  - Fácil adicionar novos observers (email, webhook, etc)
  - Service não conhece os observers

- **Dependency Injection** - `Container`
  - Gerencia dependências centralizadamente
  - Facilita testes com mocks

### Infraestrutura

- **Container** (`backend/Container.php`) - Injeção de dependências centralizada
- **Router** (`backend/Router.php`) - Roteamento de requisições
- **JsonResponse** (`backend/JsonResponse.php`) - Respostas HTTP padronizadas (elimina duplicação)
- **JsonRequest** (`backend/JsonRequest.php`) - Leitura de corpo JSON (elimina duplicação)
- **bootstrap.php** - Autoloader PSR-4

### Frontend

- **index.html** - Interface em HTML/CSS (estático)
- **script.js** - Consumo da API via `fetch` sem dependências

## 🚀 Produção

**Aplicação ao vivo:**
- Frontend: [https://pedidosweb1.netlify.app/](https://pedidosweb1.netlify.app/)
- API: [https://pedidos-rg35.onrender.com/pedidos](https://pedidos-rg35.onrender.com/pedidos)

## Como executar

### Backend (desenvolvimento)

```bash
php -S localhost:8000 -t backend backend/router.php
```

A API estará disponível em `http://localhost:8000/pedidos`

### Frontend (desenvolvimento)

Abra `frontend/index.html` no navegador. 

Se necessário, servir com PHP:
```bash
php -S localhost:8001 -t frontend
```

### Docker

```bash
docker compose up --build
```

Acesse `http://localhost:8000` no navegador

### Testes

```bash
php tests/run-all-tests.php
```

Valida:
- Cálculo do total do pedido
- Aplicação de descontos (percentual, mínimo, máximo)
- Múltiplos itens
- Arredondamento de centavos
- **Padrões de projeto** (Factory, Strategy, Singleton, Repository, Observer)
- **Integração frontend-backend** (requisições, respostas, erros)

## 📚 Documentação

- [PATTERNS.md](PATTERNS.md) - Design patterns com exemplos
- [INTEGRATION.md](INTEGRATION.md) - Integração frontend ↔ backend

## Endpoints

- `GET /pedidos` - lista pedidos
- `POST /pedidos` - cria pedido
- `DELETE /pedidos?id=1` - remove pedido

## Payload esperado

```json
{
  "desconto_percentual": 10,
  "itens": [
    {
      "produto": {
        "id": 1,
        "nome": "Notebook",
        "preco": 3500.00
      },
      "quantidade": 1
    }
  ]
}
```

## WhatsApp

O frontend gera links no formato:

```text
https://wa.me/NUMERO?text=mensagem
```

A mensagem inclui o resumo do pedido, total bruto, desconto e total final.
