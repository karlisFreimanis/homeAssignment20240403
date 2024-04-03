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

### Development notes
```
php bin/console make:entity User
```
```
php bin/console make:entity Currency
```
```
php bin/console make:entity Account
```
ManyToOne->User  
ManyToOne->Currency
```
php bin/console make:migration
```
```
php bin/console doctrine:migrations:migrate
```
```
```
```
```
```
```
```
```
```

