<?php
declare(strict_types = 1);



/*




need to still test processresult and then build out joining infrastructure, also need to do more getBy* tests








*/










namespace n0nag0n;

use Exception;
use PDO;

abstract class Super_Model {
	protected $db, $table, $disallow_wide_open_queries = true, $is_testing = false, $db_values = [];

	/**
	 * The public construct
	 *
	 * @param PDO $db
	 */
	public function __construct(PDO $db = null) {

		if(empty($this->table)) {
			throw new Exception('table not defined in final model');
		}

		if(!is_object($db) || !($db instanceof PDO)) {
			throw new Exception('$db needs to be instance of PDO');
		}

		$this->db = $db;
	}

	/**
	 * Sets the key attached to the db_values array
	 *
	 * @param mixed $key
	 * @param mixed $val
	 */
	// @codeCoverageIgnoreStart
	public function __set($key, $val) {
		$this->db_values[$key] = $val;
	}

	/**
	 * Gets the key from db_values
	 *
	 * @param [type] $key
	 * @return mixed
	 */
	public function __get($key) {
		return $this->db_values[$key];
	}

	/**
	 * Checks if key is set in db_values
	 *
	 * @param mixed $key
	 * @return boolean
	 */
	public function __isset($key) {
		return isset($this->db_values[$key]);  
	}

	/**
	 * Unsets key in db_values
	 *
	 * @param mixed $key
	 */
	public function __unset($key) {  
		unset($this->db_values[$key]);  
	}

	/**
	 * Allows you to use "getBy[table_field]" or "getAllBy[table_field]" Ex: getAllByCompany_Id(5), getById(1), getByToken('abalkjdoiad')
	 *
	 * @param string $method
	 * @param array $parameters
	 * @return void
	 */
	public function __call(string $method, array $parameters) {
		$has_get_by = strpos($method, 'getBy') !== false;
		$has_get_by_all = strpos($method, 'getAllBy') !== false;
		if($has_get_by || $has_get_by_all) {
			$field = strtolower(str_ireplace([ 'getBy', 'getAllBy' ], '', $method));
			if(empty($field)) {
				throw new Exception('unable to parse out field');
			}

			if(!isset($parameters[0])) {
				throw new Exception('no value supplied');
			}
			$value = $parameters[0];

			$method_to_call = $has_get_by ? '_getSingleRowBy' : '_getAllRowsBy';
			return $this->{$method_to_call}($field, $value);
		}
	}
	// @codeCoverageIgnoreEnd

	/**
	 * Returns PDO connection
	 *
	 * @return PDO
	 */
	public function getDbConnection() {
		return $this->db;
	}

	/**
	 * Used by __call('getBy*') to pull out a single row from the database
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return mixed
	 */
	protected function _getSingleRowBy(string $field, $value) {
		$result = $this->getAll([ $field => $value, 'limit' => 1 ], true);
		if(is_array($result) && count($result)) {
			$this->mapResultToModel($result);
		}
		return $result;
	}

	/**
	 * Used by __call('getAllBy*') to pull out rows from the database
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return mixed
	 */
	protected function _getAllRowsBy(string $field, $value) {
		$result = $this->getAll([ $field => $value ], false);
		return $result;
	}

	/**
	 * Main function to pull out data from the database. 
	 *
	 * @example $model->getAll([ 'some_field' => 1, 'another_field->=' => 5, 'group_by' => 'another_field', 'having' => 'another_field > 10' ]);
	 * @param array $filters
	 * @param boolean $return_one_row
	 * @return array
	 */
	public function getAll(array $filters = [], $return_one_row = false) {

		$processed_filters = $this->processAllFilters($filters);
		$sql = $processed_filters['sql'];
		$params = $processed_filters['params'];

		$statement = $this->db->prepare($sql);
		$statement->execute($params);
		$results = $statement->fetchAll();
		
		$results = $this->processResults($filters, $results);

		return $return_one_row ? $results[0] : $results;
	}

	public function processResults(array $filters, array $results): array {
		if(count($results)) {
			foreach($results as $key => $result) {
				if(is_array($result)) {
					$results[$key] = $this->processResult($filters, $result);
				}
			}
		}
		return $results;
	}

	public function processResult(array $filters, array $result): array {
		return $result;
	}

	public function create(array $data): int {

		$create_data = $this->processCreateData($data);
		$sql = $create_data['sql'];
		$params = $create_data['params'];

		$statement = $this->db->prepare($sql);
		$statement->execute($params);
		
		return intval($this->db->lastInsertId());
	}

	protected function processCreateData(array $data): array {
		$fields = $this->getCreateFields($data);
		$placeholders = $this->getCreatePlaceholders($data);
		$sql = $this->getCreateSql($this->table, $fields, $placeholders);
		$params = $this->getCreateParams($data);

		return [
			'sql' => $sql,
			'params' => $params,
		];
	}

	protected function getCreateParams(array $data): array {
		$params = [];
		if(is_array($data) && isset($data[0])) {
			foreach($data as $d) {
				$params = array_merge($params, array_values($d));
			}
		} else {
			$params = array_values($data);
		}
		return $params;
	}

	protected function getCreateSql($table, $fields, $placeholders) {
		if(is_array($placeholders) && isset($placeholders[0])) {
			$placeholder_sql = [];
			foreach($placeholders as $placeholder) {
				$placeholder_sql[] = "({$placeholder})";
			}
			$placeholder_sql = join(', ', $placeholder_sql);
		} else {
			$placeholder_sql = "({$placeholders})";
		}
		return "INSERT INTO `{$table}` ({$fields}) VALUES {$placeholder_sql}";
	}

	protected function getCreateFields(array $data): string {
		if(is_array($data) && isset($data[0])) {
			$data = $data[0];
		}
		return '`'.join('`, `', array_keys($data)).'`';
	}

	protected function getCreatePlaceholders(array $data): array {
		$placeholder_sql = [];
		if(is_array($data) && isset($data[0])) {
			foreach($data as $d) {
				$placeholder_sql[] = join(', ', array_fill(0, count($d), '?'));
			}
		} else {
			$placeholder_sql[] = join(', ', array_fill(0, count($data), '?'));
		}
		return $placeholder_sql;
	}

	public function update(array $data, string $update_field = 'id'): int {

		$update_data = $this->processUpdateData($data, $update_field);
		$sql = $update_data['sql'];
		$params = $update_data['params'];

		$statement = $this->db->prepare($sql);
		$statement->execute($params);
		
		return $statement->rowCount();
	}

	protected function processUpdateData(array $data, string $update_field): array {

		if(!isset($data[$update_field])) {
			throw new Exception($update_field.' update field missing');
		}

		$where = "{$update_field} = ?";
		$where_field = $data[$update_field];
		unset($data[$update_field]);

		$fields = $this->getUpdateFields($data);

		$sql = $this->getUpdateSql($this->table, $fields, $where);
		$params = array_values($data);
		$params[] = $where_field;
		return [
			'sql' => $sql,
			'params' => $params,
		];
	}

	protected function getUpdateSql(string $table, string $fields, string $where): string {
		return "UPDATE `{$table}` SET {$fields} WHERE {$where}";
	}

	protected function getUpdateFields(array $data): string {

		if(!count($data)) {
			throw new Exception('no data to update');
		}

		return '`'.join("` = ?, `", array_keys($data)).'` = ?';
	}

	protected function mapResultToModel(array $result): void {

		if(isset($result[0])) {
			throw new Exception('cannot map multi-dimentional arrays');
		}

		foreach($result as $key => $value) {
			$this->{$key} = $value;
		}
	}

	protected function processAllFilters(array $filters): array {
		$select_fields = $this->processSelectFields($filters);
		$joins = $this->processJoins($filters);
		$group_by = $this->processGroupBy($filters);
		$order_by = $this->processOrderBy($filters);
		$limit = $this->processLimit($filters);
		$offset = $this->processOffset($filters);
		$having = $this->processHaving($filters);

		$where = $this->buildWhereSqlStringFromFilters($filters);

		if($where === '' && $this->disallow_wide_open_queries) {
			throw new Exception('Cannot run wide open query against the table');
		}

		$params = $this->processParams($filters);

		$sql = $this->getSelectSql($select_fields, $this->table, $joins, $where, $group_by, $having, $order_by, $limit, $offset);

		return [
			'select_fields' => $select_fields,
			'joins' => $joins,
			'group_by' => $group_by,
			'having' => $having,
			'order_by' => $order_by,
			'limit' => $limit,
			'offset' => $offset,
			'where' => $where,
			'params' => $params,
			'sql' => $sql
		];
	}

