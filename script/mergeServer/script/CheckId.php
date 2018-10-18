<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckId.php 260912 2016-09-06 05:50:57Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/mergeServer/script/CheckId.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-09-06 05:50:57 +0000 (Tue, 06 Sep 2016) $
 * @version $Revision: 260912 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( __FILE__ ) ) . '/lib/RPCProxy.php';

class CDBInterface {

	// 私有变量 mysqli 实例
	private static $m_DBInterface = null;

	/**
	 * 关闭数据库连接
	 */
	public static function destroyConnection() {

		self::$m_DBInterface->kill(self::$m_DBInterface->thread_id);
		self::$m_DBInterface->close();
	}

	/**
	 * 执行SQL
	 * @param string $strSql					将要执行的SQL语句
	 * @return 返回 $stmt
	 */
	public static function execute($strSql) {

		// 准备SQL语句
		$stmt = self::$m_DBInterface->prepare($strSql);
		// 错误处理
		if (!$stmt) {
			echo self::$m_DBInterface->error;
			exit();
		}
		// 执行SQL
		$return = $stmt->execute();
		if ( $return == FALSE )
		{
			echo self::$m_DBInterface->error;
			exit();
		}
		// 返回 stmt
		return  $stmt;
	}

	/**
	 * 连接数据库
	 */
	public static function connect($ip, $dbName, $user, $pass) {

		self::$m_DBInterface = new mysqli($ip, $user, $pass);
		if (self::$m_DBInterface->connect_error != null) {
			printf("Could not connect to MySQL: %s\n", self::$m_DBInterface->error);
			exit();
		}
		// 选择需要连接的实例
		self::$m_DBInterface->select_db($dbName);
		// 更改字符集
		self::$m_DBInterface->query("SET NAMES utf8;");
		// 关闭自动提交
		self::$m_DBInterface->autocommit(false);

	}

	public static function getAllTblNames()
	{
		$arr = array();

		$result = self::$m_DBInterface->query("show tables;");

		$row = null;
		while (1) {
			$row = $result->fetch_array(3);
			if ($row != null)
				$arr[] = $row[0];
			else
				break;
		}

		return $arr;
	}
} // CDBInterface

function getIdTableMap($idXmlPath, $dataXmlPath)
{
	$arrIdNoTable = array(
			//abyss_id
	);
	$arrIdIgnore = array(
			'worldarena_pos_id',
			'uuid',
	);
	if ( !file_exists($idXmlPath)  )
	{
		throw new Exception("$idXmlPath not exist");
	}
	if ( !file_exists($dataXmlPath)  )
	{
		throw new Exception("$dataXmlPath not exist");
	}
	$idXmlRoot = simplexml_load_file($idXmlPath);
	$dataXmlRoot = simplexml_load_file($dataXmlPath);

	$idTableMap = array();
	foreach ( $idXmlRoot->id as $node )
	{
		if( empty($node->name) )
		{
			throw new Exception('invalid id.xml. no name');
		}
		$idName = strval($node->name);
		if ( in_array( $idName, $arrIdIgnore ) )
		{
			printf("ignore id:%s\n", $idName );
			continue;
		}
		if( !in_array($node->name, $arrIdNoTable) && empty($node->table) )
		{
			throw new Exception('invalid id.xml. no table');
		}

		$idTableMap[$idName] = empty($node->table) ? 'notable' : strval($node->table);
	}

	$idTableMapNoPart = $idTableMap;
	$idTableMapPart = array();
	foreach( $dataXmlRoot->table as $node )
	{
		if( empty($node->name) )
		{
			throw new Exception('invalid data.xml. no name');
		}
		$tblName = strval($node->name);

		$idName = array_search($tblName, $idTableMap);
		if( !empty($idName) )
		{
			if( !empty($node->partition) )
			{
				$idTableMapPart[$idName] = $tblName;
				unset( $idTableMapNoPart[$idName] );
			}
		}
	}

	return array(
			'idTableMapNoPart' => $idTableMapNoPart,
			'idTableMapPart' => $idTableMapPart,
	);
}

function getMaxIDNoPartition($idName, $tblName)
{
	$id = 0;

	if( empty($tblName) )
	{
		throw new Exception('invalid tableName');
	}

	if( $tblName == 'notable' )
	{
		return $id;
	}

	$sql = "select max(".$idName.") from ".$tblName.";";

	$stmt = CDBInterface::execute($sql);
	$stmt->bind_result($id);
	$stmt->fetch();
	$stmt->close();
	
	return $id;

	// 最大的下一个id
	//return intval(($id + 99) / 100) * 100 + 100;
}

function getMaxIDPartition($idName, $tblName, $allTbls)
{
	$max = 0;
	$id = 0;
	$tblName = $tblName . '_';
	$strLength = strlen($tblName);
	foreach ($allTbls as $tbl)
	{
		// 如果前缀相等, 并且后面的是数字
		if (substr($tbl, 0, $strLength) == $tblName && is_numeric(substr($tbl, $strLength)) )
		{
			$sql = "select max(".$idName.") from ".$tbl.";";
			$stmt = CDBInterface::execute($sql);
			$stmt->bind_result($id);
			$stmt->fetch();
			$stmt->close();

			if ($id > $max)
			{
				$max = $id;
			}
		}
	}
	
	return $max;

	// 最大的下一个id
	//return intval(($max + 99) / 100) * 100 + 100;
}

function getCurrIdFromDataproxy($idName, $db, $slaveIp)
{
	$proxy = new RPCProxy($slaveIp, 3300);
	$proxy->setClass('id');
	return $proxy->show($idName, $db);
}

$help = "param: master_ip slave_ip dbname user pass id.xml table.xml\n";
if ($argc != 8)
{
	exit($help);
}

$masterIp = $argv[1];
$slaveIp = $argv[2];
$db = $argv[3];
$user = $argv[4];
$pass = $argv[5];
$idXML = $argv[6];
$tableXML = $argv[7];

CDBInterface::connect($masterIp, $db, $user, $pass);

$arrRet = getIdTableMap($idXML, $tableXML);
$idTableMapNoPart = $arrRet['idTableMapNoPart'];
$idTableMapPart = $arrRet['idTableMapPart'];

$arrId = array();

foreach ($idTableMapNoPart as $idName => $tblName)
{
	$arrId[$idName] = getMaxIDNoPartition($idName, $tblName);
}

$allTbls = CDBInterface::getAllTblNames();

foreach ($idTableMapPart as $idName => $tblName)
{
	$arrId[$idName] = getMaxIDPartition($idName, $tblName, $allTbls);
}

CDBInterface::destroyConnection();

$error = false;
foreach ($arrId as $idName => $maxId)
{
	$maxIdFromDataproxy = getCurrIdFromDataproxy($idName, $db, $slaveIp);
	printf("id:%s, max id from db:%d, max id from dataproxy:%d\n", $idName, $maxId, $maxIdFromDataproxy);
	if ($maxId >= $maxIdFromDataproxy) 
	{
		$error = true;
	}
}

if ($error) 
{
	exit(1);
}

exit(0);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */