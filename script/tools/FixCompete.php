<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixCompete.php 122121 2014-07-22 09:24:27Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixCompete.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-07-22 09:24:27 +0000 (Tue, 22 Jul 2014) $
 * @version $Revision: 122121 $
 * @brief 
 *  
 **/
/**
 * 此脚本在该服积分没有清的情况下使用。
 * 用当前积分减去上一轮积分，加上1000，就是当前的积分。
 */
class FixCompete extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$time = strtotime('2014-06-14 23:00:00');
		$i = 0;
		$count = CData::MAX_FETCH_SIZE;
		$order = CompeteDef::LAST_POINT;
		$arrfield = array(
				CompeteDef::COMPETE_UID,
				CompeteDef::COMPETE_POINT, 
				CompeteDef::LAST_POINT,
				CompeteDef::REWARD_TIME
		);
		
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$arrRankInfo = self::getRankList($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE, $order, $arrfield);
			$count = count($arrRankInfo);
			++$i;
			if ($count == 0)
			{
				break;
			}
			$arrUid = array_keys($arrRankInfo);
			$uid = current($arrUid);
			while ($uid != false)
			{
				try
				{
					$curPoint = $arrRankInfo[$uid][CompeteDef::COMPETE_POINT];
					$lastPoint = $arrRankInfo[$uid][CompeteDef::LAST_POINT];
					$rewardTime = $arrRankInfo[$uid][CompeteDef::REWARD_TIME];
					Logger::info("uid:%d, last:%d\n, cur:%d, reward time:%s", $uid, $lastPoint, $curPoint, $rewardTime);
					if ($rewardTime <= $time) 
					{
						$uid = next($arrUid);
						continue;
					}
					$point = $curPoint - $lastPoint + 1000;
					$point = $point < 1000 ? 1000 : $point;
					Logger::info('fix point. uid:%d, last:%d, cur:%d, fix:%d', $uid, $lastPoint, $curPoint, $point);
	
					$arrValue = array('point' => $point);
					$ret = CompeteDao::update($uid, $arrValue);
					$uid = next($arrUid);
				}
				catch( Exception $e )
				{
					Logger::fatal('failed:%s', $e->getMessage() );
					$uid = next($arrUid);
				}
			}
		}
	}
	
	public static function getRankList($offset, $limit, $order = CompeteDef::COMPETE_POINT, $arrField = array())
	{
		if (!in_array(CompeteDef::COMPETE_UID, $arrField))
		{
			$arrField[] = CompeteDef::COMPETE_UID;
		}
		$data = new CData();
		$arrRet = $data->select($arrField)
					->from(CompeteDef::COMPETE_TABLE)
					->where(array(CompeteDef::COMPETE_UID, '>', 0))
					->orderBy($order, false)
					->limit($offset, $limit)
					->query();
		return 	Util::arrayIndex($arrRet, CompeteDef::COMPETE_UID);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */