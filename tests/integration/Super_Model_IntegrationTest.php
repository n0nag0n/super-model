<?php

use PHPUnit\Framework\TestCase;

class Super_Model_IntegrationTest extends TestCase {
	protected $pdo, $obj;

	public function setUp(): void {
		$this->pdo = new PDO('sqlite::memory:', '', '', [ PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC ]);
		$this->obj = new Example_Model($this->pdo);
	}

	public function tearDown(): void {
		$this->dropDbTable();
	}

	protected function dropDbTable() {
		$this->pdo->exec("DROP TABLE IF EXISTS 'example_model'");
	}

	protected function createDbTable() {
		$this->pdo->exec("CREATE TABLE IF NOT EXISTS 'example_model' ('id' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, 'int_field' INTEGER NOT NULL, 'string_field' TEXT, 'date_field' TEXT)");
	}

	protected function createRow(array $insert_data) {
		$statement = $this->pdo->prepare("INSERT INTO example_model (int_field, string_field, date_field) VALUES (?, ?, ?)");
		$statement->execute($insert_data);
		return $this->pdo->lastInsertId();
	}

	public function testCreate() {
		$this->createDbTable();
		$id = $this->obj->create([ 'int_field' => 1, 'string_field' => 'a string', 'date_field' => '2020-02-02 20:20:20' ]);
		$this->assertSame(1, $id);

		$id = $this->obj->create([ [ 'int_field' => 1, 'string_field' => 'a string', 'date_field' => '2020-02-02 20:20:20' ], [ 'int_field' => 1, 'string_field' => 'a string', 'date_field' => '2020-02-02 20:20:20' ] ]);
		$this->assertSame(3, $id);
	}

	public function testUpdate() {
		$this->createDbTable();
		$id = $this->createRow([ 2, 50, 'badstring' ]);
		$affected_rows = $this->obj->update([ 'id' => $id, 'int_field' => 55, 'string_field' => 'something else', 'date_field' => '2020-01-01 00:00:00' ]);
		$this->assertSame(1, $affected_rows);

		$pdo = $this->obj->getDbConnection();
		$s = $pdo->prepare("SELECT * FROM example_model");
		$s->execute();

		$result = $s->fetchAll(PDO::FETCH_ASSOC)[0];
		$this->assertSame('1', $result['id']);
		$this->assertSame('55', $result['int_field']);
		$this->assertSame('something else', $result['string_field']);
		$this->assertSame('2020-01-01 00:00:00', $result['date_field']);
	}

	public function testGetAll() {
		$this->createDbTable();
		$id1 = $this->createRow([ 2, 50, '2020-02-02 10:00:00' ]);
		$id2 = $this->createRow([ 2, 'hi', '2020-02-02 12:00:00' ]);
		$id3 = $this->createRow([ 3, 'there', '2020-02-02 14:00:00' ]);

		try {
			$results = $this->obj->getAll([]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('Cannot run wide open query against the table', $e->getMessage());
		}

		$results = $this->obj->getAll([ 'int_field' => 2 ]);
		$result1 = $results[0];
		$result2 = $results[1];
		$this->assertSame('1', $result1['id']);
		$this->assertSame('2', $result1['int_field']);
		$this->assertSame('50', $result1['string_field']);
		$this->assertSame('2020-02-02 10:00:00', $result1['date_field']);

		$this->assertSame('2', $result2['id']);
		$this->assertSame('2', $result2['int_field']);
		$this->assertSame('hi', $result2['string_field']);
		$this->assertSame('2020-02-02 12:00:00', $result2['date_field']);

		$this->assertArrayNotHasKey(2, $results);
	}

	public function testGetAllBySomething() {
		$this->createDbTable();
		$id1 = $this->createRow([ 2, 50, '2020-02-02 10:00:00' ]);
		$id2 = $this->createRow([ 2, 'hi', '2020-02-02 12:00:00' ]);
		$id3 = $this->createRow([ 3, 'there', '2020-02-02 14:00:00' ]);

		try {
			$results = $this->obj->getAllBy([]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('unable to parse out field', $e->getMessage());
		}

		try {
			$results = $this->obj->getAllByNothing();
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('no value supplied', $e->getMessage());
		}

		$results = $this->obj->getAllByInt_field(2);
		$result1 = $results[0];
		$result2 = $results[1];
		$this->assertSame('1', $result1['id']);
		$this->assertSame('2', $result1['int_field']);
		$this->assertSame('50', $result1['string_field']);
		$this->assertSame('2020-02-02 10:00:00', $result1['date_field']);

		$this->assertSame('2', $result2['id']);
		$this->assertSame('2', $result2['int_field']);
		$this->assertSame('hi', $result2['string_field']);
		$this->assertSame('2020-02-02 12:00:00', $result2['date_field']);

		$this->assertArrayNotHasKey(2, $results);

		$results = $this->obj->getAllByINT_FIELD(2);
		$result1 = $results[0];
		$result2 = $results[1];
		$this->assertSame('1', $result1['id']);
		$this->assertSame('2', $result1['int_field']);
		$this->assertSame('50', $result1['string_field']);
		$this->assertSame('2020-02-02 10:00:00', $result1['date_field']);

		$this->assertSame('2', $result2['id']);
		$this->assertSame('2', $result2['int_field']);
		$this->assertSame('hi', $result2['string_field']);
		$this->assertSame('2020-02-02 12:00:00', $result2['date_field']);

		$this->assertArrayNotHasKey(2, $results);
	}

	public function testGetBySomething() {
		$this->createDbTable();
		$id1 = $this->createRow([ 2, 50, '2020-02-02 10:00:00' ]);
		$id2 = $this->createRow([ 2, 'hi', '2020-02-02 12:00:00' ]);
		$id3 = $this->createRow([ 3, 'there', '2020-02-02 14:00:00' ]);

		try {
			$results = $this->obj->getBy([]);
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('unable to parse out field', $e->getMessage());
		}

		try {
			$results = $this->obj->getByNothing();
			$this->fail('Should have failed');
		} catch(Exception $e) {
			$this->assertSame('no value supplied', $e->getMessage());
		}

		$result = $this->obj->getByInt_field(2);
		$this->assertSame('1', $result['id']);
		$this->assertSame('2', $result['int_field']);
		$this->assertSame('50', $result['string_field']);
		$this->assertSame('2020-02-02 10:00:00', $result['date_field']);

		$results = $this->obj->getByInt_field(2);
		$this->assertSame('1', $result['id']);
		$this->assertSame('2', $result['int_field']);
		$this->assertSame('50', $result['string_field']);
		$this->assertSame('2020-02-02 10:00:00', $result['date_field']);
	}
}