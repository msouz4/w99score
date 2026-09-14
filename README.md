# ⚽ w99score - Plataforma de Análise Estatística de Futebol & Oportunidades HT/FT

O **w99score** é uma plataforma avançada para análise estatística de partidas de futebol, focada em métricas detalhadas de 1º Tempo (HT) e Fim de Jogo (FT). O sistema oferece identificação automatizada de oportunidades estatísticas (*value bets*), cálculo de probabilidades, acompanhamento de assertividade e sincronização automática de dados.

---

## 🚀 Funcionalidades Principais

- 📊 **Análise Pré-Jogo Detalhada (`analise.php`)**:
  - Comparativo frente a frente (H2H) entre equipes.
  - Histórico de desempenho com métricas separadas por tempo: **Gols**, **Escanteios**, **Cartões Amarelos**, **Total de Chutes** e **Chutes a Gol**.
  - Cálculo de médias e probabilidades estatísticas por mercado (HT/FT).

- 🎯 **Buscador de Oportunidades (`oportunidades.php`)**:
  - Algoritmo automatizado (`OpportunityService.php`) para identificação de partidas com alta probabilidade estatística.
  - Pontuação de confiança (*Score*) e classificação visual de relevância das oportunidades.

- 📈 **Painel de Assertividade (`assertividade.php`)**:
  - Acompanhamento da taxa de acerto das oportunidades identificadas em relação aos resultados reais dos jogos finalizados.

- 🏆 **Gerenciador de Ligas Favoritas (`ligas.php`)**:
  - Cadastro, busca e gerenciamento de campeonatos monitorados.
  - Sincronização sob demanda ou automatizada de torneios e partidas.

- 🔄 **Motor de Sincronização & API Ingest (`SyncService.php`, `api.php`)**:
  - Integração com dados estatísticos do Sofascore (`SofascoreApi.php`).
  - Endpoint de Ingestão (`/api.php?action=ingest_match`) protegido por chave de segurança `X-API-Key` (ideal para conectores/coletores remotos ou VPS).

---

## 🛠️ Tecnologias e Infraestrutura

- **PHP 8.3** (Apache, PDO MySQL, mysqli, zip, mod_rewrite)
- **MySQL 8.0** (Com suporte a índices otimizados para busca por torneio, data e status)
- **phpMyAdmin** (Interface visual para gerenciamento do banco de dados)
- **Docker & Docker Compose** (Containerização completa do ambiente)
- **Ngrok** (Integração para exposição pública do ambiente local via `start.sh`)

---

## ⚡ Como Iniciar

### 1. Configurar Variáveis de Ambiente

Copie o arquivo de exemplo `.env.example` para `.env`:

```bash
cp .env.example .env
```

Ajuste as variáveis se necessário (senhas do banco, portas e `INGEST_API_KEY`).

### 2. Iniciar a Aplicação

Você pode utilizar o script utilitário `start.sh` ou os comandos do Docker Compose:

#### Opção A: Via script `start.sh` (com túnel Ngrok)
```bash
./start.sh
```

#### Opção B: Via Docker Compose puro
```bash
docker compose up -d
```

### 3. Acessar os Serviços

- 🌐 **Aplicação Web (PHP)**: [http://localhost:8080](http://localhost:8080)
- 🗄️ **phpMyAdmin (Gerenciador do Banco)**: [http://localhost:8081](http://localhost:8081)
- 🔌 **Banco de Dados MySQL**: `localhost:33061`

---

## 🔒 Segurança e Ingestão de Dados (API Ingest)

O sistema possui um endpoint seguro para recebimento de partidas e estatísticas de coletores externos.

- **Header de Autenticação**: `X-API-Key: <SUA_INGEST_API_KEY>` (configurada no `.env`).
- **Endpoint**: `/api.php?action=ingest_match`

---

## 📁 Estrutura do Projeto

```text
w99score/
├── Dockerfile              # Imagem PHP 8.3 Apache configurada
├── docker-compose.yml      # Definição dos serviços (web, db, phpmyadmin)
├── start.sh                # Script para subir containers e túnel Ngrok
├── stop.sh                 # Script para encerrar containers Docker
├── .env.example            # Template das variáveis de ambiente
├── README.md               # Documentação do projeto
└── src/                    # Código-fonte da aplicação PHP
    ├── index.php           # Página inicial / Dashboard principal
    ├── analise.php         # Análise detalhada pré-jogo
    ├── oportunidades.php   # Buscador estatístico de oportunidades
    ├── assertividade.php   # Dashboard de validação estatística
    ├── ligas.php           # Gerenciador de ligas/campeonatos favoritos
    ├── api.php             # Endpoints REST e ingestão de dados
    ├── db.php              # Conexão PDO e auto-inicialização do schema
    ├── header.php          # Componente de navegação e cabeçalho
    ├── schema.sql          # Estrutura das tabelas MySQL (matches, favorite_leagues)
    ├── SyncService.php     # Serviço de sincronização e parsing de dados
    ├── SofascoreApi.php    # Cliente de comunicação com API Sofascore
    └── OpportunityService.php # Algoritmo de inteligência de oportunidades
```

---

## 🛑 Encerrar os Serviços

Para parar a execução dos containers:

```bash
./stop.sh
```
Ou via docker compose:
```bash
docker compose down
```

Para parar e remover também os volumes de dados do MySQL:
```bash
docker compose down -v
```
