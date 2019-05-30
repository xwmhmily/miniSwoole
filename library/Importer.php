<?php

class Importer {

	public static function task(...$param){
		Logger::log('Param in '.__METHOD__.' is => '.JSON($param));
	}

	public static function run(...$param){
		Logger::log('Param in '.__METHOD__.' => '.JSON($param));
	}
}