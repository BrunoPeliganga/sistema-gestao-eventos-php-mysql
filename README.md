# LiveEvents - Sistema de Gestão de Eventos e Bilheteira

Projeto final desenvolvido no âmbito da formação de Web Development. Trata-se de uma aplicação web para consulta de eventos, compra de bilhetes digitais e gestão administrativa.

---

## Tecnologias

- **Front-end:** HTML5, CSS3, Bootstrap 5, JavaScript (Fetch / AJAX).
- **Back-end:** PHP 8 (com PDO e Prepared Statements).
- **Base de Dados:** MySQL.

---

## Como Instalar e Executar

1. **Copiar o projeto:**
   Coloque a pasta `LiveEvents` dentro da diretoria do seu servidor local:
   ```
   C:/xampp/htdocs/pratice/LiveEvents
   ```

2. **Criar a base de dados:**
   - Abra o phpMyAdmin (`http://localhost/phpmyadmin`).
   - Crie uma base de dados chamada `eventos`.
   - Na aba **Importar**, selecione o ficheiro `eventos.sql` (na raiz do projeto) e clique em **Executar**.

3. **Abrir no navegador:**
   Aceda ao link:
   ```
   http://localhost/pratice/LiveEvents/index.php
   ```

---

## Contas de Teste

Para testar as diferentes permissões do sistema, utilize as seguintes credenciais:

- **Administrador:**
   - **Username:** `Jesus`
   - **Palavra-passe:** `123`

   - **Username:** `brunopeliganga`
   - **Palavra-passe:** `123`
  - *Dá acesso ao Painel de Administração (`/admin`), onde pode criar, editar e excluir eventos, gerir utilizadores e alterar o estado dos pagamentos.*

- **Cliente / Utilizador Comum:**
  - **Username:** `Codep24`
  - **Palavra-passe:** `123`
  - *Permite pesquisar eventos, adicionar bilhetes ao carrinho, finalizar compras e visualizar os bilhetes digitais no perfil.*

---

## Principais Funcionalidades

- **Catálogo com Pesquisa AJAX:** Filtros por nome, data, categoria, cidade e preço em tempo real sem recarregar a página.
- **Carrinho de Compras:** Adiciona bilhetes via AJAX, atualiza o contador no menu e respeita o limite de stock existente.
- **Checkout e Pagamento:** Validação de dados com JavaScript (bloqueio de caracteres inválidos, verificação de 9 dígitos e bloqueio de cartões expirados).
- **Bilhete Digital Individual:** Apresenta o bilhete oficial com código QR e botão para imprimir em nova aba (`target="_blank"`).
- **Área de Perfil:** Atualização de dados pessoais, alteração de palavra-passe e histórico das compras realizadas.
- **Painel Administrativo:** Dashboard com métricas de vendas, CRUD completo de eventos (com imagem atribuída por categoria), gestão de utilizadores e aprovação de encomendas (`Pendente` / `Concluído`).

---

## Estrutura do Projeto

```
LiveEvents/
├── admin/         # Painel e gestão administrativa
├── assets/        # CSS, JavaScript e imagens
├── auth/          # Login, registo e logout
├── cart/          # Carrinho de compras e endpoints de stock
├── config/        # Conexão à base de dados (PDO)
├── events/        # Catálogo, pesquisa dinâmica e detalhes
├── includes/      # Componentes partilhados (header, footer, helpers)
├── profile/       # Perfil do cliente e emissão de bilhetes
├── purchases/     # Checkout e histórico de encomendas
├── eventos.sql    # Base de dados pronta a importar
├── index.php      # Página principal
└── README.md      # Instruções do projeto
```
