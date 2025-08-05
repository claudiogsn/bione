const baseUrl = window.location.hostname !== 'localhost'
    ? 'https://bionetecnologia.com.br/crm/api/v1/index.php'
    : 'http://localhost/bione/api/v1/index.php';

const baseUrlRedirect = window.location.hostname !== 'localhost'
    ? 'https://bionetecnologia.com.br/crm/external'
    : 'http://localhost/bione/external';

const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');
const documento = urlParams.get('documento');
const user = urlParams.get('user');

if (!token) {
    Swal.fire('Erro', 'Token de autenticação ausente na URL.', 'error');
    throw new Error('Token ausente');
}

// Carregar dados se controle vier na URL
if (documento) {
    axios.post(`${baseUrl}`, {
        method: 'getOrderDetailsByDocumento',
        token: token,
        data: { documento: documento }
    }).then(response => {
        if (response.data && response.data.success && response.data.details) {
            preencherDadosDaOrdem(response.data.details);
        }
        else {
            Swal.fire('Erro', response.data.message || 'Erro ao carregar ordem.', 'error');
        }
    }).catch(err => {
        console.error(err);
        Swal.fire('Erro', 'Falha ao consultar a ordem.', 'error');
    });
}

// Salvar ou atualizar ordem
function salvarProposta() {
    const evento_id = $('#evento_id').val();
    const cliente_id = $('#cliente_id').val();
    const data_montagem = $('#data_montagem').val();
    const data_recolhimento = $('#data_recolhimento').val();
    const contato_montagem = $('#contato_montagem').val();
    const local_montagem = $('#local').val();
    const endereco = $('#evento_endereco').val();
    const place_url = $('#evento_place_url').val();
    const observacao = $('#obs').val();

    // Verificações obrigatórias
    if (!evento_id || !cliente_id || !data_montagem || !data_recolhimento || !contato_montagem || !local_montagem || !endereco || !place_url) {
        return Swal.fire('Atenção', 'Preencha todos os campos obrigatórios.', 'warning');
    }

    const itens = [];

    $('#tabela-itens tbody tr').each(function () {
        const row = $(this);
        itens.push({
            material_id: parseInt(row.find('.material_id').val()),
            descricao: row.find('td').eq(1).text().trim(),
            valor: parseFloat(row.find('.valor_unitario').val()) || 0,
            custo: 0,
            dias_uso: parseInt(row.find('.dias_uso').val()) || 1,
            data_inicial: row.find('.data_inicio').val(),
            data_final: row.find('.data_fim').val(),
            status: 'ativo',
            quantidade: parseInt(row.find('.quantidade').val()) || 1,
            observacao: row.find('.observacao_item').val()?.trim() || null
        });

    });

    if (itens.length === 0 ) {
        return Swal.fire('Atenção', 'Adicione pelo menos um item', 'warning');
    }

    const payments = [];

    $('#tabela-pagamentos tbody tr').each(function () {
        const row = $(this);
        payments.push({
            forma_pg: row.find('.metodo_pagamento_id').val(),
            valor_pg: parseFloat(row.find('.valor_pagamento').val()) || 0,
            data_prog: row.find('.data_pagamento').val(),
            data_pg: row.find('.data_pagamento').val(),
            status: 'confirmado'
        });
    });

    const payload = {
        proposta: {
            ...(documento && { documento: documento }),
            evento_id,
            cliente_id,
            data_montagem,
            data_recolhimento,
            status: '1',
            contato_montagem,
            local_montagem,
            endereco,
            place_url,
            observacao
        },
        itens,
        payments
    };

    axios.post(`${baseUrl}`, {
        method: 'createOrUpdateProposta',
        token: token,
        data: payload
    }).then(response => {
        if (response.data && response.data.success) {
            Swal.fire('Sucesso', 'Ordem salva com sucesso!', 'success').then(() => {
                window.location.href = `${baseUrlRedirect}/listOrdem.html?user=${user}&token=${token}`;
            });
        } else {
            Swal.fire('Erro', response.data.message || 'Erro ao salvar ordem.', 'error');
        }
    }).catch(error => {
        console.error(error);
        Swal.fire('Erro', 'Falha ao enviar dados da ordem.', 'error');
    });
}

