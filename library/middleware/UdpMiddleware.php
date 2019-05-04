<?php

class UdpMiddleware {

	// Do anything you want befpre udp packet
	public static function beforePacket(swoole_server $server, string $json, array $client){
		Logger::log(__METHOD__.' => '.$json);
	}

	// Do anything you want after udp packet
	public static function afterPacket(swoole_server $server, string $json, array $client){
		Logger::log(__METHOD__.' => '.$json);
	}

}