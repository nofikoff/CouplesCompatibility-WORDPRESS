# Инструкция по развертыванию

## Использование

После того как вы закоммитили изменения в git, просто запустите:
```bash
./deploy.sh
```

При первом запуске скрипт автоматически инициализирует git на сервере.
При последующих запусках будет просто выполнять `git pull`.

## Структура

**В репозитории:**
```
plugins/numerology-compatibility/
  ├── admin/
  ├── api/
  ├── includes/
  └── ...
```

**На сервере:**
```
/home/couplescompatibility/public_html/wp-content/
  └── plugins/numerology-compatibility/
      ├── admin/
      ├── api/
      ├── includes/
      └── ...
```

Git репозиторий инициализирован на уровне `wp-content/`, поэтому структура папок совпадает.
