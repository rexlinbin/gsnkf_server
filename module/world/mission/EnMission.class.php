<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnMission.class.php 199500 2015-09-18 02:21:27Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/EnMission.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-18 02:21:27 +0000 (Fri, 18 Sep 2015) $
 * @version $Revision: 199500 $
 * @brief 
 *  
 **/
class EnMission
{
	static function readMissionCSV($arrData)
	{
		$incre = 0;
		$tag = array
		(
				MissionCsvField::ID => $incre++,
				MissionCsvField::SESS => $incre++,
				MissionCsvField::NEEDLV => $incre++,
				MissionCsvField::RANK_REWARDARR => $incre++,
				MissionCsvField::DAY_REWARDARR => $incre++,
				MissionCsvField::DONATE_ITEM_LIMIT => $incre++,
				MissionCsvField::FAME_PERGOLD => ($incre+=2)-1,
				MissionCsvField::DONATE_GOLD_RANGEARR => $incre++,
				MissionCsvField::MISSION_IDARR => $incre++,
				MissionCsvField::MISSION_LASTTIME => $incre++,
				MissionCsvField::MISSION_BACKGROUNDARR => $incre++,
				MissionCsvField::MISSION_SHOWRANKARR => $incre++,
		);
		
		$arrv1 = array(
				MissionCsvField::DONATE_GOLD_RANGEARR,
				MissionCsvField::MISSION_IDARR,
				MissionCsvField::MISSION_LASTTIME,
				MissionCsvField::MISSION_SHOWRANKARR,
		);
		$arrv2 = array(
				MissionCsvField::RANK_REWARDARR,
				MissionCsvField::DAY_REWARDARR,
				MissionCsvField::MISSION_BACKGROUNDARR,
		);
		
		$confList = array();
		foreach ($arrData as $data)
		{
			$conf = array();
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			foreach ($tag as $tagName => $tagIndex)
			{
				if( in_array( $tagName , $arrv2) )
				{
					$conf[$tagName] = explode( ',' , $data[$tagIndex]);
					foreach ( $conf[$tagName] as $index => $val )
					{
						$conf[$tagName][$index] = array_map( 'intval' , explode('|' , $val));
					}
				}
				elseif( in_array( $tagName , $arrv1) )
				{
					$conf[$tagName] = array_map( 'intval' , explode(',' , $data[$tagIndex]));
				}
				else
				{
					$conf[$tagName] = intval( $data[$tagIndex] );
				}
					
			}
			
			$confList = $conf;
			Logger::debug('mission conf is:%s', $confList);
		}
		
		if(ActivityConf::$STRICT_CHECK_CONF
			&& !Util::isInCross()
			&& EnActivity::isOpen(ActivityName::MISSION))
		{
			$acData = EnActivity::getConfByName(ActivityName::MISSION);
			if( !isset( $acData['data']['sess'] ) )
			{
				if($confList[MissionCsvField::SESS] != 1 )
				{
					throw new ConfigException( 'sess should be 1:%s', $confList[MissionCsvField::SESS] );
				}
			}
			else
			{
				if( $confList[MissionCsvField::SESS] > $acData['data']['sess']+1 )
				{
					throw new ConfigException( 'sess :%s,err, last one is:%s', $confList[MissionCsvField::SESS], $acData['data']['sess'] );
				}
			}
		}
		
		
		return $confList;
	}
	 
	
	static function doMission($uid, $type, $data = 1)
	{
		$guid = RPCContext::getInstance()->getUid();
		if( $uid != $guid )
		{
			throw new InterException( 'invalid uid:%s', $uid );
		}
		Logger::debug('mission back info:%s', $type, $data );
		MissionLogic::doMission(false, $uid, $type, $data);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */