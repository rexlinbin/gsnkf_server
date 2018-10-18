<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendCityWarReward.php 120817 2014-07-16 09:58:37Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/SendCityWarReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-07-16 09:58:37 +0000 (Wed, 16 Jul 2014) $
 * @version $Revision: 120817 $
 * @brief 
 *  
 **/
/*
 * UPDATE pirate40010021.t_city_war SET city_defence = 3000, defence_time = 1405341000, last_gid = 10996, curr_gid = 10996, 
 * occupy_time = 1405341000, 
 * va_reward = UNHEX('0A0B01096C6973740A0B010D31333736353504000B343832323804010B383637353304020D31333031373504000B333034363004000D31313831353004000B343434353304000B323833363404020B393731333904000D31323630343804000B333931383604000B393738343604000D31303036393604000D31343033313804000B323135343404000D31333631373904000B363731393304000D31303238373904000D31343030373404000B373837373004000D31323131333704000B353730373804000B343737333604000D31323835383204000B383431313504000D31303332343304000D31323739303204000B353537313004000101') 
 * WHERE city_id = '13'
 * va_reward里面是所有的军团成员和职位，脚本是给这些成员直接发奖励到奖励中心
 */
class SendCityWarReward extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$rewardTime = strtotime('2014-07-16 12:00:00');
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$cityId = 13;
		$uidType = array(
				137655=>0,48228=>1,86753=>2,130175=>0,30460=>0,118150=>0,
				44453=>0,28364=>2,97139=>0,126048=>0,39186=>0,97846=>0,
				100696=>0,140318=>0,21544=>0,136179=>0,67193=>0,102879=>0,
				140074=>0,78770=>0,121137=>0,57078=>0,47736=>0,128582=>0,
				84115=>0,103243=>0,127902=>0,55710=>0);
		
		foreach ($uidType as $uid => $type)
		{
			if (self::isFix($uid, $rewardTime))
			{
				Logger::info('uid:%d already fix, ignore', $uid);
				continue;
			}
			$ret = array();
			$reward = btstore_get()->CITY_WAR[$cityId][CityWarDef::CITY_REWARD];
			$param = btstore_get()->CITY_WAR_ATTACK[CityWarDef::REWARD_PARAM][$type];
			foreach ($reward as $key => $value)
			{
				$ret[$key] = array($value[0], $value[1], intval($value[2] * $param / 100));
			}
			if ($fix) 
			{
				RewardUtil::reward3DtoCenter($uid, array($ret), RewardSource::SYSTEM_GENERAL);
			}
		}
		
		if ($fix) 
		{
			$arrField = array(
					CityWarDef::DEFENCE_TIME => 1405341000,
					CityWarDef::LAST_GID => 10996,
					CityWarDef::CURR_GID => 10996,
					CityWarDef::OCCUPY_TIME => 1405341000,
					CityWarDef::VA_REWARD => array('list' => $uidType),
			);
			CityWarDao::updateCity($cityId, $arrField);
		}
		echo "done\n";
	}
	
	public static function isfix($uid, $rewardTime)
	{
		$data = new CData();
		$ret = $data->select(array(RewardDef::SQL_RID))
					->from( RewardDef::SQL_TABLE )
					->where(RewardDef::SQL_UID , '=', $uid)
					->where(RewardDef::SQL_SEND_TIME, '>', $rewardTime)
					->where(RewardDef::SQL_SOURCE , '=', RewardSource::SYSTEM_GENERAL)
					->query();
		if (empty($ret))
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */