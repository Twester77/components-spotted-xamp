# Projeto a Fenda
"Professor, o projeto 'A Fenda' é um portal de interação estudantil desenvolvido em PHP e MySQL. Ele conta com um sistema de gerenciamento de estado via sessões, permitindo postagens identificadas ou anônimas, e utiliza Prepared Statements para garantir a integridade do banco de dados."

## O que é "Prepared Statements"? 
Imagine que o seu banco de dados é o tanque de combustível de um carro. O SQL Injection (o ataque dos hackers) é como se alguém tentasse jogar areia ou açúcar no seu tanque para ferrar com o motor.

O Prepared Statement (ou Declaração Preparada) é como um filtro de combustível de alta performance que você coloca antes da entrada do motor.

Como que isso funciona na prática:
A Receita (Template): Primeiro, você manda para o banco de dados apenas o "esqueleto" da ordem. Ex: "SELECT * FROM usuarios WHERE email = ?"

**Note que você não mandou o e-mail ainda, só mandou o lugarzinho guardado pelo ponto de interrogação (?).**

* A Blindagem: O banco de dados prepara essa ordem e já sabe exatamente o que ela vai fazer.

* O Envio do Dado (Bind): Depois, você manda o dado (o e-mail do usuário) separado.

* O banco de dados é inteligente: ele pega esse dado e trata ele apenas como texto, nunca como um comando.

## Minuto 1: O Problema e a Solução
Fala: "Bom dia/noite, professor. O projeto 'A Fenda' nasceu da necessidade de centralizar a comunicação dos alunos da UNIFEV de forma anárquica, porém organizada. É um sistema de Spotted com foco em comunidade."

**Destaque: Mostre a Home com aquele texto de boas-vindas que a gente ajustou.**

## Minuto 2:Arquitetura e Organização (O diferencial!)
Fala: "Não fiz apenas um amontoado de arquivos. Usei uma arquitetura modular com Includes. Isso facilita a manutenção (o DRY - Don't Repeat Yourself). Se eu mudar a Navbar uma vez, ela muda no site todo."

**Destaque: Explique que o header.php e footer.php são reaproveitados.**

## Minuto 3: Lógica de Sessão e Segurança
Fala: "Um dos maiores desafios foi a gestão de sessões. O session_start() é tratado como prioridade no topo do código para evitar erros de cabeçalho (Header). Também usei mysqli_real_escape_string para tratar os dados e evitar problemas básicos de segurança no banco."

**Destaque: Mostre o código do perdidos.php ou do login.php.**

## Minuto 4: Banco de Dados e Filtros Dinâmicos
Fala: "O sistema de Achados e Perdidos usa filtros SQL dinâmicos. Em vez de criar várias páginas, eu uso uma lógica que filtra os posts por categoria (status) direto na Query, economizando processamento e código."

**Destaque: Mostre a página de Perdidos funcionando.**

## Minuto 5: Conclusão e Futuro
Fala: "O projeto já conta com sistema de login, feed interativo e perfil editável. Para o futuro (2º ano), pretendo implementar Orientação a Objetos para tornar o sistema ainda mais robusto, como se fosse um Circuito Integrado de funções."

**Destaque: Clique no seu novo botão de Editar Perfil e encerre com chave de ouro.**


## Este projeto foi desenvolvido de ponta a ponta por mim  , unindo a precisão da programação com a necessidade humana de se conectar. 'A Fenda' não é apenas um site, é um espaço vivo. Muito obrigado a todos, eu sou Leonardo, idealizador do projeto da nossa Fenda, espero que tenham gostado!