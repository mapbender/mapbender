# Doctrine Migrations

## How to use 
First of all you have to install [DoctrineMigrationsBundle](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html).

### Configuration
You can provide some custom configuration for your migrations in the app/config/config.yml file.

* dir_name: a folder in you project where migrations will be stored. 
* namespace: a namespace for migration calsses
* table_name: a table where will be stored information about migrations executions
* organize_migrations: the way how your migrations will be stored in the folder. Possible values are: "BY_YEAR", "BY_YEAR_AND_MONTH", false

### Create a new migration
* Generate a migration by comparing your current database to your mapping information:
```
app/console doctrine:migrations:diff
```
* Generate a blank migration class
```
app/console doctrine:migrations:generate
```
In the current folder you can find two migrations example: with SQL code and with EntityManager.

### Execute migrations
* Execute migrations to a specified version or the latest available version
```
app/console doctrine:migrations:migrate
```
* Execute a single migration
```
app/console doctrine:migrations:execute [migration]
```
* Revert migration (only in case if the down() function is developed)
```
app/console doctrine:migrations:execute [migration] --down
```
* Execute the same migration ones again
```
app/console doctrine:migrations:execute [migration] --up
```

## Documentation
* Symfony documentation: [DoctrineMigrationsBundle](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
* Doctrine documentation: [Doctrine Migrations](http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/reference/introduction.html)