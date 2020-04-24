<?php

use PHPUnit\Framework\TestCase;
use n0nag0n\Super_Model;

class Super_ModelTest extends TestCase {

	public function setUp(): void {
		$this->pdo = new PDO('sqlite:test.db', 'test', 'test');
		$this->obj = new Example_Model($this->pdo);
	}

	public function tearDown(): void {
		$this->dropDbTable();
	}

	public function dropDbTable() {
		$this->pdo->exec("DROP TABLE IF EXISTS 'example_model'");
	}

	public function populateDbTable() {
		$this->pdo->exec("CREATE TABLE IF NOT EXISTS 'example_model' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'int_field' INTEGER NOT NULL, 'string_field' TEXT, 'date_field' TEXT)");
	}

	public function testConstruct() {
		try {
			new Example_Model;
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertStringContainsString('$db needs to be instance of PDO', $e->getMessage());
		}

		try {
			new Example_Model('hi');
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertStringContainsString('$db needs to be instance of PDO', $e->getMessage());
		}

		try {
			new Example_Model(new stdClass);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertStringContainsString('$db needs to be instance of PDO', $e->getMessage());
		}

		$obj = new Example_Model($this->pdo);

		$this->assertInstanceOf('PDO', $obj->getDbConnection());
	}

	public function testGetCreateSql() {
		$result = PHPUnitUtil::callMethod($this->obj, 'getCreateSql', [ 'test_table', 'some, fields, right, here', '?, ?, ?, ?' ]);
		$this->assertSame('INSERT INTO `test_table` (some, fields, right, here) VALUES (?, ?, ?, ?)', $result);
	}

	public function testGetCreateFields() {
		$result = PHPUnitUtil::callMethod($this->obj, 'getCreateFields', [ [ 'key1' => 'hi there', 'key2' => 'thanks' ] ]);
		$this->assertSame('`key1`, `key2`', $result);
		$result = PHPUnitUtil::callMethod($this->obj, 'getCreateFields', [ [ 'key1' => 'hi there' ] ]);
		$this->assertSame('`key1`', $result);
	}

	public function testGetCreatePlaceholders() {
		$result = PHPUnitUtil::callMethod($this->obj, 'getCreatePlaceholders', [ [ 'key1' => 'hi there', 'key2' => 'thanks' ] ]);
		$this->assertSame([ '?, ?' ], $result);
		$result = PHPUnitUtil::callMethod($this->obj, 'getCreatePlaceholders', [ [ 'key1' => 'hi there' ] ]);
		$this->assertSame([ '?' ], $result);
	}

	public function testProcessCreateData() {
		$result = PHPUnitUtil::callMethod($this->obj, 'processCreateData', [ [ 'key1' => 'hi there', 'key2' => 'thanks' ] ]);
		$this->assertCount(2, $result);
		$this->assertSame('INSERT INTO `example_model` (`key1`, `key2`) VALUES (?, ?)', $result['sql']);
		$this->assertSame([ 'hi there', 'thanks' ], $result['params']);

		$result = PHPUnitUtil::callMethod($this->obj, 'processCreateData', [ [ 'key1' => 'hi there' ] ]);
		$this->assertSame('INSERT INTO `example_model` (`key1`) VALUES (?)', $result['sql']);
		$this->assertSame([ 'hi there' ], $result['params']);

		$result = PHPUnitUtil::callMethod($this->obj, 'processCreateData', [ [ [ 'key1' => 'hi there', 'key2' => 'thanks' ], [ 'key1' => 'dun', 'key2' => 'do' ] ] ]);
		$this->assertCount(2, $result);
		$this->assertSame('INSERT INTO `example_model` (`key1`, `key2`) VALUES (?, ?), (?, ?)', $result['sql']);
		$this->assertSame([ 'hi there', 'thanks', 'dun', 'do' ], $result['params']);
	}

	public function testProcessUpdateData() {

		try {
			PHPUnitUtil::callMethod($this->obj, 'processUpdateData', [ [ 'key1' => 'hi there', 'key2' => 'thanks' ], '' ]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame(' update field missing', $e->getMessage());
		}

		try {
			PHPUnitUtil::callMethod($this->obj, 'processUpdateData', [ [ 'key1' => 'hi there', 'key2' => 'thanks' ], 'id' ]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('id update field missing', $e->getMessage());
		}

		try {
			PHPUnitUtil::callMethod($this->obj, 'processUpdateData', [ [ 'id' => 1 ], 'id' ]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('no data to update', $e->getMessage());
		}

		$result = PHPUnitUtil::callMethod($this->obj, 'processUpdateData', [ [ 'id' => 1, 'key1' => 'hi there', 'key2' => 'thanks' ], 'id' ]);
		$this->assertCount(2, $result);
		$this->assertSame('UPDATE `example_model` SET `key1` = ?, `key2` = ? WHERE id = ?', $result['sql']);
		$this->assertSame([ 'hi there', 'thanks', 1 ], $result['params']);

	}

	public function testGetUpdateSql() {
		$result = PHPUnitUtil::callMethod($this->obj, 'getUpdateSql', [ 'some_table', 'some, fields, here', 'where something happened' ]);
		$this->assertSame('UPDATE `some_table` SET some, fields, here WHERE where something happened', $result);
	}

	public function testGetUpdateFields() {
		try {
			PHPUnitUtil::callMethod($this->obj, 'getUpdateFields', [ [ ] ]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('no data to update', $e->getMessage());
		}

		$result = PHPUnitUtil::callMethod($this->obj, 'getUpdateFields', [ [ 'key1' => 'hi there', 'key2' => 'thanks' ] ]);
		$this->assertSame('`key1` = ?, `key2` = ?', $result);

		$result = PHPUnitUtil::callMethod($this->obj, 'getUpdateFields', [ [ 'key1' => 'hi there' ] ]);
		$this->assertSame('`key1` = ?', $result);
	}

	public function testProcessOperator() {
		try {
			$field = 'fieldname-bogus';
			$value = 'value';
			PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('Operator not defined: BOGUS', $e->getMessage());
		}

		$field = 'field';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('= ?', $result);

		$field = 'field-=';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('= ?', $result);

		$field = 'field-<>';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('<> ?', $result);

		$field = 'field-!=';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('!= ?', $result);

		$field = 'field->=';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('>= ?', $result);

		$field = 'field-<=';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('<= ?', $result);

		$field = 'field->';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('> ?', $result);

		$field = 'field-<';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('< ?', $result);

		$field = 'field-like';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('LIKE ?', $result);

		$field = 'field-not-like';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('NOT LIKE ?', $result);

		$field = 'field';
		$value = null;
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame(null, $value);
		$this->assertSame('IS NULL', $result);

		$field = 'field';
		$value = 'is null';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame(null, $value);
		$this->assertSame('IS NULL', $result);

		$field = 'field';
		$value = 'null';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame(null, $value);
		$this->assertSame('IS NULL', $result);

		$field = 'field';
		$value = 'is not null';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame(null, $value);
		$this->assertSame('IS NOT NULL', $result);

		$field = 'field';
		$value = 'not null';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame(null, $value);
		$this->assertSame('IS NOT NULL', $result);

		$field = 'field-raw-between ? and ?';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('BETWEEN ? AND ?', $result);

		$field = 'field-raw-> DATE_SUB(?, INTERVAL 1 DAY)';
		$value = 'value';
		$result = PHPUnitUtil::callMethod($this->obj, 'processOperator', [ &$field, &$value ]);
		$this->assertSame('field', $field);
		$this->assertSame('value', $value);
		$this->assertSame('> DATE_SUB(?, INTERVAL 1 DAY)', $result);
	}

	public function testBuildWhereSqlStringFromFilters() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'buildWhereSqlStringFromFilters', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [
			'field' => 'value'
		];
		$result = PHPUnitUtil::callMethod($this->obj, 'buildWhereSqlStringFromFilters', [ &$filters ]);
		$this->assertSame([ 'field' => 'value' ], $filters);
		$this->assertSame('WHERE `example_model`.`field` = ?', $result);

		$filters = [
			'field' => 'value',
			'another_field' => 'is null',
			'this_field' => null,
			'that_one->=' => 15
		];
		$result = PHPUnitUtil::callMethod($this->obj, 'buildWhereSqlStringFromFilters', [ &$filters ]);
		$this->assertSame([ 'field' => 'value', 'this_field' => null, 'another_field' => null, 'that_one' => 15 ], $filters);
		$this->assertSame('WHERE `example_model`.`field` = ? AND `example_model`.`another_field` IS NULL AND `example_model`.`this_field` IS NULL AND `example_model`.`that_one` >= ?', $result);
	}

	public function testProcessJoins() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processJoins', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processJoins', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever', 'joins' => 'LEFT JOIN something to something' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processJoins', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('LEFT JOIN something to something', $result);

		$filters = [ 'some_field' => 'whatever', 'joins' => [ 'LEFT JOIN something to something', 'INNER JOIN another thing here' ] ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processJoins', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame("LEFT JOIN something to something\nINNER JOIN another thing here", $result);
	}

	public function testProcessLimit() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processLimit', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processLimit', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever', 'limit' => 5 ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processLimit', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('LIMIT 5', $result);

		$filters = [ 'some_field' => 'whatever', 'limit' => '5.5' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processLimit', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('LIMIT 5', $result);
	}

	public function testProcessOffset() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOffset', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOffset', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever', 'offset' => 5 ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOffset', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('OFFSET 5', $result);

		$filters = [ 'some_field' => 'whatever', 'offset' => '5.5' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOffset', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('OFFSET 5', $result);
	}

	public function testProcessHaving() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processHaving', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processHaving', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever', 'having' => 5 ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processHaving', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('HAVING 5', $result);

		$filters = [ 'some_field' => 'whatever', 'having' => '5.5' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processHaving', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('HAVING 5.5', $result);

		$filters = [ 'some_field' => 'whatever', 'having' => 'some_field > 60' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processHaving', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('HAVING some_field > 60', $result);
	}

	public function testProcessGroupBy() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processGroupBy', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processGroupBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever', 'group_by' => 5 ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processGroupBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('GROUP BY 5', $result);

		$filters = [ 'some_field' => 'whatever', 'group_by' => '5.5' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processGroupBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('GROUP BY 5.5', $result);

		$filters = [ 'some_field' => 'whatever', 'group_by' => 'some_field > 60' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processGroupBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('GROUP BY some_field > 60', $result);
	}

	public function testProcessOrderBy() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOrderBy', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOrderBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('', $result);

		$filters = [ 'some_field' => 'whatever', 'order_by' => 5 ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOrderBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('ORDER BY 5', $result);

		$filters = [ 'some_field' => 'whatever', 'order_by' => '5.5' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOrderBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('ORDER BY 5.5', $result);

		$filters = [ 'some_field' => 'whatever', 'order_by' => 'some_field > 60' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processOrderBy', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('ORDER BY some_field > 60', $result);
	}

	public function testProcessSelectFields() {
		$filters = [];
		$result = PHPUnitUtil::callMethod($this->obj, 'processSelectFields', [ &$filters ]);
		$this->assertSame([], $filters);
		$this->assertSame('SELECT `example_model`.*', $result);

		$filters = [ 'some_field' => 'whatever' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processSelectFields', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('SELECT `example_model`.*', $result);

		$filters = [ 'some_field' => 'whatever', 'select_fields' => 5 ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processSelectFields', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('SELECT 5', $result);

		$filters = [ 'some_field' => 'whatever', 'select_fields' => '5.5' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processSelectFields', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('SELECT 5.5', $result);

		$filters = [ 'some_field' => 'whatever', 'select_fields' => 'some_field > 60' ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processSelectFields', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('SELECT some_field > 60', $result);

		$filters = [ 'some_field' => 'whatever', 'select_fields' => [ 'field', 'another_one' ] ];
		$result = PHPUnitUtil::callMethod($this->obj, 'processSelectFields', [ &$filters ]);
		$this->assertSame([ 'some_field' => 'whatever' ], $filters);
		$this->assertSame('SELECT `example_model`.`field`, `example_model`.`another_one`', $result);
	}
}