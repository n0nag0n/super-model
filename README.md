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

This is written with performance in mind. So while it will not satisfy every single requirement in every project that's ever been built, it will work in the majority of cases, and do a kick butt job at it too!

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
-------------------------------------------------
| id	| email					| company_id	| 
| 1		| hi@example.com		| 50			|
| 2		| another@example.com	| 61			|
| 3		| whatever@example.com	| 61			|
-------------------------------------------------
```

```php
<?php
// somefile.php

$pdo = new PDO('sqlite::memory:', '', '', [ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]);

$User = new User($pdo);

// WHERE company_id = 50
$users = $User->getAllBycompany_id(50);

// same as above
$users = $User->getAll([ 'company_id' => 50 ]);

```
Easy peasy, lemon squeezy right?

## Docs
### `getBy*(mixed $value): array [result]`
This is a method that returns one row back from the value specified. The `*` part of the method refers to a field in the database. The field name is case-sensitive to whatever your field name is on your database table.
```php

// get by the id field on the users table
$User->getByid(3);

/* 
	[ 
		'id' => 3,
		'email' => 'whatever@example.com',
		'company_id' => 61
	]
*/
$User->getBycompany_id(61);
/* 
	// it only will pull the first row, not all rows
	[ 
		'id' => 2,
		'email' => 'another@example.com',
		'company_id' => 61
	]
*/
```

### `getAllBy*(mixed $value): array [ [result], [result] ]`
This is a shortcut filter to return all rows by a given value. The `*` part of the method refers to a field in the database. The field name is case-sensitive to whatever your field name is on your database table.
```php

// this is pointless, but will still work
$User->getAllByid(3);

/* 
	[
		[ 
			'id' => 3,
			'email' => 'whatever@example.com',
			'company_id' => 61
		]
	]
*/
$User->getAllBycompany_id(61);
/* 
	[
		[ 
			'id' => 2,
			'email' => 'another@example.com',
			'company_id' => 61
		],
		[ 
			'id' => 3,
			'email' => 'whatever@example.com',
			'company_id' => 61
		]
	]
*/
```

### `getAll(array $filters, bool $return_one_row = false): array [ [result], [result] ] or [result]`


### `create(array $data): int [insert id]`
This will create a single row on the table, but if you supply a multi-dimensional array, it will insert multiple rows. A primary key of `id` is assumed.
```php
$User->create([ 'email' => 'onemore@example.com', 'company_id' => 55 ]);
// returns 4

$User->create([ [ 'email' => 'ok@example.com', 'company_id' => 55 ], [ 'email' => 'thanks@example.com', 'company_id' => 56 ] ]);
// returns 6, only the last id will be returned
```

### `update(array $data, string $update_field = 'id'): int (number of rows updated)`
This will create a single row on the table, but if you supply a multi-dimensional array, it will insert multiple rows. A primary key of `id` is assumed.
```php
$User->update([ 'id' => 1, 'email' => 'whoneedsemail@example.com' ]);
// returns 1 and will only update the email field

$User->update([ 'email' => 'whoneedsemail@example.com', 'company_id' => 61 ], 'email');
// returns 1

$User->update([ 'company_id' => 61, 'email' => 'donotreply@example.com' ], 'company_id');
// returns 3, not really logical, but it would update all the emails
```

## FAQ (Advanced Usage)

*What if you want an automated way to alter your result if a specific flag is fired?*
Easy peasy. There is a method called `processResult()` that will run through every result you pull back. You inject special filters for this method in the `$filters['processResults']` key.
```php
<?php
	use n0nag0n\Super_Model;
	class User extends Super_Model {
		protected $table = 'users';

		public processResult(array $process_filters, array $result): array {

			// add some trigger here and do whatever checks you need
			if(isset($process_filters['set_full_name']) && $process_filters['set_full_name'] === true && !empty($result['first_name']) && !empty($result['last_name'])) {
				$result['full_name'] = $result['first_name'].' '.$result['last_name'];
			}

			return $result;
		}
	}

	// later on in some other file.
	$User = new User($pdo);

	// setting the processResults filter here is the key to connecting the getAll statement with your processResult method
	$users = $User->getAll([ 'company_id' => 51, 'processResults' => [ 'set_full_name' => true ] ]);

	echo $users[0]['full_name']; // Bob Smith
```


*What if you need to do a crazy complex SQL query that doesn't fall in the realm of this class or the `getAll()` filters?*

Remember the point of this class is **NOT** to satisfy every requirement from every project that ever has or will exist, but it will get you 90% the way there. In light of that, there is a simple way to execute the above question. Just use RAW SQL for your one off.
```php
<?php
	use n0nag0n\Super_Model;
	class User extends Super_Model {
		protected $table = 'users';

		public function processCrazyKukooQuery(/* add whatever required fields you need */): array {
			$db = $this->getDbConnection();

			// shamelessly ripped from StackOverflow
			$statement = $db->prepare("SELECT 
				DISTINCT
				t.id,
				t.tag, 
				c.title AS Category
				FROM
				tags2Articles t2a 
				INNER JOIN tags t ON t.id = t2a.idTag
				INNER JOIN categories c ON t.tagCategory = c.id
				INNER JOIN (
					SELECT
					a.id 
					FROM 
					articles AS a
					JOIN tags2articles AS ta  ON a.id=ta.idArticle
					JOIN tags AS tsub ON ta.idTag=tsub.id
					WHERE 
					tsub.id IN (12,13,16) 
					GROUP BY a.id
					HAVING COUNT(DISTINCT tsub.id)=3 
				) asub ON t2a.idArticle = asub.id");
			$statement->execute();

			return $statement->fetchAll();
		}
	}

	
```

## Testing
Simply run `composer test` to run `phpunit` and `phpstan`. Currently at 100% coverage and that's where I'd like to keep it. 

*A note about 100% coverage:* While the code may have 100% coverage, **actual** coverage is different. The goal is to test many different scenarios against the code to think through the code and anticipate unexpected results. I code to "real" coverage, not "did the code run" coverage. 