markdown
# 📚 Leitor Social · Plataforma Inteligente para Leitores

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/pt-BR/docs/Web/JavaScript)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Google Books API](https://img.shields.io/badge/Google_Books_API-v1-4285F4?style=for-the-badge&logo=google&logoColor=white)](https://developers.google.com/books)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)
[![Code Style](https://img.shields.io/badge/Code%20Style-PSR--12-9cf?style=for-the-badge)](https://www.php-fig.org/psr/psr-12/)
[![Security](https://img.shields.io/badge/Security-Prepared%20Statements-brightgreen?style=for-the-badge)](https://php.net/manual/en/pdo.prepared-statements.php)

> **Uma experiência completa de gestão literária:** organize sua estante, compartilhe resenhas, receba recomendações inteligentes e conecte-se com clubes de leitura — tudo em uma única plataforma.

---

## 📖 Índice

- [Sobre o Projeto](#-sobre-o-projeto)
- [Funcionalidades em Destaque](#-funcionalidades-em-destaque)
- [Stack Tecnológica e Arquitetura](#-stack-tecnológica-e-arquitetura)
- [Estrutura do Projeto](#-estrutura-do-projeto-organização-de-pastas)
- [Documentação da API REST](#-documentação-da-api-rest)
  - [Autenticação](#autenticação)
  - [Estante do Usuário](#estante-do-usuário)
  - [Busca de Livros](#busca-de-livros-google-books)
  - [Recomendações](#recomendações)
  - [Perfil Público](#perfil-público)
  - [Clubes de Leitura](#clubes-de-leitura)
- [Como Executar](#-como-executar-o-projeto)
- [Como Usar (Fluxo Principal)](#-como-usar-fluxo-principal)
- [Roadmap](#-roadmap-melhorias-futuras)
- [Contribuição](#-contribuição)
- [Licença](#-licença)
- [Autor e Processo Autoral](#-autor--processo-autoral-e-créditos)

---

## 🧠 Sobre o Projeto

**Leitor Social** nasceu como um projeto de portfólio para demonstrar habilidades em **desenvolvimento full‑stack** com foco em usabilidade, performance e arquitetura limpa. A proposta é oferecer uma ferramenta que vá além do simples CRUD, incorporando **inteligência nas recomendações**, **interação social** e **integração com APIs externas** — tudo com uma interface moderna e responsiva.

Este sistema foi pensado para atender leitores apaixonados que desejam:

- Manter um registro detalhado de suas leituras com notas, resenhas e datas.
- Descobrir novos livros com base em seu perfil e histórico.
- Compartilhar resenhas e criar uma identidade pública como leitor.
- Participar de clubes de leitura e trocar ideias em discussões temáticas.

---

## 🚀 Funcionalidades em Destaque

### 🔐 Autenticação & Perfil
- Cadastro e login com sessão persistente e segurança (hash de senha, regeneração de ID, cookie seguro).
- Perfil público com estatísticas, lista de livros avaliados, resenhas e tags mais usadas.
- Compartilhamento do perfil via link, WhatsApp e Twitter.

### 📖 Estante Inteligente
- **Status personalizados:** “Quero Ler”, “Lendo”, “Concluído”.
- **Avaliações e resenhas:** nota de 1 a 5, resenha com edição posterior.
- **Controle de tempo:** datas de início e término da leitura.
- **Tags e categorias:** organize seus livros por gêneros ou temas (ex: "Ficção Científica", "Romance").
- **Busca avançada:** filtre por título, autor, tags ou status, tudo em tempo real.
- **Layout flexível:** visualize sua estante em grade ou lista.

### 🔍 Descoberta & Recomendações
- **Integração com Google Books API:** busque qualquer livro pelo título ou autor, com capa, sinopse e dados editoriais.
- **Links de compra:** acesso direto ao Google Play, Amazon, Submarino e Americanas com preços (quando disponíveis).
- **Recomendações personalizadas:** baseadas nas suas tags e histórico, com sistema de cache para performance e fallback local para momentos de indisponibilidade da API.

### 👥 Clubes de Leitura (Funcionalidade Social)
- Crie clubes públicos ou privados com código de convite único.
- Adicione e gerencie livros dentro do clube.
- Participe de discussões em tempo real com outros membros.
- Papéis de administrador, moderador e membro.

### 📊 Dashboard Visual & Estatísticas
- Contagem de livros por status.
- Distribuição de leitura por status e tags mais frequentes.
- Média de avaliações e total de resenhas.

### 🎨 Experiência de Usuário (UX)
- Interface responsiva com Bootstrap 5.
- Modais, notificações toast e feedback visual a cada ação.
- Animações suaves e transições.

---

## 🛠️ Stack Tecnológica e Arquitetura

| Camada            | Tecnologia / Ferramenta                         |
|-------------------|--------------------------------------------------|
| **Backend**       | PHP 8.2+ (POO, PDO, Sessões)                    |
| **Banco de Dados**| MySQL 5.7+ (modelo relacional, índices otimizados) |
| **Frontend**      | HTML5, CSS3, JavaScript ES6+ (Vanilla)          |
| **Framework CSS** | Bootstrap 5.3 + Custom CSS                      |
| **Ícones**        | Bootstrap Icons + Font Awesome 6                |
| **API Externa**   | Google Books API (REST, JSON)                   |
| **Comunicação**   | Fetch API com `credentials: 'include'`          |
| **Servidor**      | Apache com mod_rewrite e .htaccess              |
| **Versionamento** | Git + GitHub (commits semânticos)               |

### Padrões e Boas Práticas
- **Arquitetura MVC implícita:** separação de responsabilidades entre rotas (API), lógica de negócio e apresentação.
- **Tratamento de erros:** validações no frontend e backend, logs de erro, respostas JSON consistentes.
- **Segurança:** prepared statements (PDO), hash de senha (bcrypt), regeneração de sessão, cookies httponly e secure.
- **Cache e fallback:** recomendações com cache de 6 horas e fallback local para indisponibilidade da API.

---

## 📁 Estrutura do Projeto (Organização de Pastas)

```bash
leitor_social/
├── api/                         # Endpoints REST da aplicação
│   ├── auth.php                # Autenticação (login, registro, logout, sessão)
│   ├── books.php               # Busca de livros (Google Books API)
│   ├── groups.php              # Clubes: CRUD, membros, livros, discussões
│   ├── my-books.php            # Estante pessoal (CRUD completo)
│   ├── public-profile.php      # Dados públicos para perfil
│   └── recommendations.php     # Recomendações com cache e fallback
│
├── config/                      # Configurações
│   └── database.php            # Conexão PDO e configurações de sessão
│
├── public/                      # Frontend público
│   ├── index.html              # Página principal (busca, estante, recomendações)
│   ├── profile.html            # Perfil público do usuário
│   ├── groups.html             # Lista de clubes do usuário
│   ├── group.html              # Página detalhada de um clube
│   ├── app.js                  # JavaScript principal (SPA-like)
│   └── style.css               # Estilos customizados
│
├── .htaccess                    # Regras de reescrita e segurança (Apache)
├── README.md                    # Esta documentação
└── LICENSE                      # Licença MIT
📡 Documentação da API REST
A API segue o padrão RESTful, retornando respostas em JSON com códigos HTTP apropriados. Todos os endpoints que exigem autenticação requerem o envio do cookie de sessão (gerenciado automaticamente com credentials: 'include' no frontend).

🔐 Autenticação
GET /api/auth.php?action=me
Descrição: Verifica se o usuário está logado.
Autenticação: Não requer.
Resposta de sucesso (200):

json
{
  "logged": true,
  "user": { "id": 1, "name": "João Silva" }
}
Resposta de erro (200):

json
{ "logged": false }
POST /api/auth.php
Descrição: Login, cadastro ou logout (determinado pelo campo action).
Autenticação: Não requer para login/registro; requer para logout (cookie de sessão).
Body (Login):

json
{ "action": "login", "email": "joao@email.com", "password": "123456" }
Resposta de sucesso (200):

json
{
  "success": true,
  "user": { "id": 1, "name": "João Silva" }
}
Body (Cadastro):

json
{ "action": "register", "name": "João Silva", "email": "joao@email.com", "password": "123456" }
Resposta de sucesso (200):

json
{ "success": true, "message": "Usuário criado com sucesso!" }
Body (Logout):

json
{ "action": "logout" }
Resposta de sucesso (200):

json
{ "success": true }
Possíveis erros:

Código	Mensagem
400	"Todos os campos são obrigatórios."
400	"Email já cadastrado."
401	"Email ou senha incorretos."
500	"Erro interno ao cadastrar."
📖 Estante do Usuário
GET /api/my-books.php
Descrição: Retorna todos os livros da estante do usuário logado.
Autenticação: Requer (cookie de sessão).
Resposta de sucesso (200):

json
[
  {
    "id": 1,
    "google_book_id": "abc123",
    "title": "Harry Potter e a Pedra Filosofal",
    "authors": "J.K. Rowling",
    "thumbnail": "https://...",
    "status": "reading",
    "rating": 5,
    "review": "Excelente!",
    "started_at": "2025-01-01",
    "finished_at": null,
    "tags": "Fantasia, Aventura",
    "updated_at": "2025-01-15 10:00:00"
  }
]
POST /api/my-books.php
Descrição: Adiciona um livro à estante.
Autenticação: Requer.
Body:

json
{
  "google_book_id": "abc123",
  "title": "Harry Potter",
  "authors": "J.K. Rowling",
  "thumbnail": "https://...",
  "status": "want_to_read"
}
Resposta de sucesso (200):

json
{ "success": true, "message": "Livro adicionado à estante!" }
PUT /api/my-books.php
Descrição: Atualiza dados de um livro da estante.
Autenticação: Requer.
Body:

json
{
  "user_book_id": 1,
  "status": "finished",
  "rating": 5,
  "review": "Excelente!",
  "started_at": "2025-01-01",
  "finished_at": "2025-01-10",
  "tags": "Fantasia, Aventura"
}
Resposta de sucesso (200):

json
{ "success": true, "message": "Atualizado com sucesso!" }
DELETE /api/my-books.php
Descrição: Remove um livro da estante.
Autenticação: Requer.
Body:

json
{ "user_book_id": 1 }
Resposta de sucesso (200):

json
{ "success": true, "message": "Livro removido da estante." }
🔍 Busca de Livros (Google Books)
GET /api/books.php?q={query}
Descrição: Busca livros na API do Google Books.
Autenticação: Não requer, mas retorna mais dados se logado (preços, links de compra).
Parâmetros: q (título, autor, etc.).
Resposta de sucesso (200):

json
[
  {
    "google_book_id": "abc123",
    "title": "Harry Potter e a Pedra Filosofal",
    "authors": "J.K. Rowling",
    "thumbnail": "https://...",
    "description": "...",
    "publisher": "Editora Rocco",
    "pageCount": 264,
    "buyLink": "https://play.google.com/...",
    "price": "39,90",
    "currency": "BRL"
  }
]
🧠 Recomendações
GET /api/recommendations.php
Descrição: Retorna recomendações personalizadas com base nas tags e histórico do usuário. Possui cache de 6 horas e fallback local.
Autenticação: Requer.
Resposta de sucesso (200):

json
{
  "success": true,
  "recommendations": [
    {
      "google_book_id": "xyz",
      "title": "O Senhor dos Anéis",
      "authors": "J.R.R. Tolkien",
      "thumbnail": "https://...",
      "pageCount": 1200,
      "price": "59,90"
    }
  ],
  "based_on": "ficção fantasia",
  "tags": ["ficção", "fantasia"],
  "cached": false
}
Resposta de fallback (200):

json
{
  "success": true,
  "recommendations": [...],
  "based_on": "ficção",
  "fallback": true,
  "message": "Recomendações geradas localmente (API indisponível)"
}
👤 Perfil Público
GET /api/public-profile.php?user_id={id}
Descrição: Retorna dados públicos de um usuário.
Autenticação: Não requer.
Parâmetros: user_id (obrigatório).
Resposta de sucesso (200):

json
{
  "user": {
    "id": 1,
    "name": "João Silva",
    "member_since": "01/01/2025"
  },
  "books": [
    {
      "title": "Harry Potter",
      "authors": "J.K. Rowling",
      "thumbnail": "https://...",
      "status": "finished",
      "rating": 5,
      "review": "Excelente!",
      "tags": "Fantasia"
    }
  ],
  "stats": {
    "total_books": 10,
    "total_reviews": 8,
    "avg_rating": 4.5
  }
}
👥 Clubes de Leitura
GET /api/groups.php
Descrição: Lista todos os clubes do usuário ou detalhes de um clube específico.
Autenticação: Requer.
Parâmetros opcionais: ?id={group_id} para detalhes.
Resposta de sucesso (200) – lista:

json
[
  {
    "id": 1,
    "name": "Clube de Fantasia",
    "description": "Discussões sobre fantasia",
    "creator_name": "João Silva",
    "member_count": 5,
    "book_count": 3,
    "is_member": true
  }
]
Resposta de sucesso (200) – detalhes:

json
{
  "id": 1,
  "name": "Clube de Fantasia",
  "description": "Discussões sobre fantasia",
  "creator_name": "João Silva",
  "member_count": 5,
  "book_count": 3,
  "is_member": true,
  "join_code": "ABC123",
  "members": [
    { "id": 1, "name": "João Silva", "role": "admin" }
  ],
  "books": [
    { "id": 10, "title": "O Senhor dos Anéis", "status": "pending" }
  ],
  "discussions": [
    { "user_name": "João Silva", "message": "Alguém já leu?", "created_at": "2025-01-15 10:00:00" }
  ]
}
POST /api/groups.php (Criar)
Body:

json
{
  "action": "create",
  "name": "Clube de Ficção",
  "description": "Discussões sobre ficção científica",
  "is_private": false,
  "join_code": "ABC123"
}
Resposta de sucesso (200):

json
{ "success": true, "message": "Grupo criado com sucesso!", "group_id": 1, "join_code": "ABC123" }
POST /api/groups.php (Entrar)
Body:

json
{ "action": "join", "group_id": 1 }
ou

json
{ "action": "join", "join_code": "ABC123" }
Resposta de sucesso (200):

json
{ "success": true, "message": "Você entrou no grupo!" }
POST /api/groups.php (Sair)
Body:

json
{ "action": "leave", "group_id": 1 }
Resposta de sucesso (200):

json
{ "success": true, "message": "Você saiu do grupo." }
POST /api/groups.php (Adicionar livro ao clube)
Body:

json
{
  "action": "add_book",
  "group_id": 1,
  "book_id": 5,
  "status": "pending"
}
Resposta de sucesso (200):

json
{ "success": true, "message": "Livro adicionado ao grupo!" }
POST /api/groups.php (Remover livro do clube)
Body:

json
{
  "action": "remove_book",
  "group_id": 1,
  "book_id": 5
}
Resposta de sucesso (200):

json
{ "success": true, "message": "Livro removido do grupo." }
POST /api/groups.php (Discussão)
Body:

json
{
  "action": "discuss",
  "group_id": 1,
  "message": "Alguém já leu este livro?",
  "book_id": null
}
Resposta de sucesso (200):

json
{ "success": true, "message": "Mensagem enviada!" }
📊 Códigos de Status HTTP Comuns
Código	Significado
200	OK – requisição bem-sucedida.
400	Bad Request – parâmetros inválidos ou faltando.
401	Unauthorized – autenticação necessária.
403	Forbidden – permissão negada.
404	Not Found – recurso não encontrado.
405	Method Not Allowed – método HTTP não suportado.
500	Internal Server Error – erro interno.
⚙️ Como Executar o Projeto
Pré-requisitos
PHP 8.2+ com extensões: PDO, MySQLi, cURL, JSON.

MySQL 5.7+ ou MariaDB 10.3+.

Apache com mod_rewrite ativado.

Passo a Passo
Clone o repositório:

bash
git clone https://github.com/alvaroacarneiro/leitor-social.git
cd leitor-social
Importe a estrutura do banco de dados:

Acesse seu phpMyAdmin ou terminal MySQL.

Execute o script SQL (fornecido no repositório em database.sql).

Configure as credenciais de banco no arquivo config/database.php:

php
$host = 'localhost';
$dbname = 'leitor_social';
$username = 'seu_usuario';
$password = 'sua_senha';
Obtenha uma chave da Google Books API:

Acesse o Google Cloud Console.

Crie um projeto, ative a Books API e gere uma chave de API.

Substitua $apiKey nos arquivos api/books.php e api/recommendations.php.

Ajuste as permissões (servidor Linux):

bash
chmod 755 api config public
chmod 644 .htaccess
Configure o Apache para apontar para a pasta public/ como DocumentRoot ou utilize o .htaccess fornecido para roteamento.

Acesse http://localhost/leitor_social/public/index.html e comece a explorar.

🧩 Como Usar (Fluxo Principal)
Cadastre-se e faça login.

Busque livros pelo título ou autor na barra de pesquisa e adicione à sua estante.

Edite os livros na estante para definir status, nota, datas, tags e resenhas.

Explore recomendações personalizadas que aparecem automaticamente na página inicial.

Acesse o menu "Clubes" para criar seu próprio clube ou participar de um existente.

Compartilhe seu perfil público com outros leitores e veja seus livros e resenhas.

🗺️ Roadmap (Melhorias Futuras)
□ Dashboard com gráficos (Chart.js) para estatísticas visuais.
□ Tema escuro (Dark Mode) com persistência em localStorage.
□ Progresso de leitura com páginas lidas e barra de progresso.
□ Notificações por e-mail (PHPMailer) para lembretes de leitura.
□ Exportação da estante em CSV e PDF.
□ Sistema de comentários nas resenhas públicas.
□ PWA (Progressive Web App) para instalação e funcionamento offline parcial.
□ Recomendação com IA (OpenAI) para sugestões ainda mais precisas.
🤝 Contribuição
Contribuições são bem-vindas! Sinta-se à vontade para:

Relatar bugs através de Issues.

Sugerir melhorias ou novas funcionalidades.

Enviar pull requests com correções e aprimoramentos.

Guia de Contribuição
Faça um fork do projeto.

Crie uma branch para sua feature (git checkout -b feature/nova-funcionalidade).

Commit suas mudanças (git commit -m 'Adiciona nova funcionalidade').

Push para a branch (git push origin feature/nova-funcionalidade).

Abra um Pull Request descrevendo suas alterações.

📜 Licença
Este projeto é distribuído sob a Licença MIT, que permite uso, modificação e distribuição livre, desde que mantidos os créditos ao autor original. Veja o arquivo LICENSE para mais detalhes.

👨‍💻 Autor – Processo Autoral e Créditos
Desenvolvido por Álvaro Antonio Carneiro


Este projeto foi criado integralmente do zero por mim, desde a concepção da ideia, modelagem de banco de dados, arquitetura de código, design de interface e documentação. Todo o código PHP, JavaScript e CSS, bem como a lógica de negócio e a integração com a API do Google Books, são de minha autoria.

As únicas exceções são bibliotecas de terceiros (Bootstrap, Font Awesome) e a própria API do Google Books, que são utilizadas como ferramentas externas, devidamente creditadas.

Etapas do Desenvolvimento
Pesquisa e Planejamento: definição de requisitos funcionais e não funcionais, escolha da stack, modelagem do banco.

Desenvolvimento Backend: criação da API RESTful em PHP com PDO, tratamento de sessões e segurança.

Desenvolvimento Frontend: construção da interface com HTML, CSS e JavaScript puro, garantindo responsividade e usabilidade.

Integrações: consumo da Google Books API, implementação de sistema de recomendações com cache e fallback.

Testes e Ajustes: testes manuais de fluxo completo, correção de bugs e otimização de performance.

Documentação: redação do README e comentários no código para facilitar a manutenção.

O projeto representa meu compromisso com código limpo, boas práticas de desenvolvimento e uma experiência de usuário de qualidade.

Feito com 💙 por um apaixonado por livros e tecnologia.
