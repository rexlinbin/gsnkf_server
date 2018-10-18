<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Bot.class.php 86911 2014-01-15 05:12:57Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/Bot.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-15 05:12:57 +0000 (Wed, 15 Jan 2014) $
 * @version $Revision: 86911 $
 * @brief 
 *  
 **/
class Bot
{
	public function rewardForBotList($boss_id, $boss_level,
			$boss_start_time, $boss_end_time, $kill_time)
	{
		sleep(BossConf::BOSS_REWARD_SLEEP_TIME);
		$boss_bot_list = BossUtil::getBossBotList($boss_id, $boss_start_time, $boss_end_time);
	
		foreach ( $boss_bot_list as $value )
		{
			$uid = $value[BossDef::UID];
			$flags = $value[BossDef::FLAGS];
			RPCContext::getInstance()->executeTask($uid, 'boss.rewardForBot',
			array($uid, $boss_id, $boss_level, $boss_start_time, $boss_end_time, $kill_time, $flags));
		}
	}
	
	
	public function rewardForBot($uid, $boss_id, $boss_level,
			$boss_start_time, $boss_end_time, $kill_time, $flags)
	{
		if ( RPCContext::getInstance()->getUid() == NULL )
		{
			RPCContext::getInstance()->setSession('global.uid', $uid);
		}
		$attack_hp_once = $this->attackBossForBot($boss_id);
		$user = EnUser::getUserObj();
		$user_level = $user->getLevel();
		$boss_max_hp = BossUtil::getBossMaxHp($boss_id, $boss_level);
	
		$gold = $user->getGold();
	
		if ( $kill_time > $boss_end_time )
		{
			$kill_time = $boss_end_time;
		}
	
		$duration_time = $kill_time - $boss_start_time;
		$sub_attack_time = 0;
		$sub_attack_time_gold = 0;
		if ( Boss::isSetBossBotSubTimeFlags($flags) == TRUE )
		{
			$sub_attack_time = min(intval($gold/BossConf::SUB_CDTIME_REQ_GOLD),
					intval($duration_time/BossConf::BOSS_BOT_SUB_ATTACK_TIME));
	
			$sub_attack_time_gold = $sub_attack_time * BossConf::SUB_CDTIME_REQ_GOLD;
			$user->subGold($sub_attack_time_gold);
		}
	
		$normal_attack_time = intval(($duration_time - $sub_attack_time * BossConf::BOSS_BOT_SUB_ATTACK_TIME)
				/ BossConf::BOSS_BOT_ATTACK_TIME);
	
		$total_attack_time = $normal_attack_time + $sub_attack_time;
	
		$total_attack_hp = $total_attack_time * $attack_hp_once;
	
		$attack_list = BossUtil::getBossAttackListSorted($boss_id, $boss_start_time, $boss_end_time);
		$order = BossConf::BOSS_BOT_ORDER_EXCURSION;
		foreach ( $attack_list as $key => $value )
		{
			if ( $total_attack_hp >= $value )
			{
				break;
			}
			$order++;
		}
	
		$attack_belly = self::bellyRewardPreAttack($user_level, $boss_id) * $total_attack_time;
		$attack_experience = self::experienceRewardPerAttack($user_level, $boss_id) * $total_attack_time;
		$attack_prestige = self::prestigeRewardPerAttack($user_level, $boss_id,
				$boss_level, $attack_hp_once) * $total_attack_time;
	
		//增加belly
		if ( $user->addBelly($attack_belly) == FALSE )
		{
			Logger::FATAL('attack callback add belly failed!');
		}
	
		//增加阅历
		if ( $user->addExperience($attack_experience) == FALSE )
		{
			Logger::FATAL('attack callback add experience failed');
		}
	
		//增加声望
		if ( $user->addPrestige($attack_prestige) == FALSE )
		{
			Logger::FATAL('attack callback add prestige failed');
		}
	
		$attack_group = BossUtil::getBossAttackHpGroup($boss_id, $boss_start_time, $boss_end_time);
		$group_id = $user->getGroupId();
		$reward_modulus = self::getRewardModulus($attack_group, $group_id);
		$reward = self::rewardUser($user->getUid(), $boss_id, $boss_level,$order, $reward_modulus);
	
		//send mail
		if ( Boss::isSetBossBotSubTimeFlags($flags) == TRUE )
		{
			MailTemplate::sendBossBotSubTime($uid, $boss_id, $total_attack_hp,
			BossUtil::getBossAttackHPPrecent($total_attack_hp, $boss_max_hp),
			$order, $attack_experience, $attack_prestige, $reward,
			$sub_attack_time, $sub_attack_time_gold);
		}
		else
		{
			MailTemplate::sendBossBot($uid, $boss_id, $total_attack_hp,
			BossUtil::getBossAttackHPPercent($total_attack_hp, $boss_max_hp),
			$order, $attack_experience, $attack_prestige, $reward);
		}
	
		//update Item
		if ( !empty($reward[BossDef::REWARD_ITEMS]) )
		{
			ItemManager::getInstance()->update();
		}
	
		//update user
		$user->update();
	
		//在线用户，推到前端
		if ($user->isOnline())
		{
			$array = array(
					'gold_num' => $user->getGold(),
					'belly_num' => $user->getBelly(),
					'experience_num' => $user->getExperience(),
					'prestige_num' => $user->getPrestige(),
			);
	
			RPCContext::getInstance()->sendMsg(array($uid), 're.user.updateUser', $array);
		}
	
		//统计
		Statistics::gold(StatisticsDef::ST_FUNCKEY_BOSS_BOT_SUBCDTIME,
		$sub_attack_time_gold,
		Util::getTime());
	
		Logger::INFO('user:%d in boss:%d at time:%d boss bot end!', $uid, $boss_id, $boss_start_time);
		Logger::INFO('user:%d total_attack_hp:%d order:%d sub_attack_time:%d sub_attack_time_gold:%d',
		$uid, $total_attack_hp, $order, $sub_attack_time, $sub_attack_time_gold);
	}
	
	private function attackBossForBot($boss_id)
	{
		$user = EnUser::getUserObj();
		$userFormation = EnFormation::getFormationInfo();
		// 将阵型ID设置为用户当前默认阵型
		$formationID = $user->getCurFormation();
		$battleInfo = $user->getBattleInfo(true);
	
		//是否使用保存的阵型
		if (empty($this->m_boss_attack)||!isset($this->m_boss_attack[BossDef::BOSS_SQL_VAINFO]))
		{
			$this->m_uid=$user->getUid();
			$this->m_boss_attack = $this->getUserBossAttackInfo($boss_id);
		}
		if ($this->canUseSavedFormationForBot())
		{
			$battleInfo=$this->m_boss_attack['va_info']['formation'];
		}
	
		$battle_user=array(
				'uid' => $user->getUid(),
				'name' => $user->getUname(),
				'level' => $user->getLevel(),
				'flag' => 0,
				'formation' => $formationID,
				'isPlayer' => 1,
				'arrHero' => $battleInfo['info']
		);
	
	
		$armyID = BossUtil::getBossArmyId($boss_id);
	
		//如果boss变身了，则更改armyid
		$boss_info = BossDAO::getBoss($boss_id);
		$boss_level = $boss_info[BossDef::BOSS_LEVEL];
		if (BossUtil::canChangBody($boss_id, $boss_level))
		{
			$armyID=BossUtil::getArmyIdByBossLevel($boss_id, $boss_level);
		}
		$teamID = btstore_get()->ARMY[$armyID]['monster_list_id'];
	
		// 敌人信息
		$enemyFormation = BossUtil::getBossFormationInfo($boss_id, $boss_level);
	
		// 将对象转化为数组
		$enemyFormationArr = EnFormation::changeForObjToInfo($enemyFormation);
	
		// 调用战斗模块
		$bt = new Battle();
		$maxTime = 2;
		$costHp = 0;
		for ( $i = 0; $i < $maxTime; $i++ )
		{
			$attack_ret = $bt->doHero($battle_user,
					array(
							'uid' => $armyID,
							'name' => btstore_get()->ARMY[$armyID]['name'],
							'level' => $boss_level,
							'flag' => 0,
							'formation' => btstore_get()->TEAM[$teamID]['fid'],
							'isPlayer' => 0,
							'arrHero' => $enemyFormationArr),
					0,
					NULL,
					array('attackRound' => BossConf::BATTLE_ROUND),
					array (
							'bgid' => btstore_get()->ARMY[$armyID]['background_id'],
							'musicId' => btstore_get()->ARMY[$armyID]['music_path'],
							'type' => BattleType::BOSS,
					)
			);
			$costHp += $attack_ret['server']['team2'][0]['costHp'];
		}
	
		return $costHp / $maxTime;
	}
	
	private function setBossBotFlags($flags, $sub_cd)
	{
		if ( $sub_cd )
		{
			$flags |= BossDef::FLAGS_BOT_SUB_CD_TIME;
		}
	
		$flags |= BossDef::FLAGS_BOT;
	
		return $flags;
	}
	
	private function unsetBossBotFlags($flags)
	{
		$flags = $flags & (~BossDef::FLAGS_BOT_SUB_CD_TIME);
		$flags = $flags & (~BossDef::FLAGS_BOT);
	
		return $flags;
	}
	
	private function isSetBossBotFlags($flags)
	{
		if ( $flags & BossDef::FLAGS_BOT )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	private function isSetBossBotSubTimeFlags($flags)
	{
		if ( $flags & BossDef::FLAGS_BOT_SUB_CD_TIME )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/* (non-PHPdoc)
	 * @see IBoss::setBossBot()
	*/
	public function setBossBot($boss_id, $sub_cd,$blformation)
	{
		$boss_id = intval($boss_id);
		$sub_cd = intval($sub_cd);
		$uid = RPCContext::getInstance()->getUid();
	
		$return = FALSE;
	
		if ( BossUtil::isBossTown($boss_id) == FALSE )
		{
			Logger::WARNING('invalid boss id:%d', $boss_id);
			return $return;
		}
	
		//check time
		$next_boss_start_time = BossUtil::getBossStartTime($boss_id);
		$before_boss_end_time = BossUtil::getBeforeBossEndTime($boss_id);
		if ( Util::getTime() < $before_boss_end_time + BossConf::BOSS_BOT_SET_TIME_SUFFIX ||
		Util::getTime() > $next_boss_start_time - BossConf::BOSS_BOT_SET_TIME_PRE )
		{
			Logger::WARNING('in invalid time!before_boss_end_time:%d next_boss_start_time:%d',
			$before_boss_end_time, $next_boss_start_time);
			return $return;
		}
	
		$this->bossUserAttackInfo($boss_id);
		$flags = $this->m_boss_attack[BossDef::FLAGS];
		if ( $this->isSetBossBotFlags($flags) == TRUE )
		{
			Logger::WARNING('already set boss bot in boss:%d', $boss_id);
			return $return;
		}
	
		$flags = $this->setBossBotFlags($flags, $sub_cd);
	
		$user = EnUser::getUserObj();
		$vip_level = $user->getVip();
		$gold = btstore_get()->VIP[$vip_level]['boss_atk_gold'];
		if ( $gold == 0 )
		{
			Logger::WARNING('vip level:%d not open boss bot!', $vip_level);
			return $return;
		}
	
		$boss_start_time = BossUtil::getBossStartTime($boss_id);
	
		BossDAO::setBossAttack($boss_id, $uid, $boss_start_time,
		NULL, NULL, NULL, NULL, $flags, NULL, NULL);
	
		//使用保存的阵型
		$formation=$this->m_boss_attack[BossDef::BOSS_SQL_VAINFO]['formation'];
		if ($blformation && !empty($formation))
		{
			$this->setFormationStatus($boss_id, $blformation,BossDef::BOSS_FORMATION_SAVE_BOT);
		}
	
		return TRUE;
	}
	
	public function unsetBossBot($boss_id)
	{
		$boss_id = intval($boss_id);
		$uid = RPCContext::getInstance()->getUid();
	
		$return = FALSE;
	
		if ( BossUtil::isBossTown($boss_id) == FALSE )
		{
			Logger::WARNING('invalid boss id:%d', $boss_id);
			return $return;
		}
	
		//check time
		$next_boss_start_time = BossUtil::getBossStartTime($boss_id);
		$before_boss_end_time = BossUtil::getBeforeBossEndTime($boss_id);
		if ( Util::getTime() < $before_boss_end_time + BossConf::BOSS_BOT_SET_TIME_SUFFIX ||
		Util::getTime() > $next_boss_start_time - BossConf::BOSS_BOT_SET_TIME_PRE )
		{
			Logger::WARNING('in invalid time!before_boss_end_time:%d next_boss_start_time:%d',
			$before_boss_end_time, $next_boss_start_time);
			return $return;
		}
	
		$this->bossUserAttackInfo($boss_id);
		$flags = $this->m_boss_attack[BossDef::FLAGS];
		if ( $this->isSetBossBotFlags($flags) == FALSE )
		{
			Logger::WARNING('not set boss bot in boss:%d', $boss_id);
			return $return;
		}
	
		$flags = $this->unsetBossBotFlags($flags);
	
		BossDAO::setBossAttack($boss_id, $uid, $before_boss_end_time,
		NULL, NULL, NULL, NULL, $flags, NULL, NULL);
	
		$this->setFormationStatus($boss_id,0,BossDef::BOSS_FORMATION_SAVE_BOT);
	
		return TRUE;
	}
	
	/* (non-PHPdoc)
	 * @see IBoss::getBossBot()
	*/
	public function getBossBot($boss_id)
	{
		//格式化输入
		$boss_id = intval($boss_id);
	
		$return = array(
				'set_status' => 1,
		);
		//check time
		$next_boss_start_time = BossUtil::getBossStartTime($boss_id);
		$before_boss_end_time = BossUtil::getBeforeBossEndTime($boss_id);
		if ( Util::getTime() < $before_boss_end_time + BossConf::BOSS_BOT_SET_TIME_SUFFIX ||
		Util::getTime() > $next_boss_start_time - BossConf::BOSS_BOT_SET_TIME_PRE )
		{
			$return = array(
					'set_status' => 2,
			);
		}
		$before_boss_end_time = BossUtil::getBeforeBossEndTime($boss_id);
		if ( Util::getTime() < $before_boss_end_time + BossConf::BOSS_BOT_SET_TIME_SUFFIX &&
		Util::getTime() >= $before_boss_end_time )
		{
			$return = array(
					'set_status' => 3,
			);
			return $return;
		}
	
		$this->bossUserAttackInfo($boss_id);
	
		$flags = $this->m_boss_attack[BossDef::FLAGS];
	
		$return[BossDef::BOT] = self::isSetBossBotFlags($flags);
		$return[BossDef::BOT_SUB_CDTIME] = self::isSetBossBotSubTimeFlags($flags);
		$return['savedFormation'] = !empty($this->m_boss1_attack[BossDef::BOSS_SQL_VAINFO]['formation']);//是否保存阵型
		$return['isUseFormation'] = $this->canUseSavedFormationForBot();//自动参与时是否使用保存的阵型
		return $return;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */