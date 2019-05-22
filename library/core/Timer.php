<?php
/**
 * File: Timer.php
 * Author: 大眼猫
 */

abstract class Timer {

	// Add timer
	public static function add(int $ms, callable $callback, $args = NULL) {
		return Server::getInstance()->tick($ms, $callback, $args);
	}

	// Add after timer
	public static function after(int $ms, callable $callback) {
		return Server::getInstance()->after($ms, $callback);
	}

	// Clear timer
	public static function clear($timerID) {
		return Server::getInstance()->clearTimer($timerID);
	}
}
