<?php
/**
 * Ультрасовременный телефонный справочник
 */

require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($config['app']['name']) ?> - Ультрасовременный поиск</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            /* Ультрасовременная цветовая палитра */
            --primary: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary: #1e293b;
            --accent: #f59e0b;
            --success: #10b981;
            --error: #ef4444;
            
            /* Градиенты */
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-dark: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            
            /* Фоны */
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-card: rgba(255, 255, 255, 0.95);
            --bg-glass: rgba(255, 255, 255, 0.1);
            
            /* Текст */
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            
            /* Границы и тени */
            --border: rgba(255, 255, 255, 0.2);
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.3);
            
            /* Радиусы */
            --radius-sm: 6px;
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            
            /* Анимации */
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: rgba(30, 41, 59, 0.95);
            --bg-glass: rgba(255, 255, 255, 0.05);
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--gradient-primary);
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Анимированный фон */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes backgroundShift {
            0%, 100% { transform: translateX(0) translateY(0); }
            25% { transform: translateX(-10px) translateY(-5px); }
            50% { transform: translateX(5px) translateY(-10px); }
            75% { transform: translateX(-5px) translateY(5px); }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        /* Современный хедер */
        .header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 200px;
            height: 200px;
            background: var(--gradient-accent);
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translateX(-50%) scale(1); opacity: 0.3; }
            50% { transform: translateX(-50%) scale(1.1); opacity: 0.5; }
        }

        .logo {
            font-size: 3rem;
            font-weight: 700;
            background: var(--gradient-accent);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .subtitle {
            font-size: 1.2rem;
            color: var(--text-secondary);
            font-weight: 400;
            margin-bottom: 2rem;
        }

        .theme-toggle {
            position: fixed;
            top: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border: none;
            border-radius: 50%;
            background: var(--bg-glass);
            backdrop-filter: blur(10px);
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-glow);
        }

        /* Стеклянная форма поиска */
        .search-form {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .search-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--gradient-accent);
        }

        .search-input {
            width: 100%;
            padding: 1.5rem 2rem;
            border: 2px solid transparent;
            border-radius: var(--radius-lg);
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 500;
            transition: var(--transition);
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--shadow-glow);
            transform: translateY(-2px);
        }

        .search-input::placeholder {
            color: var(--text-muted);
            font-weight: 400;
        }

        .search-button {
            width: 100%;
            padding: 1.5rem 2rem;
            border: none;
            border-radius: var(--radius-lg);
            background: var(--gradient-primary);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .search-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .search-button:hover::before {
            left: 100%;
        }

        .search-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }

        .search-button:active {
            transform: translateY(0);
        }

        /* Результаты поиска */
        .results {
            margin-bottom: 3rem;
        }

        .result-item {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .result-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--gradient-accent);
            transform: scaleY(0);
            transition: var(--transition);
        }

        .result-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .result-item:hover::before {
            transform: scaleY(1);
        }

        .result-info h3 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .additional-info {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }

        .result-email, .result-phone {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }

        .result-email {
            background: var(--gradient-secondary);
            color: white;
        }

        .result-phone {
            background: var(--gradient-accent);
            color: white;
        }

        .result-email:hover, .result-phone:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Состояния */
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

        /* Метрики */
        .metrics {
            background: var(--bg-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            padding: 2rem;
            margin-top: 3rem;
        }

        .metrics h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .metric-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .logo {
                font-size: 2rem;
            }
            
            .search-form {
                padding: 1.5rem;
            }
            
            .result-item {
                padding: 1.5rem;
            }
            
            .theme-toggle {
                top: 1rem;
                right: 1rem;
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }

        /* Анимации появления */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-up {
            animation: slideUp 0.4s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" onclick="toggleTheme()">🌙</button>
    
    <div class="container">
        <header class="header fade-in">
            <h1 class="logo">📞 PhoneBook</h1>
            <p class="subtitle">Ультрасовременный поиск сотрудников</p>
        </header>
        
        <div class="search-form slide-up">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Поиск по ФИО, телефону, должности, отделу или email..." autocomplete="off">
            <button id="searchButton" class="search-button">🚀 Найти сотрудника</button>
        </div>
        
        <div class="results" id="results">
            <div class="no-results">Введите запрос для поиска сотрудников</div>
        </div>
        
        <div class="metrics" id="metrics">
            <h3>📊 Статистика использования</h3>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-value" id="totalSearches">0</div>
                    <div class="metric-label">Всего поисков</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="uniqueUsers">0</div>
                    <div class="metric-label">Уникальных пользователей</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="hourlySearches">0</div>
                    <div class="metric-label">Поисков за час</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Переключение темы
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
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
            fetch('<?= $config['paths']['base_url'] ?>/api_metrics.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('Ошибка загрузки метрик:', data.error);
                        return;
                    }
                    
                    document.getElementById('totalSearches').textContent = data.total_searches || 0;
                    document.getElementById('uniqueUsers').textContent = data.unique_users_today || 0;
                    document.getElementById('hourlySearches').textContent = data.hourly_searches || 0;
                })
                .catch(error => {
                    console.error('Ошибка загрузки метрик:', error);
                });
        }

        // Поиск
        function performSearch() {
            const query = searchInput.value.trim();
            
            if (!query) {
                results.innerHTML = '<div class="no-results">Введите запрос для поиска сотрудников</div>';
                return;
            }

            results.innerHTML = '<div class="loading">🔍 Поиск сотрудников...</div>';

            fetch('<?= $config['paths']['base_url'] ?>/api_search.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        results.innerHTML = '<div class="no-results">❌ Ошибка поиска: ' + data.error + '</div>';
                        return;
                    }

                    if (data.results.length === 0) {
                        results.innerHTML = '<div class="no-results">😔 Ничего не найдено</div>';
                        return;
                    }

                    let html = '';
                    data.results.forEach(function(person, index) {
                        const phone = person.phone || '';
                        const mobile = person.mobile || '';
                        const email = person.email || '';
                        const title = person.title || '';
                        const department = person.department || '';
                        
                        let additionalInfo = [];
                        if (mobile) additionalInfo.push('📱 ' + mobile);
                        if (title) additionalInfo.push('💼 ' + title);
                        if (department) additionalInfo.push('🏢 ' + department);
                        
                        const additionalText = additionalInfo.length > 0 ? additionalInfo.join(' • ') : 'Дополнительная информация не указана';
                        
                        let emailHtml = '';
                        if (email) {
                            emailHtml = '<a href="mailto:' + email + '" class="result-email" title="Отправить email">📧 ' + email + '</a>';
                        }
                        
                        let phoneHtml = '';
                        if (phone) {
                            phoneHtml = '<a href="sip:' + phone + '" class="result-phone" title="Позвонить">📞 ' + phone + '</a>';
                        } else if (mobile) {
                            phoneHtml = '<a href="sip:' + mobile + '" class="result-phone" title="Позвонить на мобильный">📱 ' + mobile + '</a>';
                        }
                        
                        html += '<div class="result-item slide-up" style="animation-delay: ' + (index * 0.1) + 's">' +
                            '<div class="result-info">' +
                                '<h3>' + person.name + '</h3>' +
                                '<p class="additional-info">' + additionalText + '</p>' +
                                emailHtml + phoneHtml +
                            '</div>' +
                        '</div>';
                    });
                    
                    results.innerHTML = html;
                    loadMetrics();
                })
                .catch(error => {
                    results.innerHTML = '<div class="no-results">❌ Ошибка соединения</div>';
                    console.error('Ошибка поиска:', error);
                });
        }

        // Инициализация
        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
            loadMetrics();
            
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const results = document.getElementById('results');
            let searchTimeout;

            searchButton.addEventListener('click', performSearch);
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(performSearch, 500);
            });
            
            setInterval(loadMetrics, 30000);
        });
    </script>
</body>
</html>
