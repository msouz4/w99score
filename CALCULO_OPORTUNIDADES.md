# 🎯 Como o Percentual das Oportunidades é Calculado (Estrutura da Lógica)

Este documento descreve de forma direta e objetiva **como o percentual de confiança das oportunidades é construído passo a passo** no **w99score**. 

> ⚠️ **REGRA DE MANUTENÇÃO**: Sempre que implementar ou alterar qualquer regra, peso, filtro ou cálculo que modifique a porcentagem gerada pelo algoritmo, **este documento deve ser atualizado**.

---

## 🧭 Passo a Passo do Cálculo do Percentual

### 1. Separação por Mando de Campo (Casa vs. Fora)
- O cálculo não junta todos os jogos de forma genérica.
- **Mandante**: Analisa exclusivamente jogos em que o time jogou em **casa**.
- **Visitante**: Analisa exclusivamente jogos em que o time jogou **fora**.
- **Aviso de Amostragem (< 5 jogos)**: Se o mandante tiver menos de 5 jogos em casa ou o visitante tiver menos de 5 jogos fora, um aviso de amostragem reduzida é exibido no card.

### 2. Filtro de Outliers (Tratamento de Discrepâncias)
- Aplica um filtro de consistência sobre as médias para suavizar atuações atípicas.
- Impede que um jogo fora da curva (ex: um jogo isolado com 18 escanteios ou goleada discrepante) distorça a média normal e infle o percentual.

### 3. Ponderação de Recência (Pesos 65% / 35%)
Ao avaliar se uma condição estatística foi cumprida nos jogos da equipe:
- **65% de Peso**: Desempenho recente nos **últimos 5 jogos**.
- **35% de Peso**: Desempenho no histórico **mais antigo** da equipe.
- *(Nota: Se a equipe possuir 5 ou menos jogos no histórico, os últimos jogos recebem 100% do peso)*.

### 4. Cruzamento entre Força Ofensiva e Fragilidade Defensiva (Feitos + Cedidos)
Para evitar olhar apenas um lado da moeda ou tirar médias brutas de jogos anteriores:
- **Chutes/Cantos/Gols Esperados do Mandante**: `(Mandante Feitos em Casa + Visitante Cedidos Fora) / 2`
- **Chutes/Cantos/Gols Esperados do Visitante**: `(Visitante Feitos Fora + Mandante Cedidos em Casa) / 2`
- **Projeção Total da Partida**: Soma do Esperado do Mandante + Esperado do Visitante.
- **Peso no Percentual**: 
  - **60% de Peso**: Capacidade do time de produzir/marcar a estatística no mando atual.
  - **40% de Peso**: Fragilidade defensiva do adversário (concessão de gols/cantos/chutes no mando atual).

### 5. Ajuste Estatístico por Confronto Direto (H2H)
- No mercado de **Ambos Marcam**:
  - A probabilidade base das equipes recebe **75% de peso**.
  - A taxa histórica de Ambos Marcam nos **confrontos diretos (H2H)** recebe **25% de peso**.

### 6. Ponderação por Volume Esperado (Benchmark da Linha)
Nos mercados de estatísticas acumuladas (Gols, Cantos, Cartões, Finalizações):
- **70% a 75% da nota**: Vem da taxa percentual ponderada de vezes em que a linha foi batida.
- **25% a 30% da nota**: Vem da relação entre a média combinada esperada e a linha benchmark do mercado.

### 7. Limites de Confiança (Clamping de Segurança)
- **Mercados Gerais** (Gols, Cantos, Cartões, Chutes, BTTS): Confiança travada entre **30% e 98%**.
- **Mercado de Favorito** (Moneyline): Confiança travada entre **45% e 96%**.
- Nenhuma oportunidade exibe 0% ou 100% para manter a coerência probabilística.

### 8. Classificação dos Níveis de Confiança
A porcentagem final define o nível e badge da oportunidade:
- **≥ 85%**: *Excelente (Oportunidade de Ouro)*
- **75% a 84%**: *Muito Alta*
- **65% a 74%**: *Alta*
- **< 65%**: *Moderada*

---

## 📌 Histórico de Ajustes e O Que Falta Implementar

### 🟢 O que já está aplicando percentual:
- [x] Mando de campo separado (Casa vs Fora).
- [x] Pesagem 65% recente (últimos 5 jogos) / 35% antigo.
- [x] Proteção contra outliers (tratamento de discrepâncias nas médias).
- [x] Cruzamento de ataque (60%) vs concessão defensiva (40%).
- [x] Peso de 25% para H2H no Ambos Marcam.
- [x] Trava de segurança (30% a 98%).
- [x] Aviso visual para amostragem < 5 jogos.

### 🟡 Ideias para alterar/adicionar no percentual futuramente:
- [ ] **Comparativo EV (Value Bet)**: Alterar o score quando a Odd da casa for desproporcional à probabilidade.
- [ ] **Peso por Força da Liga**: Ajustar percentual se a liga tiver média de gols/cantos muito acima/abaixo do padrão global.
- [ ] **Fator Mandante/Visitante em Sequência (W-L-D)**: Adicionar peso extra para times em sequência de vitórias/derrotas.
