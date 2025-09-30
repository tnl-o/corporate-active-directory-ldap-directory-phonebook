# Corporate Phone Directory или Планетарный Адресный Центр Абонентских Ключей (ПАЦАК 3.0)

[![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Version](https://img.shields.io/badge/Version-3.1.1-orange.svg)](CHANGELOG.md)

Ультрасовременный веб-интерфейс для поиска сотрудников в корпоративном Active Directory с поддержкой LDAP.

## ✨ Особенности

- 🔍 **Быстрый поиск** по ФИО, телефону, должности, отделу и email
- 🎨 **Современный дизайн** с градиентами, анимациями и стеклянными эффектами
- 🌙 **Темная/светлая тема** с автоматическим сохранением предпочтений
- 📊 **Метрики использования** с отслеживанием популярных запросов
- 📱 **Адаптивный дизайн** для всех устройств
- 🔒 **Безопасность** с защитой от XSS и CSRF
- ⚡ **Высокая производительность** с оптимизированными LDAP запросами
- 🚫 **Фильтрация** тестовых записей и системных объектов

## 🚀 Быстрый старт

### Требования

- PHP 8.3 или выше
- Веб-сервер (Apache/Nginx)
- Доступ к Active Directory через LDAP
- Учетная запись с правами чтения LDAP

### Установка

1. **Клонируйте репозиторий:**
   ```bash
   git clone https://github.com/yourusername/corporate-phone-directory.git
   cd corporate-phone-directory
   ```

2. **Настройте конфигурацию:**
   ```bash
   cp config.example.php config.php
   # Отредактируйте config.php под ваши настройки
   ```

3. **Создайте директории:**
   ```bash
   mkdir -p logs
   chmod 755 logs
   ```

4. **Настройте веб-сервер:**
   
   **Apache (.htaccess):**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

   **Nginx:**
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

## ⚙️ Конфигурация

### Основные настройки

Отредактируйте файл `config.php`:

```php
return [
    'app' => [
        'name' => 'Corporate Phone Directory',
        'version' => '3.1.1',
        'debug' => false,
        'timezone' => 'Europe/Moscow',
    ],
    
    'paths' => [
        'base_url' => '/phonebook', // Измените на ваш путь
    ],
    
    'ldap' => [
        'server' => 'ldap.yourcompany.com',
        'port' => 389,
        'base_dn' => 'DC=yourcompany,DC=com',
        'read_user' => 'service_account@yourcompany.com',
        'read_password' => 'your_password_here',
        // ... другие настройки
    ],
];
```

### Настройка LDAP

1. **Создайте сервисную учетную запись** в Active Directory с правами чтения
2. **Настройте базовый DN** вашего домена
3. **Укажите исключаемые контейнеры** (Users, Computers, Service Accounts)
4. **Настройте SSL** если требуется

### Безопасность

- Установите `require_https => true` для продакшена
- Используйте сильные пароли для сервисных учетных записей
- Регулярно ротируйте пароли
- Настройте мониторинг логов

## 📁 Структура проекта

```
corporate-phone-directory/
├── index.php              # Главная страница приложения
├── api_search.php         # API для поиска сотрудников
├── api_metrics.php        # API для метрик использования
├── config.php             # Конфигурация (не коммитится)
├── config.example.php     # Пример конфигурации
├── logs/                  # Директория логов
├── README.md              # Документация
├── LICENSE                # Лицензия MIT
├── CHANGELOG.md           # История изменений
├── CONTRIBUTING.md        # Руководство для контрибьюторов
└── .gitignore            # Игнорируемые файлы
```

## 🔧 API

### Поиск сотрудников

**Endpoint:** `GET /api_search.php`

**Параметры:**
- `q` (string) - Поисковый запрос

**Пример:**
```bash
curl "https://yourdomain.com/api_search.php?q=иванов"
```

**Ответ:**
```json
{
  "results": [
    {
      "name": "Иванов Иван Иванович",
      "phone": "1234",
      "mobile": "8(999)123-45-67",
      "email": "i.ivanov@company.com",
      "title": "Инженер",
      "department": "IT отдел",
      "username": "i.ivanov"
    }
  ],
  "query": "иванов",
  "count": 1
}
```

### Метрики использования

**Endpoint:** `GET /api_metrics.php`

**Ответ:**
```json
{
  "total_searches": 1250,
  "unique_users_today": 45,
  "hourly_searches": 12,
  "popular_queries": {
    "иванов": 25,
    "петров": 18,
    "сидоров": 15
  }
}
```

## 🎨 Кастомизация

### Темы

Приложение поддерживает светлую и темную темы. Тема автоматически сохраняется в localStorage.

### Стили

Основные CSS переменные в `index.php`:

```css
:root {
    --primary: #6366f1;
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --bg-card: rgba(255, 255, 255, 0.95);
    --shadow-glow: 0 0 20px rgba(99, 102, 241, 0.3);
}
```

### Локализация

Для изменения языка отредактируйте `config.php`:

```php
'app' => [
    'language' => 'en', // ru, en, de, fr
],
```

## 📊 Мониторинг

### Логи

Логи сохраняются в директории `logs/`:
- `php_errors.log` - Ошибки PHP
- `usage_metrics.json` - Метрики использования

### Метрики

Приложение автоматически собирает:
- Общее количество поисков
- Уникальных пользователей за день
- Поисков за час
- Популярные запросы

## 🔒 Безопасность

### Реализованные меры

- ✅ Защита от XSS атак
- ✅ Защита от CSRF атак
- ✅ Валидация входных данных
- ✅ Экранирование LDAP запросов
- ✅ Логирование подозрительной активности

### Рекомендации

1. **Используйте HTTPS** в продакшене
2. **Регулярно обновляйте** пароли сервисных учетных записей
3. **Мониторьте логи** на предмет подозрительной активности
4. **Ограничьте доступ** к API по IP адресам если возможно

## 🐛 Отладка

### Включение режима отладки

```php
'app' => [
    'debug' => true,
],
```

### Проверка LDAP соединения

Создайте файл `test_ldap.php`:

```php
<?php
require_once 'config.php';

$ldap = ldap_connect($config['ldap']['server'], $config['ldap']['port']);
if (!$ldap) {
    die('Ошибка подключения к LDAP');
}

$bind = ldap_bind($ldap, $config['ldap']['read_user'], $config['ldap']['read_password']);
if (!$bind) {
    die('Ошибка авторизации: ' . ldap_error($ldap));
}

echo 'LDAP соединение успешно!';
ldap_close($ldap);
?>
```

## 🤝 Участие в разработке

Мы приветствуем вклад в развитие проекта! См. [CONTRIBUTING.md](CONTRIBUTING.md) для подробностей.

### Процесс

1. Fork репозитория
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Commit изменения (`git commit -m 'Add amazing feature'`)
4. Push в branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

## 📝 Лицензия

Этот проект лицензирован под MIT License - см. файл [LICENSE](LICENSE) для подробностей.

## 🙏 Благодарности

- [Inter Font](https://rsms.me/inter/) - современный шрифт
- [PHP LDAP](https://www.php.net/manual/en/book.ldap.php) - расширение для работы с LDAP
- Сообщество разработчиков за обратную связь и предложения

## 📞 Поддержка

Если у вас есть вопросы или проблемы:

1. Проверьте [Issues](https://github.com/yourusername/corporate-phone-directory/issues)
2. Создайте новый Issue с подробным описанием
3. Свяжитесь с нами: support@yourcompany.com

---

**Сделано с ❤️ для корпоративных пользователей**
