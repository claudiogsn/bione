<?php
// Painel do projetor (telão)
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel · Telão</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">

  <style>
    html, body {
      margin: 0;
      height: 100%;
      overflow: hidden;
    }
    body {
      background: #0f172a;
      color: #ffffff;
    }
    #stage {
      position: fixed;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 6vh 8vw;
      transition: background-color .4s ease, color .4s ease;
    }
    .hide-cursor #stage { cursor: none; }

    /* Controles no canto */
    #controls {
      position: fixed;
      top: 16px; right: 16px;
      display: flex; gap: 8px;
      z-index: 10;
      opacity: 0;
      transition: opacity .25s ease;
    }
    body:hover #controls,
    #controls:focus-within { opacity: 1; }

    .ctrl-btn {
      background: rgba(0, 0, 0, .35);
      color: #fff;
      border: 1px solid rgba(255, 255, 255, .2);
      border-radius: 10px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 500;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      cursor: pointer;
      transition: background .15s ease;
    }
    .ctrl-btn:hover { background: rgba(0, 0, 0, .55); }
  </style>
</head>
<body>

  <div id="controls">
    <button id="btnFullscreen" class="ctrl-btn" title="Fullscreen (F)">⛶ Fullscreen</button>
    <button id="btnHide" class="ctrl-btn" title="Esconder controles">× Ocultar</button>
  </div>

  <main id="stage">

    <!-- Estado vazio -->
    <div id="emptyState" class="text-center projector-enter">
      <div class="projector-name" style="opacity:.5; font-size: clamp(1rem, 1.8vw, 1.75rem); text-transform: uppercase; letter-spacing: .3em;">
        Aguardando pergunta
      </div>
      <div class="mt-8 flex justify-center">
        <div class="w-3 h-3 rounded-full bg-current opacity-60 mx-1" style="animation: pulse-ring 1.4s infinite"></div>
        <div class="w-3 h-3 rounded-full bg-current opacity-60 mx-1" style="animation: pulse-ring 1.4s infinite .2s"></div>
        <div class="w-3 h-3 rounded-full bg-current opacity-60 mx-1" style="animation: pulse-ring 1.4s infinite .4s"></div>
      </div>
    </div>

    <!-- Pergunta -->
    <div id="questionState" class="hidden w-full max-w-[92vw] text-center">
      <div id="questionName" class="projector-name mb-8"></div>
      <div id="questionText" class="projector-question"></div>
    </div>

  </main>

  <script src="assets/js/painel.js"></script>
</body>
</html>