	protected function getSelectSql(string $select_fields, string $table, string $joins = '', string $where = '', string $group_by = '', string $having = '', string $order_by = '', string $limit = '', string $offset = ''): string {
		$array = [
			$joins,
			$where,
			$group_by,
			$having,
			$order_by,
			$limit,
			$offset
		];
		$array = array_filter($array, function($value) { return $value !== ''; });
		return "{$select_fields} FROM `{$table}` ".join(' ', $array);
	}

	protected function processParams(array $filters) {
		$filters = array_filter($filters, function($value) { 
			return $value !== null; 
		});
		return array_values($filters);
	}

	protected function processSelectFields(array &$filters): string {
		$select_fields = '`'.$this->table.'`.*';
		if(isset($filters['select_fields'])) {
			$table = $this->table;
			$select_fields = is_array($filters['select_fields']) ? join(', ', array_map(function($value) use ($table) { return '`'.$table.'`.`'.$value.'`'; }, $filters['select_fields'])) : $filters['select_fields'];
			unset($filters['select_fields']);
		}

		return 'SELECT '.$select_fields;
	}

	protected function processSimpleStatement(array &$filters, string $field_key, string $sql_statement, string $field_type = 'raw'): string {
		$sql = '';
		if(isset($filters[$field_key])) {
			$value = $field_type === 'int' ? intval($filters[$field_key]) : $filters[$field_key];
			$sql = $sql_statement.' '.$value;
			unset($filters[$field_key]);
		}

		return $sql;
	}

	protected function processOrderBy(array &$filters): string {
		return $this->processSimpleStatement($filters, 'order_by', 'ORDER BY');
	}

	protected function processGroupBy(array &$filters): string {
		return $this->processSimpleStatement($filters, 'group_by', 'GROUP BY');
	}

	protected function processHaving(array &$filters): string {
		return $this->processSimpleStatement($filters, 'having', 'HAVING');
	}

	protected function processLimit(array &$filters): string {
		return $this->processSimpleStatement($filters, 'limit', 'LIMIT', 'int');
	}

	protected function processOffset(array &$filters): string {
		return $this->processSimpleStatement($filters, 'offset', 'OFFSET', 'int');
	}

	protected function processJoins(array &$filters): string {
		$join_sql_str = '';

		if(isset($filters['joins'])) {
			$join_sql_str = is_array($filters['joins']) ? join("\n", $filters['joins']) : $filters['joins'];
			unset($filters['joins']);
		}
		return $join_sql_str;
	}

	protected function buildWhereSqlStringFromFilters(array &$filters): string {
		$table = $this->table;
		$this_model = $this;
		$sql_str = join(' AND ', array_map(function(&$field, &$value) use ($table, $this_model, &$filters) {
			$original_field = $field;
			$original_value = $value;
			$operator = $this_model->processOperator($field, $value);
			if($original_field !== $field || $original_value !== $value) {
				unset($filters[$original_field]);
				$filters[$field] = $value;
			}
			return "`{$table}`.`{$field}` {$operator}";
		}, array_keys($filters), $filters));

		return strlen($sql_str) !== 0 ? 'WHERE '.$sql_str : '';
	}

	protected function processOperator(string &$field, &$value): string {
		$operator = '';

		$data = explode('-', $field, 2);

		if(count($data) === 1) {
			$compare_value = '';
			if(is_string($value)) {
				$compare_value = $value;
				$compare_value = strtoupper($compare_value);
			}
			if($value === null || $compare_value === 'IS NULL' || $compare_value === 'NULL') {
				$value = null;
				return 'IS NULL';
			} else if($compare_value === 'IS NOT NULL' || $compare_value === 'NOT NULL') {
				$value = null;
				return 'IS NOT NULL';
			} else {
				return '= ?';
			}
		}

		$data[1] = strtoupper($data[1]);
		switch($data[1]) {
			case '!=':
			case '<>':
			case '=':
			case '>=':
			case '>':
			case '<':
			case '<=':
			case 'LIKE':
			case 'NOT LIKE':
			case 'NOT-LIKE':
				$operator = str_replace('-', ' ', $data[1]).' ?';
			break;

			case 'IN':
			case 'NOT IN':
			case 'NOT-IN':
				$operator = $data[1].'(??)';
			break;

			case substr($data[1], 0, 3) === 'RAW':
				$another_explode = explode('-', $data[1]);
				$operator = $another_explode[1];
			break;

			default:
				throw new Exception('Operator not defined: '.$data[1]);
		}

		$field = $data[0];

		return $operator;
	}
}