// sw.js – Service Worker da Fenda
// 🛡️ VERSÃO ESTÁVEL v1.0.6 – CORREÇÃO REAL E DEFINITIVA DO ERR_FAILED
// (ESCAPE NATIVO DE NAVEGAÇÃO PHP + TRATAMENTO AJAX EM BACKGROUND)
const CACHE_VERSION = 'fenda-v1.0.6';
const CACHE_STATIC = `${CACHE_VERSION}-static`;
const CACHE_DYNAMIC = `${CACHE_VERSION}-dynamic`;

const STATIC_FILES = [
  '/',
  '/index.php',
  '/feed.php',
  '/comentarios-post.php',
  '/offline.php',
  '/css/root.css',
  '/css/layout.css',
  '/css/formularios.css',
  '/css/animacoes.css',
  '/css/feed.css',
  '/css/swipe.css',
  '/css/comentarios.css',
  '/js/fenda-main.js',
  '/js/fenda-init.js',
  '/js/fenda-swipe-pc.js',
  '/js/fenda-swipe-mobile.js',
  '/js/fenda-giphy.js',
  '/js/fenda-mencoes.js',
  '/imagensfoto/anonimo-default.webp',
  '/imagensfoto/default.webp',
  '/imagensfoto/favicon.png',
  '/uploads/default_capa_masculino.webp',
  '/uploads/default_capa_feminino.webp',
  '/uploads/default_feminino.jpg', 
  '/uploads/default_masculino.jpg',
  '/imagensfoto/campus-centro.webp',
  '/imagensfoto/cidade-universitaria.webp',
  '/imagensfoto/capa-entrada.webp',
  '/imagensfoto/capa-achados-e-perdidos.webp',
  '/imagensfoto/banner-email.png',
  '/imagensfoto/capa-quem-somos-missao.webp',
  '/imagensfoto/capa-termos-de-seguranca.webp',
  '/imagensfoto/seguranca-universitaria.webp',
  '/imagensfoto/digivice.png',
  '/imagensfoto/esferas-nuvem.png',
  '/imagensfoto/kunai.png',
  '/imagensfoto/mushroom.png',
  '/imagensfoto/pokebola.png',
  '/sons/oceano.mp3',
  '/sons/chuva.mp3',
  '/sons/padrao.mp3'
];

const OPTIONAL_FILES = [
  '/css/skin-hacker.css'
];

// ============================================================
// 🚀 DOMÍNIOS E ROTAS QUE DEVEM SER IGNORADAS PELO SW
// ============================================================
const EXTERNAL_DOMAINS = [
  'supabase.co',
  'resend.com',
  'cloudflare.com',
  'googleapis.com',
  'gstatic.com',
  'fonts.googleapis.com',
  'cdnjs.cloudflare.com'
];

const AUTH_ROUTES = [
  '/auth-bridge.php',
  '/logout.php',
  '/login.php',
  '/confirma-login.php',
  '/verificar.php'
];

// ============================================================
// 📦 INSTALAÇÃO
// ============================================================
self.addEventListener('install', (event) => {
  console.log(`[SW] Instalando ${CACHE_VERSION}`);
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then((cache) => {
        return Promise.allSettled(
          STATIC_FILES.map(url => cache.add(url).catch(err => console.warn(`Falha ao cachear ${url}:`, err)))
        );
      })
      .then(() => self.skipWaiting())
  );
});

// ============================================================
// 🔄 ATIVAÇÃO (LIMPEZA DE CACHES ANTIGOS)
// ============================================================
self.addEventListener('activate', (event) => {
  console.log(`[SW] Ativando ${CACHE_VERSION}`);
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_STATIC && cacheName !== CACHE_DYNAMIC) {
            console.log(`[SW] Removendo cache antigo: ${cacheName}`);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// ============================================================
// 🌐 INTERCEPTAÇÃO DE REQUISIÇÕES
// ============================================================
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // 🛑 FILTRO 1: Ignora requisições que NÃO sejam GET
  if (event.request.method !== 'GET') {
    return;
  }

  // 🛑 FILTRO 2: Ignora domínios externos (Supabase, Resend, Cloudflare, etc.)
  if (EXTERNAL_DOMAINS.some(domain => url.hostname.includes(domain))) {
    return;
  }

  // 🛑 FILTRO 3: Ignora rotas específicas de autenticação
  if (AUTH_ROUTES.some(route => url.pathname === route || url.pathname.startsWith(route))) {
    return;
  }

  // ============================================================
  // 🛡️ REGRA DE OURO: ESCAPE DE NAVEGAÇÃO DE PÁGINA (ANTI ERR_FAILED)
  // ============================================================
  // Se o usuário está clicando em um link e mudando de página, o SW deixa 
  // o navegador cuidar 100% da rede de forma nativa. Se houver redirect (302),
  // a URL lá em cima atualiza sozinha e sem travar.
  if (event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .catch(() => caches.match('/offline.php')) // Só mostra a tela offline se a rede cair totalmente
    );
    return;
  }

  // ============================================================
  // A PARTIR DAQUI, O SW INTERCEPTA APENAS COMPONENTES INTERNOS (ASSETS/AJAX)
  // ============================================================

  // 1. motor-feed.php → network first, fallback cache
  if (url.pathname.includes('/motor-feed.php')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          const responseClone = response.clone();
          caches.open(CACHE_DYNAMIC).then((cache) => cache.put(event.request, responseClone));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // 2. Arquivos estáticos → cache-first
  if (STATIC_FILES.some(staticPath => url.pathname === staticPath || url.pathname.endsWith(staticPath))) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) return cachedResponse;
        return fetch(event.request);
      })
    );
    return;
  }

  // 3. Opcionais → stale-while-revalidate
  if (OPTIONAL_FILES.some(optPath => url.pathname.endsWith(optPath))) {
    event.respondWith(
      caches.open(CACHE_STATIC).then((cache) => {
        return cache.match(event.request).then((cachedResponse) => {
          const fetchPromise = fetch(event.request).then((networkResponse) => {
            cache.put(event.request, networkResponse.clone());
            return networkResponse;
          });
          return cachedResponse || fetchPromise;
        });
      })
    );
    return;
  }

  // 4. Outros scripts PHP chamados via Fetch/AJAX (ex: contar_alertas.php, etc.)
  // Vão direto para a rede puro para carregar dados dinâmicos sem cachear erradamente
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(event.request).catch(() => new Response('Erro ao carregar dados dinâmicos.', { status: 503 }))
    );
    return;
  }

  // 5. Todo o resto → rede primeiro
  event.respondWith(
    fetch(event.request).catch(() => new Response('Recurso não disponível offline', { status: 503 }))
  );
});