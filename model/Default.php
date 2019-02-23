<?php
/**
 * 这是默认模型文件, DO NOT DELETE !!!
 */

class M_Default extends Model {

	function __construct($table) {
		$this->table = TB_PREFIX.$table;
		parent::__construct();
	}

}