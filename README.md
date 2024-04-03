# Template setup

### env
```
cp -a .docker/.env.dist .docker/.env
```
```
vim .docker/.env
```

### Installation
```
cd .docker/
```
```
docker compose up --build -d
```
```
docker exec -it {PROJECT_NAME}_php /bin/sh
```
```
composer install
```
```
php bin/console doctrine:migrations:migrate
```

### Api docs
```
http://127.0.0.1:{NGINX_PORT}/api/doc
```

### Tests
phpunit.xml.dist
```
php vendor/bin/phpunit --coverage-html coverage
```

### Console
```
php bin/console
```

### Queue
```
php bin/console messenger:consume -vv
```


