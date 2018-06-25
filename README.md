Laravel Scout MySQL Driver
==========================

Search Eloquent Models using MySQL `FULLTEXT` Indexes or `WHERE LIKE '%:search%`' statements.

1. [Installation](#installation)
2. [Usage](#usage)
3. [Modes](#modes)
4. [Console Command](#console-command)
5. [Configuration](#configuration)


Installation <div id="installation"></div>
------------

**Note: Any Models you plan to search using this driver must use a MySQL MyISAM or InnoDB table.**

If you haven't already you should [install Laravel Scout](https://laravel.com/docs/5.6/scout#installation) to
your project and apply the `Laravel\Scout\Searchable` trait to any Eloquent models you would like to make searchable.

Install this package via **Composer**

`composer require yab/laravel-scout-mysql-driver`


Next add the ServiceProvider to the Package Service Providers in `config/app.php`

```php
        /*
         * Package Service Providers...
         */
        Yab\MySQLScout\Providers\MySQLScoutServiceProvider::class,
```

Append the default configuration to `config/scout.php`

```php

    'mysql' => [
        'mode' => 'NATURAL_LANGUAGE',
        'model_directories' => [app_path()],
        'min_search_length' => 0,
        'min_fulltext_search_length' => 4,
        'min_fulltext_search_fallback' => 'LIKE',
        'query_expansion' => false
    ]

```

Set `SCOUT_DRIVER=mysql` in your `.env` file

Please note this Laravel Scout driver does not need to update any indexes when a Model is changed as this is handled
natively by MySQL. Therefore you can safely disable queuing in `config/scout.php`.

```php
    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => false,
```

In addition there is no need to use the `php artisan scout:import` command. However, if you plan to use this driver in
either `NATURAL_LANGUAGE` or `BOOLEAN` mode you should first run the provided [console command](#console-command) to
create the needed `FULLTEXT` indexes.

Usage <div id="usage"></div>
-----

Simply call the `search()` method on your `Searchable` models:

`$beers = App\Drink::search('beer')->get();`

Or With pagination:

`$beers = App\Drink::search('beer')->paginate(15);`

Simple constraints can be applied using the `where()` builder method
(each additional `WHERE` will be applied using `AND`).

`$beers = App\Drink::search('beer')->where('in_stock', 1)->get();`

The following operators can be applied to the `WHERE` statements: `<> != = <= < >= >`
(`=` will be used if no operator is specified)

`$beers = App\Drink::search('beer')->where('abv >', 10)->get();`

For more usage information see the [Laravel Scout Documentation](https://laravel.com/docs/5.3/scout).

Modes <div id="modes"></div>
-----

This driver can perform different types of search queries depending on the mode set in the `scout.mysql.mode`
Laravel configuration value. Currently 4 different modes are supported `NATURAL_LANGUAGE`,`BOOLEAN`,`LIKE` and `LIKE_EXPANDED`.


### NATURAL_LANGUAGE and BOOLEAN Modes

In `NATURAL_LANGUAGE` and `BOOLEAN` mode the driver will run MySQL `WHERE MATCH() AGAINST()` queries in the
respective modes.

Both modes search queries will include all of Model's `FULLTEXT` compatible fields (`CHAR`,`VARCHAR`,`TEXT`)
returned from the Model's `toSearchableArray()` method. It is required to have a `FULLTEXT` index for these fields.
You can create  this index using the provided [console command](#console-command).

For example running a search on a `POST` model with the following database structure:

| column name | type             |
|-------------|------------------|
| id          | int(10) UN AI PK |
| content     | VARCHAR(255)     |
| meta        | TEXT             |


would produce the following query in `NATURAL_LANGUAGE` mode:


```sql
select * from `posts` where MATCH(content,meta) AGAINST(? IN NATURAL LANGUAGE MODE)
```

and the following query in `BOOLEAN` mode:

```sql
select * from `posts` where MATCH(content,meta) AGAINST(? IN BOOLEAN MODE)
```

Operators for `BOOLEAN` mode should be passed as part of the search string.


For more information see the
[MySQL's Full-Text Search Functions documentation](http://dev.mysql.com/doc/refman/5.7/en/fulltext-search.html).

### LIKE and LIKE_EXPANDED Modes

`LIKE` and `LIKE_EXPANDED` modes will run `WHERE LIKE %?%` queries that will include all of the Model's fields
returned from `toSearchableArray()`. `LIKE_EXPANDED` mode will query each field using each individual word in the search string.

For example running a search on a `Customer` model with the following database structure:

| column name | type             |
|-------------|------------------|
| id          | int(10) UN AI PK |
| first_name  | VARCHAR(255)     |
| last_name   | VARCHAR(255)     |

would produce the following query in `LIKE` mode given the search string "John":

```sql
SELECT * FROM `customers` WHERE (`id` LIKE '%John%' OR `first_name` LIKE '%John%' OR `last_name` LIKE '%JOHN%')
```

and the following query in `LIKE_EXPANDED` mode given the search string "John Smith":

```sql
SELECT * FROM `customers` WHERE (`id` LIKE '%John%' OR `id` LIKE '%Smith%' OR `first_name` LIKE '%John%' OR `first_name` LIKE '%Smith%' OR `last_name` LIKE '%John%' OR `last_name` LIKE '%Smith%')
```

Console Command <div id="console-command"></div>
---------------

The command `php artisan scout:mysql-index {model?}` is included to manage the `FULLTEXT` indexes needed for
`NATURAL_LANGUAGE`and `BOOLEAN` modes.

If the  model parameter is omitted the command will run with all Model's with the `Laravel\Scout\Searchable` trait
and a MySQL connection within the  directories defined in the `scout.mysql.model_directories` Laravel configuration value.

### Creating Indexes

Pass the command a Model to create a `FULLTEXT` index for all of the Model's `FULLTEXT` compatible fields
(`CHAR`,`VARCHAR`,`TEXT`) returned from the Model's `toSearchableArray()` method.  The index name will be the result of
the Model's `searchableAs()` method.

If an index already exists for the Model and the Model contains new searchable fields not in the existing index the
index will be dropped and recreated.

`php artisan scout:mysql-index App\\Post`

### Dropping index

Pass the `-D` or `--drop` options to drop an existing index for a Model.

`php artisan scout:mysql-index App\\Post --drop`

Configuration <div id="configuration"></div>
-------------

Behavior can be changed by modifying the `scout.mysql` Laravel configuration values.

* `scout.mysql.mode` - The [mode](#mode) used to determine how the driver runs search queries. Acceptable values are
`NATURAL_LANGUAGE`,`BOOLEAN`,`LIKE` and `LIKE_EXPANDED`.

* `scout.mysql.model_directories` - If no model parameter is provided to the included `php artisan scout:mysql-index`
command the directories defined here will be searched for Model's with the `Laravel\Scout\Searchable` trait
and a MySQL connection.

* `scout.mysql.min_search_length` - If the length of a search string is smaller then this value no search queries will
run and an empty Collection will be returned.

* `scout.mysql.min_fulltext_search_length` - If using `NATURAL_LANGUAGE` or `BOOLEAN` modes and a search string's length
is less than this value the driver will revert to a fallback mode. By default MySQL requires a search string length of at
least 4 to to run `FULLTEXT` queries. For information on changing this see the
[MySQL's Fine-Tuning MySQL Full-Text Search documentation](http://dev.mysql.com/doc/refman/5.7/en/fulltext-fine-tuning.html).

* `scout.mysql.min_fulltext_search_fallback` - The mode that will be used as a fallback when the search string's length
is less than `scout.mysql.min_fulltext_search_length` in `NATURAL_LANGUAGE` or `BOOLEAN` modes. Acceptable values are
`LIKE` and `LIKE_EXPANDED`.

* `scout.mysql.query_expansion` - If set to true MySQL query expansion will be used in search queries. Only applies if
using `NATURAL_LANGUAGE` mode. For more information see
[MySQL's Full-Text Searches with Query Expansion documentation](http://dev.mysql.com/doc/refman/5.7/en/fulltext-query-expansion.html).
