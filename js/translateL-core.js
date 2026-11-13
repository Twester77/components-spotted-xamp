/* ============================================================================
 * translateL-core.js — Protocolo translateL (Swipe Acessível)
 * ============================================================================
 *
 * Versão:    0.1.1 (Fase 2.1 - Homologação Acessibilidade)
 * Branch:    feature/translateL
 * Base:      A Fenda v1.7.2 (main) — motores fenda-swipe-mobile.js,
 *            fenda-swipe-pc.js e bt-swipe.js
 * Autoria:   Instância Concha
 *
 * ── Estrutura do arquivo ──────────────────────────────────────────────────
 *   §1  Constantes e estados        → docs/api.md, docs/arquitetura.md §2
 *   §2  Detecção de recursos        → docs/compatibilidade.md §2
 *   §3  Utilitários                 → (helpers puros)
 *   §4  Gerenciamento de contexto   → docs/arquitetura.md §5 (ciclo de vida)
 *   §5  CSS do fallback acessível   → docs/acessibilidade.md §2-§3 (Opção A)
 *   §6  Injeção do fallback HTML    → docs/acessibilidade.md §4
 *   §7  Web Speech API (opt-in)     → docs/acessibilidade.md §6
 *   §8  Handlers de ponteiro        → docs/arquitetura.md §5, stateDiagram
 *   §9  Transições de estado        → docs/diagramas/stateDiagram-swipe.md
 *   §10 Transformações CSS          → docs/arquitetura.md §3 (camadas)
 *   §11 API pública                 → docs/api.md
 *   §12 Presets                     → docs/api.md (presets.feed, .balanga)
 * ============================================================================ */

(function (global) {
    'use strict';

    /* ═══════════════════════════════════════════════════════════════════
     * §1 — CONSTANTES E ESTADOS
     * ═══════════════════════════════════════════════════════════════════ */

    var VERSION = '0.1.1';

    var STATE = {
        IDLE: 'IDLE',
        PRESSING: 'PRESSING',
        DRAGGING: 'DRAGGING',
        LONG_PRESS: 'LONG_PRESS',
        MENU: 'MENU',
        DECIDE: 'DECIDE',
        EXITING: 'EXITING',
        SPRING_BACK: 'SPRING_BACK',
        REMOVED: 'REMOVED'
    };

    var DEFAULTS = {
        threshold: 120,
        moveStartThreshold: 10,
        rotationFactor: 25,
        scaleOnDrag: 1.02,
        scaleOnExit: 0.95,
        exitAnimationDuration: 300,
        springDuration: 400,
        longPressDelay: 400,
        upSwipeTolerance: 80,

        modeClass: null,
        bodyDraggingClass: 'translate-l-arrastando',
        dragClass: 'translate-l-dragging',
        transitionClass: 'translate-l-transition',
        longPressClass: 'translate-l-long-press',

        feedbackSelectors: {
            right: null,
            left: null,
            up: null
        },
        feedbackPosition: 'viewport',
        feedbackMaxDistance: 150,

        injectCoreCSS: 'auto',

        hooks: {
            onSwipeRight: null,
            onSwipeLeft: null,
            onSwipeUp: null,
            onLongPress: null,
            onBeforeLongPress: null,
            onVoiceCommand: null,
            onDragStart: null,
            onDragEnd: null,
            onCardRemoved: null,
            onCardsLoaded: null,
            onPauseMedia: null,
            onResumeMedia: null
        },

        accessibility: {
            injectFallback: true,
            fallbackStrategy: 'sr-only-reveal',
            labels: {
                right: 'Curtir',
                left: 'Descartar',
                up: 'Comentar',
                menu: 'Abrir menu de ações',
                voice: 'Ativar comandos de voz'
            },
            respectReducedMotion: true
        },

        voice: {
            enabled: false,
            lang: 'pt-BR',
            commands: {
                right: ['amei', 'curtir', 'like', 'gostei'],
                left: ['descartar', 'passar', 'próximo', 'nao', 'não'],
                up: ['comentar', 'fofocar', 'abrir comentários']
            }
        }
    };


    /* ═══════════════════════════════════════════════════════════════════
     * §2 — DETECÇÃO DE RECURSOS
     * ═══════════════════════════════════════════════════════════════════ */

    var HAS = (function () {
        var probe = document.createElement('div');
        var supportFn = (typeof CSS !== 'undefined' && CSS.supports) ? CSS.supports : function () { return false; };
        return {
            pointerEvent: 'PointerEvent' in window,
            pointerCapture: 'setPointerCapture' in probe,
            customEvent: typeof window.CustomEvent === 'function',
            speechRecognition: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
            speechSynthesis: 'speechSynthesis' in window,
            matchMedia: typeof window.matchMedia === 'function',
            requestAnimationFrame: typeof window.requestAnimationFrame === 'function',
            focusWithin: supportFn.call(CSS, 'selector(:focus-within)'),
            focusVisible: supportFn.call(CSS, 'selector(:focus-visible)')
        };
    })();


    /* ═══════════════════════════════════════════════════════════════════
     * §3 — UTILITÁRIOS
     * ═══════════════════════════════════════════════════════════════════ */

    function noop() {}

    function now() {
        if (typeof performance !== 'undefined' && typeof performance.now === 'function') {
            return performance.now();
        }
        return Date.now();
    }

    function rAF(fn) {
        if (HAS.requestAnimationFrame) return window.requestAnimationFrame(fn);
        return setTimeout(fn, 16);
    }

    function cAF(id) {
        if (typeof window.cancelAnimationFrame === 'function') return window.cancelAnimationFrame(id);
        return clearTimeout(id);
    }

    function closest(el, selector) {
        if (!el || el.nodeType !== 1) return null;
        if (el.closest) return el.closest(selector);
        var matches = el.matches || el.msMatchesSelector || el.webkitMatchesSelector;
        while (el && el !== document) {
            if (matches && matches.call(el, selector)) return el;
            el = el.parentElement;
        }
        return null;
    }

    function prefersReducedMotion() {
        if (!HAS.matchMedia) return false;
        try {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {
            return false;
        }
    }

    function safeCall(fn, a, b, c, d) {
        if (typeof fn !== 'function') return;
        try { fn(a, b, c, d); }
        catch (err) { console.error('[translateL] hook error:', err); }
    }

    function deepMerge(target, source) {
        if (!source) return target;
        var out = {};
        Object.keys(target || {}).forEach(function (k) { out[k] = target[k]; });
        Object.keys(source || {}).forEach(function (k) {
            var t = target ? target[k] : undefined;
            var s = source[k];
            if (s && typeof s === 'object' && !Array.isArray(s) &&
                t && typeof t === 'object' && !Array.isArray(t)) {
                out[k] = deepMerge(t, s);
            } else {
                out[k] = s;
            }
        });
        return out;
    }

    function mergeOptions(userOptions) {
        return deepMerge(DEFAULTS, userOptions || {});
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §4 — GERENCIAMENTO DE CONTEXTO
     * ═══════════════════════════════════════════════════════════════════ */

    var contexts = new WeakMap();        // card → ctx
    var managedCards = new WeakSet();    // cards inicializados
    var containerListeners = new WeakSet(); // containers com listener ativo
    var activeByPointer = new Map();     // pointerId → ctx (drag em curso)

    function getContext(card) {
        return contexts.get(card) || null;
    }

    function getTopManagedCard(container) {
        if (!container) return null;
        var children = container.children;
        for (var i = 0; i < children.length; i++) {
            if (managedCards.has(children[i])) return children[i];
        }
        return null;
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §5 — CSS DO FALLBACK ACESSÍVEL
     * ═══════════════════════════════════════════════════════════════════ */

    function ensureFallbackCSS() {
        if (document.getElementById('translate-l-fallback-css')) return;
        var style = document.createElement('style');
        style.id = 'translate-l-fallback-css';
        style.textContent =
            '.translate-l-fallback{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;}' +
            '.translate-l-fallback:focus,' +
            '.translate-l-fallback:focus-within,' +
            '.translate-l-fallback:focus-visible{' +
            'position:static;width:auto;height:auto;padding:8px 16px;margin:0;overflow:visible;clip:auto;white-space:normal;' +
            'background:rgba(10,10,10,0.95);border:1px solid #ffbc00;border-radius:12px;z-index:10001;' +
            'display:flex;gap:6px;flex-wrap:wrap;' +
            '}' +
            '.translate-l-fallback button{' +
            'background:transparent;border:1px solid rgba(255,188,0,0.4);border-radius:20px;' +
            'padding:6px 12px;color:#fff;cursor:pointer;font-family:inherit;font-size:0.9rem;' +
            '}' +
            '.translate-l-fallback button:hover{background:rgba(255,188,0,0.15);}' +
            '.translate-l-fallback button:focus-visible{outline:2px solid #ffbc00;outline-offset:2px;}' +
            '.translate-l-live{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);}';
        document.head.appendChild(style);
    }

    /* ═══════════════════════════════════════════════════════════════════
     * §5.1 — CSS FUNCIONAL DO MOTOR
     * ═══════════════════════════════════════════════════════════════════ */

    function ensureCoreEngineCSS() {
        if (document.getElementById('translate-l-core-css')) return;
        var style = document.createElement('style');
        style.id = 'translate-l-core-css';
        style.textContent =
            '.translate-l-card{' +
                'touch-action:none;-ms-touch-action:none;' +
                'user-select:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;' +
                '-webkit-touch-callout:none;-webkit-user-drag:none;' +
                'cursor:grab;cursor:-webkit-grab;' +
                'will-change:transform,opacity;' +
                '-webkit-backface-visibility:hidden;backface-visibility:hidden;' +
            '}' +
            '.translate-l-card[data-translate-l-scrollable="true"]{touch-action:pan-y;}' +
            '.translate-l-card.translate-l-dragging{' +
                'cursor:grabbing;cursor:-webkit-grabbing;' +
                'transition:none!important;' +
            '}' +
            '.translate-l-card.translate-l-transition{' +
                'transition:transform .4s cubic-bezier(.175,.885,.32,1.275)!important;' +
            '}' +
            '.translate-l-card.translate-l-long-press{' +
                'box-shadow:0 4px 10px rgba(255,140,0,.4);' +
                'background-image:linear-gradient(120deg,rgba(255,255,255,0) 40%,rgba(255,255,255,.5) 50%,rgba(255,255,255,0) 60%);' +
                'background-size:200% 100%;' +
                'animation:translateLShimmer .6s infinite ease-in-out;' +
            '}' +
            '@keyframes translateLShimmer{from{background-position:200% 0}to{background-position:-200% 0}}' +
            '@media (prefers-reduced-motion: reduce){' +
                '.translate-l-card.translate-l-transition{transition-duration:.01ms!important;}' +
                '.translate-l-card.translate-l-long-press{animation:none!important;}' +
            '}';
        document.head.appendChild(style);
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §6 — INJEÇÃO DO FALLBACK ACESSÍVEL (HTML) + FOCUS TRAP
     * ═══════════════════════════════════════════════════════════════════ */

    function injectFallback(ctx) {
        if (!ctx.options.accessibility.injectFallback) return;
        if (ctx.fallbackEl) return;

        ensureFallbackCSS();

        var card = ctx.card;
        var labels = ctx.options.accessibility.labels;

        var fallback = document.createElement('div');
        fallback.className = 'translate-l-fallback';
        fallback.setAttribute('role', 'group');
        fallback.setAttribute('aria-label', 'Ações do card');

        var acoes = [
            { action: 'right', label: labels.right, text: '💖 ' + labels.right },
            { action: 'left', label: labels.left, text: '🗑️ ' + labels.left },
            { action: 'up', label: labels.up, text: '💬 ' + labels.up },
            { action: 'menu', label: labels.menu, text: '⋮ Menu' }
        ];

        if (HAS.speechRecognition && ctx.options.voice.enabled) {
            acoes.push({
                action: 'voice',
                label: labels.voice,
                text: '🎤 Voz',
                pressed: 'false'
            });
        }

        acoes.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-translate-l-action', item.action);
            btn.setAttribute('aria-label', item.label);
            btn.textContent = item.text;
            if (item.action === 'voice') {
                btn.setAttribute('aria-pressed', item.pressed);
            }
            fallback.appendChild(btn);
        });

        fallback.addEventListener('click', function (e) {
            var btn = closest(e.target, 'button[data-translate-l-action]');
            if (!btn) return;
            var action = btn.getAttribute('data-translate-l-action');

            if (action === 'voice') {
                toggleVoice(ctx, btn);
                emitAction(ctx, 'voice', { listening: ctx.voiceActive });
                return;
            }
            emitAction(ctx, action);
        });

        var body = card.querySelector('.card-body');
        if (body) body.appendChild(fallback);
        else card.appendChild(fallback);

        ctx.fallbackEl = fallback;
    }

    /**
     * NOVO (Fase 2.1): Sincroniza a ordem de foco.
     * Apenas o card do topo da pilha deve ter seus botões focáveis.
     * Cards de trás recebem [inert] e [tabindex="-1"] para não vazar no Tab.
     */
    function syncTopCardFocus(container) {
        if (!container) return;
        var topCard = getTopManagedCard(container);
        var children = container.children;

        for (var i = 0; i < children.length; i++) {
            var card = children[i];
            if (!managedCards.has(card)) continue;

            var ctx = contexts.get(card);
            if (!ctx || !ctx.fallbackEl) continue;

            var btns = ctx.fallbackEl.querySelectorAll('button');

            if (card === topCard) {
                ctx.fallbackEl.removeAttribute('inert');
                ctx.fallbackEl.setAttribute('aria-hidden', 'false');
                for (var j = 0; j < btns.length; j++) {
                    btns[j].removeAttribute('tabindex');
                }
            } else {
                ctx.fallbackEl.setAttribute('inert', '');
                ctx.fallbackEl.setAttribute('aria-hidden', 'true');
                for (var k = 0; k < btns.length; k++) {
                    btns[k].setAttribute('tabindex', '-1');
                }
            }
        }
    }

    function emitAction(ctx, action, extra) {
        var detail = {
            action: action,
            card: ctx.card,
            id: ctx.id,
            translateL: true
        };
        if (extra) {
            Object.keys(extra).forEach(function (k) { detail[k] = extra[k]; });
        }
        var evt;
        if (HAS.customEvent) {
            try {
                evt = new CustomEvent('translate-l-action', {
                    detail: detail,
                    bubbles: true,
                    cancelable: true
                });
            } catch (e) {
                evt = document.createEvent('CustomEvent');
                evt.initCustomEvent('translate-l-action', true, true, detail);
            }
        } else {
            evt = document.createEvent('Event');
            evt.initEvent('translate-l-action', true, true);
            evt.detail = detail;
        }
        ctx.card.dispatchEvent(evt);
    }

    function announce(ctx, message) {
        var card = ctx.card;
        var el = card.querySelector('.translate-l-live');
        if (!el) {
            el = document.createElement('div');
            el.className = 'translate-l-live';
            el.setAttribute('aria-live', 'polite');
            el.setAttribute('aria-atomic', 'true');
            card.appendChild(el);
        }
        el.textContent = '';
        setTimeout(function () { el.textContent = message; }, 30);
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §7 — WEB SPEECH API (opt-in)
     * ═══════════════════════════════════════════════════════════════════ */

    function setupVoice(ctx) {
        if (!HAS.speechRecognition) {
            console.info('[translateL] SpeechRecognition indisponível. Voz desabilitada.');
            return;
        }
        var Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
        var rec = new Rec();
        rec.lang = ctx.options.voice.lang;
        rec.continuous = false;
        rec.interimResults = false;
        rec.maxAlternatives = 1;

        rec.onresult = function (event) {
            var transcript = event.results[0][0].transcript.toLowerCase().trim();
            var action = parseVoiceCommand(transcript, ctx.options.voice.commands);
            if (action) {
                announce(ctx, 'Comando reconhecido: ' + transcript);
                safeCall(ctx.options.hooks.onVoiceCommand, action, ctx.card, ctx.id);
                emitAction(ctx, action, { via: 'voice', transcript: transcript });
            } else {
                announce(ctx, 'Comando não reconhecido. Tente novamente.');
            }
        };

        rec.onerror = function (event) {
            console.warn('[translateL] Voz erro:', event.error);
            ctx.voiceActive = false;
            updateVoiceButton(ctx);
        };

        rec.onend = function () {
            ctx.voiceActive = false;
            updateVoiceButton(ctx);
        };

        ctx.recognition = rec;
    }

    function toggleVoice(ctx, btnRef) {
        if (!ctx.recognition) {
            announce(ctx, 'Comando de voz indisponível neste navegador.');
            return;
        }
        if (ctx.voiceActive) {
            try { ctx.recognition.stop(); } catch (e) { /* noop */ }
            ctx.voiceActive = false;
        } else {
            try {
                ctx.recognition.start();
                ctx.voiceActive = true;
                announce(ctx, 'Ouvindo...');
            } catch (e) {
                console.warn('[translateL] Falha ao iniciar voz:', e);
                ctx.voiceActive = false;
            }
        }
        updateVoiceButton(ctx, btnRef);
    }

    function updateVoiceButton(ctx, btnRef) {
        var btn = btnRef || (ctx.fallbackEl ? ctx.fallbackEl.querySelector('[data-translate-l-action="voice"]') : null);
        if (!btn) return;
        btn.setAttribute('aria-pressed', ctx.voiceActive ? 'true' : 'false');
        btn.textContent = ctx.voiceActive ? '⏹️ Parar' : '🎤 Voz';
    }

    function parseVoiceCommand(transcript, commands) {
        var lower = transcript.toLowerCase();
        var dirs = ['right', 'left', 'up'];
        for (var i = 0; i < dirs.length; i++) {
            var list = commands[dirs[i]] || [];
            for (var j = 0; j < list.length; j++) {
                if (lower.indexOf(list[j]) !== -1) return dirs[i];
            }
        }
        return null;
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §8 — HANDLERS DE PONTEIRO
     * ═══════════════════════════════════════════════════════════════════ */

    function onContainerPointerDown(e) {
        var card = null;
        var node = e.target;
        while (node && node !== e.currentTarget) {
            if (managedCards.has(node)) { card = node; break; }
            node = node.parentElement;
        }
        if (!card) return;

        var ctx = getContext(card);
        if (!ctx) return;

        if (!e.isPrimary) return;

        if (ctx.options.modeClass) {
            if (!document.body.classList.contains(ctx.options.modeClass)) return;
        } else {
            var hasAnyMode =
                document.body.classList.contains('modo-swipe-ativo') ||
                document.body.classList.contains('modo-tinder-ativo');
            if (!hasAnyMode) return;
        }

        var topCard = getTopManagedCard(card.parentElement);
        if (card !== topCard) return;

        if (closest(e.target, 'button, a, input, textarea, select, .reacao-wrapper, .reacoes-popup, [data-translate-l-fallback], .translate-l-fallback')) {
            return;
        }

        if (ctx.state !== STATE.IDLE) return;
        if (activeByPointer.has(e.pointerId)) return;

        e.preventDefault();
        e.stopPropagation();

        ctx.state = STATE.PRESSING;
        ctx.pointerId = e.pointerId;
        ctx.startX = e.clientX;
        ctx.startY = e.clientY;
        ctx.currentX = 0;
        ctx.currentY = 0;
        ctx.moveDetected = false;

        activeByPointer.set(e.pointerId, ctx);

        if (HAS.pointerCapture && card.setPointerCapture) {
            try { card.setPointerCapture(e.pointerId); } catch (err) { /* noop */ }
        }

        safeCall(ctx.options.hooks.onBeforeLongPress, card, ctx.id);

        ctx.longPressTimer = setTimeout(function () {
            if (ctx.state !== STATE.PRESSING || ctx.moveDetected) return;
            enterLongPress(ctx);
        }, ctx.options.longPressDelay);
    }

    function onWindowPointerMove(e) {
        var ctx = activeByPointer.get(e.pointerId);
        if (!ctx) return;
        if (ctx.state !== STATE.PRESSING && ctx.state !== STATE.DRAGGING) return;

        var dx = e.clientX - ctx.startX;
        var dy = e.clientY - ctx.startY;

        if (ctx.state === STATE.PRESSING) {
            var moveThresh = ctx.options.moveStartThreshold;
            if (Math.abs(dx) < moveThresh && Math.abs(dy) < moveThresh) return;
            if (ctx.longPressTimer) {
                clearTimeout(ctx.longPressTimer);
                ctx.longPressTimer = null;
            }
            ctx.moveDetected = true;
            enterDragging(ctx);
        }

        if (ctx.state === STATE.DRAGGING) {
            ctx.currentX = Math.round(dx);
            ctx.currentY = Math.round(dy);
            e.preventDefault();

            if (ctx.rafId) cAF(ctx.rafId);
            ctx.rafId = rAF(function () {
                ctx.rafId = null;
                if (ctx.state !== STATE.DRAGGING) return;
                applyTransform(ctx);
            });
        }
    }

    function onWindowPointerUp(e) {
        var ctx = activeByPointer.get(e.pointerId);
        if (!ctx) return;
        activeByPointer.delete(e.pointerId);

        if (ctx.longPressTimer) {
            clearTimeout(ctx.longPressTimer);
            ctx.longPressTimer = null;
        }

        if (ctx.state === STATE.PRESSING) {
            enterIdle(ctx);
            return;
        }

        if (ctx.state === STATE.DRAGGING) {
            enterDecide(ctx);
        }
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §9 — TRANSIÇÕES DE ESTADO
     * ═══════════════════════════════════════════════════════════════════ */

    function enterDragging(ctx) {
        ctx.state = STATE.DRAGGING;
        var card = ctx.card;
        var opts = ctx.options;

        card.style.transition = 'none';
        card.classList.remove(opts.transitionClass);
        card.classList.add(opts.dragClass);

        if (opts.bodyDraggingClass) {
            document.body.classList.add(opts.bodyDraggingClass);
        }

        safeCall(opts.hooks.onPauseMedia, card);
        safeCall(opts.hooks.onDragStart, card);
    }

    function enterDecide(ctx) {
        ctx.state = STATE.DECIDE;
        var dx = ctx.currentX;
        var dy = ctx.currentY;
        var opts = ctx.options;

        if (dx > opts.threshold) {
            enterExiting(ctx, 'right', 800, dy);
        } else if (dx < -opts.threshold) {
            enterExiting(ctx, 'left', -800, dy);
        } else if (dy < -opts.threshold && Math.abs(dx) < opts.upSwipeTolerance) {
            enterUpAction(ctx);
        } else {
            enterSpringBack(ctx);
        }
    }

    function enterUpAction(ctx) {
        var card = ctx.card;
        var opts = ctx.options;
        var reduceMotion = opts.accessibility.respectReducedMotion && prefersReducedMotion();
        var duration = reduceMotion ? 0 : opts.exitAnimationDuration;

        card.classList.remove(opts.dragClass);
        if (opts.bodyDraggingClass) {
            document.body.classList.remove(opts.bodyDraggingClass);
        }

        var targetY = ctx.currentY - 40;
        card.style.transition = 'transform ' + duration + 'ms ease-out, opacity ' + duration + 'ms';
        card.style.transform = 'translate(calc(-50% + ' + ctx.currentX + 'px), calc(-50% + ' + targetY + 'px)) rotate(0deg) scale(' + opts.scaleOnDrag + ')';

        safeCall(opts.hooks.onDragEnd, card, 'up');
        safeCall(opts.hooks.onSwipeUp, card, ctx.id);

        ctx.state = STATE.SPRING_BACK;
        setTimeout(function () {
            if (ctx.state === STATE.SPRING_BACK) {
                resetTransform(ctx);
                safeCall(opts.hooks.onResumeMedia, card);
                setTimeout(function () {
                    if (ctx.state === STATE.SPRING_BACK) {
                        ctx.state = STATE.IDLE;
                        ctx.pointerId = null;
                        card.style.transition = '';
                    }
                }, opts.springDuration + 50);
            }
        }, duration + 20);
    }

    function enterExiting(ctx, direction, exitX, exitY) {
        ctx.state = STATE.EXITING;
        var card = ctx.card;
        var opts = ctx.options;
        var reduceMotion = opts.accessibility.respectReducedMotion && prefersReducedMotion();
        var duration = reduceMotion ? 0 : opts.exitAnimationDuration;

        card.style.pointerEvents = 'none';

        card.classList.remove(opts.dragClass);
        if (opts.bodyDraggingClass) {
            document.body.classList.remove(opts.bodyDraggingClass);
        }

        var rotate = (exitX / opts.rotationFactor).toFixed(1);
        card.style.transition = 'transform ' + duration + 'ms ease-out, opacity ' + duration + 'ms';
        card.style.transform =
            'translate(calc(-50% + ' + exitX + 'px), calc(-50% + ' + exitY + 'px)) ' +
            'rotate(' + rotate + 'deg) scale(' + opts.scaleOnExit + ')';
        card.style.opacity = '0';

        safeCall(opts.hooks.onDragEnd, card, direction);
        if (direction === 'right') {
            safeCall(opts.hooks.onSwipeRight, card, ctx.id);
        } else if (direction === 'left') {
            safeCall(opts.hooks.onSwipeLeft, card, ctx.id);
        }

        setTimeout(function () {
            if (ctx.state === STATE.EXITING) {
                enterRemoved(ctx, direction);
            }
        }, duration + 20);
    }

    function enterRemoved(ctx, direction) {
        ctx.state = STATE.REMOVED;
        var card = ctx.card;
        var opts = ctx.options;

        safeCall(opts.hooks.onResumeMedia, card);

        // Guarda o container para atualizar o foco após remover
        var container = card.parentElement;

        if (card && card.parentNode) {
            card.parentNode.removeChild(card);
        }

        // NOVO: Atualiza o foco para o próximo card da pilha
        if (container) {
            syncTopCardFocus(container);
        }

        safeCall(opts.hooks.onCardRemoved, card, ctx.id, direction);

        activeByPointer.delete(ctx.pointerId);
        contexts.delete(card);
        managedCards.delete(card);
    }

    function enterSpringBack(ctx) {
        ctx.state = STATE.SPRING_BACK;
        var card = ctx.card;
        var opts = ctx.options;

        card.classList.remove(opts.dragClass);
        if (opts.bodyDraggingClass) {
            document.body.classList.remove(opts.bodyDraggingClass);
        }

        resetTransform(ctx);

        safeCall(opts.hooks.onResumeMedia, card);
        safeCall(opts.hooks.onDragEnd, card, 'cancelled');

        setTimeout(function () {
            if (ctx.state === STATE.SPRING_BACK) {
                ctx.state = STATE.IDLE;
                ctx.pointerId = null;
                ctx.currentX = 0;
                ctx.currentY = 0;
                card.style.transition = '';
            }
        }, opts.springDuration + 50);
    }

    function enterLongPress(ctx) {
        ctx.state = STATE.LONG_PRESS;
        var card = ctx.card;
        var opts = ctx.options;
        var isOwner = card.classList.contains('post-admin-gold') ||
                      card.getAttribute('data-owner') === 'true';

        card.classList.add(opts.longPressClass);
        safeCall(opts.hooks.onLongPress, card, ctx.id, isOwner);

        setTimeout(function () {
            if (ctx.state === STATE.LONG_PRESS) {
                card.classList.remove(opts.longPressClass);
                ctx.state = STATE.IDLE;
                ctx.pointerId = null;
            }
        }, 5000);
    }

    function enterIdle(ctx) {
        ctx.state = STATE.IDLE;
        ctx.pointerId = null;
        ctx.currentX = 0;
        ctx.currentY = 0;
        ctx.moveDetected = false;

        var opts = ctx.options;
        ctx.card.classList.remove(opts.longPressClass);
        ctx.card.classList.remove(opts.dragClass);
        if (opts.bodyDraggingClass) {
            document.body.classList.remove(opts.bodyDraggingClass);
        }
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §10 — TRANSFORMAÇÕES CSS
     * ═══════════════════════════════════════════════════════════════════ */

    function applyTransform(ctx) {
        var card = ctx.card;
        var opts = ctx.options;
        var dx = ctx.currentX;
        var dy = ctx.currentY;
        var rotate = Number((dx / opts.rotationFactor).toFixed(1));
        var reduceMotion = opts.accessibility.respectReducedMotion && prefersReducedMotion();
        var scale = reduceMotion ? 1 : opts.scaleOnDrag;

        card.style.transform =
            'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px)) ' +
            'rotate(' + rotate + 'deg) scale(' + scale + ')';

        card.style.setProperty('--pos-x', dx + 'px');
        card.style.setProperty('--pos-y', dy + 'px');
        card.style.setProperty('--swipe-rot', rotate + 'deg');

        updateFeedback(ctx, dx, dy);
    }

    function resetTransform(ctx) {
        var card = ctx.card;
        card.style.setProperty('--pos-x', '0px');
        card.style.setProperty('--pos-y', '0px');
        card.style.setProperty('--swipe-rot', '0deg');
        card.style.transform = '';
    }

    function updateFeedback(ctx, dx, dy) {
        var opts = ctx.options;
        var sels = opts.feedbackSelectors;
        if (!sels || (!sels.right && !sels.left && !sels.up)) return;

        var root = (opts.feedbackPosition === 'container') ? ctx.card.parentElement : document;
        if (!root) return;

        var elRight = sels.right ? root.querySelector(sels.right) : null;
        var elLeft = sels.left ? root.querySelector(sels.left) : null;
        var elUp = sels.up ? root.querySelector(sels.up) : null;

        var max = opts.feedbackMaxDistance;
        var oRight = 0, oLeft = 0, oUp = 0;

        if (dx > 40) oRight = easeInOutOpacity(dx, max);
        else if (dx < -40) oLeft = easeInOutOpacity(dx, max);
        else if (dy < -40 && Math.abs(dx) < 40) oUp = easeInOutOpacity(dy, max);

        if (elRight) elRight.style.opacity = String(oRight);
        if (elLeft) elLeft.style.opacity = String(oLeft);
        if (elUp) elUp.style.opacity = String(oUp);
    }

    function easeInOutOpacity(dist, max) {
        var t = Math.min(Math.abs(dist) / (max || 150), 1);
        return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
    }


    /* ═══════════════════════════════════════════════════════════════════
     * §11 — API PÚBLICA
     * ═══════════════════════════════════════════════════════════════════ */

    var TranslateL = {
        version: VERSION,

        init: function (card, userOptions) {
            if (!card || card.nodeType !== 1) {
                console.warn('[translateL] init: card inválido.');
                return;
            }
            var id = card.getAttribute('data-id');
            if (!id) {
                console.warn('[translateL] init: card sem data-id, ignorado.');
                return;
            }

            var existing = contexts.get(card);
            if (existing) {
                existing.options = mergeOptions(userOptions);
                return existing;
            }

            var opts = mergeOptions(userOptions);

            var ctx = {
                card: card,
                id: String(id),
                options: opts,
                state: STATE.IDLE,
                pointerId: null,
                startX: 0,
                startY: 0,
                currentX: 0,
                currentY: 0,
                longPressTimer: null,
                moveDetected: false,
                voiceActive: false,
                recognition: null,
                fallbackEl: null,
                rafId: null
            };

            contexts.set(card, ctx);
            managedCards.add(card);

            if (opts.injectCoreCSS !== 'never') {
                ensureCoreEngineCSS();
                card.classList.add('translate-l-card');
            }

            injectFallback(ctx);

            if (opts.voice.enabled && HAS.speechRecognition) {
                setupVoice(ctx);
            }

            var container = card.parentElement;
            if (container && !containerListeners.has(container)) {
                container.addEventListener('pointerdown', onContainerPointerDown);
                containerListeners.add(container);
            }

            // NOVO: Sincroniza o foco logo após injetar
            if (container) {
                syncTopCardFocus(container);
            }

            return ctx;
        },

        destroy: function (card) {
            var ctx = contexts.get(card);
            if (!ctx) return;

            TranslateL.stop(card);

            if (ctx.recognition) {
                try { ctx.recognition.stop(); } catch (e) { /* noop */ }
                ctx.recognition = null;
            }

            if (ctx.fallbackEl && ctx.fallbackEl.parentNode) {
                ctx.fallbackEl.parentNode.removeChild(ctx.fallbackEl);
            }

            card.classList.remove('translate-l-card', 'translate-l-dragging', 'translate-l-transition', 'translate-l-long-press');

            var container = card.parentElement;
            contexts.delete(card);
            managedCards.delete(card);

            if (container) {
                syncTopCardFocus(container);
            }
        },

        stop: function (card) {
            var ctx = contexts.get(card);
            if (!ctx) return;

            if (ctx.longPressTimer) {
                clearTimeout(ctx.longPressTimer);
                ctx.longPressTimer = null;
            }
            if (ctx.rafId) {
                cAF(ctx.rafId);
                ctx.rafId = null;
            }
            if (ctx.pointerId != null) {
                activeByPointer.delete(ctx.pointerId);
            }
            resetTransform(ctx);
            enterIdle(ctx);
        },

        rebind: function (container, userOptions) {
            if (!container || !container.querySelectorAll) return;
            var cards = container.querySelectorAll('[data-id]');
            var novos = [];
            for (var i = 0; i < cards.length; i++) {
                if (!managedCards.has(cards[i])) {
                    TranslateL.init(cards[i], userOptions);
                    novos.push(cards[i]);
                }
            }
            if (novos.length) {
                var anyCtx = contexts.get(novos[0]);
                if (anyCtx) safeCall(anyCtx.options.hooks.onCardsLoaded, novos);
            }
            // NOVO: Atualiza o foco pós-AJAX
            syncTopCardFocus(container);
            return novos;
        },

        clearLongPress: function (card) {
            var ctx = contexts.get(card);
            if (!ctx) return;
            ctx.card.classList.remove(ctx.options.longPressClass);
            if (ctx.state === STATE.LONG_PRESS) {
                ctx.state = STATE.IDLE;
                ctx.pointerId = null;
            }
        },

        presets: {}
    };


    /* ═══════════════════════════════════════════════════════════════════
     * §12 — PRESETS
     * ═══════════════════════════════════════════════════════════════════ */

    TranslateL.presets.feed = function (userOverrides) {
        var preset = {
            modeClass: 'modo-swipe-ativo',
            bodyDraggingClass: 'fenda-arrastando',
            dragClass: 'translate-l-dragging',
            transitionClass: 'translate-l-transition',
            longPressClass: 'translate-l-long-press',
            feedbackSelectors: {
                right: '.feedback-swipe.feedback-direita',
                left: '.feedback-swipe.feedback-esquerda',
                up: '.feedback-swipe.feedback-cima'
            },
            feedbackPosition: 'viewport',
            hooks: {
                onSwipeRight: function (card, id) {
                    if (typeof window.enviarReacao === 'function') {
                        try {
                            var p = window.enviarReacao(id, 'amei');
                            if (p && typeof p.catch === 'function') p.catch(noop);
                        } catch (e) {
                            console.error('[translateL:feed] enviarReacao falhou:', e);
                        }
                    }
                },
                onSwipeLeft: function (card, id) {
                    if (card.classList.contains('post-admin-gold')) {
                        if (typeof window.confirmarExclusao === 'function') {
                            window.confirmarExclusao(id);
                        }
                    }
                },
                onSwipeUp: function (card, id) {
                    window.location.href = 'comentarios-post.php?id=' +
                        encodeURIComponent(id) + '#fofocar';
                },
                onLongPress: function (card, id, isOwner) {
                    if (typeof window.mostrarMenuAcoes === 'function') {
                        window.mostrarMenuAcoes(id, isOwner, card);
                    }
                },
                onBeforeLongPress: function () {
                    if (typeof window.forcarParadaSwipe === 'function') {
                        window.forcarParadaSwipe();
                    }
                },
                onPauseMedia: function (card) {
                    if (typeof window.pausarVideosGifDoCard === 'function') {
                        window.pausarVideosGifDoCard(card);
                    }
                },
                onResumeMedia: function (card) {
                    if (typeof window.retomarVideosGifDoCard === 'function') {
                        window.retomarVideosGifDoCard(card);
                    }
                },
                onCardRemoved: function () {
                    if (typeof window.abastecerPilhaFenda === 'function') {
                        window.abastecerPilhaFenda();
                    }
                }
            },
            accessibility: {
                labels: {
                    right: 'Curtir',
                    left: 'Descartar',
                    up: 'Comentar',
                    menu: 'Abrir menu de ações',
                    voice: 'Ativar comandos de voz'
                }
            },
            voice: {
                enabled: true,
                lang: 'pt-BR'
            }
        };
        return deepMerge(preset, userOverrides);
    };

    TranslateL.presets.balanga = function (userOverrides) {
        var preset = {
            modeClass: 'modo-tinder-ativo',
            bodyDraggingClass: 'bt-arrastando',
            dragClass: 'translate-l-dragging',
            transitionClass: 'translate-l-transition',
            longPressClass: 'translate-l-long-press',
            feedbackSelectors: {
                right: '.bt-feedback.bt-feedback-direita',
                left: '.bt-feedback.bt-feedback-esquerda',
                up: '.bt-feedback.bt-feedback-cima'
            },
            feedbackPosition: 'viewport',
            hooks: {
                onSwipeRight: function (card, id) {
                    if (typeof window.enviarRespostaEvento === 'function') {
                        window.enviarRespostaEvento(id, 'vou', card);
                    } else {
                        console.warn('[translateL:balanga] enviarRespostaEvento não exposto.');
                    }
                },
                onSwipeLeft: function (card, id) {
                    if (typeof window.enviarRespostaEvento === 'function') {
                        window.enviarRespostaEvento(id, 'nao_vou', card);
                    }
                },
                onSwipeUp: function (card, id) {
                    if (typeof window.enviarRespostaEvento === 'function') {
                        window.enviarRespostaEvento(id, 'talvez', card);
                    }
                },
                onBeforeLongPress: function () {
                    if (typeof window.btForcarParada === 'function') {
                        window.btForcarParada();
                    }
                }
            },
            accessibility: {
                labels: {
                    right: 'Vou',
                    left: 'Não vou',
                    up: 'Talvez',
                    menu: 'Abrir menu do evento',
                    voice: 'Ativar comandos de voz'
                }
            },
            voice: {
                enabled: true,
                lang: 'pt-BR'
            }
        };
        return deepMerge(preset, userOverrides);
    };


    /* ═══════════════════════════════════════════════════════════════════
     * BOOT GLOBAL
     * ═══════════════════════════════════════════════════════════════════ */

    window.addEventListener('pointermove', onWindowPointerMove, { passive: false });
    window.addEventListener('pointerup', onWindowPointerUp);
    window.addEventListener('pointercancel', onWindowPointerUp);

    global.TranslateL = TranslateL;

    console.info('[translateL] v' + VERSION + ' carregado. Features:',
        'pointer=' + HAS.pointerEvent,
        'capture=' + HAS.pointerCapture,
        'speechRec=' + HAS.speechRecognition,
        'focusWithin=' + HAS.focusWithin,
        'focusVisible=' + HAS.focusVisible
    );

})(window);