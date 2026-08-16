// sw.js – Service Worker da Fenda
// 🛡️ VERSÃO v1.2.4 – Cache busting para proxy.php
const CACHE_VERSION = 'fenda-v1.2.4';
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
  '/uploads/ui/img_avatar1.webp',
  '/uploads/ui/img_avatar2.webp',
  '/uploads/ui/fallback-post.webp',
  '/uploads/ui/fallback-avatar.webp',
  '/uploads/ui/anonimo-default.webp',
  '/uploads/ui/default.webp',
  '/uploads/ui/favicon.png',
  '/uploads/ui/default_comunidade.webp',
  '/uploads/ui/default_evento.webp',
  '/uploads/ui/default_capa_masculino.webp',
  '/uploads/ui/default_capa_feminino.webp',
  '/uploads/ui/default_feminino.jpg', 
  '/uploads/ui/default_masculino.jpg',
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
  '/sons/oceano.opus',
  '/sons/chuva.opus',
  '/sons/padrao.mp3'
];

const OPTIONAL_FILES = [
  '/css/skin-hacker.css'
];

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
// INSTALAÇÃO
// ============================================================
self.addEventListener('install', (event) => {
  console.log('[SW] 🔵 Instalando versão', CACHE_VERSION);
  event.waitUntil(
    caches.open(CACHE_STATIC)
      .then((cache) => {
        console.log('[SW] 📦 Cache estático aberto. Adicionando arquivos...');
        return Promise.allSettled(
          STATIC_FILES.map(url => 
            cache.add(url).catch(err => console.warn(`[SW] ⚠️ Falha ao cachear ${url}:`, err))
          )
        );
      })
      .then(() => {
        console.log('[SW] ✅ Instalação concluída. Forçando ativação...');
        return self.skipWaiting();
      })
  );
});

// ============================================================
// ATIVAÇÃO
// ============================================================
self.addEventListener('activate', (event) => {
  console.log('[SW] 🟢 Ativando versão', CACHE_VERSION);
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      console.log('[SW] 🗑️ Caches encontrados:', cacheNames);
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_STATIC && cacheName !== CACHE_DYNAMIC) {
            console.log(`[SW] 🧹 Removendo cache antigo: ${cacheName}`);
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      console.log('[SW] ✅ Ativação concluída. Assumindo controle dos clientes...');
      return self.clients.claim();
    })
  );
});

// ============================================================
// INTERCEPTAÇÃO DE REQUISIÇÕES (FETCH)
// ============================================================
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  if (event.request.method !== 'GET') return;

  if (EXTERNAL_DOMAINS.some(domain => url.hostname.includes(domain))) {
    return;
  }

  if (AUTH_ROUTES.some(route => url.pathname === route || url.pathname.startsWith(route))) {
    return;
  }

  if (event.request.mode === 'navigate') {
    console.log('[SW] 📄 Navegação para:', url.pathname);
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          console.warn('[SW] ⚠️ Navegação offline, servindo offline.php');
          return caches.match('/offline.php');
        })
    );
    return;
  }

  // ============================================================
  // 🔥 REGRA 1: Cache-first para proxy.php (com SWR e limite)
  // 🔥 CORREÇÃO: Usa a URL COMPLETA (com parâmetro v) como chave de cache
  // ============================================================
  if (url.pathname.includes('/proxy.php')) {
    console.log('[SW] 🖼️ Interceptando proxy.php:', url.pathname + url.search);
    event.respondWith(
      caches.open(CACHE_DYNAMIC).then((cache) => {
        // 🔥 Usa a URL completa (com parâmetros) como chave de cache
        const cacheKey = event.request.url;
        return cache.match(cacheKey).then((cachedResponse) => {
          if (cachedResponse) {
            console.log('[SW] ✅ Servindo imagem do CACHE (com busting):', url.pathname);
          } else {
            console.log('[SW] 🌍 Buscando imagem da REDE (primeira vez):', url.pathname);
          }

          const fetchPromise = fetch(event.request).then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              console.log('[SW] 💾 Armazenando imagem no cache (com busting):', url.pathname);
              // 🔥 Armazena usando a URL completa como chave
              cache.put(cacheKey, networkResponse.clone());
              // Limpeza LRU: remove os mais antigos se exceder 100
              cache.keys().then(keys => {
                if (keys.length > 100) {
                  console.log(`[SW] 🧹 Cache excedeu 100 itens (${keys.length}). Removendo os mais antigos...`);
                  const toDelete = keys.slice(0, keys.length - 80);
                  toDelete.forEach(key => cache.delete(key));
                }
              });
            } else {
              console.warn('[SW] ⚠️ Resposta da rede não foi OK (status:', networkResponse?.status, ') para:', url.pathname);
            }
            return networkResponse;
          });

          return cachedResponse || fetchPromise;
        });
      })
    );
    return;
  }

  // ============================================================
  // REGRA 2: motor-feed.php → network first, fallback cache
  // ============================================================
  if (url.pathname.includes('/motor-feed.php')) {
    console.log('[SW] 📡 Interceptando motor-feed.php:', url.pathname);
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          const responseClone = response.clone();
          caches.open(CACHE_DYNAMIC).then((cache) => {
            console.log('[SW] 💾 Cacheando motor-feed.php');
            cache.put(event.request, responseClone);
          });
          return response;
        })
        .catch(() => {
          console.warn('[SW] ⚠️ motor-feed.php offline, servindo do cache (se houver)');
          return caches.match(event.request);
        })
    );
    return;
  }

  // ============================================================
  // REGRA 3: Arquivos estáticos → cache-first
  // ============================================================
  if (STATIC_FILES.some(staticPath => url.pathname === staticPath || url.pathname.endsWith(staticPath))) {
    event.respondWith(
      caches.match(event.request).then((cachedResponse) => {
        if (cachedResponse) {
          return cachedResponse;
        }
        console.log('[SW] 🌍 Buscando estático da REDE:', url.pathname);
        return fetch(event.request);
      })
    );
    return;
  }

  // ============================================================
  // REGRA 4: Opcionais → stale-while-revalidate
  // ============================================================
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

  // ============================================================
  // REGRA 5: Outros PHP → rede pura
  // ============================================================
  if (url.pathname.endsWith('.php')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        console.warn('[SW] ⚠️ Falha ao carregar PHP:', url.pathname);
        return new Response('Erro ao carregar dados dinâmicos.', { status: 503 });
      })
    );
    return;
  }

  // ============================================================
  // REGRA 6: Todo o resto → rede primeiro
  // ============================================================
  event.respondWith(
    fetch(event.request).catch(() => {
      console.warn('[SW] ⚠️ Recurso não disponível offline:', url.pathname);
      return new Response('Recurso não disponível offline', { status: 503 });
    })
  );
});