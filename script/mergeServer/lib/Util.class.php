<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Util.class.php 65620 2013-09-22 08:41:21Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/lib/Util.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2013-09-22 16:41:21 +0800 (星期日, 22 九月 2013) $
 * @version $Revision: 65620 $
 * @brief
 *
 **/

class Util
{
	public static function amfDecode($data, $uncompress = false, $flag = 7)
	{
		if ( empty($data) )
		{
			return array();
		}

		if ($uncompress)
		{
			$data = gzuncompress ( $data );
			if (false === $data)
			{
				Logger::fatal ( "uncompress data failed" );
				throw "inter";
			}
		}

		if ($flag & 1)
		{
			if ($data [0] != chr(0x11))
			{
				$data = chr(0x11) . $data;
			}
		}
		return amf_decode ( $data, $flag );
	}

	public static function amfEncode($data, &$compressed = false, $threshold = false,
			$flag = 3)
	{

		$data = amf_encode ( $data, $flag );
		if (false === $data)
		{
			Logger::fatal ( "amf_encode failed" );
			throw "inter";
		}
		if ($flag & 1)
		{
			if ($data [0] == chr(0x11))
			{
				$data = substr ( $data, 1 );
			}
		}
		if ($compressed || ($threshold > 0 && strlen ( $data ) > $threshold))
		{
			$data = gzcompress ( $data );
			$compressed = true;
		}
		return $data;
	}

	public static function getSuffixName($game_id)
	{
		if ( strpos($game_id, '_') === FALSE )
		{
			return '.s' . self::getServerId($game_id);
		}
		else
		{
			return '';
		}
	}

	public static function getServerId($game_id)
	{
		$id = intval($game_id);
		//兼容一下昆仑的gameid。昆仑的服id都是6位数
		if ( $id > 40000000 || $id < 100000)
		{
			$server_id = substr($game_id, strlen($game_id) - 4);
		}
		else
		{
			$server_id = substr($game_id, strlen($game_id) - 3);
		}
		
		$suffixNum = intval($server_id);
		
		return $suffixNum;
	}
	

	public static function genInsertSql($tableName, $arrValue)
	{
		$columns = "";
		$columnValues = "";
		foreach ( $arrValue as $key => $value )
		{
			if ( !empty($columns) )
			{
				$columns .= ", ";
			}
			$columns .= "`$key`";
	
			if ( !empty($columnValues) )
			{
				$columnValues .= ", ";
			}
	
			//deal va
			if ( strpos($key, "va_") === 0 )
			{
				$value = Util::AMFEncode($value);
				$columnValues .= "UNHEX(\"" . bin2hex($value) . "\")";
			}
			else
			{
				if ( is_string($value) )
				{
					$columnValues .= "UNHEX(\"" . bin2hex($value) . "\")";
				}
				else
				{
					$columnValues .= "\"$value\"";
				}
			}
		}
	
		$sql = "INSERT IGNORE INTO $tableName ($columns) values($columnValues)";
		return $sql;
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */