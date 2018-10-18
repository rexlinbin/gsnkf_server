<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckVitualTable.php 98539 2014-04-09 07:19:34Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/CheckVitualTable.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2014-04-09 07:19:34 +0000 (Wed, 09 Apr 2014) $
 * @version $Revision: 98539 $
 * @brief
 *
 **/

/**
 *
 * 检查线上的虚表,如果不足则创建
 *
 * @example php CheckVitualTable.php -h 192.168.3.31 -u root -p admin -d pirate30001 -m 192.168.5.249
 *
 * @author pkujhd(jhd@babeltime.com)
 *
 */

/**
 *
 * 每次建立的表的数量
 * @var int
 */
const CREATE_TABLE_NUM = 5;

class partitionTable
{
	private static $XMLPATH = "/home/pirate/rpcfw/data/dataproxy.xml";

	private static $XMLS = NULL;

	private static $TABLES = array();

	public static function setXMLPATH($path)
	{
		if ( file_exists($path) )
		{
			self::$XMLPATH = $path;
		}
	}

	private static function loadXML()
	{
		if ( isset(self::$XMLS) )
		{
			return self::$XMLS;
		}

		if ( file_exists(self::$XMLPATH) == FALSE )
		{
			throw new Exception(self::$XMLPATH . 'not exist!');
		}

		self::$XMLS = simplexml_load_file(self::$XMLPATH);
		return self::$XMLS;
	}

	public static function getCheckTable()
	{
		if ( !empty(self::$TABLES) )
		{
			return self::$TABLES;
		}

		$xmls = self::loadXML();

		$tables = array();

		foreach ( $xmls->table as $key => $value )
		{
			if ( isset($value->partition) && $value->partition->method == 'div')
			{
				$tables[] = $value->name;
			}
		}

		self::$TABLES = $tables;
		return self::$TABLES;
	}
}

class MysqlQuery
{
	private $m_mysql = NULL;

	private $m_server = NULL;

	private $m_user = NULL;

	private $m_password = NULL;

	public function setServerInfo($server, $db, $user, $password)
	{
		$this->m_server = $server;
		$this->m_db = $db;
		$this->m_user = $user;
		$this->m_password = $password;
		if ( !empty($this->m_mysql) )
		{
			$this->m_mysql->mysqli_close();
		}
		$this->m_mysql = NULL;
	}

	private function connect()
	{
		if ( $this->m_mysql === NULL )
		{
			if ( empty($this->m_server) || empty($this->m_db) ||
				empty($this->m_user) || empty($this->m_password) )
			{
				throw new Exception('mysql connect info invalid!');
			}
			$this->m_mysql = mysqli_connect($this->m_server, $this->m_user,
				 $this->m_password, $this->m_db);
			if ( $this->m_mysql == FALSE )
			{
				throw new Exception('connect mysql failed!');
			}
			mysqli_query($this->m_mysql, "set names utf8;");
		}
		return $this->m_mysql;
	}

	public function query($sql)
	{
		if ( $this->m_mysql === NULL )
		{
			self::connect();
		}
		$query = mysqli_query($this->m_mysql, $sql);
		$error = mysqli_error($this->m_mysql);

		if ( !empty($error) )
		{
			var_dump($sql);
			var_dump($error);
			return array();
		}

		if ( strpos($sql, 'INSERT') !== 0 and
			strpos($sql, 'insert') !== 0 and
			strpos($sql, 'UPDATE') !== 0 and
			strpos($sql, 'update') !== 0 )
		{
			$return = array();
			while ( TRUE )
			{
				$row = mysqli_fetch_array($query, MYSQLI_ASSOC);
				if ( empty($row) )
				{
					break;
				}
				$return[] = $row;
			}
			return $return;
		}
	}
}

function print_usage()
{
	echo	"Usage:php CheckVitualTable.php [options]:
				-h		db host;
				-u		db user;
				-p		db password;
				-d		db name;
				-m		master db host;
				-x		xml path default:/home/pirate/rpcfw/data/dataproxy.xml;
				-?		help list!\n";
}

function main()
{
	global $argc, $argv;

	$result = getopt('u:p:d:h:x:m:?');

	$dbhost = '';
	$user = '';
	$password = '';
	$dbname = '';
	$path = '';
	$masterdbhost = '';

	foreach ( $result as $key => $value )
	{
		switch ( $key )
		{
			case 'h':
				$dbhost = strval($value);
				break;
			case 'u':
				$user = strval($value);
				break;
			case 'p':
				$password = strval($value);
				break;
			case 'd':
				$dbname = strval($value);
				break;
			case 'x':
				$path = strval($value);
				break;
			case 'm':
				$masterdbhost = strval($value);
				break;
			case '?':
			default:
				print_usage();
				exit;
		}
	}

	if ( empty($dbhost) )
	{
		fwrite(STDERR, "-h should be set!");
		print_usage();
		exit;
	}

	if ( empty($user) )
	{
		fwrite(STDERR, "-u should be set!");
		print_usage();
		exit;
	}

	if ( empty($password) )
	{
		fwrite(STDERR, "-p should be set!");
		print_usage();
		exit;
	}

	if ( empty($dbname) )
	{
		fwrite(STDERR, "-d should be set!");
		print_usage();
		exit;
	}

	if ( !empty($path) )
	{
		if ( !file_exists($path) )
		{
			fwrite(STDERR, "-x should be set a valid file!");
			print_usage();
			exit;
		}
		partitionTable::setXMLPATH($path);
	}

	$tables = partitionTable::getCheckTable();

	foreach ( $tables as $table )
	{
		$mysql = new MysqlQuery();
		$mysql->setServerInfo($dbhost, $dbname, $user, $password);
		$query = $mysql->query("show tables like '$table%'");
		$max_id = -1;
		foreach ( $query as $data )
		{
			foreach ( $data as $key => $value )
			{
				if ( strlen($value) > strlen($table)+1 &&
					 $value[strlen($table)+1] >= chr(48) &&
					 $value[strlen($table)+1] <= chr(57) )
				{
					$v = intval(substr($value, strlen($table)+1));
					if ( $v > $max_id )
					{
						$max_id = $v;
					}
				}
			}
		}
		if ( $max_id == -1 )
		{
			echo "DB:$dbhost DBNAME:$dbname table:$table may be has mistake!\n";
		}
		else
		{
			$table_name = $table . "_" . $max_id;
			$return = $mysql->query("select * from $table_name limit 1");
			if ( count($return) == 0 )
			{
				echo "DB:$dbhost DBNAME:$dbname table:$table is enough!\n";
			}
			else
			{
				echo "IMPORT!DB:$dbhost DBNAME:$dbname table:$table need add new table!\n";
				if ( !empty($masterdbhost) )
				{
					$mdb_mysql = new MysqlQuery();
					$mdb_mysql->setServerInfo($masterdbhost, $dbname, $user, $password);
					for ( $k = 0; $k < CREATE_TABLE_NUM; $k++ )
					{
						$new_table_name = $table . '_' . ($max_id + $k + 1);
						$base_table_name = $table . '_0';
						$query = $mdb_mysql->query("create table if not exists `$new_table_name` like `$base_table_name`");
						echo "INFO!DB:$masterdbhost DBNAME:$dbname table:$new_table_name is create!\n";
					}
				}
			}
		}
	}
}

main ($argc, $argv);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */