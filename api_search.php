<?php
/**
 * API для поиска сотрудников в LDAP
 * 
 * Этот файл предоставляет REST API для поиска сотрудников в Active Directory.
 * Поддерживает поиск по ФИО, телефону, должности, отделу и email.
 * 
 * @author Your Name
 * @version 3.1.1
 * @since 2025-01-01
 * 
 * @param string $q Поисковый запрос
 * @return JSON Результаты поиска в формате JSON
 * 
 * Пример использования:
 * GET /api_search.php?q=иванов
 * 
 * Возвращает:
 * {
 *   "results": [
 *     {
 *       "name": "Иванов Иван Иванович",
 *       "phone": "1234",
 *       "mobile": "8(999)123-45-67",
 *       "email": "i.ivanov@company.com",
 *       "title": "Инженер",
 *       "department": "IT отдел",
 *       "username": "i.ivanov"
 *     }
 *   ],
 *   "query": "иванов",
 *   "count": 1
 * }
 */

// Настройка обработки ошибок
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загрузка конфигурации
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    $config = require $configFile;
} else {
    $config = [
        'ldap' => [
            'server' => '172.16.1.20',
            'port' => 389,
            'base_dn' => 'DC=ronc,DC=ru',
            'read_user' => 'ebook@ronc.ru',
            'read_password' => 'LeSjH5dhWn6i3na7393Lr6Nm',
        ],
        'logging' => [
            'path' => __DIR__ . '/logs',
        ],
    ];
}

// Настройка логирования
$logPath = $config['logging']['path'];
if (!is_dir($logPath)) {
    mkdir($logPath, 0755, true);
}
ini_set('error_log', $logPath . '/php_errors.log');

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');

// Логирование метрик использования
$metricsFile = $logPath . '/usage_metrics.json';
$metrics = [];
if (file_exists($metricsFile)) {
    $metrics = json_decode(file_get_contents($metricsFile), true) ?: [];
}

// Обновляем метрики
$today = date('Y-m-d');
$hour = date('H');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!isset($metrics[$today])) {
    $metrics[$today] = ['total_searches' => 0, 'unique_users' => [], 'hourly' => array_fill(0, 24, 0), 'popular_queries' => []];
}

$metrics[$today]['total_searches']++;
$metrics[$today]['unique_users'][$ip] = true;
$metrics[$today]['hourly'][$hour]++;

if (!empty($query)) {
    $query_lower = strtolower($query);
    if (!isset($metrics[$today]['popular_queries'][$query_lower])) {
        $metrics[$today]['popular_queries'][$query_lower] = 0;
    }
    $metrics[$today]['popular_queries'][$query_lower]++;
}

// Сохраняем метрики (только за последние 30 дней)
$cutoff_date = date('Y-m-d', strtotime('-30 days'));
foreach ($metrics as $date => $data) {
    if ($date < $cutoff_date) {
        unset($metrics[$date]);
    }
}

file_put_contents($metricsFile, json_encode($metrics, JSON_PRETTY_PRINT));

if (empty($query)) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    // Подключение к LDAP серверу
    $ldap = ldap_connect($config['ldap']['server'], $config['ldap']['port']);
    
    if (!$ldap) {
        throw new Exception('Не удалось подключиться к LDAP серверу');
    }
    
    // Настройка параметров LDAP
    ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
    
    // Привязка к LDAP с учетными данными для чтения
    $bind = ldap_bind($ldap, $config['ldap']['read_user'], $config['ldap']['read_password']);
    
    if (!$bind) {
        throw new Exception('Не удалось авторизоваться в LDAP: ' . ldap_error($ldap));
    }
    
    // Создаем фильтр поиска
    $searchConditions = [];
    
    // Базовые условия для пользователей
    $searchConditions[] = "(objectClass=user)";
    $searchConditions[] = "(!(objectClass=computer))"; // Исключаем компьютеры
    $searchConditions[] = "(!(userAccountControl:1.2.840.113556.1.4.803:=2))"; // Исключаем отключенные учетки
    
    if (!empty($query)) {
        $searchConditions[] = "(|(cn=*{$query}*)(displayName=*{$query}*)(telephoneNumber=*{$query}*)(mobile=*{$query}*)(sAMAccountName=*{$query}*)(title=*{$query}*)(department=*{$query}*)(mail=*{$query}*))";
    }
    
    $searchFilter = "(&" . implode('', $searchConditions) . ")";
    
    // Определяем базовый DN для поиска (по всему каталогу, если search_ou пустой)
    $searchBase = !empty($config['ldap']['search_ou']) ? $config['ldap']['search_ou'] : $config['ldap']['base_dn'];
    
    // Добавляем фильтр исключения контейнеров (например, CN=Users)
    $excludeFilter = '';
    if (!empty($config['ldap']['exclude_ous'])) {
        $excludeConditions = [];
        foreach ($config['ldap']['exclude_ous'] as $excludeOU) {
            $excludeConditions[] = "(!(distinguishedName=*{$excludeOU}*))";
        }
        if (!empty($excludeConditions)) {
            $excludeFilter = '(&' . implode('', $excludeConditions) . ')';
        }
    }
    
    // Комбинируем основной фильтр с исключениями
    $finalFilter = $excludeFilter ? "(&{$searchFilter}{$excludeFilter})" : $searchFilter;
    
    // Логируем информацию о поиске для отладки
    error_log("LDAP Search: Base='{$searchBase}', Filter='{$finalFilter}', Query='{$query}'");
    
    $searchResult = ldap_search(
        $ldap, 
        $searchBase, 
        $finalFilter,
        [
            'cn',           // Полное имя
            'displayName',  // Отображаемое имя
            'telephoneNumber', // Рабочий телефон
            'mobile',       // Мобильный телефон
            'mail',         // Email
            'title',        // Должность
            'department',   // Отдел
            'sAMAccountName', // Имя пользователя
            'distinguishedName' // DN для проверки исключений
        ],
        0,
        20 // Ограничиваем результат 20 записями для лучшей производительности
    );
    
    if (!$searchResult) {
        throw new Exception('Ошибка поиска в LDAP: ' . ldap_error($ldap));
    }
    
    $entries = ldap_get_entries($ldap, $searchResult);
    ldap_close($ldap);
    
    $results = [];
    
    for ($i = 0; $i < $entries['count']; $i++) {
        $entry = $entries[$i];
        
        // Проверяем исключения по DN
        $dn = $entry['distinguishedname'][0] ?? '';
        $shouldExclude = false;
        
        if (!empty($config['ldap']['exclude_ous'])) {
            foreach ($config['ldap']['exclude_ous'] as $excludeOU) {
                if (strpos($dn, $excludeOU) !== false) {
                    $shouldExclude = true;
                    break;
                }
            }
        }
        
        if ($shouldExclude) {
            continue;
        }
        
        // Получаем имя (приоритет: displayName > cn)
        $name = '';
        if (isset($entry['displayname'][0])) {
            $name = $entry['displayname'][0];
        } elseif (isset($entry['cn'][0])) {
            $name = $entry['cn'][0];
        }
        
        // Пропускаем записи без имени
        if (empty($name)) {
            continue;
        }
        
        // Исключаем тестовые записи и системные объекты
        $username = $entry['samaccountname'][0] ?? '';
        if (empty($username) || 
            strpos($username, 'TEST') !== false) {
            continue;
        }
        
        $results[] = [
            'name' => $name,
            'phone' => $entry['telephonenumber'][0] ?? '',
            'mobile' => $entry['mobile'][0] ?? '',
            'email' => $entry['mail'][0] ?? '',
            'title' => $entry['title'][0] ?? '',
            'department' => $entry['department'][0] ?? '',
            'username' => $entry['samaccountname'][0] ?? ''
        ];
    }
    
    echo json_encode([
        'results' => $results,
        'query' => $query,
        'count' => count($results)
    ]);
    
} catch (Exception $e) {
    error_log('LDAP Search Error in API: ' . $e->getMessage());
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
}
?>