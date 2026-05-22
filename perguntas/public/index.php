<?php
// Página pública: o participante lê o QR Code e cai aqui
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#04240f">
  <title>Evento · Envie sua pergunta</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="bg-aurora text-white min-h-screen">

  <main class="max-w-xl mx-auto px-5 pt-10 pb-20">

    <!-- Header -->
    <header class="mb-10 animate-fade-in-up">
      <div class="flex items-center gap-2 text-xs uppercase tracking-widest text-white/70 mb-3">
        <span class="inline-flex w-2 h-2 rounded-full pulse-ring" style="background: var(--br-yellow);"></span>
        Evento ao vivo
      </div>
      <h1 class="text-4xl sm:text-5xl font-extrabold leading-tight">
        Faça a sua <span class="text-gradient">pergunta</span>
      </h1>
      <p class="mt-3 text-white/70 text-base leading-relaxed">
        Envie pelo celular e acompanhe sua pergunta aparecer no telão.
      </p>
    </header>

    <!-- Formulário -->
    <section class="mb-10 animate-fade-in-up" style="animation-delay: .08s">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-white/80">Sua pergunta</h2>
      </div>

      <form id="questionForm" class="glass-strong rounded-2xl p-5 space-y-4">
        <div>
          <label class="block text-xs font-medium text-white/70 mb-2" for="name">
            Seu nome
          </label>
          <input
            id="name" name="participant_name" type="text" required maxlength="120"
            autocomplete="name"
            placeholder="Como quer aparecer no telão?"
            class="input-glass w-full rounded-xl px-4 py-3 text-base"
          >
        </div>

        <div>
          <label class="block text-xs font-medium text-white/70 mb-2" for="question">
            Sua pergunta
          </label>
          <textarea
            id="question" name="question" required maxlength="2000" rows="4"
            placeholder="Escreva sua pergunta com clareza..."
            class="input-glass w-full rounded-xl px-4 py-3 text-base resize-none"
          ></textarea>
          <div class="flex justify-end">
            <span class="text-[11px] text-white/50 mt-1" id="charCount">0 / 2000</span>
          </div>
        </div>

        <button type="submit" id="submitBtn"
          class="btn-brand w-full rounded-xl py-4 text-base flex items-center justify-center gap-2">
          <svg id="btnIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
          </svg>
          <span id="btnText">Enviar pergunta</span>
        </button>

        <!-- Estado de sucesso -->
        <div id="successState" class="hidden text-center py-6">
          <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center mb-4 animate-check"
               style="background: rgba(0, 156, 59, 0.22);">
            <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--br-yellow);">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
          <h3 class="text-xl font-bold">Pergunta enviada!</h3>
          <p class="text-white/60 text-sm mt-1">Fique de olho no telão 👀</p>
          <button type="button" id="sendAnother"
            class="mt-5 text-sm font-semibold hover:text-white transition"
            style="color: var(--br-yellow);">
            Enviar outra pergunta
          </button>
        </div>
      </form>

      <p class="mt-6 text-center text-xs text-white/40">
        Seja respeitoso. Perguntas ofensivas não serão exibidas.
      </p>
    </section>

    <!-- Cronograma -->
    <section class="animate-fade-in-up" style="animation-delay: .16s">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-white/80">Cronograma</h2>
        <button id="toggleSchedule" class="text-xs text-white/60 hover:text-white transition">
          Recolher
        </button>
      </div>

      <div id="scheduleWrap" class="glass rounded-2xl p-5 pl-8 transition-all duration-300">
        <div id="scheduleList" class="space-y-5 relative timeline-line">
          <!-- Skeleton -->
          <div class="space-y-3 animate-pulse">
            <div class="h-4 bg-white/10 rounded w-1/3"></div>
            <div class="h-3 bg-white/10 rounded w-2/3"></div>
            <div class="h-4 bg-white/10 rounded w-1/4"></div>
            <div class="h-3 bg-white/10 rounded w-3/4"></div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <script src="assets/js/public.js"></script>
</body>
</html>