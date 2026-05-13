---
name: sigep-debug-helper
description: Comprehensive debugging and troubleshooting tools for SIGEP development with error analysis, performance monitoring, and system diagnostics
---

# SIGEP Debug Helper Skill

## Purpose

This skill provides comprehensive debugging and troubleshooting capabilities for SIGEP development, including error analysis, performance monitoring, system diagnostics, and development assistance tools.

## Debugging Categories

### 1. PHP Error Analysis

- Parse PHP error messages
- Identify syntax errors
- Analyze runtime exceptions
- Debug database connection issues
- Trace function call stacks

### 2. Performance Monitoring

- Database query analysis
- Memory usage tracking
- Execution time measurement
- Resource utilization monitoring
- Bottleneck identification

### 3. System Diagnostics

- Server configuration checks
- File permission verification
- Database connectivity tests
- Module integration validation
- Environment variable analysis

### 4. Application Debugging

- Session state analysis
- User permission verification
- Request flow tracing
- Component interaction debugging
- Data flow validation

## Debug Tools and Functions

### PHP Error Analyzer

```php
<?php
class SIGEPDebugger {
    private $logs = [];
    private $startTime;
    private $memoryStart;

    public function __construct() {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);

        // Set error handler
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);

        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 0); // SIGEP: Don't display errors in production
        ini_set('log_errors', 1);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline) {
        $error = [
            'type' => 'ERROR',
            'errno' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'stack' => $this->getStackTrace(),
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => $this->getMemoryUsage(),
            'execution_time' => $this->getExecutionTime()
        ];

        $this->addLog($error);

        // Log to file
        error_log("SIGEP Error: $errstr in $errfile on line $errline");
    }

    public function exceptionHandler($exception) {
        $error = [
            'type' => 'EXCEPTION',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => $this->getMemoryUsage(),
            'execution_time' => $this->getExecutionTime()
        ];

        $this->addLog($error);

        error_log("SIGEP Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    }

    public function analyzeError($errorLog) {
        $analysis = [];

        // Parse common error patterns
        $patterns = [
            'SQLSTATE' => 'Database Error',
            'mysqli_' => 'MySQL Error',
            'PDO' => 'Database Error',
            'Call to undefined function' => 'Function Not Found',
            'Undefined variable' => 'Variable Not Defined',
            'Failed opening' => 'File Access Error',
            'Permission denied' => 'Permission Error',
            'Connection refused' => 'Connection Error'
        ];

        foreach ($patterns as $pattern => $category) {
            if (strpos($errorLog, $pattern) !== false) {
                $analysis['category'] = $category;
                $analysis['pattern'] = $pattern;
                break;
            }
        }

        // Extract specific details
        if (preg_match('/in (\S+) on line (\d+)/', $errorLog, $matches)) {
            $analysis['file'] = $matches[1];
            $analysis['line'] = $matches[2];
        }

        if (preg_match('/SQLSTATE\[(\w+)\]/', $errorLog, $matches)) {
            $analysis['sql_state'] = $matches[1];
        }

        return $analysis;
    }

    public function debugQuery($sql, $params = []) {
        $debug = [
            'query' => $sql,
            'params' => $params,
            'timestamp' => date('Y-m-d H:i:s'),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ];

        // Log the query
        $logMessage = "SQL Debug: $sql";
        if (!empty($params)) {
            $logMessage .= " | Params: " . json_encode($params);
        }

        error_log($logMessage);

        return $debug;
    }

    public function measureExecutionTime($callback) {
        $start = microtime(true);

        $result = $callback();

        $end = microtime(true);
        $executionTime = ($end - $start) * 1000; // Convert to milliseconds

        $this->addLog([
            'type' => 'PERFORMANCE',
            'operation' => 'Execution Time',
            'time_ms' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => $this->getMemoryUsage()
        ]);

        return $result;
    }

    public function checkDatabaseConnection() {
        $checks = [];

        try {
            // Test database connection
            $config = require __DIR__ . '/../../../conf/db.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            $pdo = new PDO($dsn, $config['user'], $config['pass']);

            $checks['connection'] = 'SUCCESS';
            $checks['database'] = $config['dbname'];
            $checks['host'] = $config['host'];
            $checks['charset'] = $config['charset'];

            // Test basic query
            $stmt = $pdo->query("SELECT 1");
            $checks['query_test'] = 'SUCCESS';

        } catch (PDOException $e) {
            $checks['connection'] = 'FAILED';
            $checks['error'] = $e->getMessage();
        }

        $checks['timestamp'] = date('Y-m-d H:i:s');

        return $checks;
    }

    public function analyzeFile($filePath) {
        $analysis = [];

        if (!file_exists($filePath)) {
            $analysis['status'] = 'FILE_NOT_FOUND';
            return $analysis;
        }

        $analysis['status'] = 'EXISTS';
        $analysis['size'] = filesize($filePath);
        $analysis['modified'] = date('Y-m-d H:i:s', filemtime($filePath));
        $analysis['readable'] = is_readable($filePath);
        $analysis['writable'] = is_writable($filePath);

        // Analyze file content
        $content = file_get_contents($filePath);
        $analysis['lines'] = substr_count($content, "\n");

        // Check for common issues
        $issues = [];

        if (strpos($content, '<?php') === false) {
            $issues[] = 'Not a PHP file';
        }

        if (strpos($content, 'error_reporting') === false) {
            $issues[] = 'Missing error reporting';
        }

        if (strpos($content, '$_GET') !== false && strpos($content, 'filter_var') === false) {
            $issues[] = 'Unfiltered GET input';
        }

        if (strpos($content, 'mysql_query') !== false) {
            $issues[] = 'Using deprecated mysql_query';
        }

        $analysis['issues'] = $issues;

        return $analysis;
    }

    public function traceSession() {
        $trace = [];

        if (session_status() === PHP_SESSION_NONE) {
            $trace['status'] = 'NO_SESSION';
            return $trace;
        }

        $trace['status'] = 'ACTIVE';
        $trace['session_id'] = session_id();
        $trace['session_name'] = session_name();
        $trace['save_path'] = session_save_path();
        $trace['cookie_params'] = session_get_cookie_params();

        $trace['data'] = $_SESSION;
        $trace['cookies'] = $_COOKIE;

        return $trace;
    }

    public function checkPermissions($filePath) {
        $checks = [];

        $checks['file_exists'] = file_exists($filePath);
        $checks['readable'] = is_readable($filePath);
        $checks['writable'] = is_writable($filePath);
        $checks['executable'] = is_executable($filePath);

        if ($checks['file_exists']) {
            $perms = fileperms($filePath);
            $checks['permissions'] = decoct($perms);

            // Check file owner
            $stats = stat($filePath);
            $checks['owner'] = $stats['uid'];
            $checks['group'] = $stats['gid'];
        }

        return $checks;
    }

    public function monitorMemoryUsage() {
        $usage = [];

        $usage['current'] = memory_get_usage(true);
        $usage['peak'] = memory_get_peak_usage(true);
        $usage['limit'] = ini_get('memory_limit');

        // Convert to human readable format
        $usage['current_mb'] = round($usage['current'] / 1024 / 1024, 2);
        $usage['peak_mb'] = round($usage['peak'] / 1024 / 1024, 2);
        $usage['limit_mb'] = round($this->parseMemoryLimit($usage['limit']) / 1024 / 1024, 2);

        $usage['usage_percentage'] = ($usage['current'] / $this->parseMemoryLimit($usage['limit'])) * 100;

        return $usage;
    }

    public function generateDebugReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time' => $this->getExecutionTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'memory_peak' => $this->getMemoryPeak(),
            'logs' => $this->logs,
            'system_info' => $this->getSystemInfo(),
            'php_info' => $this->getPHPInfo(),
            'database_info' => $this->checkDatabaseConnection()
        ];

        return $report;
    }

    private function addLog($log) {
        $this->logs[] = $log;

        // Keep only last 100 logs to prevent memory issues
        if (count($this->logs) > 100) {
            array_shift($this->logs);
        }
    }

    private function getStackTrace() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $trace = [];

        foreach ($backtrace as $item) {
            $trace[] = [
                'file' => $item['file'] ?? 'unknown',
                'line' => $item['line'] ?? 'unknown',
                'function' => $item['function'] ?? 'unknown',
                'class' => $item['class'] ?? 'unknown'
            ];
        }

        return $trace;
    }

    private function getExecutionTime() {
        return round((microtime(true) - $this->startTime) * 1000, 2);
    }

    private function getMemoryUsage() {
        return round((memory_get_usage(true) - $this->memoryStart) / 1024 / 1024, 2);
    }

    private function getMemoryPeak() {
        return round(memory_get_peak_usage(true) / 1024 / 1024, 2);
    }

    private function parseMemoryLimit($limit) {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    private function getSystemInfo() {
        return [
            'os' => PHP_OS,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
    }

    private function getPHPInfo() {
        ob_start();
        phpinfo();
        $info = ob_get_contents();
        ob_end_clean();

        return $info;
    }
}
```

