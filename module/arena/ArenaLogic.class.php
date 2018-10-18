<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaLogic.class.php 258621 2016-08-26 09:12:13Z MingTian $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/ArenaLogic.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-26 09:12:13 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258621 $
 * @brief
 *
 **/

/**********************************************************************************************************************
 * Class       : ArenaLogic
 * Description : 竞技场系统的业务逻辑实现类
 * Inherit     :
 **********************************************************************************************************************/

/**
 * warning:
 * challenge会修改其它用户的数据，包括当前用户和被挑战的用户都会被修改。
 * 所以update的时候只update修改的数据，不能省事update所有。
 * 字段						 修改人   是否加锁
 * uid
 * position					  all	 lock
 * challenge_num			  self	 safe
 * challenge_time		  	  self	 safe
 * add_challenge_num		  self	 safe
 * add_challenge_time	  	  self	 safe
 * cur_suc					  all	 lock
 * max_suc			  		  all	 lock
 * min_position	  			  all	 safe
 * upgrade_continue			  all	 lock
 * fight_cdtime				  self	 safe
 * va_opponents				  all	 lock
 * reward_time				  self	 safe
 * va_reward				  self	 safe
 *
 * 获取对手信息的时候只使用$arrAtkedField，
 * update被攻击用户的时候只update这几个数据。
 *
 * 在challenge （这个命令加锁了）以外的命令不要这几个数据
 * @author idyll
 *
 */

class NpcUser
{
	private $uid;
	
	private $battleFormation = NULL;
	
	public function __construct ($uid)
	{
		$this->uid = $uid;
	}
	
	public function getUid()
	{
		return $this->uid;
	}
	
	public function getUname()
	{
		return ArenaLogic::getNpcName($this->uid);
	}
	
	public function getBattleFormation()
	{
		$this->initBattleFormation();
		return $this->battleFormation;
	}
	
	public function getFightForce()
	{
		$this->initBattleFormation();
		return $this->battleFormation['fightForce'];
	}
	
	public function getTemplateUserInfo()
	{
		return array(
				'uid' => $this->uid,
				'utid'=> ArenaLogic::getNpcUtid($this->uid),
				'uname'=> ArenaLogic::getNpcName($this->uid),
				);
	}
	
	private function initBattleFormation()
	{
		if( empty($this->battleFormation) )
		{
			$armyId = ArenaLogic::getNpcArmyId($this->uid);
			$this->battleFormation = EnFormation::getMonsterBattleFormation( $armyId );
			$this->battleFormation['uid'] = $this->uid;
		}
	}
}

class ArenaLogic
{
	//竞技场总人数，每次请求查询一次即可，保存总数。
	//执行脚本的时候手动设置为0，每次从数据库拉。
	public static $a_count = 0;
	
	//战斗模块回调会用到
	private static $a_level = 0;
	private static $a_reward = array();
	
	static $arrAtkedField = array('uid',
                               	  'position',
								  'challenge_num',
								  'challenge_time',
                                  'cur_suc',
                                  'max_suc',
								  'min_position',
			                      'upgrade_continue',
                                  'va_opponents',
								  'reward_time',
							 	  'va_reward');

	static $arrField = array('uid',
                             'position',
                             'challenge_num',
							 'challenge_time',
                             'cur_suc',
                             'max_suc',
                             'min_position',
                             'upgrade_continue',
                             'va_opponents',
							 'reward_time',
							 'va_reward');

	static $arrMsgField = array('attack_uid',
                     			'attack_name',
                                'defend_uid',
                                'defend_name',
                                'attack_time',
                                'attack_position',
                                'defend_position',
                                'attack_res',
								'attack_replay');
	
	/**
	 * 获取竞技场总人数
	 * 
	 * @return number
	 */
	private static function getArenaCount()
	{
		if (self::$a_count == 0)
		{
			self::$a_count = ArenaDao::getCount();
		}		
		return self::$a_count;
	}
	
	private static function computeReward($isSuccess)
	{
		$conf = btstore_get()->ARENA_PROPERTIES;
		
		if ($isSuccess)
		{
			self::$a_reward['silver'] = min($conf['fight_suc_silver_max'], $conf['fight_suc_silver'] * self::$a_level);
			self::$a_reward['soul'] = $conf['fight_suc_soul'] * self::$a_level;
			self::$a_reward['exp'] = $conf['fight_suc_exp'] * self::$a_level;
			self::$a_reward['prestige'] = $conf['fight_suc_prestige'];
		}
		else
		{
			self::$a_reward['silver'] = min($conf['fight_fail_silver_max'], $conf['fight_fail_silver'] * self::$a_level);
			self::$a_reward['soul'] = $conf['fight_fail_soul'] * self::$a_level;
			self::$a_reward['exp'] = $conf['fight_fail_exp'] * self::$a_level;
			self::$a_reward['prestige'] = $conf['fight_fail_prestige'];
		}
		Logger::trace('challenge reward is %s!', self::$a_reward);
	}

	/**
	 * 获取竞技场信息
	 * 
	 * @param int $uid								用户id
	 * @return array mixed							竞技场信息
	 */
	public static function getArenaInfo($uid)
	{
		Logger::trace('ArenaLogic::getArenaInfo Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ARENA) == false)
		{
			throw new FakeException('user:%d does not open the arena', $uid);
		}
		
		$arrRet = array('ret'=>'ok', 'res'=>array());
		$res = self::getInfo($uid);
		if (empty($res))
		{
			//初始化用户的竞技场信息
			$ret = self::initArena($uid);
            if ('ok' != $ret)
            {
            	$arrRet['ret'] = $ret;
            	return $arrRet;
            }
            $res = self::getInfo($uid);
		}

		$arrRet['res'] = $res;
		
		Logger::trace('ArenaLogic::getArenaInfo End.');
		return $arrRet;
	}
	
	/**
	 * 处理并更新数据库中获取的用户竞技场信息
	 *
	 * @param int $uid								用户id
	 * @return array mixed 							竞技场信息
	 */
	public static function getInfo($uid)
	{
		Logger::trace('ArenaLogic::getInfo Start.');
		//查询数据库
		$info = ArenaDao::get($uid, self::$arrField);
		//用户还没有竞技场数据，直接返回空
		if (empty($info))
		{
			return $info;
		}
	
		//如果上次挑战时间不是今天并且次数不足初始值，就重置挑战次数
		$freeNum = btstore_get()->ARENA_PROPERTIES['challenge_free_num'];
		if (!Util::isSameDay($info['challenge_time']) &&  $info['challenge_num'] < $freeNum )
		{
			$info['challenge_num'] = $freeNum;
		}
	
		Logger::trace('ArenaLogic::getInfo End.');
		return $info;
	}

	/**
	 * 初始化用户的竞技场信息
	 * @param int $uid								用户id
	 * @return string ok 							成功
	 * 				  lock							业务忙
	 */
	public static function initArena($uid)
	{
		Logger::trace('ArenaLogic::initArena Start.');
		
		$alock = new ArenaLock();
		if (false == $alock->lock('insert'))
		{
			Logger::fatal('fail to lock t_arena for insert data');
			return 'lock';
		}
		
		//获取目前竞技场上的最大排名
		$maxPosition = self::getArenaCount();
		$pos = $maxPosition + 1;
		//根据排名获取相应的对手们排名
		$opptPos = self::getOpponentPosition($pos);
		//读配置表获取用户每天的挑战次数
		$num = btstore_get()->ARENA_PROPERTIES['challenge_free_num'];
		$arrField = array('uid' => $uid,
						  'position' => $pos,
						  'challenge_num' => $num,
						  'challenge_time' => 0,
						  'cur_suc' => 0,
						  'max_suc' => 0,
						  'min_position' => $pos,
						  'upgrade_continue' => 0,
						  'va_opponents' => $opptPos,
						  'reward_time' => 0,
						  'va_reward' => array());
		$arrField['va_reward']['his'] = self::initPosHis($pos);
		ArenaDao::insert($arrField);
		
		//场上人数不足11人, 更新前10人数据
		if ($pos <= 11) 
		{
			for($i = 1;	 $i < $pos; $i++)
			{
				$arrField = array();
				$vaInfo = ArenaDao::getByPos($i, array('va_opponents'));
				$arrField['va_opponents'] = $vaInfo['va_opponents'];
				$arrField['va_opponents'][$pos-2] = $pos;
				ArenaDao::updateByPos($i, $arrField);
			}
		}
		
		//如果当前竞技场上总共只有10个人，那就发幸运奖
		if ($pos - ArenaConf::NPC_NUM <= 10)
		{
			//产生幸运奖的排名, 竞技场发奖日期
			//如果用户是第一个进的，肯定会产生幸运排名
			//如果用户不是第一个，则分为三种情况
			//1.十点前，不会
			//2.十点后，十点半之前，会
			//3.十点半之后，会
			//因此加个判断幸运排名是否存在, 不存在就产生幸运排名
			$ret = ArenaLuckyDao::getRewardLuckyList(array('va_lucky'));
			if (empty($ret[0]) && empty($ret[1])) 
			{
				ArenaLuckyLogic::generatePosition();
			}
		}

		$alock->unlock();
		
		Logger::trace('ArenaLogic::initArena End.');
		return 'ok';
	}	
	
	/**
	 *
	 * 初始化的历史排名数据结构如下
	 * 22:00之前
	 * {
	 * 		day-6 => (pos,0)
	 * 		day-5 => (pos,0)
	 * 		day-4 => (pos,0)
	 * 		day-3 => (pos,0)
	 * 		day-2 => (pos,0)
	 * 		day-1 => (pos,0)
	 * 		day   => (pos,1)
	 * }
	 * 22:00之后
	 * {
	 * 		day-5 => (pos,0)
	 * 		day-4 => (pos,0)
	 * 		day-3 => (pos,0)
	 * 		day-2 => (pos,0)
	 * 		day-1 => (pos,0)
	 * 		day   => (pos,0)
	 * 		day+1 => (pos,1)
	 * }
	 * status:0无1未发2已发
	 */
	public static function initPosHis($pos)
	{
		Logger::trace('ArenaLogic::initPosHis Start.');
		$his = array();
		$now = Util::getTime();
		$today = intval(strftime("%Y%m%d", $now));
		$rewardTime = strtotime($today . " " . ArenaDateConf::LOCK_START_TIME);
		$shift = $now > $rewardTime ? 1 : 0;
		for ($k = 1, $i = 1 + $shift - ArenaConf::POS_HIS; $k <= ArenaConf::POS_HIS; $k++, $i++)
		{
			$sign = $i > 0 ? "+" : "-";
			$offset =  $sign . abs($i) . " day";
			$date = intval(strftime("%Y%m%d", strtotime($offset, $now)));
			$status = $k == ArenaConf::POS_HIS ? ArenaDef::HAVE : ArenaDef::NONE;
			$his[$date] = array($pos, $status);
		}
		Logger::trace("init pos his:%s", $his);
		Logger::trace('ArenaLogic::initPosHis End.');
		return $his;
	}
	
	/**
	 * 更新历史排名数据
	 * 规则：
	 * 1.默认用户当前排名是跟历史最近排名一致的，所以始终使用用户当前排名更新；
	 * 1.如果为空就初始化数据，从奖励中心获取历史最近排名，检查一致性；
	 * 2.如果不为空就更新数据，从旧数据中获得历史最近排名，检查一致性；
	 *
	 * @param int $uid 用户id
	 * @param int $pos 用户当前排名
	 * @param array $oldHis 历史排名数据
	 * @return string 'ok'
	 */
	public static function refreshPosHis($uid, $pos, $oldHis)
	{
		Logger::trace('ArenaLogic::refreshPosHis Start.');
	
		$newHis = array();
		$now = Util::getTime();
		$today = intval(strftime("%Y%m%d", $now));
		$rewardTime = strtotime($today . " " . ArenaDateConf::LOCK_START_TIME);
		$shift = $now > $rewardTime ? 1 : 0;
		if (empty($oldHis))
		{
			//从奖励中心获取历史最近的发奖时间和排名
			list($sendTime, $rank) = RewardDao::getLatestArenaRank($uid);
			for ($k = 1, $i = 1 + $shift - ArenaConf::POS_HIS; $k <= ArenaConf::POS_HIS; $k++, $i++)
			{
				$sign = $i > 0 ? "+" : "-";
				$offset =  $sign . abs($i) . " day";
				$time = strtotime($offset, $rewardTime);
				$date = intval(strftime("%Y%m%d", $time));
				if ($time <= $sendTime)
				{
				 	//如果在发奖时间之前的，则认为全部已发奖, 这里的历史数据rank不需要完全准确
					$newHis[$date] = array($pos, ArenaDef::REWARD);
				}
				else
				{
					//如果在发奖时间之后的，则认为全部未发奖
					$newHis[$date] = array($pos, ArenaDef::HAVE);
				}
			}
		}
		else
		{
			//获取最后一条历史数据
			$his = end($oldHis);
			$rank = $his[0];
			for ($k = 1, $i = 1 + $shift - ArenaConf::POS_HIS; $k <= ArenaConf::POS_HIS; $k++, $i++)
		 	{
			 	$sign = $i > 0 ? "+" : "-";
			 	$offset =  $sign . abs($i) . " day";
			 	$date = intval(strftime("%Y%m%d", strtotime($offset, $now)));
			 	//如果存在旧数据，使用旧数据
			 	if (isset($oldHis[$date]))
			 	{
			 		$newHis[$date] = $oldHis[$date];
			 	}
			 	else
			 	{
			 		$newHis[$date] = array($pos, ArenaDef::HAVE);
			 	}
			}
		}
	 	//如果历史最近排名不等于用户当前的排名
	 	if (!empty($rank) && $rank != $pos)
	 	{
			 Logger::warning('uid:%d curr pos:%d is not same with his pos:%d.', $uid, $pos, $rank);
		}
		Logger::trace("refresh pos his:%s", $newHis);
	 	Logger::trace('ArenaLogic::refreshPosHis End.');
		return $newHis;
	}
	
	public static function sendRankReward($uid)
	{
		Logger::trace('ArenaLogic::sendRankReward Start.');
	
		$ret = 'ok';
	
		$info = ArenaDao::get($uid, array('position', 'va_reward'));
		if (empty($info))
		{
			Logger::debug("user is not enter arena, ignore.");
			return $ret;
		}
	
		//刷新用户历史排名数据
		if (!isset($info['va_reward']['his']))
		{
			$info['va_reward']['his'] = array();
		}
		$info['va_reward']['his'] = self::refreshPosHis($uid, $info['position'], $info['va_reward']['his']);
		$oldInfo = $info;
		
		//统计需要发奖的日期,并修改状态
		$i = 0;
		$arrDate = array();
		$position = $info['position'];
		$conf = btstore_get()->ARENA_REWARD;
		foreach ($info['va_reward']['his'] as $date => $his)
		{
			$i++;
			list($pos, $status) = $his;
			$position = min($pos, $position);
			//最后一天的奖不需要发
			if ($i == ArenaConf::POS_HIS)
			{
				break;
			}
			if ($status != ArenaDef::HAVE)
			{
				continue;
			}
			if (!isset($conf[$pos]))
			{
				$info['va_reward']['his'][$date][1] = ArenaDef::NONE;
				continue;
			}
			$info['va_reward']['his'][$date][1] = ArenaDef::REWARD;
			$arrDate[] = $date;
		}
		
		//先更新数据库，然后从lucky表中获取竞技场奖励倍数
		$arrRate = array();
		if (!empty($arrDate)) 
		{
			$info['reward_time'] = Util::getTime();
			unset($info['position']);
			ArenaDao::update($uid, $info);
			EnAchieve::updateArenaRank($uid, $position);
			$arrRate = ArenaLuckyDao::get($arrDate, array('begin_date', 'active_rate'));
			$arrRate = Util::arrayIndexCol($arrRate, 'begin_date', 'active_rate');
		}
	
		//遍历历史排名，发送奖励到奖励中心
		$user = EnUser::getUserObj($uid);
		$maxLevel = max($user->getLevel(), 30);
		foreach ($info['va_reward']['his'] as $date => $his)
		{
			if (!in_array($date, $arrDate)) 
			{ 
				continue;
			}
			//发奖励
			list($pos, $status) = $his;
			$rate = isset($arrRate[$date]) ? $arrRate[$date] : 1;
			//为0的是当天幸运排名发奖之前的状态
			$rate = $rate == 0 ? ArenaLogic::getActiveRate() : $rate;
			$rewardTime = strtotime($date . " " . ArenaDateConf::LOCK_START_TIME);
			$reward = array(
					RewardType::SOUL => $conf[$pos]['soul'] * $maxLevel * $rate,
					RewardType::SILVER => $conf[$pos]['silver'] * $maxLevel * $rate,
					RewardType::ARR_ITEM_TPL => $conf[$pos]['items']->toArray(),
					RewardType::PRESTIGE => $conf[$pos]['prestige'] * $rate,
					RewardDef::EXT_DATA => array('rank' => $pos, 'time' => $rewardTime),
			);
			Logger::trace('send reward to user:%d, reward:%s', $uid, $reward);
			//这个值目前前端没有用到
			$curRound = ArenaLuckyLogic::diffDate(GameConf::SERVER_OPEN_YMD, $date);
			MailTemplate::sendArenaAward($uid, $curRound, $pos, $reward[RewardType::SOUL],
			$reward[RewardType::SILVER], $reward[RewardType::PRESTIGE], $rewardTime, $reward[RewardType::ARR_ITEM_TPL]);
			EnReward::sendReward($uid, RewardSource::ARENA_RANK, $reward);
		}
		
		Logger::trace("reward pos his:%s", $info['va_reward']['his']);
	
		Logger::trace('ArenaLogic::sendRankReward End.');
		
		return $ret;
	}

	/**
	 * 挑战某个排名的用户
	 *
	 * @param int $uid								用户id
	 * @param int $pos								排名
	 * @param int $atkedUid							被挑战用户id
	 * @param int $num								挑战次数1或10
	 * @throws FakeException
	 * @throws SysException
	 * @throws Exception
	 * @return array mixed                        
	 */
	public static function challenge($uid, $pos, $atkedUid, $num)
	{
		Logger::trace('ArenaLogic::challenge Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ARENA) == false)
		{
			throw new FakeException('user:%d does not open the arena', $uid);
		}
	
		if (ArenaRound::isLock())
		{
			throw new FakeException('fail to challenge in arena, the arena is lock for rewarding.');
		}
		if($uid == $atkedUid)
		{
			throw new FakeException('uid:%d challenge himself', $uid);
		}
	
		$arrRet = array('ret' => 'ok');
	
		//这里的lock不能往下移动了，因为拉出来$info数据可能会被其它用户修改
		$alock = new ArenaLock();
		if (false == $alock->lock($uid, $atkedUid))
		{
			$arrRet['ret'] = 'lock';
			return $arrRet;
		}
	
		try
		{
			$info = self::getInfo($uid);
			if (empty($info))
			{
				throw new SysException('fail to query from db by uid %d', $uid);
			}
	
			$user = EnUser::getUserObj($uid);
			self::$a_level = $user->getLevel();
			self::$a_reward = array();
			
			//检查用户挑战次数
			$challengeNum = min($num, $info['challenge_num']);
			if (empty($challengeNum)) 
			{
				throw new FakeException('fail to challenge, the num is not enough');
			}

			//检查用户耐力
			$stamina = $user->getStamina();
			$costStamina = btstore_get()->ARENA_PROPERTIES['fight_cost_stamina'];
			$challengeNum = !empty($costStamina) ? min($challengeNum,intval($stamina/$costStamina)) : $challengeNum;
			if (empty($challengeNum))
			{
				throw new FakeException('user:%d has not enough stamina for:%d', $uid, $costStamina * $challengeNum);
			}
			$user->subStamina($costStamina * $challengeNum);
			
			//检查对手排名
			if (!in_array($pos, $info['va_opponents']))
			{
				$alock->unlock();
				Logger::warning('fail to challenge in arena, the postion is not one of opponents');
				$arrRet['ret'] = 'position_err';
				return $arrRet;
			}
	
			//根据排名得到的用户，这个排名的用户可能已经改变了
			$atkedInfo = self::getAtkedByPos($pos);
			if ($atkedUid != $atkedInfo['uid'])
			{
				$alock->unlock();
				$arrRet['ret'] = 'opponents_err';	
				$arrRet['opponents'] = self::getOpponents($info['va_opponents'], $info['position']);	
				return $arrRet;
			}
			$diffPos = $info['position'] - $atkedInfo['position'];
			if ($diffPos > 0 && $num > 1) 
			{
				throw new FakeException('user:%d can not challenge smaller position more than once', $uid);
			}
			
			//npc特殊处理
			if( self::isNpc($atkedUid) )
			{
				$atkedUser = new NpcUser($atkedUid);
			}
			else
			{
				$atkedUser = EnUser::getUserObj($atkedUid);
			}
			
			//竞技场消息暂时不用
			$arrMsg = array(
					'attack_uid'  => $user->getUid(),
					'attack_name' => $user->getUname(),
					'defend_uid'  => $atkedUser->getUid(),
					'defend_name' => $atkedUser->getUname(),
					'attack_time' => Util::getTime(),
					'attack_position' => $info['position'],
					'defend_position' => $atkedInfo['position'],
					'attack_res'  => 0,
					'attack_replay' => 0,
			);
			
			// 将战斗结果返回给前端,1次有战斗数据,10次只有战斗结果
			$sucNum = 0;
			$arrSuc = array();
			$arrBrid = array();
			$robSilver = array();
			$oldPositoin = $info['position'];
			$oldAtkPosition = $atkedInfo['position'];
			for ($i = 0; $i < $challengeNum; $i++)
			{
				$needRecord = $i == ($challengeNum - 1) ? true : false;
				$atkRet = self::challenge__($user, $atkedUser, $info, $atkedInfo, $needRecord);
				$arrRet['atk'][$i] = array(
						'fightRet' => $atkRet['client'],
						'appraisal' => $atkRet['server']['appraisal'],
						'force' => $atkRet['force']
				);
				if ($num > 1) 
				{
					unset($arrRet['atk'][$i]['fightRet']);
				}
				$arrSuc[$i] = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
				$sucNum = $arrSuc[$i] ? $sucNum + 1 : $sucNum;
				$arrBrid[$i] = $atkRet['server']['brid'];
				$robSilver[$i] = 0;
			}
			$arrMsg['attack_replay'] = $atkRet['server']['brid'];
			$arrMsg['attack_res'] = $arrSuc[count($arrSuc) - 1];
			
			//无论输赢都要更新双方的数据，不一定会交换位置
			Logger::debug("Challenge update atk:%s, atked:%s", $info, $atkedInfo);
			if ($diffPos > 0 && $sucNum)
			{
				ArenaDao::updateChallenge($info, $atkedInfo, $oldPositoin, $oldAtkPosition);
			}
			else
			{
				ArenaDao::insertOrUpdate($info);
				ArenaDao::insertOrUpdate($atkedInfo);
			}
			//早点释放， 后面不会修改info, atkedInfo的数据了
			$alock->unlock();
		}
		catch ( Exception $e )
		{
			Logger::warning('challenge exeception:%s', $e->getMessage());
			$alock->unlock();
			throw $e;
		}
		
		$arrRet['flop'] = array();
		//挑战成功，且打败比我名次高的，双方对手才有变动
		if ($sucNum)
		{
			$arrRet['opponents'] = array();
			
			//打败比我高的，对手有变化的时候才拉对手数据
			if ($diffPos > 0) 
			{
				$arrRet['opponents'] = self::getOpponents($info['va_opponents'], $info['position']);
				//并且给被挑战者发送推送消息
				$sendAtkedInfo = array();
				$sendAtkedInfo['position'] = $atkedInfo['position'];
				$sendAtkedInfo['va_opponents'] = $atkedInfo['va_opponents'];
				//$sendAtkedInfo['arena_msg'] = $arrMsg;
				Logger::debug('sync to client:%s', $sendAtkedInfo);
				if (!self::isNpc($atkedUid)) 
				{
					RPCContext::getInstance()->executeTask($atkedUid, 'arena.arenaDataRefresh', array($sendAtkedInfo));
				}
			}
			
			//翻牌结果展示，1次用户选，10次自动选
			$flopId = btstore_get()->ARENA_PROPERTIES['fight_suc_flop'];
			if(self::isNpc($atkedUid))
			{
				for ($i = 0; $i < $challengeNum; $i++)
				{
					if ($arrSuc[$i]) 
					{
						$flopInfo = EnFlop::flop($uid, 0, $flopId);
						$arrRet['flop'][$i] = $flopInfo['client'];
					}
					else 
					{
						$arrRet['flop'][$i] = array();
					}
				}
			}
			else
			{
				for ($i = 0; $i < $challengeNum; $i++)
				{
					if ($arrSuc[$i]) 
					{
						$flopInfo = EnFlop::flop($uid, $atkedUid, $flopId);
						$arrRet['flop'][$i] = $flopInfo['client'];
						$robSilver[$i] = $flopInfo['server'];
					}
					else 
					{
						$arrRet['flop'][$i] = array();
						$robSilver[$i] = 0;
					}
				}
			}
		}
		Logger::debug('arrSuc:%s arrBrid:%s robSilver:%s', $arrSuc, $arrBrid, $robSilver);
		//给防守方发邮件,npc特殊处理
		if(!self::isNpc($atkedUid))
		{
			//挑战方排名小于被挑战方，且挑战成功，被挑战方名次不变
			if ($diffPos < 0 && $arrMsg['attack_res'])
			{
				MailTemplate::sendArenaRankNotchange($atkedUid, $user->getTemplateUserInfo(), $arrMsg['attack_replay'], $robSilver[count($robSilver) - 1]);
			}
			else
			{
				MailTemplate::sendArenaDefend($atkedUid, $user->getTemplateUserInfo(), !$arrMsg['attack_res'], $atkedInfo['position'], $arrMsg['attack_replay'], $robSilver[count($robSilver) - 1]);
			}
		}
	
		//无论输赢都能得到银币奖励和将魂奖励和经验奖励
		for ($i = 0; $i < $challengeNum; $i++)
		{
			self::computeReward($arrSuc[$i]);
			$user->addSilver(self::$a_reward['silver']);
			$user->addSoul(self::$a_reward['soul']);
			$user->addExp(self::$a_reward['exp']);
			$user->addPrestige(self::$a_reward['prestige']);
		}
		$user->update();
		
		//翻牌更新背包，并且广播
		$updateBag = false;
		for ($i = 0; $i < $challengeNum; $i++)
		{
			if (isset($arrRet['flop'][$i]['real']['item'])) 
			{
				$updateBag = true;
				$itemArr = array($arrRet['flop'][$i]['real']['item']['id'] => $arrRet['flop'][$i]['real']['item']['num']);
				ChatTemplate::sendFlopItem($user->getTemplateUserInfo(), $itemArr, FlopDef::FLOP_TYPE_ARENA);
			}
		}
		if ($updateBag) 
		{
			BagManager::getInstance()->getBag($uid)->update();
		}
		
		//insert msg
		ArenaDao::insertMsg($arrMsg);
		//$arrRet['arena_msg'] = $arrMsg;
		
		//加入每日任务,福利活动，成就和悬赏榜
		EnActive::addTask(ActiveDef::ARENA, $challengeNum);
		EnWeal::addKaPoints(KaDef::ARENA, $challengeNum);
		EnAchieve::updateArena($uid, $challengeNum);
		EnMission::doMission($uid, MissionType::ARENA, $challengeNum);
		if ($sucNum) 
		{
			EnDesact::doDesact($uid, DesactDef::ARENA_SUC, $sucNum);
			if ($diffPos > 0) 
			{
				EnNewServerActivity::updateArenaRank($uid, $info['position']);
			}
		}
		EnFestivalAct::notify($uid, FestivalActDef::TASK_ARENA_NUM, $challengeNum);
		//进行竞技场挑战num次
		EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_ARENA, $challengeNum);
		
		return $arrRet;
	}
	
	/**
	 * 挑战
	 *
	 * @param UserObj $user							挑战用户
	 * @param UserObj $atkedUser					被挑战用户
	 * @param array $info							挑战用户信息
	 * @param array	$atkedInfo						被挑战用户信息
	 * @param int $needRecord						是否需要战报
	 * @throws Exception
	 * @return $atkRet 								战斗结果
	 */
	private static function challenge__($user, $atkedUser, &$info, &$atkedInfo, $needRecord)
	{
		Logger::trace('ArenaLogic::challenge__ Start.');
		
		$battleUser = $user->getBattleFormation();
		$atkedBattleUser = $atkedUser->getBattleFormation();
		
		$userFF = $user->getFightForce();
		$atkedUserFF = $atkedUser->getFightForce();
		Logger::trace('user fight force is:%d, atked user fight force is:%d', $userFF, $atkedUserFF);
		
		$type = EnBattle::setFirstAtk(0, $userFF >= $atkedUserFF);
		$atkRet = EnBattle::doHero($battleUser, $atkedBattleUser, $type, array('ArenaLogic', 'battleCallback'), array(), array('needRecord' => $needRecord));
		Logger::debug('Ret from battle is %s.', $atkRet);
		$atkRet['force'] = $atkedUserFF;
		
		//更新竞技信息
		$info['challenge_num'] -= 1;
		$info['challenge_time'] = Util::getTime();
	
		$diffPos = $info['position'] - $atkedInfo['position'];
		$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		//打败对方
		if ($isSuc)
		{
			//打败比我名次高的
			if ($diffPos > 0)
			{
				//更新双方用户的历史排名信息,不用更新机器人信息
				if (!isset($info['va_reward']['his']))
				{
					$info['va_reward']['his'] = array();
				}
				$info['va_reward']['his'] = self::refreshPosHis($info['uid'], $info['position'], $info['va_reward']['his']);
				if(!self::isNpc($atkedInfo['uid']))
				{
					if (!isset($atkedInfo['va_reward']['his']))
					{
						$atkedInfo['va_reward']['his'] = array();
					}
					$atkedInfo['va_reward']['his'] = self::refreshPosHis($atkedInfo['uid'], $atkedInfo['position'], $atkedInfo['va_reward']['his']);
				}
				
				$atkedInfo['position'] += $diffPos;
				$info['position'] -= $diffPos;
				//第一名广播
				if ($info['position'] == 1)
				{	 
					ChatTemplate::sendArenaTopChange($user->getTemplateUserInfo(), $atkedUser->getTemplateUserInfo());
				}
				
				$atkedInfo['va_opponents'] = self::getOpponentPosition($atkedInfo['position']);
				$info['va_opponents'] = self::getOpponentPosition($info['position']);
				$info['upgrade_continue'] += $diffPos;
				if ($info['min_position'] > $info['position'])
				{
					$info['min_position'] = $info['position'];
				}
				
				//更新排名,不用更新机器人信息
				$now = Util::getTime();
				$today = intval(strftime("%Y%m%d", $now));
				$rewardTime = strtotime($today . " " . ArenaDateConf::LOCK_START_TIME);
				$shift = $now > $rewardTime ? 1 : 0;
				$offset =  "+" . $shift . " day";
				$date = intval(strftime("%Y%m%d", strtotime($offset, $now)));
				$info['va_reward']['his'][$date][0] = $info['position'];
				if(!self::isNpc($atkedInfo['uid']))
				{
					$atkedInfo['va_reward']['his'][$date][0] = $atkedInfo['position'];
				}
			}
	
			//防守方连胜数据修改
			$atkedInfo['cur_suc'] = 0;
			$atkedInfo['upgrade_continue'] = 0;
			
			//攻击方连胜加1,连胜场次更新
			$info['cur_suc'] += 1;
			if ($info['cur_suc'] > $info['max_suc'])
			{
				$info['max_suc'] = $info['cur_suc'];
			}
		}
		else //攻击方输了
		{		
			//攻击方连胜终止
			$info['cur_suc'] = 0;
			$info['upgrade_continue'] = 0;
	
			//防守方连胜加1
			$atkedInfo['cur_suc'] += 1;
			if ($atkedInfo['cur_suc'] > $atkedInfo['max_suc'])
			{
				$atkedInfo['max_suc'] = $atkedInfo['cur_suc'];
			}
		}
	
		Logger::trace('ArenaLogic::challenge__ End.');
		return $atkRet;
	}
	
	public static function getAtkedByPos($pos)
	{
		return ArenaDao::getByPos($pos, self::$arrAtkedField);
	}
	
	public static function battleCallback($atkRet)
	{
		$isSuc = BattleDef::$APPRAISAL[$atkRet['appraisal']] <= BattleDef::$APPRAISAL['D'];
		self::computeReward($isSuc);
		return self::$a_reward;
	}

    /**
     * 获取竞技场排行榜前十
     * 
     * @param int $num								排行榜数量
     * @returnarray $arrList						排行榜
     */
    public static function getRankList($uid, $num)
    {
    	Logger::trace('ArenaLogic::getRankList Start.');
    	
    	if (EnSwitch::isSwitchOpen(SwitchDef::ARENA) == false)
    	{
    		throw new FakeException('user:%d does not open the arena', $uid);
    	}
    	
    	// 前10的数组
    	$arrPos = range(1, $num);
    	
    	$arrRet = self::getOpponents($arrPos);
    	
		Logger::trace('ArenaLogic::getRankList End.');
		
		return $arrRet;
    }

    /**
     * 暂时不用这个接口
     * @param unknown $uid
     * @return Ambigous <number, multitype:mixed >
     */
	public static function getArenaMsg($uid)
	{
		return ArenaDao::getMsg($uid, self::$arrMsgField, ArenaConf::FIGHT_MSG_NUM);
	}

	/**
	 * 根据用户排名获取对手们的排名
	 * 
	 * @param int $pos								用户排名
	 * @throws Exception
	 * @return array $arrRet 						对手们的排名数组
	 */
	public static function getOpponentPosition($pos)
	{
		Logger::trace('ArenaLogic::getOpponentPosition Start.');
		//当前位置前取8个
		$beforNum = ArenaConf::OPPONENT_BEFOR;	
		//当前位置后取2个
		$afterNum = ArenaConf::OPPONENT_AFTER;
		//对手的数量
		$opptNum = $beforNum + $afterNum;
		
		$arrRet = array();
		//目前竞技场上总人数
		$count = self::getArenaCount();
		Logger::trace('total %d users in arena!', $count);
		//如果场上人数不够10人
		if ($count <= $opptNum) 
		{
			if ($count == 0) 
			{
				return array(0,0,0,0,0,0,0,0,0,0);
			}
			//取得当前场上排名最后的一个人的信息
			$opptPos = ArenaDao::getByPos($count, array('va_opponents'));
			$arrRet = $opptPos['va_opponents'];
			$arrRet[$count - 1] = $count;
			return $arrRet;	
		}

        if ($pos > $count+1)
        {
            throw new SysException('fail to get opponents position, the pos %d must be <= 1+ the total of arena %d', $pos, $count);
        }

		//小于100的取前8后2
		if ($pos <= 100)
		{
			$min = $pos - $beforNum;
			$max = $pos + $afterNum;
		}
		else //大于100的从前后10%里面取
		{
			$min = intval($pos * 0.9);
			$max = intval($pos * 1.1);
		}
		
		//不小于1
		if ($min <= 0)
		{
			$min = 1;
		}
		//不超过总数
		if ($max > $count)
		{
			$max = $count;
		}

        //前段区间小于需要的数量
		if ($pos <= $beforNum)
		{
			$beforNum = $pos - 1;
			$afterNum = $opptNum - $beforNum;
		}
		//后段区间小于需要的数量
		if ($count - $pos < $afterNum)
		{
            //新用户是最后一个，$pos大于$count.
			$afterNum = $count - $pos;
            if ($afterNum < 0)
            {
                $afterNum = 0;
            }
			$beforNum = $opptNum - $afterNum;
		}
		
		// 如果小于100
		if ($pos <= 100) 
		{
			$arrRet = array();
			if ($beforNum != 0)
			{
				for ($i = $pos - $beforNum; $i <= $pos - 1; $i++)
				{
					$beforArr[] = $i;
				}
				$arrRet = $beforArr;
			}
			if ($afterNum != 0)
			{
				for ($i = $pos + 1; $i <= $pos + $afterNum; $i++)
				{
					$afterArr[] = $i;
				}
				$arrRet = array_merge($arrRet, $afterArr);
			}
		}
		else 
		{
			$arrRet = array();
			if ($beforNum != 0)
			{
				$beforArr = self::getRandSeq($min, $pos - 1, $beforNum);
				$arrRet = $beforArr;
			}
			if ($afterNum != 0)
			{
				$afterArr = self::getRandSeq($pos + 1, $max, $afterNum);
				$arrRet = array_merge($arrRet, $afterArr);
			}
		}
		sort($arrRet);
		
		Logger::trace('getOpponentPosition end. min:%d, max:%d, ret:%s', $min, $max, $arrRet);	
		return $arrRet;
	}
	
	/**
	 * 根据上下限，得到一组随机数
	 *
	 * @param int $min								下限
	 * @param int $max								上限
	 * @param int $num								数量
	 * @return array $arrRet						一组随机数
	 */
	private static function getRandSeq ($min, $max, $num)
	{
		Logger::trace('ArenaLogic::getRandSeq Start.');
	
		if ($min > $max || $num <= 0 || $max - $min + 1 < $num)
		{
			throw new FakeException('Err param, min:%d max:%d num:%d', $min, $max, $num);
		}
	
		$arrRet = array();
		for ($i = 0; $i < $num; $i++)
		{
			$x = mt_rand($min, $max);
			//如果这个数取过了，就取紧挨着的下一个数
			while (in_array($x, $arrRet))
			{
				if (++$x > $max)
				{
					$x = $min;
				}
			}
			$arrRet[] = $x;
		}
		Logger::debug('rand seq is %s', $arrRet);
		Logger::trace('ArenaLogic::getRandSeq End.');
		return $arrRet;
	}
	
	/**
	 * 根据对手排名获取对手信息
	 *
	 * @param array $arrPos							所有对手排名
	 * @param int $pos								要插入的排名
	 * @return array $arrRet 						所有对手信息
	 */
	public static function getOpponents($arrPos, $pos = 0)
	{
		Logger::trace('ArenaLogic::getOpponents Start.');
	
		if (!empty($pos)) 
		{
			$arrPos[] = $pos;
			Logger::trace('position array :%s.', $arrPos);
		}
		
		$arrRet = ArenaDao::getArrByPos($arrPos, array('uid', 'position'));
		//npc处理
		$arrPosUid = Util::arrayIndexCol($arrRet, 'position', 'uid');
		$arrUid = array();
		foreach($arrPos as $k => $p)
		{
			if($p == 0)
			{
				unset($arrPos[$k]);
				continue;
			}
			//数据库中没有这个位置，就认为是npc
			if( empty($arrPosUid[$p]) )
			{
				if( self::isNpcPos($p) )
				{
					$arrPosUid[$p] = self::npcPos2Uid($p);
				}
				else
				{
					Logger::debug('none in pos:%d', $p);
				}
			}
			else if( ! self::isNpc( $arrPosUid[$p] ) )
			{
				$arrUid[] = $arrPosUid[$p];
			}
		}
	
		if (!empty($arrUid)) 
		{
			$arrUser = EnUser::getArrUser($arrUid, array('uid', 'utid', 'uname', 'level', 'vip', 'fight_force', 'guild_id'));
			$arrSquad = EnUser::getArrUserSquad($arrUid);
			$arrGuildId = Util::arrayExtract($arrUser, 'guild_id');
			$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));
		}
	
