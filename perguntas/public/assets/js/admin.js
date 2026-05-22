/* ==========================================================
   Admin - gerenciamento de perguntas, cronograma e painel
   ========================================================== */
(function () {
  'use strict';

  const API = {
    questionList: 'api/question_list.php',
    questionDelete: 'api/question_delete.php',
    scheduleList: 'api/schedule_list.php',
    scheduleSave: 'api/schedule_save.php',
    scheduleDelete: 'api/schedule_delete.php',
    panelSet: 'api/panel_set.php',
    panelClear: 'api/panel_clear.php',
    settingsGet: 'api/settings_get.php',
    settingsSave: 'api/settings_save.php',
  };

  // Dataset carregado em memória - busca NÃO faz nova requisição
  let questionsCache = [];
  let scheduleCache = [];

  // ---------- Utilidades ----------
  function $(s, r = document) { return r.querySelector(s); }
  function $$(s, r = document) { return Array.from(r.querySelectorAll(s)); }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, s => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[s]);
  }

  function toast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .3s ease';
      setTimeout(() => el.remove(), 300);
    }, 2500);
  }

  async function postJSON(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    return res.json();
  }

  // ---------- Navegação por tabs ----------
  function bindTabs() {
    const setActive = (tab) => {
      $$('.nav-item').forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
      $$('.tab-panel').forEach(p => p.classList.toggle('hidden', p.dataset.panel !== tab));
      $('#mobileTab').value = tab;
      if (tab === 'schedule') loadSchedule();
      if (tab === 'settings') loadSettings();
    };
    $$('.nav-item').forEach(b => b.addEventListener('click', () => setActive(b.dataset.tab)));
    $('#mobileTab').addEventListener('change', e => setActive(e.target.value));
    setActive('questions');
  }

  // ============================================================
  //  PERGUNTAS
  // ============================================================

  async function loadQuestions() {
    try {
      const res = await fetch(API.questionList, { cache: 'no-store' });
      const json = await res.json();
      if (!json.ok) throw new Error();
      questionsCache = json.data;
      renderQuestions();
    } catch (e) {
      toast('Erro ao carregar perguntas', 'error');
    }
  }

  // Filtro em memória
  function filterQuestions() {
    const term = $('#searchInput').value.trim().toLowerCase();
    if (!term) return questionsCache;
    return questionsCache.filter(q =>
      q.question.toLowerCase().includes(term) ||
      q.participant_name.toLowerCase().includes(term)
    );
  }

  function renderQuestions() {
    const list = filterQuestions();
    const wrap = $('#questionsList');
    const empty = $('#questionsEmpty');

    $('#statsText').textContent = `${questionsCache.length} pergunta${questionsCache.length === 1 ? '' : 's'} · ${list.length} exibida${list.length === 1 ? '' : 's'}`;
    $('#countBadge').textContent = questionsCache.filter(q => q.status === 'pending').length;

    if (!list.length) {
      wrap.innerHTML = '';
      empty.classList.remove('hidden');
      return;
    }
    empty.classList.add('hidden');

    wrap.innerHTML = list.map(q => {
      let badge;
      if (q.is_active) {
        badge = `<span class="badge" style="background:#fff7c2;color:var(--br-blue);"><span class="badge-dot pulse-ring" style="background:var(--br-yellow);"></span>No painel</span>`;
      } else if (q.status === 'shown') {
        badge = `<span class="badge bg-brand-soft text-brand-strong"><span class="badge-dot bg-brand"></span>Já exibida</span>`;
      } else {
        badge = `<span class="badge bg-amber-50 text-amber-700"><span class="badge-dot bg-amber-400"></span>Pendente</span>`;
      }

      const cardCls = q.is_active
        ? 'card-active'
        : 'hover:border-green-300';

      return `
        <article class="bg-white rounded-xl p-5 border shadow-sm transition ${cardCls}">
          <div class="flex items-start justify-between gap-4 mb-3">
            <div class="min-w-0">
              <div class="font-semibold text-slate-900">${escapeHtml(q.participant_name)}</div>
              <div class="text-xs text-slate-500">${escapeHtml(q.created_at)}</div>
            </div>
            ${badge}
          </div>
          <p class="text-slate-700 leading-relaxed whitespace-pre-wrap break-words">${escapeHtml(q.question)}</p>
          <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t">
            <button data-action="show" data-id="${q.id}"
              class="px-4 py-2 text-sm font-semibold rounded-lg transition ${q.is_active
          ? 'bg-slate-200 text-slate-500 cursor-default'
          : 'btn-brand'}"
              ${q.is_active ? 'disabled' : ''}>
              ${q.is_active ? '★ Exibindo agora' : 'Mostrar no painel'}
            </button>
            <button data-action="remove" data-id="${q.id}"
              class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition">
              Remover
            </button>
          </div>
        </article>
      `;
    }).join('');
  }

  function bindQuestionActions() {
    // Delegação para os botões das perguntas
    $('#questionsList').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;
      const id = parseInt(btn.dataset.id, 10);
      const action = btn.dataset.action;

      if (action === 'show') {
        btn.disabled = true;
        const json = await postJSON(API.panelSet, { id });
        if (json.ok) {
          toast('Exibindo no painel');
          await loadQuestions();
        } else {
          toast(json.error || 'Erro', 'error');
          btn.disabled = false;
        }
      }

      if (action === 'remove') {
        if (!confirm('Remover esta pergunta?')) return;
        const json = await postJSON(API.questionDelete, { id });
        if (json.ok) {
          toast('Pergunta removida');
          await loadQuestions();
        } else {
          toast(json.error || 'Erro', 'error');
        }
      }
    });

    // Busca em memória
    $('#searchInput').addEventListener('input', renderQuestions);

    // Atualizar
    $('#btnRefresh').addEventListener('click', loadQuestions);

    // Limpar painel
    $('#btnClearPanel').addEventListener('click', async () => {
      if (!confirm('Tem certeza que deseja limpar o painel?')) return;
      const json = await postJSON(API.panelClear, {});
      if (json.ok) {
        toast('Painel limpo');
        await loadQuestions();
      } else {
        toast('Erro ao limpar', 'error');
      }
    });
  }

  // ============================================================
  //  CRONOGRAMA
  // ============================================================

  async function loadSchedule() {
    try {
      const res = await fetch(API.scheduleList, { cache: 'no-store' });
      const json = await res.json();
      if (!json.ok) throw new Error();
      scheduleCache = json.data;
      renderSchedule();
    } catch (e) {
      toast('Erro ao carregar cronograma', 'error');
    }
  }

  function renderSchedule() {
    const wrap = $('#scheduleList');
    if (!scheduleCache.length) {
      wrap.innerHTML = `
        <div class="p-8 text-center text-slate-500 text-sm">
          Nenhum item cadastrado. Clique em <strong>+ Novo item</strong> para começar.
        </div>`;
      return;
    }

    wrap.innerHTML = scheduleCache.map(item => {
      const time = item.end_time ? `${item.start_time} — ${item.end_time}` : item.start_time;
      return `
        <div class="p-4 flex items-center gap-4 hover:bg-slate-50 transition">
          <div class="text-xs font-bold w-28 shrink-0" style="color: var(--br-green);">${escapeHtml(time)}</div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold truncate">${escapeHtml(item.title)}</div>
            ${item.description ? `<div class="text-sm text-slate-500 truncate">${escapeHtml(item.description)}</div>` : ''}
          </div>
          <div class="flex gap-1">
            <button data-sch-edit="${item.id}" class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-white rounded-lg transition bg-brand-hover">Editar</button>
            <button data-sch-del="${item.id}" class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition">Excluir</button>
          </div>
        </div>
      `;
    }).join('');
  }

  function openScheduleModal(item = null) {
    $('#scheduleModalTitle').textContent = item ? 'Editar item' : 'Novo item';
    $('#schId').value = item?.id ?? '';
    $('#schTitle').value = item?.title ?? '';
    $('#schDesc').value = item?.description ?? '';
    $('#schStart').value = item?.start_time ?? '';
    $('#schEnd').value = item?.end_time ?? '';
    $('#schOrder').value = item?.sort_order ?? (scheduleCache.length + 1);
    const modal = $('#scheduleModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => $('#schTitle').focus(), 50);
  }
  function closeScheduleModal() {
    const m = $('#scheduleModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
  }

  function bindScheduleActions() {
    $('#btnNewSchedule').addEventListener('click', () => openScheduleModal(null));
    $('#schCancel').addEventListener('click', closeScheduleModal);

    $('#scheduleModal').addEventListener('click', (e) => {
      if (e.target.id === 'scheduleModal') closeScheduleModal();
    });

    $('#scheduleList').addEventListener('click', async (e) => {
      const edit = e.target.closest('[data-sch-edit]');
      const del = e.target.closest('[data-sch-del]');
      if (edit) {
        const id = parseInt(edit.dataset.schEdit, 10);
        const item = scheduleCache.find(s => s.id === id);
        if (item) openScheduleModal(item);
      }
      if (del) {
        const id = parseInt(del.dataset.schDel, 10);
        if (!confirm('Excluir este item do cronograma?')) return;
        const json = await postJSON(API.scheduleDelete, { id });
        if (json.ok) { toast('Item excluído'); await loadSchedule(); }
        else toast(json.error || 'Erro', 'error');
      }
    });

    $('#scheduleForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const payload = {
        id: parseInt($('#schId').value, 10) || 0,
        title: $('#schTitle').value.trim(),
        description: $('#schDesc').value.trim(),
        start_time: $('#schStart').value,
        end_time: $('#schEnd').value,
        sort_order: parseInt($('#schOrder').value, 10) || 0,
      };
      const json = await postJSON(API.scheduleSave, payload);
      if (json.ok) {
        toast('Salvo!');
        closeScheduleModal();
        await loadSchedule();
      } else {
        toast(json.error || 'Erro', 'error');
      }
    });
  }

  // ============================================================
  //  CONFIGURAÇÕES VISUAIS
  // ============================================================

  function syncColorInputs(colorInputId, textInputId) {
    const color = $('#' + colorInputId);
    const text = $('#' + textInputId);
    color.addEventListener('input', () => { text.value = color.value; updatePreview(); });
    text.addEventListener('input', () => {
      if (/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(text.value)) {
        color.value = text.value;
        updatePreview();
      }
    });
  }

  function updatePreview() {
    const bg = $('#bgColor').value;
    const font = $('#fontColor').value;
    const p = $('#preview');
    p.style.backgroundColor = bg;
    p.style.color = font;
  }

  async function loadSettings() {
    try {
      const res = await fetch(API.settingsGet, { cache: 'no-store' });
      const json = await res.json();
      if (!json.ok) throw new Error();
      $('#bgColor').value = json.data.background_color;
      $('#bgColorText').value = json.data.background_color;
      $('#fontColor').value = json.data.font_color;
      $('#fontColorText').value = json.data.font_color;
      updatePreview();
    } catch (e) {
      toast('Erro ao carregar configurações', 'error');
    }
  }

  function bindSettingsActions() {
    syncColorInputs('bgColor', 'bgColorText');
    syncColorInputs('fontColor', 'fontColorText');

    $$('[data-preset-bg]').forEach(btn => {
      btn.addEventListener('click', () => {
        const c = btn.dataset.presetBg;
        $('#bgColor').value = c;
        $('#bgColorText').value = c;
        updatePreview();
      });
    });

    $('#btnSaveSettings').addEventListener('click', async () => {
      const json = await postJSON(API.settingsSave, {
        background_color: $('#bgColor').value,
        font_color: $('#fontColor').value,
      });
      if (json.ok) toast('Configurações salvas');
      else toast(json.error || 'Erro', 'error');
    });
  }

  // ============================================================
  //  INIT
  // ============================================================

  document.addEventListener('DOMContentLoaded', () => {
    bindTabs();
    bindQuestionActions();
    bindScheduleActions();
    bindSettingsActions();
    loadQuestions();
    // Auto-refresh de perguntas a cada 10s
    setInterval(loadQuestions, 10000);
  });
})();