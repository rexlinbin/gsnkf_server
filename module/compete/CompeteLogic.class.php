<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CompeteLogic.class.php 253893 2016-08-01 02:37:05Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/CompeteLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-01 02:37:05 +0000 (Mon, 01 Aug 2016) $
 * @version $Revision: 253893 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : CompeteLogic
 * Description : 比武系统的业务逻辑实现类
 * Inherit     :
 **********************************************************************************************************************/
class CompeteLogic
{
	//战斗模块回调会用到
	private static $day = 0;
	private static $round = 0;
	private static $level = 0;
	private static $reward = array();
	
	/**
	 * 获取用户的比武信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array mixed
	 */
	public static function getCompeteInfo($uid)
	{
		Logger::trace('CompeteLogic::getCompeteInfo Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete system', $uid);
		}
		
		//获取比武轮次
		self::getRound();
		if (empty(self::$round))
		{
			throw new FakeException('today does not have the compete');
		}
		
		//状态：0没有开始，1比武，2休息，3发奖
		$now = Util::getTime();
		$isOpen = self::isOpen();
		$rewardTime = self::getRewardTime();
		//从数据库取用户的数据
		$info = CompeteDao::select($uid);
		
		//如果比武还没结束，就返回对手列表；否则，就返回排行榜前3。
		if ($now < $rewardTime[0])
		{
			//发奖前
			if (empty($info))
			{
				$info = self::init($uid);
			}
			$state = ($isOpen ? 1 : 0);
			$rankList = array();
			$point = $info[CompeteDef::COMPETE_POINT];
			$time = $info[CompeteDef::POINT_TIME];
			$rank = self::getRank($uid, $point, $time);
			$arrRival = $info[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST];
			if (empty($arrRival))
			{
				$arrRival = self::getRivalList($uid, $point);
				$arrField = array(CompeteDef::VA_COMPETE => $info[CompeteDef::VA_COMPETE]);
				$arrField[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST] = $arrRival;
				CompeteDao::update($uid, $arrField);
			}
			$rivalList = self::getRivalInfo($arrRival);
		}
		elseif ($now >= $rewardTime[0] && $now <= $rewardTime[1])
		{
			//发奖中
			if (empty($info))
			{
				//没有初始化用户数据到数据库
				$info = self::initCompete($uid);
			}
			$state = 3;
			$rivalList = array();
			$point = $info[CompeteDef::COMPETE_POINT];
			$time = $info[CompeteDef::POINT_TIME];
			$rank = self::getRank($uid, $point, $time, true);
			$rankList = self::getRankListByRound(CompeteConf::COMPETE_TOP_THREE, true);
		}
		else
		{
			//发奖后
			if (empty($info))
			{
				$info = self::init($uid);
			}
			$state = 2;
			$rivalList = array();
			$point = $info[CompeteDef::LAST_POINT];
			$time = $info[CompeteDef::POINT_TIME];
			$rank = self::getRank($uid, $point, $time, false);
			$rankList = self::getRankListByRound(CompeteConf::COMPETE_TOP_THREE, false);
		}
		
		$num = Util::isSameDay($info[CompeteDef::COMPETE_TIME]) ? $info[CompeteDef::COMPETE_NUM] : 0;
		$buy = Util::isSameDay($info[CompeteDef::COMPETE_TIME]) ? $info[CompeteDef::COMPETE_BUY] : 0;
		$honor = $info[CompeteDef::COMPETE_HONOR];
		$refresh = $info[CompeteDef::REFRESH_TIME];
		$arrFoe = $info[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST];
		//上限99的原因是sql里面in和limit导致的100的临界值
		while (count($arrFoe) > CData::MAX_FETCH_SIZE - 1) 
		{
			array_shift($arrFoe);
		}
		$foeList = self::getRivalInfo($arrFoe, 3);
		
		Logger::trace('CompeteLogic::getCompeteInfo End.');
		
		return array(
				'round' => self::$round,
				'state' => $state,
				'num' => $num,
				'buy' => $buy,
				'honor' => $honor,
				'point' => $point,
				'rank' => $rank,
				'refresh' => $refresh,
				'rivalList' => $rivalList,
				'rankList' => $rankList,
				'foeList' => $foeList,
		);
	}
	
	/**
	 * 刷新用户的对手列表
	 * 
	 * @param int $uid 用户uid
	 * @throws FakeException
	 * @return array mixed
	 */
	public static function refreshRivalList($uid)
	{
		Logger::trace('CompeteLogic::refreshRivalList Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete system', $uid);
		}
		
		//获取比武轮次
		self::getRound();
		if (empty(self::$round))
		{
			throw new FakeException('today does not have the compete');
		}
		
		//判断比武活动是否开启
		if (self::isOpen() == false)
		{
			throw new FakeException('the compete is not open yet');
		}
		
		//判断是否在比武时间
		if (self::isContestTime() == false)
		{
			throw new FakeException('the compete is in the rest time');
		}
		
		//从数据库取用户的数据
		$info = CompeteDao::select($uid);
		$refresh = $info[CompeteDef::REFRESH_TIME];
		
		//判断冷却时间是否结束
		$now = Util::getTime();
		if ($now < $refresh) 
		{
			throw new FakeException('the compete is in the refresh CD time');
		}
		  
		//刷新对手列表并更新到数据库
		$point = $info[CompeteDef::COMPETE_POINT];
		$arrUid = self::getRivalList($uid, $point);
		$conf = btstore_get()->COMPETE[self::$round];
		$arrField = array(
				CompeteDef::REFRESH_TIME => $now + $conf[CompeteDef::COMPETE_REFRESH_TIME],
				CompeteDef::VA_COMPETE => $info[CompeteDef::VA_COMPETE]
		);
		$arrField[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST] = $arrUid;
		CompeteDao::update($uid, $arrField);
		
		Logger::trace('refresh cd time is:%d', $arrField[CompeteDef::REFRESH_TIME]);
		Logger::trace('CompeteLogic::refreshRivalList End.');
		
		return self::getRivalInfo($arrUid);
	}
	
	/**
	 * 比武或复仇
	 * 
	 * @param int $uid	比武id
	 * @param int $atkedUid 被攻击用户id
	 * @param int $type	攻击类型
	 * @return array mixed
	 */
	public static function contest($uid, $atkedUid, $type)
	{
		Logger::trace('CompeteLogic::contest Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete system', $uid);
		}
		
		//获取比武轮次
		self::getRound();
		if (empty(self::$round))
		{
			throw new FakeException('today does not have the compete');
		}
		
		//判断比武活动是否开启
		if (self::isOpen() == false)
		{
			throw new FakeException('the compete is not open yet');
		}
		
		//判断是否在比武时间
		if (self::isContestTime() == false)
		{
			throw new FakeException('the compete is in the rest time');
		}
		
		//从数据库取用户的数据
		$info = CompeteDao::select($uid);
		if (CompeteDef::COMPETE_TYPE_RIVAL == $type) 
		{
			$arrUid = $info[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST];
			//被攻击方是否在用户的对手列表里
			if (!in_array($atkedUid, $arrUid)) 
			{
				throw new FakeException('the atked uid:%d is not in user:%d rival list:%s', $atkedUid, $uid, $arrUid);
			}
		}
		if (CompeteDef::COMPETE_TYPE_FOE == $type)
		{
			$arrUid = $info[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST];
			//被攻击方是否在用户的仇人列表里
			if (!in_array($atkedUid, $arrUid))
			{
				throw new FakeException('the atked uid:%d is not in user:%d foe list:%s', $atkedUid, $uid, $arrUid);
			}
		}
		
		//用户耐力是否足够
		$conf = btstore_get()->COMPETE[self::$round];
		$needStamina = $conf[CompeteDef::COMPETE_COST_STAMINA];
		$user = EnUser::getUserObj($uid);
		if ($user->subStamina($needStamina) == false) 
		{
			throw new FakeException('fail to sub stamina, not enough for %d', $needStamina);
		}
		
		//检查用户比武次数是否用完
		if (!Util::isSameDay($info[CompeteDef::COMPETE_TIME])) 
		{
			$info[CompeteDef::COMPETE_NUM] = 0;
			$info[CompeteDef::COMPETE_BUY] = 0;
		}
		$baseNum = btstore_get()->VIP[$user->getVip()]['competeNum'][0];
		$maxNum = $baseNum + $info[CompeteDef::COMPETE_BUY];
		if ($info[CompeteDef::COMPETE_NUM] >= $maxNum) 
		{
			throw new FakeException('no enough num:%d to contest, max num:', $info[CompeteDef::COMPETE_NUM], $maxNum);
		}
		//用户需要更新的数据部分, 比武次数加1, 比武时间更新
		$arrField = array(
				CompeteDef::COMPETE_NUM => $info[CompeteDef::COMPETE_NUM] + 1,
				CompeteDef::COMPETE_BUY => $info[CompeteDef::COMPETE_BUY],
				CompeteDef::COMPETE_TIME => Util::getTime(),
		);
		
		//初始化用户数据和战斗奖励信息
		self::$level = $user->getLevel();
		self::$reward = array();
			
		// 获取被攻击用户对象
		$atkedUser = EnUser::getUserObj($atkedUid);
		// 准备用户和被攻击方的战斗信息,战斗
		$battleUser = $user->getBattleFormation();
		$atkedBattleUser = $atkedUser->getBattleFormation();
		$userFF = $user->getFightForce();
		$atkedUserFF = $atkedUser->getFightForce();
		$atkType = EnBattle::setFirstAtk(0, $userFF >= $atkedUserFF);
		$atkRet = EnBattle::doHero($battleUser, $atkedBattleUser, $atkType, array('CompeteLogic', 'battleCallback'));
		$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		$arrRet = array(
				'atk' => array(
						'fightRet' => $atkRet['client'],
						'appraisal' => $atkRet['server']['appraisal'],
				),
		);
		
		//如果攻击方胜利
		if ($isSuc == true) 
		{
			//无论比武还是复仇，需要翻牌
			$silver = 0;
			$flopInfo = EnFlop::flop($uid, $atkedUid, $conf[CompeteDef::COMPETE_SUC_FLOP]);
			$arrRet['flop'] = $flopInfo['client'];
			$silver = $flopInfo['server'];
			
			//无论比武还是复仇，胜利方加积分，失败方减积分。需要获取被攻击方的积分。
			$atkedInfo = CompeteDao::select($atkedUid);
			$sucPoint = $conf[CompeteDef::COMPETE_SUC_POINT] 
			+ intval(min($conf[CompeteDef::COMPETE_MAX_POINT], $atkedInfo[CompeteDef::COMPETE_POINT] * $conf[CompeteDef::COMPETE_SUC_RATE] / UNIT_BASE));
			$failPoint = $conf[CompeteDef::COMPETE_FAIL_POINT] 
			+ intval(min($conf[CompeteDef::COMPETE_MAX_POINT], $atkedInfo[CompeteDef::COMPETE_POINT] * $conf[CompeteDef::COMPETE_FAIL_RATE] / UNIT_BASE));
			Logger::trace('suc point:%d, fail point:%d', $sucPoint, $failPoint);
			
			//无论比武还是复仇，胜利方需要更新数据库的部分
			$arrField[CompeteDef::COMPETE_POINT] = $info[CompeteDef::COMPETE_POINT] + $sucPoint;
			$arrField[CompeteDef::POINT_TIME] = Util::getTime();
			$arrField[CompeteDef::COMPETE_HONOR] = $info[competeDef::COMPETE_HONOR] + $conf[CompeteDef::COMPETE_ADD_HONOR];
			//新排名
			$arrRet['suc_point'] = $sucPoint;
			$arrRet['point'] = $arrField[CompeteDef::COMPETE_POINT];
			$arrRet['rank'] = self::getRank($uid, $arrField[CompeteDef::COMPETE_POINT], $arrField[CompeteDef::POINT_TIME]);
			//如果是比武，还需要刷新胜利方的对手列表
			if (CompeteDef::COMPETE_TYPE_RIVAL == $type) 
			{
				$arrUid = self::getRivalList($uid, $arrField[CompeteDef::COMPETE_POINT]);
				$arrField[CompeteDef::VA_COMPETE] = $info[CompeteDef::VA_COMPETE];
				$arrField[CompeteDef::VA_COMPETE][CompeteDef::RIVAL_LIST] = $arrUid;
				$arrRet['rivalList'] = self::getRivalInfo($arrUid);
				//失败方需要更新的数据部分,如果是机器人就不发
				if( ! self::isRobotUid($atkedUid) )
				{
					$arrFieldAtked = array($atkedUid, $uid, $silver, $failPoint, $atkRet['server']['brid']);
					RPCContext::getInstance()->executeTask($atkedUid, 'compete.competeDataRefresh', $arrFieldAtked, false);
				}
			}
			else 
			{
				$arrField[CompeteDef::VA_COMPETE] = $info[CompeteDef::VA_COMPETE];
				$key = array_search($atkedUid, $arrField[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST]);
				unset($arrField[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST][$key]);
			}
		}
		//更新数据库
		CompeteDao::update($uid, $arrField);
		
		//无论输赢都能得到经验奖励
		$user->addExp(self::$reward['exp']);
		$user->update();
		if (isset($arrRet['flop']['real']['item']))
		{
			$bag = BagManager::getInstance()->getBag($uid)->update();
			$itemArr = array($arrRet['flop']['real']['item']['id'] => $arrRet['flop']['real']['item']['num']);
			ChatTemplate::sendFlopItem($user->getTemplateUserInfo(), $itemArr, FlopDef::FLOP_TYPE_COMPETE);
		}
		
		EnWeal::addKaPoints(KaDef::COMPETE);
		EnAchieve::updateCompete($uid, 1);
		EnActive::addTask(ActiveDef::COMPETE);
		EnMission::doMission($uid, MissionType::COMPETE);
		EnDesact::doDesact($uid, DesactDef::COMPETE, 1);
		// 比武攻打次数统计 - $num传的是当前攻打的总次数，这个次数是每天重置的
		EnFestivalAct::notify($uid, FestivalActDef::TASK_COMPETE_NUM, $arrField[CompeteDef::COMPETE_NUM]);
		
		Logger::trace('CompeteLogic::contest End.');
		
		return $arrRet;
	}
	
	/**
	 * 拉取积分排行榜
	 * 
	 * @param int $uid 用户id
	 * @param int $num 数量
	 * @throws FakeException
	 * @return array mixed
	 */
	public static function getRankList($uid, $num)
	{
		Logger::trace('CompeteLogic::getRankList Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete system', $uid);
		}
		
		//获取比武轮次
		self::getRound();
		if (empty(self::$round))
		{
			throw new FakeException('today does not have the compete');
		}
		
		//不同时间段，排名所用积分不一样
		$now = Util::getTime();
		$rewardTime = self::getRewardTime();
		$isCurrRound = ($now <= $rewardTime[1] ? true : false);
		$ret = self::getRankListByRound($num, $isCurrRound);
		
		Logger::trace('CompeteLogic::getRankList End.');
		
		return $ret;
	}
	
	public static function buyCompeteNum($uid, $num)
	{
		Logger::trace('CompeteLogic::buyCompeteNum Start. num:%d', $num);
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			throw new FakeException('user:%d does not open the compete system', $uid);
		}
		
		//获取比武轮次
		self::getRound();
		if (empty(self::$round))
		{
			throw new FakeException('today does not have the compete');
		}
		
		//发奖之后到下一轮开始都不让购买
		$now = Util::getTime();
		$rewardTime = self::getRewardTime();
		if ($now >= $rewardTime[0]) 
		{
			throw new FakeException('now can not buy compete num');
		}
		
		//检查用户金币是否足够
		$user = EnUser::getUserObj($uid);
		list($baseNum, $limitNum, $cost) = btstore_get()->VIP[$user->getVip()]['competeNum'];
		if ($user->subGold($cost * $num, StatisticsDef::ST_FUNCKEY_COMPETE_BUY) == false) 
		{
			throw new FakeException('no enough gold:%d to buy compete num', $cost * $num);
		}
		
		//从数据库取用户的数据
		$info = CompeteDao::select($uid);
		//检查用户够买次数是否用完
		if (!Util::isSameDay($info[CompeteDef::COMPETE_TIME]))
		{
			$info[CompeteDef::COMPETE_NUM] = 0;
			$info[CompeteDef::COMPETE_BUY] = 0;
		}
		if ($info[CompeteDef::COMPETE_BUY] + $num > $limitNum)
		{
			throw new FakeException('bought num:%d, need num:%d, limit num:%d', $info[CompeteDef::COMPETE_BUY], $num, $limitNum);
		}
		
		//用户需要更新的数据部分, 购买次数增加, 比武时间更新
		$arrField = array(
				CompeteDef::COMPETE_NUM => $info[CompeteDef::COMPETE_NUM],
				CompeteDef::COMPETE_BUY => $info[CompeteDef::COMPETE_BUY] + $num,
				CompeteDef::COMPETE_TIME => Util::getTime(),
		);
		CompeteDao::update($uid, $arrField);
		
		//更新用户数据
		$user->update();
		
		Logger::trace('CompeteLogic::buyCompeteNum End.');
		
		return 'ok';
	}
	
	public static function isHonorEnough($uid, $goodsId, $num)
	{
		Logger::trace('CompeteLogic::isHonorEnough Start.');
		
		//读配置
		$goodsConf = btstore_get()->COMPETE_GOODS[$goodsId];
		$costHonor = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		
		//检查用户荣誉值是否足够
		$info = CompeteDao::select($uid);
		if ($info[CompeteDef::COMPETE_HONOR] < $costHonor * $num)
		{
			return false;
		}
		
		Logger::trace('CompeteLogic::isHonorEnough End.');
		
		return true;
	}
	
	public static function subHonor($uid, $goodsId, $num)
	{
		Logger::trace('CompeteLogic::subHonor Start.');
	
		//读配置
		$goodsConf = btstore_get()->COMPETE_GOODS[$goodsId];
		$costHonor = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];
		
		//检查用户荣誉值是否足够
		$info = CompeteDao::select($uid);
		if ($info[CompeteDef::COMPETE_HONOR] < $costHonor * $num)
		{
			throw new FakeException('user:%d honor is not enough for buy %d goodsId:%d', $uid, $num, $goodsId);
		}
		
		//更新用户的荣誉值
		$arrField = array(
				CompeteDef::COMPETE_HONOR=> $info[CompeteDef::COMPETE_HONOR] - $costHonor * $num,
		);
		CompeteDao::update($uid, $arrField);
	
		Logger::trace('CompeteLogic::subHonor End.');
	
		return 'ok';
	}
	
	public static function addHonor($uid, $honor)
	{
		Logger::trace('CompeteLogic::addHonor Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ROB) == false)
		{
			Logger::warning('user:%d does not open the compete system, add honor:%d', $uid, $honor);
			return 'failed';
		}
		
		//检查用户是否进入比武了
		$info = CompeteDao::select($uid);
		if (empty($info)) 
		{
			Logger::warning('user:%d does not enter the compete system', $uid);
			return 'failed';
		}
		
		if ($info[CompeteDef::COMPETE_HONOR] + $honor < 0) 
		{
			return 'failed';
		}
		
		//更新用户的荣誉值
		$arrField = array(
				CompeteDef::COMPETE_HONOR => $info[CompeteDef::COMPETE_HONOR] + $honor,
		);
		CompeteDao::update($uid, $arrField);
	
		Logger::trace('CompeteLogic::addHonor End.');
	
		return 'ok';
	}
	
	/**
	 * 更新用户数据
	 * 
	 * @param int $uid 用户id
	 * @param int $atkUid 攻击者id
	 * @param int $silver 被掠夺银币
	 * @param int $failPoint 失败积分
	 * @param int $replayId 战报id
	 */
	public static function atkUserByOther($uid, $atkUid, $silver, $failPoint, $replayId)
	{
		Logger::trace('CompeteLogic::atkUserByOther Start.');
		
		//如果已经到发奖时间了，就不扣用户积分了
		self::getRound();
		$now = Util::getTime();
		$rewardTime = self::getRewardTime();
		if ($now >= $rewardTime[0] + CompeteConf::OFFSET_TIME)
		{
			return ;
		}
		
		//从数据库取用户的数据
		$info = CompeteDao::select($uid);
		//不够减，就减当前用户积分
		if ($failPoint > $info[CompeteDef::COMPETE_POINT]) 
		{
			$failPoint = $info[CompeteDef::COMPETE_POINT];
		}
		Logger::trace('real fail point:%d', $failPoint);
		//更新用户数据部分
		$arrField = array(
				CompeteDef::COMPETE_POINT => $info[CompeteDef::COMPETE_POINT] - $failPoint,
				CompeteDef::POINT_TIME => $now,
				CompeteDef::VA_COMPETE => $info[CompeteDef::VA_COMPETE],
		);
		//推送消息给前端
		$rank = self::getRank($uid, $arrField[CompeteDef::COMPETE_POINT], $arrField[CompeteDef::POINT_TIME]);
		$refreshData = array(
				'point' => $arrField[CompeteDef::COMPETE_POINT],
				'rank' => $rank,
		);
		//如果仇人用户不在当前用户的仇人列表，则加入列表, 先取userobj, 以便缓存
		$atkUser = EnUser::getUserObj($atkUid);
		if (!in_array($atkUid, $arrField[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST])) 
		{
			$arrField[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST][] = $atkUid;
			while (count($arrField[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST]) > CData::MAX_FETCH_SIZE - 1) 
			{
				array_shift($arrField[CompeteDef::VA_COMPETE][CompeteDef::FOE_LIST]);
			}
			$atkUserInfo = self::getRivalInfo(array($atkUid), 3);
			$refreshData['addFoeInfo'] = $atkUserInfo;
		}
		CompeteDao::update($uid, $arrField);
	
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::COMPETE_REFRESH, $refreshData);
		//给用户发邮件
		MailTemplate::sendCompeteRob($uid, $atkUser->getTemplateUserInfo(), $silver, $failPoint, $replayId);
		
		Logger::trace('CompeteLogic::atkUserByOther End.');
	}
	
	/**
	 * 战斗回调
	 */
	public static function battleCallback($atkRet)
	{
		$isSuc = BattleDef::$APPRAISAL[$atkRet['appraisal']] <= BattleDef::$APPRAISAL['D'];
		self::computeReward($isSuc);
		return self::$reward;
	}
	
	/**
	 * 通知前端:发奖开始。给脚本调用
	 */
	public static function startReward()
	{
		Logger::trace('CompeteLogic::startReward Start.');
		
		//必须在比武的最后一天，且比武结束后发奖
		self::getRound();
		if (empty(self::$round) || !self::isRewardTime())
		{
			Logger::info('today is not the day of generate reward.');
			return ;
		}
		
		RPCContext::getInstance()->sendMsg(array(SPECIAL_UID::BROADCAST), PushInterfaceDef::COMPETE_REWARD, array('start'));
		
		Logger::trace('CompeteLogic::startReward End.');
	}
	
	/**
	 * 发奖励，给脚本调用
	 * 
	 * @param boolean $redo 是否重做
	 */
	public static function generateReward($redo = false)
	{
		Logger::trace('CompeteLogic::generateReward Start.');
		
		//必须在比武的最后一天，且比武结束后发奖
		self::getRound();
		if ((empty(self::$round) || !self::isRewardTime()) && !$redo)
		{
			Logger::info('today is not the day of generate reward.');
			return ;
		}
		$rewardTime = self::getRewardTime();
		
		//开始发奖
		Logger::info('start to generateReward by point');
		$i = 0; 
		$rank = 0;
		$count = CData::MAX_FETCH_SIZE;
		$order = CompeteDef::COMPETE_POINT;
		$arrfield = array(CompeteDef::REWARD_TIME);
		$rewardConf = btstore_get()->COMPETE_REWARD;
		$maxRank = EnAchieve::getMaxRank(AchieveDef::COMPETE_RANK);
		
		//收集所有需要通知的uid，然后一起通知
		$arrNotifyUid = array();
		MailConf::$NO_CALLBACK = true;
		RewardCfg::$NO_CALLBACK = true;
		
		$arrUserRank = array();
		while($count >= CData::MAX_FETCH_SIZE)
		{
			$arrRankInfo = CompeteDao::getRankList($i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE, $order, $arrfield);
			$count = count($arrRankInfo);
			++$i;
			//没有数据直接退出
			if ($count == 0)
			{
				break;
			}
			//拉取用户等级
			$arrUid = array_keys($arrRankInfo);
			$arrUserInfo = EnUser::getArrUser($arrUid, array('level'));
			//连续修改多少个后休眠
			$sleepCount = 0;
			$uid = current($arrUid);
			while ($uid != false)
			{
				++$rank;
				try
				{
					if( $rank > CompeteConf::REWARD_NUM )
					{
						Logger::trace('uid:%d, rank:%d, ignore', $uid, $rank);
						self::init($uid, $arrRankInfo[$uid][$order], Util::getTime());
						$uid = next($arrUid);
						continue;
					}
					if( self::isRobotUid($uid) )
					{
						Logger::trace('uid:%d is NPC, ignore.', $uid);
						$uid = next($arrUid);
						continue;
					}
					if (!isset($arrUserInfo[$uid]))
					{
						Logger::fatal('fail to get user %d', $uid);
						$uid = next($arrUid);
						continue;
					}
					if ($arrRankInfo[$uid][CompeteDef::REWARD_TIME] >= $rewardTime[0])
					{
						Logger::warning('uid:%d has reward, ignore', $uid);
						$uid = next($arrUid);
						continue;
					}
					$conf = array();
					foreach ($rewardConf as $key => $value)
					{
						if ($rank >= $value[CompeteDef::COMPETE_REWARD_MIN] 
						&& $rank <= $value[CompeteDef::COMPETE_REWARD_MAX])
						{
							$conf = $value;
							break;
						}
					}
					$level = $arrUserInfo[$uid]['level'];
					$reward = array(
							RewardType::SILVER => $conf[CompeteDef::COMPETE_REWARD_SILVER] * $level,
							RewardType::SOUL => $conf[CompeteDef::COMPETE_REWARD_SOUL] * $level,
							RewardType::GOLD => $conf[CompeteDef::COMPETE_REWARD_GOLD],
							RewardType::ARR_ITEM_TPL => $conf[CompeteDef::COMPETE_REWARD_ITEM]->toArray(),
							RewardType::HORNOR => $conf[CompeteDef::COMPETE_REWARD_HONOR],
							RewardDef::EXT_DATA => array( 'rank' => $rank ),
					);
					Logger::trace('generate reward for user:%d, reward:%s', $uid, $reward);
						
					//发邮件通知用户
					MailTemplate::sendCompeteRank($uid, $rank, $reward[RewardType::SOUL],
					$reward[RewardType::SILVER], $reward[RewardType::GOLD], $reward[RewardType::HORNOR],$reward[RewardType::ARR_ITEM_TPL]);
						
					//发奖励到奖励中心
					$rid = EnReward::sendReward($uid, RewardSource::COMPETE_RANK, $reward);
					Logger::trace('user:%d reward id is %d in reward center', $uid, $rid);
					
					//成就优化
					if ($rank <= $maxRank) 
					{
						$arrUserRank[$uid] = $rank;
					}

					//初始化用户信息
					self::init($uid, $arrRankInfo[$uid][$order], Util::getTime());
					
					$arrNotifyUid[] = $uid;
						
					if (++$sleepCount == CompeteConf::NUM_OF_REWARD_PER)
					{
						usleep(CompeteConf::SLEEP_MTIME);
						$sleepCount = 0;
					}
					$uid = next($arrUid);
				}
				catch (Exception $e )
				{
					Logger::fatal('fail to generateReward for uid:%d, $reward:%s', $uid, $reward);
					$uid = next($arrUid);
				}
			}
		}
		
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::MAIL_CALLBACK, array() );
		RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::REWARD_NEW, array() );
		
		$batchNum = 1000;
		$slice = array();
		foreach($arrUserRank as $uid => $rank) {
			$slice[$uid] = $rank;
			if(sizeof($slice) >= $batchNum) {
				RPCContext::getInstance()->executeTask($uid,
				 	'achieve.updateTypeArrBySystem',
				  	array(AchieveDef::COMPETE_RANK, $slice));
				$slice = array();
			}
		}
		if(sizeof($slice) > 0) {
				RPCContext::getInstance()->executeTask($uid,
				 	'achieve.updateTypeArrBySystem',
				  	array(AchieveDef::COMPETE_RANK, $slice));
		}
		Logger::trace('CompeteLogic::generateReward End.');
	}
	
	/**
	 * 通知前端:发奖结束。给脚本调用
	 * 
	 * @param boolean $redo 是否重做
	 */
	public static function endReward($redo = false)
	{
		Logger::trace('CompeteLogic::endReward Start.');
	
		//必须在比武的最后一天，且比武结束后发奖
		self::getRound();
		$now = Util::getTime();
		$rewardTime = self::getRewardTime();
		Logger::trace('now time is:%d, reward end time is:%d', $now, $rewardTime[1]);
		if ((empty(self::$round) || !Util::isSameDay($rewardTime[1]) || $now < $rewardTime[1]) && !$redo)
		{
			Logger::info('today is not the day of generate reward.');
			return ;
		}
		
		//根据发奖时间判断奖励是否发完了,发完了才初始化积分
		$minRewardTime = CompeteDao::getMinRewardTime();
		if ((empty($minRewardTime) || $minRewardTime < $rewardTime[0]) && !$redo) 
		{
			$arrCond = array(array(CompeteDef::COMPETE_UID, '>', SPECIAL_UID::MAX_ROBOT_UID));
			$userNum = CompeteDao::getUserNum($arrCond);
			if (empty($userNum)) 
			{
				Logger::info('No user in compete.');
			}
			else 
			{
				//reward_time大于1是保持同前面一致，原因不清楚，可能会造成新进用户没有统计
				$arrCond = array(
						array(CompeteDef::COMPETE_UID, '>', SPECIAL_UID::MAX_ROBOT_UID),
						array(CompeteDef::REWARD_TIME, 'between', array(1, $rewardTime[0] - 1))
				);
				$failNum = CompeteDao::getUserNum($arrCond);
				if ($failNum > 1000)
				{
					Logger::fatal('Fix Me! Reward is not generated successfully.');
				}
				else 
				{
					self::initPoint();
				}
			}
		}
		else 
		{
			self::initPoint();
		}

		RPCContext::getInstance()->sendMsg(array(SPECIAL_UID::BROADCAST), PushInterfaceDef::COMPETE_REWARD, array('end'));
	
		Logger::trace('CompeteLogic::endReward End.');
	}
	
	/**
	 * 计算战斗基础奖励
	 * 
	 * @param boolean $isSuc
	 */
	private static function computeReward($isSuc)
	{
		$conf = btstore_get()->COMPETE[self::$round];
			
		if ($isSuc)
		{
			self::$reward['exp'] = $conf[CompeteDef::COMPETE_SUC_EXP] * self::$level;
		}
		else
		{
			self::$reward['exp'] = $conf[CompeteDef::COMPETE_FAIL_EXP] * self::$level;
		}

		Logger::trace('compete reward is %s!', self::$reward);
	}
	
	/**
	 * 用户数据初始化
	 *
	 * @param int $uid									用户id
	 * @param int $lastPoint							上轮积分	
	 * @param int $rewardTime							发奖时间			
	 * @return array $arrField							用户信息
	 */
	public static function init($uid, $lastPoint = 0, $rewardTime = 0)
	{
		Logger::trace('CompeteLogic::init Start.');
		
		$arrField = self::initCompete($uid, $lastPoint, $rewardTime);
		
		//COMPETE_POINT在没有数据的时候才会初始化一次
		CompeteDao::insertOrUpdate($arrField);
	
		Logger::trace('CompeteLogic::init End.');
		return $arrField;
	}
	
	private static function initCompete($uid, $lastPoint = 0, $rewardTime = 0)
	{
		Logger::trace('CompeteLogic::initCompete Start.');
		
		if(isset(btstore_get()->COMPETE[self::$round]))
		{
			$initPoint = btstore_get()->COMPETE[self::$round][CompeteDef::COMPETE_INIT_POINT];
		}
		else
		{
			//当前不在活动中时，取第一个配置
			$arrConf = btstore_get()->COMPETE->toArray();
			list($key, $conf) = each($arrConf);
			$initPoint = $conf[CompeteDef::COMPETE_INIT_POINT];
		}
		
		$arrField = array(
				CompeteDef::COMPETE_UID => $uid,
				CompeteDef::COMPETE_NUM => 0,
				CompeteDef::COMPETE_BUY => 0,
				CompeteDef::COMPETE_HONOR => 0,
				CompeteDef::COMPETE_POINT => $initPoint,
				CompeteDef::LAST_POINT => $lastPoint,
				CompeteDef::POINT_TIME => 0,
				CompeteDef::COMPETE_TIME => 0,
				CompeteDef::REFRESH_TIME => 0,
				CompeteDef::REWARD_TIME => $rewardTime,
				CompeteDef::VA_COMPETE => array(
						CompeteDef::RIVAL_LIST => array(),
						CompeteDef::FOE_LIST => array(),
				),
		);
		
		Logger::trace('CompeteLogic::initCompete End.');
		
		return $arrField;
	}
	
	/**
	 * 初始化所有用户的积分
	 */
	private static function initPoint()
	{
		Logger::trace('CompeteLogic::initPoint Start.');
		
		$round = self::getNextRound();
		$initPoint = btstore_get()->COMPETE[$round][CompeteDef::COMPETE_INIT_POINT];
		$arrField = array(CompeteDef::COMPETE_POINT => $initPoint);
		CompeteDao::updateAll($arrField);
		
		Logger::trace('CompeteLogic::initPoint End.');
	}

	/**
	 * 检查比武是否开启
	 * 
	 * @return boolean true开启，false关闭
	 */
	private static function isOpen()
	{
		Logger::trace('CompeteLogic::isOpen Start.');
		
		$ret = true;
		
		//获得当前时间
		$now = Util::getTime();
		$hour = strftime("%H:%M:%S", $now);
		//是否在比武时间内
		if ($hour < CompeteConf::COMPETE_START_TIME 
		 || $hour > CompeteConf::COMPETE_END_TIME) 
		{
			$ret = false;
		}
		
		Logger::trace('CompeteLogic::isOpen End.');
		
		return $ret;
	}
	
	/**
	 * 计算当前比武的轮次
	 * 调用对外接口的时候必须先调这个函数
	 */
	private static function getRound()
	{
		Logger::trace('CompeteLogic::getRound Start.');
		
		//获得周几, 0(周日)到6(周6)
		self::$day = Util::getTodayWeek();
		
		//初始化轮次
		self::$round = 0;
		
		//遍历配置表
		$conf = btstore_get()->COMPETE->toArray();
		foreach ($conf as $roundId => $roundInfo)
		{
			if (in_array(self::$day, $roundInfo[CompeteDef::COMPETE_LAST_TIME])
			 || in_array(self::$day, $roundInfo[CompeteDef::COMPETE_REST_TIME])) 
			{
				self::$round = $roundId;
				break;
			}
		}
		
		Logger::trace('CompeteLogic::getRound End.');
	}
	
	/**
	 * 计算下轮比武的轮次
	 * 
	 * @return int $round
	 */
	private static function getNextRound()
	{
		Logger::trace('CompeteLogic::getNextRound Start.');
		
		$arrConf = btstore_get()->COMPETE->toArray();
		
		//从头遍历配置表
		$conf = current($arrConf);
		while ($conf != false)
		{
			//如果遍历到当前轮就返回，否则继续遍历下轮
			if ($conf[CompeteDef::COMPETE_TEMPLATE_ID] == self::$round)
			{
				break;
			}
			$conf = next($arrConf);
		}
		//取下轮的配置，如果没有下轮的配置就从头取第一轮的配置
		$conf = next($arrConf);
		if ($conf == false)
		{
			$conf = array_slice($arrConf, 0, 1);
			$conf = $conf[0];
		}
		
		Logger::trace('CompeteLogic::getNextRound End.');
		
		return $conf[CompeteDef::COMPETE_TEMPLATE_ID];
	}
	
	/**
	 * 是否比武时间
	 * 
	 * @return boolean true是，false否
	 */
	private static function isContestTime()
	{
		Logger::trace('CompeteLogic::isContestTime Start.');
		
		$ret = false;
		
		$conf = btstore_get()->COMPETE[self::$round]->toArray();
		if (in_array(self::$day, $conf[CompeteDef::COMPETE_LAST_TIME])) 
		{
			$ret = true;
		}
		
		Logger::trace('CompeteLogic::isContestTime End.');
		
		return $ret;
	}
	
	/**
	 * 是否到发奖时间
	 * 
	 * @return boolean true是，false否
	 */
	private static function isRewardTime()
	{
		Logger::trace('CompeteLogic::isRewardTime Start.');
		
		$ret = false;
		
		$now = Util::getTime();
		$rewardTime = self::getRewardTime();
		Logger::trace('reward start time is:%d, end time is:%d, now time is:%d', $rewardTime[0], $rewardTime[1], $now);
		if ($now >= $rewardTime[0] && $now <= $rewardTime[1]) 
		{
			$ret = true;
		}
		
		Logger::trace('CompeteLogic::isRewardTime End.');
		
		return $ret;
	}
	
	/**
	 * 获取发奖开始时间和结束时间
	 *
	 * @param int $round 轮次
	 * @return array 发奖开始时间和发奖结束时间
	 */
	private static function getRewardTime($round = 0)
	{
		Logger::trace('CompeteLogic::getRewardTime Start.');
		
		if (empty($round)) 
		{
			$round = self::$round;
		}
		
		//获得发奖日
		$conf = btstore_get()->COMPETE[$round];
		$count = count($conf[CompeteDef::COMPETE_LAST_TIME]);
		$lastDay = $conf[CompeteDef::COMPETE_LAST_TIME][$count - 1];
		$startRewardDay = ($lastDay == 0 ? 7 : $lastDay);
		$endRewardDay = $lastDay + 1;
		$day = (self::$day == 0 ? 7 : self::$day);
		
		$now = Util::getTime();
		$startRewardDate = intval(strftime("%Y%m%d", $now + ($startRewardDay - $day) * SECONDS_OF_DAY));
		$startRewardTime = strtotime($startRewardDate . " " . CompeteConf::REWARD_START_TIME);
		Logger::trace('start reward time is:%s %s', $startRewardDate, CompeteConf::REWARD_START_TIME);
		$endRewardDate = intval(strftime("%Y%m%d", $now + ($endRewardDay - $day) * SECONDS_OF_DAY));
		$endRewardTime = strtotime($endRewardDate . " " . CompeteConf::REWARD_END_TIME);
		Logger::trace('end reward time is:%s %s', $endRewardDate, CompeteConf::REWARD_END_TIME);
		
		Logger::trace('CompeteLogic::getRewardTime End.');
		
		return array($startRewardTime, $endRewardTime);
	}
	
	/**
	 * 获取用户的积分排名
	 * 
	 * @param int $uid	用户id
	 * @param int $point 用户积分
	 * @param int $time	积分时间
	 * @param int $isCurrRound 是否当前轮
	 * @return number 积分排名
	 */
	private static function getRank($uid, $point, $time, $isCurrRound = TRUE)
	{
		Logger::trace('CompeteLogic::getRank Start.');
		
		$field = ($isCurrRound ? CompeteDef::COMPETE_POINT : CompeteDef::LAST_POINT);
		
		//原来的两次请求由于线上用户大部分都是相同的分数，导致第二次请求数很大，所以改成了现在的3次请求
		//获取积分比我高的用户数量
		$arrCond = array(array($field, '>', $point));
		$rank = CompeteDao::getUserNum($arrCond);
		
		//获取积分等于我，且时间小于我的用户数量
		$arrCond = array(
				array($field, '=', $point),
				array(CompeteDef::POINT_TIME, '<', $time),
		);
		$rank += CompeteDao::getUserNum($arrCond);
		
		//获取积分等于我，时间等于我，且uid比我小的用户数量
		$arrCond = array(
				array($field, '=', $point),
				array(CompeteDef::POINT_TIME, '=', $time),
				array(CompeteDef::COMPETE_UID, '<', $uid),
		);
		$rank += CompeteDao::getUserNum($arrCond);

		Logger::trace('CompeteLogic::getRank End.');
		
		return $rank + 1;
	}
	
	/**
	 * 通过比武轮次来获得排名
	 * 
	 * @param int $num 数量
	 * @param boolean $isCurrRound 是否当前轮
	 * @return multitype:
	 */
	private static function getRankListByRound($num, $isCurrRound = TRUE)
	{
		Logger::trace('CompeteLogic::getRankListByRound Start.');
		
		$order = ($isCurrRound ? CompeteDef::COMPETE_POINT : CompeteDef::LAST_POINT);
		$arrRankInfo = CompeteDao::getRankList(0, $num, $order);
		$arrUid = array_keys($arrRankInfo);
		$arrSquad = EnUser::getArrUserSquad($arrUid);
		$arrUsers = EnUser::getArrUser($arrUid, array('uid', 'uname', 'level', 'vip', 'title', 'fight_force', 'guild_id'));
		$arrGuildId = Util::arrayExtract($arrUsers, 'guild_id');
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));
		
		$rank = 0;
		foreach ($arrRankInfo as $uid => $rankInfo)
		{
			$arrUsers[$uid]['squad'] = array_slice($arrSquad[$uid], 0, 1);
			$arrUsers[$uid]['point'] = $rankInfo[$order];
			$arrUsers[$uid]['rank'] = ++$rank;
			$guildId = $arrUsers[$uid]['guild_id'];
			unset($arrUsers[$uid]['guild_id']);
			if (!empty($guildId) && !empty($arrGuildInfo[$guildId][GuildDef::GUILD_NAME]) ) 
			{
				$arrUsers[$uid][GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
			}
		}
		
		Logger::trace('CompeteLogic::getRankListByRound End.');
		
		return array_values($arrUsers);
	}
	
	/**
	 * 获取用户的对手id组
	 *
	 * @param int $uid								用户uid
	 * @param int $point							用户积分
	 * @throws Exception
	 * @return array mixed 							对手uid组
	 */
	private static function getRivalList($uid, $point)
	{
		Logger::trace('CompeteLogic::getRivalList Start.');

		//获取对手所在的积分区段
		$conf = btstore_get()->COMPETE[self::$round];
		$confPointSeg = $conf[CompeteDef::COMPETE_POINT_GROUP];
		$segIndex = self::getSegmentIndex($point, $confPointSeg);
		$segMin = $confPointSeg[$segIndex] + 1;
		
		$arrLow = array();
		$arrMid = array();
		$arrHigh = array();
		
		if( isset($confPointSeg[$segIndex - 1]) )//只有当：我不是处在最低一段，才需要获取低段数据
		{
			$arrLow = CompeteDao::getUsersWithLessPoint($segMin - 1, 2 * CompeteConf::COMPETE_RIVAL_NUM + 1);
		}
		if( isset($confPointSeg[$segIndex + 1]) )
		{
			$segMax = $confPointSeg[$segIndex + 1];
			$arrHigh = CompeteDao::getUsersWithMorePoint($segMax + 1, 2 * CompeteConf::COMPETE_RIVAL_NUM + 1);				
			$arrMid = CompeteDao::getUsersBetweenPoint($segMin, $segMax, 2 * CompeteConf::COMPETE_RIVAL_NUM + 1);
		}
		else //如果我处在最高段,大于积分段最大值
		{
			$arrMid = CompeteDao::getUsersWithMorePoint($segMin, 2 * CompeteConf::COMPETE_RIVAL_NUM + 1);
		}
		
		$arrUidPoint = Util::arrayMerge(array($arrLow, $arrMid, $arrHigh));
		unset($arrUidPoint[$uid]);
		//将用户分段，并分段随机
		$arrUid = self::randUsers($segIndex, $arrUidPoint, $confPointSeg);
		Logger::trace('arr uid:%s', $arrUid);
		Logger::trace('CompeteLogic::getRivalList End.');
		return $arrUid;
	}
	
	/**
	 * 获取对手信息
	 * 
	 * @param array $arrUid 对手id组
	 * @return array mixed 对手信息
	 */
	private static function getRivalInfo($arrUid, $squadNum = 1)
	{
		Logger::trace('CompeteLogic::getRivalInfo Start.');
		
		if (empty($arrUid)) 
		{
			return array();
		}
		
		//arrUid不能超过100，不然就报错了
		$arrField = array(CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT);
		$arrCond = array(array(CompeteDef::COMPETE_UID, 'IN', $arrUid));
		$arrRet = CompeteDao::getUsers($arrField, $arrCond);
		$arrUidPoint = Util::arrayIndexCol($arrRet, CompeteDef::COMPETE_UID, CompeteDef::COMPETE_POINT);
		$arrUsers = EnUser::getArrUser($arrUid, array('uid', 'utid', 'uname', 'level', 'vip', 'fight_force', 'guild_id', 'title'));
		$arrSquad = EnUser::getArrUserSquad($arrUid);
		$arrGuildId = Util::arrayExtract($arrUsers, 'guild_id');
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));
		
		foreach ($arrUidPoint as $uid => $point)
		{
			$arrUsers[$uid]['squad'] = array_slice($arrSquad[$uid], 0, $squadNum);
			//FIXME:暂时规避前端bug用的
			$arrUsers[$uid]['squad'][0]['dress']['useless'] = 1;
			unset($arrUsers[$uid]['squad'][0]['dress']['useless']);
			$arrUsers[$uid]['point'] = $point;
			$guildId = $arrUsers[$uid]['guild_id'];
			unset($arrUsers[$uid]['guild_id']);
			if (!empty($guildId) && !empty($arrGuildInfo[$guildId][GuildDef::GUILD_NAME]) )
			{
				$arrUsers[$uid][GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
			}
		}
		Logger::trace('CompeteLogic::getRivalInfo End.');
		return array_values($arrUsers);
	}
	
	/**
	 * 获取对手所在的积分区段
	 * 
	 * @param int $point
	 * @param array $confPointSeg
	 */
	public static function getSegmentIndex($point, $confPointSeg)
	{
		$segIndex = -1;
		foreach ($confPointSeg as $key => $value)
		{
			if( $point <= $value )
			{
				break;
			}
			$segIndex = $key;
		}
		if($segIndex < 0)
		{
			throw new ConfigException('cant find segment. point:%d', $point);
		} 
		Logger::trace('getSegmentIndex:%d', $segIndex);
		
		return $segIndex;
	}
	
	
	/**
	 * 分段随机出用户
	 * 
	 * @param int $segIndex  我自己处在哪一段
	 * @param array $arrUidPoint
	 * @return array 随机出的用户id组
	 */
	public static function randUsers($segIndex, $arrUidPoint, $confPointSeg)
	{
		Logger::trace('CompeteLogic::randUsers Start.');
		
		if(count($arrUidPoint) <= CompeteConf::COMPETE_RIVAL_NUM)
		{
			Logger::fatal('no enough user:%s', $arrUidPoint);
			return array_keys($arrUidPoint);
		}
		
		$arrSeg = array();
		foreach($arrUidPoint as $uid => $point)
		{
			$index = self::getSegmentIndex($point, $confPointSeg);//此处效率较差
			$arrSeg[$index][$uid] = 1;
		}
		Logger::debug('segIndex:%d, arrSeg:%s', $segIndex, $arrSeg);
		
		$returnData = array();
		$tryNum = count($confPointSeg) * 2 + 1;
		for( $i = 0; $i < CompeteConf::COMPETE_RIVAL_NUM; $i++)
		{
			$index = $segIndex - intval(CompeteConf::COMPETE_RIVAL_NUM / 2) + $i;
			
			for($j = 1; $j <= $tryNum; $j++)
			{
				//下面看着很罗嗦的语句是想生成序列：0 -1 1 -2 2 ...
				$k = $index + intval( $j / 2 ) * ( ($j % 2) * 2 - 1 );
				if( !empty( $arrSeg[$k] ) )
				{
					$id = array_rand($arrSeg[$k]);
					$returnData[] = $id;
					unset($arrSeg[$k][$id]);
					break;
				}
			}			
		}
		
		Logger::trace('CompeteLogic::randUsers End %s', $returnData);
		
		return $returnData;
	}
	
	public static function isRobotUid($uid)
	{
		return $uid >= SPECIAL_UID::MIN_ROBOT_UID && $uid <= SPECIAL_UID::MAX_ROBOT_UID;
	}
	
	public static function getRetrieveInfo($uid)
	{
		if (!EnSwitch::isSwitchOpen(SwitchDef::ROB))
		{
			return FALSE;
		}
		
		self::getRound();
		if (empty(self::$round)) 
		{
			return FALSE;
		}
		
		if (self::isContestTime() && self::isOpen())
		{
			return FALSE;
		}
		
		$info = CompeteDao::select($uid);
		$competeTime = $info[CompeteDef::COMPETE_TIME];
		$currRank = self::getRank($uid, $info[CompeteDef::COMPETE_POINT], $info[CompeteDef::POINT_TIME]);
		
		list($startTime, $endTime) = self::getCompeteTime();
		list($beforeStartTime, $beforeEndTime) = self::getBeforeCompeteTime();
		if ($competeTime >= $beforeStartTime && $currRank <= 32)
		{
			return FALSE;
		}
		
		return array($beforeEndTime, $startTime);
	}
	
	public static function getCompeteTime($time = NULL)
	{
		self::getRound();
		if (empty(self::$round))
		{
			FALSE;
		}
		
		$curTime = ($time === NULL ? Util::getTime() : $time);
		
		$dayStartTime = strtotime(CompeteConf::COMPETE_START_TIME) - mktime (0, 0, 0);
		$dayStartTimes = array($dayStartTime);
		
		$dayEndTime = strtotime(CompeteConf::COMPETE_END_TIME) - mktime (0, 0, 0);
		$dayEndTimes = array($dayEndTime);
		
		$dayList = array();
		$conf = btstore_get()->COMPETE[self::$round]->toArray();
		$weekList = $conf[CompeteDef::COMPETE_LAST_TIME];
		foreach ($weekList as &$week)
		{
			$week = ($week ? $week : 7);
		}
		
		sort($dayStartTimes);
		sort($dayEndTimes);
		sort($dayList);
		sort($weekList);
		Logger::trace("CompeteLogic::getCompeteTime dayStartTimes:%s, dayEndTimes:%s, dayList:%s, weekList:%s", $dayStartTimes, $dayEndTimes, $dayList, $weekList);
		
		$interval = TimeInterval::getTimeInterval($curTime, 0, strtotime('20201231235959'), $dayStartTimes, $dayEndTimes, $dayList, $weekList);
		return $interval;
	}
	
	public static function getBeforeCompeteTime($time = NULL)
	{
		self::getRound();
		if (empty(self::$round))
		{
			FALSE;
		}
		
		$curTime = ($time === NULL ? Util::getTime() : $time);
		
		$dayStartTime = strtotime(CompeteConf::COMPETE_START_TIME) - mktime (0, 0, 0);
		$dayStartTimes = array($dayStartTime);
		
		$dayEndTime = strtotime(CompeteConf::COMPETE_END_TIME) - mktime (0, 0, 0);
		$dayEndTimes = array($dayEndTime);
		
		$dayList = array();
		$conf = btstore_get()->COMPETE[self::$round]->toArray();
		$weekList = $conf[CompeteDef::COMPETE_LAST_TIME];
		foreach ($weekList as &$week)
		{
			$week = ($week ? $week : 7);
		}
		
		rsort($dayStartTimes);
		rsort($dayEndTimes);
		rsort($dayList);
		rsort($weekList);
		Logger::trace("CompeteLogic::getBeforeCompeteTime dayStartTimes:%s, dayEndTimes:%s, dayList:%s, weekList:%s", $dayStartTimes, $dayEndTimes, $dayList, $weekList);
		
		$interval = TimeInterval::getTimeIntervalBefore($curTime, 0, strtotime('20201231235959'), $dayStartTimes, $dayEndTimes, $dayList, $weekList);
		return $interval;
	}
	
	public static function getTopActivityInfo()
	{
		$ret = array('status' => 'ok', 'extra' => array('num' => 0));
		
		self::getRound();
		if (empty(self::$round)
		|| !self::isOpen()
		|| !self::isContestTime()
		|| !EnSwitch::isSwitchOpen(SwitchDef::ROB))
		{
			$ret['status'] = 'invalid';
			return $ret;
		}
		
		$user = EnUser::getUserObj();
		$uid = RPCContext::getInstance()->getUid();
		$info = CompeteDao::select($uid);
		
		//刷新比武次数和购买次数
		if (!Util::isSameDay($info[CompeteDef::COMPETE_TIME]))
		{
			$info[CompeteDef::COMPETE_NUM] = 0;
			$info[CompeteDef::COMPETE_BUY] = 0;
		}
		
		$maxNum = btstore_get()->VIP[$user->getVip()]['competeNum'][0] + $info[CompeteDef::COMPETE_BUY];
		$ret['extra']['num'] = $maxNum - $info[CompeteDef::COMPETE_NUM];
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */