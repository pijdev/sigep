---
name: sigep-php-validator
description: Validates PHP code according to SIGEP standards and best practices
---

# SIGEP PHP Validator Skill

## Purpose
This skill validates PHP code to ensure it follows SIGEP project standards, security practices, and coding conventions.

## Validation Checklist

### 1. Security Requirements
- [ ] All database queries use PDO prepared statements
- [ ] User input is properly sanitized and validated
- [ ] Session validation is present before sensitive operations
- [ ] SQL injection prevention measures are in place
- [ ] XSS prevention with proper output escaping

### 2. SIGEP Architecture Compliance
- [ ] Follows MVC pattern (view/logica separation)
- [ ] Uses proper file naming conventions
- [ ] Includes required headers and session checks
- [ ] Follows established database connection patterns
- [ ] Maintains UTF-8 charset consistency

### 3. Code Quality Standards
- [ ] Proper error handling with try-catch blocks
- [ ] Consistent indentation and formatting
- [ ] Meaningful variable and function names
- [ ] Adequate comments for complex logic
- [ ] No hardcoded credentials or paths

### 4. Database Best Practices
- [ ] Uses utf8mb4 charset for all operations
- [ ] Implements proper transaction handling when needed
- [ ] Includes proper error handling for database operations
- [ ] Uses appropriate fetch modes (PDO::FETCH_ASSOC)
- [ ] Closes database connections properly

### 5. Frontend Integration
- [ ] Returns proper JSON responses with correct headers
- [ ] Handles AJAX requests appropriately
- [ ] Maintains AdminLTE compatibility
- [ ] Uses proper HTTP status codes
- [ ] Implements proper error messaging

## Common Issues to Check

### Security Vulnerabilities
```php
// ❌ BAD - Direct SQL injection
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];

// ✅ GOOD - Prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);
```

### Session Management
```php
// ❌ BAD - No session validation
function deleteUser($userId) {
    // Direct database operation
}

// ✅ GOOD - Proper session validation
function deleteUser($userId) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        returnError('Unauthorized', 401);
    }
    // Database operation
}
```

### Error Handling
```php
// ❌ BAD - No error handling
$result = $pdo->query("SELECT * FROM users");

// ✅ GOOD - Proper error handling
try {
    $result = $pdo->query("SELECT * FROM users");
} catch (PDOException $e) {
    returnError('Database error: ' . $e->getMessage(), 500);
}
```

## Validation Commands

### Basic Syntax Check
```bash
php -l filename.php
```

### Code Style Validation
```bash
# Check for common issues
grep -n "mysqli_" filename.php  # Should use PDO
grep -n "$_GET" filename.php    # Check for unsanitized input
grep -n "echo.*$_" filename.php # Check for unescaped output
```

## SIGEP Specific Patterns

### Standard Database Connection
```php
$config = require __DIR__ . '/../../../conf/db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec("SET time_zone = '-03:00'");
```

### Standard Error Response
```php
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}
```

### Standard JSON Response
```php
function returnSuccess($data, $message = 'Success') {
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}
```

## File Structure Validation

### Module Structure Check
```
modulos/[setor]/[modulo]/
├── [modulo]_view.php      # ✅ Must exist
├── [modulo]_logica.php    # ✅ Must exist
├── assets/
│   ├── css/[modulo].css   # ✅ Should exist
│   └── js/[modulo].js     # ✅ Should exist
└── README.md              # ✅ Optional but recommended
```

### Required Headers
```php
<?php
// Required for all logica files
session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Required JSON headers
header('Content-Type: application/json; charset=utf-8');

// Error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Timezone
date_default_timezone_set('America/Sao_Paulo');
```

## Testing Recommendations

### Unit Testing Structure
```php
// Example test for database operations
public function testUserCreation() {
    $testData = [
        'name' => 'Test User',
        'email' => 'test@example.com'
    ];
    
    $result = $this->createUser($testData);
    $this->assertTrue($result['success']);
    $this->assertNotEmpty($result['data']['id']);
}
```

### Integration Testing
```php
// Test complete workflow
public function testCompleteUserWorkflow() {
    // 1. Create user
    // 2. Update user
    // 3. Delete user
    // 4. Verify audit trail
}
```

## Performance Considerations

### Database Optimization
- [ ] Use appropriate indexes
- [ ] Avoid N+1 queries
- [ ] Implement proper pagination
- [ ] Use connection pooling when possible
- [ ] Cache frequently accessed data

### Code Optimization
- [ ] Avoid unnecessary database calls in loops
- [ ] Use efficient algorithms
- [ ] Implement proper caching strategies
- [ ] Minimize memory usage
- [ ] Optimize file I/O operations

## Documentation Requirements

### Function Documentation
```php
/**
 * Creates a new user in the system
 * 
 * @param array $userData User data including name, email, etc.
 * @return array Result with success status and user ID
 * @throws PDOException When database operation fails
 */
function createUser($userData) {
    // Implementation
}
```

### Inline Comments
```php
// Validate user input before database insertion
if (empty($userData['email']) || !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
    returnError('Invalid email format');
}

// Insert user with prepared statement to prevent SQL injection
$stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
```

## Resources and References

### SIGEP Documentation
- [SIGEP Architecture Guide](../../../architecture/visao_geral.md)
- [Database Schema](../../../architecture/database/schema_completo.md)
- [Security Guidelines](../../../architecture/security/seguranca_completa.md)

### PHP Best Practices
- [PHP Security Guidelines](https://www.php.net/manual/en/security.php)
- [PDO Documentation](https://www.php.net/manual/en/book.pdo.php)
- [JSON Best Practices](https://www.php.net/manual/en/json.php)

## Usage Examples

### Basic Validation
```
@sigep-php-validator validate this PHP file for SIGEP compliance
```

### Security Check
```
@sigep-php-validator check for security vulnerabilities in this code
```

### Architecture Review
```
@sigep-php-validator review this module for SIGEP MVC compliance
```

## Troubleshooting

### Common Validation Failures
1. **Missing session validation** - Add proper session checks
2. **Direct SQL queries** - Convert to prepared statements
3. **Missing error handling** - Add try-catch blocks
4. **Incorrect headers** - Set proper JSON headers
5. **Hardcoded paths** - Use relative paths or configuration

### Performance Issues
1. **Slow queries** - Check database indexes
2. **Memory leaks** - Monitor memory usage
3. **Inefficient loops** - Optimize algorithm complexity
4. **Excessive database calls** - Implement caching

## Maintenance

### Regular Tasks
- [ ] Update validation rules as standards evolve
- [ ] Review new PHP version compatibility
- [ ] Update security guidelines
- [ ] Maintain documentation currency

### Version Control
- [ ] Tag skill versions for different SIGEP releases
- [ ] Maintain backward compatibility notes
- [ ] Document breaking changes
- [ ] Keep changelog updated
