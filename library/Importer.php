<?php

class Importer {

	public static function run(...$param){
		Logger::log('Param in '.__METHOD__.' => '.JSON($param));
	}
}