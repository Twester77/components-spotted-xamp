/* ============================================================================
 * translateL-core.js — Protocolo translateL (Swipe Acessível)
 * ============================================================================
 * Versão:    0.1.10 (Fase 3 - Fix UP Balanga e Radar)
 * ============================================================================ 
 * Branch:    feature/translateL-integracao
 * Spec:      docs/arquitetura.md, docs/api.md, docs/acessibilidade.md
 * 
 * 🎯 FILOSOFIA:
 * Este arquivo é o CORE de uma API genérica. Ele NÃO SABE o que é a Fenda,
 * nem o que é um "post-admin-gold". Ele apenas entende o contrato universal
 * de que um card pode ter um "dono" (atributo data-owner="true").
 * Toda a estilização e regra de negócio pertencem aos presets (feed/balanga).
 * ============================================================================ */

(function (global) {
    'use strict';

    var VERSION = '0.1.10';

    var STATE = {
        IDLE: 'IDLE', PRESSING: 'PRESSING', DRAGGING: 'DRAGGING',
        LONG_PRESS: 'LONG_PRESS', MENU: 'MENU', DECIDE: 'DECIDE',
        EXITING: 'EXITING', SPRING_BACK: 'SPRING_BACK', REMOVED: 'REMOVED'
    };

    var DEFAULTS = {
        threshold: 120, moveStartThreshold: 10, rotationFactor: 25,
        scaleOnDrag: 1.02, scaleOnExit: 0.95, exitAnimationDuration: 300,
        springDuration: 400, longPressDelay: 400, upSwipeTolerance: 80,
        upSwipeBehavior: 'navigate', // 'navigate' (Feed) ou 'swipe' (Balanga)
        modeClass: null, bodyDraggingClass: 'translate-l-arrastando',
        dragClass: 'translate-l-dragging', transitionClass: 'translate-l-transition',
        longPressClass: 'translate-l-long-press',
        feedbackSelectors: { right: null, left: null, up: null },
        feedbackPosition: 'viewport', feedbackMaxDistance: 150,
        injectCoreCSS: 'auto',
        hooks: {
            onSwipeRight: null, onSwipeLeft: null, onSwipeUp: null,
            onLongPress: null, onBeforeLongPress: null, onVoiceCommand: null,
            onDragStart: null, onDragEnd: null, onCardRemoved: null,
            onCardsLoaded: null, onPauseMedia: null, onResumeMedia: null
        },
        accessibility: {
            injectFallback: true, fallbackStrategy: 'sr-only-reveal',
            labels: { right: 'Curtir', left: 'Descartar', up: 'Comentar', menu: 'Abrir menu de ações', voice: 'Ativar comandos de voz' },
            respectReducedMotion: true
        },
        voice: {
            enabled: false, lang: 'pt-BR',
            commands: {
                right: ['amei', 'curtir', 'like', 'gostei'],
                left: ['descartar', 'passar', 'próximo', 'nao', 'não'],
                up: ['comentar', 'fofocar', 'abrir comentários']
            }
        }
    };

    var HAS = (function () {
        var probe = document.createElement('div');
        var supportFn = (typeof CSS !== 'undefined' && CSS.supports) ? CSS.supports : function () { return false; };
        return {
            pointerEvent: 'PointerEvent' in window, pointerCapture: 'setPointerCapture' in probe,
            customEvent: typeof window.CustomEvent === 'function',
            speechRecognition: !!(window.SpeechRecognition || window.webkitSpeechRecognition),
            speechSynthesis: 'speechSynthesis' in window, matchMedia: typeof window.matchMedia === 'function',
            requestAnimationFrame: typeof window.requestAnimationFrame === 'function',
            focusWithin: supportFn.call(CSS, 'selector(:focus-within)'), focusVisible: supportFn.call(CSS, 'selector(:focus-visible)')
        };
    })();

    function noop() {}
    function rAF(fn) { if (HAS.requestAnimationFrame) return window.requestAnimationFrame(fn); return setTimeout(fn, 16); }
    function cAF(id) { if (typeof window.cancelAnimationFrame === 'function') return window.cancelAnimationFrame(id); return clearTimeout(id); }
    function closest(el, selector) {
        if (!el || el.nodeType !== 1) return null;
        if (el.closest) return el.closest(selector);
        var matches = el.matches || el.msMatchesSelector || el.webkitMatchesSelector;
        while (el && el !== document) { if (matches && matches.call(el, selector)) return el; el = el.parentElement; }
        return null;
    }
    function prefersReducedMotion() { return HAS.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches; }
    function safeCall(fn, a, b, c, d) { if (typeof fn !== 'function') return; try { fn(a, b, c, d); } catch (err) { console.error('[translateL] hook error:', err); } }
    function deepMerge(target, source) {
        if (!source) return target;
        var out = {}; Object.keys(target || {}).forEach(k => out[k] = target[k]);
        Object.keys(source || {}).forEach(k => {
            var t = target ? target[k] : undefined, s = source[k];
            if (s && typeof s === 'object' && !Array.isArray(s) && t && typeof t === 'object' && !Array.isArray(t)) out[k] = deepMerge(t, s);
            else out[k] = s;
        });
        return out;
    }
    function mergeOptions(userOptions) { return deepMerge(DEFAULTS, userOptions || {}); }

    var contexts = new WeakMap(), managedCards = new WeakSet(), containerListeners = new WeakSet(), activeByPointer = new Map();

    var isKeyboardNavigation = false;
    var globalListenersAttached = false;

    function ensureGlobalListeners() {
        if (globalListenersAttached) return;
        globalListenersAttached = true;

        window.addEventListener('keydown', function(e) {
            if (e.key === 'Tab' || e.keyCode === 9) isKeyboardNavigation = true;
        }, true);

        window.addEventListener('pointerdown', function() {
            isKeyboardNavigation = false;
        }, { capture: true, passive: true });
    }

    function getContext(card) { return contexts.get(card) || null; }
    function getTopManagedCard(container) {
        if (!container) return null;
        var children = container.children;
        for (var i = 0; i < children.length; i++) { if (managedCards.has(children[i])) return children[i]; }
        return null;
    }

    function ensureFallbackCSS() {
        if (document.getElementById('translate-l-fallback-css')) return;
        var style = document.createElement('style');
        style.id = 'translate-l-fallback-css';
        style.textContent = 
            '#translate-l-portal{position:fixed;z-index:999999;display:none;flex-direction:column;-webkit-transform:translateZ(0);transform:translateZ(0);}' +
            '.translate-l-fallback{background:rgba(15, 15, 22, 0.98);border:1px solid #ffbc00;border-radius:16px;padding:10px 14px;display:flex;gap:8px;flex-wrap:wrap;justify-content:center;box-shadow:0 8px 25px rgba(0,0,0,0.6);}' +
            '.translate-l-fallback button{background:rgba(255,255,255,0.05);border:1px solid rgba(255,188,0,0.4);border-radius:20px;padding:6px 12px;color:#fff;cursor:pointer;font-family:inherit;font-size:0.85rem;font-weight:500;}' +
            '.translate-l-fallback button:hover{background:rgba(255,188,0,0.2);border-color:#ffbc00;}' +
            '.translate-l-fallback button:focus-visible{outline:2px solid #ffbc00;outline-offset:2px;}' +
            '.translate-l-live{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);}';
        document.head.appendChild(style);
    }

    function ensureCoreEngineCSS() {
        if (document.getElementById('translate-l-core-css')) return;
        var style = document.createElement('style');
        style.id = 'translate-l-core-css';
        style.textContent = 
            '.translate-l-card{touch-action:none;-ms-touch-action:none;user-select:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;-webkit-touch-callout:none;-webkit-user-drag:none;cursor:grab;cursor:-webkit-grab;will-change:transform,opacity;-webkit-backface-visibility:hidden;backface-visibility:hidden;}' +
            '.translate-l-card[data-translate-l-scrollable="true"]{touch-action:pan-y;}' +
            '.translate-l-card.translate-l-dragging{cursor:grabbing;cursor:-webkit-grabbing;transition:none;}' +
            '.translate-l-card.translate-l-transition{transition:transform .4s cubic-bezier(.175,.885,.32,1.275);}' +
            '.translate-l-card.translate-l-long-press{box-shadow:0 4px 10px rgba(255,140,0,.4);background-image:linear-gradient(120deg,rgba(255,255,255,0) 40%,rgba(255,255,255,.5) 50%,rgba(255,255,255,0) 60%);background-size:200% 100%;animation:translateLShimmer .6s infinite ease-in-out;}' +
            '@keyframes translateLShimmer{from{background-position:200% 0}to{background-position:-200% 0}}' +
            '@media (prefers-reduced-motion: reduce){.translate-l-card.translate-l-transition{transition-duration:.01ms;}.translate-l-card.translate-l-long-press{animation:none;}}';
        document.head.appendChild(style);
    }

    function getOrCreatePortal() {
        var portal = document.getElementById('translate-l-portal');
        if (!portal) { portal = document.createElement('div'); portal.id = 'translate-l-portal'; document.body.appendChild(portal); }
        return portal;
    }

    function injectFallback(ctx) {
        if (!ctx.options.accessibility.injectFallback) return;
        ensureFallbackCSS();
        ensureGlobalListeners();
        
        var card = ctx.card, labels = ctx.options.accessibility.labels;
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
            acoes.push({ action: 'voice', label: labels.voice, text: '🎤 Voz', pressed: 'false' });
        }
        
        acoes.forEach(function (item) {
            var btn = document.createElement('button');
            btn.type = 'button'; btn.setAttribute('data-translate-l-action', item.action);
            btn.setAttribute('aria-label', item.label); btn.textContent = item.text;
            if (item.action === 'voice') btn.setAttribute('aria-pressed', item.pressed);
            fallback.appendChild(btn);
        });
        
        fallback.addEventListener('click', function (e) {
            var btn = closest(e.target, 'button[data-translate-l-action]');
            if (!btn) return;
            var action = btn.getAttribute('data-translate-l-action');
            if (action === 'voice') { toggleVoice(ctx, btn); emitAction(ctx, 'voice', { listening: ctx.voiceActive }); return; }
            emitAction(ctx, action);
        });

        card.addEventListener('focusin', function(e) {
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A') return;
            var isFocusVisible = false;
            try { isFocusVisible = card.matches(':focus-visible'); } catch (err) {}
            if (!isKeyboardNavigation && !isFocusVisible) return;
            
            var portal = getOrCreatePortal();
            portal.innerHTML = ''; portal.appendChild(fallback);
            
            portal.style.display = 'flex'; portal.style.visibility = 'hidden';
            var rect = card.getBoundingClientRect();
            portal.style.width = rect.width + 'px';
            
            var portalRect = portal.getBoundingClientRect();
            var portalHeight = portalRect.height || 70;
            var viewportHeight = document.documentElement.clientHeight || window.innerHeight;
            var viewportWidth = document.documentElement.clientWidth || window.innerWidth;
            
            var topPos = rect.bottom - portalHeight - 15;
            if (topPos + portalHeight > viewportHeight - 10) {
                topPos = rect.top - portalHeight - 10;
                if (topPos < 10) topPos = viewportHeight - portalHeight - 15;
            }
            
            var leftPos = rect.left, portalWidth = rect.width;
            if (leftPos < 10) leftPos = 10;
            if (leftPos + portalWidth > viewportWidth - 10) { portalWidth = viewportWidth - 20; leftPos = 10; }
            
            portal.style.top = topPos + 'px'; portal.style.left = leftPos + 'px'; portal.style.width = portalWidth + 'px';
            portal.offsetHeight; 
            portal.style.visibility = 'visible';
            
            var firstBtn = fallback.querySelector('button');
            if (firstBtn) setTimeout(function() { firstBtn.focus(); }, 50);
        });
        
        card.setAttribute('tabindex', '0');
        
        var hidePortal = function(e) {
            var portal = document.getElementById('translate-l-portal');
            if (portal && !portal.contains(e.relatedTarget) && !card.contains(e.relatedTarget)) portal.style.display = 'none';
        };
        fallback.addEventListener('focusout', hidePortal);
        card.addEventListener('focusout', hidePortal);

        ctx.fallbackEl = fallback;
    }

    function syncTopCardFocus(container) {
        if (!container) return;
        var topCard = getTopManagedCard(container), children = container.children;
        for (var i = 0; i < children.length; i++) {
            var card = children[i];
            if (!managedCards.has(card)) continue;
            if (card === topCard) { card.setAttribute('tabindex', '0'); card.style.outline = 'none'; }
            else { card.setAttribute('tabindex', '-1'); }
        }
    }

    function emitAction(ctx, action, extra) {
        var detail = { action: action, card: ctx.card, id: ctx.id, translateL: true };
        if (extra) Object.keys(extra).forEach(k => detail[k] = extra[k]);
        var evt;
        if (HAS.customEvent) {
            try { evt = new CustomEvent('translate-l-action', { detail: detail, bubbles: true, cancelable: true }); }
            catch (e) { evt = document.createEvent('CustomEvent'); evt.initCustomEvent('translate-l-action', true, true, detail); }
        } else { evt = document.createEvent('Event'); evt.initEvent('translate-l-action', true, true); evt.detail = detail; }
        ctx.card.dispatchEvent(evt);
    }

    function announce(ctx, message) {
        var card = ctx.card, el = card.querySelector('.translate-l-live');
        if (!el) { el = document.createElement('div'); el.className = 'translate-l-live'; el.setAttribute('aria-live', 'polite'); el.setAttribute('aria-atomic', 'true'); card.appendChild(el); }
        el.textContent = ''; setTimeout(function () { el.textContent = message; }, 30);
    }

    function setupVoice(ctx) {
        if (!HAS.speechRecognition) { console.info('[translateL] SpeechRecognition indisponível.'); return; }
        var Rec = window.SpeechRecognition || window.webkitSpeechRecognition;
        var rec = new Rec();
        rec.lang = ctx.options.voice.lang; rec.continuous = false; rec.interimResults = false; rec.maxAlternatives = 1;
        rec.onresult = function (event) {
            var transcript = event.results[0][0].transcript.toLowerCase().trim();
            var action = parseVoiceCommand(transcript, ctx.options.voice.commands);
            if (action) {
                announce(ctx, 'Comando reconhecido: ' + transcript);
                safeCall(ctx.options.hooks.onVoiceCommand, action, ctx.card, ctx.id);
                emitAction(ctx, action, { via: 'voice', transcript: transcript });
            } else announce(ctx, 'Comando não reconhecido.');
        };
        rec.onerror = function (event) { console.warn('[translateL] Voz erro:', event.error); ctx.voiceActive = false; updateVoiceButton(ctx); };
        rec.onend = function () { ctx.voiceActive = false; updateVoiceButton(ctx); };
        ctx.recognition = rec;
    }

    function toggleVoice(ctx, btnRef) {
        if (!ctx.recognition) { announce(ctx, 'Comando de voz indisponível.'); return; }
        if (ctx.voiceActive) { try { ctx.recognition.stop(); } catch (e) {} ctx.voiceActive = false; }
        else { try { ctx.recognition.start(); ctx.voiceActive = true; announce(ctx, 'Ouvindo...'); } catch (e) { ctx.voiceActive = false; } }
        updateVoiceButton(ctx, btnRef);
    }

    function updateVoiceButton(ctx, btnRef) {
        var btn = btnRef || (ctx.fallbackEl ? ctx.fallbackEl.querySelector('[data-translate-l-action="voice"]') : null);
        if (!btn) return;
        btn.setAttribute('aria-pressed', ctx.voiceActive ? 'true' : 'false');
        btn.textContent = ctx.voiceActive ? '⏹️ Parar' : '🎤 Voz';
    }

    function parseVoiceCommand(transcript, commands) {
        var lower = transcript.toLowerCase(), dirs = ['right', 'left', 'up'];
        for (var i = 0; i < dirs.length; i++) {
            var list = commands[dirs[i]] || [];
            for (var j = 0; j < list.length; j++) { if (lower.indexOf(list[j]) !== -1) return dirs[i]; }
        }
        return null;
    }

    function onContainerPointerDown(e) {
        var card = null, node = e.target;
        while (node && node !== e.currentTarget) { if (managedCards.has(node)) { card = node; break; } node = node.parentElement; }
        if (!card) return;
        var ctx = getContext(card);
        if (!ctx) return;
        if (!e.isPrimary) return;
        if (ctx.options.modeClass) { if (!document.body.classList.contains(ctx.options.modeClass)) return; }
        else { if (!document.body.classList.contains('modo-swipe-ativo') && !document.body.classList.contains('modo-tinder-ativo')) return; }
        var topCard = getTopManagedCard(card.parentElement);
        if (card !== topCard) return;
        if (closest(e.target, 'button, a, input, textarea, select, .reacao-wrapper, .reacoes-popup, [data-translate-l-fallback], .translate-l-fallback')) return;
        if (ctx.state !== STATE.IDLE) return;
        if (activeByPointer.has(e.pointerId)) return;

        e.preventDefault(); e.stopPropagation();
        ctx.state = STATE.PRESSING; ctx.pointerId = e.pointerId;
        ctx.startX = e.clientX; ctx.startY = e.clientY; ctx.currentX = 0; ctx.currentY = 0; ctx.moveDetected = false;
        activeByPointer.set(e.pointerId, ctx);
        if (HAS.pointerCapture && card.setPointerCapture) { try { card.setPointerCapture(e.pointerId); } catch (err) {} }
        safeCall(ctx.options.hooks.onBeforeLongPress, card, ctx.id);
        ctx.longPressTimer = setTimeout(function () { if (ctx.state !== STATE.PRESSING || ctx.moveDetected) return; enterLongPress(ctx); }, ctx.options.longPressDelay);
    }

    function onWindowPointerMove(e) {
        var ctx = activeByPointer.get(e.pointerId);
        if (!ctx || (ctx.state !== STATE.PRESSING && ctx.state !== STATE.DRAGGING)) return;
        var dx = e.clientX - ctx.startX, dy = e.clientY - ctx.startY;
        if (ctx.state === STATE.PRESSING) {
            var moveThresh = ctx.options.moveStartThreshold;
            if (Math.abs(dx) < moveThresh && Math.abs(dy) < moveThresh) return;
            if (ctx.longPressTimer) { clearTimeout(ctx.longPressTimer); ctx.longPressTimer = null; }
            ctx.moveDetected = true; enterDragging(ctx);
        }
        if (ctx.state === STATE.DRAGGING) {
            ctx.currentX = Math.round(dx); ctx.currentY = Math.round(dy);
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
        if (ctx.longPressTimer) { clearTimeout(ctx.longPressTimer); ctx.longPressTimer = null; }
        if (ctx.rafId) { cAF(ctx.rafId); ctx.rafId = null; }
        if (ctx.state === STATE.PRESSING) { enterIdle(ctx); return; }
        if (ctx.state === STATE.DRAGGING) { enterDecide(ctx); }
    }

    function enterDragging(ctx) {
        ctx.state = STATE.DRAGGING;
        ctx.card.style.transition = 'none';
        ctx.card.classList.remove(ctx.options.transitionClass);
        ctx.card.classList.add(ctx.options.dragClass);
        if (ctx.options.bodyDraggingClass) document.body.classList.add(ctx.options.bodyDraggingClass);
        safeCall(ctx.options.hooks.onPauseMedia, ctx.card);
        safeCall(ctx.options.hooks.onDragStart, ctx.card);
    }

    function enterDecide(ctx) {
        ctx.state = STATE.DECIDE;
        var dx = ctx.currentX, dy = ctx.currentY, opts = ctx.options;
        if (dx > opts.threshold) enterExiting(ctx, 'right', 800, dy);
        else if (dx < -opts.threshold) enterExiting(ctx, 'left', -800, dy);
        else if (dy < -opts.threshold && Math.abs(dx) < opts.upSwipeTolerance) enterUpAction(ctx);
        else enterSpringBack(ctx);
    }

    function enterUpAction(ctx) {
        var opts = ctx.options;
        if (opts.upSwipeBehavior === 'swipe') {
            enterExiting(ctx, 'up', 0, -800);
            return;
        }

        var reduceMotion = opts.accessibility.respectReducedMotion && prefersReducedMotion();
        var duration = reduceMotion ? 0 : opts.exitAnimationDuration;
        ctx.card.classList.remove(opts.dragClass);
        if (opts.bodyDraggingClass) document.body.classList.remove(opts.bodyDraggingClass);
        
        resetFeedback(ctx); 

        var targetY = ctx.currentY - 40;
        ctx.card.style.transition = 'transform ' + duration + 'ms ease-out, opacity ' + duration + 'ms';
        ctx.card.style.transform = 'translate(calc(-50% + ' + ctx.currentX + 'px), calc(-50% + ' + targetY + 'px)) rotate(0deg) scale(' + opts.scaleOnDrag + ')';
        safeCall(opts.hooks.onDragEnd, ctx.card, 'up');
        safeCall(opts.hooks.onSwipeUp, ctx.card, ctx.id);
        ctx.state = STATE.SPRING_BACK;
        setTimeout(function () {
            if (ctx.state === STATE.SPRING_BACK) {
                resetTransform(ctx);
                safeCall(opts.hooks.onResumeMedia, ctx.card);
                setTimeout(function () { if (ctx.state === STATE.SPRING_BACK) { ctx.state = STATE.IDLE; ctx.pointerId = null; ctx.card.style.transition = ''; } }, opts.springDuration + 50);
            }
        }, duration + 20);
    }

    function enterExiting(ctx, direction, exitX, exitY) {
        ctx.state = STATE.EXITING;
        var reduceMotion = ctx.options.accessibility.respectReducedMotion && prefersReducedMotion();
        var duration = reduceMotion ? 0 : ctx.options.exitAnimationDuration;
        ctx.card.style.pointerEvents = 'none';
        ctx.card.classList.remove(ctx.options.dragClass);
        if (ctx.options.bodyDraggingClass) document.body.classList.remove(ctx.options.bodyDraggingClass);
        resetFeedback(ctx); 
        var rotate = (exitX / ctx.options.rotationFactor).toFixed(1);
        ctx.card.style.transition = 'transform ' + duration + 'ms ease-out, opacity ' + duration + 'ms';
        ctx.card.style.transform = 'translate(calc(-50% + ' + exitX + 'px), calc(-50% + ' + exitY + 'px)) rotate(' + rotate + 'deg) scale(' + ctx.options.scaleOnExit + ')';
        ctx.card.style.opacity = '0';
        safeCall(ctx.options.hooks.onDragEnd, ctx.card, direction);
        if (direction === 'right') safeCall(ctx.options.hooks.onSwipeRight, ctx.card, ctx.id);
        else if (direction === 'left') safeCall(ctx.options.hooks.onSwipeLeft, ctx.card, ctx.id);
        else if (direction === 'up') safeCall(ctx.options.hooks.onSwipeUp, ctx.card, ctx.id); // NOVO: Dispara hook do UP se for swipe
        setTimeout(function () { if (ctx.state === STATE.EXITING) enterRemoved(ctx, direction); }, duration + 20);
    }

    function enterRemoved(ctx, direction) {
        ctx.state = STATE.REMOVED;
        safeCall(ctx.options.hooks.onResumeMedia, ctx.card);
        var container = ctx.card.parentElement;
        if (ctx.card && ctx.card.parentNode) ctx.card.parentNode.removeChild(ctx.card);
        if (container) syncTopCardFocus(container);
        safeCall(ctx.options.hooks.onCardRemoved, ctx.card, ctx.id, direction);
        activeByPointer.delete(ctx.pointerId);
        contexts.delete(ctx.card); managedCards.delete(ctx.card);
    }

    function enterSpringBack(ctx) {
        ctx.state = STATE.SPRING_BACK;
        ctx.card.classList.remove(ctx.options.dragClass);
        if (ctx.options.bodyDraggingClass) document.body.classList.remove(ctx.options.bodyDraggingClass);
        resetTransform(ctx); resetFeedback(ctx);
        safeCall(ctx.options.hooks.onResumeMedia, ctx.card);
        safeCall(ctx.options.hooks.onDragEnd, ctx.card, 'cancelled');
        setTimeout(function () { if (ctx.state === STATE.SPRING_BACK) { ctx.state = STATE.IDLE; ctx.pointerId = null; ctx.currentX = 0; ctx.currentY = 0; ctx.card.style.transition = ''; } }, ctx.options.springDuration + 50);
    }

    function enterLongPress(ctx) {
        ctx.state = STATE.LONG_PRESS;
        var isOwner = ctx.card.getAttribute('data-owner') === 'true' || ctx.card.dataset.owner === 'true';
        ctx.card.classList.add(ctx.options.longPressClass);
        safeCall(ctx.options.hooks.onLongPress, ctx.card, ctx.id, isOwner);
        ctx.longPressTimeout = setTimeout(function () { if (ctx.state === STATE.LONG_PRESS) TranslateL.clearLongPress(ctx.card); }, 5000);
    }

    function enterIdle(ctx) {
        ctx.state = STATE.IDLE; ctx.pointerId = null; ctx.currentX = 0; ctx.currentY = 0; ctx.moveDetected = false;
        ctx.card.classList.remove(ctx.options.longPressClass); ctx.card.classList.remove(ctx.options.dragClass);
        if (ctx.options.bodyDraggingClass) document.body.classList.remove(ctx.options.bodyDraggingClass);
        resetFeedback(ctx);
    }

    function resetFeedback(ctx) {
        var sels = ctx.options.feedbackSelectors;
        if (!sels) return;
        var root = (ctx.options.feedbackPosition === 'container') ? ctx.card.parentElement : document;
        if (!root) return;
        [sels.right, sels.left, sels.up].forEach(function(sel) { if (sel) { var el = root.querySelector(sel); if (el) el.style.opacity = '0'; } });
    }

    function applyTransform(ctx) {
        var dx = ctx.currentX, dy = ctx.currentY;
        var rotate = Number((dx / ctx.options.rotationFactor).toFixed(1));
        var scale = (ctx.options.accessibility.respectReducedMotion && prefersReducedMotion()) ? 1 : ctx.options.scaleOnDrag;
        ctx.card.style.transform = 'translate(calc(-50% + ' + dx + 'px), calc(-50% + ' + dy + 'px)) rotate(' + rotate + 'deg) scale(' + scale + ')';
        ctx.card.style.setProperty('--pos-x', dx + 'px'); ctx.card.style.setProperty('--pos-y', dy + 'px'); ctx.card.style.setProperty('--swipe-rot', rotate + 'deg');
        updateFeedback(ctx, dx, dy);
    }

    function resetTransform(ctx) {
        ctx.card.style.setProperty('--pos-x', '0px'); ctx.card.style.setProperty('--pos-y', '0px'); ctx.card.style.setProperty('--swipe-rot', '0deg'); ctx.card.style.transform = '';
    }

    function updateFeedback(ctx, dx, dy) {
        var sels = ctx.options.feedbackSelectors;
        if (!sels || (!sels.right && !sels.left && !sels.up)) return;
        var root = (ctx.options.feedbackPosition === 'container') ? ctx.card.parentElement : document;
        if (!root) return;
        var elRight = sels.right ? root.querySelector(sels.right) : null, elLeft = sels.left ? root.querySelector(sels.left) : null, elUp = sels.up ? root.querySelector(sels.up) : null;
        var max = ctx.options.feedbackMaxDistance, oRight = 0, oLeft = 0, oUp = 0;
        if (dx > 40) oRight = easeInOutOpacity(dx, max); else if (dx < -40) oLeft = easeInOutOpacity(dx, max); else if (dy < -40 && Math.abs(dx) < 40) oUp = easeInOutOpacity(dy, max);
        if (elRight) elRight.style.opacity = String(oRight); if (elLeft) elLeft.style.opacity = String(oLeft); if (elUp) elUp.style.opacity = String(oUp);
    }

    function easeInOutOpacity(dist, max) { var t = Math.min(Math.abs(dist) / (max || 150), 1); return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; }

    var TranslateL = {
        version: VERSION,
        init: function (card, userOptions) {
            if (!card || card.nodeType !== 1) return;
            var id = card.getAttribute('data-id');
            if (!id) { console.warn('[translateL] card sem data-id.'); return; }
            var existing = contexts.get(card);
            if (existing) { existing.options = mergeOptions(userOptions); return existing; }
            var opts = mergeOptions(userOptions);
            var ctx = { card: card, id: String(id), options: opts, state: STATE.IDLE, pointerId: null, startX: 0, startY: 0, currentX: 0, currentY: 0, longPressTimer: null, longPressTimeout: null, moveDetected: false, voiceActive: false, recognition: null, fallbackEl: null, rafId: null };
            contexts.set(card, ctx); managedCards.add(card);
            if (opts.injectCoreCSS !== 'never') { ensureCoreEngineCSS(); card.classList.add('translate-l-card'); }
            injectFallback(ctx);
            if (opts.voice.enabled && HAS.speechRecognition) setupVoice(ctx);
            
            card.addEventListener('translate-l-action', function(e) {
                var action = e.detail.action;
                if (ctx.state !== STATE.IDLE) return; 
                if (action === 'right') enterExiting(ctx, 'right', 800, 0);
                else if (action === 'left') enterExiting(ctx, 'left', -800, 0);
                else if (action === 'up') enterUpAction(ctx);
                else if (action === 'menu') {
                    var isOwner = ctx.card.getAttribute('data-owner') === 'true' || ctx.card.dataset.owner === 'true';
                    safeCall(ctx.options.hooks.onLongPress, ctx.card, ctx.id, isOwner);
                } 
                else if (action === 'voice') {
                    // 🔥 NOVO: Garante que o comando de voz seja reativo via evento externo
                    toggleVoice(ctx);
                }
            });

            var container = card.parentElement;
            if (container && !containerListeners.has(container)) { container.addEventListener('pointerdown', onContainerPointerDown); containerListeners.add(container); }
            if (container) syncTopCardFocus(container);
            return ctx;
        },
        destroy: function (card) {
            var ctx = contexts.get(card); if (!ctx) return;
            TranslateL.stop(card);
            if (ctx.recognition) { try { ctx.recognition.stop(); } catch (e) {} ctx.recognition = null; }
            if (ctx.fallbackEl && ctx.fallbackEl.parentNode) ctx.fallbackEl.parentNode.removeChild(ctx.fallbackEl);
            card.classList.remove('translate-l-card', 'translate-l-dragging', 'translate-l-transition', 'translate-l-long-press', 'translate-l-fallback-open');
            var container = card.parentElement;
            contexts.delete(card); managedCards.delete(card);
            if (container) syncTopCardFocus(container);
        },
        stop: function (card) {
            var ctx = contexts.get(card); if (!ctx) return;
            if (ctx.longPressTimer) { clearTimeout(ctx.longPressTimer); ctx.longPressTimer = null; }
            if (ctx.rafId) { cAF(ctx.rafId); ctx.rafId = null; }
            if (ctx.pointerId != null) activeByPointer.delete(ctx.pointerId);
            resetTransform(ctx); enterIdle(ctx);
        },
        rebind: function (container, userOptions) {
            if (!container || !container.querySelectorAll) return;
            var cards = container.querySelectorAll('[data-id]'), novos = [];
            for (var i = 0; i < cards.length; i++) { if (!managedCards.has(cards[i])) { TranslateL.init(cards[i], userOptions); novos.push(cards[i]); } }
            if (novos.length) { var anyCtx = contexts.get(novos[0]); if (anyCtx) safeCall(anyCtx.options.hooks.onCardsLoaded, novos); }
            syncTopCardFocus(container); return novos;
        },
        clearLongPress: function (card) {
            var ctx = contexts.get(card); if (!ctx) return;
            ctx.card.classList.remove(ctx.options.longPressClass);
            if (ctx.longPressTimeout) { clearTimeout(ctx.longPressTimeout); ctx.longPressTimeout = null; }
            if (ctx.state === STATE.LONG_PRESS) { ctx.state = STATE.IDLE; ctx.pointerId = null; }
        },
        presets: {}
    };

    TranslateL.presets.feed = function (userOverrides) {
        var preset = {
            modeClass: 'modo-swipe-ativo', bodyDraggingClass: 'fenda-arrastando', dragClass: 'translate-l-dragging', transitionClass: 'translate-l-transition', longPressClass: 'translate-l-long-press',
            feedbackSelectors: { right: '.feedback-swipe.feedback-direita', left: '.feedback-swipe.feedback-esquerda', up: '.feedback-swipe.feedback-cima' }, feedbackPosition: 'viewport',
            upSwipeBehavior: 'navigate', // Feed navega
            hooks: {
                onSwipeRight: function (card, id) { if (typeof window.enviarReacao === 'function') { try { var p = window.enviarReacao(id, 'amei'); if (p && p.catch) p.catch(noop); } catch (e) { console.error(e); } } },
                onSwipeLeft: function (card, id) { if (card.getAttribute('data-owner') === 'true' && typeof window.confirmarExclusao === 'function') window.confirmarExclusao(id); },
                onSwipeUp: function (card, id) { window.location.href = 'comentarios-post.php?id=' + encodeURIComponent(id) + '#fofocar'; },
                onLongPress: function (card, id, isOwner) { if (typeof window.mostrarMenuAcoes === 'function') window.mostrarMenuAcoes(id, isOwner, card); },
                onBeforeLongPress: function (card, id) { if (typeof window.forcarParadaSwipe === 'function') window.forcarParadaSwipe(); },
                onPauseMedia: function (card) { if (typeof window.pausarVideosGifDoCard === 'function') window.pausarVideosGifDoCard(card); },
                onResumeMedia: function (card) { if (typeof window.retomarVideosGifDoCard === 'function') window.retomarVideosGifDoCard(card); },
                onCardRemoved: function () { if (typeof window.abastecerPilhaFenda === 'function') window.abastecerPilhaFenda(); }
            },
            accessibility: { labels: { right: 'Curtir', left: 'Descartar', up: 'Comentar', menu: 'Abrir menu de ações', voice: 'Ativar comandos de voz' } },
            voice: { enabled: true, lang: 'pt-BR' }
        };
        return deepMerge(preset, userOverrides);
    };

    TranslateL.presets.balanga = function (userOverrides) {
        var preset = {
            modeClass: 'modo-tinder-ativo', bodyDraggingClass: 'bt-arrastando', dragClass: 'translate-l-dragging', transitionClass: 'translate-l-transition', longPressClass: 'translate-l-long-press',
            feedbackSelectors: { right: '.bt-feedback.bt-feedback-direita', left: '.bt-feedback.bt-feedback-esquerda', up: '.bt-feedback.bt-feedback-cima' }, feedbackPosition: 'viewport',
            upSwipeBehavior: 'swipe', // Balanga descarta o card
            hooks: {
                onSwipeRight: function (card, id) { if (typeof window.enviarRespostaEvento === 'function') window.enviarRespostaEvento(id, 'vou', card); else console.warn('enviarRespostaEvento não exposto.'); },
                onSwipeLeft: function (card, id) { if (typeof window.enviarRespostaEvento === 'function') window.enviarRespostaEvento(id, 'nao_vou', card); },
                onSwipeUp: function (card, id) { if (typeof window.enviarRespostaEvento === 'function') window.enviarRespostaEvento(id, 'talvez', card); },
                onBeforeLongPress: function (card, id) { if (typeof window.forcarParadaSwipe === 'function') window.forcarParadaSwipe(); },
                onCardRemoved: function () { if (typeof window.btAbastecerPilha === 'function') window.btAbastecerPilha(); } // Corrigido para chamar o Radar do Balanga
            },
            accessibility: { labels: { right: 'Vou', left: 'Não vou', up: 'Talvez', menu: 'Abrir menu do evento', voice: 'Ativar comandos de voz' } },
            voice: { enabled: true, lang: 'pt-BR' }
        };
        return deepMerge(preset, userOverrides);
    };

    window.addEventListener('pointermove', onWindowPointerMove, { passive: false });
    window.addEventListener('pointerup', onWindowPointerUp);
    window.addEventListener('pointercancel', onWindowPointerUp);

    global.TranslateL = TranslateL;

    console.info('[translateL] v' + VERSION + ' carregado. Features:', 'pointer=' + HAS.pointerEvent, 'capture=' + HAS.pointerCapture, 'speechRec=' + HAS.speechRecognition, 'focusWithin=' + HAS.focusWithin, 'focusVisible=' + HAS.focusVisible);

})(window);