<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Portal Parceiros - Bione</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Teko:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/313adf4cdc.js" crossorigin="anonymous"></script>

    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { dark: '#1B1B1B', darkLighter: '#252525', accentLaranja: '#F4AA48', accentVermelho: '#E34F57' },
                    fontFamily: { teko: ['Teko', 'sans-serif'], inter: ['Inter', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .input-code { letter-spacing: 0.5em; text-align: center; font-size: 1.5rem; font-weight: 700; }
        .app-input {
            width: 100%; padding: 16px; border-radius: 12px; font-size: 15px;
            background: #FFFFFF; border: 1px solid #E5E7EB; outline: none; transition: all 0.2s;
        }
        .dark .app-input { background: #252525; color: #fff; border-color: #333; }
        .app-input:focus { border-color: #F4AA48; box-shadow: 0 0 0 1px #F4AA48; }
        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-up { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 0.6s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .toast {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 999;
            padding: 14px 24px; border-radius: 14px; font-size: 14px; font-weight: 600;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12); opacity: 0; transition: opacity 0.3s;
            max-width: 90%; text-align: center;
        }
        .toast.show { opacity: 1; }
        .toast.success { background: #DEF7EC; color: #03543F; }
        .toast.error { background: #FDE8E8; color: #9B1C1C; }
    </style>
</head>
<body class="bg-[#F9FAFB] dark:bg-dark text-gray-900 dark:text-gray-100 transition-colors duration-300 min-h-screen flex flex-col relative">

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<div class="absolute top-6 right-6 z-10">
    <button onclick="toggleTheme()" class="w-10 h-10 rounded-full bg-white dark:bg-darkLighter shadow-sm border border-gray-100 dark:border-gray-800 flex items-center justify-center active:scale-95 transition">
        <i class="fas fa-moon dark:hidden text-gray-600"></i>
        <i class="fas fa-sun hidden dark:block text-accentLaranja"></i>
    </button>
</div>

<div class="flex-1 px-6 flex flex-col justify-start max-w-md mx-auto w-full pt-20 pb-10">

    <div class="text-center mb-8 flex flex-col items-center">
        <img id="logo" src="https://bionetecnologia.com.br/logos/logo-bione-preta.png"
             class="h-32 object-contain transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] mb-6" alt="Bione Logo">
        <h2 class="text-[11px] font-bold uppercase tracking-widest text-accentLaranja mb-2">Portal de Parceiros</h2>
        <h1 id="main-title" class="text-3xl font-bold font-inter tracking-tight transition-all duration-300">Acesse sua conta</h1>
        <p id="main-subtitle" class="text-sm text-gray-500 dark:text-gray-400 mt-2 transition-all duration-300">Digite seu e-mail para receber o código de acesso.</p>
    </div>

    <div class="relative w-full">

        <!-- STEP: Email -->
        <div id="step-email" class="animate-slide-up w-full">
            <input type="email" id="login_email" class="app-input mb-5 shadow-sm" placeholder="seu@email.com" autocomplete="email"
                   onkeydown="if(event.key==='Enter') verificarLogin()">

            <button id="btn_login" onclick="verificarLogin()" class="w-full bg-accentLaranja text-white font-bold py-4 rounded-xl active:scale-[0.98] transition shadow-lg shadow-orange-500/20">
                Continuar
            </button>

            <button onclick="show('step-register')" class="w-full text-gray-500 dark:text-gray-400 font-medium py-4 mt-2 active:scale-[0.98] transition">
                Não tem conta? <span class="text-accentLaranja font-bold">Cadastre-se</span>
            </button>
        </div>

        <!-- STEP: OTP Code -->
        <div id="step-code" class="hidden w-full">
            <input type="text" id="otp" maxlength="6" class="app-input input-code mb-4 shadow-sm" placeholder="000000" inputmode="numeric"
                   onkeydown="if(event.key==='Enter') validarCodigo()">

            <button id="btn_otp" onclick="validarCodigo()" class="w-full bg-dark dark:bg-white dark:text-dark text-white font-bold py-4 rounded-xl active:scale-[0.98] transition shadow-lg">
                Acessar Portal
            </button>

            <p id="otp-timer" class="text-center text-xs text-gray-400 mt-3"></p>

            <button onclick="show('step-email')" class="w-full text-gray-500 text-sm font-medium py-4 mt-1 active:scale-[0.98] transition">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </button>
        </div>

        <!-- STEP: Cadastro -->
        <div id="step-register" class="hidden w-full">
            <form id="form-cadastro" class="space-y-4 no-scrollbar overflow-y-auto max-h-[55vh] pb-4 px-1" onsubmit="event.preventDefault(); finalizarCadastro();">

                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block uppercase tracking-wide">E-mail</label>
                    <input type="email" id="cad_email" class="app-input shadow-sm" placeholder="seu@email.com" required onblur="verificarEmailCadastro(this.value)">
                    <span id="erro_email" class="text-xs text-red-500 hidden mt-1"><i class="fas fa-exclamation-circle"></i> Este e-mail já possui cadastro.</span>
                </div>

                <input type="text" id="cad_nome" class="app-input shadow-sm" placeholder="Nome Completo" required>
                <input type="text" id="cad_cpf" class="app-input shadow-sm" placeholder="CPF" required oninput="maskCPF(this)" maxlength="14">
                <input type="tel" id="cad_telefone" class="app-input shadow-sm" placeholder="Telefone / WhatsApp" required oninput="maskPhone(this)" maxlength="15">

                <div>
                    <label class="text-xs font-semibold text-gray-500 mb-1 block uppercase tracking-wide">Data de Nascimento</label>
                    <input type="date" id="cad_nascimento" class="app-input shadow-sm" required>
                </div>

                <div class="pt-4 mt-2 border-t border-gray-200 dark:border-darkLighter">
                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2 block uppercase tracking-wide">Dados Bancários (Pix)</label>
                    <div class="flex gap-2 mb-3">
                        <select id="cad_tipo_chave" class="app-input shadow-sm w-1/3 p-3">
                            <option value="cpf">CPF</option>
                            <option value="celular">Celular</option>
                            <option value="email">E-mail</option>
                            <option value="aleatoria">Aleatória</option>
                        </select>
                        <input type="text" id="cad_chave_pix" class="app-input shadow-sm w-2/3" placeholder="Sua Chave Pix" required>
                    </div>
                </div>

                <button type="submit" id="btn_cadastrar" class="w-full bg-accentLaranja text-white font-bold py-4 rounded-xl mt-2 active:scale-[0.98] transition shadow-lg shadow-orange-500/20">
                    Concluir Cadastro
                </button>

                <button type="button" onclick="show('step-email')" class="w-full text-gray-500 text-sm font-medium py-3 active:scale-[0.98] transition">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar ao Login
                </button>
            </form>
        </div>

    </div>
</div>

<script>
    // ===== Estado Global =====
    let currentEmail = '';
    let otpCountdown = null;

    // ===== Toast =====
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = 'toast ' + type + ' show';
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    // ===== Loading nos botões =====
    function setLoading(btnId, loading) {
        const btn = document.getElementById(btnId);
        if (loading) {
            btn.dataset.text = btn.innerHTML;
            btn.innerHTML = '<span class="spinner"></span>';
            btn.disabled = true;
            btn.classList.add('opacity-70');
        } else {
            btn.innerHTML = btn.dataset.text || btn.innerHTML;
            btn.disabled = false;
            btn.classList.remove('opacity-70');
        }
    }

    // ===== API Helper =====
    async function api(action, payload = {}) {
        const res = await fetch('ajax_auth.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...payload })
        });
        return res.json();
    }

    // ===== Theme Toggle =====
    function toggleTheme() {
        document.documentElement.classList.toggle('dark');
        const isDark = document.documentElement.classList.contains('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.getElementById('logo').style.filter = isDark ? 'invert(1)' : 'none';
    }
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        document.getElementById('logo').style.filter = 'invert(1)';
    }

    // ===== Máscaras =====
    function maskCPF(i) {
        let v = i.value.replace(/\D/g, "");
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        i.value = v;
    }
    function maskPhone(i) {
        let v = i.value.replace(/\D/g, "");
        v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
        v = v.replace(/(\d)(\d{4})$/, "$1-$2");
        i.value = v;
    }

    // ===== Navegação com Animação =====
    function show(id) {
        const logo = document.getElementById('logo');
        const title = document.getElementById('main-title');
        const subtitle = document.getElementById('main-subtitle');

        ['step-email', 'step-code', 'step-register'].forEach(el => {
            const element = document.getElementById(el);
            element.classList.add('hidden');
            element.classList.remove('animate-slide-up');
        });

        const target = document.getElementById(id);
        target.classList.remove('hidden');
        void target.offsetWidth;
        target.classList.add('animate-slide-up');

        if (id === 'step-register') {
            logo.classList.replace('h-32', 'h-16');
            title.innerText = "Criar Conta";
            subtitle.innerText = "Complete os dados para acessar as vagas e pagamentos.";
            const emailDigitado = document.getElementById('login_email').value;
            if (emailDigitado) document.getElementById('cad_email').value = emailDigitado;
        } else if (id === 'step-code') {
            logo.classList.replace('h-32', 'h-16');
            title.innerText = "Código enviado!";
            subtitle.innerText = "Digite o código de 6 dígitos enviado para seu e-mail.";
            startOtpTimer();
        } else {
            logo.classList.replace('h-16', 'h-36');
            title.innerText = "Acesse sua conta";
            subtitle.innerText = "Digite seu e-mail para receber o código de acesso.";
        }
    }

    // ===== Timer do OTP =====
    function startOtpTimer() {
        clearInterval(otpCountdown);
        let seconds = 600; // 10 minutos
        const el = document.getElementById('otp-timer');
        otpCountdown = setInterval(() => {
            seconds--;
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            el.textContent = `Código expira em ${m}:${String(s).padStart(2, '0')}`;
            if (seconds <= 0) {
                clearInterval(otpCountdown);
                el.textContent = 'Código expirado. Volte e tente novamente.';
                el.classList.add('text-red-400');
            }
        }, 1000);
    }

    // ===== LOGIN: Verifica email e envia OTP =====
    async function verificarLogin() {
        const email = document.getElementById('login_email').value.trim();
        if (!email) return showToast('Digite um e-mail válido.', 'error');

        setLoading('btn_login', true);
        try {
            const res = await api('check_login', { email });

            if (res.error) {
                showToast(res.error, 'error');
                return;
            }

            if (res.exists) {
                currentEmail = email;
                show('step-code');
                showToast('Código enviado! Verifique seu e-mail.', 'success');
                setTimeout(() => document.getElementById('otp').focus(), 300);
            } else {
                showToast('E-mail não cadastrado. Crie sua conta.', 'error');
                show('step-register');
            }
        } catch (e) {
            showToast('Erro de conexão. Tente novamente.', 'error');
        } finally {
            setLoading('btn_login', false);
        }
    }

    // ===== CADASTRO: Verifica se email já existe =====
    async function verificarEmailCadastro(email) {
        if (!email) return;
        try {
            const res = await api('check_register', { email });
            const erroSpan = document.getElementById('erro_email');
            const btnSubmit = document.getElementById('btn_cadastrar');

            if (res.exists) {
                erroSpan.classList.remove('hidden');
                btnSubmit.disabled = true;
                btnSubmit.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                erroSpan.classList.add('hidden');
                btnSubmit.disabled = false;
                btnSubmit.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } catch (e) {
            // silencioso
        }
    }

    // ===== OTP: Valida código =====
    async function validarCodigo() {
        const code = document.getElementById('otp').value.trim();
        if (code.length !== 6) return showToast('Digite o código de 6 dígitos.', 'error');

        setLoading('btn_otp', true);
        try {
            const res = await api('verify_otp', { email: currentEmail, code });

            if (res.success) {
                clearInterval(otpCountdown);
                showToast('Login realizado!', 'success');
                setTimeout(() => window.location.href = 'dashboard.php', 800);
            } else {
                showToast(res.message || 'Código inválido.', 'error');
            }
        } catch (e) {
            showToast('Erro de conexão.', 'error');
        } finally {
            setLoading('btn_otp', false);
        }
    }

    // ===== CADASTRO: Finaliza =====
    async function finalizarCadastro() {
        const payload = {
            nome:           document.getElementById('cad_nome').value.trim(),
            email:          document.getElementById('cad_email').value.trim(),
            cpf:            document.getElementById('cad_cpf').value.trim(),
            telefone:       document.getElementById('cad_telefone').value.trim(),
            data_nascimento: document.getElementById('cad_nascimento').value,
            tipo_chave_pix: document.getElementById('cad_tipo_chave').value,
            chave_pix:      document.getElementById('cad_chave_pix').value.trim()
        };

        if (!payload.nome || !payload.email || !payload.cpf || !payload.telefone || !payload.chave_pix) {
            return showToast('Preencha todos os campos obrigatórios.', 'error');
        }

        setLoading('btn_cadastrar', true);
        try {
            const res = await api('register', payload);

            if (res.error) {
                showToast(res.error, 'error');
                return;
            }

            if (res.success) {
                currentEmail = payload.email;
                showToast(res.message, 'success');
                show('step-code');
                setTimeout(() => document.getElementById('otp').focus(), 300);
            }
        } catch (e) {
            showToast('Erro de conexão.', 'error');
        } finally {
            setLoading('btn_cadastrar', false);
        }
    }
</script>
</body>
</html>