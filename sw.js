// sw.js – Service Worker da Fenda
// 🛡️ VERSÃO ESTÁVEL – CORREÇÃO DEFINITIVA DO ERR_FAILED (FILTRO GET + REDIRECT HANDLING)
const CACHE_VERSION = 'fenda-v1.0.2'; // Incrementado para forçar atualização com a correção do redirect
const CACHE_STATIC = `${CACHE_VERSION}-static`;
const CACHE_DYNAMIC = `${CACHE_VERSION}-dynamic`;

// ==================== ARQUIVOS ESTÁTICOS (CACHE-FIRST) ====================
const STATIC_FILES = [
  '/',
  '/index.php',
  '/feed.php',
  '/comentarios-post.php',
  '/offline.php',

  // CSS
  '/css/root.css',
  '/css/layout.css',
  '/css/formularios.css',
  '/css/animacoes.css',
  '/css/feed.css',
  '/css/swipe.css',
  '/css/comentarios.css',

  // JS
  '/js/fenda-main.js',
  '/js/fenda-init.js',
  '/js/fenda-swipe-pc.js',
  '/js/fenda-swipe-mobile.js',
  '/js/fenda-giphy.js',
  '/js/fenda-mencoes.js',

  // Imagens estáticas (essenciais)
  '/imagensfoto/anonimo-default.webp',
  '/imagensfoto/default.webp',
  '/imagensfoto/favicon.png',
  '/uploads/default_capa_masculino.jpg',
  '/uploads/default_capa_feminino.jpg',
  '/uploads/default_feminino.jpg',
  '/uploads/default_masculino.webp',
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

  // Sons
  '/sons/oceano.mp3',
  '/sons/chuva.mp3',
  '/sons/padrao.mp3'
];

// ==================== ARQUIVOS OPCIONAIS ====================
const OPTIONAL_FILES = [
  '/css/skin-hacker.css'
];

// ==================== INSTALAÇÃO ====================
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

// ==================== ATIVAÇÃO ====================
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

// ==================== INTERCEPTAÇÃO ====================
self.addEventListener('fetch', (event) => {
  // 🛑 FILTRO ANTI ERR_FAILED: Ignora requisições que NÃO sejam GET
  if (event.request.method !== 'GET') {
    return; // Deixa POST, PUT, DELETE passarem direto para a rede
  }

  const url = new URL(event.request.url);

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

  // 4. Páginas principais (.php, navegação) → network first, fallback offline
  // 🔥 CORREÇÃO DO ERR_FAILED EM REDIRECIONAMENTOS
  if (event.request.mode === 'navigate' || url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Se a resposta for um redirecionamento travado (redirect: 'manual'),
          // criamos uma nova requisição com redirect: 'follow' para o navegador
          // seguir o fluxo naturalmente.
          if (response.type === 'opaqueredirect' || response.redirected) {
            console.log('[SW] Redirecionamento detectado em página PHP. Forçando modo follow...');
            return fetch(new Request(event.request.url, {
              method: event.request.method,
              headers: event.request.headers,
              credentials: 'include', // Mantém a sessão
              redirect: 'follow'      // Força o navegador a seguir o redirecionamento
            }));
          }
          return response;
        })
        .catch(() => caches.match('/offline.php'))
    );
    return;
  }

  // 5. Todo o resto → rede primeiro
  event.respondWith(
    fetch(event.request).catch(() => new Response('Recurso não disponível offline', { status: 503 }))
  );
});