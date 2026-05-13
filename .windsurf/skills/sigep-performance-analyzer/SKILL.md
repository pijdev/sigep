---
name: sigep-performance-analyzer
description: Analyzes and optimizes SIGEP application performance including database queries, memory usage, and execution time
---

# SIGEP Performance Analyzer Skill

## Purpose
This skill provides comprehensive performance analysis and optimization capabilities for SIGEP applications, focusing on database query optimization, memory usage monitoring, and execution time analysis.

## Performance Analysis Categories

### 1. Database Performance
- Query execution time analysis
- Index optimization recommendations
- Slow query identification
- Connection pool monitoring
- Query plan analysis

### 2. Memory Usage Analysis
- Memory consumption tracking
- Memory leak detection
- Peak usage monitoring
- Garbage collection analysis
- Memory optimization suggestions

### 3. Execution Time Analysis
- Function performance profiling
- Request lifecycle analysis
- Bottleneck identification
- Response time optimization
- Performance regression detection

### 4. Resource Utilization
- CPU usage monitoring
- Disk I/O analysis
- Network performance
- Cache hit rates
- System resource optimization

## Performance Analysis Tools

### Database Query Analyzer
```php
<?php
class SIGEPPerformanceAnalyzer {
    private $queries = [];
    private $slowQueries = [];
    private $threshold = 1000; // 1 second threshold
    
    public function analyzeQuery($sql, $params = [], $executionTime) {
        $analysis = [
            'sql' => $sql,
            'params' => $params,
            'execution_time' => $executionTime,
            'timestamp' => date('Y-m-d H:i:s'),
            'is_slow' => $executionTime > $this->threshold,
            'recommendations' => []
        ];
        
        // Analyze query pattern
        $analysis['recommendations'] = array_merge(
            $this->analyzeQueryPattern($sql),
            $this->analyzeIndexUsage($sql),
            $this->analyzeQueryStructure($sql)
        );
        
        $this->queries[] = $analysis;
        
        if ($analysis['is_slow']) {
            $this->slowQueries[] = $analysis;
            $this->logSlowQuery($analysis);
        }
        
        return $analysis;
    }
    
    private function analyzeQueryPattern($sql) {
        $recommendations = [];
        $sql = strtolower($sql);
        
        // Check for SELECT *
        if (strpos($sql, 'select *') !== false) {
            $recommendations[] = [
                'type' => 'optimization',
                'message' => 'Avoid SELECT *, specify only needed columns',
                'impact' => 'high'
            ];
        }
        
        // Check for missing WHERE clause
        if (strpos($sql, 'where') === false && strpos($sql, 'select') !== false) {
            $recommendations[] = [
                'type' => 'performance',
                'message' => 'Consider adding WHERE clause to limit result set',
                'impact' => 'medium'
            ];
        }
        
        // Check for ORDER BY without LIMIT
        if (strpos($sql, 'order by') !== false && strpos($sql, 'limit') === false) {
            $recommendations[] = [
                'type' => 'optimization',
                'message' => 'Add LIMIT clause to ORDER BY queries',
                'impact' => 'medium'
            ];
        }
        
        // Check for subqueries
        if (strpos($sql, '(select') !== false) {
            $recommendations[] = [
                'type' => 'optimization',
                'message' => 'Consider optimizing subqueries or using JOINs',
                'impact' => 'high'
            ];
        }
        
        return $recommendations;
    }
    
    private function analyzeIndexUsage($sql) {
        $recommendations = [];
        
        // Check for LIKE with leading wildcard
        if (preg_match('/like\s+[\'"]%/', $sql)) {
            $recommendations[] = [
                'type' => 'index',
                'message' => 'LIKE with leading % prevents index usage',
                'impact' => 'high'
            ];
        }
        
        // Check for functions on indexed columns
        if (preg_match('/where\s+\w+\s*\(/', $sql)) {
            $recommendations[] = [
                'type' => 'index',
                'message' => 'Functions on WHERE columns prevent index usage',
                'impact' => 'high'
            ];
        }
        
        return $recommendations;
    }
    
    private function analyzeQueryStructure($sql) {
        $recommendations = [];
        
        // Check for multiple queries
        if (substr_count($sql, ';') > 1) {
            $recommendations[] = [
                'type' => 'structure',
                'message' => 'Consider splitting multiple queries',
                'impact' => 'low'
            ];
        }
        
        return $recommendations;
    }
    
    public function getPerformanceReport() {
        $totalQueries = count($this->queries);
        $slowQueries = count($this->slowQueries);
        $avgTime = $totalQueries > 0 ? array_sum(array_column($this->queries, 'execution_time')) / $totalQueries : 0;
        
        return [
            'summary' => [
                'total_queries' => $totalQueries,
                'slow_queries' => $slowQueries,
                'slow_query_percentage' => $totalQueries > 0 ? ($slowQueries / $totalQueries) * 100 : 0,
                'average_execution_time' => round($avgTime, 2),
                'slowest_query' => $this->getSlowestQuery(),
                'fastest_query' => $this->getFastestQuery()
            ],
            'slow_queries' => $this->slowQueries,
            'recommendations' => $this->getOverallRecommendations()
        ];
    }
    
    private function getSlowestQuery() {
        if (empty($this->queries)) return null;
        
        return array_reduce($this->queries, function($slowest, $query) {
            return (!$slowest || $query['execution_time'] > $slowest['execution_time']) ? $query : $slowest;
        });
    }
    
    private function getFastestQuery() {
        if (empty($this->queries)) return null;
        
        return array_reduce($this->queries, function($fastest, $query) {
            return (!$fastest || $query['execution_time'] < $fastest['execution_time']) ? $query : $fastest;
        });
    }
    
    private function getOverallRecommendations() {
        $recommendations = [];
        
        if (count($this->slowQueries) > 0) {
            $recommendations[] = [
                'type' => 'urgent',
                'message' => 'Address slow queries to improve performance',
                'count' => count($this->slowQueries)
            ];
        }
        
        return $recommendations;
    }
    
    private function logSlowQuery($analysis) {
        $logMessage = sprintf(
            "SIGEP Slow Query: %sms - %s",
            $analysis['execution_time'],
            $analysis['sql']
        );
        
        error_log($logMessage);
    }
}
```

### Memory Usage Monitor
```php
<?php
class SIGEPMemoryMonitor {
    private $snapshots = [];
    private $peakUsage = 0;
    private $startUsage;
    
    public function __construct() {
        $this->startUsage = memory_get_usage(true);
        $this->takeSnapshot('start');
    }
    
    public function takeSnapshot($label) {
        $snapshot = [
            'label' => $label,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'memory_diff' => $this->startUsage ? memory_get_usage(true) - $this->startUsage : 0
        ];
        
        $this->snapshots[] = $snapshot;
        
        if ($snapshot['memory_usage'] > $this->peakUsage) {
            $this->peakUsage = $snapshot['memory_usage'];
        }
        
        return $snapshot;
    }
    
    public function analyzeMemoryUsage() {
        if (empty($this->snapshots)) {
            return ['error' => 'No memory snapshots available'];
        }
        
        $totalSnapshots = count($this->snapshots);
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        $analysis = [
            'summary' => [
                'current_usage' => $this->formatBytes($currentUsage),
                'peak_usage' => $this->formatBytes($peakUsage),
                'memory_limit' => $this->formatBytes($memoryLimit),
                'usage_percentage' => ($currentUsage / $memoryLimit) * 100,
                'peak_percentage' => ($peakUsage / $memoryLimit) * 100,
                'snapshots_count' => $totalSnapshots
            ],
            'snapshots' => $this->snapshots,
            'recommendations' => []
        ];
        
        // Generate recommendations
        if ($analysis['summary']['usage_percentage'] > 80) {
            $analysis['recommendations'][] = [
                'type' => 'critical',
                'message' => 'Memory usage is high, consider optimization',
                'percentage' => $analysis['summary']['usage_percentage']
            ];
        }
        
        if ($analysis['summary']['peak_percentage'] > 90) {
            $analysis['recommendations'][] = [
                'type' => 'warning',
                'message' => 'Peak memory usage is approaching limit',
                'percentage' => $analysis['summary']['peak_percentage']
            ];
        }
        
        // Check for memory leaks
        $memoryGrowth = $this->analyzeMemoryGrowth();
        if ($memoryGrowth['is_leaking']) {
            $analysis['recommendations'][] = [
                'type' => 'leak',
                'message' => 'Possible memory leak detected',
                'growth_rate' => $memoryGrowth['growth_rate']
            ];
        }
        
        return $analysis;
    }
    
    private function analyzeMemoryGrowth() {
        if (count($this->snapshots) < 2) {
            return ['is_leaking' => false];
        }
        
        $firstSnapshot = $this->snapshots[0];
        $lastSnapshot = end($this->snapshots);
        
        $timeDiff = $lastSnapshot['timestamp'] - $firstSnapshot['timestamp'];
        $memoryDiff = $lastSnapshot['memory_usage'] - $firstSnapshot['memory_usage'];
        
        $growthRate = $timeDiff > 0 ? $memoryDiff / $timeDiff : 0;
        
        return [
            'is_leaking' => $growthRate > 1000, // Growing more than 1KB per second
            'growth_rate' => $growthRate,
            'total_growth' => $memoryDiff
        ];
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
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

### Execution Time Profiler
```php
<?php
class SIGEPExecutionProfiler {
    private $timers = [];
    private $callStack = [];
    private $totalExecutionTime;
    
    public function __construct() {
        $this->totalExecutionTime = microtime(true);
    }
    
    public function startTimer($name, $context = []) {
        $timer = [
            'name' => $name,
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
            'call_stack' => $this->getCurrentCallStack()
        ];
        
        $this->timers[$name] = $timer;
        $this->callStack[] = $name;
        
        return $timer;
    }
    
    public function endTimer($name) {
        if (!isset($this->timers[$name])) {
            return null;
        }
        
        $timer = $this->timers[$name];
        $timer['end_time'] = microtime(true);
        $timer['end_memory'] = memory_get_usage(true);
        $timer['duration'] = ($timer['end_time'] - $timer['start_time']) * 1000; // ms
        $timer['memory_used'] = $timer['end_memory'] - $timer['start_memory'];
        
        $this->timers[$name] = $timer;
        
        // Remove from call stack
        $key = array_search($name, $this->callStack);
        if ($key !== false) {
            unset($this->callStack[$key]);
            $this->callStack = array_values($this->callStack);
        }
        
        return $timer;
    }
    
    public function getProfileReport() {
        $totalTime = (microtime(true) - $this->totalExecutionTime) * 1000;
        
        $report = [
            'summary' => [
                'total_execution_time' => round($totalTime, 2),
                'timers_count' => count($this->timers),
                'slowest_timer' => $this->getSlowestTimer(),
                'fastest_timer' => $this->getFastestTimer(),
                'memory_intensive' => $this->getMemoryIntensiveTimer()
            ],
            'timers' => $this->timers,
            'bottlenecks' => $this->identifyBottlenecks($totalTime),
            'recommendations' => []
        ];
        
        // Generate recommendations
        $report['recommendations'] = $this->generateRecommendations($report);
        
        return $report;
    }
    
    private function getSlowestTimer() {
        if (empty($this->timers)) return null;
        
        return array_reduce($this->timers, function($slowest, $timer) {
            if (!isset($timer['duration'])) return $slowest;
            return (!$slowest || $timer['duration'] > $slowest['duration']) ? $timer : $slowest;
        });
    }
    
    private function getFastestTimer() {
        if (empty($this->timers)) return null;
        
        return array_reduce($this->timers, function($fastest, $timer) {
            if (!isset($timer['duration'])) return $fastest;
            return (!$fastest || $timer['duration'] < $fastest['duration']) ? $timer : $fastest;
        });
    }
    
    private function getMemoryIntensiveTimer() {
        if (empty($this->timers)) return null;
        
        return array_reduce($this->timers, function($intensive, $timer) {
            if (!isset($timer['memory_used'])) return $intensive;
            return (!$intensive || $timer['memory_used'] > $intensive['memory_used']) ? $timer : $intensive;
        });
    }
    
    private function identifyBottlenecks($totalTime) {
        $bottlenecks = [];
        
        foreach ($this->timers as $timer) {
            if (!isset($timer['duration'])) continue;
            
            $percentage = ($timer['duration'] / $totalTime) * 100;
            
            if ($percentage > 20) { // More than 20% of total time
                $bottlenecks[] = [
                    'timer' => $timer,
                    'percentage' => round($percentage, 2),
                    'severity' => $percentage > 50 ? 'critical' : 'warning'
                ];
            }
        }
        
        return $bottlenecks;
    }
    
    private function generateRecommendations($report) {
        $recommendations = [];
        
        // Check bottlenecks
        foreach ($report['bottlenecks'] as $bottleneck) {
            $recommendations[] = [
                'type' => 'bottleneck',
                'message' => "Timer '{$bottleneck['timer']['name']}' takes {$bottleneck['percentage']}% of execution time",
                'severity' => $bottleneck['severity']
            ];
        }
        
        // Check memory usage
        $memoryIntensive = $report['summary']['memory_intensive'];
        if ($memoryIntensive && $memoryIntensive['memory_used'] > 10 * 1024 * 1024) { // 10MB
            $recommendations[] = [
                'type' => 'memory',
                'message' => "Timer '{$memoryIntensive['name']}' uses significant memory",
                'memory_used' => $this->formatBytes($memoryIntensive['memory_used'])
            ];
        }
        
        return $recommendations;
    }
    
    private function getCurrentCallStack() {
        return $this->callStack;
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

## Usage Examples

### Analyze database performance
```
@sigep-performance-analyzer analyze the database queries in the escolta module for performance issues
```

### Monitor memory usage
```
@sigep-performance-analyzer monitor memory usage during a large data export operation
```

### Profile execution time
```
@sigep-performance-analyzer profile the execution time of the user authentication process
```

### Generate performance report
```
@sigep-performance-analyzer generate a comprehensive performance report for the current request
```

### Identify bottlenecks
```
@sigep-performance-analyzer identify performance bottlenecks in the internos management module
```

### Optimize slow queries
```
@sigep-performance-analyzer analyze and provide optimization recommendations for slow queries
```

## Performance Optimization Guidelines

### Database Optimization
1. **Index Optimization**
   - Add indexes for frequently queried columns
   - Use composite indexes for multi-column queries
   - Monitor index usage with `EXPLAIN`

2. **Query Optimization**
   - Avoid SELECT * queries
   - Use LIMIT for large result sets
   - Optimize JOIN operations
   - Use appropriate data types

3. **Connection Management**
   - Use connection pooling
   - Close connections properly
   - Monitor connection count

### Memory Optimization
1. **Memory Management**
   - Release large objects when done
   - Use memory-efficient data structures
   - Monitor memory usage patterns

2. **Caching Strategies**
   - Implement query result caching
   - Use opcode caching (OPcache)
   - Cache frequently accessed data

### Execution Time Optimization
1. **Algorithm Optimization**
   - Use efficient algorithms
   - Avoid nested loops when possible
   - Implement lazy loading

2. **I/O Optimization**
   - Minimize file operations
   - Use buffered I/O
   - Optimize network requests

## Integration with SIGEP

### Automatic Performance Monitoring
```php
// Add to controllers for automatic monitoring
$performanceAnalyzer = new SIGEPPerformanceAnalyzer();
$memoryMonitor = new SIGEPMemoryMonitor();
$profiler = new SIGEPExecutionProfiler();

// Monitor database queries
$profiler->startTimer('database_query');
$result = $pdo->query($sql);
$profiler->endTimer('database_query');

// Monitor memory usage
$memoryMonitor->takeSnapshot('after_query');

// Generate report at end
$report = $performanceAnalyzer->getPerformanceReport();
error_log("Performance Report: " . json_encode($report));
```

### Performance Dashboard
```php
// Create performance dashboard endpoint
function getPerformanceDashboard() {
    $analyzer = new SIGEPPerformanceAnalyzer();
    $memoryMonitor = new SIGEPMemoryMonitor();
    $profiler = new SIGEPExecutionProfiler();
    
    return [
        'database_performance' => $analyzer->getPerformanceReport(),
        'memory_usage' => $memoryMonitor->analyzeMemoryUsage(),
        'execution_profile' => $profiler->getProfileReport()
    ];
}
```

## Troubleshooting Performance Issues

### Common Performance Problems
1. **Slow Database Queries**
   - Missing indexes
   - Inefficient queries
   - Large result sets
   - Lock contention

2. **Memory Issues**
   - Memory leaks
   - Large object creation
   - Insufficient memory limits
   - Garbage collection delays

3. **Execution Time Issues**
   - Inefficient algorithms
   - Excessive I/O operations
   - Network latency
   - Blocking operations

### Debug Tools
- MySQL slow query log
- PHP error log with timing
- Memory profiling with Xdebug
- Performance monitoring tools

## Best Practices

### Development
1. Profile code during development
2. Monitor performance in production
3. Set performance budgets
4. Regular performance audits

### Monitoring
1. Set up performance alerts
2. Monitor key metrics
3. Track performance trends
4. Document performance issues

### Optimization
1. Optimize critical paths first
2. Measure before and after changes
3. Consider trade-offs
4. Document optimizations
