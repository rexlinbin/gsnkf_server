<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixCityWar.php 108140 2014-05-14 04:07:35Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixCityWar.php $
 * @author $Author: wuqilin $(tianming@babeltime.com)
 * @date $Date: 2014-05-14 04:07:35 +0000 (Wed, 14 May 2014) $
 * @version $Revision: 108140 $
 * @brief 
 *  
 **/
class FixCityWar extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		//获取所有军团占领的城池信息
		$num = 0;
		$cityList = CityWarDao::getCityList();
		$arrGuildId = Util::arrayExtract($cityList, CityWarDef::LAST_GID);
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME, GuildDef::GUILD_LEVEL));
		foreach($cityList as $cityId => $cityInfo)
		{
			$guildId = $cityInfo[CityWarDef::LAST_GID];
			$guildName = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
			$guildLevel = $arrGuildInfo[$guildId][GuildDef::GUILD_LEVEL];
			$needLevel = btstore_get()->CITY_WAR[$cityId][CityWarDef::GUILD_LEVEL];
			if ($guildLevel < $needLevel)
			{
				$num ++;
				Logger::info('cityId:%d, guildId:%d, guildName:%s, level:%d is not reach need:%d, clear it. va_city_war:%s, va_reward:%s', 
							$cityId, $guildId, $guildName, $guildLevel, $needLevel, 
							$cityInfo[CityWarDef::VA_CITY_WAR ], $cityInfo[CityWarDef::VA_REWARD ]);
				if ($fix) 
				{
					$arrField = array(
						CityWarDef::LAST_GID => 0,
						CityWarDef::CURR_GID => 0,
						CityWarDef::OCCUPY_TIME => 0,
						CityWarDef::SIGNUP_END_TIMER => 0,
						CityWarDef::BATTLE_END_TIMER => 0,
						CityWarDef::VA_CITY_WAR => array(),
						CityWarDef::VA_REWARD => array(),
					);
					CityWarDao::updateCity($cityId, $arrField);
				}
			}
		}
		printf("The acount of city with invalid guild is %d\n", $num);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */