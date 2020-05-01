![Dependencies](https://david-dm.org/n0nag0n/super-model.svg)
[![Build Status](https://travis-ci.org/n0nag0n/super-model.png?branch=master)](https://travis-ci.org/n0nag0n/super-model)
[![codecov](https://codecov.io/gh/n0nag0n/super-model/branch/master/graph/badge.svg)](https://codecov.io/gh/n0nag0n/super-model)
[![contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/dwyl/esta/issues)
[![HitCount](http://hits.dwyl.com/n0nag0n/super-model.svg)](http://hits.dwyl.com/n0nag0n/super-model)

# Super Model

Super model is a very simple ORM type php class to easily interact with tables in a database without writing a ton of SQL code all over the place.

To prove it, here are the lines of code...
```
$ cloc src/
       1 text file.
       1 unique file.                              
       0 files ignored.

github.com/AlDanial/cloc v 1.74  T=0.01 s (71.8 files/s, 48768.5 lines/s)
-------------------------------------------------------------------------------
Language                     files          blank        comment           code
-------------------------------------------------------------------------------
PHP                              1             86            246            347
-------------------------------------------------------------------------------
```

## Basic Usage
Getting started with Super Model is easy, simply extend the super model class and define a table name. That's about it.
```php
<?php
use n0nag0n\Super_Model;
	class User extends Super_Model {
		protected $table = 'users';
	}
```
Now what about some simple examples of how she works?

First, lets assume the following table:
```
Table: users
------------------------------------------
| id | email 				| company_id | 
| 1  | hi@example.com 		| 50 		 |
| 2  | another@example.com 	| 61 		 |
| 3  | whatever@example.com | 61  		 |
------------------------------------------
```

```php
<?php
// somefile.php

$pdo = new PDO('sqlite::memory:', '', '', [ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]);

$User = new User($pdo);

// WHERE company_id = 5
$users = $User->getAllByCompany_Id(5);

// same as above
$users = $User->getAll([ 'company_id' => 5 ]);

```