function abrirModalClientes() {
    $('#modalClientes').modal('show');
    $('#tabela-clientes tbody').empty();

    axios.post(`${baseUrl}`, {
        method: 'listClients',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.clients)) {
            res.data.clients.forEach(cli => {
                $('#tabela-clientes tbody').append(`
                    <tr>
                        <td>${cli.nome}</td>
                        <td>${cli.cpf_cnpj || '-'}</td>
                        <td><a href="javascript:void(0)" onclick="selecionarCliente(${cli.id}, '${cli.nome}')"><i class="fa fa-plus green green"></i></a></td>
                    </tr>
                `);
            });
        }
    });
}

function selecionarCliente(id, nome) {
    $('#cliente_id').val(id);
    $('#cliente_nome').val(nome);
    $('#modalClientes').modal('hide');
}

function abrirModalEventos() {
    $('#modalEventos').modal('show');
    $('#tabela-eventos tbody').empty();

    axios.post(`${baseUrl}`, {
        method: 'listEvents',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.events)) {
            res.data.events.forEach(ev => {
                $('#tabela-eventos tbody').append(`
                    <tr>
                        <td>${ev.nome}</td>
                        <td>${ev.data_inicio}</td>
                        <td>${ev.endereco}</td>
                        <td><a href="javascript:void(0)" onclick="selecionarEvento(${ev.id}, '${ev.nome}', '${ev.endereco}', '${ev.place_url || ''}', '${ev.data_inicio}', '${ev.data_fim}')"><i class="fa fa-plus green"></i></a></td>
                    </tr>
                `);
            });
        }
    });
}

function selecionarEvento(id, nome, endereco, place_url, data_inicio, data_fim) {
    $('#evento_id').val(id);
    $('#evento_nome').val(nome);
    $('#evento_endereco').val(endereco);
    $('#evento_place_url').val(place_url);

    // Converte as datas do evento e aplica os ajustes
    const dataInicioEvento = new Date(data_inicio);
    const dataFimEvento = new Date(data_fim);

    const dataMontagem = new Date(dataInicioEvento);
    dataMontagem.setDate(dataMontagem.getDate() - 1);

    const dataRecolhimento = new Date(dataFimEvento);
    dataRecolhimento.setDate(dataRecolhimento.getDate() + 1);

    // Formata as datas para input type="datetime-local"
    function formatDateTimeInput(date) {
        const pad = n => n.toString().padStart(2, '0');
        const dataFormatada = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        console.log(`Data formatada para input: ${dataFormatada}`);
        return dataFormatada;
    }

    $('#data_montagem').val(formatDateTimeInput(dataMontagem));
    $('#data_recolhimento').val(formatDateTimeInput(dataRecolhimento));

    $('#modalEventos').modal('hide');

    // Armazena as datas globais do evento para uso posterior em itens/serviços
    window.DATA_INICIO_EVENTO = data_inicio;
    window.DATA_FIM_EVENTO = data_fim;
}

$(document).on('click', '.remover-linha', function () {
    $(this).closest('tr').remove();
    atualizarTotal();
});

function atualizarTotaisOS() {
    let total = 0;

    // Soma dos itens
    $('#tabela-itens tbody tr').each(function () {
        const qtd = parseInt($(this).find('.quantidade').val()) || 0;
        const unit = parseFloat($(this).find('.valor_unitario').val()) || 0;
        const dias = parseInt($(this).find('.dias_uso').val()) || 1;
        total += qtd * unit * dias;
    });

    // Soma dos serviços
    $('#tabela-servicos tbody tr').each(function () {
        const qtd = parseInt($(this).find('.quantidade_servico').val()) || 1;
        const unit = parseFloat($(this).find('.valor_unitario_servico').val()) || 0;
        const dias = parseInt($(this).find('.dias_uso_servico').val()) || 1;
        total += qtd * unit * dias;
    });

    animateTotalValor(total);
}

