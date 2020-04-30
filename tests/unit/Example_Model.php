<?php

class Example_Model extends n0nag0n\Super_Model {
	protected $is_testing = true,
		$table = 'example_model';

	public function processResult(array $process_results_filters, array $result): array {
		if(isset($process_results_filters['some_field']) && $process_results_filters['some_field'] === true) {
			$result['added_new_field'] = 'totally true';
		}

		return $result;
	}
}