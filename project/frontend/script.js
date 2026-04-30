const API_BASE_URL = 'http://localhost:8000';

const state = {
  pedidos: [],
  whatsappNumero: localStorage.getItem('whatsappNumero') || '',
};

const pedidoForm = document.getElementById('pedidoForm');
const itemsContainer = document.getElementById('itemsContainer');
const ordersContainer = document.getElementById('ordersContainer');
const addItemButton = document.getElementById('addItemButton');
const reloadButton = document.getElementById('reloadButton');
const descontoPercentualInput = document.getElementById('descontoPercentual');
const whatsappNumeroInput = document.getElementById('whatsappNumero');

function formatMoney(value) {
  return new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
  }).format(Number(value || 0));
}

function getWhatsappNumero() {
  return whatsappNumeroInput.value.trim().replace(/\D/g, '');
}

function saveWhatsappNumero() {
  state.whatsappNumero = getWhatsappNumero();
  localStorage.setItem('whatsappNumero', state.whatsappNumero);
  renderPedidos();
}

function createItemRow(item = {}) {
  const wrapper = document.createElement('div');
  wrapper.className = 'item-row';

  wrapper.innerHTML = `
    <div class="field">
      <label class="mini-label">ID</label>
      <input type="number" min="1" step="1" class="produto-id" value="${item.id ?? ''}" placeholder="1" />
    </div>
    <div class="field">
      <label class="mini-label">Nome do produto</label>
      <input type="text" class="produto-nome" value="${item.nome ?? ''}" placeholder="Ex.: Notebook" />
    </div>
    <div class="field">
      <label class="mini-label">Preço</label>
      <input type="number" min="0" step="0.01" class="produto-preco" value="${item.preco ?? ''}" placeholder="199.90" />
    </div>
    <div class="field">
      <label class="mini-label">Quantidade</label>
      <input type="number" min="1" step="1" class="item-quantidade" value="${item.quantidade ?? 1}" placeholder="1" />
    </div>
    <button type="button" class="button button--danger button--small remove-item">Remover</button>
  `;

  wrapper.querySelector('.remove-item').addEventListener('click', () => {
    if (itemsContainer.children.length === 1) {
      wrapper.querySelector('.produto-id').value = '';
      wrapper.querySelector('.produto-nome').value = '';
      wrapper.querySelector('.produto-preco').value = '';
      wrapper.querySelector('.item-quantidade').value = '1';
      return;
    }

    wrapper.remove();
  });

  return wrapper;
}

function ensureOneItemRow() {
  if (itemsContainer.children.length === 0) {
    itemsContainer.appendChild(createItemRow());
  }
}

function getFormItems() {
  return [...itemsContainer.querySelectorAll('.item-row')].map((row) => ({
    produto: {
      id: row.querySelector('.produto-id').value ? Number(row.querySelector('.produto-id').value) : null,
      nome: row.querySelector('.produto-nome').value.trim(),
      preco: Number(row.querySelector('.produto-preco').value),
    },
    quantidade: Number(row.querySelector('.item-quantidade').value),
  }));
}

function buildWhatsappMessage(pedido) {
  const linhas = [
    `Pedido #${pedido.id}`,
    '',
    'Itens:',
    ...pedido.itens.map((item) => `- ${item.produto.nome} x${item.quantidade} = ${formatMoney(item.subtotal)}`),
    '',
    `Total bruto: ${formatMoney(pedido.total_bruto)}`,
    `Desconto: ${pedido.desconto_percentual}%`,
    `Total final: ${formatMoney(pedido.total)}`,
  ];

  return linhas.join('\n');
}

function buildWhatsappLink(pedido) {
  const numero = state.whatsappNumero;

  if (!numero) {
    return null;
  }

  const mensagem = encodeURIComponent(buildWhatsappMessage(pedido));
  return `https://wa.me/${numero}?text=${mensagem}`;
}

function renderPedidos() {
  if (!state.pedidos.length) {
    ordersContainer.innerHTML = '<div class="empty-state">Nenhum pedido encontrado. Crie o primeiro pedido no formulário ao lado.</div>';
    return;
  }

  ordersContainer.innerHTML = '';

  state.pedidos.forEach((pedido) => {
    const card = document.createElement('section');
    card.className = 'order-card';

    const whatsappLink = buildWhatsappLink(pedido);

    card.innerHTML = `
      <div class="order-card__top">
        <div>
          <div class="chip">Pedido #${pedido.id}</div>
          <div class="order-card__meta">
            <span>Itens: ${pedido.itens.length}</span>
            <span>Desconto: ${pedido.desconto_percentual}%</span>
            <span>Total: ${formatMoney(pedido.total)}</span>
          </div>
        </div>
        <button type="button" class="button button--danger button--small delete-order">Remover</button>
      </div>
      <div class="items-list">
        ${pedido.itens
          .map(
            (item) => `
              <div class="item-line">
                <div>
                  <strong>${item.produto.nome}</strong>
                  <span class="muted">ID: ${item.produto.id ?? '-'} | Preço: ${formatMoney(item.produto.preco)}</span>
                </div>
                <div>
                  <strong>${item.quantidade} un.</strong>
                  <span class="muted">Subtotal: ${formatMoney(item.subtotal)}</span>
                </div>
              </div>
            `,
          )
          .join('')}
      </div>
      <div class="footer-actions">
        <div class="hint">Total bruto: ${formatMoney(pedido.total_bruto)}</div>
        ${whatsappLink ? `<a class="button button--primary button--small" href="${whatsappLink}" target="_blank" rel="noreferrer">Abrir WhatsApp</a>` : '<span class="hint">Informe um número de WhatsApp para gerar o link.</span>'}
      </div>
    `;

    card.querySelector('.delete-order').addEventListener('click', async () => {
      await deletePedido(pedido.id);
    });

    ordersContainer.appendChild(card);
  });
}

async function loadPedidos() {
  ordersContainer.innerHTML = '<div class="empty-state">Carregando pedidos...</div>';

  try {
    const response = await fetch(`${API_BASE_URL}/pedidos`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const payload = await response.json();

    if (!payload.success) {
      throw new Error(payload.message || 'Falha ao carregar pedidos.');
    }

    state.pedidos = payload.data || [];
    renderPedidos();
  } catch (error) {
    console.error('Erro ao carregar pedidos:', error);
    ordersContainer.innerHTML = `<div class="empty-state">❌ ${error.message}</div>`;
  }
}

async function deletePedido(id) {
  if (!confirm('Deseja realmente remover este pedido?')) {
    return;
  }

  try {
    const response = await fetch(`${API_BASE_URL}/pedidos?id=${encodeURIComponent(id)}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const payload = await response.json();

    if (!payload.success) {
      throw new Error(payload.message || 'Falha ao remover pedido.');
    }

    await loadPedidos();
  } catch (error) {
    console.error('Erro ao remover pedido:', error);
    alert(`❌ Erro ao remover: ${error.message}`);
  }
}

async function submitPedido(event) {
  event.preventDefault();

  const itens = getFormItems().filter(
    (item) => item.produto.nome && Number.isFinite(item.produto.preco) && item.produto.preco >= 0 && Number.isFinite(item.quantidade) && item.quantidade > 0,
  );

  if (!itens.length) {
    alert('❌ Adicione ao menos um item válido.');
    return;
  }

  const payload = {
    itens,
    desconto_percentual: Number(descontoPercentualInput.value || 0),
  };

  try {
    const response = await fetch(`${API_BASE_URL}/pedidos`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message || 'Falha ao criar pedido.');
    }

    pedidoForm.reset();
    itemsContainer.innerHTML = '';
    ensureOneItemRow();
    whatsappNumeroInput.value = state.whatsappNumero;
    await loadPedidos();
    alert('✓ Pedido criado com sucesso!');
  } catch (error) {
    console.error('Erro ao criar pedido:', error);
    alert(`❌ Erro ao criar pedido: ${error.message}`);
  }
}

addItemButton.addEventListener('click', () => {
  itemsContainer.appendChild(createItemRow());
});

reloadButton.addEventListener('click', loadPedidos);
pedidoForm.addEventListener('submit', submitPedido);
whatsappNumeroInput.addEventListener('input', saveWhatsappNumero);

whatsappNumeroInput.value = state.whatsappNumero;
ensureOneItemRow();
loadPedidos();
