# 🚀 Руководство по развертыванию

Это руководство поможет вам развернуть Corporate Phone Directory в различных средах.

## 📋 Предварительные требования

### Системные требования

- **PHP**: 8.3 или выше
- **Веб-сервер**: Apache 2.4+ или Nginx 1.18+
- **Расширения PHP**:
  - `ldap` - для работы с Active Directory
  - `json` - для API ответов
  - `mbstring` - для работы с UTF-8
  - `openssl` - для SSL соединений (опционально)

### Сетевые требования

- Доступ к LDAP серверу (обычно порт 389 или 636)
- Возможность создания исходящих соединений
- Доступ к Active Directory для чтения

## 🏗️ Установка

### 1. Загрузка проекта

```bash
# Клонирование из Git
git clone https://github.com/yourusername/corporate-phone-directory.git
cd corporate-phone-directory

# Или загрузка ZIP архива
wget https://github.com/yourusername/corporate-phone-directory/archive/main.zip
unzip main.zip
cd corporate-phone-directory-main
```

### 2. Настройка конфигурации

```bash
# Копирование примера конфигурации
cp config.example.php config.php

# Редактирование конфигурации
nano config.php
```

### 3. Создание необходимых директорий

```bash
# Создание директории для логов
mkdir -p logs
chmod 755 logs

# Создание директории для загрузок (если планируется)
mkdir -p uploads
chmod 755 uploads

# Установка правильных прав доступа
chown -R www-data:www-data .
chmod -R 755 .
```

## ⚙️ Конфигурация веб-сервера

### Apache

#### 1. Виртуальный хост

```apache
<VirtualHost *:80>
    ServerName phonebook.yourcompany.com
    DocumentRoot /var/www/html/corporate-phone-directory
    
    <Directory /var/www/html/corporate-phone-directory>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Логи
    ErrorLog ${APACHE_LOG_DIR}/phonebook_error.log
    CustomLog ${APACHE_LOG_DIR}/phonebook_access.log combined
</VirtualHost>
```

#### 2. .htaccess файл

```apache
# Включение mod_rewrite
RewriteEngine On

# Перенаправление на HTTPS (рекомендуется)
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Обработка API запросов
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api_$1.php [QSA,L]

# Безопасность
<Files "config.php">
    Require all denied
</Files>

<Files "*.log">
    Require all denied
</Files>

# Кэширование статических файлов
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

### Nginx

#### 1. Конфигурация сервера

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name phonebook.yourcompany.com;
    
    # Перенаправление на HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name phonebook.yourcompany.com;
    
    root /var/www/html/corporate-phone-directory;
    index index.php;
    
    # SSL сертификаты
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Безопасность
    location ~ /(config\.php|\.git|\.env) {
        deny all;
    }
    
    location ~ /\.(log|sqlite) {
        deny all;
    }
    
    # API маршруты
    location /api/ {
        try_files $uri $uri/ /api_$1.php?$query_string;
    }
    
    # Основные маршруты
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # PHP обработка
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Кэширование
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }
}
```

## 🔧 Настройка PHP

### 1. PHP-FPM конфигурация

```ini
; /etc/php/8.3/fpm/pool.d/phonebook.conf
[phonebook]
user = www-data
group = www-data
listen = /var/run/php/php8.3-fpm-phonebook.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
pm.max_requests = 1000

; Безопасность
php_admin_value[disable_functions] = exec,passthru,shell_exec,system
php_admin_value[open_basedir] = /var/www/html/corporate-phone-directory
```

### 2. PHP настройки

```ini
; Рекомендуемые настройки в php.ini
memory_limit = 256M
max_execution_time = 30
max_input_time = 30
post_max_size = 10M
upload_max_filesize = 10M

; Безопасность
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; LDAP
extension=ldap
```

## 🔒 Настройка безопасности

### 1. SSL/TLS сертификаты

```bash
# Использование Let's Encrypt (рекомендуется)
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d phonebook.yourcompany.com

# Или использование собственных сертификатов
sudo cp your-certificate.crt /etc/ssl/certs/
sudo cp your-private-key.key /etc/ssl/private/
sudo chmod 600 /etc/ssl/private/your-private-key.key
```

### 2. Firewall настройки

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# iptables
sudo iptables -A INPUT -p tcp --dport 22 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 389 -j ACCEPT  # LDAP
sudo iptables -A INPUT -p tcp --dport 636 -j ACCEPT  # LDAPS
```

### 3. Настройка LDAP соединения

```bash
# Тестирование LDAP соединения
ldapsearch -H ldap://your-ldap-server.com -D "service_account@yourcompany.com" -W -b "DC=yourcompany,DC=com" "(objectClass=user)" cn
```

## 📊 Мониторинг и логирование

### 1. Настройка логирования

```bash
# Создание директории для логов
sudo mkdir -p /var/log/phonebook
sudo chown www-data:www-data /var/log/phonebook

# Настройка logrotate
sudo nano /etc/logrotate.d/phonebook
```

```bash
# /etc/logrotate.d/phonebook
/var/log/phonebook/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
    endscript
}
```

### 2. Мониторинг

```bash
# Установка мониторинга (Prometheus + Grafana)
# Создание метрик для мониторинга
```

## 🐳 Docker развертывание

### 1. Dockerfile

```dockerfile
FROM php:8.3-apache

# Установка расширений
RUN apt-get update && apt-get install -y \
    libldap2-dev \
    && docker-php-ext-install ldap \
    && rm -rf /var/lib/apt/lists/*

# Копирование файлов
COPY . /var/www/html/
WORKDIR /var/www/html

# Настройка прав
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p logs \
    && chmod 755 logs

# Настройка Apache
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
```

### 2. docker-compose.yml

```yaml
version: '3.8'

services:
  phonebook:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./logs:/var/www/html/logs
      - ./config.php:/var/www/html/config.php
    environment:
      - PHP_TIMEZONE=Europe/Moscow
    restart: unless-stopped

  nginx:
    image: nginx:alpine
    ports:
      - "443:443"
    volumes:
      - ./docker/nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/ssl/certs
    depends_on:
      - phonebook
    restart: unless-stopped
```

## 🔄 Обновление

### 1. Обновление кода

```bash
# Создание бэкапа
cp -r /var/www/html/phonebook /var/www/html/phonebook.backup.$(date +%Y%m%d)

# Обновление из Git
cd /var/www/html/phonebook
git pull origin main

# Обновление прав
chown -R www-data:www-data .
chmod -R 755 .
```

### 2. Обновление конфигурации

```bash
# Сравнение конфигураций
diff config.php config.example.php

# Применение изменений
# (вручную обновите config.php с новыми настройками)
```

## 🧪 Тестирование развертывания

### 1. Проверка работоспособности

```bash
# Проверка PHP
php -v
php -m | grep ldap

# Проверка веб-сервера
curl -I http://phonebook.yourcompany.com

# Проверка API
curl "http://phonebook.yourcompany.com/api_search.php?q=test"
```

### 2. Проверка безопасности

```bash
# SSL тест
openssl s_client -connect phonebook.yourcompany.com:443

# Проверка заголовков безопасности
curl -I https://phonebook.yourcompany.com
```

## 🚨 Устранение неполадок

### Частые проблемы

1. **Ошибка LDAP соединения**
   ```bash
   # Проверка доступности LDAP сервера
   telnet your-ldap-server.com 389
   
   # Проверка учетных данных
   ldapsearch -H ldap://your-ldap-server.com -D "service_account@yourcompany.com" -W
   ```

2. **Ошибки прав доступа**
   ```bash
   # Исправление прав
   sudo chown -R www-data:www-data /var/www/html/phonebook
   sudo chmod -R 755 /var/www/html/phonebook
   ```

3. **Проблемы с SSL**
   ```bash
   # Проверка сертификата
   openssl x509 -in /path/to/certificate.crt -text -noout
   ```

### Логи для диагностики

- **Apache**: `/var/log/apache2/phonebook_error.log`
- **Nginx**: `/var/log/nginx/error.log`
- **PHP**: `/var/log/php/error.log`
- **Приложение**: `/var/www/html/phonebook/logs/`

## 📞 Поддержка

При возникновении проблем:

1. Проверьте логи приложения
2. Убедитесь в правильности конфигурации
3. Проверьте доступность LDAP сервера
4. Обратитесь к документации
5. Создайте Issue в репозитории

---

**Удачного развертывания! 🚀**
