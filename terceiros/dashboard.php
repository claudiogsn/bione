<?php
require 'config.php';
if (!isset($_SESSION['freelancer_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <title>Dashboard - Bione</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/313adf4cdc.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Teko:wght@400;500;600&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { dark: '#1B1B1B', darkLighter: '#252525', accentLaranja: '#F4AA48', accentVermelho: '#E34F57', mrkGreen: '#08A794' }, fontFamily: { teko: ['Teko'], inter: ['Inter'] } } }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        .glass-card {
            background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.4); box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .dark .glass-card {
            background: rgba(37, 37, 37, 0.8); border-color: rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .toast {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 999;
            padding: 14px 24px; border-radius: 14px; font-size: 14px; font-weight: 600;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12); opacity: 0; transition: opacity 0.3s;
            max-width: 90%; text-align: center;
        }
        .toast.show { opacity: 1; }
        .toast.success { background: #DEF7EC; color: #03543F; }
        .toast.error { background: #FDE8E8; color: #9B1C1C; }
        .tab-active { color: #F4AA48; }
        .tab-inactive { color: #9CA3AF; }
        .dark .tab-inactive { color: #6B7280; }
        .skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; }
        .dark .skeleton { background: linear-gradient(90deg, #333 25%, #3a3a3a 50%, #333 75%); background-size: 200% 100%; }
        @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        @keyframes fadeIn { 0% { opacity: 0; transform: translateY(10px); } 100% { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.4s ease forwards; }
    </style>
</head>
<body class="bg-[#F6F7F9] dark:bg-dark text-gray-900 dark:text-gray-100 min-h-screen pb-24 transition-colors">

<div id="toast" class="toast"></div>

<!-- Header (carrega via JS) -->
<div id="header" class="pt-12 pb-6 px-6 bg-white dark:bg-darkLighter rounded-b-[2rem] shadow-sm mb-6 flex justify-between items-center">
    <div class="flex items-center gap-3">
        <div id="avatar" class="w-12 h-12 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center font-bold text-lg shadow-md skeleton"></div>
        <div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider">Bem-vindo(a)</p>
            <h1 id="user-name" class="font-bold text-lg leading-tight"><span class="skeleton inline-block w-32 h-5">&nbsp;</span></h1>
        </div>
    </div>
    <div class="flex gap-2">
        <button onclick="toggleTheme()" class="w-10 h-10 rounded-full bg-gray-50 dark:bg-dark text-gray-400 active:scale-95 transition">
            <i class="fas fa-moon dark:hidden"></i>
            <i class="fas fa-sun hidden dark:block"></i>
        </button>
        <button onclick="logout()" class="w-10 h-10 rounded-full bg-gray-50 dark:bg-dark text-gray-400 active:scale-95 transition" title="Sair">
            <i class="fas fa-sign-out-alt"></i>
        </button>
    </div>
</div>

<!-- Conteúdo principal - Tabs -->
<div id="content" class="px-5 space-y-6">

    <!-- TAB: Eventos -->
    <div id="tab-eventos" class="space-y-5">
        <!-- Cards de Valor -->
        <div class="grid grid-cols-2 gap-3">
            <div class="glass-card rounded-2xl p-4 relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-mrkGreen/10 rounded-bl-full"></div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium mb-1 uppercase">A Receber</p>
                <h2 id="val-previsto" class="text-2xl font-teko text-mrkGreen tracking-wide font-medium">
                    <span class="skeleton inline-block w-24 h-7">&nbsp;</span>
                </h2>
            </div>
            <div class="glass-card rounded-2xl p-4 relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-accentLaranja/10 rounded-bl-full"></div>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium mb-1 uppercase">Recebido</p>
                <h2 id="val-pago" class="text-2xl font-teko text-accentLaranja tracking-wide font-medium">
                    <span class="skeleton inline-block w-24 h-7">&nbsp;</span>
                </h2>
            </div>
        </div>

        <!-- Meus Eventos -->
        <div id="meus-eventos-section" class="hidden">
            <div class="flex justify-between items-end mb-3">
                <h3 class="font-bold text-base">Meus Eventos</h3>
            </div>
            <div id="meus-eventos-list" class="space-y-3"></div>
        </div>

        <!-- Eventos Disponíveis -->
        <div>
            <div class="flex justify-between items-end mb-3">
                <h3 class="font-bold text-base">Eventos Disponíveis</h3>
            </div>
            <div id="eventos-list" class="space-y-3">
                <div class="glass-card rounded-2xl p-4 skeleton h-28"></div>
                <div class="glass-card rounded-2xl p-4 skeleton h-28"></div>
            </div>
        </div>
    </div>

    <!-- TAB: Extrato -->
    <div id="tab-extrato" class="hidden space-y-4">
        <h3 class="font-bold text-lg">Extrato de Pagamentos</h3>
        <div id="pagamentos-list" class="space-y-3">
            <div class="glass-card rounded-2xl p-4 skeleton h-20"></div>
        </div>
        <p id="pagamentos-vazio" class="hidden text-center text-gray-400 dark:text-gray-500 text-sm py-10">
            <i class="fas fa-receipt text-3xl mb-3 block opacity-30"></i>
            Nenhum pagamento registrado ainda.
        </p>
    </div>

    <!-- TAB: Perfil -->
    <div id="tab-perfil" class="hidden space-y-4">
        <h3 class="font-bold text-lg mb-2">Meu Perfil</h3>
        <div class="glass-card rounded-2xl p-5 space-y-4">
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Nome</label>
                <p id="perfil-nome" class="font-semibold">-</p>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">E-mail</label>
                <p id="perfil-email" class="font-semibold">-</p>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">CPF</label>
                <p id="perfil-cpf" class="font-semibold">-</p>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Telefone</label>
                <p id="perfil-telefone" class="font-semibold">-</p>
            </div>
            <div>
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Data de Nascimento</label>
                <p id="perfil-nascimento" class="font-semibold">-</p>
            </div>
            <div class="border-t border-gray-100 dark:border-white/5 pt-4">
                <label class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block mb-1">Chave Pix</label>
                <p id="perfil-pix" class="font-semibold">-</p>
                <p id="perfil-tipo-pix" class="text-xs text-gray-400">-</p>
            </div>
        </div>
    </div>

</div>

<!-- Bottom Nav -->
<div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-darkLighter border-t border-gray-100 dark:border-white/5 flex justify-around items-center px-4 py-3 pb-8 z-50">
    <button onclick="switchTab('eventos')" id="nav-eventos" class="flex flex-col items-center tab-active cursor-pointer transition">
        <i class="fas fa-calendar-alt text-xl mb-1"></i>
        <span class="text-[10px] font-bold">Eventos</span>
    </button>
    <button onclick="switchTab('extrato')" id="nav-extrato" class="flex flex-col items-center tab-inactive cursor-pointer transition">
        <i class="fas fa-file-invoice-dollar text-xl mb-1"></i>
        <span class="text-[10px] font-bold">Extrato</span>
    </button>
    <button onclick="switchTab('perfil')" id="nav-perfil" class="flex flex-col items-center tab-inactive cursor-pointer transition">
        <i class="fas fa-user text-xl mb-1"></i>
        <span class="text-[10px] font-bold">Perfil</span>
    </button>
</div>

<script>
    // ===== Toast =====
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast ' + type + ' show';
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    // ===== API Helper =====
    async function api(action, payload = {}) {
        const res = await fetch('ajax_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...payload })
        });
        if (res.status === 401) {
            window.location.href = 'index.php';
            return null;
        }
        return res.json();
    }

    // ===== Formatadores =====
    function moeda(v) {
        return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function dataFormatada(d) {
        if (!d) return '-';
        const dt = new Date(d);
        return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short' });
    }
    function dataCompleta(d) {
        if (!d) return '-';
        return new Date(d).toLocaleDateString('pt-BR');
    }
    function horaFormatada(d) {
        if (!d) return '';
        return new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }
    function iniciais(nome) {
        if (!nome) return '??';
        return nome.split(' ').filter(Boolean).slice(0, 2).map(p => p[0]).join('').toUpperCase();
    }

    // ===== Tabs =====
    function switchTab(tab) {
        ['eventos', 'extrato', 'perfil'].forEach(t => {
            document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
            const nav = document.getElementById('nav-' + t);
            nav.classList.toggle('tab-active', t === tab);
            nav.classList.toggle('tab-inactive', t !== tab);
        });

        if (tab === 'perfil') loadPerfil();
    }

    // ===== Theme =====
    function toggleTheme() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }

    // ===== Logout =====
    async function logout() {
        await api('logout');
        window.location.href = 'index.php';
    }

    // ===== Inscrever em Evento =====
    async function inscreverEvento(eventoId, btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const res = await api('inscrever_evento', { evento_id: eventoId });

        if (res && res.success) {
            showToast(res.message, 'success');
            loadDashboard(); // recarrega tudo
        } else {
            showToast(res?.error || 'Erro ao se inscrever.', 'error');
            btn.disabled = false;
            btn.innerHTML = 'Me Inscrever';
        }
    }

    // ===== Render: Eventos Disponíveis =====
    function renderEventos(eventos) {
        const list = document.getElementById('eventos-list');
        if (!eventos || eventos.length === 0) {
            list.innerHTML = `
                <div class="text-center text-gray-400 dark:text-gray-500 text-sm py-10">
                    <i class="fas fa-calendar-check text-3xl mb-3 block opacity-30"></i>
                    Nenhum evento disponível no momento.
                </div>`;
            return;
        }

        list.innerHTML = eventos.map(e => {
            const statusMap = { aberto: { label: 'Vaga Aberta', cls: 'bg-accentLaranja/10 text-accentLaranja' } };
            const st = statusMap[e.status_freelancer] || statusMap.aberto;
            return `
            <div class="glass-card rounded-2xl p-4 fade-in">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-[10px] ${st.cls} font-bold px-2 py-1 rounded-md uppercase">${st.label}</span>
                        <h4 class="font-bold mt-2 text-base">${e.nome}</h4>
                        ${e.descricao_freelancer ? `<p class="text-xs text-gray-400 mt-1 line-clamp-2">${e.descricao_freelancer}</p>` : ''}
                    </div>
                    <div class="text-right flex-shrink-0 ml-3">
                        <span class="text-xs text-gray-500 block">${dataFormatada(e.data_inicio)}</span>
                        <span class="text-xs text-gray-500 block">${horaFormatada(e.data_inicio)}</span>
                        <span class="text-xs font-bold text-mrkGreen block mt-1">${moeda(e.valor_freelancer)}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between border-t border-gray-100 dark:border-white/5 pt-3 mt-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400"><i class="fas fa-map-marker-alt mr-1"></i> ${e.cidade || ''}, ${e.estado || ''}</p>
                    <button onclick="inscreverEvento(${e.id}, this)" class="text-xs font-bold bg-dark dark:bg-white text-white dark:text-dark px-4 py-1.5 rounded-full active:scale-95 transition">Me Inscrever</button>
                </div>
            </div>`;
        }).join('');
    }

    // ===== Render: Meus Eventos =====
    function renderMeusEventos(eventos) {
        const section = document.getElementById('meus-eventos-section');
        const list = document.getElementById('meus-eventos-list');

        if (!eventos || eventos.length === 0) {
            section.classList.add('hidden');
            return;
        }

        section.classList.remove('hidden');
        const statusColors = {
            pendente: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
            aprovado: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            recusado: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
        };

        list.innerHTML = eventos.map(e => `
            <div class="glass-card rounded-2xl p-4 fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-bold text-sm">${e.nome}</h4>
                        <p class="text-xs text-gray-400 mt-1"><i class="fas fa-calendar mr-1"></i>${dataFormatada(e.data_inicio)} · ${horaFormatada(e.data_inicio)}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-1 rounded-md uppercase ${statusColors[e.inscricao_status] || ''}">${e.inscricao_status}</span>
                </div>
            </div>
        `).join('');
    }

    // ===== Render: Pagamentos =====
    function renderPagamentos(pagamentos) {
        const list = document.getElementById('pagamentos-list');
        const vazio = document.getElementById('pagamentos-vazio');

        if (!pagamentos || pagamentos.length === 0) {
            list.innerHTML = '';
            vazio.classList.remove('hidden');
            return;
        }

        vazio.classList.add('hidden');
        const statusIcons = {
            previsto: { icon: 'fa-clock', color: 'text-accentLaranja', bg: 'bg-accentLaranja/10' },
            pago:     { icon: 'fa-check-circle', color: 'text-mrkGreen', bg: 'bg-mrkGreen/10' },
            cancelado: { icon: 'fa-times-circle', color: 'text-red-400', bg: 'bg-red-100 dark:bg-red-900/20' }
        };

        list.innerHTML = pagamentos.map(p => {
            const st = statusIcons[p.status] || statusIcons.previsto;
            return `
            <div class="glass-card rounded-2xl p-4 flex items-center gap-4 fade-in">
                <div class="w-10 h-10 rounded-full ${st.bg} flex items-center justify-center ${st.color} flex-shrink-0">
                    <i class="fas ${st.icon}"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate">${p.descricao}</p>
                    <p class="text-xs text-gray-400">${p.evento_titulo || ''} · ${p.status === 'pago' ? dataCompleta(p.data_pagamento) : (p.data_prevista ? 'Previsto: ' + dataCompleta(p.data_prevista) : '')}</p>
                </div>
                <span class="font-teko text-lg font-medium ${p.status === 'pago' ? 'text-mrkGreen' : 'text-gray-600 dark:text-gray-300'}">${moeda(p.valor)}</span>
            </div>`;
        }).join('');
    }

    // ===== Load Dashboard =====
    async function loadDashboard() {
        const data = await api('get_dashboard');
        if (!data) return;

        // Header
        const f = data.freelancer;
        document.getElementById('user-name').textContent = f.nome;
        const av = document.getElementById('avatar');
        if (f.foto_url) {
            av.innerHTML = `<img src="${f.foto_url}" class="w-12 h-12 rounded-full object-cover">`;
        } else {
            av.textContent = iniciais(f.nome);
            av.className = 'w-12 h-12 rounded-full bg-accentLaranja text-white flex items-center justify-center font-bold text-lg shadow-md';
        }

        // Valores
        document.getElementById('val-previsto').textContent = moeda(data.total_previsto);
        document.getElementById('val-pago').textContent = moeda(data.total_pago);

        // Listas
        renderEventos(data.eventos_disponiveis);
        renderMeusEventos(data.meus_eventos);
        renderPagamentos(data.pagamentos);
    }

    // ===== Load Perfil =====
    async function loadPerfil() {
        const data = await api('get_perfil');
        if (!data || !data.freelancer) return;
        const f = data.freelancer;

        document.getElementById('perfil-nome').textContent = f.nome || '-';
        document.getElementById('perfil-email').textContent = f.email || '-';
        document.getElementById('perfil-cpf').textContent = f.cpf || '-';
        document.getElementById('perfil-telefone').textContent = f.telefone || '-';
        document.getElementById('perfil-nascimento').textContent = f.data_nascimento ? dataCompleta(f.data_nascimento) : '-';
        document.getElementById('perfil-pix').textContent = f.chave_pix || '-';
        document.getElementById('perfil-tipo-pix').textContent = f.tipo_chave_pix ? ('Tipo: ' + f.tipo_chave_pix.toUpperCase()) : '-';
    }

    // ===== Init =====
    loadDashboard();
</script>
</body>
</html>