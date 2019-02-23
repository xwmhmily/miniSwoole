<?php

class M_Default extends Model {

	function __construct($table) {
		$this->table = TB_PREFIX.$table;
		parent::__construct();
	}

}