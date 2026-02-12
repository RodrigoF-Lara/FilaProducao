# VOLTEI A FUNCIONAR COMO AGENTE
# Projeto: Gestão de Filas PPCP e Requisição de Demandas

Este projeto é composto por duas partes principais e distintas:

1.  **Painel de Gestão de Filas PPCP**: Uma interface frontend para visualização e gerenciamento do fluxo de Ordens de Fabricação (OFs).
2.  **Ferramenta de Requisição de Demandas**: Um sistema backend em PHP para interagir com o banco de dados Oracle (FoccoERP) e requisitar materiais para as OFs.

---

## 0. Sistema de Login

A aplicação possui um sistema de autenticação simples que captura os dados do usuário:

- **Usuário**: Identificação do usuário no sistema
- **Senha**: Autenticação básica
- **Código Focco**: Código de usuário do FoccoERP que será utilizado em operações de requisição

Ao fazer login, o código Focco é armazenado em `sessionStorage` e utilizado automaticamente em todas as operações da aplicação, como requisições de demandas.

### Como Usar

Basta abrir o arquivo `login.html` ou acessar a aplicação. Se não estiver logado, será automaticamente redirecionado para a página de login.

---

## 1. Painel de Gestão de Filas PPCP (Dashboard)

Uma interface web (`index.html`, `script.js`, `styles.css`) que exibe um painel para acompanhar o status de ordens de produção em diferentes setores (MT1, MT2, etc.) e fases (Aguardando Produção, Separação, Componentes).

### Funcionalidades

-   **Navegação por Setores**: A barra lateral permite selecionar um setor específico para visualizar suas filas.
-   **Visualização por Status**: As ordens são organizadas em abas de acordo com seu status atual.
-   **Modal de Ações Centralizado**: Cada ordem possui um botão "⚙️ Ações" que abre um modal com funcionalidades detalhadas:
    -   Alterar o status da ordem (mover entre Produção, Separação, etc.).
    -   Gerenciar o processo de separação (iniciar, pausar, finalizar).
    -   Adicionar e visualizar um histórico de comentários.
    -   Mover a ordem para um setor diferente.

### Status Atual

Atualmente, esta interface opera com **dados estáticos (mock data)** definidos diretamente no arquivo `script.js`. A integração com um backend para buscar e salvar os dados dinamicamente ainda não foi implementada.

### Como Usar

Basta abrir o arquivo `index.html` em um navegador web.

---

## 2. Ferramenta de Requisição de Demandas (Backend)

Um sistema em PHP localizado na pasta `/Requisicao` que se conecta a um banco de dados Oracle para buscar e requisitar materiais para uma Ordem de Fabricação (OF).

### Pré-requisitos

-   Um servidor web com suporte a PHP (XAMPP, WAMP, etc.).
-   A extensão **PHP OCI8** para conexão com o banco de dados Oracle.
-   Acesso de rede ao servidor do banco de dados Oracle no endereço `192.168.2.60`.

### Configuração

As credenciais e o endereço do banco de dados estão configurados no arquivo `Requisicao/config_oracle.php`.

```php
// Requisicao/config_oracle.php
private const DB_USER = 'focco_consulta';
private const DB_PASS = 'consulta3i08';
private const DB_CONNECTION_STRING = '192.168.2.60:1521/f3ipro';
```

### Funcionalidades

-   **`Requisicao/index.php`**: Serve uma interface web para:
    -   Realizar uma requisição manual de um item específico.
    -   Buscar todas as demandas de uma OF.
    -   Requisitar individualmente ou em lote os itens pendentes de uma OF.
-   **`Requisicao/buscar_demandas.php`**: Endpoint que recebe o número da OF e retorna os itens de demanda em formato JSON.
-   **`Requisicao/testar_conexao.php`**: Um script simples para verificar se a conexão com o banco de dados Oracle está funcionando corretamente.

### Como Usar

1.  Certifique-se de que os pré-requisitos estão atendidos.
2.  Coloque a pasta do projeto no diretório do seu servidor web (ex: `c:/xampp/htdocs/`).
3.  Acesse a ferramenta através do seu navegador, apontando para a pasta `Requisicao`. Exemplo: `http://localhost/seu-projeto/Requisicao/`.

http://localhost/PROJETOS%20DESENVOLVEDOR/Cofelma/Mercado/Gest%C3%A3o%20Filas%20v1.1/login.html