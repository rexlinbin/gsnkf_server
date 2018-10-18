<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Mysql.class.php 100294 2014-04-15 07:44:47Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/lib/Mysql.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-04-15 15:44:47 +0800 (星期二, 15 四月 2014) $
 * @version $Revision: 100294 $
 * @brief
 *
 **/

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
				 $this->m_password, 'pirate'.$this->m_db);
			if ( $this->m_mysql == FALSE )
			{
				throw new Exception("connect mysql failed!db:" . $this->m_db . " server:" . $this->m_server);
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
			throw new Exception("mysql error!");
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
				//deal va
				foreach ( $row as $key => $value )
				{
					if ( strpos($key, "va_") === 0 )
					{
						$row[$key] = Util::amfDecode($value);
					}
				}
				$return[] = $row;
			}
			return $return;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */