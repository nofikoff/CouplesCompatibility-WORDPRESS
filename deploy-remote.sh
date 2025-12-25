#!/bin/bash

# Load environment variables from .env file
if [ -f ".env" ]; then
    export $(grep -v '^#' .env | xargs)
fi

# Configuration from environment variables
PROJECT_PATH="${PROJECT_PATH:-/path/to/wp-content}"
REPOSITORY="${REPOSITORY:-git@github.com:user/repo.git}"
BRANCH="${BRANCH:-main}"
SSH_HOST="${SSH_HOST:-user@server}"
SSH_KEY="${SSH_KEY:-~/.ssh/id_rsa}"
CONTROL_PATH="/tmp/ssh-deploy-control-%r@%h:%p"

set -e
set -x

# Create SSH master connection
function ssh_start() {
    ssh -i "$SSH_KEY" -o ControlMaster=auto -o ControlPath="$CONTROL_PATH" -o ControlPersist=10m -Nf "$SSH_HOST"
}

# Execute commands via existing connection
function ssh_exec() {
    ssh -i "$SSH_KEY" -o ControlPath="$CONTROL_PATH" "$SSH_HOST" "$1"
}

# Close SSH connection
function ssh_stop() {
    ssh -i "$SSH_KEY" -o ControlPath="$CONTROL_PATH" -O exit "$SSH_HOST" 2>/dev/null || true
}

# Ensure connection is closed on exit
trap ssh_stop EXIT

echo "🔌 Establishing SSH connection..."
ssh_start

echo "🚀 Updating plugin on server..."

# Add directory to safe.directory
ssh_exec "git config --global --add safe.directory $PROJECT_PATH" 2>/dev/null || true

# Check if git is initialized
if ! ssh_exec "cd $PROJECT_PATH && [ -d .git ]" 2>/dev/null; then
    echo "📦 Git not initialized, performing initial setup..."
    ssh_exec "cd $PROJECT_PATH && git init"
    ssh_exec "cd $PROJECT_PATH && git remote add origin $REPOSITORY"
    ssh_exec "cd $PROJECT_PATH && git fetch"
    ssh_exec "cd $PROJECT_PATH && git checkout $BRANCH"
    echo "✅ Git initialized"
else
    echo "📥 Updating from repository..."

    # Check if remote origin is configured
    if ! ssh_exec "cd $PROJECT_PATH && git remote get-url origin" 2>/dev/null; then
        echo "📌 Configuring remote origin..."
        ssh_exec "cd $PROJECT_PATH && git remote add origin $REPOSITORY"
        ssh_exec "cd $PROJECT_PATH && git fetch"
        ssh_exec "cd $PROJECT_PATH && git checkout $BRANCH"
    else
        ssh_exec "cd $PROJECT_PATH && git pull origin $BRANCH"
    fi
fi

echo "✅ Done!"
