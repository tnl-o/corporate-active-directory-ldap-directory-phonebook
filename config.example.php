<?php
/**
 * Пример конфигурации приложения
 * 
 * Скопируйте этот файл в config.php и настройте под ваши нужды.
 * 
 * @author Your Name
 * @version 3.1.1
 * @since 2025-01-01
 */

return [
    // Основные настройки приложения
    'app' => [
        'name' => 'Corporate Phone Directory', // Название приложения
        'version' => '3.1.1', // Версия приложения
        'debug' => false, // Режим отладки (true для разработки)
        'timezone' => 'Europe/Moscow', // Часовой пояс
        'language' => 'ru', // Язык интерфейса
    ],
    
    // Настройки путей
    'paths' => [
        'base_url' => '/phonebook', // Базовый URL приложения (измените на ваш)
        'assets' => '/phonebook/assets', // Путь к статическим файлам
    ],
    
    // Настройки LDAP (Active Directory)
    'ldap' => [
        'server' => 'ldap.yourcompany.com', // Адрес LDAP сервера
        'port' => 389, // Порт LDAP (389 для обычного, 636 для SSL)
        'use_ssl' => false, // Использовать SSL соединение
        'base_dn' => 'DC=yourcompany,DC=com', // Базовый DN домена
        'search_ou' => '', // OU для поиска (пустой = весь каталог)
        'exclude_ous' => [ // Исключаемые контейнеры
            'CN=Users,DC=yourcompany,DC=com', // Стандартный контейнер Users
            'CN=Computers,DC=yourcompany,DC=com', // Контейнер компьютеров
            'OU=Service Accounts,DC=yourcompany,DC=com', // Служебные учетки
        ],
        'username_field' => 'userprincipalname', // Поле имени пользователя
        'read_user' => 'service_account@yourcompany.com', // Учетная запись для чтения
        'read_password' => 'your_service_password_here', // Пароль сервисной учетки
        'admin_logins' => ['admin', 'administrator'], // Логины администраторов
        'no_show_group' => 'HiddenUsers', // Группа скрытых пользователей
        'timeout' => 30, // Таймаут соединения в секундах
    ],
    
    // Настройки безопасности
    'security' => [
        'session_lifetime' => 3600, // Время жизни сессии в секундах (1 час)
        'require_https' => false, // Требовать HTTPS (true для продакшена)
        'max_login_attempts' => 5, // Максимальное количество попыток входа
        'enable_csrf' => true, // Включить защиту от CSRF
        'enable_xss_protection' => true, // Включить защиту от XSS
    ],
    
    // Настройки логирования
    'logging' => [
        'path' => __DIR__ . '/logs', // Путь к директории логов
        'level' => 'info', // Уровень логирования (debug, info, warning, error)
        'max_file_size' => 10485760, // Максимальный размер файла лога (10MB)
        'max_files' => 5, // Максимальное количество файлов логов
        'log_queries' => true, // Логировать поисковые запросы
        'log_errors' => true, // Логировать ошибки
    ],
    
    // Настройки метрик
    'metrics' => [
        'enabled' => true, // Включить сбор метрик
        'retention_days' => 30, // Хранить метрики N дней
        'track_popular_queries' => true, // Отслеживать популярные запросы
        'track_user_activity' => true, // Отслеживать активность пользователей
    ],
    
    // Настройки кэширования
    'cache' => [
        'enabled' => false, // Включить кэширование (требует Redis/Memcached)
        'driver' => 'file', // Драйвер кэша (file, redis, memcached)
        'ttl' => 3600, // Время жизни кэша в секундах
    ],
];
?>
