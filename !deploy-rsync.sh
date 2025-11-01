#!/bin/bash

# Конфигурация
PROJECT_PATH="/home/couplescompatibility/public_html/wp-content/plugins/numerology-compatibility"
REPOSITORY="git@github.com:nofikoff/test-numerorly-WORDPRESS.git"
BRANCH="main"
SSH_HOST="root@176.9.151.51"
SSH_KEY="~/.ssh/id_rsa"
LOCAL_TMP_DIR="/tmp/numerology-deploy-$(date +%s)"
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

# Очистка при завершении
function cleanup() {
    if [ -d "$LOCAL_TMP_DIR" ]; then
        log "Очистка локальных временных файлов..."
        rm -rf "$LOCAL_TMP_DIR"
    fi
}
trap cleanup EXIT

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

# Создание локальной временной директории
log "Создание локальной временной директории..."
mkdir -p "$LOCAL_TMP_DIR"

# Клонирование репозитория локально
log "Клонирование репозитория локально..."
cd "$LOCAL_TMP_DIR"
git clone --branch "$BRANCH" --depth 1 "$REPOSITORY" repo

# Проверка наличия папки плагина в репозитории
log "Проверка структуры репозитория..."
if [ ! -d "$LOCAL_TMP_DIR/repo/plugins/numerology-compatibility" ]; then
    log_error "Папка плагина не найдена в репозитории!"
    exit 1
fi

# Установка composer зависимостей локально (если требуется)
if [ -f "$LOCAL_TMP_DIR/repo/plugins/numerology-compatibility/composer.json" ]; then
    log "Установка зависимостей composer локально..."
    cd "$LOCAL_TMP_DIR/repo/plugins/numerology-compatibility"
    composer install --no-dev --optimize-autoloader --no-interaction
fi

# Создание целевой директории на сервере если она не существует
log "Подготовка целевой директории на сервере..."
ssh_exec "mkdir -p $PROJECT_PATH"

# Синхронизация файлов через rsync
log "Синхронизация файлов плагина через rsync..."
rsync -avz --delete \
    -e "ssh -i $SSH_KEY" \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.gitignore' \
    --exclude='.DS_Store' \
    --exclude='*.log' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='tests' \
    "$LOCAL_TMP_DIR/repo/plugins/numerology-compatibility/" \
    "$SSH_HOST:$PROJECT_PATH/"

# Установка правильных прав доступа
log "Установка прав доступа..."
ssh_exec "chown -R www-data:www-data $PROJECT_PATH"
ssh_exec "find $PROJECT_PATH -type d -exec chmod 755 {} \;"
ssh_exec "find $PROJECT_PATH -type f -exec chmod 644 {} \;"

# Удаление старых резервных копий (оставляем только последние 5)
log "Очистка старых резервных копий..."
ssh_exec "cd $BACKUP_DIR && ls -t backup-*.tar.gz 2>/dev/null | tail -n +6 | xargs -r rm --"

# Получение информации о текущей версии
log "Получение информации о развернутой версии..."
COMMIT_HASH=$(cd "$LOCAL_TMP_DIR/repo" && git rev-parse HEAD)
COMMIT_MSG=$(cd "$LOCAL_TMP_DIR/repo" && git log -1 --pretty=%B)

# Финальное сообщение
echo ""
log "${GREEN}========================================${NC}"
log "${GREEN}Деплой успешно завершен!${NC}"
log "${GREEN}========================================${NC}"
log "Путь к плагину: $PROJECT_PATH"
log "Резервная копия: $BACKUP_FILE"
log "Коммит: $COMMIT_HASH"
log "Сообщение: $COMMIT_MSG"
echo ""