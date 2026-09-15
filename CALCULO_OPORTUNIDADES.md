# 📊 Guia de Cálculos do Algoritmo de Oportunidades - w99score

Este documento detalha todos os cálculos, fórmulas, pesos e regras de negócio utilizados no sistema **w99score** para identificar, filtrar e classificar as **Melhores Oportunidades de Apostas**.

> 📌 **IMPORTANTE:** Este documento deve ser atualizado sempre que houver modificações nas fórmulas, novos mercados adicionados, alterações de pesos de recência/H2H ou regras de amostragem.

---

## 1. Estrutura de Amostragem & Filtros de Dados

### 1.1 Separação por Mando de Campo
Para maximizar a precisão estatística, o sistema compara as estatísticas específicas da condição do jogo:
- **Mandante**: Dados de partidas jogadas em casa (`venue = 'home'`).
- **Visitante**: Dados de partidas jogadas fora (`venue = 'away'`).
- **Confronto Direto (H2H)**: Histórico recente entre as duas equipes específicas.

### 1.2 Tratamento de Amostragem Reduzida (< 5 jogos)
- Se o mandante jogou **menos de 5 partidas em casa** OR o visitante jogou **menos de 5 partidas fora**:
  - A flag `is_low_sample` é definida como `true`.
  - É exibido na interface um **badge de aviso** (`< 5 Jogos`) e um **alerta em amarelo/âmbar**:
    > *⚠️ Atenção: Oportunidade calculada com menos de 5 jogos (X do mandante em casa / Y do visitante fora). Os dados possuem maior margem de variação.*

### 1.3 Proteção contra Outliers
- Médias discrepantes são suavizadas através de filtros de consistência para evitar que uma goleada atípica ou um jogo com número anômalo de cantos/cartões distorça a probabilidade calculada.

---

## 2. Função Base de Análise de Condição (`analyzeConditionOnMatches`)

Todas as taxas de sucesso e tendências de cada mercado são calculadas pela função `analyzeConditionOnMatches($matches, $condition)`.

### 2.1 Taxas Calculadas
1. **Taxa Geral (`pct`)**:
   $$\text{pct} = \text{round}\left( \frac{\text{jogos\_que\_cumpriram}}{\text{total\_de\_jogos}} \times 100 \right)$$

2. **Taxa Recente (`recent_pct`)**:
   - Analisa os **últimos 5 jogos** do histórico.
   $$\text{recent\_pct} = \text{round}\left( \frac{\text{jogos\_cumpridos\_ultimos\_5}}{\text{min}(total\_jogos, 5)} \times 100 \right)$$

3. **Recência Ponderada (`weighted_pct`)**:
   - Dá maior peso para o momento atual da equipe:
   $$\text{weighted\_pct} = \begin{cases} \text{round}((\text{recent\_pct} \times 0.65) + (\text{older\_pct} \times 0.35)), & \text{se } \text{total\_jogos} > 5 \\ \text{recent\_pct}, & \text{se } \text{total\_jogos} \le 5 \end{cases}$$

4. **Sequência Atual (`streak`)**:
   - Conta o número consecutivo de partidas mais recentes em que a condição foi **verdadeira**. Zera no primeiro `false`.

5. **Tendência Recente (`recent_form`)**:
   - Vetor booleano `[bool, bool, bool, bool, bool]` ordenado cronologicamente representando os últimos 5 jogos (renderizado como pontos verdes/vermelhos na tela).

---

## 3. Fórmulas de Confiança por Mercado

Cada mercado calcula um valor de **Confiança (`confidence`)** entre **30% e 98%** (com exceção do mercado de Favorito, que varia entre 45% e 96%).

---

### 3.1 Ambos Marcam (`ambos_marcam`)
- **Premissas**:
  - `hScored`: Mandante marcou em casa.
  - `hConceded`: Mandante sofreu gol em casa.
  - `aScored`: Visitante marcou fora.
  - `aConceded`: Visitante sofreu gol fora.
- **Cálculo da Probabilidade**:
  $$\text{probHomeScores} = (\text{hScored.weighted\_pct} \times 0.6) + (\text{aConceded.weighted\_pct} \times 0.4)$$
  $$\text{probAwayScores} = (\text{aScored.weighted\_pct} \times 0.6) + (\text{hConceded.weighted\_pct} \times 0.4)$$
  $$\text{bttsConfidence} = \text{round}\left( \frac{\text{probHomeScores} + \text{probAwayScores}}{2} \right)$$
- **Ajuste de Confronto Direto (H2H)**:
  - Se houver histórico H2H entre as equipes:
    $$\text{bttsConfidence} = \text{round}((\text{bttsConfidence} \times 0.75) + (\text{h2hBttsPct} \times 0.25))$$
- **Linha / Tag**: `Ambos Marcam: SIM`

---

### 3.2 Escanteios (Cantos HT, ST, FT)

#### Escanteios 1º Tempo (`cantos_ht`)
- **Média Esperada**: $\text{expHtCorners} = \text{round}\left(\frac{\text{mídia\_ht\_mandante} + \text{mídia\_ht\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 5$ cantos no 1ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expHtCorners}}{5.2} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expHtCorners} \ge 5.2 \implies$ **Mais de 4.5 Cantos HT**
  - Caso contrário $\implies$ **Mais de 3.5 Cantos HT**

#### Escanteios 2º Tempo (`cantos_st`)
- **Média Esperada**: $\text{expStCorners} = \text{round}\left(\frac{\text{mídia\_st\_mandante} + \text{mídia\_st\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 6$ cantos no 2ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expStCorners}}{5.8} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expStCorners} \ge 5.5 \implies$ **Mais de 5.5 Cantos 2ºT**
  - Caso contrário $\implies$ **Mais de 4.5 Cantos 2ºT**

#### Escanteios Tempo Integral (`cantos_ft`)
- **Média Esperada**: $\text{expFtCorners} = \text{round}\left(\frac{\text{mídia\_ft\_mandante} + \text{mídia\_ft\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 10$ cantos no jogo.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expFtCorners}}{10.5} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expFtCorners} \ge 10.5 \implies$ **Mais de 10.5 Escanteios**
  - Caso contrário $\implies$ **Mais de 9.5 Escanteios**

---

### 3.3 Gols (Gols HT, ST, FT)

