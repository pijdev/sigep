# PLANO ENTERPRISE — PAINEL DE INTERNOS (SIGEP)

Documento completo para construção do módulo Painel de Internos em nível enterprise.
Compatível com arquitetura atual do SIGEP (PHP puro + SPA loadpage).
Base URL: http://localhost/
Caminho: C:\Sites\sigep\modulos\geral\painel_internos



# 1. ARQUITETURA GERAL

Camadas:
- Backend PHP (API interna)
- Banco MySQL (estrutura existente)
- Frontend Vue 3 (CDN)
- Comunicação via fetch/AJAX

Padrão:
- *_view.php
- *_logica.php
- api/*
- assets/*



# 2. ESTRUTURA DE DIRETÓRIOS

modulos/geral/painel_internos/
├── painel_internos_view.php
├── painel_internos_logica.php
├── api/
├── assets/



# 3. ENDPOINTS API (DETALHADOS)

GET action=resumo
- retorno: lista agrupada por galeria/bloco/cela

GET action=cela
- params: galeria, bloco, cela
- retorno: internos da cela

GET action=dossie
- params: id
- retorno: dados completos do interno

GET action=buscar
- params: q
- retorno: lista de internos

Futuro:
- action=itens
- action=movimentacoes
- action=disciplinar



# 4. MODELO DE DADOS UTILIZADO

Tabela principal: internos

Campos relevantes:
- id
- nome
- ipen
- galeria
- bloco
- res
- status

Relacionamentos futuros:
- itens_internos
- movimentacoes
- disciplina



# 5. FRONTEND (VUE)

Componentes:
- App
- GaleriaCard
- BlocoCard
- CelaCard
- ModalCela
- ModalDossie

Estado:
- dados
- loading
- filtros
- busca



# 6. FLUXO DE NAVEGAÇÃO

Painel → Cela → Interno → Dossiê

Regras:
- manter contexto
- não recarregar página
- usar modais empilhados



# 7. BUSCA GLOBAL

Campos:
- ipen
- nome
- nome_social
- apelido

Regras:
- mínimo 2 caracteres
- debounce 300ms
- limite 20 resultados



# 8. FILTROS AVANÇADOS

Modal com:
- galeria
- bloco
- cela
- status
- trabalho
- eletrônicos

Persistência:
localStorage



# 9. PERFORMANCE

- queries agregadas
- cache
- lazy loading
- limitar payload



# 10. UX/UI

- cores por galeria
- grid responsivo
- animações leves
- feedback visual



# 11. TEMPO REAL

- polling 10s
- websocket futuro



# 12. SEGURANÇA (PREPARAÇÃO)

- validar sessão
- controle de acesso
- logs



# 13. EXPANSÃO FUTURA

- heatmap
- alertas
- relatórios



# ANEXO TÉCNICO 1

Detalhamento técnico adicional 1:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 2

Detalhamento técnico adicional 2:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 3

Detalhamento técnico adicional 3:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 4

Detalhamento técnico adicional 4:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 5

Detalhamento técnico adicional 5:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 6

Detalhamento técnico adicional 6:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 7

Detalhamento técnico adicional 7:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 8

Detalhamento técnico adicional 8:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 9

Detalhamento técnico adicional 9:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 10

Detalhamento técnico adicional 10:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 11

Detalhamento técnico adicional 11:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 12

Detalhamento técnico adicional 12:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 13

Detalhamento técnico adicional 13:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 14

Detalhamento técnico adicional 14:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 15

Detalhamento técnico adicional 15:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 16

Detalhamento técnico adicional 16:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 17

Detalhamento técnico adicional 17:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 18

Detalhamento técnico adicional 18:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 19

Detalhamento técnico adicional 19:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 20

Detalhamento técnico adicional 20:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 21

Detalhamento técnico adicional 21:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 22

Detalhamento técnico adicional 22:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 23

Detalhamento técnico adicional 23:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 24

Detalhamento técnico adicional 24:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 25

Detalhamento técnico adicional 25:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 26

Detalhamento técnico adicional 26:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 27

Detalhamento técnico adicional 27:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 28

Detalhamento técnico adicional 28:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 29

Detalhamento técnico adicional 29:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado



# ANEXO TÉCNICO 30

Detalhamento técnico adicional 30:
- regras de negócio
- validações
- fluxos alternativos
- cenários de erro
- otimizações possíveis

Checklist:
- validar entrada
- tratar erro
- logar evento
- retornar JSON padronizado


