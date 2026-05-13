# Ficha de Controle de Manutenção - Formato Paisagem

## Descrição

FICHA DE CONTROLE DE MANUTENÇÃO em formato paisagem para registro diário de atividades, seguindo o padrão oficial do Estado com cabeçalho SAP. Ideal para controle de múltiplas atividades em um único turno.

## Arquivos Criados

- `manutencao_controle_manual.php` - Ficha de Controle para impressão
- `assets/css/manutencao_impressao.css` - Estilos específicos
- `manutencao_view.php` - Botão de acesso (modificado)

## Como Usar

1. Acessar o módulo: **Controle de Manutenções** (`/censura/manutencao/`)
2. Clicar no botão: **"Imprimir Controle Manual"** (amarelo)
3. Documento abre em nova aba: `http://sigep.pij.local/manutencao/controle-manual/`
4. Impressão inicia automaticamente
5. **Preencher manualmente com caneta:**
   - Preencher dados de controle (data, turno, equipe)
   - Registrar cada atividade na tabela
   - Preencher horários, local, descrição, materiais
   - Anotar nome do executor
   - Preencher resumo do dia
   - Obter assinaturas do Monitor e Solicitante
6. Arquivar a ficha preenchida

## URLs Disponíveis

- **Módulo Principal**: `/censura/manutencao/`
- **Ficha de Controle**: `/manutencao/controle-manual/`
- **Acesso Direto**: `http://sigep.pij.local/manutencao/controle-manual/`

## Estrutura do Documento (FICHA DE CONTROLE)

### Cabeçalho Oficial
- Logo Estado e SAP
- Título: "FICHA DE CONTROLE DE MANUTENÇÃO"
- Identificação da unidade prisional

### Dados de Controle
- **DATA**: Data do controle
- **TURNO**: Manhã / Tarde / Noite
- **EQUIPE**: Nome da equipe responsável

### Tabela Principal de Atividades (20 linhas)
- **ATIVIDADE**: Tipo de serviço realizado
- **LOCAL**: Onde foi executado
- **DATA/HORA INÍCIO**: Registro do início
- **DESCRIÇÃO**: Detalhes do serviço
- **MATERIAL UTILIZADO**: Insumos usados
- **EXECUTOR**: Quem realizou o serviço
- **DATA/HORA FIM**: Registro do término

### Resumo do Dia
- **TOTAL DE ATIVIDADES**: Quantidade de serviços
- **CONCLUÍDAS**: Serviços finalizados
- **PENDENTES**: Serviços em andamento
- **OBSERVAÇÕES**: Notas importantes

### Assinaturas
- **MONITOR DA MANUTENÇÃO**: Supervisor do turno
- **SOLICITANTE DA MANUTENÇÃO**: Quem solicitou os serviços
- Data de cada assinatura

## Características Técnicas

- **Formato**: A4 paisagem
- **Fonte**: Times New Roman 10px
- **Impressão**: Automática ao abrir
- **Layout**: Oficial Estado/SAP
- **Preenchimento**: Manual com caneta/lápis
- **Capacidade**: 20 atividades por ficha
- **Altura das linhas**: 10px para máximo aproveitamento
- **Margens**: 0.5cm para máximo aproveitamento
- **Bordas**: Finas para economizar espaço

## Vantagens

✅ **Formato paisagem** otimizado para tabelas
✅ **Documento oficial** com cabeçalho padrão
✅ **20 linhas** com altura otimizada
✅ **Controle completo** com horários
✅ **Assinaturas duplas** (Monitor + Solicitante)
✅ **Resumo diário** para acompanhamento
✅ **Profissionalismo** na documentação
✅ **Preenchimento manual** com caneta/lápis
✅ **Máximo aproveitamento** da página A4

## Fluxo de Trabalho

1. **Início do turno**: Imprimir ficha de controle
2. **Durante o dia**: Registrar cada atividade conforme execução
3. **Final do turno**: Preencher resumo e observações
4. **Assinaturas**: Obter assinaturas do Monitor e Solicitante
5. **Arquivamento**: Guardar ficha preenchida

## Tipos de Atividades

### Predial
- Portas, janelas, paredes
- Pisos, tetos, pintura
- Estruturas em geral

### Elétrico
- Lâmpadas, tomadas
- Disjuntores, fiação
- Instalações elétricas

### Eletrônico
- TV, rádio, ventilador
- Chuveiro, outros aparelhos
- Equipamentos eletrônicos

### Hidráulico
- Torneiras, vasos
- Chuveiros, encanamentos
- Sistemas de água

## Suporte

Em caso de dúvidas ou problemas, contatar a equipe de TI da unidade.

## Tipos de Serviço

### Predial
- Portas, janelas, paredes
- Pisos, tetos, pintura
- Estruturas em geral

### Elétrico
- Lâmpadas, tomadas
- Disjuntores, fiação
- Instalações elétricas

### Eletrônico
- TV, rádio, ventilador
- Chuveiro, outros aparelhos
- Conforme sistema existente

### Hidráulico
- Torneiras, vasos
- Chuveiros, encanamentos
- Sistemas de água

## Fluxo de Trabalho

1. **Manhã**: Imprimir ficha de controle
2. **Durante o dia**: Preencher serviços executados
3. **Final do turno**: Entregar ficha preenchida
4. **Administração**: Digitalizar/documentar
5. **Futuro**: Importação automática para o sistema

## Características Técnicas

- **Formato**: A4 retrato
- **Fonte**: Times New Roman 12px
- **Impressão**: Automática ao abrir
- **Páginas**: 2 páginas (30 serviços no total)
- **CSS**: Otimizado para impressão

## Vantagens

✅ **Zero dependência tecnológica** no campo
✅ **Padrão único** para registro
✅ **Documentação oficial** com cabeçalho SAP
✅ **Flexibilidade** para todos os tipos de serviço
✅ **Facilidade de uso** para equipe técnica
✅ **Preparado para digitalização futura**

## Próximos Passos

- Sistema de OCR para leitura automática
- Importação via foto do celular
- Dashboard comparativo (manual vs digital)
- Integração completa com módulo existente

## Suporte

Em caso de dúvidas ou problemas, contatar a equipe de TI da unidade.
