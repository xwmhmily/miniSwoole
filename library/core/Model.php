<?php
/**
 * File: Model.php
 * Functionality: Core PDO model class
 * Author: 大眼猫
 * Date: 2013-2-28
 * Note:
 *	1 => This class requires PDO support !
 *	2 => $conn MUST BE set to static for transaction !
 */

abstract class Model {

	protected $table;
	private $insert;
	public $originalTable;
	public $db = 'MASTER';
	private static $conn;        // master connection
	private static $slave;       // slave connection
	private $result = NULL;	
	private $retryMax = 3;
	private static $retries = 0; // 重试次数					
	private $options;            // SQL 中的 field, where, orderby, limit`
	private $selectOne = FALSE;  // 是否是 SelectOne, 不需要 updateOne, deleteOne

	// success code of PDO
	private $successCode = '00000';

	// The result of last operation: failure OR success
	private $success = FALSE;

	/**
	 * Constructor
	 */
	function __construct() {
		//$this->connect();
	}

	private function connect(){
		if(!self::$conn){
			self::$conn = Pool::pop(Pool::TYPE_MYSQL);
		}
	}

	private function connectSlave(){
		if(!self::$slave){
			self::$slave = Pool::getSlaveInstance();
		}
	}

	final public function SetDB($db){
		$this->db = strtoupper($db);
		return $this;
	}

	// table suffix
	final public function Suffix($suffix){
		$this->originalTable = $this->table;

		if(!defined('TB_SUFFIX_SF')){
			$this->table = $this->table.$suffix;
		}else{
			$this->table = $this->table.TB_SUFFIX_SF.$suffix;
		}

		return $this;
	}

	// Clear suffix
    final public function ClearSuffix(){
        if($this->originalTable){
            $this->table = $this->originalTable;
        }

        return $this;
    }

	/**
	 * Field
	 */
	final public function Field($field){
		if(!$field){
			return $this;
		}

		$str = '';
		if(is_array($field)){
			foreach($field as $val){
				// 判断有没有 AS
				if(strpos($val, strtoupper('as')) !== FALSE){
					$str .= $val.',';
				}else{
					$str .= '`'.$val.'`, ';
				}
			}

			$this->options['field'] = substr($str, 0, strlen($str)-2); // 2:　Cos there is a BLANK
		}else{
			$this->options['field'] = $field;
		}

		unset($str, $field);
		return $this;
	}

	/**
	 * Between 支持多次调用
	 */
	final public function Between($key, $start, $end){
		$str = '`'.$key.'` BETWEEN "'.$start.'" AND "'.$end.'"';
		if(isset($this->options['between'])){
			$this->options['between'] .= ' AND '.$str;
		}else{
			$this->options['between'] = $str;
		}
		
		return $this;
	}

	/**
	 * OR 也支持多次调用
	 * 因为 OR 为PHP 关键字, 不能用 OR 作函数名了
	 */
	final public function ORR(){
		$this->options['or'] = TRUE;

		return $this;
	}

	/**
	 * Where 支持多次调用
	 * where 有三种调用方式
	 */
	final public function Where($where, $condition = '', $value = '', $notEmptyValue = FALSE){
		if(!$where){
			return $this;
		}

		if($notEmptyValue){
			if(!$value){
				return $this;
			}
		}

		$str = '';
		if(is_array($where)){
			// 1: $where = array('username' => 'yaf'); 这样的形式
			$total = sizeof($where);
			$i = 1;
			foreach($where as $key => $val){
				$str .= '`'.$key.'` = "'.$val.'"';
				if($i != $total){
					$str .= ' AND ';
				}
				$i++;
			}
		}else{
			// 2: $this->Where($where, $condition, $val); 这样的形式
			// $condition 可为 =, !=, >, >=, <, <=, IN, NOT IN, LIKE, NOT LIKE
			if($condition){
				// 此时的 $where 变成了表字段
				$str .= ' `'.$where.'`'.' '.$condition.' ';

				// 是否是 IN, NOT IN, 是则值带上 (), 支持数组或字符串
				if(stripos($condition, 'IN') !== FALSE){
					// 如果是数组, 则 implode
					if(is_array($value)){
						$str .= '(';
						foreach($value as $v){
							$str .= '"'.$v.'",';
						}

						// 去掉,
						$str = substr($str, 0, -1);
						$str .= ')';
					}else{
						$error = 'The value of IN MUST BE an array';
						Helper::raiseError(debug_backtrace(), $error, $this->sql);
					}
				}else if(stripos($condition, 'LIKE') !== FALSE){
					// 是否是 LIKE, NOT LIKE
					$str .= '"%'.$value.'%"';
				}else{
					// =, !=, >, >=, <, <= 等形式
					$str .= '"'.$value.'"';
				}
			}else{
				// 3: $where = 'username != "yaf"'; 这样的字符串形式
				$str = $where;
			}
		}

		// 无限 WHERE
		if(isset($this->options['where'])){
			// 是否是 OR
			if($this->options['or']){
				$connector = ' OR ';
				$this->options['or'] = FALSE;
			}else{
				$connector = ' AND ';
			}

			$this->options['where'] .= $connector.$str;
		}else{
			$this->options['where'] = $str;
		}
		
		unset($str, $i, $total, $where, $connector);

		return $this;
	}

	/*
	 * Order 支持多次调用
	 */
	final public function Order($order){
		if(!$order){
			return $this;
		}

		if(is_array($order)){
			$total = sizeof($order);
			$i   = 1;
			$str = '';
			foreach($order as $key => $val){
				$str .= '`'.$key.'` '.$val;
				if($i != $total){
					$str .= ' , ';
				}
				$i++;
			}
		}else{
			$str = $order;
		}

		if(isset($this->options['order'])){
			$this->options['order'] .= ', '.$str;
		}else{
			$this->options['order'] = $str;
		}

		unset($str, $i, $total, $order);

		return $this;
	}

	/*
	 * Limit
	 * 可传一个或二个参数
	 */
	final public function Limit($start, $size = ''){
		$this->options['limit'] = $start;

		if($size){
			$this->options['limit'] .= ', '.$size;
		}

		unset($start, $size);

		return $this;
	}

	// Reset SQL options
	final private function reset() {
		self::$retries = 0;
		unset($this->options);

		// TO-DO: A bug when DB is SLAVE
		if(!$this->inTransaction()){
			if(!$this->insert){
				$this->unshift();
			}
		}
	}

	/**
	 * Select records
	 * @return records on success or FALSE on failure 
	 */
	final public function Select(){
		$this->sql = $this->generateSQL();

		$this->Execute();
		$result = $this->success ? $this->Fetch() : NULL;

		if($this->selectOne == TRUE){
			if($result){
				$data = $result[0];
			}else{
				$data = NULL;
			}
		}else{
			$data = $result;
		}

		$this->selectOne = FALSE;
		return $data;
	}

	/**
	 * Select one record
	 */
	final public function SelectOne(){
		$this->options['limit'] = 1;
		$this->selectOne = TRUE;

		return $this->Select();
	}

	/**
	 * Insert | Add a new record
	 *
	 * @param Array => Array('field1'=>'value1', 'field2'=>'value2')
	 * @return FALSE on failure or inserted_id on success
	 */
	final public function Insert($map = [], $ignore = FALSE) {
		if (!$map || !is_array($map)) {
			return FALSE;
		} else {
			$fields = $values = [];

			foreach ($map as $key => $value) {
				$fields[] = '`' . $key . '`';
				$values[] = "'$value'";
			}

			$fieldString = implode(',', $fields);
			$valueString = implode(',', $values);

			$this->sql = 'INSERT ';
			if($ignore){
				$this->sql .= ' IGNORE ';
			}

			$this->sql .= 'INTO '.$this->table." ($fieldString) VALUES ($valueString)";

			$this->insert = TRUE;
			$this->Execute();
			return $this->success ? $this->getInsertID() : NULL;
		}
	}

	/**
	 * Insert | Add a list record
	 *
	 * @param type $data
	 * @return boolean
	 */
	public function MultiInsert($data, $ignore = FALSE){
		$sql = "INSERT ";
		if($ignore){
			$this->sql .= ' IGNORE ';
		}
		$sql .= " INTO ". $this->table;

		$sqlFieldArr = $sqlValueArr = [];
		$first = TRUE;
		foreach($data as $item){
			if(!is_array($item)){
				return FALSE;
			}

			if($first){
				$sqlFieldArr = array_keys($item);

				$sqlFieldStr = implode('`,`', $sqlFieldArr);
				$first = FALSE;
			}

			$tmp = implode('\',\'', $item);
			$tmp = "('$tmp')";
			$sqlValueArr[] = $tmp;
		}

		$sqlValueStr = implode(',', $sqlValueArr);
		$sql .= "(`$sqlFieldStr`) VALUES $sqlValueStr";

		$this->sql = $sql;
		return self::$conn->exec($this->sql);
	}

	/**
	 * Execute special SELECT SQL statement
	 *
	 * @param string  => SQL statement for execution
	 */
	final public function Query($sql) {
		if($sql){
			$this->sql = $sql;
		}else{
			return NULL;
		}

		$this->Execute();

		if($this->success){
			return $this->Fetch();
		}else{
			return FALSE;
		}
	}

	final public function QueryOne($sql){
        if($sql){
            $this->sql = $sql;
        }else{
            return NULL;
        }

        $this->Execute();

        if($this->success){
            return $this->FetchOne();
        }else{
            return FALSE;
        }
    }

	// 根据ID 查询字段:
	public function SelectByID($field, $id){
		$where = [TB_PK => $id];
		return $this->Field($field)->Where($where)->SelectOne();
	}

	// 根据ID更新某一条记录
	public function UpdateByID($map, $id){
		$where = [TB_PK => $id];
		return $this->Where($where)->UpdateOne($map);
	}

	// 根据ID删除某一条记录
	public function DeleteByID($id){
		if(!$id || !is_numeric($id)){
			return FALSE;
		}

		$where = [TB_PK => $id];
		return $this->Where($where)->DeleteOne();
	}

	// 根据ID获取某个字段
	public function SelectFieldByID($field, $id){
		$where = [TB_PK => $id];
		$data = $this->Field($field)->Where($where)->SelectOne();
		return $data[$field];
	}

	/**
	 * Generate SQL by options for Select, SelectOne
	 */
	final protected function generateSQL(){
		if(isset($this->options['field'])){
			$field = $this->options['field'];
		}else{
			$field = '*';
		}

		$sql = 'SELECT '. $field .' FROM `'. $this->table. '`';

		if(isset($this->options['where'])){
			$sql .= ' WHERE '. $this->options['where'];
		}

		// 是否有 BETWEEN
		if(isset($this->options['between'])){
			if(isset($this->options['where'])){
				$sql .= ' AND ';
			}else{
				$sql .= ' WHERE ';
			}

			$sql .= $this->options['between'];
		}

		if(isset($this->options['order'])){
			$sql .= ' ORDER BY '. $this->options['order'];
		}

		if(isset($this->options['limit'])){
			$sql .= ' LIMIT '. $this->options['limit'];
		}

		return $sql;
	}

	/**
	 * Return last inserted_id
	 *
	 * @param NULL
	 * @return the last inserted_id
	 */
	public function getInsertID() {
		$lastInsertID = self::$conn->lastInsertId();
		if(!$this->inTransaction()){
			$this->unshift();
		}
		$this->insert = FALSE;
		return $lastInsertID;
	}

	/**
	 * Fetch data
	 */
	private function Fetch() {
		return $this->result->fetchAll(PDO::FETCH_ASSOC);
	}

    /**
     * Fetch data
     */
    private function FetchOne() {
        return $this->result->fetch(PDO::FETCH_ASSOC);
    }

	/**
	 * Calculate record counts
	 *
	 * @param string => where condition
	 * @return int => total record counts
	 */
	final public function Total() {
		$data = $this->Field('COUNT(*) AS `total`')->SelectOne();
		return $data['total'];
	}

	/**
	 * Execute SELECT | INSERT SQL statements
	 *
	 * @param string => SQL statement to execute
	 * @return result of execution
	 */
	final private function Execute() {
		while(self::$retries < $this->retryMax){
			if($this->db == 'MASTER'){
				$this->connect();
				$this->result = self::$conn->query($this->sql);
			}else{
				$this->connectSlave();
				$this->result = self::$slave->query($this->sql);
			}

			$retval = $this->checkResult();
			if(!$retval['retry']){
				break;
			}

			self::$retries++;
		}

		$this->reset();
		return TRUE;
	}

	/**
	 * Execute UPDATE, DELETE SQL statements
	 *
	 * @return result of execution
	 */
	final private function Exec() {
		while(self::$retries < $this->retryMax){
			$this->connect();
			$rows = self::$conn->exec($this->sql);
			$retval = $this->checkResult();
			if(!$retval['retry']){
				break;
			}
			
			self::$retries++;
		}

		$this->reset();
		return $rows;
	}

	/**
	 * Update record(s)
	 *
	 * @param array => $map = array('field1'=>value1, 'field2'=>value2)
	 * @param boolean $self => self field ?
	 * @return FALSE on failure or affected rows on success
	 */
	final public function Update($map, $self = FALSE) {
		if(!$this->options['where'] && !$this->options['between']){
			return FALSE;
		}

		if (!$map) {
			return FALSE;
		} else {
			$this->sql = 'UPDATE `' . $this->table .'` SET ';
			$sets = [];
			if($self){
				foreach ($map as $key => $value) {
					if (strpos($value, '+') !== FALSE) {
						list($flag, $v) = explode('+', $value);
						$sets[] = "`$key` = `$key` + '$v'";
					} elseif (strpos($value, '-') !== FALSE) {
						list($flag, $v) = explode('-', $value);
						$sets[] = "`$key` = `$key` - '$v'";
					} else {
						$sets[] = "`$key` = '$value'";
					}
				}
			} else {
				foreach ($map as $key => $value) {
					$sets[] = "`$key` = '$value'";
				}
			}

			$this->sql .= implode(',', $sets). ' ';

			if(isset($this->options['where'])){
				$this->sql .= ' WHERE '.$this->options['where'];
			}

			// 是否有 BETWEEN
			if(isset($this->options['between'])){
				if(isset($this->options['where'])){
					$this->sql .= ' AND ';
				}else{
					$this->sql .= ' WHERE ';
				}

				$this->sql .= $this->options['between'];
			}

			if(isset($this->options['order'])){
				$this->sql .= ' ORDER BY '. $this->options['order'];
			}

			if(isset($this->options['limit'])){
				$this->sql .= ' LIMIT '.$this->options['limit'];
			}

			return $this->Exec();
		}
	}

	// 根据ID 给字段累加或减
	public function IncrByID($field, $id, $step = 1, $op = '+'){
		$this->sql = 'UPDATE `' . $this->table .'` SET ';
		$this->sql .= "`$field` = `$field` $op $step ";
		$this->sql .= ' WHERE `'.TB_PK.'` = "'.$id.'" LIMIT 1';

		return $this->Exec();
	}
	
	/*
     *  Update one record
     */
	public function UpdateOne($map, $self = FALSE){
		$this->options['limit'] = 1;
		return $this->Update($map, $self);
	}

	/**
	 * Delete record(s)
	 * @param string => where condition for deletion
	 * @return FALSE on failure or affected rows on success
	 */
	final public function Delete() {
		if(!$this->options['where'] && !$this->options['between']){
			return FALSE;
		}

		$this->sql = 'DELETE FROM `'.$this->table.'` WHERE '.$this->options['where'];

		// 是否有 BETWEEN
		if(isset($this->options['between'])){
			if(isset($this->options['where'])){
				$this->sql .= ' AND ';
			}else{
				$this->sql .= ' ';
			}

			$this->sql .= $this->options['between'];
		}

		if(isset($this->options['order'])){
			$this->sql .= ' ORDER BY '. $this->options['order'];
		}

		if(isset($this->options['limit'])){
			$this->sql .= ' LIMIT '.$this->options['limit'];
		}

		return $this->Exec();
	}