function animateTotalValor(finalValue) {
    const element = document.getElementById('total_os');
    const current = parseFloat(element.innerText.replace(/\./g, '').replace(',', '.')) || 0;
    const duration = 600;
    const frameRate = 30;
    const totalFrames = duration / (1000 / frameRate);
    let frame = 0;

    const easeOut = t => 1 - Math.pow(1 - t, 3); // easing suave

    const interval = setInterval(() => {
        frame++;
        const progress = easeOut(frame / totalFrames);
        const currentValue = current + (finalValue - current) * progress;

        element.innerText = `R$ ${currentValue.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;

        if (frame >= totalFrames) clearInterval(interval);
    }, 1000 / frameRate);
}

function atualizarTotal() {
    let totalItens = 0;
    $('#tabela-itens tbody tr').each(function () {
        const qtde = parseFloat($(this).find('.quantidade').val()) || 0;
        const valor = parseFloat($(this).find('.valor_unitario').val()) || 0;
        totalItens += qtde * valor;
    });
    let totalPagamentos = 0;
    $('#tabela-pagamentos tbody tr').each(function () {
        totalPagamentos += parseFloat($(this).find('.valor_pago').val()) || 0;
    });

    $('#total-itens').text(totalItens.toFixed(2));
    $('#total-servicos').text(totalServicos.toFixed(2));
    $('#total-pagamentos').text(totalPagamentos.toFixed(2));
}

$(document).on('input', '.quantidade, .valor_unitario, .valor_servico, .valor_pago', atualizarTotal);

function retornarLista() {
    window.location.href = `${baseUrlRedirect}/listOrdem.html?user=${user}&token=${token}`;
}

const formatDateOnly = (date) => {
    date = new Date(date);
    const pad = n => n.toString().padStart(2, '0');
    const dataFormatada = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    console.log(`Data formatada para input: ${dataFormatada}`);
    return dataFormatada;
};

$('#modalCadastroIframe').on('hidden.bs.modal', function () {
    if ($('#modalClientes').hasClass('in')) {
        abrirModalClientes(); // ou atualizar DataTable
    }
    if ($('#modalEventos').hasClass('in')) {
        abrirModalEventos(); // ou atualizar DataTable
    }
});

function abrirCadastroIframe(titulo, baseUrl) {
    const urlComToken = `${baseUrl}?token=${token}`;

    $('#iframeCadastro').attr('src', urlComToken);
    $('#iframeModalTitle').text(titulo);
    $('#modalCadastroIframe').modal('show');
}

$('#selectMaterial').on('change', function () {
    const selected = $(this).find(':selected');
    const materialId = selected.val();
    if (!materialId) return $('#formDadosItem').hide();

    const nome = selected.data('nome');
    const valor = selected.data('valor') || 0;

    const dataInicio = formatDateOnly(window.DATA_INICIO_EVENTO);
    const dataFim = formatDateOnly(window.DATA_FIM_EVENTO);

    $('#material_id_modal').val(materialId);
    $('#descricao_item_modal').val(nome);
    $('#observacao_item_modal').val('');
    $('#valor_item_modal').val(valor);
    $('#quantidade_item_modal').val(1);
    $('#data_inicio_item_modal').val(dataInicio);
    $('#data_fim_item_modal').val(dataFim);
    $('#subtotal_item_modal').text('R$ 0,00');

    $('#formDadosItem').show();
    calcularSubtotalModalItem();
});

function calcularSubtotalModalItem() {
    const qtd = parseFloat($('#quantidade_item_modal').val()) || 1;
    const valor = parseFloat($('#valor_item_modal').val()) || 0;

    const dtInicio = new Date($('#data_inicio_item_modal').val());
    const dtFim = new Date($('#data_fim_item_modal').val());

    let dias = Math.floor((dtFim - dtInicio) / (1000 * 60 * 60 * 24)) + 1;
    if (isNaN(dias) || dias < 1) dias = 1;

    const subtotal = qtd * valor * dias;

    $('#subtotal_item_modal').text(`R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`);
    $('#total_os_label').text($('#total_os').text()); // sincronia visual com total da OS
}

function adicionarItemDaModal() {
    const rowId = `item-${Date.now()}`;
    const id = $('#material_id_modal').val();
    const nome = $('#descricao_item_modal').val();
    const observacao = $('#observacao_item_modal').val();
    const valor = parseFloat($('#valor_item_modal').val()) || 0;
    const qtd = parseInt($('#quantidade_item_modal').val()) || 1;
    const dtInicio = $('#data_inicio_item_modal').val();
    const dtFim = $('#data_fim_item_modal').val();

    const dias = Math.floor((new Date(dtFim) - new Date(dtInicio)) / (1000 * 60 * 60 * 24)) + 1;
    const subtotal = valor * qtd * (dias > 0 ? dias : 1);

    $('#tabela-itens tbody').append(`
        <tr id="${rowId}">
            <td style="display:none;">
                <input type="hidden" class="material_id" value="${id}">
                <input type="hidden" class="data_inicio" value="${dtInicio}">
                <input type="hidden" class="data_fim" value="${dtFim}">
                <input type="hidden" class="dias_uso" value="${dias}">
            </td>
            <td>${nome}</td>
            <td>${observacao || ''}</td>
            <td>R$ ${valor.toFixed(2)}</td>
            <td>${qtd}</td>
            <td>${formatDateOnly(dtInicio)} - ${formatDateOnly(dtFim)}</td>
            <td style="display:none;"></td>
            <td>R$ ${subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
            <td><a href="javascript:void(0)" onclick="$(this).closest('tr').remove(); atualizarTotaisOS();"><i class="fa fa-trash red"></i></a></td>
        </tr>
    `);

    $('#modalNovoItem').modal('hide');
    atualizarTotaisOS();
}

function abrirModalItem() {
    $('#modalNovoItem').modal('show');
    $('#formDadosItem').hide();
    $('#selectMaterial').empty().append('<option value="">Selecione o material...</option>');

    axios.post(baseUrl, {
        method: 'listMaterials',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.materials)) {
            res.data.materials.forEach(mat => {
                $('#selectMaterial').append(`<option value="${mat.id}" data-nome="${mat.nome}" data-valor="${mat.valor_locacao}">${mat.nome} (${mat.unidade})</option>`);
            });

            $('#selectMaterial').select2({
                dropdownParent: $('#modalNovoItem')
            });
        }
    });
}

function abrirModalPagamento() {
    $('#modalNovoPagamento').modal('show');
    $('#selectMetodoPagamento').empty().append('<option value="">Selecione...</option>');
    $('#valor_pagamento_modal').val('0.00');
    $('#data_pagamento_modal').val(new Date().toISOString().split('T')[0]);
    $('#descricao_pagamento_modal').val('');
    $('#total_os_pagamento_label').text($('#total_os').text());

    axios.post(baseUrl, {
        method: 'listMetodosPagamento',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.metodos)) {
            res.data.metodos.forEach(m => {
                $('#selectMetodoPagamento').append(
                    `<option value="${m.id}" data-nome="${m.nome}" data-descricao="${m.descricao || ''}">${m.nome}</option>`
                );
            });

            $('#selectMetodoPagamento').select2({
                dropdownParent: $('#modalNovoPagamento')
            });
        }
    });
}

function adicionarPagamentoDaModal() {
    const metodoId = $('#selectMetodoPagamento').val();
    const metodoNome = $('#selectMetodoPagamento option:selected').text();
    const descricao = $('#descricao_pagamento_modal').val();
    const data = $('#data_pagamento_modal').val();
    const valor = parseFloat($('#valor_pagamento_modal').val()) || 0;
    const rowId = `pagamento-${metodoId}-${Date.now()}`;

    if (!metodoId || !data || valor <= 0) {
        return Swal.fire('Atenção', 'Preencha todos os campos do pagamento.', 'warning');
    }

    $('#tabela-pagamentos tbody').append(`
        <tr id="${rowId}">
            <td>
                <input type="hidden" class="metodo_pagamento_id" value="${metodoId}">
                ${metodoNome}
            </td>
            <td>
                <input type="hidden" class="data_pagamento" value="${data}">
                ${data}
            </td>
            <td>
                <input type="hidden" class="valor_pagamento" value="${valor}">
                R$ ${valor.toLocaleString('pt-BR', {minimumFractionDigits: 2})}
            </td>
            <td>
                <input type="hidden" class="descricao_pagamento" value="${descricao}">
                ${descricao}
            </td>
            <td><a href="javascript:void(0)" onclick="$(this).closest('tr').remove(); atualizarTotaisOS();"><i class="fa fa-trash red"></i></a></td>
        </tr>
    `);

    $('#modalNovoPagamento').modal('hide');
    atualizarTotaisOS();
}














