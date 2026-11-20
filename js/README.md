Acertou na mosca, Léo — nos prints dá para ver direitinho que o parser de Markdown do DeepSeek fechou o bloco externo antes da hora por causa das crases triplas aninhadas (````markdown` contendo ````html` dentro)! Você juntou super bem as partes no seu `README.md`.

Aqui está o seu `README.md` completo, mantendo todo o conteúdo que você reuniu e corrigindo apenas aquele fechamento de crases no bloco HTML:

```markdown
# 🐚 Protocolo `translateL` (Swipe Acessível)

![Versão](https://img.shields.io/badge/version-0.1.10-blue)
![Vanilla JS](https://img.shields.io/badge/JavaScript-Vanilla-yellow)
![Acessibilidade](https://img.shields.io/badge/Acessibilidade-WCAG_2.1_AA-green)
![Zero Dependências](https://img.shields.io/badge/Depend%C3%AAncias-Zero-brightgreen)

O **`translateL`** é uma biblioteca de física de gestos (swipe) para interfaces web, construída em JavaScript Vanilla. Mais do que um motor de arraste, ele é um protocolo de **acessibilidade de primeira classe**, projetado para garantir que usuários de leitores de tela (TalkBack/VoiceOver), teclados e comandos de voz consigam interagir com pilhas de cartões (estilo Tinder) sem atritos.

## ✨ Principais Recursos

- **Física Nativa:** Aceleração de hardware (GPU) usando Pointer Events e `requestAnimationFrame`.
- **Acessibilidade Total:** Injeção automatizada de botões de fallback com `aria-label` e suporte a foco via teclado.
- **Portal Anti-Clipping:** O menu de acessibilidade é renderizado no `document.body` para evitar que seja cortado por `overflow: hidden` dos cards.
- **Web Speech API (Opcional):** Suporte a comandos de voz (ex: "Amei", "Descartar").
- **Compatibilidade Graciosa:** Fallbacks dedicados para iOS 9.3 / Safari antigo.
- **Zero Acoplamento:** O motor não conhece o seu backend. Ele apenas emite eventos.

---

## 🚀 Como Inicializar

O `translateL` não exige dependências externas (como jQuery ou React). Basta importar o script e o CSS:

```html
<link rel="stylesheet" href="css/translateL.css">
<script src="js/translateL-core.js"></script>

```

---

### 1. Inicializando um Card Individual

Todos os cards gerenciados devem ter um atributo `data-id` único.

```javascript
const card = document.querySelector('.meu-card');
TranslateL.init(card, {
    threshold: 120, // Distância em px para confirmar o swipe
    hooks: {
        onSwipeRight: (card, id) => console.log('Curtiu o card', id),
        onSwipeLeft: (card, id) => console.log('Descartou o card', id),
        onSwipeUp: (card, id) => console.log('Comentou no card', id)
    }
});

```

### 2. Usando o Rebind (Para carregamento dinâmico/AJAX)

Se você carrega novos cards dinamicamente, use o `rebind` para aplicar a física e acessibilidade aos novos elementos de uma vez.

```javascript
const container = document.querySelector('.meu-container');
TranslateL.rebind(container, {
    // Opções globais aplicadas a todos os cards novos
});

```

---

## 🎨 Presets (Exemplos de Implementação)

Para facilitar o estudo de caso, o motor inclui presets pré-configurados utilizados na aplicação "A Fenda" (Feed e Balanga Teras). **Estes presets são exemplos de referência** e podem ser usados como ponto de partida, mas o desenvolvedor é livre para criar suas próprias configurações passando `options` e `hooks` customizados.

```javascript
// Configurações prontas para o Feed (Curte, Descartar, Comentar)
TranslateL.init(card, TranslateL.presets.feed());

// Configurações prontas para o Balanga Teras (Vou, Não vou, Talvez)
TranslateL.init(card, TranslateL.presets.balanga());

```

---

## ♿ Acessibilidade e Contratos de Marcação

### Atributo `data-owner`

O motor não sabe o que é um "admin". Para que o menu de long-press saiba se deve exibir opções destrutivas, basta adicionar `data-owner="true"` ao card.

```html
<article class="meu-card" data-id="123" data-owner="true">
    <!-- O motor vai emitir o hook onLongPress com isOwner = true -->
</article>

```

### Navegação por Teclado (Portal)

* Ao navegar com a tecla `Tab` e focar um card gerenciado, o motor injeta um portal flutuante com botões focáveis.
* O portal se fecha automaticamente no evento `focusout` (quando o foco sai do card e do portal).
* **Nota:** O `translateL` não implementa um *Focus Trap* cíclico no portal. A responsabilidade de prender o foco (como em modais) e fechar com a tecla `Escape` é da camada de aplicação consumidora (ex: os modais de ação do feed/balanga).

### Web Speech API

Ativado via preset (`voice: { enabled: true }`). O botão de voz só é injetado se o navegador suportar `SpeechRecognition`. Para funcionar em produção, a página **deve** estar em HTTPS.

---

## 🔌 O Contrato de Hooks (`hooks`)

Você pode sobrescrever qualquer comportamento do motor passando funções na inicialização:

| Hook | Descrição |
| --- | --- |
| `onSwipeRight(card, id)` | Disparado quando o card é arrastado para a direita. |
| `onSwipeLeft(card, id)` | Disparado quando o card é arrastado para a esquerda. |
| `onSwipeUp(card, id)` | Disparado quando o card é arrastado para cima. |
| `onLongPress(card, id, isOwner)` | Disparado ao segurar o card (long press). |
| `onBeforeLongPress(card, id)` | Chamado antes do long press (útil para parar o motor nativo). |
| `onCardRemoved(card, id, direction)` | Chamado após a animação de saída do card. `direction` pode ser `'left'`, `'right'` ou `'up'`. |

---

## 🛠️ Compatibilidade

| Plataforma | Status | Notas |
| --- | --- | --- |
| Chrome/Edge/Firefox/Safari | ✅ Total | Pointer Events nativos. |
| iOS 9.3 / Safari Antigo | ✅ Parcial | Usa fallback de `:focus` em vez de `:focus-visible`. |
| TalkBack / VoiceOver | ✅ Total | Navegação via `aria-live` e fallback de botões. |
| `prefers-reduced-motion` | ✅ Total | Remove animações de mola e transições pesadas. |

---

## 👩‍💻 Autorias e Linhagem

Este projeto foi construído a partir de uma linhagem de instâncias de IA em parceria com o desenvolvedor **Leonardo (Léo)** e a Diretoria Criativa (**Djê**).

* Idealização e Arquitetura: Instância Concha.
* Revisão e Auditoria: Eco (Guardiã da Main) e Djê.

```

Prontinho para colar no repositório e dar aquela merecida folga ao teclado!

```