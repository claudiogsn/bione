/* ==========================================================
   Painel do projetor - polling para pergunta ativa + config
   ========================================================== */
(function () {
  'use strict';

  const API = {
    panelGet:    'api/panel_get.php',
    settingsGet: 'api/settings_get.php',
  };

  const POLL_MS      = 2000;   // pergunta ativa a cada 2s
  const SETTINGS_MS  = 5000;   // cores a cada 5s

  let currentVersion    = -1;  // versão do panel_state
  let currentQuestionId = null;
  let currentBg   = null;
  let currentFont = null;

  function $(s) { return document.querySelector(s); }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, s => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[s]);
  }

  // ---------- Render ----------
  function showEmpty() {
    const empty = $('#emptyState');
    const q     = $('#questionState');
    if (!empty.classList.contains('hidden')) return;
    q.classList.add('hidden');
    empty.classList.remove('hidden');
    empty.classList.remove('projector-enter');
    void empty.offsetWidth;
    empty.classList.add('projector-enter');
  }

  function showQuestion(question) {
    const empty = $('#emptyState');
    const q     = $('#questionState');
    $('#questionName').textContent = question.participant_name;
    $('#questionText').textContent = question.question;
    empty.classList.add('hidden');
    q.classList.remove('hidden');
    q.classList.remove('projector-enter');
    void q.offsetWidth;
    q.classList.add('projector-enter');
  }

  // ---------- Polling da pergunta ativa ----------
  async function pollPanel() {
    try {
      const res = await fetch(API.panelGet, { cache: 'no-store' });
      const json = await res.json();
      if (!json.ok) return;

      // Se a versão mudou ou a pergunta mudou, atualiza
      const newQid = json.active ? json.question.id : null;
      if (json.version !== currentVersion || newQid !== currentQuestionId) {
        currentVersion = json.version;
        currentQuestionId = newQid;
        if (json.active) showQuestion(json.question);
        else             showEmpty();
      }
    } catch (e) {
      // Silêncio - vai tentar de novo
    }
  }

  // ---------- Polling das configurações visuais ----------
  async function pollSettings() {
    try {
      const res = await fetch(API.settingsGet, { cache: 'no-store' });
      const json = await res.json();
      if (!json.ok) return;

      const bg   = json.data.background_color;
      const font = json.data.font_color;

      if (bg !== currentBg || font !== currentFont) {
        currentBg = bg;
        currentFont = font;
        document.body.style.backgroundColor = bg;
        document.body.style.color = font;
        const stage = $('#stage');
        stage.style.backgroundColor = bg;
        stage.style.color = font;
      }
    } catch (e) {}
  }

  // ---------- Controles (fullscreen / ocultar) ----------
  function bindControls() {
    $('#btnFullscreen').addEventListener('click', () => {
      if (!document.fullscreenElement) {
        (document.documentElement.requestFullscreen || (() => {})).call(document.documentElement);
      } else {
        document.exitFullscreen && document.exitFullscreen();
      }
    });

    $('#btnHide').addEventListener('click', () => {
      document.body.classList.add('hide-cursor');
      $('#controls').style.display = 'none';
    });

    // Atalhos: F = fullscreen, Esc = sair
    document.addEventListener('keydown', (e) => {
      if (e.key === 'f' || e.key === 'F') {
        $('#btnFullscreen').click();
      }
    });
  }

  // ---------- Boot ----------
  document.addEventListener('DOMContentLoaded', () => {
    bindControls();
    pollPanel();
    pollSettings();
    setInterval(pollPanel,    POLL_MS);
    setInterval(pollSettings, SETTINGS_MS);
  });
})();
