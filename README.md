# Ambiente Docker PHP 8 + MySQL + PDO

Este repositório contém a infraestrutura Docker inicial para o desenvolvimento do seu novo sistema em PHP 8 e MySQL.

## 🚀 Como Iniciar

### 1. Iniciar os Contêineres

No terminal, dentro da pasta do projeto, execute:

```bash
docker compose up -d
```

### 2. Acessar a Aplicação

- **Aplicação Web (PHP)**: [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin (Gerenciador do Banco)**: [http://localhost:8081](http://localhost:8081)

### 3. Credenciais Padrão do Banco

- **Host (interno do docker)**: `db`
- **Porta Host**: `33061`
- **Banco de Dados**: `app_db`
- **Usuário**: `app_user`
- **Senha**: `app_password`
- **Senha Root**: `root_password`

## 📁 Estrutura de Arquivos

- `Dockerfile`: Imagem PHP 8.3 Apache configurada com extensões `pdo`, `pdo_mysql`, `mysqli`, `zip` e `mod_rewrite`.
- `docker-compose.yml`: Definição dos serviços `web`, `db` e `phpmyadmin`.
- `.env`: Variáveis de ambiente editáveis.
- `src/`: Código fonte da sua aplicação PHP.
  - `src/db.php`: Função utilitária para obter a conexão PDO.
  - `src/index.php`: Página inicial com verificação de status.

## 🛠️ Encerrar os Contêineres

Para parar os contêineres:

```bash
docker compose down
```

Para parar e remover os volumes de dados do MySQL:

```bash
docker compose down -v
```
# w99score
