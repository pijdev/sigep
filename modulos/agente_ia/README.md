# 🤖 AI Agent Tools para Consulta de Internos

## Resumo

Foi criado um sistema de **tools** (ferramentas) que permite à IA do SIGEP fazer consultas automáticas ao banco de dados de internos, respondendo perguntas dos usuários de forma inteligente.

## Arquivos Criados/Modificados

### 1. `modulos/agente_ia/InternosTools.php` (NEW)
Classe que implementa todas as ferramentas de consulta. Contém métodos para:
- `buscar_por_ipen()` - Buscar um interno pelo número IPEN
- `buscar_por_nome()` - Buscar interno por nome ou nome social
- `internos_cela()` - Listar todos os internos em uma cela específica
- `contar_cela()` - Contar quantos internos estão em uma cela
- `internos_por_situacao()` - Listar internos por situação (Saída Temporária, Portaria, etc)
- `localizar_interno()` - Localizar onde está um interno (sua localização atual)
- `listar_situacoes()` - Listar todas as situações possíveis no sistema

### 2. `modulos/agente_ia/IAController.php` (MODIFIED)
Modificado para:
- Conectar ao banco de dados
- Carregar as tools disponíveis
- Enviar contexto das tools para o modelo de IA
- Processar chamadas de tools na resposta da IA
- Executar as tools e devolver resultados

## Como Funciona

### Fluxo de Execução

```
1. Usuário digita pergunta no chat
   ↓
2. IAController.php recebe a mensagem
   ↓
3. Contexto das tools é adicionado ao prompt para o Ollama
   ↓
4. Ollama responde usando o padrão [TOOL: nome|param=valor]
   ↓
5. IAController detecta chamadas de tools na resposta
   ↓
6. Executa a tool (InternosTools)
   ↓
7. Substitui [TOOL:...] com o resultado
   ↓
8. Resposta final é retornada ao usuário
```

### Formato das Ferramentas

A IA reconhece tools pelo padrão:
```
[TOOL: nome_ferramenta|parametro1=valor1|parametro2=valor2]
```

### Exemplos de Uso

**Pergunta do usuário:** "Onde está o interno 787056?"
```
IA responde: [TOOL: localizar_interno|ipen=787056]
Resultado: **João Silva** (IPEN 787056): Na cela S-A-9 (M)
```

**Pergunta do usuário:** "Quantos internos na cela AA-1?"
```
IA interpreta como: Galeria S, Bloco A, Res 1
IA responde: [TOOL: contar_cela|galeria=S|bloco=A|res=1]
Resultado: A cela S-A-1 contém **8** interno(s).
```

**Pergunta do usuário:** "Liste os internos em saída temporária"
```
IA responde: [TOOL: internos_por_situacao|situacao=SAÍDA TEMPORÁRIA]
Resultado: Lista de internos com situação de saída temporária
```

**Pergunta do usuário:** "Liste os internos na portaria"
```
IA responde: [TOOL: internos_por_situacao|situacao=PORTARIA]
Resultado: Lista de internos em portaria
```

## Exemplos de Perguntas que a IA Pode Responder

✅ "Onde está o interno 787056?"
✅ "Qual é a cela do João Silva?"
✅ "Quantos internos estão em saída temporária?"
✅ "Liste os internos na cela DB-10"
✅ "Quem está na cela S-A-1?"
✅ "Internos em portaria"
✅ "Localize o interno de nome social Maria"

## Estrutura de Localização

Os internos são organizados da seguinte forma no banco:

```
Galeria + Bloco + Res = Localização
Exemplo: S + A + 9 = Cela S-A-9

Onde:
- Galeria: S (Semi-aberto), 01, 02, C, D, etc
- Bloco: A, B, C, D, E, T, etc
- Res: Número da cela (1-40 típico)
```

## Situações Possíveis

O sistema reconhece as seguintes situações:
- **SAÍDA TEMPORÁRIA** - Em saída temporária/livramento condicional
- **PORTARIA** - Na portaria da unidade
- **RECOLHIDO** - Na cela
- **TRABALHO INTERNO** - Em trabalho dentro da unidade
- **TRABALHO EXTERNO** - Em trabalho externo
- **ESTUDO** - Em atividade de estudo
- Outras situações específicas do sistema

## Logs

Todas as chamadas de tools são registradas em `debug_ollama.log`:
- Ferramentas chamadas
- Parâmetros utilizados
- Erros encontrados

Exemplo:
```
Executando tool: localizar_interno com params: {"ipen":"787056"}
```

## Próximos Passos (Sugestões)

1. **Expandir tools:** Adicionar mais consultas úteis
   - Internos por gênero/LGBT
   - Internos por setor/regalia
   - Estatísticas gerais
   - Histórico de movimentações

2. **Melhorar a IA:**
   - Adicionar histórico de conversa
   - Permitir follow-ups ("Mostre mais")
   - Tratamento de erros melhorado

3. **Segurança:**
   - Validar permissões do usuário
   - Sanitizar entradas
   - Auditoria de consultas

## Teste Rápido

Para testar uma ferramenta diretamente:

```php
require 'modulos/agente_ia/InternosTools.php';
$pdo = Database::getConnection();
$tools = new \AgentIA\InternosTools($pdo);

// Buscar um interno
echo $tools->buscar_por_ipen('787056');

// Contar cela
echo $tools->contar_cela('S', 'A', '1');

// Listar situações
echo $tools->listar_situacoes();
```

---
**Data de Criação:** 2025-01-08  
**Versão:** 1.0
