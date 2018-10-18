<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: partitionTable.class.php 65620 2013-09-22 08:41:21Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/lib/partitionTable.class.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2013-09-22 16:41:21 +0800 (星期日, 22 九月 2013) $
 * @version $Revision: 65620 $
 * @brief
 *
 **/

class partitionTable
{
	public static $XMLPATH = "/home/pirate/rpcfw/data/dataproxy.xml";

	public static $XMLS = NULL;

	public static $PARTITION = array();

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

	private static function getPartition($table)
	{
		if ( !isset(self::$PARTITION[$table]) )
		{
			$xmls = self::loadXML();
			foreach ( $xmls->table as $key => $value )
			{
				if ( $value->name == $table )
				{
					if ( !isset($value->partition) )
					{
						if ( !isset(self::$PARTITION[$table]) )
						{
							self::$PARTITION[$table] = array();
						}
					}
					else
					{
						if ( !isset(self::$PARTITION[$table]) )
						{
							self::$PARTITION[$table] = array(
								'key' => $value->partition->key,
								'method' => $value->partition->method,
								'value' => $value->partition->value,
							);
						}
					}
				}
			}
			if ( !isset(self::$PARTITION[$table]) )
			{
				self::$PARTITION[$table] = array();
			}
		}
		return self::$PARTITION[$table];
	}

	public static function getTableName($table, $key, $value)
	{
		$table_name = '';
		$partition = self::getPartition($table);
		if ( empty($partition) )
		{
			$table_name = $table;
		}
		else
		{
			if ( $key != $partition['key'] )
			{
				throw new Exception("invalid key for $table!");
			}

			switch ( $partition['method'] )
			{
				case 'div':
					$table_name = $table . "_" . intval($value / $partition['value']);
					break;
				case 'mod':
					$table_name = $table . "_" . intval($value % $partition['value']);
					break;
				default:
					break;
			}
		}
		return $table_name;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */