<?php
declare(strict_types=1);

/**
 * StaffSearch v3.0 - Упрощенный телефонный справочник
 * Поиск по ФИО и номеру телефона из LDAP
 */

// Настройка обработки ошибок
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Загрузка конфигурации
$config = loadConfig();

// Настройка логирования
setupLogging($config);

// Обработка запроса
try {
    $path = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($path, PHP_URL_PATH);
    
    // Убираем базовый путь /eight из URL
    $basePath = $config['paths']['base_url'];
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
        if (empty($path)) {
            $path = '/';
        }
    }
    
    $response = handleRequest($path, $config);
    $response->send();
    
} catch (Throwable $e) {
    handleError($e, $config);
}

/**
 * Загрузка конфигурации
 */
function loadConfig(): array {
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) {
        return require $configFile;
    }
    
    // Конфигурация по умолчанию
    return [
        'app' => [
            'name' => 'StaffSearch',
            'version' => '3.0.0',
            'debug' => true,
            'timezone' => 'Europe/Moscow',
        ],
        'ldap' => [
            'server' => 'ldap://your-ldap-server.com',
            'port' => 389,
            'base_dn' => 'DC=company,DC=com',
            'username_field' => 'userprincipalname',
            'read_user' => 'readonly_user',
            'read_password' => 'readonly_password',
            'admin_logins' => ['admin'],
            'no_show_group' => '',
        ],
        'security' => [
            'session_lifetime' => 3600,
            'require_https' => false,
        ],
        'logging' => [
            'path' => __DIR__ . '/logs',
        ],
        'paths' => [
            'base_url' => '/eight',
            'assets' => '/eight/assets',
        ],
    ];
}

/**
 * Настройка логирования
 */
function setupLogging(array $config): void {
    $logPath = $config['logging']['path'];
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }
    ini_set('error_log', $logPath . '/php_errors.log');
}

/**
 * Обработка HTTP запроса
 */
function handleRequest(string $path, array $config): Response {
    switch ($path) {
        case '/':
        case '/index.php':
        case '/login':
            return showSearchPage($config);
            
        default:
            return new Response('Страница не найдена', 404);
    }
}

/**
 * Показать страницу поиска
 */
function showSearchPage(array $config): Response {
    // Убираем проверку авторизации - показываем сразу телефонную книгу
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$config['app']['name']} - Поиск сотрудников</title>
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius: 0.5rem;
            --radius-lg: 0.75rem;
        }

        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: #334155;
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.3), 0 1px 2px -1px rgb(0 0 0 / 0.3);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.3), 0 4px 6px -4px rgb(0 0 0 / 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            background: var(--bg-secondary); 
            color: var(--text-primary);
            line-height: 1.6;
            transition: all 0.3s ease;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 20px; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header { 
            background: var(--bg-primary); 
            padding: 2rem; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-lg); 
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .header h1 { 
            color: var(--text-primary); 
            margin-bottom: 0.5rem; 
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info { 
            color: var(--text-secondary); 
            font-size: 1rem; 
            font-weight: 500;
        }
        
        .theme-toggle {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            color: var(--text-secondary);
        }
        
        .theme-toggle:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        .search-form { 
            background: var(--bg-primary); 
            padding: 2rem; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-lg); 
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        
        .search-input { 
            width: 100%; 
            padding: 1rem 1.5rem; 
            border: 2px solid var(--border-color); 
            border-radius: var(--radius); 
            font-size: 1.1rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }
        
        .search-input:focus { 
            outline: none; 
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-input::placeholder {
            color: var(--text-muted);
        }
        .search-button { 
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); 
            color: white; 
            border: none; 
            padding: 1rem 2rem; 
            border-radius: var(--radius); 
            font-size: 1.1rem; 
            font-weight: 600;
            cursor: pointer; 
            margin-top: 1rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        
        .search-button:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .search-button:active {
            transform: translateY(0);
        }
        
        .results { 
            background: var(--bg-primary); 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-lg); 
            min-height: 200px;
            border: 1px solid var(--border-color);
            flex: 1;
        }
        
        .result-item { 
            padding: 1.5rem; 
            border-bottom: 1px solid var(--border-color); 
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .result-item:hover {
            background: var(--bg-tertiary);
            transform: translateX(4px);
        }
        
        .result-item:last-child { 
            border-bottom: none; 
        }
        .result-info h3 { 
            color: var(--text-primary); 
            margin-bottom: 0.5rem; 
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .result-info p { 
            color: var(--text-secondary); 
            font-size: 0.9rem; 
            margin: 0; 
        }
        
        .additional-info { 
            color: var(--text-muted); 
            font-size: 0.85rem; 
            line-height: 1.4; 
            margin-top: 0.5rem !important; 
        }
        
        .result-email { 
            color: var(--primary-color); 
            font-weight: 600; 
            font-size: 1rem;
            background: var(--bg-tertiary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .result-email:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .result-phone { 
            color: var(--success-color); 
            font-weight: 700; 
            font-size: 1rem;
            background: var(--bg-tertiary);
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .result-phone:hover {
            background: var(--success-color);
            color: white;
            transform: scale(1.05);
        }
        
        .loading { 
            text-align: center; 
            padding: 3rem; 
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .no-results { 
            text-align: center; 
            padding: 3rem; 
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .metrics {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .metrics h3 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .metric-card {
            background: var(--bg-tertiary);
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border-color);
            text-align: center;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .popular-queries {
            margin-top: 1.5rem;
        }
        
        .query-tag {
            display: inline-block;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            margin: 0.25rem;
            font-weight: 500;
        }
        .no-results { text-align: center; padding: 40px; color: #7f8c8d; }
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header { padding: 1.5rem; }
            .search-form { padding: 1.5rem; }
            .result-item { 
                grid-template-columns: 1fr; 
                gap: 0.75rem;
                padding: 1rem;
            }
            .result-email, .result-phone { 
                justify-self: start;
                font-size: 0.9rem;
                padding: 0.4rem 0.8rem;
            }
            .additional-info { font-size: 0.8rem; }
            .metrics-grid { grid-template-columns: 1fr; }
        }
        
        @media (max-width: 480px) {
            .header h1 { font-size: 1.5rem; }
            .search-input { font-size: 1rem; padding: 0.8rem 1rem; }
            .search-button { padding: 0.8rem 1.5rem; font-size: 1rem; }
            .result-info h3 { font-size: 1.1rem; }
            .result-email, .result-phone { font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <button class="theme-toggle" onclick="toggleTheme()" title="Переключить тему">
                🌙
            </button>
            <h1>{$config['app']['name']}</h1>
            <div class="user-info">
                Телефонный справочник организации
            </div>
        </div>
        
        <div class="search-form">
            <input type="text" id="searchInput" class="search-input" placeholder="Поиск по ФИО, телефону, должности, отделу или email..." autocomplete="off">
            <button id="searchButton" class="search-button">🔍 Поиск</button>
        </div>
        
        <div class="results" id="results">
            <div class="no-results">Введите запрос для поиска сотрудников</div>
        </div>
        
        <div class="metrics" id="metrics">
            <h3>📊 Статистика использования</h3>
            <div class="metrics-grid" id="metricsGrid">
                <div class="metric-card">
                    <div class="metric-value" id="totalSearches">-</div>
                    <div class="metric-label">Всего поисков</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="uniqueUsers">-</div>
                    <div class="metric-label">Пользователей сегодня</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="popularCount">-</div>
                    <div class="metric-label">Популярных запросов</div>
                </div>
            </div>
            <div class="popular-queries" id="popularQueries">
                <h4>🔥 Популярные запросы:</h4>
                <div id="queryTags"></div>
            </div>
        </div>
    </div>

    <script>
        // v3.1.1 - Исправлены JavaScript ошибки
        // Переключение темы
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Обновляем иконку
            const themeToggle = document.querySelector('.theme-toggle');
            themeToggle.textContent = newTheme === 'dark' ? '☀️' : '🌙';
        }
        
        // Загрузка сохраненной темы
        function loadTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
            const themeToggle = document.querySelector('.theme-toggle');
            themeToggle.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        }
        
        // Загрузка метрик
        function loadMetrics() {
            fetch('{$config['paths']['base_url']}/api_metrics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Ошибка загрузки метрик:', data.error);
                        return;
                    }
                    
                    document.getElementById('totalSearches').textContent = data.total_searches || 0;
                    document.getElementById('uniqueUsers').textContent = data.unique_users_today || 0;
                    document.getElementById('popularCount').textContent = Object.keys(data.popular_queries || {}).length;
                    
                    // Отображаем популярные запросы
                    const queryTags = document.getElementById('queryTags');
                    queryTags.innerHTML = '';
                    
                    Object.entries(data.popular_queries || {}).slice(0, 10).forEach(([query, count]) => {
                        const tag = document.createElement('span');
                        tag.className = 'query-tag';
                        tag.textContent = query + ' (' + count + ')';
                        tag.title = 'Найдено ' + count + ' раз';
                        queryTags.appendChild(tag);
                    });
                })
                .catch(error => {
                    console.error('Ошибка загрузки метрик:', error);
                });
        }
        
        
        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
            loadMetrics();
            
            // Обновляем метрики каждые 30 секунд
            setInterval(loadMetrics, 30000);
        });
        
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');
        const results = document.getElementById('results');
        let searchTimeout;

        function performSearch() {
            const query = searchInput.value.trim();
            
            if (!query) {
                results.innerHTML = '<div class="no-results">Введите запрос для поиска сотрудников</div>';
                return;
            }

            results.innerHTML = '<div class="loading">Поиск...</div>';

            fetch('{$config['paths']['base_url']}/api_search.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        results.innerHTML = '<div class="no-results">Ошибка поиска: ' + data.error + '</div>';
                        return;
                    }

                    if (data.results.length === 0) {
                        results.innerHTML = '<div class="no-results">Ничего не найдено</div>';
                        return;
                    }

                    let html = '';
                    data.results.forEach(function(person) {
                        const phone = person.phone || '';
                        const mobile = person.mobile || '';
                        const email = person.email || '';
                        const title = person.title || '';
                        const department = person.department || '';
                        
                        // Формируем дополнительную информацию (без email и телефона)
                        let additionalInfo = [];
                        if (mobile) additionalInfo.push('Моб: ' + mobile);
                        if (title) additionalInfo.push('Должность: ' + title);
                        if (department) additionalInfo.push('Отдел: ' + department);
                        
                        const additionalText = additionalInfo.length > 0 ? additionalInfo.join(' • ') : 'Дополнительная информация не указана';
                        
                        // Формируем email ссылку
                        let emailHtml = '';
                        if (email) {
                            emailHtml = '<a href="mailto:' + email + '" class="result-email" title="Отправить email">' + email + '</a>';
                        } else {
                            emailHtml = '<span class="result-email" style="opacity: 0.5;">Нет email</span>';
                        }
                        
                        // Формируем телефон ссылку
                        let phoneHtml = '';
                        if (phone) {
                            phoneHtml = '<a href="sip:' + phone + '" class="result-phone" title="Позвонить">' + phone + '</a>';
                        } else if (mobile) {
                            phoneHtml = '<a href="sip:' + mobile + '" class="result-phone" title="Позвонить на мобильный">' + mobile + '</a>';
                        } else {
                            phoneHtml = '<span class="result-phone" style="opacity: 0.5;">Нет телефона</span>';
                        }
                        
                        html += '<div class="result-item">' +
                            '<div class="result-info">' +
                                '<h3>' + person.name + '</h3>' +
                                '<p class="additional-info">' + additionalText + '</p>' +
                            '</div>' +
                            emailHtml +
                            phoneHtml +
                        '</div>';
                    });

                    results.innerHTML = html;
                    
                    // Обновляем метрики после успешного поиска
                    setTimeout(loadMetrics, 1000);
                })
                .catch(error => {
                    results.innerHTML = '<div class="no-results">Ошибка соединения</div>';
                });
        }

        searchButton.addEventListener('click', performSearch);
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Поиск с задержкой при вводе (автопоиск)
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                searchTimeout = setTimeout(performSearch, 500);
            } else if (query.length === 0) {
                results.innerHTML = '<div class="no-results">Введите запрос для поиска сотрудников</div>';
            }
        });
    </script>
</body>
</html>
HTML;

    return new Response($html);
}

/**
 * Показать страницу входа
 */
function showLoginPage(): Response {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - StaffSearch</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .login-container h1 { text-align: center; color: #2c3e50; margin-bottom: 10px; font-size: 28px; }
        .login-container h2 { text-align: center; color: #7f8c8d; margin-bottom: 30px; font-size: 16px; font-weight: normal; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #2c3e50; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px; }
        .form-group input:focus { outline: none; border-color: #3498db; }
        .login-button { width: 100%; background: #3498db; color: white; border: none; padding: 12px; border-radius: 6px; font-size: 16px; cursor: pointer; }
        .login-button:hover { background: #2980b9; }
        .error { background: #e74c3c; color: white; padding: 10px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        @media (max-width: 480px) {
            .login-container { margin: 20px; padding: 30px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>StaffSearch</h1>
        <h2>Вход в систему</h2>
        
        <form method="POST" action="{$config['paths']['base_url']}/login">
            <div class="form-group">
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="login-button">Войти</button>
        </form>
    </div>
</body>
</html>
HTML;

    return new Response($html);
}

/**
 * Обработка входа
 */
function handleLogin(array $config): Response {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        return new Response(showLoginPage()->getContent() . '<div class="error">Заполните все поля</div>', 400);
    }
    
    // Простая проверка (замените на реальную LDAP аутентификацию)
    if (authenticateUser($username, $password, $config)) {
        $_SESSION['user'] = [
            'username' => $username,
            'name' => $username,
            'is_admin' => in_array($username, $config['ldap']['admin_logins'])
        ];
        
        return redirectToHome();
    } else {
        return new Response(showLoginPage()->getContent() . '<div class="error">Неверные учетные данные</div>', 401);
    }
}

/**
 * Аутентификация пользователя
 */
function authenticateUser(string $username, string $password, array $config): bool {
    try {
        // Подключение к LDAP серверу
        $ldap = ldap_connect($config['ldap']['server'], $config['ldap']['port']);
        
        if (!$ldap) {
            logError('LDAP connection failed');
            return false;
        }
        
        // Настройка параметров LDAP
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        
        // Формируем DN пользователя
        $userDN = $username . '@ronc.ru';
        
        // Попытка привязки с учетными данными пользователя
        $bind = ldap_bind($ldap, $userDN, $password);
        
        ldap_close($ldap);
        
        if ($bind) {
            logError('Successful LDAP authentication for user: ' . $username);
            return true;
        } else {
            logError('Failed LDAP authentication for user: ' . $username);
            return false;
        }
        
    } catch (Exception $e) {
        logError('LDAP Auth Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Обработка выхода
 */
function handleLogout(): Response {
    session_destroy();
    return redirectToLogin();
}

/**
 * Обработка поиска
 */
function handleSearch(array $config): Response {
    // Убираем проверку авторизации - поиск доступен всем
    
    $query = trim($_GET['q'] ?? '');
    
    if (empty($query)) {
        return new Response(json_encode(['results' => []]), 200, ['Content-Type' => 'application/json']);
    }
    
    try {
        $results = searchStaff($query, $config);
        
        return new Response(json_encode([
            'results' => $results,
            'query' => $query,
            'count' => count($results)
        ]), 200, ['Content-Type' => 'application/json']);
        
    } catch (Exception $e) {
        logError('Search failed: ' . $e->getMessage());
        return new Response(json_encode(['error' => 'Search failed']), 500, ['Content-Type' => 'application/json']);
    }
}

/**
 * Поиск сотрудников
 */
function searchStaff(string $query, array $config): array {
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
        
        // Поиск пользователей по ФИО и телефону
        $searchFilter = "(|(cn=*{$query}*)(displayName=*{$query}*)(telephoneNumber=*{$query}*)(mobile=*{$query}*)(sAMAccountName=*{$query}*))";
        
        $searchResult = ldap_search(
            $ldap, 
            $config['ldap']['base_dn'], 
            $searchFilter,
            [
                'cn',           // Полное имя
                'displayName',  // Отображаемое имя
                'telephoneNumber', // Рабочий телефон
                'mobile',       // Мобильный телефон
                'mail',         // Email
                'title',        // Должность
                'department',   // Отдел
                'sAMAccountName' // Имя пользователя
            ],
            0,
            50 // Ограничиваем результат 50 записями
        );
        
        if (!$searchResult) {
            throw new Exception('Ошибка поиска в LDAP: ' . ldap_error($ldap));
        }
        
        $entries = ldap_get_entries($ldap, $searchResult);
        ldap_close($ldap);
        
        $results = [];
        
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            
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
        
        return $results;
        
    } catch (Exception $e) {
        logError('LDAP Search Error: ' . $e->getMessage());
        
        // В случае ошибки LDAP возвращаем тестовые данные
        $testData = [
            [
                'name' => 'Иванов Иван Иванович',
                'phone' => '1234',
                'mobile' => '+7-999-123-45-67',
                'email' => 'ivanov@ronc.ru',
                'title' => 'Менеджер',
                'department' => 'Отдел продаж'
            ],
            [
                'name' => 'Петров Петр Петрович',
                'phone' => '5678',
                'mobile' => '+7-999-567-89-01',
                'email' => 'petrov@ronc.ru',
                'title' => 'Разработчик',
                'department' => 'IT отдел'
            ]
        ];
        
        // Фильтруем тестовые данные по запросу
        $filtered = array_filter($testData, function($person) use ($query) {
            return stripos($person['name'], $query) !== false || 
                   stripos($person['phone'], $query) !== false ||
                   stripos($person['mobile'], $query) !== false;
        });
        
        return array_values($filtered);
    }
}

/**
 * Получить текущего пользователя
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Перенаправление на главную страницу
 */
function redirectToHome(): Response {
    global $config;
    return new Response('', 302, ['Location' => $config['paths']['base_url'] . '/']);
}

/**
 * Перенаправление на страницу входа
 */
function redirectToLogin(): Response {
    global $config;
    return new Response('', 302, ['Location' => $config['paths']['base_url'] . '/login']);
}

/**
 * Обработка ошибок
 */
function handleError(Throwable $e, array $config): void {
    logError('Application Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    if ($config['app']['debug']) {
        echo '<h1>Ошибка приложения</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Внутренняя ошибка сервера</h1>';
        echo '<p>Пожалуйста, попробуйте позже или обратитесь к администратору.</p>';
    }
}

/**
 * Логирование ошибок
 */
function logError(string $message): void {
    error_log(date('Y-m-d H:i:s') . ' - ' . $message);
}

/**
 * Простой класс Response
 */
class Response {
    private string $content;
    private int $statusCode;
    private array $headers;
    
    public function __construct(string $content = '', int $statusCode = 200, array $headers = []) {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }
    
    public function send(): void {
        http_response_code($this->statusCode);
        
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        echo $this->content;
    }
    
    public function getContent(): string {
        return $this->content;
    }
}
?>
