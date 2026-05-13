---
name: sigep-mysql-operations
description: Provides safe MySQL database operations for SIGEP with proper error handling and security
---

# SIGEP MySQL Operations Skill

## Purpose
This skill provides standardized, secure MySQL database operations specifically designed for the SIGEP system, ensuring data integrity, security compliance, and proper error handling.

## Database Connection Standards

### Standard Connection Setup
```php
<?php
// Standard SIGEP database connection
function getSIGEPConnection() {
    try {
        $config = require __DIR__ . '/../../../conf/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '-03:00'");
        return $pdo;
    } catch (PDOException $e) {
        returnError('Database connection failed: ' . $e->getMessage(), 500);
    }
}
```

### Connection with Transaction Support
```php
<?php
function getSIGEPConnectionWithTransaction() {
    $pdo = getSIGEPConnection();
    $pdo->beginTransaction();
    return $pdo;
}
```

## CRUD Operations Templates

### Create Operations
```php
<?php
// Standard insert with proper validation
function createRecord($table, $data, $requiredFields = []) {
    $pdo = getSIGEPConnection();
    
    // Validate required fields
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            returnError("Required field '$field' is missing", 400);
        }
    }
    
    // Build dynamic query
    $fields = array_keys($data);
    $placeholders = str_repeat('?,', count($fields) - 1) . '?';
    
    $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ($placeholders)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        return [
            'success' => true,
            'data' => ['id' => $pdo->lastInsertId()],
            'message' => 'Record created successfully'
        ];
    } catch (PDOException $e) {
        returnError('Failed to create record: ' . $e->getMessage(), 500);
    }
}
```

### Read Operations
```php
<?php
// Standard select with pagination
function getRecords($table, $conditions = [], $orderBy = '', $limit = 50, $offset = 0) {
    $pdo = getSIGEPConnection();
    
    $sql = "SELECT * FROM $table";
    $params = [];
    
    // Add WHERE conditions
    if (!empty($conditions)) {
        $whereClauses = [];
        foreach ($conditions as $field => $value) {
            $whereClauses[] = "$field = ?";
            $params[] = $value;
        }
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    
    // Add ORDER BY
    if (!empty($orderBy)) {
        $sql .= " ORDER BY $orderBy";
    }
    
    // Add LIMIT and OFFSET
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        
        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total FROM $table";
        if (!empty($conditions)) {
            $countSql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute(array_slice($params, 0, -2));
        $total = $countStmt->fetch()['total'];
        
        return [
            'success' => true,
            'data' => $records,
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'pages' => ceil($total / $limit)
            ]
        ];
    } catch (PDOException $e) {
        returnError('Failed to retrieve records: ' . $e->getMessage(), 500);
    }
}
```

### Update Operations
```php
<?php
// Standard update with validation
function updateRecord($table, $id, $data, $idField = 'id') {
    $pdo = getSIGEPConnection();
    
    if (empty($id)) {
        returnError("Record ID is required for update operation", 400);
    }
    
    // Build dynamic SET clause
    $setClauses = [];
    $params = [];
    
    foreach ($data as $field => $value) {
        $setClauses[] = "$field = ?";
        $params[] = $value;
    }
    
    $params[] = $id; // Add ID for WHERE clause
    
    $sql = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE $idField = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $affected = $stmt->rowCount();
        if ($affected === 0) {
            returnError('No record found or no changes made', 404);
        }
        
        return [
            'success' => true,
            'message' => 'Record updated successfully',
            'affected_rows' => $affected
        ];
    } catch (PDOException $e) {
        returnError('Failed to update record: ' . $e->getMessage(), 500);
    }
}
```

