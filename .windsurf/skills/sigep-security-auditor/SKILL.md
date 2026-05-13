---
name: sigep-security-auditor
description: Performs comprehensive security audits on SIGEP code including SQL injection, XSS, authentication, and permission checks
---

# SIGEP Security Auditor Skill

## Purpose
This skill performs comprehensive security audits on SIGEP code, identifying vulnerabilities, security anti-patterns, and ensuring compliance with security best practices for prison management systems.

## Security Checklist

### 1. SQL Injection Prevention
- [ ] All database queries use prepared statements
- [ ] No direct variable interpolation in SQL
- [ ] Input validation before database operations
- [ ] Proper error handling without exposing database structure

### 2. Cross-Site Scripting (XSS) Prevention
- [ ] All user output is properly escaped
- [ ] Content-Type headers set correctly
- [ ] No unescaped user input in HTML
- [ ] CSP headers implemented where possible

### 3. Authentication & Authorization
- [ ] Session validation before sensitive operations
- [ ] Proper password hashing (bcrypt/argon2)
- [ ] Session timeout implementation
- [ ] Secure session configuration

### 4. Access Control
- [ ] Permission checks for all operations
- [ ] Role-based access control implementation
- [ ] Least privilege principle followed
- [ ] Authorization bypass prevention

### 5. Data Protection
- [ ] Sensitive data encryption at rest
- [ ] HTTPS enforcement for sensitive operations
- [ ] Input sanitization and validation
- [ ] Secure file upload handling

## Vulnerability Detection Patterns

### SQL Injection Patterns
```php
// ❌ VULNERABLE - Direct interpolation
$sql = "SELECT * FROM users WHERE id = " . $_GET['id'];
$sql = "SELECT * FROM users WHERE name = '$name'";

// ❌ VULNERABLE - String concatenation
$sql = "SELECT * FROM users WHERE name = '" . $_POST['name'] . "'";

// ❌ VULNERABLE - sprintf without proper escaping
$sql = sprintf("SELECT * FROM users WHERE id = %d", $_GET['id']);

// ✅ SECURE - Prepared statements
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_GET['id']]);

// ✅ SECURE - Named parameters
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_GET['id']]);
```

### XSS Prevention Patterns
```php
// ❌ VULNERABLE - Direct output
echo $_GET['message'];
echo $user_input;

// ❌ VULNERABLE - HTML context
$html = "<div>" . $_GET['message'] . "</div>";

// ✅ SECURE - Proper escaping
echo htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8');

// ✅ SECURE - JSON context
header('Content-Type: application/json');
echo json_encode(['message' => $_GET['message']]);

// ✅ SECURE - Template engine
$template->render('page', ['message' => htmlspecialchars($_GET['message'])]);
```

### Authentication Patterns
```php
// ❌ VULNERABLE - No session check
function deleteUser($userId) {
    // Direct database operation without authentication
}

// ❌ VULNERABLE - Weak session validation
if ($_SESSION['logged_in']) {
    // Weak authentication check
}

// ✅ SECURE - Proper session validation
function deleteUser($userId) {
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_authenticated'])) {
        http_response_code(401);
        die('Unauthorized');
    }
    
    // Additional validation
    if ($_SESSION['last_activity'] < time() - 1800) {
        session_destroy();
        die('Session expired');
    }
    
    // Proceed with operation
}

// ✅ SECURE - Comprehensive authentication
function requireAuthentication() {
    session_start();
    
    // Check session exists
    if (!isset($_SESSION['user_id'])) {
        redirectToLogin();
    }
    
    // Check session validity
    if (!isset($_SESSION['session_token']) || $_SESSION['session_token'] !== getSessionToken()) {
        session_destroy();
        redirectToLogin();
    }
    
    // Check session timeout
    $timeout = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        session_destroy();
        redirectToLogin();
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}
```

### Permission Check Patterns
```php
// ❌ VULNERABLE - No permission check
function deleteAllUsers() {
    // Anyone can call this function
}

// ❌ VULNERABLE - Hardcoded admin check
if ($_SESSION['user_role'] === 'admin') {
    // Weak permission check
}

// ✅ SECURE - Proper permission validation
function deleteUser($userId) {
    requireAuthentication();
    
    // Check specific permission
    if (!hasPermission('user.delete')) {
        http_response_code(403);
        die('Insufficient permissions');
    }
    
    // Check ownership or additional constraints
    if (!canDeleteUser($userId)) {
        http_response_code(403);
        die('Cannot delete this user');
    }
    
    // Proceed with operation
}

// ✅ SECURE - Permission system
function hasPermission($permission) {
    $userPermissions = $_SESSION['permissions'] ?? [];
    return in_array($permission, $userPermissions);
}

function canDeleteUser($userId) {
    // User can delete themselves
    if ($userId === $_SESSION['user_id']) {
        return true;
    }
    
    // Admin can delete anyone
    if (hasPermission('user.delete.all')) {
        return true;
    }
    
    // Manager can delete users in their department
    if (hasPermission('user.delete.department')) {
        return isUserInSameDepartment($userId);
    }
    
    return false;
}
```

## Security Audit Functions

### Code Scanner
```php
<?php
class SIGEPSecurityAuditor {
    private $vulnerabilities = [];
    private $fileContents = [];
    
    public function auditFile($filePath) {
        $this->fileContents = file_get_contents($filePath);
        $this->vulnerabilities = [];
        
        // Check for SQL injection patterns
        $this->checkSQLInjection($filePath);
        
        // Check for XSS patterns
        $this->checkXSS($filePath);
        
        // Check for authentication issues
        $this->checkAuthentication($filePath);
        
        // Check for permission issues
        $this->checkPermissions($filePath);
        
        // Check for hardcoded credentials
        $this->checkHardcodedCredentials($filePath);
        
        // Check for insecure file operations
        $this->checkFileOperations($filePath);
        
        return $this->generateReport();
    }
    
    private function checkSQLInjection($filePath) {
        $patterns = [
            '/\$\w+\s*\.\s*\$_GET\[[\'"][^\'"]+[\'"]\]/',
            '/\$\w+\s*\.\s*\$_POST\[[\'"][^\'"]+[\'"]\]/',
            '/\$\w+\s*\.\s*\$_REQUEST\[[\'"][^\'"]+[\'"]\]/',
            '/mysqli_query\s*\(\s*["\'][^"\']*["\'][^)]*\)/',
            '/mysql_query\s*\(\s*["\'][^"\']*["\'][^)]*\)/',
            '/sprintf\s*\([^)]*\$\w+\)/',
            '/echo\s*["\'][^"\']*SELECT[^"\']*["\']/',
            '/SELECT\s+.*\s+FROM\s+.*WHERE\s+.*\$\w+/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $this->fileContents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = $this->getLineNumber($match[1]);
                    $this->addVulnerability(
                        'SQL_INJECTION',
                        $filePath,
                        $line,
                        'Potential SQL injection vulnerability',
                        $match[0]
                    );
                }
            }
        }
    }
    
    private function checkXSS($filePath) {
        $patterns = [
            '/echo\s*\$\w+/',
            '/print\s*\$\w+/',
            '/<\?=\s*\$\w+/',
            '/htmlspecialchars_decode\s*\(/',
            '/strip_tags\s*\(\s*\$\w+/',
            '/<script[^>]*>.*?<\/script>/is',
            '/on\w+\s*=\s*["\'][^"\']*["\'][^>]*>/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $this->fileContents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = $this->getLineNumber($match[1]);
                    $this->addVulnerability(
                        'XSS',
                        $filePath,
                        $line,
                        'Potential XSS vulnerability',
                        $match[0]
                    );
                }
            }
        }
    }
    
    private function checkAuthentication($filePath) {
        // Check for functions that should have authentication
        $sensitiveFunctions = [
            'deleteUser',
            'updateUser',
            'createUser',
            'deleteRecord',
            'updateRecord',
            'adminPanel',
            'dashboard'
        ];
        
        foreach ($sensitiveFunctions as $function) {
            if (preg_match("/function\s+$function\s*\(/", $this->fileContents, $matches, PREG_OFFSET_CAPTURE)) {
                // Check if function has authentication check
                $functionStart = $matches[0][1];
                $functionEnd = strpos($this->fileContents, '}', $functionStart);
                $functionCode = substr($this->fileContents, $functionStart, $functionEnd - $functionStart);
                
                if (!preg_match('/session_start|isset\(\$_SESSION|requireAuthentication/', $functionCode)) {
                    $line = $this->getLineNumber($functionStart);
                    $this->addVulnerability(
                        'MISSING_AUTHENTICATION',
                        $filePath,
                        $line,
                        "Function '$function' lacks authentication check",
                        $matches[0][0]
                    );
                }
            }
        }
    }
    
    private function checkPermissions($filePath) {
        // Check for database operations without permission checks
        $dbOperations = [
            '/DELETE\s+FROM/i',
            '/UPDATE\s+.*SET/i',
            '/INSERT\s+INTO/i'
        ];
        
        foreach ($dbOperations as $operation) {
            if (preg_match_all($operation, $this->fileContents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = $this->getLineNumber($match[1]);
                    
                    // Check if there's a permission check nearby
                    $contextStart = max(0, $match[1] - 200);
                    $context = substr($this->fileContents, $contextStart, 400);
                    
                    if (!preg_match('/hasPermission|checkPermission|canDelete|canUpdate|canCreate/', $context)) {
                        $this->addVulnerability(
                            'MISSING_PERMISSION_CHECK',
                            $filePath,
                            $line,
                            'Database operation without permission check',
                            $match[0]
                        );
                    }
                }
            }
        }
    }
    
    private function checkHardcodedCredentials($filePath) {
        $patterns = [
            '/password\s*=\s*["\'][^"\']+["\']/',
            '/secret\s*=\s*["\'][^"\']+["\']/',
            '/api_key\s*=\s*["\'][^"\']+["\']/',
            '/database_password\s*=\s*["\'][^"\']+["\']/',
            '/db_pass\s*=\s*["\'][^"\']+["\']/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $this->fileContents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = $this->getLineNumber($match[1]);
                    $this->addVulnerability(
                        'HARDCODED_CREDENTIALS',
                        $filePath,
                        $line,
                        'Hardcoded credentials found',
                        $match[0]
                    );
                }
            }
        }
    }
    
    private function checkFileOperations($filePath) {
        $patterns = [
            '/file_get_contents\s*\(\s*\$\w+\s*\)/',
            '/file_put_contents\s*\(\s*\$\w+\s*\)/',
            '/fopen\s*\(\s*\$\w+\s*,/',
            '/unlink\s*\(\s*\$\w+\s*\)/',
            '/include\s*\(\s*\$\w+\s*\)/',
            '/require\s*\(\s*\$\w+\s*\)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $this->fileContents, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = $this->getLineNumber($match[1]);
                    
                    // Check if there's input validation
                    $contextStart = max(0, $match[1] - 100);
                    $context = substr($this->fileContents, $contextStart, 200);
                    
                    if (!preg_match('/basename|realpath|validate|filter_var|preg_match/', $context)) {
                        $this->addVulnerability(
                            'UNSAFE_FILE_OPERATION',
                            $filePath,
                            $line,
                            'Unsafe file operation without validation',
                            $match[0]
                        );
                    }
                }
            }
        }
    }
    
    private function addVulnerability($type, $file, $line, $description, $code) {
        $this->vulnerabilities[] = [
            'type' => $type,
            'file' => $file,
            'line' => $line,
            'description' => $description,
            'code' => $code,
            'severity' => $this->getSeverity($type)
        ];
    }
    
    private function getSeverity($type) {
        $severityMap = [
            'SQL_INJECTION' => 'CRITICAL',
            'XSS' => 'CRITICAL',
            'MISSING_AUTHENTICATION' => 'HIGH',
            'MISSING_PERMISSION_CHECK' => 'HIGH',
            'HARDCODED_CREDENTIALS' => 'CRITICAL',
            'UNSAFE_FILE_OPERATION' => 'MEDIUM'
        ];
        
        return $severityMap[$type] ?? 'MEDIUM';
    }
    
    private function getLineNumber($offset) {
        $lines = explode("\n", substr($this->fileContents, 0, $offset));
        return count($lines);
    }
    
    private function generateReport() {
        $report = [
            'summary' => [
                'total_vulnerabilities' => count($this->vulnerabilities),
                'critical' => count(array_filter($this->vulnerabilities, fn($v) => $v['severity'] === 'CRITICAL')),
                'high' => count(array_filter($this->vulnerabilities, fn($v) => $v['severity'] === 'HIGH')),
                'medium' => count(array_filter($this->vulnerabilities, fn($v) => $v['severity'] === 'MEDIUM')),
                'low' => count(array_filter($this->vulnerabilities, fn($v) => $v['severity'] === 'LOW'))
            ],
            'vulnerabilities' => $this->vulnerabilities
        ];
        
        return $report;
    }
}
```

### Security Testing Functions
```php
<?php
// Security testing utilities
class SIGEPSecurityTester {
    
    // Test SQL injection resistance
    public function testSQLInjection($url, $params = []) {
        $maliciousInputs = [
            "' OR '1'='1",
            "'; DROP TABLE users; --",
            "1' UNION SELECT password FROM users --",
            "admin'--",
            "' OR 1=1#"
        ];
        
        $results = [];
        
        foreach ($maliciousInputs as $input) {
            $testParams = array_merge($params, ['id' => $input]);
            
            $response = $this->makeRequest($url, $testParams);
            
            if ($this->containsSQLError($response)) {
                $results[] = [
                    'input' => $input,
                    'vulnerable' => true,
                    'response' => substr($response, 0, 200)
                ];
            } else {
                $results[] = [
                    'input' => $input,
                    'vulnerable' => false,
                    'response' => 'No error detected'
                ];
            }
        }
        
        return $results;
    }
    
    // Test XSS resistance
    public function testXSS($url, $params = []) {
        $maliciousInputs = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '"><script>alert("XSS")</script>',
            'javascript:alert("XSS")',
            '<svg onload=alert("XSS")>'
        ];
        
        $results = [];
        
        foreach ($maliciousInputs as $input) {
            $testParams = array_merge($params, ['message' => $input]);
            
            $response = $this->makeRequest($url, $testParams);
            
            if (strpos($response, $input) !== false) {
                $results[] = [
                    'input' => $input,
                    'vulnerable' => true,
                    'response' => substr($response, 0, 200)
                ];
            } else {
                $results[] = [
                    'input' => $input,
                    'vulnerable' => false,
                    'response' => 'No reflection detected'
                ];
            }
        }
        
        return $results;
    }
    
    // Test authentication bypass
    public function testAuthenticationBypass($url) {
        $tests = [
            'no_session' => $this->makeRequest($url, [], 'GET', false),
            'invalid_session' => $this->makeRequest($url, ['session_id' => 'invalid'], 'GET', false),
            'empty_session' => $this->makeRequest($url, ['session_id' => ''], 'GET', false)
        ];
        
        $results = [];
        
        foreach ($tests as $testName => $response) {
            $results[$testName] = [
                'status_code' => $this->getStatusCode($response),
                'vulnerable' => $this->getStatusCode($response) !== 401,
                'response' => substr($response, 0, 200)
            ];
        }
        
        return $results;
    }
    
    // Test authorization bypass
    public function testAuthorizationBypass($url, $userId) {
        $tests = [
            'direct_access' => $this->makeRequest($url, ['user_id' => $userId], 'GET'),
            'role_manipulation' => $this->makeRequest($url, ['user_id' => $userId, 'role' => 'admin'], 'GET'),
            'permission_override' => $this->makeRequest($url, ['user_id' => $userId, 'admin' => 'true'], 'GET')
        ];
        
        $results = [];
        
        foreach ($tests as $testName => $response) {
            $results[$testName] = [
                'status_code' => $this->getStatusCode($response),
                'vulnerable' => $this->getStatusCode($response) !== 403,
                'response' => substr($response, 0, 200)
            ];
        }
        
        return $results;
    }
    
    private function makeRequest($url, $params = [], $method = 'POST', $withSession = true) {
        $ch = curl_init();
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            $url .= '?' . http_build_query($params);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        if ($withSession && isset($_COOKIE['PHPSESSID'])) {
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . $_COOKIE['PHPSESSID']);
        }
        
        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return $response;
    }
    
    private function getStatusCode($response) {
        // Extract status code from response if available
        if (preg_match('/HTTP\/\d+\s+(\d+)/', $response, $matches)) {
            return (int)$matches[1];
        }
        return 200;
    }
    
    private function containsSQLError($response) {
        $sqlErrors = [
            'SQL syntax',
            'mysql_fetch',
            'mysqli_fetch',
            'PDOException',
            'SQLSTATE',
            'column not found',
            'table doesn\'t exist'
        ];
        
        foreach ($sqlErrors as $error) {
            if (stripos($response, $error) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
```

## Security Recommendations

### Immediate Actions Required

#### Critical Vulnerabilities
1. **SQL Injection** - Replace all direct SQL concatenation with prepared statements
2. **XSS** - Escape all user output before displaying
3. **Hardcoded Credentials** - Move all credentials to environment variables
4. **Missing Authentication** - Add session validation to all sensitive functions

#### High Priority Issues
1. **Missing Permission Checks** - Implement proper authorization
2. **Unsafe File Operations** - Add input validation for file operations
3. **Session Security** - Implement proper session management
4. **Error Information Disclosure** - Remove sensitive data from error messages

### Code Templates for Fixes

#### Secure Database Operations
```php
<?php
// Secure database operation template
function secureDatabaseOperation($operation, $table, $data = [], $conditions = []) {
    requireAuthentication();
    
    if (!hasPermission($operation)) {
        returnError('Insufficient permissions', 403);
    }
    
    $pdo = getDatabaseConnection();
    
    switch ($operation) {
        case 'create':
            $sql = "INSERT INTO $table (";
            $sql .= implode(', ', array_keys($data));
            $sql .= ") VALUES (";
            $sql .= str_repeat('?,', count($data) - 1) . '?)';
            break;
            
        case 'read':
            $sql = "SELECT * FROM $table";
            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $field => $value) {
                    $whereClauses[] = "$field = ?";
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            break;
            
        case 'update':
            $sql = "UPDATE $table SET ";
            $setClauses = [];
            foreach ($data as $field => $value) {
                $setClauses[] = "$field = ?";
            }
            $sql .= implode(', ', $setClauses);
            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $field => $value) {
                    $whereClauses[] = "$field = ?";
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            break;
            
        case 'delete':
            $sql = "DELETE FROM $table";
            if (!empty($conditions)) {
                $whereClauses = [];
                foreach ($conditions as $field => $value) {
                    $whereClauses[] = "$field = ?";
                }
                $sql .= " WHERE " . implode(' AND ', $whereClauses);
            }
            break;
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $params = array_merge(array_values($data), array_values($conditions));
        $stmt->execute($params);
        
        return [
            'success' => true,
            'message' => 'Operation completed successfully'
        ];
    } catch (PDOException $e) {
        returnError('Database operation failed', 500);
    }
}
```

#### Secure Input Handling
```php
<?php
// Secure input validation template
function secureInput($input, $type = 'string', $options = []) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL);
            
        case 'integer':
            return filter_var($input, FILTER_VALIDATE_INT);
            
        case 'boolean':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
            
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL);
            
        case 'string':
        default:
            $input = trim($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            // Additional validation based on options
            if (isset($options['min_length']) && strlen($input) < $options['min_length']) {
                throw new InvalidArgumentException("Input too short");
            }
            
            if (isset($options['max_length']) && strlen($input) > $options['max_length']) {
                throw new InvalidArgumentException("Input too long");
            }
            
            if (isset($options['pattern']) && !preg_match($options['pattern'], $input)) {
                throw new InvalidArgumentException("Invalid input format");
            }
            
            return $input;
    }
}
```

#### Secure Session Management
```php
<?php
// Secure session management template
function secureSessionStart() {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    
    session_start();
    
    // Regenerate session ID to prevent fixation
    if (!isset($_SESSION['initialized'])) {
        session_regenerate_id(true);
        $_SESSION['initialized'] = true;
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate session
    if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        session_destroy();
        throw new SecurityException('Session hijacking detected');
    }
    
    // Check session timeout
    $timeout = 1800; // 30 minutes
    if (time() - $_SESSION['last_activity'] > $timeout) {
        session_destroy();
        throw new SecurityException('Session expired');
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
}

function getSessionToken() {
    return $_SESSION['session_token'] ?? '';
}

function validateSessionToken($token) {
    return hash_equals($_SESSION['session_token'] ?? '', $token);
}
```

## Usage Examples

### Audit a single file
```
@sigep-security-auditor audit the file modulos/censura/cartas/censura_cartas_logica.php for security vulnerabilities
```

### Audit entire directory
```
@sigep-security-auditor scan all PHP files in the modulos directory for security issues
```

### Test SQL injection resistance
```
@sigep-security-auditor test the user management API for SQL injection vulnerabilities
```

### Test XSS resistance
```
@sigep-security-auditor test the message posting functionality for XSS vulnerabilities
```

### Comprehensive security test
```
@sigep-security-auditor perform a complete security audit of the authentication system including:
1. Session management
2. Permission checking
3. Password handling
4. Token validation
5. Logout functionality
```

## Security Best Practices Checklist

### Development
- [ ] Use prepared statements for all database queries
- [ ] Escape all user output
- [ ] Validate all input data
- [ ] Implement proper error handling
- [ ] Use HTTPS for all communications
- [ ] Implement proper session management
- [ ] Use secure password hashing

### Configuration
- [ ] Disable error display in production
- [ ] Use secure session configuration
- [ ] Implement proper file permissions
- [ ] Use environment variables for credentials
- [ ] Enable security headers
- [ ] Implement rate limiting
- [ ] Use secure cookie settings

### Deployment
- [ ] Keep all software updated
- [ ] Use web application firewall
- [ ] Implement intrusion detection
- [ ] Regular security audits
- [ ] Monitor access logs
- [ ] Backup security configurations
- [ ] Test for common vulnerabilities
- [ ] Document security procedures

## Resources and References

### Security Documentation
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Guidelines](https://www.php.net/manual/en/security.php)
- [MySQL Security](https://dev.mysql.com/doc/refman/8.0/en/security.html)
- [AdminLTE Security](https://adminlte.io/docs/3.2/security/)

### Security Tools
- [OWASP ZAP](https://www.zaproxy.org/)
- [Burp Suite](https://portswigger.net/burp/)
- [Nmap](https://nmap.org/)
- [Nikto](https://cirt.net/nikto2/)

### SIGEP Security Policies
- [Security Guidelines](../../../architecture/security/seguranca_completa.md)
- [Database Security](../../../architecture/database/security.md)
- [Access Control](../../../architecture/security/acesso.md)
