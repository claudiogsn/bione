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
function salvarOrdemServico() {
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
    const services = [];

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

    $('#tabela-servicos tbody tr').each(function () {
        const row = $(this);
        services.push({
            servico_id: parseInt(row.find('.servico_id').val()),
            valor: parseFloat(row.find('.valor_unitario_servico').val()) || 0,
            custo: 0,
            dias_uso: parseInt(row.find('.dias_uso_servico').val()) || 1,
            data_inicial: row.find('.data_inicio_servico').val(),
            status: 'ativo',
            quantidade: parseInt(row.find('.quantidade_servico').val()) || 1
        });
    });

    // Pelo menos 1 item ou 1 serviço
    if (itens.length === 0 && services.length === 0) {
        return Swal.fire('Atenção', 'Adicione pelo menos um item ou serviço.', 'warning');
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
        order: {
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
        services,
        payments
    };

    axios.post(`${baseUrl}`, {
        method: 'createOrUpdateOrder',
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

// Função auxiliar para preencher os dados da ordem
function preencherDadosDaOrdem(dados) {
    $('#evento_id').val(dados.evento_id);
    $('#cliente_id').val(dados.cliente_id);
    $('#data_montagem').val(dados.data_montagem);
    $('#data_recolhimento').val(dados.data_recolhimento);
    $('#local').val(dados.local);
    $('#responsavel').val(dados.responsavel);
    $('#telefone').val(dados.telefone);
    $('#obs').val(dados.observacao);

    preencherGrid('#tabela-itens', dados.itens, preencherLinhaItem);
    preencherGrid('#tabela-servicos', dados.services, preencherLinhaServico);
    preencherGrid('#tabela-pagamentos', dados.payments, preencherLinhaPagamento);
}

// Função genérica para preencher grids
function preencherGrid(tabela, dados, preencherLinhaCallback) {
    const tbody = $(`${tabela} tbody`);
    tbody.empty();
    dados.forEach(dado => {
        const row = $('<tr>');
        preencherLinhaCallback(row, dado);
        tbody.append(row);
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


function abrirModalItem() {
    $('#modalItens').modal('show');
    $('#tabela-itens-modal tbody').empty();

    axios.post(`${baseUrl}`, {
        method: 'listMaterials',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.materials)) {
            res.data.materials.forEach(mat => {
                const selectId = `select-modelo-${mat.id}`;

                $('#tabela-itens-modal tbody').append(`
                    <tr>
                        <td>${mat.nome}</td>
                        <td>${mat.unidade}</td>
                        <td>
                            <select class="form-control modelo-select" id="${selectId}" data-material-id="${mat.id}" style="width: 100%;">
                                <option value="">Sem modelo</option>
                            </select>
                        </td>
                       <td><a href="javascript:void(0)" onclick="adicionarItemSelecionadoComModelo(${mat.id},'${mat.nome}',${mat.valor_locacao})"><i class="fa fa-plus green"></i></a></td>
                    </tr>
                `);

                // Chama API para buscar os modelos agrupados por material
                axios.post(`${baseUrl}`, {
                    method: 'listPatrimoniosAgrupado',
                    token: token,
                    data: { material_id: mat.id }
                }).then(resp => {
                    const agrupado = resp.data.agrupado || [];
                    agrupado.forEach(m => {
                        $(`#${selectId}`).append(`<option value="${m.modelo}">${m.modelo}</option>`);
                    });
                    $(`#${selectId}`).select2({ dropdownParent: $('#modalItens') });
                });
            });
        }
    });
}

function adicionarItemSelecionadoComModelo(id, nome, valor_locacao) {
    const modelo = $(`#select-modelo-${id}`).val();
    const label = modelo ? `${nome}&nbsp;&rarr;&nbsp;<br><small>${modelo}</small>` : nome;

    adicionarItem(id, 1, valor_locacao, modelo, label); // função ajustada abaixo
    $('#modalItens').modal('hide');
}

function recalcularItem(rowId) {

    const row = $(`#${rowId}`);
    const qtd = parseInt(row.find('.quantidade').val()) || 0;
    const unit = parseFloat(row.find('.valor_unitario').val()) || 0;
    const dtInicio = new Date(row.find('.data_inicio').val());
    const dtFim = new Date(row.find('.data_fim').val());

    let dias = Math.floor((dtFim - dtInicio) / (1000 * 60 * 60 * 24)) + 1;
    if (isNaN(dias) || dias < 1) dias = 1;

    const subtotal = qtd * unit * dias;

    row.find('.dias_uso').val(dias);
    row.find('.subtotal').text(`R$ ${subtotal.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`);

    atualizarTotaisOS();
}

function abrirModalServico() {
    $('#modalServicos').modal('show');
    $('#tabela-servicos-modal tbody').empty();

    axios.post(`${baseUrl}`, {
        method: 'listServicos',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.servicos)) {
            res.data.servicos.forEach(serv => {
                $('#tabela-servicos-modal tbody').append(`
                    <tr>
                        <td>${serv.descricao}</td>
                        <td>${(serv.valor_servico || 0).toFixed(2)}</td>
                        <td><a href="javascript:void(0)" onclick="adicionarServicoSelecionado(${serv.id}, '${serv.descricao}', ${serv.valor_servico || 0})"><i class="fa fa-plus green"></i></a></td>
                    </tr>
                `);
            });
        }
    });
}

function adicionarServicoSelecionado(id, descricao, valor) {
    const rowId = `servico-${id}-${Date.now()}`;
    const dataInicio = formatDateOnly(window.DATA_INICIO_EVENTO);
    const dataFim = formatDateOnly(window.DATA_FIM_EVENTO);



    $('#tabela-servicos tbody').append(`
        <tr id="${rowId}">
            <td>
                <input type="hidden" class="servico_id" value="${id}">
                ${descricao}
            </td>
            <td>
                <input type="number" class="form-control valor_unitario_servico valor_reais" value="${valor}" onchange="recalcularServico('${rowId}')">
            </td>
            <td>
                <input type="number" class="form-control quantidade_servico" value="1" onchange="recalcularServico('${rowId}')">
            </td>
            <td>
                <input type="date" class="form-control data_inicio_servico" value="${dataInicio}" onchange="recalcularServico('${rowId}')">
            </td>
            <td>
                <input type="date" class="form-control data_fim_servico" value="${dataFim}" onchange="recalcularServico('${rowId}')">
            </td>
            <td style="display:none;">
                <input type="number" class="form-control dias_uso_servico" value="1" readonly>
            </td>
            <td>
                <span class="subtotal_servico">R$ 0,00</span>
            </td>
            <td><a href="javascript:void(0)" onclick="$(this).closest('tr').remove(); atualizarTotaisOS();"><i class="fa fa-trash red"></i></a></td>
        </tr>
    `);

    recalcularServico(rowId);
    $('#modalServicos').modal('hide');
}

function recalcularServico(rowId) {

    const row = $(`#${rowId}`);
    const valorUnitario = parseFloat(row.find('.valor_unitario_servico').val()) || 0;
    const quantidade = parseInt(row.find('.quantidade_servico').val()) || 1;
    const dataInicio = new Date(row.find('.data_inicio_servico').val());
    const dataFim = new Date(row.find('.data_fim_servico').val());

    let dias = Math.floor((dataFim - dataInicio) / (1000 * 60 * 60 * 24)) + 1;
    dias = dias < 1 ? 1 : dias;

    row.find('.dias_uso_servico').val(dias);

    const total = valorUnitario * quantidade * dias;
    row.find('.subtotal_servico').text(`R$ ${total.toFixed(2)}`);
    atualizarTotaisOS();
}

function abrirModalPagamento() {
    $('#modalPagamentos').modal('show');
    $('#tabela-pagamentos-modal tbody').empty();

    axios.post(baseUrl, {
        method: 'listMetodosPagamento',
        token: token,
        data: {}
    }).then(res => {
        if (Array.isArray(res.data.metodos)) {
            res.data.metodos.forEach(met => {
                $('#tabela-pagamentos-modal tbody').append(`
                    <tr>
                        <td><strong>${met.nome}</strong><br><small>${met.tipo} ${met.descricao ? '- ' + met.descricao : ''}</small></td>
                        <td><a href="javascript:void(0)" onclick="adicionarPagamentoSelecionado(${met.id}, '${met.nome.replace(/'/g, "\\'")}')"><i class="fa fa-plus green"></i></a></td>
                    </tr>
                `);
            });
        }
    });
}

function adicionarPagamentoSelecionado(id, nome) {
    const rowId = `pagamento-${id}-${Date.now()}`;
    const dataInicio = window.DATA_INICIO_EVENTO?.split('T')[0] || new Date().toISOString().split('T')[0];
    const dataFim = window.DATA_FIM_EVENTO?.split('T')[0] || dataInicio;



    $('#tabela-pagamentos tbody').append(`
        <tr id="${rowId}">
            <td>
                <input type="hidden" class="metodo_pagamento_id" value="${id}">
                ${nome}
            </td>
            <td>
                <input type="date" class="form-control data_pagamento" value="">
            </td>
            <td>
                <input type="number" class="form-control valor_pagamento" value="0.00" onchange="atualizarTotaisOS()">
            </td>
            <td><a href="javascript:void(0)" onclick="$(this).closest('tr').remove(); atualizarTotaisOS();"><i class="fa fa-trash red"></i></a></td>
        </tr>
    `);
    $('#modalPagamentos').modal('hide');

}

// Adição e remoção de linhas
function adicionarItem(id, qtd = 1, valor_locacao, modelo = '', nomeExibicao = '') {
    const rowId = `item-${id}-${Date.now()}`;
    const dataInicio = formatDateOnly(window.DATA_INICIO_EVENTO);
    const dataFim = formatDateOnly(window.DATA_FIM_EVENTO);

    $('#tabela-itens tbody').append(`
        <tr id="${rowId}">
            <td style="display:none;">
                <input type="hidden" class="material_id" value="${id}">
                <input type="hidden" class="modelo" value="${modelo}">
            </td>
            <td>${nomeExibicao}</td>
            <td><input type="text" class="form-control observacao_item" placeholder="Observação..."></td>
            <td style="width: 100px;">
                <input type="number" class="form-control valor_unitario valor_reais" value="${valor_locacao}" onchange="recalcularItem('${rowId}')">
            </td>
            <td style="width: 80px;">
                <input type="number" class="form-control quantidade" value="${qtd}" onchange="recalcularItem('${rowId}')">
            </td>
            <td style="width: 120px;">
                <div class="input-group">
                    <input type="date" class="form-control data_inicio" value="${dataInicio}" onchange="recalcularItem('${rowId}')">
                    <span class="input-group-addon">→</span>
                    <input type="date" class="form-control data_fim" value="${dataFim}" onchange="recalcularItem('${rowId}')">
                </div>
            </td>
            <td style="display:none;">
                <input type="number" class="form-control dias_uso" value="1" readonly>
            </td>
            <td><span class="subtotal">R$ 0,00</span></td>
            <td><a href="javascript:void(0)" onclick="$(this).closest('tr').remove(); atualizarTotaisOS();"><i class="fa fa-trash red"></i></a></td>
        </tr>
    `);

    recalcularItem(rowId);
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

// Atualização de total
function atualizarTotal() {
    let totalItens = 0;
    $('#tabela-itens tbody tr').each(function () {
        const qtde = parseFloat($(this).find('.quantidade').val()) || 0;
        const valor = parseFloat($(this).find('.valor_unitario').val()) || 0;
        totalItens += qtde * valor;
    });

    let totalServicos = 0;
    $('#tabela-servicos tbody tr').each(function () {
        totalServicos += parseFloat($(this).find('.valor_unitario').val()) || 0;
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

// Renderiza linha de item
function preencherLinhaItem(row, item) {
    const rowId = `item-${item.material_id}-${Date.now()}`;
    const dataInicio = formatDateOnly(item.data_inicial || window.DATA_INICIO_EVENTO);
    const dataFim = formatDateOnly(item.data_final || window.DATA_FIM_EVENTO);
    const modelo = item.modelo || '';
    const nomeExibicao = item.nome || `Item ${item.material_id}`;
    const valorUnit = parseFloat(item.valor) || 0;
    const quantidade = parseInt(item.quantidade) || 1;
    const observacao = item.observacao || '';

    row.attr('id', rowId).html(`
        <td style="display:none;">
            <input type="hidden" class="material_id" value="${item.material_id}">
            <input type="hidden" class="modelo" value="${modelo}">
        </td>
        <td>${nomeExibicao}</td>
        <td><input type="text" class="form-control observacao_item" value="${observacao}" placeholder="Observação..."></td>
        <td style="width: 80px;"><input type="number" class="form-control valor_unitario valor_reais" value="${valorUnit}" onchange="recalcularItem('${rowId}')"></td>
        <td style="width: 60px;"><input type="number" class="form-control quantidade" value="${quantidade}" onchange="recalcularItem('${rowId}')"></td>
        <td style="width: 180px;">
            <div class="input-group">
                <input type="date" class="form-control data_inicio" value="${dataInicio}" onchange="recalcularItem('${rowId}')">
                <span class="input-group-addon">→</span>
                <input type="date" class="form-control data_fim" value="${dataFim}" onchange="recalcularItem('${rowId}')">
            </div>
        </td>
        <td style="display:none;"><input type="number" class="form-control dias_uso" value="1" readonly></td>
        <td><span class="subtotal">R$ 0,00</span></td>
        <td><a href="javascript:void(0)" onclick="$(this).closest('tr').remove(); atualizarTotaisOS();"><i class="fa fa-trash red red"></i></a></td>
    `);

    recalcularItem(rowId);
}



// Renderiza linha de serviço
function preencherLinhaServico(row, servico) {
    row.append(`<td><input type="text" class="form-control servico_id" value="${servico.servico_id}"></td>`);
    row.append(`<td><input type="text" class="form-control valor_servico" value="${servico.valor_unitario}"></td>`);
}

// Renderiza linha de pagamento
function preencherLinhaPagamento(row, pagamento) {
    row.append(`<td><input type="text" class="form-control forma_pagamento" value="${pagamento.forma_pagamento}"></td>`);
    row.append(`<td><input type="text" class="form-control valor_pago" value="${pagamento.valor_pago}"></td>`);
}

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







