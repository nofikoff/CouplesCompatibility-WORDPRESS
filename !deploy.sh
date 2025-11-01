#!/bin/bash

# Конфигурация
PROJECT_PATH="/home/couplescompatibility/public_html/wp-content/plugins/numerology-compatibility"
REPOSITORY="git@github.com:nofikoff/test-numerorly-WORDPRESS.git"
BRANCH="main"
SSH_HOST="root@176.9.151.51"
SSH_KEY="~/.ssh/id_rsa"
TMP_DIR="/tmp/numerology-deploy-$(date +%s)"
BACKUP_DIR="/home/couplescompatibility/backups/numerology-compatibility"

# Включаем режим отладки и останавливаем выполнение при ошибках
set -e
set -x

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для выполнения команд на удаленном сервере
function ssh_exec() {
    ssh -i "$SSH_KEY" "$SSH_HOST" "$1"
}

# Функция для копирования файлов на сервер
function scp_copy() {
    scp -i "$SSH_KEY" -r "$1" "$SSH_HOST:$2"
}

# Логирование
function log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

function log_error() {
    echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1"
}

function log_warning() {
    echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

# Проверка SSH подключения
log "Проверка подключения к серверу..."
if ! ssh -i "$SSH_KEY" -o ConnectTimeout=10 "$SSH_HOST" "echo 'SSH connection successful'" > /dev/null 2>&1; then
    log_error "Не удалось подключиться к серверу $SSH_HOST"
    exit 1
fi
log "Подключение к серверу успешно"

# Создание директории для резервных копий на сервере
log "Создание директории для резервных копий..."
ssh_exec "mkdir -p $BACKUP_DIR"

# Создание резервной копии текущей версии плагина
log "Создание резервной копии текущей версии..."
BACKUP_FILE="$BACKUP_DIR/backup-$(date +%Y%m%d-%H%M%S).tar.gz"
ssh_exec "if [ -d '$PROJECT_PATH' ]; then tar -czf $BACKUP_FILE -C $(dirname $PROJECT_PATH) $(basename $PROJECT_PATH) && echo 'Backup created: $BACKUP_FILE'; else echo 'No existing plugin found, skipping backup'; fi"

# Создание временной директории на сервере
log "Создание временной директории на сервере..."
ssh_exec "mkdir -p $TMP_DIR"

# Клонирование репозитория на сервере
log "Клонирование репозитория..."
ssh_exec "cd $TMP_DIR && git clone --branch $BRANCH --depth 1 $REPOSITORY repo"

# Проверка наличия папки плагина в репозитории
log "Проверка структуры репозитория..."
ssh_exec "if [ ! -d '$TMP_DIR/repo/plugins/numerology-compatibility' ]; then echo 'Plugin directory not found in repository!'; exit 1; fi"

# Создание целевой директории если она не существует
log "Подготовка целевой директории..."
ssh_exec "mkdir -p $PROJECT_PATH"

# Синхронизация файлов (исключаем .git, node_modules, и другие ненужные файлы)
log "Синхронизация файлов плагина..."
ssh_exec "rsync -av --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.gitignore' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    $TMP_DIR/repo/plugins/numerology-compatibility/ \
    $PROJECT_PATH/"

# Установка зависимостей composer (если composer.json существует)
log "Проверка наличия composer.json..."
if ssh_exec "[ -f '$PROJECT_PATH/composer.json' ]" 2>/dev/null; then
    log "Установка зависимостей composer..."
    ssh_exec "cd $PROJECT_PATH && composer install --no-dev --optimize-autoloader --no-interaction"
else
    log_warning "composer.json не найден, пропускаем установку зависимостей"
fi

# Установка правильных прав доступа
log "Установка прав доступа..."
ssh_exec "chown -R www-data:www-data $PROJECT_PATH"
ssh_exec "find $PROJECT_PATH -type d -exec chmod 755 {} \;"
ssh_exec "find $PROJECT_PATH -type f -exec chmod 644 {} \;"

# Очистка временной директории
log "Очистка временных файлов..."
ssh_exec "rm -rf $TMP_DIR"

# Удаление старых резервных копий (оставляем только последние 5)
log "Очистка старых резервных копий..."
ssh_exec "cd $BACKUP_DIR && ls -t backup-*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm --"

# Получение информации о текущей версии
log "Получение информации о развернутой версии..."
COMMIT_HASH=$(ssh_exec "cat $TMP_DIR/repo/.git/refs/heads/$BRANCH 2>/dev/null || echo 'unknown'")
log "Развернут коммит: $COMMIT_HASH"

# Финальное сообщение
echo ""
log "${GREEN}========================================${NC}"
log "${GREEN}Деплой успешно завершен!${NC}"
log "${GREEN}========================================${NC}"
log "Путь к плагину: $PROJECT_PATH"
log "Резервная копия: $BACKUP_FILE"
log "Коммит: $COMMIT_HASH"
echo ""