#### Gols 1º Tempo (`gols_ht`)
- **Média Esperada**: $\text{expHtGoals} = \text{round}\left(\frac{\text{mídia\_gols\_ht\_mandante} + \text{mídia\_gols\_ht\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 1$ gol no 1ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.75 + \left(\min\left(100, \frac{\text{expHtGoals}}{1.3} \times 80\right)\right) \times 0.25 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expHtGoals} \ge 1.4 \implies$ **Mais de 1.5 Gols no 1ºT**
  - Caso contrário $\implies$ **Mais de 0.5 Gols no 1ºT**

#### Gols 2º Tempo (`gols_st`)
- **Média Esperada**: $\text{expStGoals} = \text{round}\left(\frac{\text{mídia\_gols\_st\_mandante} + \text{mídia\_gols\_st\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 1$ gol no 2ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.75 + \left(\min\left(100, \frac{\text{expStGoals}}{1.5} \times 80\right)\right) \times 0.25 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expStGoals} \ge 1.6 \implies$ **Mais de 1.5 Gols no 2ºT**
  - Caso contrário $\implies$ **Mais de 0.5 Gols no 2ºT**

#### Gols Tempo Integral (`gols_ft`)
- **Média Esperada**: $\text{expFtGoals} = \text{round}\left(\frac{\text{mídia\_gols\_ft\_mandante} + \text{mídia\_gols\_ft\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 3$ gols na partida (Over 2.5).
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expFtGoals}}{2.7} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expFtGoals} \ge 2.6 \implies$ **Mais de 2.5 Gols FT**
  - Caso contrário $\implies$ **Mais de 1.5 Gols FT**

---

### 3.4 Cartões Amarelos (HT, ST, FT)

#### Cartões 1º Tempo (`cartoes_ht`)
- **Média Esperada**: $\text{expHtCards} = \text{round}\left(\frac{\text{mídia\_cartões\_ht\_mandante} + \text{mídia\_cartões\_ht\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 2$ cartões no 1ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expHtCards}}{2.0} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expHtCards} \ge 2.0 \implies$ **Mais de 2.5 Cartões HT**
  - Se $1.2 \le \text{expHtCards} < 2.0 \implies$ **Mais de 1.5 Cartões HT**
  - Caso contrário $\implies$ **Mais de 0.5 Cartões HT**

#### Cartões 2º Tempo (`cartoes_st`)
- **Média Esperada**: $\text{expStCards} = \text{round}\left(\frac{\text{mídia\_cartões\_st\_mandante} + \text{mídia\_cartões\_st\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 3$ cartões no 2ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expStCards}}{2.8} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expStCards} \ge 2.6 \implies$ **Mais de 2.5 Cartões 2ºT**
  - Caso contrário $\implies$ **Mais de 1.5 Cartões 2ºT**

#### Cartões Tempo Integral (`cartoes_ft`)
- **Média Esperada**: $\text{expFtCards} = \text{round}\left(\frac{\text{mídia\_cartões\_ft\_mandante} + \text{mídia\_cartões\_ft\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 5$ cartões no jogo.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expFtCards}}{5.2} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expFtCards} \ge 5.5 \implies$ **Mais de 5.5 Cartões**
  - Se $4.3 \le \text{expFtCards} < 5.5 \implies$ **Mais de 4.5 Cartões**
  - Caso contrário $\implies$ **Mais de 3.5 Cartões**

---

### 3.5 Finalizações (HT, ST, FT)

#### Finalizações 1º Tempo (`finalizacoes_ht`)
- **Média Esperada**: $\text{expHtShots} = \text{round}\left(\frac{\text{mídia\_chutes\_ht\_mandante} + \text{mídia\_chutes\_ht\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 10$ finalizações no 1ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expHtShots}}{11.5} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expHtShots} \ge 11.5 \implies$ **Mais de 11.5 Finalizações HT**
  - Caso contrário $\implies$ **Mais de 9.5 Finalizações HT**

#### Finalizações 2º Tempo (`finalizacoes_st`)
- **Média Esperada**: $\text{expStShots} = \text{round}\left(\frac{\text{mídia\_chutes\_st\_mandante} + \text{mídia\_chutes\_st\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 11$ finalizações no 2ºT.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expStShots}}{12.5} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expStShots} \ge 12.5 \implies$ **Mais de 12.5 Finalizações 2ºT**
  - Caso contrário $\implies$ **Mais de 10.5 Finalizações 2ºT**

#### Finalizações Tempo Integral (`finalizacoes_ft`)
- **Média Esperada**: $\text{expFtShots} = \text{round}\left(\frac{\text{mídia\_chutes\_ft\_mandante} + \text{mídia\_chutes\_ft\_visitante}}{2}, 2\right)$
- **Condição Aprovada**: $\ge 23$ finalizações na partida.
- **Fórmula de Confiança**:
  $$\text{confidence} = \text{round}\left( \left(\frac{\text{hWeightedPct} + \text{aWeightedPct}}{2}\right) \times 0.7 + \left(\min\left(100, \frac{\text{expFtShots}}{24.0} \times 80\right)\right) \times 0.3 \right)$$
- **Linhas Sugeridas**:
  - Se $\text{expFtShots} \ge 24.0 \implies$ **Mais de 23.5 Finalizações**
  - Caso contrário $\implies$ **Mais de 20.5 Finalizações**

---

### 3.6 Favorito Vence (`favorito_vence`)
- **Cálculo da Força (`Power Index`)**:
  $$\text{hPower} = (\text{hWinPct} \times 0.6) + (\text{aLossPct} \times 0.4) + ((\text{hSaldoGolsAvg}) \times 10)$$
  $$\text{aPower} = (\text{aWinPct} \times 0.6) + (\text{hLossPct} \times 0.4) + ((\text{aSaldoGolsAvg}) \times 10)$$
- **Identificação do Favorito**:
  - Se $\text{hPower} \ge \text{aPower} \implies$ **Mandante é o Favorito**.
  - Caso contrário $\implies$ **Visitante é o Favorito**.
- **Cálculo da Confiança do Favorito**:
  $$\text{favConfidence} = \text{round}((\text{favWinPct} \times 0.6) + (\text{underdogLossPct} \times 0.4))$$
  - Clamped entre **45% e 96%**.

---

## 4. Classificação dos Níveis de Confiança (`rating`)

O nível exibido no card do usuário é determinado pela pontuação final de confiança:

| Confiança (%) | Nível Exibido (`rating`) |
| :--- | :--- |
| $\ge 85\%$ | **Excelente (Oportunidade de Ouro)** |
| $75\% \text{ a } 84\%$ | **Muito Alta** |
| $65\% \text{ a } 74\%$ | **Alta** |
| $< 65\%$ | **Moderada** |

---

## 5. Badges de Destaque (`streak_badge`)

O card pode exibir badges promocionais/estatísticas:
1. **Badges de Sequência (`streak`)**:
   - Se `streak >= 3`: `🔥 Sequência de N jogos...` (ex: *🔥 5+ cantos no 1ºT em 4 jogos seguidos*).
2. **Badges de Consistência**:
   - Se não houver streak $\ge 3$ e `consistency_pct >= 75%` (ou $\ge 80\%$ em gols): `🎯 Consistência N%`.

---

## 6. Lista de Tarefas / O que Falta Implementar (Roadmap Proposto)

### 🟢 Já Implementado
- [x] Pesagem com recência ponderada (65% últimos 5 jogos / 35% histórico antigo).
- [x] Filtro de mando de campo (Mandante em Casa / Visitante Fora).
- [x] Ajuste estatístico H2H no mercado de Ambos Marcam.
- [x] Detecção e aviso em tela de amostragem reduzida (< 5 jogos).
- [x] Proteção contra atuações discrepantes (Filtro de Outliers).
- [x] Auditoria / Backtest automatizado de assertividade (GREEN / RED) em partidas finalizadas.
- [x] Suporte a 14 mercados distintos (Gols, Cantos, Cartões e Finalizações por tempo HT/ST/FT + BTTS e Favorito).

### 🟡 O que Pode Ser Implementado Futuramente
- [ ] **Integração de Odds de Casas de Aposta**: Calcular o Value Bet (Expected Value $EV > 0$) comparando a probabilidade do algoritmo contra as Odds das casas.
- [ ] **Peso Dinâmico por Força do Campeonato/Liga**: Ajustar peso do H2H ou amostragem de acordo com a competitividade da liga.
- [ ] **Desfalques / Escalacoes Confirmadas**: Impacto de desfalques de artilheiros/zagueiros na confiança final.
- [ ] **Fator Clima / Condições de Campo**: Ajuste automático em volume de finalizações e cantos em dias de chuva pesada.