	/**
	 * Delete record(s)
	 * @param string => where condition for deletion
	 * @return FALSE on failure or affected rows on success
	 */
	final public function DeleteOne() {
		$this->options['limit'] = 1;
		return $this->Delete();
	}

	private function getUnderscore($total = 10, $sub = 0) {
		$result = '';
		for($i = $sub; $i<= $total; $i++){
			$result .= '_';
		}
		return $result;
	}

	/**
	 * Check result for the last execution
	 *
	 * @param NULL
	 * @return NULL
	 */
	final private function checkResult(){
		$retry = FALSE;
		if($this->db == 'MASTER'){
			if (self::$conn->errorCode() == $this->successCode) {
				$this->success = TRUE;
			}else{
				$this->success = FALSE;
				$error = self::$conn->errorInfo();
				Helper::raiseError(debug_backtrace(), $error[2], $this->sql);
				if(strpos($error[2], 'MySQL server has gone away') !== FALSE){
					$retry = TRUE;
					$this->reconnect();
				}
			}
		}else{
			if (self::$slave->errorCode() == $this->successCode) {
				$this->success = TRUE;
			}else{
				$this->success = FALSE;
				$error = self::$slave->errorInfo();
				Helper::raiseError(debug_backtrace(), $error[2], $this->sql);
				if(strpos($error[2], 'MySQL server has gone away') !== FALSE){
					$retry = TRUE;
					$this->reconnect();
				}
			}
		}

		$retval = [];
		$retval['retry']   = $retry;
		$retval['success'] = $this->success;
		return $retval;
	}

	private function unshift(){
		Pool::unshift(Pool::TYPE_MYSQL, self::$conn);
		$this->Close();
	}

	// 重新连接
    private function reconnect(){
		Logger::log('Info: reconnect to '.$this->db.' MySQL '.(self::$retries + 1).' time');

		if($this->db == 'MASTER'){
			$this->Close();
			Pool::getInstance(Pool::TYPE_MYSQL);
		}else{
			$this->CloseSlave();
			$this->connectSlave();
		}
	}

    public function ping(){
    	$this->connect();
    	try{
		   	self::$conn->getAttribute(PDO::ATTR_SERVER_INFO);
		} catch (PDOException $e) {
		    if(strpos($e->getMessage(), 'MySQL server has gone away') !== FALSE){
		      	return FALSE;
		    }
		}

		return TRUE;
    }

	// ********* Execute transaction ********* //
	/**
	 * Start a transaction
	 *
	 * @param NULL
	 * @return TRUE on success or FALSE on failure
	 */
	public function BeginTransaction() {
		$this->connect();
		return self::$conn->beginTransaction();
	}

	/**
	 * In a transaction ???
	 *
	 * @param NULL
	 * @return TRUE on yes or FALSE on no
	 */
	public function inTransaction() {
		if(self::$conn){
			return self::$conn->inTransaction();
		}else{
			return FALSE;
		}
	}

	/**
	 * Commit a transaction
	 *
	 * @param NULL
	 * @return TRUE on success or FALSE on failure
	 */
	public function Commit() {
		self::$conn->commit();
		$this->unshift();
	}

	/**
	 * Rollback a transaction
	 *
	 * @param  NULL
	 * @return TRUE on success or FALSE on failure
	 */
	public function Rollback() {
		self::$conn->rollBack();
		$this->unshift();
	}
	// *************** End ***************** //

	/**
	 * Close master connection
	 *
	 * @param NULL
	 * @return NULL
	 */
	private function Close() {
		self::$conn = NULL;
	}

	/**
	 * Close master connection
	 *
	 * @param NULL
	 * @return NULL
	 */
	private function CloseSlave() {
		self::$slave = NULL;
	}

}