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
```
php bin/console dummyData 1000
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
Make endpoint for  
Given a client id return list of accounts (each client might have 0 or more accounts
with different currencies)  

Ask chatgpt to generate documentation
```
php bin/console make:entity Transaction
```
from/to ManyToOne->Account
```
php bin/console make:migration
```
Chose ecb for rates input. Most likely better limits
[ECB XML](http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml)  
Generate some fake data to work with
```
php bin/console make:command dummyData
```
```
php bin/console dummyData 1000
```
Make transfer funds api that saves transaction request  
Make queue that process new transactions  
Fail transactions that didn't process
Add new container that process queue in background
```
```
```
```
```
```
```
```

