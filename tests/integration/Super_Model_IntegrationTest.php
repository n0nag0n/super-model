<?php

use PHPUnit\Framework\TestCase;

class Super_Model_IntegrationTest extends TestCase {

	public function setUp(): void {
		$this->pdo = new PDO('sqlite::memory:');
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
}