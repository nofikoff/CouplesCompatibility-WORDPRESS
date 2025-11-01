#!/bin/bash

# Путь к wp-content, т.к. в репозитории структура plugins/numerology-compatibility
PROJECT_PATH="/home/couplescompatibility/public_html/wp-content"
REPOSITORY="git@github.com:nofikoff/test-numerorly-WORDPRESS.git"
BRANCH="main"
SSH_HOST="root@176.9.151.51"
SSH_KEY="~/.ssh/id_rsa"
CONTROL_PATH="/tmp/ssh-deploy-control-%r@%h:%p"

set -e
set -x

# Функция для создания SSH мастер-соединения
function ssh_start() {
    ssh -i "$SSH_KEY" -o ControlMaster=auto -o ControlPath="$CONTROL_PATH" -o ControlPersist=10m -Nf "$SSH_HOST"
}

# Функция для выполнения команд через существующее соединение
function ssh_exec() {
    ssh -i "$SSH_KEY" -o ControlPath="$CONTROL_PATH" "$SSH_HOST" "$1"
}

# Функция для закрытия SSH соединения
function ssh_stop() {
    ssh -i "$SSH_KEY" -o ControlPath="$CONTROL_PATH" -O exit "$SSH_HOST" 2>/dev/null || true
}

# Убедимся что соединение закроется при выходе
trap ssh_stop EXIT

echo "🔌 Устанавливаем SSH соединение..."
ssh_start

echo "🚀 Обновление плагина на сервере..."

# Добавляем директорию в safe.directory
ssh_exec "git config --global --add safe.directory $PROJECT_PATH" 2>/dev/null || true

# Проверяем, инициализирован ли git
if ! ssh_exec "cd $PROJECT_PATH && [ -d .git ]" 2>/dev/null; then
    echo "📦 Git не инициализирован, выполняем первичную настройку..."
    ssh_exec "cd $PROJECT_PATH && git init"
    ssh_exec "cd $PROJECT_PATH && git remote add origin $REPOSITORY"
    ssh_exec "cd $PROJECT_PATH && git fetch"
    ssh_exec "cd $PROJECT_PATH && git checkout $BRANCH"
    echo "✅ Git инициализирован"
else
    echo "📥 Обновляем из репозитория..."

    # Проверяем, настроен ли remote origin
    if ! ssh_exec "cd $PROJECT_PATH && git remote get-url origin" 2>/dev/null; then
        echo "📌 Настраиваем remote origin..."
        ssh_exec "cd $PROJECT_PATH && git remote add origin $REPOSITORY"
        ssh_exec "cd $PROJECT_PATH && git fetch"
        ssh_exec "cd $PROJECT_PATH && git checkout $BRANCH"
    else
        ssh_exec "cd $PROJECT_PATH && git pull origin $BRANCH"
    fi
fi

echo "✅ Готово!"
