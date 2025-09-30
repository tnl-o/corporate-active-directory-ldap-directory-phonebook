<?php
/**
 * API для получения метрик использования
 * 
 * Этот файл предоставляет REST API для получения статистики использования
 * телефонного справочника. Собирает данные о поисковых запросах,
 * популярных запросах и активности пользователей.
 * 
 * @author Your Name
 * @version 3.1.1
 * @since 2025-01-01
 * 
 * @return JSON Статистика использования в формате JSON
 * 
 * Пример использования:
 * GET /api_metrics.php
 * 
 * Возвращает:
 * {
 *   "total_searches": 1250,
 *   "unique_users_today": 45,
 *   "hourly_searches": 12,
 *   "popular_queries": {
 *     "иванов": 25,
 *     "петров": 18,
 *     "сидоров": 15
 *   }
 * }
 */

// Настройка обработки ошибок
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Загрузка конфигурации
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'logging' => [
            'path' => __DIR__ . '/logs',
        ],
    ];
}

$logPath = $config['logging']['path'];
$metricsFile = $logPath . '/usage_metrics.json';

header('Content-Type: application/json');

try {
    if (!file_exists($metricsFile)) {
        echo json_encode([
            'total_searches' => 0,
            'unique_users_today' => 0,
            'popular_queries' => [],
            'hourly_stats' => array_fill(0, 24, 0),
            'daily_stats' => []
        ]);
        exit;
    }

    $metrics = json_decode(file_get_contents($metricsFile), true) ?: [];
    
    // Подсчет общей статистики
    $totalSearches = 0;
    $uniqueUsersToday = 0;
    $popularQueries = [];
    $hourlyStats = array_fill(0, 24, 0);
    $dailyStats = [];

    foreach ($metrics as $date => $data) {
        $totalSearches += $data['total_searches'] ?? 0;
        
        if ($date === date('Y-m-d')) {
            $uniqueUsersToday = count($data['unique_users'] ?? []);
        }
        
        // Популярные запросы
        foreach ($data['popular_queries'] ?? [] as $query => $count) {
            if (!isset($popularQueries[$query])) {
                $popularQueries[$query] = 0;
            }
            $popularQueries[$query] += $count;
        }
        
        // Почасовые статистики (только за сегодня)
        if ($date === date('Y-m-d')) {
            $hourlyStats = $data['hourly'] ?? array_fill(0, 24, 0);
        }
        
        // Дневные статистики (последние 7 дней)
        if (strtotime($date) >= strtotime('-7 days')) {
            $dailyStats[$date] = [
                'searches' => $data['total_searches'] ?? 0,
                'users' => count($data['unique_users'] ?? [])
            ];
        }
    }

    // Сортируем популярные запросы
    arsort($popularQueries);
    $popularQueries = array_slice($popularQueries, 0, 10, true);

    echo json_encode([
        'total_searches' => $totalSearches,
        'unique_users_today' => $uniqueUsersToday,
        'popular_queries' => $popularQueries,
        'hourly_stats' => $hourlyStats,
        'daily_stats' => $dailyStats,
        'last_updated' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('Metrics API Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Failed to load metrics']);
}
?>