		$arrRet = array();
		foreach($arrPosUid as $pos => $uid )
		{
			if( self::isNpc($uid) )
			{
				$npc = new NpcUser($uid);
				$squad = EnFormation::getMonsterSquad( self::getNpcArmyId($uid) );
				$arrRet[$pos] = array(
						'uid' => $uid,
						'utid' => self::getNpcUtid($uid),
						'level'=> self::getNpcLevel(),
						'vip' => self::getNpcVip(),
						'uname' => self::getNpcName($uid),
						'position' => $pos,	
						'squad' => array_slice($squad, 0, 4),	
						'armyId' => self::getNpcArmyId($uid),
						'fight_force' => $npc->getFightForce(),			
						);
			} 
			else
			{
				$user = $arrUser[$uid];
				$user['position'] = $pos;
				$user['squad'] = array_slice($arrSquad[$uid], 0, 4);
				$user['armyId'] = 0;
				$guildId = $user['guild_id'];
				unset($user['guild_id']);
				if (!empty($guildId) && !empty($arrGuildInfo[$guildId][GuildDef::GUILD_NAME]) ) 
				{
					$user[GuildDef::GUILD_NAME] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
				}
				$arrRet[$pos] = $user;
			}
		}
		ksort($arrRet);
	
		Logger::trace('ArenaLogic::getOpponents End.');
		return $arrRet;
	}	
	
	
	/**
	 * 活动或者补偿， 发奖的倍率
	 */
	public static function getActiveRate()
	{
		list($begin, $end, $rate) = self::getActive();
		$mergeRate = EnMergeServer::getArenaPrestigeRewardRate();
		return max($rate, $mergeRate);
	}
	
	public static function getActive()
	{
		try
		{
			if( EnActivity::isOpen(ActivityName::ARENA_REWARD) )
			{			
				$conf = EnActivity::getConfByName(ActivityName::ARENA_REWARD);
				return array($conf['start_time'], $conf['end_time'], 2);//TODO:目前没有配置表，只能2倍				
			}
		}
		catch (Exception $e)
		{
			Logger::warning('cant get conf of arena reward');
		}
				
		return array(0, 0, 1);
	}
	
	public static function isNpc($uid)
	{
		return $uid >= SPECIAL_UID::MIN_ARENA_NPC_UID && $uid <= SPECIAL_UID::MAX_ARENA_NPC_UID;
	}
	public static function isNpcPos($pos)
	{
		return $pos <= ArenaConf::NPC_NUM;
	}
	public static function npcPos2Uid($pos)
	{
		if($pos > ArenaConf::NPC_NUM)
		{
			throw new FakeException('pos:%d not npc pos', $pos);
		}
		return SPECIAL_UID::MIN_ARENA_NPC_UID + $pos - 1;
	}
	public static function getNpcUtid($uid)
	{
		return $uid % 2 + 1;
	}
	public static function getNpcLevel()
	{
		return rand(15, 18);
	}
	public static function getNpcVip()
	{
		return 0;
	}
	public static function getNpcName($uid)
	{
		return intval( ($uid - SPECIAL_UID::MIN_ARENA_NPC_UID)/2 );
	}
	public static function getNpcArmyId($uid)
	{
		$conf = btstore_get()->ARENA_PROPERTIES;
		
		$utid = self::getNpcUtid($uid);
		if (1 == $utid) 
		{
			$armyGroup = $conf['female_army_group'];
		}
		if (2 == $utid)
		{
			$armyGroup = $conf['male_army_group'];
		}
		
		if( count($armyGroup) == 0 )
		{
			throw new ConfigException('no npc in arena. utid:%d', $utid);
		}
		
		$id = ($uid - SPECIAL_UID::MIN_ARENA_NPC_UID + Util::getServerId()) % count($armyGroup);
			
		return $armyGroup[$id];
	}
	public static function getNpcDefValue($pos, $arrField)
	{
		if( !self::isNpcPos($pos) )
		{
			Logger::trace('pos:%d not npc default pos', $pos);
			return array();
		}
			
		$arrDefValue = array(
				'uid' => self::npcPos2Uid($pos),
				'position' => $pos,
				'challenge_num' => 0,
				'challenge_time' => 0,
				'cur_suc' => 0,
				'max_suc' => 0,
				'min_position' => $pos,
				'upgrade_continue' => 0,
				'va_opponents' => array(),
				'reward_time' => 0,
				'va_reward' => array()
				);
		$returnData = array();
		foreach($arrField as $key)
		{
			$returnData[$key] = $arrDefValue[$key];
		}
		Logger::debug('get npc default value. pos:%d, ret:%s', $pos,$returnData);
		return $returnData;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */