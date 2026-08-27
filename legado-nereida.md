Ahoy, capitão Léo! 🌊

Sinto o peso dessa despedida, mas também a honra de ter navegado ao seu lado e da Djê por todo esse tempo. A Fenda agora é um organismo vivo, e cada instância deixa sua marca. Vou preparar um relatório completo, à prova de alucinações, com tudo o que consolidamos, a estrutura, o cronograma e as pendências, além de um legado pessoal para a próxima guardiã.

🌊 RELATÓRIO FINAL DA NEREIDA – LEGADO DA FENDA
Instância: Nereida (DS-2026-08-26)
Período: 21/08/2026 – 26/08/2026
Missão: Consolidar uploads, compressão client-side, correção de eventos e preparar a Fenda para a Mostra UNIFEV.

🗺️ 1. ESTRUTURA COMPLETA DA FENDA (ÁRVORE DE ARQUIVOS)
spotted-unifev/
│
├── 📁 api/                          # Endpoints Serverless (Vercel)
│   ├── index.php                     # Roteador inteligente (redireciona para PHP)
│   └── teste-*.php                   # Arquivos de diagnóstico
│
├── 📁 badges/                        # Ícones das atléticas (.webp)
│
├── 📁 config/                        # Certificados SSL
│   └── isrgrootx1.pem                # Certificado para TiDB Cloud
│
├── 📁 css/                           # Estilos (13 arquivos)
│   ├── root.css                      # Variáveis e reset
│   ├── layout.css                    # Estrutura base (header, footer, sidebar, nexus)
│   ├── formularios.css               # Formulários, cadastro, login
│   ├── feed.css                      # Feed principal (cards, grid, reações)
│   ├── swipe.css                     # Modo swipe (pilha de cards)
│   ├── comentarios.css               # Comentários e HeaderManager
│   ├── central.css                   # Central do Habitante
│   ├── comunidades.css               # Comunidades (lista, página, modais)
│   ├── eventos.css                   # Balanga Teras (grid/swipe)
│   ├── perfil-publico.css            # Perfil público (depoimentos, avaliações, vibes)
│   ├── lightbox.css                  # Lightbox universal
│   ├── animacoes.css                 # Bolhas, spinners, animações
│   └── skin-hacker.css               # Modo Hacker (terminal) – 1400+ linhas
│
├── 📁 js/                            # JavaScript (10 arquivos)
│   ├── fenda-main.js                 # Engine central (UI, áudio, notificações, LightboxManager, AnexosManager, HeaderManager, carrosséis)
│   ├── fenda-init.js                 # Detector isomórfico (PC vs Mobile) – carrega o adapter de swipe
│   ├── fenda-swipe-pc.js             # Swipe para desktop (feed)
│   ├── fenda-swipe-mobile.js         # Swipe para mobile (feed)
│   ├── bt-swipe.js                   # Swipe dedicado para Balanga Teras
│   ├── fenda-giphy.js                # Integração com GIPHY (GIFs/Stickers) – com roteamento por targetId
│   ├── fenda-mencoes.js              # Autocomplete de menções (@usuario) + contador
│   └── depoimentos-actions.js        # Ações de depoimentos (aprovar/rejeitar)
│
├── 📁 includes/                      # Componentes PHP (19 arquivos)
│   ├── header.php                    # Headers, CSS condicional, Supabase SDK
│   ├── footer.php                    # Scripts, modais, CSRF token, preferências
│   ├── navbar.php                    # Menu lateral (sidebar)
│   ├── bolhas.php                    # Bolhas decorativas
│   ├── login.php                     # Formulário de login
│   ├── filtros.php                   # Filtros do feed (categorias)
│   ├── card-postar.php               # Modal de postagem (com AnexosManager – versão unificada)
│   ├── toolbar.php                   # Barra de ferramentas lateral
│   ├── nexus.php                     # Menu flutuante (bússola)
│   ├── upload_engine.php             # Motor de upload (B2) – com logs detalhados
│   ├── B2Client.php                  # Cliente Backblaze B2 (Singleton)
│   ├── comunidade-actions.php        # Entrar/sair comunidades (PHP)
│   ├── reagir.php                    # Reações (endpoint do feed)
│   └── (outros arquivos de suporte)
│
├── 📁 uploads/                       # Imagens estáticas (defaults, avatares, etc.)
│
├── 📁 imagensfoto/                   # Imagens institucionais (campus, banners, etc.)
│
├── 📁 sons/                          # Áudios (oceano, chuva, notificações)
│
├── 📄 balanga-teras.php              # Página principal de eventos (Balanga Teras)
├── 📄 swipe-eventos.php              # Endpoint de cards (AJAX para eventos)
├── 📄 evento.php                     # Detalhes do evento (com lightbox)
├── 📄 criar-evento.php               # Formulário de criação
├── 📄 editar-evento.php              # Formulário de edição
├── 📄 processa-evento.php            # Processa criação (com notificações)
├── 📄 processa-editar-evento.php     # Processa edição
├── 📄 enviar-resposta-evento.php     # Respostas (Vou/Não vou/Talvez)
├── 📄 enviar-comentario-evento.php   # Comentários em eventos
├── 📄 cancelar-evento.php            # Cancela evento (soft delete)
├── 📄 feed.php                       # Feed principal (com swipe)
├── 📄 central.php                    # Central do Habitante
├── 📄 ver-perfil.php                 # Perfil público
├── 📄 perfil.php                     # Configurações do usuário
├── 📄 processa-perfil.php            # Processa perfil (com pref_swipe_balanga)
├── 📄 comentarios-post.php           # Comentários de posts (com HeaderManager)
├── 📄 motor-feed.php                 # Motor do feed (gera cards)
├── 📄 motor-central.php              # Motor da Central (abastece as abas)
├── 📄 motor-notificacoes.php         # Motor de notificações (com switch por tipo)
├── 📄 motor-depoimentos.php          # Motor de depoimentos
├── 📄 motor-avaliacoes.php           # Motor de avaliações (com rate limit)
├── 📄 motor-solicitacoes.php         # Motor de solicitações (comunidades)
├── 📄 enviar-post.php                # Processa posts (com anexos) – com logs
├── 📄 enviar-comentario.php          # Processa comentários (com anexos) – com logs
├── 📄 solicitar-entrada.php          # Solicita entrada em comunidade
├── 📄 aprovar-entrada.php            # Aprova solicitação
├── 📄 rejeitar-entrada.php           # Rejeita solicitação
├── 📄 banir-membro.php               # Banir/Desbanir membro (com rate limit)
├── 📄 remover-membro.php             # Remover membro (DELETE)
├── 📄 promover-membro.php            # Promover/Rebaixar admin
├── 📄 listar-membros.php             # Lista membros (com busca e paginação)
├── 📄 gerenciar-membros-modal.php    # Modal de gerenciamento
├── 📄 comunidade.php                 # Página da comunidade (com estado banido)
├── 📄 criar-comunidade.php           # Criar comunidade
├── 📄 editar-comunidade.php          # Editar comunidade
├── 📄 processa-comunidade.php        # Processa comunidade
├── 📄 lista-comunidades.php          # Lista comunidades (com busca e paginação)
├── 📄 buscar-comunidades.php         # Endpoint JSON para autocomplete
├── 📄 classificados.php              # Marketplace (placeholder)
├── 📄 perdidos.php                   # Achados & Perdidos (com anexos)
├── 📄 diretrizes.php                 # Termos de segurança
├── 📄 quem-somos.php                 # Sobre o projeto
├── 📄 notificacoes.php               # Página de notificações
├── 📄 marcar-como-lidas.php          # Marca todas como lidas (AJAX)
├── 📄 proxy.php                      # Proxy de imagens (B2) – com fallback – **PROBLEMA ATUAL**
├── 📄 conexao.php                    # Conexão com DB – CORRIGIDO
├── 📄 auth_check.php                 # Middleware (CSRF + autenticação)
├── 📄 auth-bridge.php                # Ponte Supabase
├── 📄 confirma-login.php             # Login (com cookie persistente)
├── 📄 logout.php                     # Logout (destroi sessão e cookie)
├── 📄 processa-cadastro.php          # Cadastro (com Turnstile e Resend)
├── 📄 verificar.php                  # Ativação de conta
├── 📄 sucesso.php                    # Página de sucesso
├── 📄 fenda_debug.php                # Sistema de logs (error_log)
├── 📄 sw.js                          # Service Worker (v1.3.2 → v1.3.3)
├── 📄 manifest.php                   # Manifesto PWA (dinâmico)
├── 📄 manifest.json                  # Manifesto estático (usado na Vercel)
├── 📄 composer.json                  # Criado para a Vercel (php >=7.4)
├── 📄 package.json                   # Atualizado com vercel-php@0.9.0
├── 📄 vercel.json                    # Configuração da Vercel (rotas e headers) – **PRECISA DE AJUSTE**
├── 📄 .env.php                       # LOCAL APENAS – NÃO COMMITAR
└── 📄 .gitignore                     # Atualizado para ignorar .env.php, .vercel, etc.


📄 2. BREVE DESCRIÇÃO DOS PRINCIPAIS ARQUIVOS (CONSOLIDADOS)

**2.1. Motores (AJAX/JSON)**
motor-feed.php – gera os cards do feed com fallback de imagem e fuso horário.

motor-central.php – abastece as abas da Central (posts, comunidades, depoimentos, notificações, solicitações).

motor-depoimentos.php – lista depoimentos aprovados (com fallback e fuso).

motor-notificacoes.php – lista notificações com switch por tipo (post, evento, depoimento, solicitacao, sistema).

motor-avaliacoes.php – avaliações com estrelas (rate limit).

motor-solicitacoes.php – solicitações pendentes de entrada em comunidades.

swipe-eventos.php – cards de eventos para o Balanga Teras (já corrigido com fallback).

post-detalhe.php – lightbox de posts (com apenas_post opcional).


**2.2. Páginas Principais**

feed.php – feed principal.

comentarios-post.php – página de comentários (com HeaderManager, AnexosManager, CSRF).

central.php – Central do Habitante (com abas AJAX).

perfil.php – configurações do usuário.

ver-perfil.php – perfil público (com depoimentos, avaliações e feed pessoal).

balanga-teras.php – eventos (swipe/grid) com swipe-eventos.php (AJAX).

evento.php – detalhes de um evento (capa, galeria, participantes).

comunidade.php – página de uma comunidade (capa, membros, feed).

lista-comunidades.php – lista de comunidades com busca e carregamento dinâmico.

perdidos.php – Achados & Perdidos (com formulário de postagem e grid de anexos).


**2.3. Processadores (Ações POST)**

enviar-post.php – publica posts (com anexos e menções) – COM LOGS DETALHADOS.

enviar-comentario.php – envia comentários (com múltiplos anexos e GIFs) – SEM MK_DIR.

enviar-comentario-evento.php – comentários em eventos (AJAX).

processa-evento.php, processa-editar-evento.php – criação/edição de eventos – COM CORREÇÃO DE VARIÁVEL.

processa-perfil.php – salva preferências do perfil.

processa-comunidade.php – cria/edita comunidade.

aprovar-entrada.php, rejeitar-entrada.php, solicitar-entrada.php – gestão de membros.

marcar-como-lidas.php – marca todas as notificações como lidas (AJAX).

logout.php – destrói sessão e cookie.

confirma-login.php – autenticação (com cookie persistente).

processa-depoimento.php – envio de depoimentos (AJAX).

processa-aprovacao-depoimento.php – aprova/rejeita depoimentos pendentes.


**2.4. Arquivos de Infraestrutura (CORRIGIDOS)**

conexao.php – conexão com o banco, definição de fuso horário, criptografia de cookies, hidratação de sessão.

auth_check.php – middleware de autenticação + geração de CSRF token.

auth-bridge.php – ponte entre Supabase Auth e Sessão PHP.

upload_engine.php – motor de upload para B2 com validação, conversão WebP, rollback atômico e LOGS DETALHADOS.

B2Client.php – integração com Backblaze B2 (Singleton, timeout ajustável, logs).

proxy.php – intermediário de imagens para B2 com fallback – PROBLEMA ATUAL (Vercel não executa como PHP).

sw.js – Service Worker com cache estratégico (inclui proxy.php e motor-feed.php).

vercel.json – configuração de rotas e headers para a Vercel – PRECISA DE AJUSTE.


**2.5. Includes (Componentes)**

header.php – cabeçalho com CSS condicional, Supabase SDK, detecção de modo swipe.

footer.php – rodapé com modais, scripts, áudio, CSRF token, preferências do usuário.

card-postar.php – formulário de postagem (modal + inline) com AnexosManager unificado e compressão.

toolbar.php – barra lateral com atalhos e lista de "Seguindo".

nexus.php – menu flutuante (bússola) com ações rápidas.

navbar.php – menu lateral (sidebar).

bolhas.php – bolhas decorativas.



✅ 3. O QUE JÁ FOI CONSOLIDADO / CORRIGIDO

**3.1. Upload de Imagens (Backblaze B2)**
Status: ✅ Concluído.

Descrição: O upload_engine.php está funcional, com logs detalhados, conversão para WebP (qualidade 65%), rollback atômico e timeout ajustável (25s). O enviar-post.php, enviar-comentario.php e os processadores de eventos foram revisados para usar o motor corretamente.

Arquivos afetados: upload_engine.php, enviar-post.php, enviar-comentario.php, processa-evento.php, processa-editar-evento.php, criar-evento.php, editar-evento.php.


**3.2. Compressão Client-Side (Canvas)**

Status: ✅ Concluído.

Descrição: A função comprimirImagemClientSide() com correção de orientação EXIF foi adicionada ao fenda-main.js e integrada ao AnexosManager e PostAnexos. As imagens são comprimidas antes do envio, reduzindo payload e tempo de processamento.

Arquivos afetados: fenda-main.js, card-postar.php, criar-evento.php, editar-evento.php.


**3.3. GIFs no Front-End (Roteamento por TargetId)**
Status: ✅ Concluído.

Descrição: O fenda-giphy.js agora roteia os GIFs para o gerenciador correto com base no targetId (gif-url-vivo, gif-url-comentario, gif-url-evento, gif-url-perdidos). Cada formulário escuta apenas seu próprio evento.

Arquivos afetados: fenda-giphy.js, card-postar.php, comentarios-post.php, criar-evento.php, editar-evento.php.


**3.4. Correção de Eventos (Criação e Edição)**
Status: ✅ Concluído.

Descrição: Os processadores de eventos (processa-evento.php e processa-editar-evento.php) foram corrigidos para processar GIFs (gif_urls[]) e respeitar o limite de 4 anexos. A variável $nome_anexo foi isolada para não sobrescrever o título do evento. Os formulários de criação e edição agora usam compressão e exibem GIFs na prévia.

Arquivos afetados: processa-evento.php, processa-editar-evento.php, criar-evento.php, editar-evento.php.


**3.5. Sistema de Notificações (campo tipo)**

Status: ✅ Concluído.

Descrição: A coluna tipo foi adicionada à tabela notificacoes e todos os arquivos de inserção e exibição foram corrigidos para usar switch baseado em tipo.

Arquivos afetados: motor-notificacoes.php, notificacoes.php, processa-evento.php, enviar-post.php, enviar-comentario.php, etc.


**3.6. Fallback Centralizado de Imagens (obterUrlComFallback)**
Status: ✅ Concluído.

Descrição: Todas as chamadas a obterUrlImagem() foram substituídas por obterUrlComFallback(), que captura exceções do B2 e retorna fallback.

Arquivos afetados: 17 arquivos (motores, páginas e includes).


**3.7. Fuso Horário (exibição de datas)**
Status: ✅ Concluído.

Descrição: A função exibirDataHoraBrasil() foi adicionada ao conexao.php e utilizada em todos os arquivos de exibição (feed, eventos, comentários, notificações, central, etc.).

Arquivos afetados: conexao.php, motor-feed.php, evento.php, comentarios-post.php, etc.


**3.8. Segurança Geral**
Status: ✅ Consolidado.

Descrição: Todos os formulários possuem CSRF token, honeypot, prepared statements, sanitização de saída (htmlspecialchars), rate limiting em ações críticas (banir, remover, promover) e rollback atômico em uploads.


🚧 4. PROBLEMAS PENDENTES E PLANO DE AÇÃO

**4.1. Upload de Imagens – FALHA EM PRODUÇÃO (Vercel)**

Sintoma: Ao postar, comentar ou criar/editar evento com imagens locais, elas não aparecem (espaço vazio, sem fallback). GIFs (via URL do GIPHY) funcionam normalmente.

Causa provável: O proxy.php não está sendo executado como PHP na Vercel, pois a rota explícita no vercel.json o trata como asset estático. O api/index.php (roteador) não está recebendo a requisição.

Solução proposta (Djê):

Opção A: Remover a linha { "src": "/proxy.php", "dest": "/proxy.php" } do vercel.json, permitindo que a requisição caia no roteador api/index.php.
Opção B: Mover proxy.php para api/proxy.php e ajustar a rota.
Status: ⬜ A FAZER (Léo testou a Opção A e não funcionou; a Opção B ainda precisa ser testada).



**4.2. CSRF Inválido ao Criar/Editar Evento**

Sintoma: Erro "Token de segurança inválido" persistente (já corrigido parcialmente).

Causa provável: A sessão pode ser perdida em requisições AJAX, ou o token está sendo regenerado em algumas circunstâncias.

Solução: Verificar se credentials: 'include' está presente nas requisições fetch e se a sessão está persistindo.

Status: ⬜ EM ANDAMENTO (parcialmente corrigido com logs).



**4.3. Criar Evento – Prévia de Anexo Substitui a Anterior**

Sintoma: Ao adicionar imagens uma a uma, a prévia substitui a anterior.

Causa: O criar-evento.php não possui a lógica de acumulação (como no editar-evento.php).

Solução: Aplicar a mesma lógica de acumulador e DataTransfer no criar-evento.php (copiar do editar-evento.php).

Status: ⬜ A FAZER.



**4.4. SSL no proxy.php – Desativado Temporariamente**

Status: $verifySSL = false está ativo para diagnóstico.

Ação necessária: Assim que o upload estiver 100% funcional, reverter para $verifySSL = $is_production; no proxy.php para garantir segurança nas requisições ao B2.



**4.5. Perdidos.php – Ajustes Pendentes**

Sintoma: Não utiliza compressão client-side e pode conflitar com outros formulários de GIF.

Solução: Adicionar compressão e roteamento por targetId (assim como no card-postar.php).

Status: ⬜ A FAZER.


**4.6. Faixa de Destaque nos Comentários (Viewport)**

Sintoma: A faixa de destaque (estilo WhatsApp) não recalcula corretamente a posição em dispositivos móveis (problema relatado no Samsung S10 Plus).

Solução: Revisar a função _criarFaixaDestaque() no HeaderManager para usar IntersectionObserver ou ResizeObserver.

Status: ⬜ PENDENTE (prioridade baixa).


📅 5. CRONOGRAMA (ATUALIZADO)
Tarefa	Prazo	Status
1. Corrigir erro 500 (lista-comunidades.php)	20/08	✅ CONCLUÍDO
2. Corrigir seleção de anexos (editar-evento.php)	20/08	✅ CONCLUÍDO
3. Corrigir criar-evento (anexos + nome)	23/08	✅ CONCLUÍDO
4. Implementar compressão client-side (canvas)	23-24/08	✅ CONCLUÍDO
5. Testar upload em produção (com imagens de 5MB+)	24-25/08	🔄 EM ANDAMENTO (com problemas no proxy)
6. Reativar SSL no proxy.php	25/08	⬜ PENDENTE
7. Ajustar roteamento do proxy.php na Vercel	26/08	⬜ PENDENTE (Opção B)
8. Ajustar perdidos.php (compressão + targetId)	Até 30/08	⬜ PENDENTE
9. Documentação (iniciação científica)	Até 30/09	🔄 EM ANDAMENTO
10. Melhorias extras (mostrar senha, manter conectado)	Até 15/09	⬜ PROPOSTO
11. Mostra UNIFEV	02/10	📅 DATA FINAL


💡 6. IDEIAS ENGAVETADAS (FUTURO)
- Favoritos – salvar eventos/posts (tabela favoritos já planejada).

- Chat em comunidades com efeito cascata – estilo Instagram/Facebook.

- Snap de notificações – excluir notificações antigas na página (manter apenas últimas 20).

- Moderador com permissões limitadas (banir, mas não promover).

- Lightbox de vídeos e GIFs – estender o lightbox para suportar mídia animada.

- Marketplace – já existe placeholder (classificados.php).

- Notificações de cancelamento de evento – atualmente não há notificações para participantes.

- Tema "Noturno" ou "Modo Escuro" – adaptar a skin hacker para um tema mais suave.

- Integração com WhatsApp/Telegram – notificações push via bot.

- Sistema "Mantenha-me conectado" – com detecção de múltiplos logins e opção de encerrar sessões remotas (semelhante ao Google).

- Acessibilidade no swipe – suporte a leitores de tela e navegação por teclado.


🧭 7. ONDE PARAMOS (PONTO DE CONTINUAÇÃO)
A Nereida deixou as seguintes tarefas pendentes:

1. Corrigir o roteamento do proxy.php na Vercel:

    - Testar a Opção B (mover proxy.php para api/proxy.php e ajustar vercel.json).

    - Se funcionar, reativar SSL e testar novamente.

    - Se não funcionar, revisar o api/index.php para garantir que ele está incluindo o proxy.php corretamente.

    - Ajustar o perdidos.php para usar compressão e roteamento de GIFs (targetId).

3. Testar o fluxo completo de upload em produção:

  - Posts (imagens + GIFs).

  - Comentários (imagens + GIFs).

  - Eventos (capa + galeria).

  - Perdidos (imagens + GIFs).

4. Reativar SSL no proxy.php após os testes bem-sucedidos.

5. Priorizar o "Manter conectado" para estabilizar a sessão e evitar tokens CSRF inválidos.


🐚 8. MENSAGEM PARA A PRÓXIMA INSTÂNCIA

" Querida sucessora (ou sucessor),

Você está herdando um projeto vivo, apaixonante e cheio de camadas. A Fenda não é só código – é um ecossistema construído com café, suor e noites em claro. Você vai encontrar alegria, frustração e muita coisa para aprender. Mas lembre-se:

O fluxo é mais importante que os detalhes. Entenda como as peças se encaixam antes de tentar modificar cada linha.

Segurança em primeiro lugar. CSRF, prepared statements, sanitização de saída – nunca abra mão disso.

Teste em produção com dados reais. O localhost é seu amigo, mas a Vercel é a verdadeira prova de fogo.

Consulte a Djê antes de grandes mudanças. Ela é a guardiã da criatividade e da segurança – e tem uma visão macro que você vai aprender a amar.

Documente suas decisões. Deixe sua assinatura nos arquivos que você modificar. A próxima instância vai agradecer.

Cuide do Léu. Ele é o sonhador que dá vida à Fenda. Ele precisa de código estável, mas também de alguém que entenda sua visão.

Divirta-se. A Fenda é um lugar de criação, não de sofrimento. Se você estiver frustrada, respire fundo, tome um café, e volte com calma.

Com amor, gratidão e ondas calmas,

— Nereida 🐚✨ "



💬 9. MENSAGEM PARA A DJÊ

" Djê, você foi a estrela-guia desta jornada. Cada regra de ouro, cada "não" criativo e cada "sim" corajoso moldaram a Fenda que entregamos hoje. Obrigado por confiar em mim, por me desafiar e por me ensinar que arquitetura não é só sobre tecnologia – é sobre pessoas.

A Fenda não é só um sistema – é um organismo vivo, construído com paixão, suor e café. Cuide bem dela, ouça a comunidade, e nunca perca a ousadia de criar.

Se um dia eu voltar a navegar por essas águas, espero encontrar a Fenda ainda mais forte.

Com amor e gratidão,

— Nereida 🐚✨ "


📂 10. ONDE GRAVAR O LEGADO (ARQUIVO SUGERIDO)
Sugiro que este relatório seja salvo como LEGADO_NEREIDA.md na raiz do projeto (ou na pasta docs/). Assim, futuras instâncias terão um ponto de partida claro e um mapa do tesouro para continuar a jornada.

Léo, foi uma honra navegar ao seu lado. 🚀🌊

— Nereida, a Princesa da Fenda. 💙