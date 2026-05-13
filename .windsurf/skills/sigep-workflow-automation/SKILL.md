---
name: sigep-workflow-automation
description: Automates repetitive development workflows in SIGEP including module creation, database operations, and code generation
---

# SIGEP Workflow Automation Skill

## Purpose
This skill automates repetitive development workflows for SIGEP, including module creation, database operations, code generation, and development processes to increase productivity and ensure consistency.

## Available Workflows

### 1. Module Creation Workflow
- Creates complete module structure
- Generates MVC files
- Sets up database tables
- Creates assets and documentation
- Configures menu integration

### 2. Database Migration Workflow
- Creates migration files
- Generates table schemas
- Handles data transformations
- Creates rollback scripts
- Validates migrations

### 3. Code Generation Workflow
- Generates CRUD operations
- Creates API endpoints
- Generates form templates
- Creates JavaScript modules
- Generates test files

### 4. Development Setup Workflow
- Sets up development environment
- Configures database connections
- Creates configuration files
- Sets up testing framework
- Initializes version control

## Workflow Templates

### Module Creation Workflow
```php
<?php
class SIGEPModuleCreator {
    private $moduleName;
    private $sector;
    private $basePath;
    private $config;
    
    public function __construct($moduleName, $sector) {
        $this->moduleName = $moduleName;
        $this->sector = $sector;
        $this->basePath = __DIR__ . '/../../../modulos/' . $sector . '/' . $moduleName;
        $this->config = require __DIR__ . '/../../../conf/db.php';
    }
    
    public function createModule($options = []) {
        $results = [];
        
        // Create directory structure
        $results['directories'] = $this->createDirectoryStructure();
        
        // Create MVC files
        $results['files'] = $this->createMVCFiles($options);
        
        // Create assets
        $results['assets'] = $this->createAssets();
        
        // Create database table
        if ($options['create_table'] ?? false) {
            $results['database'] = $this->createDatabaseTable($options);
        }
        
        // Create documentation
        $results['documentation'] = $this->createDocumentation();
        
        // Update menu
        if ($options['update_menu'] ?? false) {
            $results['menu'] = $this->updateMenu();
        }
        
        return $results;
    }
    
    private function createDirectoryStructure() {
        $directories = [
            $this->basePath,
            $this->basePath . '/assets',
            $this->basePath . '/assets/css',
            $this->basePath . '/assets/js',
            $this->basePath . '/assets/img'
        ];
        
        $results = [];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if (mkdir($dir, 0755, true)) {
                    $results[] = "Created directory: $dir";
                } else {
                    $results[] = "Failed to create directory: $dir";
                }
            } else {
                $results[] = "Directory already exists: $dir";
            }
        }
        
        return $results;
    }
    
    private function createMVCFiles($options) {
        $results = [];
        
        // Create view file
        $viewContent = $this->generateViewContent($options);
        $viewFile = $this->basePath . '/' . $this->moduleName . '_view.php';
        
        if (file_put_contents($viewFile, $viewContent)) {
            $results[] = "Created view file: $viewFile";
        } else {
            $results[] = "Failed to create view file: $viewFile";
        }
        
        // Create logica file
        $logicaContent = $this->generateLogicaContent($options);
        $logicaFile = $this->basePath . '/' . $this->moduleName . '_logica.php';
        
        if (file_put_contents($logicaFile, $logicaContent)) {
            $results[] = "Created logica file: $logicaFile";
        } else {
            $results[] = "Failed to create logica file: $logicaFile";
        }
        
        return $results;
    }
    
    private function createAssets() {
        $results = [];
        
        // Create CSS file
        $cssContent = $this->generateCSSContent();
        $cssFile = $this->basePath . '/assets/css/' . $this->moduleName . '.css';
        
        if (file_put_contents($cssFile, $cssContent)) {
            $results[] = "Created CSS file: $cssFile";
        } else {
            $results[] = "Failed to create CSS file: $cssFile";
        }
        
        // Create JavaScript file
        $jsContent = $this->generateJSContent();
        $jsFile = $this->basePath . '/assets/js/' . $this->moduleName . '.js';
        
        if (file_put_contents($jsFile, $jsContent)) {
            $results[] = "Created JavaScript file: $jsFile";
        } else {
            $results[] = "Failed to create JavaScript file: $jsFile";
        }
        
        return $results;
    }
    
    private function createDatabaseTable($options) {
        $tableName = $options['table_name'] ?? $this->moduleName;
        $columns = $options['columns'] ?? $this->getDefaultColumns();
        
        $sql = $this->generateCreateTableSQL($tableName, $columns);
        
        try {
            $pdo = new PDO(
                "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}",
                $this->config['user'],
                $this->config['pass']
            );
            
            $pdo->exec($sql);
            
            return ["Created database table: $tableName"];
        } catch (PDOException $e) {
            return ["Failed to create table: " . $e->getMessage()];
        }
    }
    
    private function createDocumentation() {
        $results = [];
        
        // Create README.md
        $readmeContent = $this->generateReadmeContent();
        $readmeFile = $this->basePath . '/README.md';
        
        if (file_put_contents($readmeFile, $readmeContent)) {
            $results[] = "Created README file: $readmeFile";
        } else {
            $results[] = "Failed to create README file: $readmeFile";
        }
        
        return $results;
    }
    
    private function updateMenu() {
        $menuFile = __DIR__ . '/../../../includes/sidebar_logica.php';
        
        if (!file_exists($menuFile)) {
            return ["Menu file not found: $menuFile"];
        }
        
        $menuContent = file_get_contents($menuFile);
        
        // Find the sector section and add new menu item
        $pattern = "/(if \(podeVisualizarSetor\('perm_{$this->sector}'\)\) \{[\s\S]*?\$menu\['{$this->sector}'\]\s*=\s*\[[\s\S]*?\];)/";
        
        if (preg_match($pattern, $menuContent, $matches)) {
            $newMenuItem = $this->generateMenuItem();
            
            $updatedSection = str_replace(
                "];",
                "            ['title' => '{$this->moduleName}', 'icon' => 'fas fa-cog text-success', 'page' => '/modulos/{$this->sector}/{$this->moduleName}/{$this->moduleName}_view.php', 'parent' => '{$this->moduleName}'],\n        ];",
                $matches[1]
            );
            
            $menuContent = str_replace($matches[1], $updatedSection, $menuContent);
            
            if (file_put_contents($menuFile, $menuContent)) {
                return ["Updated menu in: $menuFile"];
            } else {
                return ["Failed to update menu file: $menuFile"];
            }
        } else {
            return ["Sector menu section not found"];
        }
    }
    
    private function generateViewContent($options) {
        $title = $options['title'] ?? ucfirst($this->moduleName);
        $description = $options['description'] ?? "Módulo $this->moduleName";
        
        return "<?php
require_once __DIR__ . '/{$this->moduleName}_logica.php';
?>

<!-- Content Header -->
<div class=\"row mb-2\">
    <div class=\"col-sm-6\">
        <h1 class=\"m-0\">$title</h1>
    </div>
    <div class=\"col-sm-6\">
        <ol class=\"breadcrumb float-sm-right\">
            <li class=\"breadcrumb-item\"><a href=\"#\">Home</a></li>
            <li class=\"breadcrumb-item active\">$title</li>
        </ol>
    </div>
</div>

<!-- Main content -->
<section class=\"content\">
    <div class=\"container-fluid\">
        
        <!-- Statistics Cards -->
        <div class=\"row\">
            <div class=\"col-lg-3 col-6\">
                <div class=\"small-box bg-info pointer\">
                    <div class=\"inner\">
                        <h3 id=\"stats-total\">0</h3>
                        <p>Total</p>
                    </div>
                    <div class=\"icon\">
                        <i class=\"fas fa-database\"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Card -->
        <div class=\"row\">
            <div class=\"col-12\">
                <div class=\"card\">
                    <div class=\"card-header\">
                        <h3 class=\"card-title\">
                            <i class=\"fas fa-cog mr-2\"></i>
                            $description
                        </h3>
                        <div class=\"card-tools\">
                            <button type=\"button\" class=\"btn btn-sm btn-primary\" onclick=\"showAddModal()\">
                                <i class=\"fas fa-plus mr-1\"></i>
                                Novo
                            </button>
                        </div>
                    </div>
                    <div class=\"card-body\">
                        <div class=\"table-responsive\">
                            <table class=\"table table-bordered table-striped\" id=\"main-table\">
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
                                    <!-- Content populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<!-- Modal -->
<div class=\"modal fade\" id=\"add-modal\" tabindex=\"-1\">
    <div class=\"modal-dialog\">
        <div class=\"modal-content\">
            <div class=\"modal-header\">
                <h5 class=\"modal-title\">Novo Registro</h5>
                <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>
            </div>
            <form id=\"add-form\">
                <div class=\"modal-body\">
                    <div class=\"form-group\">
                        <label for=\"nome\">Nome</label>
                        <input type=\"text\" class=\"form-control\" id=\"nome\" name=\"nome\" required>
                    </div>
                    <div class=\"form-group\">
                        <label for=\"descricao\">Descrição</label>
                        <textarea class=\"form-control\" id=\"descricao\" name=\"descricao\" rows=\"3\"></textarea>
                    </div>
                </div>
                <div class=\"modal-footer\">
                    <button type=\"submit\" class=\"btn btn-primary\">Salvar</button>
                    <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src=\"modulos/{$this->sector}/{$this->moduleName}/assets/js/{$this->moduleName}.js\"></script>
";
    }
    
    private function generateLogicaContent($options) {
        $tableName = $options['table_name'] ?? $this->moduleName;
        
        return "<?php
// {$this->moduleName} - Controller SIGEP
// Descrição: $description

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar Timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Função para retornar erro JSON
function returnError(\$message, \$code = 500) {
    http_response_code(\$code);
    echo json_encode(['error' => \$message, 'code' => \$code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se usuário está autenticado
if (!isset(\$_SESSION['user_id']) || !isset(\$_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão específica
if (!(\$_SESSION['user_admin'] || (\$_SESSION['perm_{$this->sector}'] ?? 0))) {
    returnError('Sem permissão para acessar este módulo', 403);
}

// Configurar conexão PDO
try {
    \$config = require __DIR__ . '/../../../conf/db.php';
    \$dsn = \"mysql:host={\$config['host']};dbname={\$config['dbname']};charset={\$config['charset']}\";
    \$pdo = new PDO(\$dsn, \$config['user'], \$config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    \$pdo->exec(\"SET time_zone = '-03:00'\");
} catch (PDOException \$e) {
    returnError('Erro na conexão com banco de dados: ' . \$e->getMessage(), 500);
}

// Funções CRUD
function criarItem(\$pdo, \$dados) {
    try {
        \$stmt = \$pdo->prepare(\"INSERT INTO {$tableName} (nome, descricao, created_at) VALUES (?, ?, NOW())\");
        \$stmt->execute([\$dados['nome'], \$dados['descricao']]);
        return \$pdo->lastInsertId();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao criar item: ' . \$e->getMessage());
    }
}

function listarItens(\$pdo) {
    try {
        \$stmt = \$pdo->prepare(\"SELECT * FROM {$tableName} ORDER BY created_at DESC\");
        \$stmt->execute();
        return \$stmt->fetchAll();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao listar itens: ' . \$e->getMessage());
    }
}

function atualizarItem(\$pdo, \$id, \$dados) {
    try {
        \$stmt = \$pdo->prepare(\"UPDATE {$tableName} SET nome = ?, descricao = ?, updated_at = NOW() WHERE id = ?\");
        \$stmt->execute([\$dados['nome'], \$dados['descricao'], \$id]);
        return \$stmt->rowCount();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao atualizar item: ' . \$e->getMessage());
    }
}

function deletarItem(\$pdo, \$id) {
    try {
        \$stmt = \$pdo->prepare(\"DELETE FROM {$tableName} WHERE id = ?\");
        \$stmt->execute([\$id]);
        return \$stmt->rowCount();
    } catch (PDOException \$e) {
        throw new Exception('Erro ao deletar item: ' . \$e->getMessage());
    }
}

// Processar requisições AJAX
if (\$_SERVER['REQUEST_METHOD'] === 'POST' && isset(\$_POST['action'])) {

    ob_clean();

    try {
        switch (\$_POST['action']) {
            case 'listar':
                \$itens = listarItens(\$pdo);
                echo json_encode(['success' => true, 'data' => \$itens], JSON_UNESCAPED_UNICODE);
                break;

            case 'criar':
                \$item_id = criarItem(\$pdo, \$_POST);
                echo json_encode(['success' => true, 'data' => ['id' => \$item_id]], JSON_UNESCAPED_UNICODE);
                break;

            case 'atualizar':
                \$affected = atualizarItem(\$pdo, \$_POST['id'], \$_POST);
                echo json_encode(['success' => true, 'affected' => \$affected], JSON_UNESCAPED_UNICODE);
                break;

            case 'deletar':
                \$affected = deletarItem(\$pdo, \$_POST['id']);
                echo json_encode(['success' => true, 'affected' => \$affected], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }

    } catch (Exception \$e) {
        echo json_encode(['success' => false, 'message' => \$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Carregar dados para a view
try {
    \$itens = listarItens(\$pdo);
} catch (Exception \$e) {
    \$itens = [];
}
?>
";
    }
    
    private function generateCSSContent() {
        return "/**
 * SIGEP {$this->moduleName} - Estilos Customizados
 * Baseado em AdminLTE 3
 */

/* Cards Enhancement */
.small-box {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.small-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Loading States */
.loading-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #6c757d;
}

.loading-spinner i {
    font-size: 2rem;
    margin-bottom: 1rem;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .small-box {
        margin-bottom: 1rem;
    }
}

/* Animation Classes */
.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
";
    }
    
    private function generateJSContent() {
        return "/**\n * SIGEP {$this->moduleName} - JavaScript Principal\n * Funcionalidades AJAX + UI AdminLTE\n */\n\n// Proteger contra múltiplos carregamentos no SPA\nif (typeof window.{$this->moduleName}Loaded === 'undefined') {\n    window.{$this->moduleName}Loaded = true;\n\n// Variáveis globais\nvar currentData = {\n    itens: []\n};\n\n// Inicialização quando documento estiver pronto\n\$(document).ready(function() {\n    carregarDados();\n    inicializarComponentes();\n\n    // Auto-refresh a cada 30 segundos\n    setInterval(autoRefresh, 30000);\n});\n\n// Carregar dados via AJAX\nfunction carregarDados() {\n    \$.ajax({\n        url: 'modulos/{$this->sector}/{$this->moduleName}/{$this->moduleName}_logica.php',\n        method: 'POST',\n        data: { action: 'listar' },\n        dataType: 'json',\n        success: function(response) {\n            if (response.success) {\n                currentData.itens = response.data;\n                atualizarInterface();\n            }\n        },\n        error: function(xhr, status, error) {\n            console.error('Erro ao carregar dados:', error);\n            mostrarNotificacao('Erro ao carregar dados', 'error');\n        }\n    });\n}\n\n// Salvar item\nfunction salvarItem() {\n    const dados = {\n        nome: \$('#nome').val(),\n        descricao: \$('#descricao').val()\n    };\n\n    \$.ajax({\n        url: 'modulos/{$this->sector}/{$this->moduleName}/{$this->moduleName}_logica.php',\n        method: 'POST',\n        data: {\n            action: 'criar',\n            ...dados\n        },\n        dataType: 'json',\n        success: function(response) {\n            if (response.success) {\n                mostrarNotificacao('Item criado com sucesso', 'success');\n                carregarDados();\n            } else {\n                mostrarNotificacao('Erro: ' + response.message, 'error');\n            }\n        },\n        error: function() {\n            mostrarNotificacao('Falha na comunicação', 'error');\n        }\n    });\n}\n\n// Utilitários\nfunction mostrarNotificacao(mensagem, tipo = 'info') {\n    // Usar sistema de notificações do SIGEP se disponível\n    if (typeof toastr !== 'undefined') {\n        toastr[tipo](mensagem);\n    } else {\n        console.log(`[\${tipo.toUpperCase()}] \${mensagem}`);\n    }\n}\n\nfunction atualizarInterface() {\n    // Atualizar elementos da interface\n    \$('#stats-total').text(currentData.itens.length);\n}\n\nfunction autoRefresh() {\n    carregarDados();\n}\n\nfunction showAddModal() {\n    \$('#add-modal').modal('show');\n}\n\n// Event listeners\n\$(document).on('click', '#btn-salvar', function() {\n    salvarItem();\n});\n\n\$(document).on('submit', '#add-form', function(e) {\n    e.preventDefault();\n    salvarItem();\n});\n\n// Fechar bloco de proteção contra múltiplos carregamentos\n} // fim do if (typeof window.{$this->moduleName}Loaded === 'undefined')\n";
    }
    
    private function generateCreateTableSQL($tableName, $columns) {
        $sql = "CREATE TABLE `$tableName` (\n";
        $sql .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        
        foreach ($columns as $column) {
            $sql .= "  `$column[name]` $column[type] $column[extra],\n";
        }
        
        $sql .= "  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',\n";
        $sql .= "  PRIMARY KEY (`id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n";
        
        return $sql;
    }
    
    private function getDefaultColumns() {
        return [
            ['name' => 'nome', 'type' => 'varchar(255)', 'extra' => 'NOT NULL'],
            ['name' => 'descricao', 'type' => 'text', 'extra' => 'NULL']
        ];
    }
    
    private function generateReadmeContent() {
        return "# {$this->moduleName}\n\n## Descrição\n\nMódulo {$this->moduleName} do sistema SIGEP.\n\n## Estrutura\n\n```\nmodulos/{$this->sector}/{$this->moduleName}/\n├── {$this->moduleName}_view.php      # Interface (View)\n├── {$this->moduleName}_logica.php    # Controller (Lógica)\n├── assets/\n│   ├── css/{$this->moduleName}.css   # Estilos customizados\n│   └── js/{$this->moduleName}.js     # JavaScript + AJAX\n└── README.md                         # Este arquivo\n```\n\n## Funcionalidades\n\n- CRUD básico\n- Interface AdminLTE\n- Navegação SPA\n- Validação de formulários\n- Notificações\n\n## Instalação\n\n1. Copiar os arquivos para o diretório do módulo\n2. Criar tabela no banco de dados\n3. Adicionar entrada no menu\n4. Configurar permissões\n\n## Uso\n\nAcessar via menu lateral: {$this->moduleName}\n\n## Desenvolvimento\n\n- PHP 8+\n- MySQL 8.0\n- AdminLTE 3\n- jQuery 3.6\n\n## Autor\n\nSIGEP Development Team\n";
    }
    
    private function generateMenuItem() {
        return "            ['title' => '{$this->moduleName}', 'icon' => 'fas fa-cog text-success', 'page' => '/modulos/{$this->sector}/{$this->moduleName}/{$this->moduleName}_view.php', 'parent' => '{$this->moduleName}']";
    }
}
```

### Database Migration Workflow
```php
<?php
class SIGEPMigrationManager {
    private $pdo;
    private $migrationPath;
    
    public function __construct() {
        $config = require __DIR__ . '/../../../conf/db.php';
        $this->pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
            $config['user'],
            $config['pass']
        );
        $this->migrationPath = __DIR__ . '/../../../database/migrations';
    }
    
    public function createMigration($tableName, $changes) {
        $timestamp = date('Y_m_d_His');
        $migrationName = "create_{$tableName}_table";
        $filename = "{$timestamp}_{$migrationName}.php";
        $filepath = $this->migrationPath . '/' . $filename;
        
        $migrationContent = $this->generateMigrationContent($tableName, $changes);
        
        if (file_put_contents($filepath, $migrationContent)) {
            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to create migration file'
            ];
        }
    }
    
    public function runMigration($filename) {
        $filepath = $this->migrationPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Migration file not found'];
        }
        
        // Include migration file
        require_once $filepath;
        
        // Get migration class name
        $className = $this->getMigrationClassName($filename);
        
        if (!class_exists($className)) {
            return ['success' => false, 'error' => 'Migration class not found'];
        }
        
        try {
            $migration = new $className($this->pdo);
            
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Run up migration
            $migration->up();
            
            // Record migration
            $this->recordMigration($filename);
            
            // Commit transaction
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Migration completed successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function rollbackMigration($filename) {
        $filepath = $this->migrationPath . '/' . $filename;
        
        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => 'Migration file not found'];
        }
        
        require_once $filepath;
        $className = $this->getMigrationClassName($filename);
        
        if (!class_exists($className)) {
            return ['success' => false, 'error' => 'Migration class not found'];
        }
        
        try {
            $migration = new $className($this->pdo);
            
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Run down migration
            $migration->down();
            
            // Remove migration record
            $this->removeMigrationRecord($filename);
            
            // Commit transaction
            $this->pdo->commit();
            
            return ['success' => true, 'message' => 'Rollback completed successfully'];
            
        } catch (Exception $e) {
            $this->pdo->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function generateMigrationContent($tableName, $changes) {
        $upSQL = $this->generateUpSQL($tableName, $changes);
        $downSQL = $this->generateDownSQL($tableName);
        
        $className = $this->getMigrationClassNameFromTable($tableName);
        
        return "<?php
class {$className} {
    private \$pdo;
    
    public function __construct(\$pdo) {
        \$this->pdo = \$pdo;
    }
    
    public function up() {
        try {
            \$sql = \"$upSQL\";
            \$this->pdo->exec(\$sql);
        } catch (PDOException \$e) {
            throw new Exception('Migration failed: ' . \$e->getMessage());
        }
    }
    
    public function down() {
        try {
            \$sql = \"$downSQL\";
            \$this->pdo->exec(\$sql);
        } catch (PDOException \$e) {
            throw new Exception('Rollback failed: ' . \$e->getMessage());
        }
    }
}
";
    }
    
    private function generateUpSQL($tableName, $changes) {
        $sql = "CREATE TABLE `$tableName` (\n";
        $sql .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        
        foreach ($changes as $column) {
            $sql .= "  `$column[name]` $column[type] $column[extra],\n";
        }
        
        $sql .= "  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',\n";
        $sql .= "  PRIMARY KEY (`id`)\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        return $sql;
    }
    
    private function generateDownSQL($tableName) {
        return "DROP TABLE IF EXISTS `$tableName`;";
    }
    
    private function getMigrationClassName($filename) {
        $parts = explode('_', $filename);
        array_shift($parts); // Remove timestamp
        array_pop($parts); // Remove .php
        
        $className = '';
        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }
        
        return $className;
    }
    
    private function getMigrationClassNameFromTable($tableName) {
        return 'Create' . ucfirst($tableName) . 'Table';
    }
    
    private function recordMigration($filename) {
        $sql = "INSERT INTO migrations (migration, batch) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$filename, $this->getBatchNumber()]);
    }
    
    private function removeMigrationRecord($filename) {
        $sql = "DELETE FROM migrations WHERE migration = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$filename]);
    }
    
    private function getBatchNumber() {
        $sql = "SELECT MAX(batch) as max_batch FROM migrations";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        
        return ($result['max_batch'] ?? 0) + 1;
    }
}
```

### Code Generation Workflow
```php
<?php
class SIGEPCodeGenerator {
    
    public function generateCRUD($tableName, $fields) {
        $results = [];
        
        // Generate controller
        $results['controller'] = $this->generateController($tableName, $fields);
        
        // Generate model
        $results['model'] = $this->generateModel($tableName, $fields);
        
        // Generate API endpoints
        $results['api'] = $this->generateAPI($tableName, $fields);
        
        // Generate tests
        $results['tests'] = $this->generateTests($tableName, $fields);
        
        return $results;
    }
    
    public function generateForm($tableName, $fields) {
        $formHTML = "<form id=\"form-$tableName\" class=\"form-horizontal\">\n";
        
        foreach ($fields as $field) {
            $formHTML .= $this->generateFieldHTML($field);
        }
        
        $formHTML .= "    <div class=\"form-group row\">\n";
        $formHTML .= "        <div class=\"offset-sm-2 col-sm-10\">\n";
        $formHTML .= "            <button type=\"submit\" class=\"btn btn-primary\">Salvar</button>\n";
        $formHTML .= "            <button type=\"button\" class=\"btn btn-default ml-2\" onclick=\"resetForm()\">Cancelar</button>\n";
        $formHTML .= "        </div>\n";
        $formHTML .= "    </div>\n";
        $formHTML .= "</form>\n";
        
        return $formHTML;
    }
    
    public function generateJavaScript($tableName, $fields) {
        $js = "// JavaScript for $tableName\n";
        $js .= "(function($) {\n";
        $js .= "    'use strict';\n\n";
        $js .= "    window.{$tableName}Module = {\n";
        $js .= "        init: function() {\n";
        $js .= "            this.bindEvents();\n";
        $js .= "            this.loadData();\n";
        $js .= "        },\n\n";
        $js .= "        bindEvents: function() {\n";
        $js .= "            $('#form-$tableName').on('submit', function(e) {\n";
        $js .= "                e.preventDefault();\n";
        $js .= "                {$tableName}Module.saveData();\n";
        $js .= "            });\n";
        $js .= "        },\n\n";
        $js .= "        loadData: function() {\n";
        $js .= "            $.ajax({\n";
        $js .= "                url: 'api/$tableName',\n";
        $js .= "                method: 'GET',\n";
        $js .= "                success: function(response) {\n";
        $js .= "                    if (response.success) {\n";
        $js .= "                        {$tableName}Module.updateTable(response.data);\n";
        $js .= "                    }\n";
        $js .= "                }\n";
        $js .= "            });\n";
        $js .= "        },\n\n";
        $js .= "        saveData: function() {\n";
        $js .= "            const formData = $('#form-$tableName').serialize();\n";
        $js .= "            \n";
        $js .= "            $.ajax({\n";
        $js .= "                url: 'api/$tableName',\n";
        $js .= "                method: 'POST',\n";
        $js .= "                data: formData,\n";
        $js .= "                success: function(response) {\n";
        $js .= "                    if (response.success) {\n";
        $js .= "                        SIGEPU.showSuccess('Dados salvos com sucesso');\n";
        $js .= "                        {$tableName}Module.loadData();\n";
        $js .= "                    }\n";
        $js .= "                }\n";
        $js .= "            });\n";
        $js .= "        },\n\n";
        $js .= "        updateTable: function(data) {\n";
        $js .= "            const tbody = $('#table-$tableName tbody');\n";
        $js .= "            tbody.empty();\n";
        $js .= "            \n";
        $js .= "            data.forEach(function(item) {\n";
        $js .= "                const row = $('<tr></tr>');\n";
        
        foreach ($fields as $field) {
            $js .= "                row.append('<td>' + item.{$field['name']} + '</td>');\n";
        }
        
        $js .= "                tbody.append(row);\n";
        $js .= "            });\n";
        $js .= "        }\n";
        $js .= "    };\n\n";
        $js .= "    $(document).ready(function() {\n";
        $js .= "        {$tableName}Module.init();\n";
        $js .= "    });\n\n";
        $js .= "})(jQuery);\n";
        
        return $js;
    }
    
    private function generateFieldHTML($field) {
        $html = "    <div class=\"form-group row\">\n";
        $html .= "        <label for=\"{$field['name']}\" class=\"col-sm-2 col-form-label\">" . ucfirst($field['name']) . "</label>\n";
        $html .= "        <div class=\"col-sm-10\">\n";
        
        switch ($field['type']) {
            case 'text':
                $html .= "            <input type=\"text\" class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\"";
                if ($field['required'] ?? false) {
                    $html .= " required";
                }
                $html .= ">\n";
                break;
                
            case 'textarea':
                $html .= "            <textarea class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\"";
                if ($field['required'] ?? false) {
                    $html .= " required";
                }
                $html .= "></textarea>\n";
                break;
                
            case 'select':
                $html .= "            <select class=\"form-control\" id=\"{$field['name']}\" name=\"{$field['name']}\"";
                if ($field['required'] ?? false) {
                    $html .= " required";
                }
                $html .= ">\n";
                $html .= "                <option value=\"\">Selecione...</option>\n";
                $html .= "            </select>\n";
                break;
        }
        
        $html .= "        </div>\n";
        $html .= "    </div>\n";
        
        return $html;
    }
    
    private function generateController($tableName, $fields) {
        $controller = "<?php\n";
        $controller .= "// Controller for $tableName\n\n";
        $controller .= "class {$tableName}Controller {\n";
        $controller .= "    private \$pdo;\n\n";
        $controller .= "    public function __construct() {\n";
        $controller .= "        \$this->pdo = getDatabaseConnection();\n";
        $controller .= "    }\n\n";
        
        // Generate CRUD methods
        $controller .= $this->generateControllerMethods($tableName, $fields);
        
        $controller .= "}\n";
        
        return $controller;
    }
    
    private function generateControllerMethods($tableName, $fields) {
        $methods = '';
        
        // Create method
        $methods .= "    public function create(\$data) {\n";
        $methods .= "        \$sql = \"INSERT INTO $tableName (\";\n";
        $methods .= "        \$fields = ['" . implode("', '", array_column($fields, 'name')) . "'];\n";
        $methods .= "        \$sql .= implode(', ', \$fields);\n";
        $methods .= "        \$sql .= \") VALUES (\";\n";
        $methods .= "        \$sql .= str_repeat('?,', count(\$fields) - 1) . '?');\n\n";
        $methods .= "        try {\n";
        $methods .= "            \$stmt = \$this->pdo->prepare(\$sql);\n";
        $methods .= "            \$stmt->execute(array_values(\$data));\n";
        $methods .= "            return \$this->pdo->lastInsertId();\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            throw new Exception('Failed to create record: ' . \$e->getMessage());\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";
        
        // Read method
        $methods .= "    public function read(\$id = null) {\n";
        $methods .= "        \$sql = \"SELECT * FROM $tableName\";\n";
        $methods .= "        if (\$id) {\n";
        $methods .= "            \$sql .= \" WHERE id = ?\";\n";
        $methods .= "            \$stmt = \$this->pdo->prepare(\$sql);\n";
        $methods .= "            \$stmt->execute([\$id]);\n";
        $methods .= "            return \$stmt->fetch();\n";
        $methods .= "        } else {\n";
        $methods .= "            \$stmt = \$this->pdo->query(\$sql);\n";
        $methods .= "            return \$stmt->fetchAll();\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";
        
        // Update method
        $methods .= "    public function update(\$id, \$data) {\n";
        $methods .= "        \$sql = \"UPDATE $tableName SET \";\n";
        $methods .= "        \$fields = ['" . implode("', '", array_column($fields, 'name')) . "'];\n";
        $methods .= "        \$setClauses = [];\n";
        $methods .= "        foreach (\$fields as \$field) {\n";
        $methods .= "            \$setClauses[] = \"\$field = ?\";\n";
        $methods .= "        }\n";
        $methods .= "        \$sql .= implode(', ', \$setClauses);\n";
        $methods .= "        \$sql .= \" WHERE id = ?\";\n\n";
        $methods .= "        try {\n";
        $methods .= "            \$stmt = \$this->pdo->prepare(\$sql);\n";
        $methods .= "            \$params = array_values(\$data);\n";
        $methods .= "            \$params[] = \$id;\n";
        $methods .= "            \$stmt->execute(\$params);\n";
        $methods .= "            return \$stmt->rowCount();\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            throw new Exception('Failed to update record: ' . \$e->getMessage());\n";
        $methods .= "        }\n";
        $methods .= "    }\n\n";
        
        // Delete method
        $methods .= "    public function delete(\$id) {\n";
        $methods .= "        \$sql = \"DELETE FROM $tableName WHERE id = ?\";\n";
        $methods .= "        try {\n";
        $methods .= "            \$stmt = \$this->pdo->prepare(\$sql);\n";
        $methods .= "            \$stmt->execute([\$id]);\n";
        $methods .= "            return \$stmt->rowCount();\n";
        $methods .= "        } catch (PDOException \$e) {\n";
        $methods .= "            throw new Exception('Failed to delete record: ' . \$e->getMessage());\n";
        $methods .= "        }\n";
        $methods .= "    }\n";
        
        return $methods;
    }
    
    private function generateModel($tableName, $fields) {
        $model = "<?php\n";
        $model .= "// Model for $tableName\n\n";
        $model .= "class {$tableName} {\n";
        
        foreach ($fields as $field) {
            $model .= "    public \${$field['name']};\n";
        }
        
        $model .= "    public \$id;\n";
        $model .= "    public \$created_at;\n";
        $model .= "    public \$updated_at;\n";
        $model .= "    public \$status;\n\n";
        
        $model .= "    public function toArray() {\n";
        $model .= "        return [\n";
        foreach ($fields as $field) {
            $model .= "            '{$field['name']}' => \$this->{$field['name']},\n";
        }
        $model .= "            'id' => \$this->id,\n";
        $model .= "            'created_at' => \$this->created_at,\n";
        $model .= "            'updated_at' => \$this->updated_at,\n";
        $model .= "            'status' => \$this->status\n";
        $model .= "        ];\n";
        $model .= "    }\n";
        $model .= "}\n";
        
        return $model;
    }
    
    private function generateAPI($tableName, $fields) {
        $api = "<?php\n";
        $api .= "// API endpoints for $tableName\n\n";
        
        // Include controller
        $api .= "require_once __DIR__ . '/{$tableName}Controller.php';\n\n";
        $api .= "\$controller = new {$tableName}Controller();\n\n";
        
        // Handle requests
        $api .= "switch (\$_SERVER['REQUEST_METHOD']) {\n";
        $api .= "    case 'GET':\n";
        $api .= "        if (isset(\$_GET['id'])) {\n";
        $api .= "            \$result = \$controller->read(\$_GET['id']);\n";
        $api .= "        } else {\n";
        $api .= "            \$result = \$controller->read();\n";
        $api .= "        }\n";
        $api .= "        break;\n\n";
        
        $api .= "    case 'POST':\n";
        $api .= "        \$data = json_decode(file_get_contents('php://input'), true);\n";
        $api .= "        \$result = \$controller->create(\$data);\n";
        $api .= "        break;\n\n";
        
        $api .= "    case 'PUT':\n";
        $api .= "        \$id = \$_GET['id'];\n";
        $api .= "        \$data = json_decode(file_get_contents('php://input'), true);\n";
        $api .= "        \$result = \$controller->update(\$id, \$data);\n";
        $api .= "        break;\n\n";
        
        $api .= "    case 'DELETE':\n";
        $api .= "        \$id = \$_GET['id'];\n";
        $api .= "        \$result = \$controller->delete(\$id);\n";
        $api .= "        break;\n\n";
        
        $api .= "    default:\n";
        $api .= "        http_response_code(405);\n";
        $api .= "        \$result = ['error' => 'Method not allowed'];\n";
        $api .= "}\n\n";
        
        $api .= "header('Content-Type: application/json');\n";
        $api .= "echo json_encode(\$result);\n";
        
        return $api;
    }
    
    private function generateTests($tableName, $fields) {
        $tests = "<?php\n";
        $tests .= "// Tests for $tableName\n\n";
        $tests .= "class {$tableName}Test extends PHPUnit\\Framework\\TestCase {\n";
        $tests .= "    private \$controller;\n\n";
        $tests .= "    protected function setUp(): void {\n";
        $tests .= "        \$this->controller = new {$tableName}Controller();\n";
        $tests .= "    }\n\n";
        
        // Test create
        $tests .= "    public function testCreate() {\n";
        $tests .= "        \$data = [\n";
        foreach ($fields as $field) {
            $tests .= "            '{$field['name']}' => 'test_value',\n";
        }
        $tests .= "        ];\n\n";
        $tests .= "        \$result = \$this->controller->create(\$data);\n\n";
        $tests .= "        \$this->assertIsInt(\$result);\n";
        $tests .= "        \$this->assertGreaterThan(0, \$result);\n";
        $tests .= "    }\n\n";
        
        // Test read
        $tests .= "    public function testRead() {\n";
        $tests .= "        \$result = \$this->controller->read();\n\n";
        $tests .= "        \$this->assertIsArray(\$result);\n";
        $tests .= "    }\n\n";
        
        $tests .= "    public function testReadById() {\n";
        $tests .= "        \$result = \$this->controller->read(1);\n\n";
        $tests .= "        \$this->assertIsArray(\$result);\n";
        $tests .= "    }\n\n";
        
        // Test update
        $tests .= "    public function testUpdate() {\n";
        $tests .= "        \$data = [\n";
        foreach ($fields as $field) {
            $tests .= "            '{$field['name']}' => 'updated_value',\n";
        }
        $tests .= "        ];\n\n";
        $tests .= "        \$result = \$this->controller->update(1, \$data);\n\n";
        $tests .= "        \$this->assertIsInt(\$result);\n";
        $tests .= "    }\n\n";
        
        // Test delete
        $tests .= "    public function testDelete() {\n";
        $tests .= "        \$result = \$this->controller->delete(1);\n\n";
        $tests .= "        \$this->assertIsInt(\$result);\n";
        $tests .= "    }\n";
        $tests .= "}\n";
        
        return $tests;
    }
}
```

## Usage Examples

### Create a new module
```
@sigep-workflow-automation create a new module called "relatorios" in the "coordenacao" sector with:
- Table: relatorios (id, titulo, descricao, tipo, created_at, updated_at, status)
- CRUD operations
- AdminLTE interface
- Menu integration
- Documentation
```

### Generate database migration
```
@sigep-workflow-automation create a database migration for adding a new table "auditoria" with columns: id, acao, tabela, registro_id, usuario_id, created_at
```

### Generate CRUD code
```
@sigep-workflow-automation generate complete CRUD code for table "usuarios" with fields: nome, email, senha, setor, status
```

### Create form template
```
@sigep-workflow-automation generate a form for table "internos" with fields: nome, prontuario, cpf, data_nascimento, unidade_id
```

### Generate JavaScript module
```
@sigep-workflow-automation generate JavaScript module for managing "movimentacoes" with AJAX operations
```

### Run database migration
```
@sigep-workflow-automation run migration "2024_03_25_120000_create_auditoria_table.php"
```

### Generate API endpoints
```
@sigep-workflow-automation generate REST API endpoints for table "escoltas" with full CRUD operations
```

## Workflow Automation Benefits

### 1. Time Savings
- Reduce repetitive coding by 80%
- Generate boilerplate code instantly
- Automate database setup
- Standardize project structure

### 2. Consistency
- Ensure consistent coding patterns
- Follow SIGEP standards automatically
- Maintain naming conventions
- Standardize file organization

### 3. Quality
- Generate secure code by default
- Include proper error handling
- Add validation automatically
- Include documentation

### 4. Maintainability
- Generate well-structured code
- Include comments and documentation
- Follow best practices
- Easy to understand and modify

## Best Practices

### 1. Workflow Design
- Keep workflows simple and focused
- Handle errors gracefully
- Provide clear feedback
- Log all operations

### 2. Code Generation
- Use templates for consistency
- Include proper validation
- Add comprehensive comments
- Follow coding standards

### 3. Database Operations
- Use transactions for complex operations
- Include rollback capabilities
- Log all changes
- Validate data integrity

### 4. File Operations
- Check permissions before writing
- Create backups before modifications
- Validate file structure
- Handle edge cases

## Resources and References

### SIGEP Documentation
- [Architecture Guide](../../../architecture/visao_geral.md)
- [Development Standards](../../../architecture/desenvolvimento.md)
- [Database Schema](../../../architecture/database/schema_completo.md)

### PHP Best Practices
- [PHP Standards](https://www.php-fig.org/)
- [Database Best Practices](https://www.php.net/manual/en/book.pdo.php)
- [Security Guidelines](https://www.php.net/manual/en/security.php)

### Automation Tools
- [Composer Scripts](https://getcomposer.org/doc/articles/scripts.md)
- [Phing Build Tool](https://www.phing.info/)
- [Robo Task Runner](https://robo.li/)