### Delete Operations
```php
<?php
// Safe delete with confirmation
function deleteRecord($table, $id, $idField = 'id', $softDelete = false) {
    $pdo = getSIGEPConnection();
    
    if (empty($id)) {
        returnError("Record ID is required for delete operation", 400);
    }
    
    if ($softDelete) {
        // Soft delete - update status instead of deleting
        $sql = "UPDATE $table SET deleted_at = NOW(), status = 'deleted' WHERE $idField = ?";
    } else {
        // Hard delete
        $sql = "DELETE FROM $table WHERE $idField = ?";
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        $affected = $stmt->rowCount();
        if ($affected === 0) {
            returnError('No record found to delete', 404);
        }
        
        return [
            'success' => true,
            'message' => $softDelete ? 'Record soft-deleted successfully' : 'Record deleted successfully',
            'affected_rows' => $affected
        ];
    } catch (PDOException $e) {
        returnError('Failed to delete record: ' . $e->getMessage(), 500);
    }
}
```

## SIGEP-Specific Operations

### User Management
```php
<?php
// Create SIGEP user with proper validation
function createSIGEPUser($userData) {
    $requiredFields = ['nome', 'email', 'senha', 'setor_id'];
    
    // Validate email format
    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        returnError('Invalid email format', 400);
    }
    
    // Hash password
    $userData['senha'] = password_hash($userData['senha'], PASSWORD_DEFAULT);
    $userData['created_at'] = date('Y-m-d H:i:s');
    $userData['status'] = 'active';
    
    return createRecord('usuarios', $userData, $requiredFields);
}

// Authenticate user
function authenticateUser($email, $senha) {
    $pdo = getSIGEPConnection();
    
    $sql = "SELECT * FROM usuarios WHERE email = ? AND status = 'active'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($senha, $user['senha'])) {
        returnError('Invalid credentials', 401);
    }
    
    // Update last login
    $updateSql = "UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$user['id']]);
    
    unset($user['senha']); // Remove password from response
    
    return [
        'success' => true,
        'data' => $user,
        'message' => 'Authentication successful'
    ];
}
```

### Interno Management
```php
<?php
// Search internos with multiple criteria
function searchInternos($criteria = []) {
    $pdo = getSIGEPConnection();
    
    $sql = "SELECT i.*, u.nome as unidade_nome 
            FROM internos i 
            LEFT JOIN unidades u ON i.unidade_id = u.id 
            WHERE i.status = 'ativo'";
    
    $params = [];
    
    // Add search conditions
    if (!empty($criteria['nome'])) {
        $sql .= " AND i.nome LIKE ?";
        $params[] = '%' . $criteria['nome'] . '%';
    }
    
    if (!empty($criteria['prontuario'])) {
        $sql .= " AND i.prontuario = ?";
        $params[] = $criteria['prontuario'];
    }
    
    if (!empty($criteria['unidade_id'])) {
        $sql .= " AND i.unidade_id = ?";
        $params[] = $criteria['unidade_id'];
    }
    
    $sql .= " ORDER BY i.nome";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $internos = $stmt->fetchAll();
        
        return [
            'success' => true,
            'data' => $internos,
            'count' => count($internos)
        ];
    } catch (PDOException $e) {
        returnError('Failed to search internos: ' . $e->getMessage(), 500);
    }
}

// Update interno status
function updateInternoStatus($internoId, $status, $motivo = '') {
    $pdo = getSIGEPConnection();
    
    $validStatuses = ['ativo', 'inativo', 'transferido', 'liberado'];
    if (!in_array($status, $validStatuses)) {
        returnError('Invalid status value', 400);
    }
    
    $pdo->beginTransaction();
    
    try {
        // Update interno status
        $sql = "UPDATE internos SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$status, $internoId]);
        
        // Add to audit log
        $auditData = [
            'interno_id' => $internoId,
            'acao' => 'status_change',
            'valor_antigo' => getCurrentInternoStatus($pdo, $internoId),
            'valor_novo' => $status,
            'motivo' => $motivo,
            'usuario_id' => $_SESSION['user_id'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $auditSql = "INSERT INTO interno_audit (interno_id, acao, valor_antigo, valor_novo, motivo, usuario_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
        $auditStmt = $pdo->prepare($auditSql);
        $auditStmt->execute([
            $auditData['interno_id'],
            $auditData['acao'],
            $auditData['valor_antigo'],
            $auditData['valor_novo'],
            $auditData['motivo'],
            $auditData['usuario_id'],
            $auditData['created_at']
        ]);
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => 'Interno status updated successfully'
        ];
    } catch (PDOException $e) {
        $pdo->rollback();
        returnError('Failed to update interno status: ' . $e->getMessage(), 500);
    }
}
```

### Audit Operations
```php
<?php
// Log audit trail
function logAudit($action, $table, $recordId, $oldData = [], $newData = []) {
    $pdo = getSIGEPConnection();
    
    $auditData = [
        'acao' => $action,
        'tabela' => $table,
        'registro_id' => $recordId,
        'dados_antigos' => json_encode($oldData),
        'dados_novos' => json_encode($newData),
        'usuario_id' => $_SESSION['user_id'] ?? null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $sql = "INSERT INTO audit_log (acao, tabela, registro_id, dados_antigos, dados_novos, usuario_id, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $auditData['acao'],
            $auditData['tabela'],
            $auditData['registro_id'],
            $auditData['dados_antigos'],
            $auditData['dados_novos'],
            $auditData['usuario_id'],
            $auditData['ip_address'],
            $auditData['user_agent'],
            $auditData['created_at']
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Log error but don't fail the main operation
        error_log('Audit log failed: ' . $e->getMessage());
        return false;
    }
}

// Get audit trail for a record
function getAuditTrail($table, $recordId, $limit = 50) {
    $pdo = getSIGEPConnection();
    
    $sql = "SELECT a.*, u.nome as usuario_nome 
            FROM audit_log a 
            LEFT JOIN usuarios u ON a.usuario_id = u.id 
            WHERE a.tabela = ? AND a.registro_id = ? 
            ORDER BY a.created_at DESC 
            LIMIT ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table, $recordId, $limit]);
        $logs = $stmt->fetchAll();
        
        // Decode JSON data
        foreach ($logs as &$log) {
            $log['dados_antigos'] = json_decode($log['dados_antigos'], true) ?: [];
            $log['dados_novos'] = json_decode($log['dados_novos'], true) ?: [];
        }
        
        return [
            'success' => true,
            'data' => $logs,
            'count' => count($logs)
        ];
    } catch (PDOException $e) {
        returnError('Failed to retrieve audit trail: ' . $e->getMessage(), 500);
    }
}
```

## Utility Functions

### Error Handling
```php
<?php
// Standard error response function
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Standard success response function
function returnSuccess($data = null, $message = 'Operation successful') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
```

### Data Validation
```php
<?php
// Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate CPF (Brazilian ID)
function validateCPF($cpf) {
    // Remove non-numeric characters
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    // Check if has 11 digits
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Check for known invalid CPFs
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    // Validate CPF digits (simplified version)
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Sanitize input data
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
```

## Performance Optimization

### Connection Pooling
```php
<?php
// Simple connection pool for better performance
class ConnectionPool {
    private static $connections = [];
    private static $maxConnections = 10;
    
    public static function getConnection() {
        if (count(self::$connections) < self::$maxConnections) {
            $connection = getSIGEPConnection();
            self::$connections[] = $connection;
            return $connection;
        }
        
        // Reuse existing connection
        return array_shift(self::$connections);
    }
    
    public static function releaseConnection($connection) {
        if (count(self::$connections) < self::$maxConnections) {
            self::$connections[] = $connection;
        }
    }
}
```

### Query Optimization
```php
<?php
// Optimized query for large datasets
function getOptimizedRecords($table, $conditions = [], $page = 1, $pageSize = 50) {
    $offset = ($page - 1) * $pageSize;
    
    // Use index hints if available
    $sql = "SELECT * FROM $table USE INDEX (PRIMARY)";
    
    // Add conditions
    if (!empty($conditions)) {
        $whereClauses = [];
        foreach ($conditions as $field => $value) {
            $whereClauses[] = "$field = ?";
        }
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }
    
    $sql .= " LIMIT ? OFFSET ?";
    
    $pdo = getSIGEPConnection();
    $stmt = $pdo->prepare($sql);
    
    $params = array_values($conditions);
    $params[] = $pageSize;
    $params[] = $offset;
    
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

## Security Best Practices

### SQL Injection Prevention
```php
<?php
// Always use prepared statements
function safeQuery($sql, $params = []) {
    $pdo = getSIGEPConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Never do this:
// $sql = "SELECT * FROM users WHERE id = " . $_GET['id'];

// Always do this:
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

### Data Encryption
```php
<?php
// Encrypt sensitive data
function encryptData($data, $key) {
    $method = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

// Decrypt sensitive data
function decryptData($encryptedData, $key) {
    $method = 'AES-256-CBC';
    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}
```

## Usage Examples

### Basic CRUD Operations
```
@sigep-mysql-operations create a new user with the following data:
- name: João Silva
- email: joao@exemplo.com
- password: senha123
- sector: TI
```

### Complex Queries
```
@sigep-mysql-operations search for internos with these criteria:
- name contains "Silva"
- unit_id: 5
- status: active
- limit: 20 results
```

### Audit Operations
```
@sigep-mysql-operations get audit trail for user ID 123 in the usuarios table
```

### Security Validation
```
@sigep-mysql-operations validate this SQL query for security risks:
SELECT * FROM users WHERE name = 'admin'
```

## Troubleshooting

### Common Issues
1. **Connection timeout** - Check database server status
2. **Query syntax error** - Validate SQL syntax
3. **Permission denied** - Check database user permissions
4. **Data truncation** - Check field sizes and data types
5. **Transaction deadlock** - Review transaction logic

### Debug Mode
```php
<?php
// Enable debug mode for development
define('SIGEP_DEBUG', true);

if (SIGEP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Log all queries
    $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, ['PDOStatement']);
}
```

## Maintenance

### Regular Tasks
- [ ] Monitor database performance
- [ ] Optimize slow queries
- [ ] Update connection parameters
- [ ] Review audit logs
- [ ] Backup critical data

### Health Checks
```php
<?php
function databaseHealthCheck() {
    try {
        $pdo = getSIGEPConnection();
        
        // Test basic connectivity
        $stmt = $pdo->query("SELECT 1");
        
        // Check table sizes
        $tables = $pdo->query("SHOW TABLE STATUS")->fetchAll();
        
        // Check connection count
        $connections = $pdo->query("SHOW STATUS LIKE 'Threads_connected'")->fetch();
        
        return [
            'status' => 'healthy',
            'tables' => count($tables),
            'connections' => $connections['Value'] ?? 0,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
```

## Integration with SIGEP

### Session Integration
```php
<?php
// Ensure session is started for all operations
function ensureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validate user session
    if (!isset($_SESSION['user_id'])) {
        returnError('User session not found', 401);
    }
}
```

### Permission Checking
```php
<?php
// Check user permissions for operations
function checkPermission($permission) {
    ensureSession();
    
    $userPermissions = $_SESSION['permissoes'] ?? [];
    
    if (!in_array($permission, $userPermissions)) {
        returnError('Insufficient permissions', 403);
    }
    
    return true;
}
```

## Resources and References

### SIGEP Database Documentation
- [Database Schema](../../../architecture/database/schema_completo.md)
- [Security Guidelines](../../../architecture/security/seguranca_completa.md)
- [Migration Scripts](../../../database/migrations/)

### External Resources
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [Database Security Best Practices](https://owasp.org/www-project-cheat-sheets/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html)
