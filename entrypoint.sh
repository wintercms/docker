#!/bin/bash
set -e

env_var_or_file() {
    local VAR="$1"
    local FILEVAR="${VAR}_FILE"
    local DEF="${2:-}"

    local VAL="$DEF"

    if [ -n "${!VAR}" ]; then
        VAL="${!VAR}"
    elif [ -n "${!FILEVAR}" ]; then
        VAL="$(< "${!FILEVAR}")"
    fi
    echo $VAL
}

# If storage directory is mounted but empty, add default directory structure
if [ -d /winter/storage ] && [ -z "$(ls -A /winter/storage)" ]; then
    echo "Storage directory is empty, creating default structure..."
    GITIGNORE=$'*\n!.gitignore'
    mkdir -p /winter/storage/app/media
    echo "$GITIGNORE" > /winter/storage/app/media/.gitignore
    mkdir -p /winter/storage/app/resized
    echo "$GITIGNORE" > /winter/storage/app/resized/.gitignore
    mkdir -p /winter/storage/app/uploads/public
    echo "$GITIGNORE" > /winter/storage/app/uploads/public/.gitignore
    echo $'*\n!.gitignore\n!public' > /winter/storage/app/uploads/.gitignore
    echo $'*\n!.gitignore\n!media\n!resized\n!uploads' > /winter/storage/app/.gitignore
    mkdir -p /winter/storage/cms/cache
    echo "$GITIGNORE" > /winter/storage/cms/cache/.gitignore
    mkdir -p /winter/storage/cms/combiner
    echo "$GITIGNORE" > /winter/storage/cms/combiner/.gitignore
    mkdir -p /winter/storage/cms/twig
    echo "$GITIGNORE" > /winter/storage/cms/twig/.gitignore
    echo "disabled.json" > /winter/storage/cms/.gitignore
    mkdir -p /winter/storage/framework/cache
    echo "$GITIGNORE" > /winter/storage/framework/cache/.gitignore
    mkdir -p /winter/storage/framework/sessions
    echo "$GITIGNORE" > /winter/storage/framework/sessions/.gitignore
    mkdir -p /winter/storage/framework/views
    echo "$GITIGNORE" > /winter/storage/framework/views/.gitignore
    echo $'config.php\nroutes.php\ncompiled.php\nservices.php\nclasses.php\npackages.php\nevents.scanned.php\nroutes.scanned.php' > /winter/storage/framework/.gitignore
    mkdir -p /winter/storage/logs
    echo "$GITIGNORE" > /winter/storage/logs/.gitignore
    mkdir -p /winter/storage/temp/public
    echo "$GITIGNORE" > /winter/storage/temp/public/.gitignore
    echo $'*\n!.gitignore\n!public' > /winter/storage/temp/.gitignore
    echo $'*.sqlite\n/tests\n/dusk\n/debugbar' > /winter/storage/.gitignore
fi

# Run Composer update if COMPOSER_UPDATE is set to true or 1
DO_COMPOSER_UPDATE=$(env_var_or_file "COMPOSER_UPDATE")
DO_COMPOSER_DEV=$(env_var_or_file "COMPOSER_DEV")
if [ "$DO_COMPOSER_UPDATE" = "true" ] || [ "$DO_COMPOSER_UPDATE" = "1" ]; then
    echo "Running composer update..."
    if [ "$DO_COMPOSER_DEV" = "true" ] || [ "$DO_COMPOSER_DEV" = "1" ]; then
        composer update --no-progress --no-interaction --no-suggest --no-audit
    else
        composer update --no-progress --no-interaction --no-suggest --no-audit --no-dev
    fi
fi

# Create database if using the default SQLite database
if [ "$DB_CONNECTION" = "sqlite" ] && [ "$DB_DATABASE" = "/winter/storage/database.sqlite" ] && [ ! -f /winter/storage/database.sqlite ]; then
    echo "Setting up SQLite database..."
    touch "$DB_DATABASE"
    export RUN_MIGRATIONS="true"
fi

# Configuration changes and allow environment variables to be specified by "_FILE" secrets
if [ -n "$(env_var_or_file "APP_KEY")" ]; then
    export APP_KEY=$(env_var_or_file "APP_KEY")
fi
if [ -n "$(env_var_or_file "DB_DATABASE")" ]; then
    export DB_DATABASE=$(env_var_or_file "DB_DATABASE")
fi
if [ -n "$(env_var_or_file "DB_USERNAME")" ]; then
    export DB_USERNAME=$(env_var_or_file "DB_USERNAME")
fi
if [ -n "$(env_var_or_file "DB_PASSWORD")" ]; then
    export DB_PASSWORD=$(env_var_or_file "DB_PASSWORD")
fi
if [ -n "$(env_var_or_file "DB_HOST")" ]; then
    export DB_HOST=$(env_var_or_file "DB_HOST")
fi
if [ -n "$(env_var_or_file "DB_PORT")" ]; then
    export DB_PORT=$(env_var_or_file "DB_PORT")
fi
if [ -n "$(env_var_or_file "DATABASE_URL")" ]; then
    export DATABASE_URL=$(env_var_or_file "DATABASE_URL")
fi
if [ -n "$(env_var_or_file "PUSHER_APP_ID")" ]; then
    export PUSHER_APP_ID=$(env_var_or_file "PUSHER_APP_ID")
fi
if [ -n "$(env_var_or_file "PUSHER_APP_KEY")" ]; then
    export PUSHER_APP_KEY=$(env_var_or_file "PUSHER_APP_KEY")
fi
if [ -n "$(env_var_or_file "PUSHER_APP_CLUSTER")" ]; then
    export PUSHER_APP_CLUSTER=$(env_var_or_file "PUSHER_APP_CLUSTER")
fi
if [ -n "$(env_var_or_file "PUSHER_APP_SECRET")" ]; then
    export PUSHER_APP_SECRET=$(env_var_or_file "PUSHER_APP_SECRET")
fi
if [ -n "$(env_var_or_file "ABLY_KEY")" ]; then
    export ABLY_KEY=$(env_var_or_file "ABLY_KEY")
fi
if [ -n "$(env_var_or_file "MEMCACHED_PERSISTENT_ID")" ]; then
    export MEMCACHED_PERSISTENT_ID=$(env_var_or_file "MEMCACHED_PERSISTENT_ID")
fi
if [ -n "$(env_var_or_file "MEMCACHED_USERNAME")" ]; then
    export MEMCACHED_USERNAME=$(env_var_or_file "MEMCACHED_USERNAME")
fi
if [ -n "$(env_var_or_file "MEMCACHED_PASSWORD")" ]; then
    export MEMCACHED_PASSWORD=$(env_var_or_file "MEMCACHED_PASSWORD")
fi
if [ -n "$(env_var_or_file "MEMCACHED_HOST")" ]; then
    export MEMCACHED_HOST=$(env_var_or_file "MEMCACHED_HOST")
fi
if [ -n "$(env_var_or_file "MEMCACHED_PASSWORD")" ]; then
    export MEMCACHED_PORT=$(env_var_or_file "MEMCACHED_PORT")
fi
if [ -n "$(env_var_or_file "REDIS_HOST")" ]; then
    export REDIS_HOST=$(env_var_or_file "REDIS_HOST")
fi
if [ -n "$(env_var_or_file "REDIS_PASSWORD")" ]; then
    export REDIS_PASSWORD=$(env_var_or_file "REDIS_PASSWORD")
fi
if [ -n "$(env_var_or_file "REDIS_PORT")" ]; then
    export REDIS_PORT=$(env_var_or_file "REDIS_PORT")
fi
if [ -n "$(env_var_or_file "REDIS_DB")" ]; then
    export REDIS_DB=$(env_var_or_file "REDIS_DB")
fi
if [ -n "$(env_var_or_file "REDIS_URL")" ]; then
    export REDIS_URL=$(env_var_or_file "REDIS_URL")
fi
if [ -n "$(env_var_or_file "AWS_BUCKET")" ]; then
    export AWS_BUCKET=$(env_var_or_file "AWS_BUCKET")
fi
if [ -n "$(env_var_or_file "AWS_ENDPOINT")" ]; then
    export AWS_ENDPOINT=$(env_var_or_file "AWS_ENDPOINT")
fi
if [ -n "$(env_var_or_file "AWS_ACCESS_KEY_ID")" ]; then
    export AWS_ACCESS_KEY_ID=$(env_var_or_file "AWS_ACCESS_KEY_ID")
fi
if [ -n "$(env_var_or_file "AWS_SECRET_ACCESS_KEY")" ]; then
    export AWS_SECRET_ACCESS_KEY=$(env_var_or_file "AWS_SECRET_ACCESS_KEY")
fi
if [ -n "$(env_var_or_file "AWS_SECRET_ACCESS_KEY")" ]; then
    export AWS_SECRET_ACCESS_KEY=$(env_var_or_file "AWS_SECRET_ACCESS_KEY")
fi
if [ -n "$(env_var_or_file "DYNAMODB_ENDPOINT")" ]; then
    export DYNAMODB_ENDPOINT=$(env_var_or_file "DYNAMODB_ENDPOINT")
fi
if [ -n "$(env_var_or_file "SQS_PREFIX")" ]; then
    export SQS_PREFIX=$(env_var_or_file "SQS_PREFIX")
fi
if [ -n "$(env_var_or_file "MAIL_HOST")" ]; then
    export MAIL_HOST=$(env_var_or_file "MAIL_HOST")
fi
if [ -n "$(env_var_or_file "MAIL_USERNAME")" ]; then
    export MAIL_USERNAME=$(env_var_or_file "MAIL_USERNAME")
fi
if [ -n "$(env_var_or_file "MAIL_PASSWORD")" ]; then
    export MAIL_PASSWORD=$(env_var_or_file "MAIL_PASSWORD")
fi
if [ -n "$(env_var_or_file "MAIL_PORT")" ]; then
    export MAIL_PORT=$(env_var_or_file "MAIL_PORT")
fi
if [ -n "$(env_var_or_file "MAILGUN_DOMAIN")" ]; then
    export MAILGUN_DOMAIN=$(env_var_or_file "MAILGUN_DOMAIN")
fi
if [ -n "$(env_var_or_file "MAILGUN_ENDPOINT")" ]; then
    export MAILGUN_ENDPOINT=$(env_var_or_file "MAILGUN_ENDPOINT")
fi
if [ -n "$(env_var_or_file "MAILGUN_SECRET")" ]; then
    export MAILGUN_SECRET=$(env_var_or_file "MAILGUN_SECRET")
fi
if [ -n "$(env_var_or_file "POSTMARK_TOKEN")" ]; then
    export POSTMARK_TOKEN=$(env_var_or_file "POSTMARK_TOKEN")
fi
if [ -n "$(env_var_or_file "ACTIVE_THEME")" ]; then
    SRC="'activeTheme' => 'demo',"
    DEST="'activeTheme' => '${ACTIVE_THEME}',"
    sed -i "s/${SRC}/${DEST}/g" /winter/config/cms.php || true
fi
if [ -n "$(env_var_or_file "BACKEND_URI")" ]; then
    SRC="'backendUri' => 'backend',"
    DEST="'backendUri' => '${BACKEND_URI}',"
    sed -i "s/${SRC}/${DEST}/g" /winter/config/cms.php || true
fi

# Create application key if not already set
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate
fi

# Run migrations if RUN_MIGRATIONS is set to true or 1
DO_RUN_MIGRATIONS=$(env_var_or_file "RUN_MIGRATIONS")
if [ "$DO_RUN_MIGRATIONS" = "true" ] || [ "$DO_RUN_MIGRATIONS" = "1" ]; then
    echo "Running database migrations..."
    php artisan migrate
fi

# Set up admin password (ignoring errors if the user doesn't exist)
DO_ADMIN_PASSWORD=$(env_var_or_file "ADMIN_PASSWORD")
if [ -n "$DO_ADMIN_PASSWORD" ]; then
    echo "Setting Winter admin password..."
    php artisan winter:passwd admin $DO_ADMIN_PASSWORD || true
fi

# Run Artisan command if requested
if [ "$1" = "artisan" ]; then
    if [ -z "$2" ]; then
        echo "No Artisan command provided."
        exit 1
    fi

    # Prevent using 'serve' command unless --force is provided
    if [ "$2" = "serve" ] && [ "$3" != "--force" ]; then
        echo "The 'serve' command should not be used in this environment as FrankenPHP is installed and configured. Add --force to override."
        exit 1
    elif [ "$2" = "serve" ] && [ "$3" = "--force" ]; then
        shift
        shift
        shift
        set -- php artisan serve "$@"
    else
        shift
        set -- php artisan "$@"
    fi
fi

# Run "winter:mirror" on startup to pickup any plugins and themes mounted
php artisan winter:mirror public --relative

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- frankenphp run "$@"
fi

exec "$@"
