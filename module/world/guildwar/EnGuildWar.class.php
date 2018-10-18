<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnGuildWar.class.php 158737 2015-02-12 10:52:05Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/EnGuildWar.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-02-12 10:52:05 +0000 (Thu, 12 Feb 2015) $
 * @version $Revision: 158737 $
 * @brief 
 *  
 **/
 
class EnGuildWar
{
	/**
	 * 读取跨服军团战活动配置
	 * 
	 * @param array $arrData
	 * @return array
	 */
	public static function readGuildWarCsv($arrData)
	{
		$incre = 0;
		$tag = array
		(
				GuildWarCsvTag::ID => $incre++,
				GuildWarCsvTag::NEED_LEVEL => $incre++,
				GuildWarCsvTag::NEED_MEMBER_COUNT => $incre++,
				GuildWarCsvTag::FAIL_NUM => $incre++,
				GuildWarCsvTag::TIME_CONFIG => $incre++,
				GuildWarCsvTag::AUDITION_GAP => $incre++,
				GuildWarCsvTag::FINALS_GAP => $incre++,
				GuildWarCsvTag::CD => $incre++,
				GuildWarCsvTag::CANDIDATES_COUNT => $incre++,
				GuildWarCsvTag::CANDIDATES_PRIZE => $incre++,
				GuildWarCsvTag::NOT_CANDIDATES_PRIZE => $incre++,
				GuildWarCsvTag::CHEER_BASE_COST => $incre++,
				GuildWarCsvTag::CHEER_PRIZE => $incre++,
				GuildWarCsvTag::ALL_SERVER_PRIZE => $incre++,
				GuildWarCsvTag::WORSHIP_PRIZE => $incre++,
				GuildWarCsvTag::LAST_ID => $incre++,
				GuildWarCsvTag::CLEAR_CD_BASE_COST => $incre++,
				GuildWarCsvTag::DEFAULT_WIN_TIME => $incre++,
				GuildWarCsvTag::BUY_WIN_TIME_COST => $incre++,
				GuildWarCsvTag::CHEER_LIMIT => $incre++,
				GuildWarCsvTag::ALL_TEAM => $incre++,
				GuildWarCsvTag::WORSHIP_COST => $incre++,
				GuildWarCsvTag::SESSION => $incre++,
		);
		
		$roundTag = array
		(
				1 => GuildWarRound::SIGNUP,
				2 => GuildWarRound::AUDITION,
				3 => GuildWarRound::ADVANCED_16,
				4 => GuildWarRound::ADVANCED_8,
				5 => GuildWarRound::ADVANCED_4,
				6 => GuildWarRound::ADVANCED_2,
		);
		
		$confList = array();
		foreach ($arrData as $data)
		{
			$conf = array();
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			// id
			$id = intval($data[$tag[GuildWarCsvTag::ID]]);
			$conf[GuildWarCsvTag::ID] = $id;
			
			// 届数
			if (isset($data[$tag[GuildWarCsvTag::SESSION]])) 
			{
				$session = intval($data[$tag[GuildWarCsvTag::SESSION]]);
				$conf[GuildWarCsvTag::SESSION] = $session;
			}
			
			// 需要的军团等级
			$needLevel = intval($data[$tag[GuildWarCsvTag::NEED_LEVEL]]);
			$conf[GuildWarCsvTag::NEED_LEVEL] = $needLevel;
			
			// 需要的成员数量
			$needMemberCount = intval($data[$tag[GuildWarCsvTag::NEED_MEMBER_COUNT]]);
			$conf[GuildWarCsvTag::NEED_MEMBER_COUNT] = $needMemberCount;
			
			// 海选赛最多可以失败的次数
			$failNum = intval($data[$tag[GuildWarCsvTag::FAIL_NUM]]);
			$conf[GuildWarCsvTag::FAIL_NUM] = $failNum;
			
			// 每轮的时间配置
			$arrTimeConfig = explode(',', $data[$tag[GuildWarCsvTag::TIME_CONFIG]]);
			$index = 0;
			for ($i = 0; $i < count($arrTimeConfig); ++$i)
			{
				$datail = array_map('intval', explode('|', $arrTimeConfig[$i]));
				$round = intval($i / 2 + 1);
				$conf[GuildWarCsvTag::TIME_CONFIG][$roundTag[$round]][] = ($datail[0] * 86400 + $datail[1]);
			}
			
			// 海选赛轮与轮之间的间隔时间
			$auditionGap = intval($data[$tag[GuildWarCsvTag::AUDITION_GAP]]);
			$conf[GuildWarCsvTag::AUDITION_GAP] = $auditionGap;
			
			// 晋级赛组与组之间的间隔时间
			$finalsGap = intval($data[$tag[GuildWarCsvTag::FINALS_GAP]]);
			$conf[GuildWarCsvTag::FINALS_GAP] = $finalsGap;
			
			// cd相关
			$arrCdConfig = explode(',', $data[$tag[GuildWarCsvTag::CD]]);
			foreach ($arrCdConfig as $key => $aConfig)
			{
				$detail = array_map('intval', explode('|', $aConfig));
				if (0 == $key) 
				{
					$conf[GuildWarCsvTag::CD][GuildWarCsvTag::AUDITION_UPD_CD] = $detail[0];
					$conf[GuildWarCsvTag::CD][GuildWarCsvTag::AUDITION_UPD_LIMIT] = $detail[1];
				}
				else if (1 == $key) 
				{
					$conf[GuildWarCsvTag::CD][GuildWarCsvTag::FINALS_UPD_CD] = $detail[0];
					$conf[GuildWarCsvTag::CD][GuildWarCsvTag::FINALS_UPD_LIMIT] = $detail[1];
				}
				else if (2 == $key) 
				{
					$conf[GuildWarCsvTag::CD][GuildWarCsvTag::FINALS_TEAM_UPD_CD] = $detail[0];
					$conf[GuildWarCsvTag::CD][GuildWarCsvTag::FINALS_TEAM_UPD_LIMIT] = $detail[1];
				}
			}
			
			// 每个军团出战的成员人数
			$candidatesCount = intval($data[$tag[GuildWarCsvTag::CANDIDATES_COUNT]]);
			$conf[GuildWarCsvTag::CANDIDATES_COUNT] = $candidatesCount;
			
			// 军团名次对应奖励【上场者获得】
			$candidatesPrize = array_map('intval', explode(',', $data[$tag[GuildWarCsvTag::CANDIDATES_PRIZE]]));
			$conf[GuildWarCsvTag::CANDIDATES_PRIZE] = $candidatesPrize;
			
			// 军团名次对应奖励【未上场者获得】
			$notCandidatesPrize = array_map('intval', explode(',', $data[$tag[GuildWarCsvTag::NOT_CANDIDATES_PRIZE]]));
			$conf[GuildWarCsvTag::NOT_CANDIDATES_PRIZE] = $notCandidatesPrize;
			
			// 助威花费银币基础值,花费的银币=花费银币基础值*助威者等级
			$cheerBaseCost = intval($data[$tag[GuildWarCsvTag::CHEER_BASE_COST]]);
			$conf[GuildWarCsvTag::CHEER_BASE_COST] = $cheerBaseCost;
			
			// 助威奖励
			$cheerPrize = intval($data[$tag[GuildWarCsvTag::CHEER_PRIZE]]);
			$conf[GuildWarCsvTag::CHEER_PRIZE] = $cheerPrize;
			
			// 全服礼包
			$allServerPrize = intval($data[$tag[GuildWarCsvTag::ALL_SERVER_PRIZE]]);
			$conf[GuildWarCsvTag::ALL_SERVER_PRIZE] = $allServerPrize;
			
			// 每日膜拜奖励
			$worshipPrize = array_map('intval', explode(',', $data[$tag[GuildWarCsvTag::WORSHIP_PRIZE]]));
			$conf[GuildWarCsvTag::WORSHIP_PRIZE] = $worshipPrize;
			
			// 上一届id
			$lastId = intval($data[$tag[GuildWarCsvTag::LAST_ID]]);
			$conf[GuildWarCsvTag::LAST_ID] = $lastId;
			
			// 清除更新战斗力基本花费
			$clearCdBaseCost = intval($data[$tag[GuildWarCsvTag::CLEAR_CD_BASE_COST]]);
			$conf[GuildWarCsvTag::CLEAR_CD_BASE_COST] = $clearCdBaseCost;
			
			// 默认连胜次数 
			$defaultWinTime = intval($data[$tag[GuildWarCsvTag::DEFAULT_WIN_TIME]]);
			$conf[GuildWarCsvTag::DEFAULT_WIN_TIME] = $defaultWinTime;
			
			// 连战花费金币增加连胜次数
			$arrBuyWinTimeCost = array_map('intval', explode(',', $data[$tag[GuildWarCsvTag::BUY_WIN_TIME_COST]]));
			$conf[GuildWarCsvTag::BUY_WIN_TIME_COST] = $arrBuyWinTimeCost;
			
			// 晋级赛每轮开打前几秒不可助威
			$cheerLimit = intval($data[$tag[GuildWarCsvTag::CHEER_LIMIT]]);
			$conf[GuildWarCsvTag::CHEER_LIMIT] = $cheerLimit;
			
			// 膜拜花费
			$conf[GuildWarCsvTag::WORSHIP_COST] = array_map('intval', explode(',', $data[$tag[GuildWarCsvTag::WORSHIP_COST]]));
			
			$confList[$id] = $conf;
		}
		
		return $confList;
	}
	
	/**
	 * 判断一个军团是否在跨服军团战期间
	 * 
	 * @param int $guildId
	 * @param int $serverId
	 */
	public static function duringGuildWar($guildId, $serverId = 0)
	{
		// 如果为0，默认为本服
		if ($serverId == 0) 
		{
			$serverId = GuildWarUtil::getMinServerId();
		}
				
		// 是否在一届跨服军团赛
		$confObj = GuildWarConfObj::getInstance();
		$session = $confObj->getSession();
		if (empty($session))
		{
			return FALSE;
		}
		
		// 获得teamId
		$teamId = GuildWarUtil::getTeamIdByServerId($session, $serverId);
		
		// 从memcache中获取军团报名的时间
		$signUpTime = GuildWarUtil::getSignUpTimeInMem($guildId, $serverId);
		
		// 如果没有设置signUpTime，就重新设置下，目前memchche有效时间是1天
		if (empty($signUpTime)) 
		{
			if (GuildWarServerObj::isGuildSignUp($session, $serverId, $guildId)) 
			{
				$guildWarServerObj = GuildWarServerObj::getInstance($session, $serverId, $guildId);
				GuildWarUtil::setSignUpTimeInMem($guildId, $serverId, $guildWarServerObj->getSignUpTime());
				$signUpTime = $guildWarServerObj->getSignUpTime();
			}
			else 
			{
				GuildWarUtil::setSignUpTimeInMem($guildId, $serverId, -1);
				return FALSE;
			}
		}
		
		// 报名时间只有大于当前届的报名时间，才算是这届报名啦
		if ($signUpTime >= $confObj->getSignUpStartTime()) 
		{
			// 获得进度表
			$procedureObj = GuildWarProcedureObj::getInstance($session);
			$teamObj = $procedureObj->getTeamObj($teamId);
			
			// 当前大轮次和大轮次状态
			$curRound = $teamObj->getCurRound();
			$curStatus = $teamObj->getCurStatus();
			
			// 没到决赛 或者 没打完 或者 没有到达决赛结束时间，都认为在跨服军团战中。
			if ($curRound < GuildWarRound::ADVANCED_2 
				|| $curStatus < GuildWarStatus::DONE
				|| Util::getTime() < $confObj->getRoundEndTime(GuildWarRound::ADVANCED_2)) 
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
}


/*require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/GuildWar.def.php";

$csvFile = './script/kuafu_legionchallenge.csv';
$file = fopen($csvFile, 'r');
if (FALSE == $file)
{
	echo $argv[1] . "{$csvFile} open failed! exit!\n";
	exit;
}

$arrCsv = array();
fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
		break;
	$arrCsv[] = $data;
}

$ret = EnGuildWar::readGuildWarCsv($arrCsv);
var_dump($ret);*/


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */