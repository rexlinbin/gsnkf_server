<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: StatisticsDAO.class.php 159164 2015-02-16 07:42:12Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/statistics/StatisticsDAO.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2015-02-16 07:42:12 +0000 (Mon, 16 Feb 2015) $
 * @version $Revision: 159164 $
 * @brief
 *
 **/

class StatisticsDAO
{
	public static function insertOnline($values)
	{
		try {
			$data = new CData();
			$data->setServiceName(StatisticsDef::ST_STATISTICS_SERVICE_NAME);
			$data->useDb(StatisticsConfig::DB_NAME);
			$return = $data->insertInto(StatisticsDef::ST_TABLE_ONLINE_TIME)
				->values($values)->query(TRUE);
			if ( $return[DataDef::AFFECTED_ROWS] != 1 )
			{
				Logger::WARNING('insert on line statistics table failed!affect rows:%d',
					$return[DataDef::AFFECTED_ROWS]);
			}
		}
		catch(Exception $e)
		{
			Logger::WARNING('exception in statistics!:%s', $e->getMessage());
		}
	}
	
	public static function insertDeviceOnline($arrValue)
	{
		try
		{
			$data = new CData();
			$data->setServiceName(StatisticsDef::ST_STATISTICS_SERVICE_NAME);
			$data->useDb(StatisticsConfig::DB_NAME);
			$return = $data->insertOrUpdate('pirate_bind_online_stat')
				->values($arrValue)
				->query(TRUE);
			if ( $return[DataDef::AFFECTED_ROWS] != 1 )
			{
				Logger::WARNING('insert on line statistics table failed!affect rows:%d',
				$return[DataDef::AFFECTED_ROWS]);
			}
		}
		catch(Exception $e)
		{
			Logger::WARNING('exception in statistics!:%s', $e->getMessage());
		}
	}

	public static function insertGold($values)
	{
		try {
			$data = new CData();
			$data->setServiceName(StatisticsDef::ST_STATISTICS_SERVICE_NAME);
			$data->useDb(StatisticsConfig::DB_NAME);
			$return = $data->insertInto(StatisticsDef::ST_TABLE_GOLD)
				->values($values)->query(TRUE);
			if ( $return[DataDef::AFFECTED_ROWS] != 1 )
			{
				Logger::WARNING('insert gold statistics table failed!affect rows:%d',
					$return[DataDef::AFFECTED_ROWS]);
			}
		}
		catch(Exception $e)
		{
			Logger::WARNING('exception in statistics!:%s', $e->getMessage());
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */