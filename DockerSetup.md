# Docker setup

## Prepare configuration

### Add laradock

- Refer: <https://laradock.io/documentation/>

``` bash
    $ cd {path/to/projectroot}
    # Clone laradock (Only run in the first time)
    $ git submodule add https://github.com/Laradock/laradock.git
    # Create environment file
    $ cp laradock/.env.example laradock/.env
```

### Update `laradock/.env`

```php
APP_CODE_PATH_HOST=../web/
COMPOSE_PROJECT_NAME=base_prj
PHP_VERSION=8.2
```

### Update `laradock/docker-compose.yml`

```php
    workspace:
      container_name: base_web
    mysql:
      container_name: base_mysql
    phpmyadmin:
      container_name: base_phpmyadmin
      environment:
        # - PMA_ARBITRARY=1
        ...
        - PMA_HOST=base_mysql
```

### Create `web/.env`

``` bash
    $ cd {path/to/projectroot}
    # Create environment file
    $ cp web/.env.example web/.env
```

```php
APP_URL=http://localhost:80
DB_HOST=base_mysql
DB_PORT=3306
DB_DATABASE=default
DB_USERNAME=default
DB_PASSWORD=secret
```

## Working with docker environment

``` bash
    $ cd {path/to/projectroot}
    # Start all containers 
    $ docker-compose -f laradock/docker-compose.yml --env-file laradock/.env up -d nginx mysql phpmyadmin workspace redis
    # Create and set role  for folders
    $ sudo mkdir -p storage/app storage/app/public storage/framework storage/framework/cache storage/framework/cache/data storage/framework/
    $ sudo chmod -R 777 storage/
    # Install dependencies
    $ docker exec -it base_web composer install
    # Update dependencies
    $ docker exec -it base_web composer update
    # Generate key
    $ docker exec -it base_web php artisan key:generate
    # Create folders
    # Installing Laravel Mix
    $ docker exec -it base_web yarn
    # Run all Mix tasks...
    $ docker exec -it base_web yarn build
    # Run dev...
    $ docker exec -it base_web yarn dev
    # Auto build and reload
    $ docker exec -it base_web npm run watch-poll
    # Comment webpack config
    # Migrate database
    $ docker exec -it base_web php artisan migrate
    # Init data
    $ docker exec -it base_web php artisan db:seed
    # Stop all containers
    $ docker-compose -f laradock/docker-compose.yml --env-file laradock/.env stop
    # Remove all containers
    $ docker-compose -f laradock/docker-compose.yml --env-file laradock/.env down
    # Linux or Mac environment
    $ make start            # Start all containers 
    $ make stop             # Stop all containers
    $ make remove           # Remove all containers
    $ make goweb            # Access web container
    $ make godb             # Access mysql container
    $ make migrate          # Migrate database
    $ make seed             # Init data
    $ make yarn             # Installing Laravel Mix
    $ make y-dev            # Run all Mix tasks...
    $ make cps              # Install dependencies (composer)
    $ make cps-update       # Update dependencies (composer)
    $ make unit-test        # Run Unit test
    $ make api-test         # Run API test
    
```

- Redis

```.env
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=secret_redis
REDIS_PORT=6379
```

## Access

### Website
   >
   > <http://localhost/>

### PhpMyAdmin
   >
   > <http://localhost:8081/>

- Server: `base_mysql`
- Username: `default`
- Password: `secret`

## Troubleshooting

### MySQL

- mysql_native_password not found
  - Solution: edit file `laradock/mysql/my.cnf`
    - Add `[mysqld-8.4]
            mysql_native_password=on`
