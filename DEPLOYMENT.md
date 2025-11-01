# Развертывание плагина Numerology Compatibility

Этот документ описывает процесс развертывания плагина на production сервере.

## Доступные скрипты

### 1. deploy.sh - Деплой на сервере
Клонирует репозиторий напрямую на сервере и синхронизирует файлы.

**Преимущества:**
- Быстрее, так как не требует передачи файлов по сети
- Меньше использует локальные ресурсы

**Недостатки:**
- Требует наличия git и composer на сервере
- SSH ключ должен быть настроен на сервере для доступа к GitHub

### 2. deploy-rsync.sh - Деплой через rsync
Клонирует репозиторий локально, собирает зависимости и загружает на сервер через rsync.

**Преимущества:**
- Не требует git/composer на сервере
- Можно контролировать процесс сборки локально
- Безопаснее, так как на сервер загружаются только собранные файлы

**Недостатки:**
- Требует локальной установки git, composer
- Медленнее из-за передачи файлов по сети

## Подготовка к развертыванию

### Требования

**Локально (для обоих скриптов):**
- Git
- SSH доступ к серверу
- SSH ключ (~/.ssh/id_rsa)

**Для deploy-rsync.sh дополнительно:**
- Composer (если в проекте есть зависимости)
- rsync

**На сервере (для deploy.sh):**
- Git
- Composer (если в проекте есть зависимости)
- SSH ключ для доступа к GitHub

### Настройка SSH ключей

1. Локально убедитесь, что есть доступ к серверу:
```bash
ssh -i ~/.ssh/id_rsa root@176.9.151.51
```

2. Для deploy.sh убедитесь, что на сервере настроен SSH ключ для GitHub:
```bash
ssh -i ~/.ssh/id_rsa root@176.9.151.51 "ssh-keygen -t rsa -b 4096 -C 'server@example.com'"
ssh -i ~/.ssh/id_rsa root@176.9.151.51 "cat ~/.ssh/id_rsa.pub"
# Добавьте этот ключ в Deploy Keys вашего GitHub репозитория
```

## Использование

### Первый деплой

1. Сделайте скрипт исполняемым:
```bash
chmod +x deploy.sh
# или
chmod +x deploy-rsync.sh
```

2. Запустите деплой:
```bash
./deploy.sh
# или
./deploy-rsync.sh
```

### Последующие обновления

Просто запустите скрипт снова:
```bash
./deploy.sh
```

## Что делает скрипт

1. **Проверка подключения** - проверяет SSH соединение с сервером
2. **Создание резервной копии** - создает backup текущей версии плагина
3. **Клонирование репозитория** - получает последнюю версию из ветки main
4. **Синхронизация файлов** - обновляет файлы плагина на сервере
5. **Установка зависимостей** - устанавливает composer пакеты (если требуется)
6. **Установка прав** - настраивает правильные права доступа для WordPress
7. **Очистка** - удаляет временные файлы и старые бэкапы (оставляет последние 5)

## Структура путей

- **Плагин на сервере:** `/home/couplescompatibility/public_html/wp-content/plugins/numerology-compatibility`
- **Резервные копии:** `/home/couplescompatibility/backups/numerology-compatibility`
- **Временные файлы:** `/tmp/numerology-deploy-*`

## Резервные копии

Скрипт автоматически создает резервную копию перед каждым обновлением:
- Формат имени: `backup-YYYYMMDD-HHMMSS.tar.gz`
- Хранятся в: `/home/couplescompatibility/backups/numerology-compatibility/`
- Автоматически удаляются старые копии (оставляются последние 5)

### Восстановление из резервной копии

Если нужно откатиться к предыдущей версии:

```bash
ssh -i ~/.ssh/id_rsa root@176.9.151.51

# Посмотреть доступные бэкапы
ls -lh /home/couplescompatibility/backups/numerology-compatibility/

# Восстановить из бэкапа
cd /home/couplescompatibility/public_html/wp-content/plugins/
rm -rf numerology-compatibility
tar -xzf /home/couplescompatibility/backups/numerology-compatibility/backup-YYYYMMDD-HHMMSS.tar.gz
chown -R www-data:www-data numerology-compatibility
```

## Исключаемые файлы

При синхронизации исключаются следующие файлы и директории:
- `.git` - история git
- `node_modules` - зависимости npm (если есть)
- `.gitignore` - конфигурация git
- `.DS_Store` - системные файлы macOS
- `*.log` - лог файлы
- `tests` - тесты (только в deploy-rsync.sh)
- `composer.json`, `package.json` и lock файлы (только в deploy-rsync.sh)

## Устранение неполадок

### Ошибка: "Permission denied"
Проверьте SSH ключ и права доступа:
```bash
chmod 600 ~/.ssh/id_rsa
ssh -i ~/.ssh/id_rsa root@176.9.151.51
```

### Ошибка: "composer: command not found"
Установите composer на сервере или используйте deploy-rsync.sh

### Ошибка: "Could not resolve host github.com"
На сервере нет доступа к GitHub. Используйте deploy-rsync.sh

### Файлы не обновляются
Проверьте права доступа на сервере:
```bash
ssh -i ~/.ssh/id_rsa root@176.9.151.51 "ls -la /home/couplescompatibility/public_html/wp-content/plugins/"
```

## Настройка скриптов

Если нужно изменить параметры (пути, SSH ключ, сервер), отредактируйте переменные в начале скрипта:

```bash
PROJECT_PATH="/home/couplescompatibility/public_html/wp-content/plugins/numerology-compatibility"
REPOSITORY="git@github.com:nofikoff/test-numerorly-WORDPRESS.git"
BRANCH="main"
SSH_HOST="root@176.9.151.51"
SSH_KEY="~/.ssh/id_rsa"
```

## Безопасность

- SSH ключи должны иметь права 600
- На production используйте отдельный SSH ключ с ограниченными правами
- Регулярно проверяйте резервные копии
- Храните резервные копии в безопасном месте
- Рассмотрите возможность использования deploy keys вместо личных SSH ключей

## Рекомендации

1. **Тестируйте перед деплоем** - проверяйте изменения на dev/staging окружении
2. **Используйте git tags** - помечайте stable релизы тегами
3. **Проверяйте после деплоя** - убедитесь, что сайт работает корректно
4. **Мониторинг** - следите за логами WordPress после обновления
5. **Документируйте изменения** - ведите CHANGELOG.md