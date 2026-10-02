# A Fenda — Spotted Universitário

Plataforma web em desenvolvimento para reunir publicações, conversas e serviços de interesse da comunidade universitária em um só lugar.

O projeto começou com a proposta de facilitar a divulgação de itens perdidos e encontrados e evoluiu para incluir um feed social, perfis, comunidades e eventos. Este repositório contém a aplicação e materiais de apoio à sua documentação.

> **Estado do projeto:** em desenvolvimento. Os recursos descritos abaixo refletem o código e a documentação disponíveis neste repositório; não representam uma garantia de disponibilidade contínua em produção nem uma validação completa de segurança, acessibilidade ou desempenho.

## Funcionalidades

| Área | Situação |
| --- | --- |
| Cadastro, autenticação e sessões | Implementada |
| Feed de publicações, comentários e reações | Implementada |
| Perfis, preferências e relações entre usuários | Implementada |
| Comunidades, participação de membros e gestão contextual | Implementada, conforme permissões da comunidade |
| Eventos, comentários e respostas de presença | Implementada |
| Notificações | Implementada |
| Achados e perdidos | Implementada como categoria de publicação |
| Classificados/marketplace | Em desenvolvimento; não considerar o fluxo completo disponível |
| Moderação global | Não disponível; há ações limitadas ao contexto de comunidades e a fluxos específicos |

## Tecnologias

- **Servidor:** PHP.
- **Interface:** HTML, CSS e JavaScript.
- **Banco de dados:** TiDB Serverless compatível com MySQL, conforme o esquema de produção documentado.
- **Serviços e integrações encontrados no projeto:** Supabase Auth e Backblaze B2.
- **Ambientes/configurações presentes no repositório:** Apache/Docker e Vercel.

A presença de uma integração ou configuração no código não significa que o serviço esteja ativo em todos os ambientes.

## Estrutura do projeto

O projeto mantém páginas, endpoints e arquivos de apoio organizados em diretórios existentes. Esta visão é apenas um guia para localizar as áreas principais; não representa uma arquitetura exaustiva.

| Caminho | Conteúdo |
| --- | --- |
| `*.php` | Páginas e processamento no servidor |
| `api/` | Entrada e roteamento de requisições da API |
| `includes/` | Componentes e lógica compartilhada |
| `css/` | Folhas de estilo |
| `js/` | Scripts de interação no navegador |
| `imagensfoto/`, `uploads/`, `sons/` | Recursos de mídia utilizados pela aplicação |
| `manifest.json`, `sw.js`, `offline.php` | Recursos relacionados à experiência instalável e ao modo offline |
| `vercel.json`, `Dockerfile` | Configurações de execução/publicação presentes no projeto |

## Executar localmente

O projeto pode ser explorado em um ambiente PHP com Apache, como o XAMPP. A compatibilidade declarada em `composer.json` é PHP 7.4 ou superior; os módulos PHP necessários podem variar conforme os recursos utilizados.

1. Clone o repositório para a pasta de projetos do ambiente local. No XAMPP, por exemplo, use `htdocs`.
2. Inicie o Apache e o serviço de banco de dados pelo painel do XAMPP.
3. Configure localmente a conexão com o banco e, se for utilizar os serviços externos, as credenciais correspondentes, de acordo com o ambiente em que a aplicação será executada.
4. Prepare um banco de desenvolvimento compatível com o esquema utilizado pela aplicação. Não use credenciais ou dados de produção para testes locais.
5. Acesse a pasta do projeto pelo navegador, por exemplo: `http://localhost/spotted-unifev/`.

Mantenha senhas, tokens e chaves em configuração privada local ou nas variáveis seguras do provedor de hospedagem; nunca os publique no repositório. A configuração local pode exigir ajustes de acordo com o ambiente e com os serviços habilitados.

O repositório também contém um `Dockerfile` e configurações para Vercel. As instruções de execução e os requisitos de cada ambiente devem ser conferidos antes da publicação; o uso dessas configurações não substitui a configuração segura de banco, armazenamento e autenticação.

## Modelagem e materiais visuais

A modelagem conceitual do domínio e os diagramas de casos de uso, classes, dados, navegação e arquitetura complementam a leitura do código. Os arquivos vetoriais e editáveis, além dos registros visuais de testes exploratórios, estão no repositório separado de [documentação e apêndices acadêmicos](https://github.com/Twester77/docmentacao-nic-apendices), organizados sob `documentacao/`.

O diagrama de classes é conceitual: os nomes apresentados descrevem elementos do domínio e não afirmam que a aplicação PHP esteja implementada por meio de classes com esses mesmos nomes. Os diagramas devem ser interpretados como registros do estado observado durante sua elaboração, não como substitutos do código e do esquema atual do banco.

## Testes e limitações

O projeto está em desenvolvimento e sua validação registrada é exploratória. Verificações visuais em emuladores de tela ou inspeções manuais não equivalem a testes exaustivos em dispositivos físicos, testes automatizados, auditoria de segurança, avaliação formal de acessibilidade ou testes de carga. Não há neste README uma declaração de certificação ou de cobertura completa.

## Licença e direitos autorais

O projeto é distribuído sob os termos de **Todos os direitos reservados**, conforme o arquivo [LICENSE](LICENSE). A publicação do repositório não concede autorização para copiar, modificar, redistribuir ou comercializar o código, o design ou os demais ativos.

A licença MIT existente no repositório de documentação aplica-se apenas aos materiais ali disponibilizados e não altera os direitos autorais nem os termos deste repositório da aplicação.
