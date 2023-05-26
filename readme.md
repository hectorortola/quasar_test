# Quasar Test
Quasar_test is a Symfony 6.2 appplication with a mysql database with the purpose of manage user with notes and categories.

## Installation

Use the package manager [composer](https://getcomposer.org/) to install all packages.

```bash
cd /quasar-test
composer install

(configure database in .env file)

php bin/console doctrine:database:create     
php bin/console doctrine:migrations:migrate

symfony server:start
```

## Postman Collection
There is a Postman Collection available in the  main directory, you can import it to make the tests easier.