### Database Query Debugger

```php
<?php
class SIGEPDatabaseDebugger {
    private $queries = [];
    private $slowQueries = [];
    private $errorQueries = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;

        // Enable MySQL general log for debugging
        try {
            $pdo->exec("SET GLOBAL general_log = 'ON'");
            $pdo->exec("SET GLOBAL slow_query_log = 'ON'");
            $pdo->exec("SET GLOBAL long_query_time = 1");
        } catch (Exception $e) {
            // Log error but continue
        }
    }

    public function logQuery($sql, $params = [], $executionTime = null) {
        $query = [
            'sql' => $sql,
            'params' => $params,
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time' => $executionTime,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)
        ];

        $this->queries[] = $query;

        // Check if it's a slow query
        if ($executionTime > 1000) { // 1 second
            $this->slowQueries[] = $query;
        }

        // Log to file
        $logMessage = "Query: $sql";
        if (!empty($params)) {
            $logMessage .= " | Params: " . json_encode($params);
        }
        if ($executionTime) {
            $logMessage .= " | Time: {$executionTime}ms";
        }

        error_log("SIGEP Query: $logMessage");
    }

    public function analyzeSlowQueries() {
        $analysis = [];

        foreach ($this->slowQueries as $query) {
            $analysis[] = [
                'sql' => $query['sql'],
                'params' => $query['params'],
                'execution_time' => $query['execution_time'],
                'timestamp' => $query['timestamp'],
                'suggestions' => $this->getOptimizationSuggestions($query['sql'])
            ];
        }

        return $analysis;
    }

    public function getQueryStatistics() {
        $stats = [
            'total_queries' => count($this->queries),
            'slow_queries' => count($this->slowQueries),
            'error_queries' => count($this->errorQueries),
            'average_time' => $this->getAverageQueryTime(),
            'total_time' => $this->getTotalQueryTime()
        ];

        return $stats;
    }

    public function explainQuery($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare("EXPLAIN " . $sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'result' => $result,
                'suggestions' => $this->getOptimizationSuggestions($sql)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function getOptimizationSuggestions($sql) {
        $suggestions = [];

        $sql = strtolower($sql);

        // Check for missing indexes
        if (strpos($sql, 'where') !== false && strpos($sql, 'index') === false) {
            $suggestions[] = 'Consider adding an index for the WHERE clause columns';
        }

        // Check for SELECT *
        if (strpos($sql, 'select *') !== false) {
            $suggestions[] = 'Avoid SELECT *, specify only needed columns';
        }

        // Check for ORDER BY without LIMIT
        if (strpos($sql, 'order by') !== false && strpos($sql, 'limit') === false) {
            $suggestions[] = 'Add LIMIT clause to ORDER BY queries';
        }

        // Check for subqueries
        if (strpos($sql, '(select') !== false) {
            $suggestions[] = 'Consider optimizing subqueries or using JOINs';
        }

        // Check for LIKE queries with wildcards
        if (strpos($sql, 'like %') !== false) {
            $suggestions[] = 'LIKE with leading % prevents index usage';
        }

        return $suggestions;
    }

    private function getAverageQueryTime() {
        if (empty($this->queries)) {
            return 0;
        }

        $total = 0;
        foreach ($this->queries as $query) {
            $total += $query['execution_time'] ?? 0;
        }

        return $total / count($this->queries);
    }

    private function getTotalQueryTime() {
        $total = 0;
        foreach ($this->queries as $query) {
            $total += $query['execution_time'] ?? 0;
        }
        return $total;
    }
}
```

### Performance Monitor

```php
<?php
class SIGEPPerformanceMonitor {
    private $metrics = [];
    private $startTime;

    public function __construct() {
        $this->startTime = microtime(true);
    }

    public function startTimer($name) {
        $this->metrics[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true)
        ];
    }

    public function endTimer($name) {
        if (!isset($this->metrics[$name])) {
            return null;
        }

        $metric = $this->metrics[$name];
        $metric['end_time'] = microtime(true);
        $metric['end_memory'] = memory_get_usage(true);
        $metric['duration'] = ($metric['end_time'] - $metric['start_time']) * 1000;
        $metric['memory_used'] = $metric['end_memory'] - $metric['start_memory'];

        $this->metrics[$name] = $metric;

        return $metric;
    }

    public function getMetrics() {
        return $this->metrics;
    }

    public function getPerformanceReport() {
        $report = [
            'total_time' => (microtime(true) - $this->startTime) * 1000,
            'metrics' => $this->metrics,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];

        return $report;
    }

    public function identifyBottlenecks() {
        $bottlenecks = [];

        foreach ($this->metrics as $name => $metric) {
            if ($metric['duration'] > 1000) { // 1 second
                $bottlenecks[] = [
                    'name' => $name,
                    'type' => 'slow_operation',
                    'duration' => $metric['duration'],
                    'suggestion' => 'Consider optimizing this operation'
                ];
            }

            if ($metric['memory_used'] > 10 * 1024 * 1024) { // 10MB
                $bottlenecks[] = [
                    'name' => $name,
                    'type' => 'memory_intensive',
                    'memory_used' => $metric['memory_used'],
                    'suggestion' => 'Consider reducing memory usage'
                ];
            }
        }

        return $bottlenecks;
    }
}
```

### System Diagnostics

```php
<?php
class SIGEPSystemDiagnostics {

    public function checkEnvironment() {
        $checks = [];

        // PHP Version
        $checks['php_version'] = [
            'current' => PHP_VERSION,
            'required' => '8.0',
            'status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'OK' : 'WARNING'
        ];

        // Required Extensions
        $required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
        $checks['extensions'] = [];

        foreach ($required_extensions as $ext) {
            $checks['extensions'][$ext] = [
                'loaded' => extension_loaded($ext),
                'status' => extension_loaded($ext) ? 'OK' : 'MISSING'
            ];
        }

        // File Permissions
        $checks['permissions'] = [
            'conf/db.php' => $this->checkFilePermissions(__DIR__ . '/../../../conf/db.php'),
            'uploads/' => $this->checkFilePermissions(__DIR__ . '/../../../uploads/'),
            'temp/' => $this->checkFilePermissions(__DIR__ . '/../../../temp/')
        ];

        // Server Configuration
        $checks['server'] = [
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'display_errors' => ini_get('display_errors') ? 'ENABLED' : 'DISABLED'
        ];

        return $checks;
    }

    public function checkModuleIntegration($moduleName) {
        $checks = [];

        $modulePath = __DIR__ . "/../../../modulos/$moduleName";

        $checks['module_exists'] = is_dir($modulePath);

        if ($checks['module_exists']) {
            $checks['files'] = $this->scanModuleFiles($modulePath);
            $checks['structure'] = $this->validateModuleStructure($modulePath);
            $checks['dependencies'] = $this->checkModuleDependencies($modulePath);
        }

        return $checks;
    }

    public function testAPIEndpoint($url, $method = 'GET', $params = []) {
        $test = [
            'url' => $url,
            'method' => $method,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        try {
            $ch = curl_init();

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            } else {
                $url .= '?' . http_build_query($params);
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            $test['http_code'] = $httpCode;
            $test['response_size'] = strlen($response);
            $test['success'] = $httpCode >= 200 && $httpCode < 300;

            if ($test['success']) {
                $test['response'] = substr($response, 0, 500);
            } else {
                $test['error'] = curl_error($ch);
            }

            curl_close($ch);

        } catch (Exception $e) {
            $test['success'] = false;
            $test['error'] = $e->getMessage();
        }

        return $test;
    }

    private function checkFilePermissions($path) {
        if (!file_exists($path)) {
            return ['exists' => false];
        }

        return [
            'exists' => true,
            'readable' => is_readable($path),
            'writable' => is_writable($path),
            'permissions' => substr(sprintf('%o', fileperms($path)), -3)
        ];
    }

    private function scanModuleFiles($modulePath) {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = [
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime())
                ];
            }
        }

        return $files;
    }

    private function validateModuleStructure($modulePath) {
        $structure = [];

        $expectedFiles = [
            '_view.php',
            '_logica.php',
            'assets/css/',
            'assets/js/'
        ];

        foreach ($expectedFiles as $file) {
            $structure[$file] = file_exists($modulePath . '/' . $file);
        }

        return $structure;
    }

    private function checkModuleDependencies($modulePath) {
        $dependencies = [];

        // Check for composer.json
        $composerFile = $modulePath . '/composer.json';
        if (file_exists($composerFile)) {
            $composer = json_decode(file_get_contents($composerFile), true);
            $dependencies['composer'] = array_keys($composer['require'] ?? []);
        }

        // Check for package.json
        $packageFile = $modulePath . '/package.json';
        if (file_exists($packageFile)) {
            $package = json_decode(file_get_contents($packageFile), true);
            $dependencies['npm'] = array_keys($package['dependencies'] ?? []);
        }

        return $dependencies;
    }
}
```

## Usage Examples

### Debug a specific error

```
@sigep-debug-helper analyze the following PHP error:
"Fatal error: Uncaught PDOException: SQLSTATE[42000]: Syntax error or access violation: 1142 The INSERT statement conflicted with a FOREIGN KEY constraint"
```

### Monitor performance

```
@sigep-debug-helper monitor the performance of the user search functionality in the internos module
```

### Check database connectivity

```
@sigep-debug-helper test the database connection for the SIGEP system
```

### Analyze a file

```
@sigep-debug-helper analyze the file modulos/censura/cartas/censura_cartas_logica.php for potential issues
```

### Debug session issues

```
@sigep-debug-helper trace the current session state and identify authentication problems
```

### Generate debug report

```
@sigep-debug-helper generate a comprehensive debug report for the current system state
```

### Check module integration

```
@sigep-debug-helper check the integration of the eclusa module with the main system
```

### Test API endpoint

```
@sigep-debug-helper test the API endpoint /modulos/censura/cartas/api/list with GET method
```

### Monitor memory usage

```
@sigep-debug-helper monitor memory usage during a large data export operation
```

## Debugging Best Practices

### 1. Error Logging

```php
// Always include context in error logs
error_log("SIGEP Error: $errorMessage in $file on line $line");
error_log("SIGEP Context: User ID: " . ($_SESSION['user_id'] ?? 'unknown'));
error_log("SIGEP Request: " . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
```

### 2. Performance Monitoring

```php
// Measure execution time
$start = microtime(true);
// ... code to measure ...
$duration = (microtime(true) - $start) * 1000;
error_log("SIGEP Performance: $function took {$duration}ms");
```

### 3. Database Debugging

```php
// Log all database queries
error_log("SIGEP Query: $sql | Params: " . json_encode($params));
```

### 4. Session Debugging

```php
// Log session state
error_log("SIGEP Session: " . json_encode($_SESSION));
```

## Troubleshooting Guide

### Common Issues

#### 1. White Screen/Blank Page

- Check PHP error logs: `tail -f /var/log/php_errors.log`
- Verify database connection with `SIGEPDebugger::checkDatabaseConnection()`
- Check file permissions: `ls -la modulos/*/`
- Examine browser console for JavaScript errors
- Check session state: `var_dump($_SESSION)`

#### 2. Database Connection Errors

- Verify database server status: `systemctl status mysql`
- Check connection parameters in `conf/db.php`
- Test with simple query: `SELECT 1`
- Check user permissions: `SHOW GRANTS FOR CURRENT_USER`

#### 3. Session Issues

- Verify `session_start()` is called in all controllers
- Check session cookie settings in `php.ini`
- Verify session save path permissions: `session.save_path`
- Check for session timeout: `session.gc_maxlifetime`
- Test session data: `print_r($_SESSION)`

#### 4. Permission Errors

- Verify user authentication: `isset($_SESSION['user_id'])`
- Check permission assignments in database
- Test role-based access: `$_SESSION['permissions']`
- Verify session state and user data

#### 5. Slow Performance

- Analyze slow queries: `SHOW FULL PROCESSLIST`
- Check memory usage: `memory_get_usage(true)`
- Identify bottlenecks with `SIGEPPerformanceMonitor`
- Optimize database indexes: `EXPLAIN SELECT ...`
- Profile with Xdebug if available

### Debug Tools

#### Browser Developer Tools

- Console: JavaScript errors and logs
- Network: Request/response analysis
- Elements: DOM inspection
- Performance: Resource loading analysis

#### Server Logs

- PHP error logs
- Apache/Nginx access logs
- Database error logs
- System logs

#### Development Tools

- Xdebug for step debugging
- Blackfire for performance profiling
- PHPUnit for unit testing
- PHP CodeSniffer for code analysis

## Resources and References

### Debugging Documentation

- [PHP Debugging Guide](https://www.php.net/manual/en/debugger.php)
- [Xdebug Documentation](https://xdebug.org/docs/)
- [Blackfire Documentation](https://blackfire.io/docs/)

### Performance Monitoring

- [PHP Performance Profiling](https://www.php.net/manual/en/book/xdebug.profiler.html)
- [MySQL Performance Schema](https://dev.mysql.com/doc/refman/5.7/en/performance-schema.html)
- [Apache Performance Tuning](https://httpd.apache.org/docs/2.4/mod/mod_info.html)

### System Monitoring

- [Linux System Monitoring](https://www.kernel.org/doc/html/latest/admin-guide/monitoring.html)
- [Windows Performance Monitor](https://docs.microsoft.com/en-us/windows-server/administration/performance/start-performance-monitor)
- [SIGEP System Architecture](../../../architecture/visao_geral.md)
