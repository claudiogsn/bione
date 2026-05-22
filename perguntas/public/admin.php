<?php
// Painel administrativo
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin · Perguntas do Evento</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">

  <div class="flex min-h-screen">

    <!-- Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-200 p-6 hidden lg:flex flex-col">
      <div class="flex items-center gap-2 mb-10">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-violet-500 to-pink-500 flex items-center justify-center">
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
          </svg>
        </div>
        <div>
          <div class="font-bold text-white">Evento Q&amp;A</div>
          <div class="text-[11px] text-slate-400">Painel administrativo</div>
        </div>
      </div>

      <nav class="flex-1 space-y-1">
        <button data-tab="questions" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
          </svg>
          Perguntas
          <span id="countBadge" class="ml-auto text-[11px] bg-violet-500/30 text-violet-200 px-2 py-0.5 rounded-full">0</span>
        </button>

        <button data-tab="schedule" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
          Cronograma
        </button>

        <button data-tab="settings" class="nav-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Painel (visual)
        </button>
      </nav>

      <div class="pt-6 border-t border-slate-800 space-y-2">
        <a href="painel.php" target="_blank"
           class="block text-center text-sm font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded-lg py-2.5 transition">
          Abrir painel (telão) ↗
        </a>
        <a href="index.php" target="_blank"
           class="block text-center text-xs text-slate-400 hover:text-white transition">
          Ver página pública
        </a>
      </div>
    </aside>

    <!-- Conteúdo principal -->
    <main class="flex-1 min-w-0">

      <!-- Topbar mobile -->
      <div class="lg:hidden bg-white border-b px-4 py-3 flex items-center justify-between sticky top-0 z-20">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-pink-500"></div>
          <div class="font-bold">Admin</div>
        </div>
        <select id="mobileTab" class="text-sm border rounded-lg px-2 py-1.5">
          <option value="questions">Perguntas</option>
          <option value="schedule">Cronograma</option>
          <option value="settings">Painel (visual)</option>
        </select>
      </div>

      <!-- ====== TAB: PERGUNTAS ====== -->
      <section data-panel="questions" class="tab-panel p-6 lg:p-8 max-w-6xl">
        <header class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold">Perguntas</h1>
            <p class="text-sm text-slate-500">Gerencie as perguntas enviadas pelos participantes.</p>
          </div>
          <div class="flex gap-2">
            <button id="btnRefresh" class="px-4 py-2.5 text-sm font-medium bg-white border rounded-lg hover:bg-slate-50 transition">
              ↻ Atualizar
            </button>
            <button id="btnClearPanel" class="px-4 py-2.5 text-sm font-medium bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition">
              Limpar painel
            </button>
          </div>
        </header>

        <!-- Busca -->
        <div class="bg-white rounded-2xl p-4 mb-5 shadow-sm border">
          <div class="relative">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input id="searchInput" type="text" placeholder="Filtrar por pergunta ou nome do participante..."
                   class="w-full pl-10 pr-4 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-400">
          </div>
          <div class="mt-3 flex items-center gap-4 text-xs text-slate-500">
            <span id="statsText">0 perguntas</span>
            <span class="flex items-center gap-1.5">
              <span class="badge-dot bg-amber-400"></span> Pendente
            </span>
            <span class="flex items-center gap-1.5">
              <span class="badge-dot bg-emerald-500"></span> Exibida
            </span>
            <span class="flex items-center gap-1.5">
              <span class="badge-dot bg-violet-500 pulse-ring"></span> No painel agora
            </span>
          </div>
        </div>

        <!-- Lista de perguntas -->
        <div id="questionsList" class="space-y-3"></div>

        <div id="questionsEmpty" class="hidden text-center py-16">
          <div class="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
          </div>
          <h3 class="font-semibold">Nenhuma pergunta encontrada</h3>
          <p class="text-sm text-slate-500 mt-1">Ajuste sua busca ou aguarde novas perguntas.</p>
        </div>
      </section>

      <!-- ====== TAB: CRONOGRAMA ====== -->
      <section data-panel="schedule" class="tab-panel p-6 lg:p-8 max-w-4xl hidden">
        <header class="mb-6 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold">Cronograma</h1>
            <p class="text-sm text-slate-500">Defina os itens que o participante vai ver na página pública.</p>
          </div>
          <button id="btnNewSchedule" class="px-4 py-2.5 text-sm font-medium bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition">
            + Novo item
          </button>
        </header>

        <div id="scheduleList" class="bg-white rounded-2xl shadow-sm border divide-y"></div>
      </section>

      <!-- ====== TAB: CONFIGURAÇÕES VISUAIS ====== -->
      <section data-panel="settings" class="tab-panel p-6 lg:p-8 max-w-2xl hidden">
        <header class="mb-6">
          <h1 class="text-2xl font-bold">Configurações do painel</h1>
          <p class="text-sm text-slate-500">Cores que serão exibidas no telão. Ideal para uso com chroma key.</p>
        </header>

        <div class="bg-white rounded-2xl shadow-sm border p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium mb-2">Cor de fundo</label>
            <div class="flex items-center gap-3">
              <input id="bgColor" type="color" value="#0f172a" class="w-14 h-14 rounded-lg border cursor-pointer">
              <input id="bgColorText" type="text" value="#0f172a"
                     class="flex-1 px-3 py-2.5 border rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
              <button type="button" data-preset-bg="#00b140" class="w-10 h-10 rounded-lg border-2 border-transparent hover:border-slate-400 transition" style="background:#00b140" title="Chroma verde"></button>
              <button type="button" data-preset-bg="#0047bb" class="w-10 h-10 rounded-lg border-2 border-transparent hover:border-slate-400 transition" style="background:#0047bb" title="Chroma azul"></button>
            </div>
            <p class="text-xs text-slate-500 mt-2">
              Dica: <strong>#00b140</strong> (verde) ou <strong>#0047bb</strong> (azul) para chroma key.
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">Cor da fonte</label>
            <div class="flex items-center gap-3">
              <input id="fontColor" type="color" value="#ffffff" class="w-14 h-14 rounded-lg border cursor-pointer">
              <input id="fontColorText" type="text" value="#ffffff"
                     class="flex-1 px-3 py-2.5 border rounded-lg font-mono text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
            </div>
          </div>

          <!-- Preview -->
          <div>
            <label class="block text-sm font-medium mb-2">Preview</label>
            <div id="preview" class="rounded-xl p-8 flex flex-col items-center justify-center text-center min-h-[220px] transition-colors">
              <div class="text-xs uppercase tracking-widest opacity-70 mb-3">Participante</div>
              <div class="text-2xl font-bold mb-3">Exemplo de participante</div>
              <div class="text-base font-medium opacity-90 max-w-md">
                Como vocês veem o futuro da inteligência artificial nos próximos anos?
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t">
            <button id="btnSaveSettings" class="px-5 py-2.5 text-sm font-medium bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition">
              Salvar
            </button>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- Modal de item do cronograma -->
  <div id="scheduleModal" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
      <h3 id="scheduleModalTitle" class="text-lg font-bold mb-4">Novo item</h3>
      <form id="scheduleForm" class="space-y-4">
        <input type="hidden" id="schId">
        <div>
          <label class="block text-sm font-medium mb-1">Título *</label>
          <input id="schTitle" type="text" required maxlength="200"
                 class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-400">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Descrição</label>
          <textarea id="schDesc" rows="2"
                    class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-400"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1">Início *</label>
            <input id="schStart" type="time" required
                   class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-400">
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Fim</label>
            <input id="schEnd" type="time"
                   class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-400">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Ordem</label>
          <input id="schOrder" type="number" value="0"
                 class="w-full px-3 py-2.5 border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-400">
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" id="schCancel" class="px-4 py-2.5 text-sm font-medium border rounded-lg hover:bg-slate-50">Cancelar</button>
          <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-violet-600 text-white rounded-lg hover:bg-violet-700">Salvar</button>
        </div>
      </form>
    </div>
  </div>

  <style>
    .nav-item {
      color: #cbd5e1;
    }
    .nav-item:hover {
      background: rgba(255,255,255,.06);
      color: #fff;
    }
    .nav-item.active {
      background: linear-gradient(135deg, rgba(124,58,237,.35), rgba(236,72,153,.25));
      color: #fff;
    }
  </style>

  <script src="assets/js/admin.js"></script>
</body>
</html>
