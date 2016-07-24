DB Populator
============

1. What's the meaning of this?
------------------------------

When one finishes the database modeling and it's time to integrate with the application, many times the problem is the DATA!
You don't have data on the database in order to make tests.
This class was developed to fill this gap, providing a simple way to populate your database with random data that makes sense.

2. How to use it?
-----------------

Just include the class and create a new instance.

```php
require('dbpopulator.class.php');
$db = new DBPopulator();
```

After that, provite the database access information.

```php
$db->setDb('localhost', 'user', 'pass');
```

Use the method 'populate' with the following parameters:

> * tables [mandatory]
> Use string or array to define this value.
> Either way, the pattern to specify the table is 'database.table'.
> You can also use * to populate all tables within a database (database.*)
>  
> * inserts
> Use this parameter to specify the number of rows to be inserted at each table.
>
> * asScript
> Define here a filename to create the SQL statements instead of running the code directly in the database.

Example (generating a script to populate two tables with 20 records each):

```php
$db->populate(array('mydb.table1', 'mydb.table2'), 20, 'my_script.sql');
```

There is also an example in [example.php](https://github.com/rafajaques/DB-Populator/blob/master/example.php).

3. Customisation
----------------

At the very beggining of the class you'll find the variables that you can modify
in order to suit your needs.

Here they are:

> * Field name pattern ($dummyName, $dummyAge, $dummyLink, ...)
> You can use these variables to set the field names to generate more consistent data into your tables.
> The script will try to match these field names and do the association with related data.
>
> * Password encrypt ($dummyPasswordEncrypt)
> Change as you need if you want to encrypt your generated passwords.
> Values can be: md5, sha1, base64, false
>
> * Number range ($dummyIntRange, $dummyRealRange)
> Define the minimum and maximum values to generate numbers.
>
> * Dummy values catalog 
> Use this variables to set the values for generation dummy data.
