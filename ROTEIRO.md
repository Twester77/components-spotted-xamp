

# Projeto a Fenda
"Professor, o projeto 'A Fenda' é um portal de interação estudantil desenvolvido em PHP e MySQL. Ele conta com um sistema de gerenciamento de estado via sessões, permitindo postagens identificadas ou anônimas, e utiliza Prepared Statements para garantir a integridade do banco de dados."

## O que é "Prepared Statements"? 
Imagine que o seu banco de dados é o tanque de combustível de um carro. O SQL Injection (o ataque dos hackers) é como se alguém tentasse jogar areia ou açúcar no seu tanque para ferrar com o motor.

O Prepared Statement (ou Declaração Preparada) é como um filtro de combustível de alta performance que você coloca antes da entrada do motor.

Como que isso funciona na prática:
* **A Receita (Template):** Primeiro, você manda para o banco de dados apenas o "esqueleto" da ordem. Ex: "SELECT * FROM usuarios WHERE email = ?"
* **A Blindagem:** O banco de dados prepara essa ordem e já sabe exatamente o que ela vai fazer.
* **O Envio do Dado (Bind):** Depois, você manda o dado (o e-mail do usuário) separado.
* **O banco de dados é inteligente:** ele pega esse dado e trata ele apenas como texto, nunca como um comando.

## Minuto 1: O Problema e a Solução
Fala: "Bom dia/noite, professor. O projeto 'A Fenda' nasceu da necessidade de centralizar a comunicação dos alunos da UNIFEV de forma anárquica, porém organizada. É um sistema de Spotted com foco em comunidade."

**Destaque: Mostre a Home com aquele texto de boas-vindas que a gente ajustou.**

## Minuto 2: Arquitetura e Organização (O diferencial!)
Fala: "Não fiz apenas um amontoado de arquivos. Usei uma arquitetura modular com Includes. Isso facilita a manutenção (o DRY - Don't Repeat Yourself). Se eu mudar a Navbar uma vez, ela muda no site todo."

**Destaque: Explique que o header.php e footer.php são reaproveitados.**

## Minuto 3: O Periscópio (A Toolbar)
Fala: "Um exemplo avançado dessa modularização é a nossa Toolbar. Ela funciona como um 'periscópio' do habitante, permitindo navegação rápida e configurações de áudio em qualquer página. Além disso, ela processa em tempo real quem está online através de cálculos de diferença de segundos no servidor, mostrando o status dos tripulantes que você segue."

**Destaque: Abra a Toolbar e mostre os botões de som e a lista de amigos online.**

## Minuto 4: Lógica de Sessão e Segurança
Fala: "Um dos maiores desafios foi a gestão de sessões. O session_start() é tratado como prioridade no topo do código para evitar erros de cabeçalho. Também usei mysqli_real_escape_string e uma trava de Regex no username para impedir espaços e garantir que as menções com '@' funcionem sempre perfeitamente."

**Destaque: Mostre o código do perfil.php ou processa-perfil.php.**

## Minuto 5: Banco de Dados e Filtros Dinâmicos
Fala: "O sistema de Achados e Perdidos usa filtros SQL dinâmicos. Em vez de criar várias páginas, eu uso uma lógica que filtra os posts por categoria (status) direto na Query, economizando processamento e código."

**Destaque: Mostre a página de Perdidos funcionando.**

## Minuto 6: Conclusão e Futuro
Fala: "O projeto já conta com sistema de login, feed interativo e perfil editável. Para o futuro (2º ano), pretendo implementar Orientação a Objetos para tornar o sistema ainda mais robusto, como se fosse um Circuito Integrado de funções."

**Destaque: Clique no seu novo botão de Editar Perfil e encerre com chave de ouro.**

## Encerramento
Este projeto foi desenvolvido de ponta a ponta por mim, unindo a precisão da programação com a necessidade humana de se conectar. 'A Fenda' não é apenas um site, é um espaço vivo. Muito obrigado a todos, eu sou Leonardo, idealizador do projeto da nossa Fenda, espero que tenham gostado! Com isso, encerramos a fase Alpha do projeto 'A Fenda'. O que era uma ideia de primeiro bimestre hoje é um sistema funcional pronto para evoluir para a versão Beta com a nossa turma de ADS. Muito obrigado!

---

## Perguntas Frequentes (FAQs):

**Pergunta: "Ah, mas não dá pra dar like?"**
* Resposta: "A Fenda prioriza, neste lançamento, a comunicação direta e o utilitarismo. As reações são elementos estéticos que pretendemos implementar com AJAX na próxima etapa para não comprometer a performance do carregamento inicial."

**Pergunta 1: "E se alguém postar algo ofensivo? Como você modera?"**
* Resposta: "Nesta fase MVP, a moderação é feita diretamente no banco de dados pelo administrador. Porém, o projeto já prevê na Seção de LGPD que a responsabilidade e o anonimato são monitorados. O próximo passo é implementar um botão de 'Denunciar'."

**Pergunta 2: "Por que você usou PHP puro e não um Framework?"**
* Resposta: "A decisão foi pedagógica e técnica. Usar PHP estruturado me permitiu dominar os fundamentos, a manipulação de sessões e a segurança via Prepared Statements na 'unha'. É como aprender a dirigir em um carro manual antes de ir para o automático."

**Pergunta 3: "O site é seguro contra hackers?"**
* Resposta: "A integridade é nossa prioridade. Utilizamos Prepared Statements para neutralizar SQL Injection e aplicamos travas no .htaccess para impedir a execução de scripts maliciosos em pastas de uploads."

**Pergunta 4: "Como você fez para o site ficar bom no celular e no PC?"**
* Resposta: "Utilizei Mobile First e Media Queries no CSS. O layout é fluido para o aluno que está no corredor da faculdade usando o 4G, então a leveza do código foi essencial."

**Pra explicar pro Menechelli:**
* "Professor, utilizei um operador ternário para tornar a folha de estilo dinâmica. Assim, o CSS reage em tempo real ao status do relacionamento entre os usuários, melhorando a experiência do usuário (UX)."

**Pergunta 5: "Onde os dados ficam salvos de verdade?"**
* Resposta: "Atualmente no servidor local via Apache, com MySQL gerenciado pelo phpMyAdmin. A estrutura está pronta para migrar para a nuvem (AWS ou Google Cloud) alterando apenas as constantes de conexão."

**Saída de Emergência:**
* "Essa é uma abordagem interessante e está sendo documentada para a nossa análise de requisitos da Versão 2.0. O foco atual foi a estabilidade do Core (núcleo) do sistema."