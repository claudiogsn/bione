# Perguntas do Evento

Sistema web simples para gerenciar perguntas de participantes em eventos,
com página pública (QR Code), painel administrativo e tela de exibição para
telão/projetor.

## Stack

- **PHP** 7.4+ (procedural, PDO, sem framework, sem composer)
- **MySQL** 5.7+ / MariaDB 10+
- **JavaScript** puro (sem build, sem dependências)
- **Tailwind CSS** via CDN
- **Fonte Inter** via Google Fonts

## Estrutura

```
perguntas-evento/
├── public/                    ← document root
│   ├── index.php              (página pública / QR Code)
│   ├── admin.php              (painel administrativo)
│   ├── painel.php             (telão / projetor)
│   ├── assets/
│   │   ├── css/app.css
│   │   └── js/{public,admin,painel}.js
│   └── api/                   (endpoints JSON)
│       ├── schedule_list.php
│       ├── schedule_save.php
│       ├── schedule_delete.php
│       ├── question_save.php
│       ├── question_list.php
│       ├── question_delete.php
│       ├── panel_set.php
│       ├── panel_get.php
│       ├── panel_clear.php
│       ├── settings_get.php
│       └── settings_save.php
├── config/
│   └── db.php                 (conexão PDO)
└── sql/
    └── schema.sql             (banco de dados)
```

## Instalação

1. **Banco de dados** – rode o schema:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```

2. **Credenciais** – edite `config/db.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_NAME', 'perguntas_evento');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Servidor web** – aponte o document root para `public/`.

   Para testar rápido, use o servidor embutido do PHP:
   ```bash
   cd public && php -S 0.0.0.0:8080
   ```

## URLs

| Página                       | URL                              |
|------------------------------|----------------------------------|
| Página pública (QR Code)     | `http://seu-host/index.php`      |
| Painel administrativo        | `http://seu-host/admin.php`      |
| Painel do projetor (telão)   | `http://seu-host/painel.php`     |

## Fluxo de uso

1. Gere um QR Code apontando para `index.php` e exiba no evento.
2. O participante lê o QR, vê o cronograma e envia a pergunta.
3. No `admin.php`, a pergunta aparece automaticamente (refresh a cada 10s).
4. Clique em **"Mostrar no painel"** em qualquer pergunta.
5. A tela `painel.php` (aberta no projetor) detecta via polling (2s) e exibe.
6. **"Limpar painel"** volta a tela ao estado vazio.

## Recursos

### Página pública
- 100% mobile-first
- Cronograma em timeline
- Formulário com feedback visual de sucesso
- Contador de caracteres
- Gradiente aurora animado

### Admin
- Navegação por tabs (Perguntas / Cronograma / Painel visual)
- **Busca em memória** – filtra por pergunta ou nome sem novo request
- CRUD completo do cronograma
- Badges visuais (pendente / exibida / no painel agora)
- Configuração de cores com preview e presets para chroma key

### Painel projetor
- Fullscreen (tecla **F** ou botão)
- Polling a cada 2 segundos
- Cores customizáveis (ideal para chroma key)
- Transição suave entre perguntas
- Estado vazio elegante
- Controles escondem sozinhos

## Dicas

- **Chroma key**: use `#00b140` (verde) ou `#0047bb` (azul) como fundo.
- **Múltiplos projetores**: basta abrir `painel.php` em várias janelas.
- **Segurança**: se for expor em produção, proteja `admin.php` com HTTP Basic Auth no servidor web (ou adicione uma camada de login).
