---
name: sigep-base-conhecimento
description: Central knowledge base for SIGEP project with quick access to architecture, patterns, documentation, and best practices
---

# SIGEP Base de Conhecimento Skill

## Purpose

This skill serves as a central knowledge base for the SIGEP project, providing quick access to architecture patterns, coding standards, documentation, and best practices for development teams.

## Knowledge Categories

### 1. Architecture Overview

- System architecture and components
- MVC pattern implementation
- Database design principles
- Security architecture
- Integration patterns

### 2. Development Standards

- Coding conventions
- File organization
- Naming patterns
- Documentation standards
- Testing practices

### 3. Database Knowledge

- Schema design
- Query patterns
- Migration guidelines
- Performance optimization
- Security practices

### 4. Frontend Patterns

- AdminLTE 3 components
- JavaScript patterns
- CSS organization
- Responsive design
- UI/UX guidelines

### 5. Security Guidelines

- Authentication patterns
- Permission systems
- Data protection
- Input validation
- Security best practices

## Quick Reference

### SIGEP Architecture

```
SIGEP/
├── auth/                    # Autenticação e sessão
├── conf/                     # Configurações
├── includes/                 # Bibliotecas compartilhadas
├── modulos/                  # Módulos do sistema (MVC)
│   ├── censura/           # Módulo de censura
│   ├── eclusa/            # Módulo de escolta
│   ├── coordenacao/       # Módulo de coordenação
│   └── ti/                # Módulo de TI
├── paginas/                  # Páginas antigas (legado)
├── assets/                   # CSS, JS, imagens
├── scripts/                  # Scripts de manutenção
└── temp/                     # Arquivos temporários
```

### MVC Pattern

```
modulos/[setor]/[modulo]/
├── [modulo]_view.php      # Interface (View)
├── [modulo]_logica.php    # Controller (Lógica)
├── assets/
│   ├── css/[modulo].css   # Estilos customizados
│   └── js/[modulo].js     # JavaScript + AJAX
└── README.md               # Documentação
```

### Database Standards

- **Charset**: utf8mb4
- **Timezone**: America/Sao_Paulo
- **Engine**: InnoDB
- **Collation**: utf8mb4_unicode_ci

### Frontend Stack

- **Framework**: AdminLTE 3 (Bootstrap 4)
- **JavaScript**: jQuery 3.6.0
- **Icons**: FontAwesome 7.2.0
- **Charts**: Chart.js 4.5.1
- **Select2**: 4.1.0-rc.0
- **Notifications**: Toastr 2.1.4

## Development Patterns

### Standard Controller Pattern

```php
<?php
// [Nome Módulo] - Controller SIGEP
session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Função para retornar erro JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissões
if (!($_SESSION['user_admin'] || ($_SESSION['perm_setor'] ?? 0))) {
    returnError('Sem permissão', 403);
}

// Configurar conexão PDO
try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    returnError('Erro na conexão: ' . $e->getMessage(), 500);
}
?>
```

### Standard View Pattern

```php
<?php
require_once __DIR__ . '/[modulo]_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Título do Módulo</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item active">Módulo</li>
        </ol>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="stats-total">0</h3>
                        <p>Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-database"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cog mr-2"></i>
                            Título do Card
                        </h3>
                    </div>
                    <div class="card-body">
                        <!-- Content here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="modulos/[setor]/[modulo]/assets/js/[modulo].js"></script>
```

### Standard JavaScript Pattern

```javascript
/**
 * SIGEP [Nome Módulo] - JavaScript Principal
 * Funcionalidades AJAX + UI AdminLTE
 */

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.[modulo]Loaded === 'undefined') {
    window.[modulo]Loaded = true;

// Variáveis globais
var currentData = {
    itens: []
};

// Inicialização
$(document).ready(function() {
    carregarDados();
    inicializarComponentes();
    setInterval(autoRefresh, 30000);
});

// Carregar dados via AJAX
function carregarDados() {
    $.ajax({
        url: 'modulos/[setor]/[modulo]/[modulo]_logica.php',
        method: 'POST',
        data: { action: 'listar' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                currentData.itens = response.data;
                atualizarInterface();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar dados:', error);
            mostrarNotificacao('Erro ao carregar dados', 'error');
        }
    });
}

// Utilitários
function mostrarNotificacao(mensagem, tipo = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensagem);
    } else {
        console.log(`[${tipo.toUpperCase()}] ${mensagem}`);
    }
}

function atualizarInterface() {
    $('#stats-total').text(currentData.itens.length);
}

function autoRefresh() {
    carregarDados();
}

// Event listeners
$(document).on('click', '#btn-salvar', function() {
    salvarItem();
});

// Fechar bloco de proteção
} // fim do if (typeof window.[modulo]Loaded === 'undefined')
```

## Database Patterns

### Standard Table Structure

```sql
CREATE TABLE `nome_tabela` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Standard CRUD Operations

```php
// Create
function criarItem($pdo, $dados) {
    $sql = "INSERT INTO tabela (nome, descricao, created_by) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$dados['nome'], $dados['descricao'], $_SESSION['user_id']]);
    return $pdo->lastInsertId();
}

// Read
function listarItens($pdo, $conditions = []) {
    $sql = "SELECT * FROM tabela";
    $params = [];

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', array_keys($conditions));
        $params = array_values($conditions);
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Update
function atualizarItem($pdo, $id, $dados) {
    $sql = "UPDATE tabela SET nome = ?, descricao = ?, updated_by = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$dados['nome'], $dados['descricao'], $_SESSION['user_id'], $id]);
}

// Delete
function deletarItem($pdo, $id) {
    $sql = "DELETE FROM tabela WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}
```

## Security Patterns

### Authentication Check

```php
function requireAuthentication() {
    session_start();

    if (!isset($_SESSION['user_id'])) {
        returnError('Usuário não autenticado', 401);
    }

    // Check session timeout
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_destroy();
        returnError('Sessão expirada', 401);
    }

    $_SESSION['last_activity'] = time();
}
```

### Permission Check

```php
function checkPermission($permission) {
    if (!hasPermission($permission)) {
        returnError('Sem permissão', 403);
    }
}

function hasPermission($permission) {
    $userPermissions = $_SESSION['permissions'] ?? [];
    return in_array($permission, $userPermissions);
}
```

### Input Validation

```php
function validateInput($data, $rules) {
    $errors = [];

    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? '';

        // Required validation
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = "Campo obrigatório";
            continue;
        }

        // Type validation
        if (isset($rule['type'])) {
            switch ($rule['type']) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "Email inválido";
                    }
                    break;
                case 'integer':
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[$field] = "Número inteiro inválido";
                    }
                    break;
            }
        }

        // Length validation
        if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[$field] = "Mínimo de {$rule['min_length']} caracteres";
        }

        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[$field] = "Máximo de {$rule['max_length']} caracteres";
        }
    }

    return $errors;
}
```

## Frontend Patterns

### AdminLTE Components

```html
<!-- Small Box (Statistics) -->
<div class="small-box bg-info">
  <div class="inner">
    <h3>150</h3>
    <p>New Orders</p>
  </div>
  <div class="icon">
    <i class="fas fa-shopping-cart"></i>
  </div>
</div>

<!-- Card -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-table mr-2"></i>
      Data Table
    </h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse">
        <i class="fas fa-minus"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    <!-- Content -->
  </div>
</div>

<!-- Alert -->
<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert">&times;</button>
  <h5><i class="icon fas fa-check"></i> Success!</h5>
  Operation completed successfully.
</div>
```

### Form Patterns

```html
<form id="form-example" class="form-horizontal">
  <div class="form-group row">
    <label for="nome" class="col-sm-2 col-form-label">Nome</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="nome" name="nome" required />
      <span class="help-block">Informe o nome completo</span>
    </div>
  </div>

  <div class="form-group row">
    <label for="email" class="col-sm-2 col-form-label">Email</label>
    <div class="col-sm-10">
      <input
        type="email"
        class="form-control"
        id="email"
        name="email"
        required
      />
    </div>
  </div>

  <div class="form-group row">
    <label for="status" class="col-sm-2 col-form-label">Status</label>
    <div class="col-sm-10">
      <select class="form-control" id="status" name="status">
        <option value="ativo">Ativo</option>
        <option value="inativo">Inativo</option>
      </select>
    </div>
  </div>

  <div class="form-group row">
    <div class="offset-sm-2 col-sm-10">
      <button type="submit" class="btn btn-primary">Salvar</button>
      <button type="button" class="btn btn-default ml-2">Cancelar</button>
    </div>
  </div>
</form>
```

### Table Patterns

```html
<div class="table-responsive">
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Status</th>
        <th>Criado em</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>João Silva</td>
        <td><span class="badge badge-success">Ativo</span></td>
        <td>25/03/2024 10:30</td>
        <td>
          <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-info" title="Editar">
              <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-danger" title="Excluir">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

## Common Tasks

### Create New Module

1. Create directory structure
2. Generate MVC files
3. Create database table
4. Add menu entry
5. Set permissions
6. Test functionality

### Debug Common Issues

1. Check PHP error logs
2. Verify database connection
3. Validate session state
4. Check file permissions
5. Test API endpoints

### Performance Optimization

1. Optimize database queries
2. Implement caching
3. Reduce memory usage
4. Minimize HTTP requests
5. Use CDN for assets

## Best Practices

### Code Quality

- Follow PSR-12 coding standards
- Use meaningful variable names
- Add comprehensive comments
- Implement error handling
- Write unit tests

### Security

- Use prepared statements
- Validate all inputs
- Implement proper authentication
- Use HTTPS for sensitive data
- Log security events

### Performance

- Use database indexes
- Implement pagination
- Cache frequently accessed data
- Optimize images and assets
- Monitor resource usage

### Maintainability

- Keep code modular
- Use consistent patterns
- Document complex logic
- Version control all changes
- Regular code reviews

## Troubleshooting

### Common Errors

1. **"Usuário não autenticado"** - Check session state
2. **"Sem permissão"** - Verify user permissions
3. **"Erro na conexão"** - Check database credentials
4. **"Table doesn't exist"** - Run migrations
5. **"Permission denied"** - Check file permissions

### Debug Steps

1. Enable error reporting
2. Check error logs
3. Verify database connection
4. Test with simple queries
5. Check session variables

### Performance Issues

1. Profile slow queries
2. Check memory usage
3. Monitor database connections
4. Analyze HTTP requests
5. Review code efficiency

## Resources

### Documentation

- [Architecture Overview](../../../architecture/visao_geral.md)
- [Database Schema](../../../architecture/database/schema_completo.md)
- [Security Guidelines](../../../architecture/security/seguranca_completa.md)
- [Development Standards](../../../architecture/desenvolvimento.md)

### External Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [AdminLTE Documentation](https://adminlte.io/docs/3.2/)
- [jQuery Documentation](https://api.jquery.com/)
- [Bootstrap Documentation](https://getbootstrap.com/docs/4.6/)

### Tools

- [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)
- [PHPUnit](https://phpunit.de/)
- [Xdebug](https://xdebug.org/)
- [MySQL Workbench](https://www.mysql.com/products/workbench/)
- [Postman](https://www.postman.com/)

## Quick Commands

### Database Operations

```sql
-- Check table structure
DESCRIBE nome_tabela;

-- Check indexes
SHOW INDEX FROM nome_tabela;

-- Optimize table
OPTIMIZE TABLE nome_tabela;

-- Check slow queries
SHOW PROCESSLIST;
```

### File Operations

```bash
# Check file permissions
ls -la arquivo.php

# Change permissions
chmod 644 arquivo.php

# Find files with specific pattern
find . -name "*.php" -type f

# Check file size
du -h arquivo.php
```

### Debug Commands

```php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session state
var_dump($_SESSION);

// Check POST data
var_dump($_POST);

// Check GET data
var_dump($_GET);
```

## Usage Examples

### Get architecture overview

```
@sigep-base-conhecimento show me the SIGEP architecture overview
```

### Get coding standards

```
@sigep-base-conhecimento what are the coding standards for SIGEP controllers
```

### Get database patterns

```
@sigep-base-conhecimento show me the standard database table structure for SIGEP
```

### Get security guidelines

```
@sigep-base-conhecimento what are the authentication and authorization patterns in SIGEP
```

### Get frontend patterns

```
@sigep-base-conhecimento show me the AdminLTE component patterns used in SIGEP
```

### Debug common issue

```
@sigep-base-conhecimento how do I debug a "Usuário não autenticado" error in SIGEP
```

### Create new module

```
@sigep-base-conhecimento what are the steps to create a new SIGEP module
```

### Performance optimization

```
@sigep-base-conhecimento how do I optimize database performance in SIGEP
```

## Maintenance

### Regular Tasks

- [ ] Update documentation
- [ ] Review code standards
- [ ] Update security guidelines
- [ ] Check performance metrics
- [ ] Review architecture decisions

### Knowledge Updates

- [ ] Document new patterns
- [ ] Update best practices
- [ ] Add troubleshooting guides
- [ ] Review resource links
- [ ] Update examples

## Conclusion

This knowledge base serves as a comprehensive reference for SIGEP development, providing quick access to patterns, standards, and best practices. Keep this resource updated and use it as the primary reference for all development activities.

For specific questions or issues, refer to the appropriate section or use the quick commands provided above.
