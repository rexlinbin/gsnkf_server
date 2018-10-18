<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NewData.class.php 55265 2013-07-13 03:01:18Z HaidongJia $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/lib/data/NewData.class.php $
 * @author $Author: HaidongJia $(hoping@babeltime.com)
 * @date $Date: 2013-07-13 11:01:18 +0800 (星期六, 13 七月 2013) $
 * @version $Revision: 55265 $
 * @brief
 *
 **/
class BaseOperator
{

	protected $value;

	public function getValue()
	{

		return intval ( $this->value );
	}
}

class IncOperator extends BaseOperator
{

	public function __construct($value)
	{

		$this->value = intval ( $value );
	}
}

class DecOperator extends BaseOperator
{

	public function __construct($value)
	{

		$this->value = intval ( $value );
	}
}

class FuncOperator extends BaseOperator
{
	public function __construct($value)
	{
	
		$this->value = strval ( $value );
	}
	
	public function getValue()
	{
		return  $this->value;
	}
}

/**
 * class CData 数据抽象层类(会使用的CDBConfig类的defaultDB配置)
 *
 * @author HaidongJia & HaopingBai
 *
 * @version 1.0.0
 *
 */
class CData
{

	/**
	 * 从数据库中一次取出的数据的最大条目
	 * @var int(const)
	 */
	const MAX_FETCH_SIZE = 100;

	/**
	 * 数据库表名
	 * @var string
	 */
	private $table;

	/**
	 * 数据库select操作列数组
	 * @var array(string)
	 */
	private $arrSelect;

	/**
	 * 数据库insert操作列数组
	 * @var array(string)
	 */
	private $arrInsert;

	/**
	 * 数据库update操作列数组
	 * @var array(array(mixed))
	 */
	private $arrUpdate;

	/**
	 * SQL语句command
	 * @var string
	 */
	private $command;

	/**
	 * 主键重复时需要更新的key
	 * @var array
	 */
	private $arrDuplicateKey;

	/**
	 * SQL语句limit子句选取开始偏移量
	 * @var int
	 */
	private $offset;

	/**
	 * SQL语句limit子句选取最大数量，被MAX_FETCH_SIZE所限制
	 * @var int
	 */
	private $limit;

	/**
	 * 排序子句
	 * @var array
	 */
	private $arrOrderBy;

	/**
	 * 用于id生成器生成唯一id
	 * @var string
	 */
	private $uniqueKey;

	/**
	 * 判断条件
	 * @var array
	 */
	private $arrWhere;

	/**
	 * 批量更新
	 * @var BatchData
	 */
	private $batchData;

	/**
	 * 和 phpproxy的连接
	 * @var PHPProxy
	 */
	private static $proxy = null;

	/**
	 * 清除缓存
	 * @var bool
	 */
	private $noCache;

	/**
	 * 服务名称
	 * @var string
	 */
	private $serviceName;

	/**
	 * 所使用的数据库名
	 * @var string
	 */
	private $db;

	/**
	 * 查询缓存
	 * @var IQueryCache
	 */
	public static $QUERY_CACHE = null;

	/**
	 * CData类构造函数
	 *
	 * @return null
	 *
	 * @author
	 */
	public function __construct($batchData = null)
	{

		$this->batchData = $batchData;
		$this->serviceName = 'data';
		$queryCache = QueryCacheConf::QUERY_CACHE;
		if (! empty ( $queryCache ) && class_exists ( $queryCache ) && empty ( self::$QUERY_CACHE ))
		{
			$pid = getmypid();
			self::$QUERY_CACHE[$pid] = new LocalQueryCache ();
		}
		$this->reset ();
	}

	/**
	 * 设置服务名
	 * @param string $serviceName
	 */
	public function setServiceName($serviceName)
	{

		$this->serviceName = $serviceName;
	}

	/**
	 * 重置条件
	 */
	public function reset()
	{

		$this->limit = null;
		$this->offset = null;
		$this->uniqueKey = null;
		$this->table = null;
		$this->command = null;
		$this->arrInsert = array ();
		$this->arrSelect = array ();
		$this->arrUpdate = array ();
		$this->arrWhere = array ();
		$this->arrOrderBy = array ();
		$this->arrDuplicateKey = array ();
		$this->noCache = false;
		$this->db = RPCContext::getInstance ()->getFramework ()->getDb ();
	}

	/**
	 *
	 * 设置由id生成器生成的唯一id
	 * @param string $uniqueKey
	 */
	public function uniqueKey($uniqueKey)
	{

		if (! is_string ( $uniqueKey ) || is_numeric ( $uniqueKey ))
		{
			$this->reset ();
			Logger::fatal ( "uniqueKey must be string" );
			throw new Exception ( "inter" );
		}
		$this->uniqueKey = $uniqueKey;
		return $this;
	}

	/**
	 * 选择使用哪个库
	 * @param string $db
	 */
	public function useDb($db)
	{

		$this->db = $db;
		return $this;
	}

	/**
	 * CData类析构函数
	 *
	 * @return null
	 *
	 * @throws Exception 如果没有完成事务提交，会throw Exception
	 *
	 * @author
	 */
	public function __destruct()
	{

	}

	/**
	 * select子句
	 *
	 * @param array $arrSelect array("uid", "name")
	 *
	 * @return CData
	 *
	 * @throws Exception 如果参数为空的array，则throw Exception
	 *
	 * @author
	 */
	public function select($arrSelect)
	{

		if (empty ( $arrSelect ) || !is_array($arrSelect))
		{
			$this->reset ();
			Logger::fatal ( 'empty select field found or not array' );
			throw new Exception ( 'inter' );
		}

		$this->command = 'select';
		$this->arrSelect = $arrSelect;
		return $this;
	}

	/**
	 * select count(*)子句
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function selectCount()
	{

		$this->command = 'selectCount';
		return $this;
	}

	/**
	 * from子句
	 *
	 * @param string $table
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function from($table)
	{

		$this->setTable ( $table );
		return $this;
	}

	/**
	 * 更改table名
	 *
	 * @param string $table
	 *
	 * @throws Exception 如果参数为空或者不是string,则会throw Exception
	 */
	private function setTable($table)
	{

		if (empty ( $table ) || ! is_string ( $table ) || is_numeric ( $table ))
		{
			$this->reset ();
			Logger::fatal ( "invalid table specified, must be a not null string" );
			throw new Exception ( "inter" );
		}
		$this->table = $table;
	}

	/**
	 * where子句(只支持单条件查询,判断操作子不支持对于非数字的>,<,<=,>=操作,支持=,!=)
	 *
	 * @param array(mixed) $arrRow array("uid", "=", 1)
	 *
	 * @return CData
	 *
	 * @throws Exception 如果参数为空,操作描述数组元素数不正确,对于判断操作子进行非数字的>,<,>=,<=操作,<br />
	 * between和IN子句不符合要求,则会throw Exception
	 *
	 * @author
	 */
	public function where()
	{

		$arrRow = func_get_args ();
		if (is_array ( $arrRow ) && count ( $arrRow ) == 1)
		{
			$arrRow = $arrRow [0];
		}

		if (empty ( $arrRow ))
		{
			$this->reset ();
			Logger::fatal ( "where can't be empty" );
			throw new Exception ( "inter" );
		}

		$arrOp = array ();
		if (count ( $arrRow ) != 3)
		{
			$this->reset ();
			Logger::fatal ( "invalid option, three values required" );
			throw new Exception ( "inter" );
		}
		$key = $arrRow [0];
		$op = strtoupper ( $arrRow [1] );
		$value = &$arrRow [2];
		switch ($op)
		{
			case '>' :
			case '<' :
			case '>=' :
			case '<=' :
			case '!=' :
			case '=' :
				if (! is_numeric ( $value ))
				{
					$this->reset ();
					Logger::fatal ( "operand %s must operate on number, field:%s is not", $op,
							$key );
					throw new Exception ( "inter" );
				}
				$value = intval ( $value );
				break;
			case 'BETWEEN' :
				if (! is_array ( $value ) || count ( $value ) != 2 || ! is_numeric ( $value [0] ) || ! is_numeric (
						$value [1] ))
				{
					$this->reset ();
					Logger::fatal ( "invalid BETWEEN value, only 2-value numeric array required" );
					throw new Exception ( "inter" );
				}
				$value [0] = intval ( $value [0] );
				$value [1] = intval ( $value [1] );
				break;
			case 'NOT IN' :
			case 'IN' :
				if (! is_array ( $value ) || empty ( $value ))
				{
					$this->reset ();
					Logger::fatal ( "invalid IN value, only not empty array required" );
					throw new Exception ( "inter" );
				}

				$value = array_unique ( $value );
				$value = array_merge ( $value );

				if (count ( $value ) > self::MAX_FETCH_SIZE)
				{
					$this->reset ();
					Logger::fatal ( "too much value for IN" );
					throw new Exception ( "inter" );
				}

				foreach ( $value as $index => $col )
				{
					if (! is_numeric ( $col ))
					{
						$this->reset ();
						Logger::fatal ( "only number is required for IN" );
						throw new Exception ( "inter" );
					}
					$value [$index] = intval ( $col );
				}
				break;
			case '==' :
			case '!==' :
			case 'LIKE' :
				$value = strval ( $value );
				break;
			default :
				$this->reset ();
				Logger::fatal ( "unsupported operand %s", $op );
				throw new Exception ( "inter" );
		}

		if (isset ( $this->arrWhere [$key] ))
		{
			Logger::fatal ( "key:%s already exists in where", $key );
			$this->reset ();
			throw new Exception ( 'inter' );
		}

		$this->arrWhere [$key] = array ($op, $value );
		return $this;
	}

	/**
	 * ordby子句
	 *
	 * @param string $field
	 *
	 * @param boolean $asc
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function orderBy($field, $asc)
	{

		$arrOrderBy = array ($field, $asc ? 'ASC' : 'DESC' );
		$this->arrOrderBy [] = $arrOrderBy;
		return $this;
	}

	/**
	 * limit子句
	 *
	 * @param integer $offset
	 *
	 * @param integer $limit
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function limit($offset, $limit)
	{

		$this->offset = intval ( $offset );
		$this->limit = intval ( $limit );

		if ($this->offset < 0 || $this->limit <= 0)
		{
			$this->reset ();
			Logger::fatal ( "invalid offset:%d limit:%d", $this->offset, $this->limit );
			throw new Exception ( 'inter' );
		}

		return $this;
	}

	/**
	 * insert into子句
	 *
	 * @param string $table
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function insertInto($table)
	{

		$this->setTable ( $table );
		$this->command = 'insertInto';
		return $this;
	}

	/**
	 * insert ignore子句
	 *
	 * @param string $table
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function insertIgnore($table)
	{

		$this->setTable ( $table );
		$this->command = 'insertIgnore';
		return $this;
	}

	/**
	 * delete子句
	 * @param string $table
	 * @return CData
	 */
	public function deleteFrom($table)
	{

		$this->setTable ( $table );
		$this->command = 'delete';
		return $this;
	}

	/**
	 * insert or update子句
	 *
	 * @param string $table
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function insertOrUpdate($table)
	{

		$this->setTable ( $table );
		$this->command = 'insertOrUpdate';
		return $this;
	}

	/**
	 * value子句
	 *
	 * @param array $arrInsert array("uid"=>1, "name"=>"name")
	 *
	 * @return CData
	 *
	 * @throws 如果command!=insert***,或者参数为空,则会throw Exception
	 *
	 * @author
	 */
	public function values($arrInsert)
	{

		if (substr ( $this->command, 0, 6 ) != 'insert')
		{
			$this->reset ();
			Logger::fatal ( "call insertXXX first" );
			throw new Exception ( "inter" );
		}

		if (empty ( $arrInsert ))
		{
			$this->reset ();
			Logger::fatal ( "empty values not allowed" );
			throw new Exception ( "inter" );
		}
		$this->arrInsert = $this->escapeBody ( $arrInsert );
		return $this;
	}

	/**
	 * update子句
	 *
	 * @param string $table
	 *
	 * @return CData
	 *
	 * @author
	 */
	public function update($table)
	{

		$this->setTable ( $table );
		$this->command = 'update';
		return $this;
	}

	/**
	 * 重复时更新的字段
	 * @param array $arrKey
	 * @return CData
	 */
	public function onDuplicateUpdateKey($arrKey)
	{

		if (empty ( $arrKey ))
		{
			Logger::fatal ( "duplicate key can't be empty" );
			throw new Exception ( 'inter' );
		}

		$this->arrDuplicateKey = $arrKey;
		return $this;
	}

	/**
	 * set子句
	 *
	 * @param array $arrUpdate
	 *
	 * @return CData
	 *
	 * @throws Exception 如果command!=update,则throw Exception
	 *
	 * @author
	 */
	public function set($arrUpdate)
	{

		if ($this->command != 'update')
		{
			$this->reset ();
			Logger::fatal ( "call update first" );
			throw new Exception ( "inter" );
		}
		$this->arrUpdate = $this->escapeBody ( $arrUpdate );
		return $this;
	}

	/**
	 * 清除缓存
	 * @return CData
	 */
	public function noCache()
	{

		$this->noCache = true;
		return $this;
	}

	/**
	 * 转义数组,如果数据不是number或者string,则throw Exception
	 *
	 * @param array(mixed) $arrBody
	 *
	 * @return array(mixed)
	 *
	 * @throws Exception
	 *
	 * @author
	 */
	private function escapeBody($arrBody)
	{

		foreach ( $arrBody as $col => $value )
		{
			if ($value instanceof IncOperator)
			{
				$arrBody [$col] = array ("+=", $value->getValue () );
				$this->noCache = true;
			}
			else if ($value instanceof DecOperator)
			{
				$arrBody [$col] = array ("-=", $value->getValue () );
				$this->noCache = true;
			}
			else if ($value instanceof FuncOperator)
			{
				$arrBody [$col] = array ("func", $value->getValue () );
				$this->noCache = true;
			}
			else
			{
				$arrBody [$col] = array ('=', $value );
			}
		}
		return $arrBody;
	}

	/**
	 * 执行SQL语句
	 *

	 *
	 * @return array(mixed) 如果返回的结果为empty array,则SQL执行出错
	 *
	 * @throws 如果command不合法,或者在事务中执行不合法的操作，则会throw Exception
	 *
	 * @author
	 */
	public function query($async = false)
	{

		if ($this->batchData && ($async || substr ( $this->command, 0, 6 ) == 'select'))
		{
			$this->reset ();
			Logger::fatal ( "batchData can't used with asyncQuery and select command" );
			throw new Exception ( "inter" );
		}

		if (empty ( $this->table ))
		{
			$this->reset ();
			Logger::fatal ( "table not set in data" );
			throw new Exception ( 'inter' );
		}

		if (empty ( $this->command ))
		{
			$this->reset ();
			Logger::fatal ( "command not set in data" );
			throw new Exception ( 'inter' );
		}

		$uid = RPCContext::getInstance ()->getUid ();
		$arrData = array ('table' => $this->table, 'command' => $this->command,
				'nocache' => $this->noCache, 'uid' => $uid );

		if (! empty ( $this->db ))
		{
			$arrData ['db'] = $this->db;
		}

		switch ($this->command)
		{
			case 'select' :
				if (empty ( $this->arrWhere ))
				{
					$this->reset ();
					Logger::fatal ( "where field not set for select command" );
					throw new Exception ( 'inter' );
				}

				$arrData ['where'] = $this->arrWhere;

				if (empty ( $this->arrSelect ))
				{
					Logger::fatal ( "select field not set for select command" );
					$this->reset ();
					throw new Exception ( 'inter' );
				}

				$arrData ['select'] = $this->arrSelect;
				if (! empty ( $this->arrOrderBy ))
				{
					$arrData ['orderBy'] = $this->arrOrderBy;
				}
				if ($this->offset !== null && $this->limit !== null)
				{
					$arrData ['offset'] = $this->offset;
					$arrData ['limit'] = $this->limit;
				}
				break;
			case 'selectCount' :
				if (empty ( $this->arrWhere ))
				{
					$this->reset ();
					Logger::fatal ( "where field not set for selectCount command" );
					throw new Exception ( 'inter' );
				}
				$arrData ['where'] = $this->arrWhere;
				break;
			case 'update' :
				if (empty ( $this->arrUpdate ))
				{
					$this->reset ();
					Logger::fatal ( "update field not set for update command" );
					throw new Exception ( 'inter' );
				}
				$arrData ['values'] = $this->arrUpdate;

				if (empty ( $this->arrWhere ))
				{
					$this->reset ();
					Logger::fatal ( "where field not set for update command" );
					throw new Exception ( 'inter' );
				}
				$arrData ['where'] = $this->arrWhere;
				break;
			case 'delete' :
				if (empty ( $this->arrWhere ))
				{
					$this->reset ();
					Logger::fatal ( "where field not set for update command" );
					throw new Exception ( 'inter' );
				}
				$arrData ['where'] = $this->arrWhere;
				break;
			case 'insertInto' :
			case 'insertIgnore' :
			case 'insertOrUpdate' :
				if (! empty ( $this->uniqueKey ))
				{
					$arrData ['unique'] = $this->uniqueKey;
				}

				if (empty ( $this->arrInsert ))
				{
					$this->reset ();
					Logger::fatal ( "insert field not set for insert command" );
					throw new Exception ( 'inter' );
				}

				if (! empty ( $this->arrDuplicateKey ) && $this->command == 'insertOrUpdate')
				{
					$arrData ['duplicate'] = $this->arrDuplicateKey;
				}

				$arrData ['values'] = $this->arrInsert;
				break;
			default :
				$this->reset ();
				Logger::fatal ( "invalid command:%s found", $this->command );
				throw new Exception ( "inter" );
		}
		
		//debug模式下检查一下请求的有效性
		if ( FrameworkConfig::DEBUG )
		{
			$this->checkQuery();
		}

		if ($this->batchData)
		{
			$this->batchData->addRequest ( $arrData );
			return;
		}

		if ($async)
		{
			$proxy = new PHPProxy ( $this->serviceName );
			if (! empty ( $this->db ))
			{
				$proxy->setDb ( $this->db );
			}
			$arrRet = $proxy->asyncQuery ( $arrData );
		}
		else
		{
			$pid = getmypid();
			if ( empty( self::$proxy[$pid] ) )
			{
				self::$proxy[$pid] = new PHPProxy ( $this->serviceName );
			}
			if (! empty ( $this->db ))
			{
				self::$proxy[$pid]->setDb ( $this->db );
			}

			$moreResult = true;
			if (! empty ( self::$QUERY_CACHE[$pid] ) && !$this->noCache)
			{
				Logger::debug ( "get data from cache now" );
				$arrCache = self::$QUERY_CACHE[$pid]->beforeQuery ( $arrData, $moreResult );
			}

			if ($moreResult)
			{
				Logger::debug ( "more data needed from dataproxy" );
				$arrRet = self::$proxy[$pid]->query ( $arrData );

				//如果设置了noCache，返回的结果不能保证包含表的所有字段，所以不能放到$QUERY_CACHE中
				//目前$QUERY_CACHE对缓存数据的字段是否完整的检查不够严格，在业务使用时需要注意。
				if (! empty ( self::$QUERY_CACHE[$pid] ) && !$this->noCache)
				{
					self::$QUERY_CACHE[$pid]->afterQuery ( $arrData, $arrRet );
					if (! empty ( $arrCache ))
					{
						$arrRet = array_merge ( $arrCache, $arrRet );
					}
				}
			}
			else
			{
				Logger::debug ( "all data found" );
				$arrRet = $arrCache;
			}

		}

		if ($this->command == 'select')
		{
			$arrReturn = array ();
			foreach ( $arrRet as $arrRow )
			{
				if (isset ( $arrRow ['__cache__'] ))
				{
					Logger::debug ( "filter found in result" );
					unset ( $arrRow ['__cache__'] );
					if (! $this->checkCondition ( $arrRow, $this->arrWhere ))
					{
						continue;
					}
				}

				if (count ( $arrRow ) > count ( $this->arrSelect ))
				{
					$arrTmpRow = array ();
					foreach ( $this->arrSelect as $col )
					{
						$arrTmpRow [$col] = $arrRow [$col];
					}
					$arrReturn [] = $arrTmpRow;
				}
				else
				{
					$arrReturn [] = $arrRow;
				}

			}
			$arrRet = $arrReturn;
		}

		$this->reset ();
		return $arrRet;
	}

	private function checkCondition($arrRow, $arrCondList)
	{

		foreach ( $arrCondList as $key => $arrCond )
		{
			if (! isset ( $arrRow [$key] ))
			{
				return true;
			}

			$value = $arrRow [$key];
			switch ($arrCond [0])
			{
				case '=' :
					$ret = $value == $arrCond [1];
					break;
				case '!=' :
					$ret = $value != $arrCond [1];
					break;
				case '>' :
					$ret = $value > $arrCond [1];
					break;
				case '<' :
					$ret = $value < $arrCond [1];
					break;
				case '>=' :
					$ret = $value >= $arrCond [1];
					break;
				case '<=' :
					$ret = $value <= $arrCond [1];
					break;
				case 'BETWEEN' :
					$ret = $value >= $arrCond [1] [0] && $value <= $arrCond [1] [1];
					break;
				case 'IN' :
					$ret = in_array ( $value, $arrCond [1] );
					break;
				case 'NOT IN' :
					$ret = ! in_array ( $value, $arrCond [1] );
					break;
				case 'LIKE' :
					//FIXME like也需要检查吗？
					$ret = true;
					break;
				default :
					Logger::fatal ( 'operand:%s not supported for primary key select',
							$arrCond [0] );
					$this->reset ();
					throw new Exception ( 'inter' );
			}

			if (! $ret)
			{
				return $ret;
			}
		}

		return true;
	}
	
	private function checkQuery()
	{
		if ( $this->command == 'update' )
		{
			$arrDecOp = array();
			foreach ( $this->arrUpdate as $col => $value)
			{
				if ( $value[0] == '-=' )
				{
					$arrDecOp[] = $col;
				}
			}
			foreach ( $arrDecOp as $col )
			{
				if ( isset($this->arrWhere[$col])  )
				{
					if( !in_array($this->arrWhere[$col][0], array('>', '>=', 'BETWEEN') ) )
					{
						throw new InterException('col:%s has DecOperator, invalid where:%s', $col, $this->arrWhere);
					}
				}
				else
				{
					throw new InterException('col:%s has DecOperator, but not found where', $col);
				}
			}
		}
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
