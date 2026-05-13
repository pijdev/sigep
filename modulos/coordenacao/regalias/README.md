# Painel Regalias - Módulo SIGEP

## Descrição
Módulo completo para visualização e gerenciamento de regalias do sistema penitenciário.

## Estrutura
```
modulos/coordenacao/regalias/
|-- regalias_view.php          # Interface principal (AdminLTE 3)
|-- regalias_logica.php        # Controller PHP com lógica de negócio
|-- assets/
    |-- css/
    |   `-- regalias.css       # Estilos customizados
    `-- js/
        `-- regalias.js        # Funcionalidades AJAX e UI
```

## Funcionalidades

### Interface Principal
- **Cards Resumo**: Total de regalias, Alimentação, Fundo Rotativo, Corte de Cabelo
- **Filtros Avançados**: Por galeria, setor, busca por nome/IPEN
- **Tabela Dinâmica**: Lista completa com detalhes de cada regalia
- **Gráficos Interativos**: Distribuição por setor e galeria
- **Modal de Detalhes**: Informações completas de cada regalia

### Dados Apresentados
- **IPEN**: Identificação única do interno
- **Nome**: Nome completo do interno
- **Galeria/Bloco**: Localização física
- **Setor**: Tipo de regalia (Alimentação, Fundo Rotativo, etc.)
- **Função**: Descrição detalhada da atividade
- **Dias de Trabalho**: Cronograma semanal
- **Status**: Situação atual (Ativo)

### Tipos de Regalias
1. **Alimentação (37)**: Entrega de marmitas (REMIÇÃO)
2. **Fundo Rotativo (17)**: Trabalho industrial remunerado
3. **Corte de Cabelo (10)**: Barbeiro (REMIÇÃO)
4. **Conveniados (7)**: Trabalho externo em hospitais
5. **Outros (40)**: Manutenção, limpeza, obras, etc.

## Tecnologias
- **Backend**: PHP 8+ com PDO prepared statements
- **Frontend**: AdminLTE 3 + Bootstrap 4 + jQuery 3.6
- **Gráficos**: Chart.js 4.5.1
- **Tabelas**: DataTables com paginação
- **Notificações**: Toastr 2.1.4

## Segurança
- Verificação de sessão e permissões
- Prepared statements contra SQL injection
- Validação de entrada de dados
- Controle de acesso por setor (perm_coord)

## Performance
- AJAX para carregamento dinâmico
- Cache de dados no frontend
- Paginação para grandes volumes
- Auto-refresh opcional (30 segundos)

## Acesso
- **Menu**: Coordenação > Painel Regalias
- **Permissão**: `perm_coord` ou administrador
- **URL**: `/modulos/coordenacao/regalias/regalias_view.php`

## Funcionalidades Futuras
- [ ] Exportação para Excel/CSV
- [ ] Edição de regalias
- [ ] Relatórios personalizados
- [ ] Impressão de listas
- [ ] Integração com sistema de notificações

## Manutenção
- Seguir padrões SIGEP de código
- Manter compatibilidade com SPA
- Atualizar documentação em mudanças
- Testar em diferentes navegadores