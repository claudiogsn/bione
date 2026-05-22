/* ==========================================================
   Página pública - envio de perguntas
   ========================================================== */
(function () {
  'use strict';

  const API = {
    schedule: 'api/schedule_list.php',
    save: 'api/question_save.php',
  };

  // ----- Utilidades -----
  function $(sel, root = document) { return root.querySelector(sel); }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, s => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[s]);
  }

  function toast(message, type = 'success') {
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.textContent = message;
    document.body.appendChild(el);
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transition = 'opacity .3s ease';
      setTimeout(() => el.remove(), 300);
    }, 2800);
  }

  // ----- Cronograma -----
  async function loadSchedule() {
    const list = $('#scheduleList');
    try {
      const res = await fetch(API.schedule, { cache: 'no-store' });
      const json = await res.json();
      if (!json.ok) throw new Error();

      if (!json.data.length) {
        list.innerHTML = `<p class="text-white/60 text-sm">Cronograma ainda não publicado.</p>`;
        return;
      }

      list.innerHTML = json.data.map(item => {
        const time = item.end_time
          ? `${item.start_time} — ${item.end_time}`
          : item.start_time;
        return `
          <div class="timeline-dot pl-1">
            <div class="text-xs font-semibold tracking-wide" style="color: var(--br-yellow);">${escapeHtml(time)}</div>
            <div class="text-base font-semibold mt-0.5">${escapeHtml(item.title)}</div>
            ${item.description
            ? `<div class="text-sm text-white/60 mt-1 leading-snug">${escapeHtml(item.description)}</div>`
            : ''}
          </div>
        `;
      }).join('');
    } catch (e) {
      list.innerHTML = `<p class="text-red-300 text-sm">Não foi possível carregar o cronograma.</p>`;
    }
  }

  // ----- Toggle cronograma -----
  function bindToggle() {
    const btn = $('#toggleSchedule');
    const wrap = $('#scheduleWrap');
    let collapsed = false;
    btn.addEventListener('click', () => {
      collapsed = !collapsed;
      if (collapsed) {
        wrap.style.maxHeight = '0px';
        wrap.style.paddingTop = '0';
        wrap.style.paddingBottom = '0';
        wrap.style.overflow = 'hidden';
        wrap.style.opacity = '0';
        btn.textContent = 'Mostrar';
      } else {
        wrap.style.maxHeight = '';
        wrap.style.paddingTop = '';
        wrap.style.paddingBottom = '';
        wrap.style.overflow = '';
        wrap.style.opacity = '';
        btn.textContent = 'Recolher';
      }
    });
  }

  // ----- Contador de caracteres -----
  function bindCharCount() {
    const ta = $('#question');
    const counter = $('#charCount');
    ta.addEventListener('input', () => {
      counter.textContent = `${ta.value.length} / 2000`;
    });
  }

  // ----- Envio do formulário -----
  function bindForm() {
    const form = $('#questionForm');
    const btn = $('#submitBtn');
    const btnText = $('#btnText');
    const success = $('#successState');
    const sendAgain = $('#sendAnother');
    const fields = form.querySelectorAll('input, textarea, button[type="submit"]');
    const body = form; // contêiner para esconder campos no sucesso

    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const name = $('#name').value.trim();
      const question = $('#question').value.trim();

      if (!name || !question) {
        toast('Preencha nome e pergunta', 'error');
        return;
      }

      // Estado "enviando"
      btn.disabled = true;
      btnText.textContent = 'Enviando...';

      try {
        const res = await fetch(API.save, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ participant_name: name, question })
        });
        const json = await res.json();
        if (!json.ok) throw new Error(json.error || 'Erro');

        // Esconde campos, mostra sucesso
        Array.from(body.children).forEach(el => {
          if (el.id !== 'successState') el.classList.add('hidden');
        });
        success.classList.remove('hidden');

      } catch (err) {
        toast(err.message || 'Não foi possível enviar', 'error');
        btn.disabled = false;
        btnText.textContent = 'Enviar pergunta';
      }
    });

    sendAgain.addEventListener('click', () => {
      // Reset
      $('#question').value = '';
      $('#charCount').textContent = '0 / 2000';
      Array.from(body.children).forEach(el => {
        if (el.id !== 'successState') el.classList.remove('hidden');
      });
      success.classList.add('hidden');
      $('#submitBtn').disabled = false;
      $('#btnText').textContent = 'Enviar pergunta';
      $('#question').focus();
    });
  }

  // ----- Boot -----
  document.addEventListener('DOMContentLoaded', () => {
    loadSchedule();
    bindToggle();
    bindCharCount();
    bindForm();
  });
})();