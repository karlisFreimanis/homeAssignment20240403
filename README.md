# Home assignment

## Intro
todo

## Installation
```
cd .docker/
```
```
cp -a .env.dist .env
```
```
vim .docker/.env
```
```
docker compose up --build -d
```
```
docker exec -it mintos_php /bin/sh
```
```
composer install
```
```
php bin/console doctrine:migrations:migrate
```
### Api docs
```
http://127.0.0.1:3003/api/doc
```

### Tests
```
php vendor/bin/phpunit --coverage-html coverage
```


