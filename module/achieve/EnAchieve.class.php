<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnAchieve.class.php 148392 2014-12-23 06:05:09Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/EnAchieve.class.php $
 * @author $Author: ShijieHan $(wuqilin@babeltime.com)
 * @date $Date: 2014-12-23 06:05:09 +0000 (Tue, 23 Dec 2014) $
 * @version $Revision: 148392 $
 * @brief 
 *  
 **/

class EnAchieve
{
	
	/**
	 * 成就通知方法
	 *
	 * @param int $uid							用户ID
	 * @param int $type							成就类型 @see AchieveDef
	 * @param int $value_1						参数1
	 * @param int $value_2						参数2
	 */
	static public function notify($uid, $type, $value1, $value2 = NULL)
	{
		// 如果是当前用户，那么进行简单的处理方式
		if (RPCContext::getInstance()->getUid() == $uid)
		{
			// 通知当前用户的成就系统
			return self::doNotify($uid, $type, $value1, $value2);
		}
		// 非当前用户
		else
		{
			throw new InterException('not support notify other achieve');
		}
	}
	
	static public function doNotify($uid, $type, $value1, $value2)
	{
		$guid = RPCContext::getInstance()->getUid();
		if($guid <= 0 || $guid != $uid )
		{
			throw new FakeException('invalid uid. guid:%d, uid:%d', $guid, $uid);
		}
		
		//FIXME:  目前只支持名将总星数成就。 $value1 是当前值, $value2是历史值
		//目前成就系统不存数据，所以需要名将系统传入历史值
		if($type != AchieveDef::STAR_ALL_FAVOR)
		{
			throw new InterException('only support type:AchieveDef::STAR_ALL_FAVOR');
		}
		$newAllFavor = $value1;
		$oldAllFavor = $value2;
		if( $newAllFavor <= $oldAllFavor)
		{
			Logger::warning('newAllFavor:%d <= oldAllFavor:%d', $newAllFavor, $oldAllFavor);
			return;
		}
		
		$userObj = EnUser::getUserObj($uid);
		$arrConf = btstore_get()->ACHIEVE;
		foreach($arrConf as $id => $conf)
		{
			if( $oldAllFavor < $conf['arrCond'][0]
					&& $newAllFavor >= $conf['arrCond'][0] )
			{
				Logger::info('add achieve:%d', $id);
				if( !empty($conf['staminaMaxNum']) )
				{
					$userObj->addStaminaMaxNum($conf['staminaMaxNum']);
				}
                if( !empty($conf['executionMaxNum']))
                {
                    $userObj->addExecutionMaxNum($conf['executionMaxNum']);
                }
			}
		}
		
	}
	
    /**
     * 初始化名将好感度，体力上限加成数值
     */
    public static function initHisExecutionNum($uid)
    {
        $allStarFavor = EnStar::getAllStarFavor($uid);
        $arrConf = btstore_get()->ACHIEVE;
        $hisExecutionNum = 0;
        foreach($arrConf as $id => $conf)
        {
            if($conf['arrCond'][0] <= $allStarFavor)
            {
                $hisExecutionNum += $conf['executionMaxNum'];
            }
        }
        return $hisExecutionNum;
    }

	/**
	 * 获取成就系统的战斗属性加成
	 * @param int $uid
	 */
	static public function getAddAttrByAchieve($uid)
	{
		$arrId = AchieveLogic::getArrAchieveId($uid);
		$arrConf = btstore_get()->ACHIEVE;
		
		$arrAddAttr = array();
		foreach($arrId as $id)
		{
			foreach( $arrConf[$id]['arrAddAttr'] as $k => $v)
			{
				if( isset($arrAddAttr[$k]) )
				{
					$arrAddAttr[$k] += $v;
				}
				else
				{
					$arrAddAttr[$k] = $v;
				}
			}
		}
		
		$arrRet = HeroUtil::adaptAttr( $arrAddAttr );
		Logger::trace('getAddAttrByAchieve. uid:%d, arr:%s', $uid, $arrRet);
		return $arrRet;
	}

    /**
     * 返回开始上榜的最大的排名
     */
    public static function getMaxRank($type)
    {
        if(!in_array($type, AchieveDef::$DESC_TYPES))
        {
            Logger::debug('invalid rank type');
            return;
        }
        $confs = AchieveObj::getTypeConf($type);
        $maxRank = 0;
        foreach($confs as $aid => $conf)
        {
            $finishNum = AchieveDef::MAX_BOSS_RANK - $conf[AchieveDef::CONF_FINISH_NUM];
            if($finishNum > $maxRank)
            {
                $maxRank = $finishNum;
            }
        }
        return $maxRank;
    }

	public static function setAchieveStatus($uid, $aid, $status)
	{
		AchieveObj::getObj($uid)->setStatus($aid, $status);
	}
	
	public static function setAchieveFinish($uid, $aid)
	{
		AchieveObj::getObj($uid)->setStatus($aid, AchieveDef::STATUS_FINISH);
	}
	
	public static function setAchieveUnfinish($uid, $aid)
	{
		AchieveObj::getObj($uid)->setStatus($aid, AchieveDef::STATUS_WAIT);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param unknown $finish_type 副本id
	 * @desc 101.通关某普通副本
	 */
	public static function updatePassNCopy($uid, $finish_type)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::PASS_NCOPY, $finish_type, 1);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param unknown $starNum 普通副本总星星数
	 * @desc 102.达到的副本星数，
	 */
	public static function updateNCopyScore($uid, $starNum)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::NCOPY_STAR, 0, $starNum);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param unknown $finish_type 精英副本id
	 * @desc 103.通关某精英副本 
	 */
	public static function updatePassECopy($uid, $finish_type)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::PASS_ECOPY, $finish_type, 1);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 对摇钱树的伤害
	 * @desc 104.攻击摇钱树伤害达到某数值，
	 */
	public static function updateGoldTree($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::GOLD_TREE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param unknown $finish_type 军团副本id
	 * @desc 105.通关某军团副本，
	 */
	public static function updateGuildCopy($uid, $finish_type)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::GUILD_COPY, $finish_type, 1);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 竞技场排名
	 * @desc 106.竞技场排名达到多少名，
	 */
	public static function updateArenaRank($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::ARENA_RANK, 0, AchieveDef::MAX_BOSS_RANK - $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 比武排名
	 * @desc 107.比武排名达到多少名
	 */
	public static function updateCompeteRank($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::COMPETE_RANK, 0, AchieveDef::MAX_BOSS_RANK - $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 主将等级
	 * @desc 201.主角等级达到某数值，
	 */
	public static function updateUserLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::USER_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 天命点亮个数
	 * @desc 202.点亮天命个数达到某数值
	 */
	public static function updateDestiny($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::DESTINY, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新得战魂个数
	 * @desc 成就203:获得某品质战魂
	 */
	public static function updateFightSoul($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::FIGHT_SOUL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新得战魂的templateid
	 * @desc 204.获得战魂种类
	 */
	public static function updateFightSoulTypes($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::FIGHT_SOUL_TYPES, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 银币总数
	 * @desc 205.拥有银币达到某数值
	 */
	public static function updateSilver($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::SILVER, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 声望总值
	 * @desc 206.拥有声望达到某数值
	 */
	public static function updatePrestige($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::PRESTIGE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 魂玉总值
	 * @desc 207.拥有魂玉达到某数值
	 */
	public static function updateJewel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::JEWEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 好友总数
	 * @desc 208.拥有好友个数达到某数值
	 */
	public static function updateFriend($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::FRIEND, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增夺宝次数
	 * @desc 209.夺宝次数达到某数值
	 */
	public static function updateSeize($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::SEIZE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新増竞技次数
	 * @desc 210.竞技次数达到某数值
	 */
	public static function updateArena($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::ARENA, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增比武次数
	 * @desc 211.比武次数达到某数值
	 */
	public static function updateCompete($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::COMPETE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新増宠物templateid
	 * @desc 212.获得宠物种类达到某数值
	 */
	public static function updatePetTypes($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::PET_TYPES, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 紫色宠物最新技能数
	 * @desc 213.任意紫色宠物学习技能个数达到某数值
	 */
	public static function updatePetSkill($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::PET_SKILL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 本次魔神活动的唯一标识,可以是活动开始时间,等等.
	 * @desc 214.击杀魔神次数
	 */
	public static function updateBoss($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::BOSS, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 魔神活动伤害排名
	 * @desc 215.进击的魔神活动结束后得到的伤害排名
	 */
	public static function updateBossRank($uid, $bossid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::BOSS_RANK, $bossid, AchieveDef::MAX_BOSS_RANK - $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 试练塔最高层数
	 * @desc 216.试练塔最高达到层数
	 */
	public static function updateTower($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::TOWER, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 战斗力最新值
	 * @desc 217.战斗力达到数值
	 */
	public static function updateFightForce($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::FIGHT_FORCE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 上阵武将数
	 * @desc 218.上阵武将数量
	 */
	public static function updateHeroFormation($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_FORMATION, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 上阵小伙伴数
	 * @desc 219.上阵小伙伴数量
	 */
	public static function updateFriendFormation($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::FRIEND_FORMATION, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增占星积分
	 * @desc 220.占星台获得积分数量
	 */
	public static function updateDivine($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::DIVINE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 军团等级
	 * @desc 221.加入军团且军团等级达到指定级别
	 */
	public static function updateGuildLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::GUILD_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 军团个人总贡献
	 * @desc 222.军团个人总贡献达到指定数值 
	 */
	public static function updateGuildContribution($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::GUILD_CONTRIBUTION, 0, $finish_num);
	}
	
	/**
	 * 
	 * @param $uid
	 * @param int $finish_num 城池战开始时间，用于区分不同的城池战
	 * @desc 223.参加城池争夺战争次数，每次活动参加一次战斗即算入参加一次
	 */
	public static function updateCityWarBattle($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::CITY_WAR_BATTLE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新占领的城市数,必1
	 * @desc 224.所在军团占领任意城市
	 */
	public static function updateCityCapture($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::CITY_CAPTURE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新得武将的品质
	 * @desc 301.获得1个某品质武将（向下兼容，比如获得紫色武将则同时激活白绿蓝紫品质武将的对应成就）
	 */
	public static function updateHeroColor($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_COLOR, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 图鉴中已获得武将种类数
	 * @desc 302.获得过的武将达到某数值，同图鉴中获得的武将，相同武将不重复计数
	 */
	public static function updateHeroTypes($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_TYPES, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 主将进阶级别
	 * @desc 303.主角进阶等级达到某数值
	 */
	public static function updateMHeroEvolve($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::MHERO_EVOLVE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 其他武将进阶级别
	 * @desc 304.除主角外某武将进阶等级达到某数值
	 */
	public static function updateHeroEvolve($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_EVOLVE, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 名将好感度总和
	 * @desc 305.名将好感等级之和达到x
	 */
	public static function updateHeroFavor($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_FAVOR, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 其他武将等级
	 * @desc 306.任意武将等级达到多少级
	 */
	public static function updateHeroLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param unknown $finish_type 通过武魂合成的英雄品质
	 * @param int $finish_num  通过武魂合成的英雄个数
	 * @desc 307.通过武魂合成，累计获得某颜色英雄数量(不向下兼容)
	 */
	public static function updateHeroFrag($uid, $finish_type, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::HERO_FRAG, $finish_type, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增装备的品质
	 * @desc 401.获得1件某品质装备（向下兼容，比如获得紫色装备则同时激活白绿蓝紫品质装备的对应成就）
	 */
	public static function updateEquipColor($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::EQUIP_COLOR, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增宝物的品质
	 * @desc 402.获得1件某品质整件宝物，同装备
	 */
	public static function updateEquipSuit($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::EQUIP_SUIT, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 图鉴中已获得装备总数
	 * @desc 403.获得装备种类数量
	 */
	public static function updateEquipTypes($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::EQUIP_TYPES, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 图鉴中已获得宝物总数
	 * @desc 404.获得整件宝物种类数量
	 */
	public static function updateEquipSuitTypes($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::EQUIP_SUIT_TYPES, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增战魂的等级或者升级后的等级
	 * @desc 405.任意战魂等级达到指定等级;
	 */
	public static function updateFightSoulLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::FIGHT_SOUL_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新增宝物等级或者升级后的等级
	 * @desc 406.任意宝物等级达到指定等级;
	 */
	public static function updateTreasureLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::TREASURE_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 宝物精炼等级
	 * @desc 407.任意宝物精炼等级达到指定等级;
	 */
	public static function updateTreasureEvolveLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::TREASURE_EVOLVE_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新増时装个数
	 * @desc 408.获得一件时装
	 */
	public static function updateDressNum($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::DRESS_NUM, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 装备强化等级
	 * @desc 409.任意装备强化等级达到指定等级;
	 */
	public static function updateArmReinforceLevel($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::ARM_REINFORE_LEVEL, 0, $finish_num);
	}
	
	/**
	 * $
	 * @param $uid
	 * @param int $finish_num 新増洗练次数
	 * @desc 410.累计进行洗练的次数;
	 */
	public static function updateArmRefreshNum($uid, $finish_num)
	{
		AchieveObj::getObj($uid)->updateType(AchieveDef::ARM_REFRESH_NUM, 0, $finish_num);
	}
	
	/*
	 public static function update($uid, $finish_num)
	 {
	AchieveObj::getObj($uid)->updateType(AchieveDef::, 0, $finish_num);
	}
	
	public static function update($uid, $finish_type, $finish_num)
	{
	AchieveObj::getObj($uid)->updateType(AchieveDef::, $finish_type, $finish_num);
	}
	*/

    /**
     * @param $uid
     * @param $finish_num int 切磋次数
     */
    public static function updateFriendsPlayWithEachOther($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::FRIENDS_PLAYWITH_EACHOTHER, 0, $finish_num);
    }

    /**
     * @param $uid
     * @param $finish_num int 橙装数量

    public static function updateOrangeDress($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::ORANGE_DRESS, 0, $finish_num);
    }
     *
     */

    /**
     * @param $uid
     * @param $finish_num int 寻龙积分
     */
    public static function updateDragonPoint($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::DRAGON_POINT, 0, $finish_num);
    }

    /**
     * @param $uid
     * @param $finish_num int 擂台赛成绩排名
     */
    public static function updateOlympicNormal($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::OLYMPIC_NORMAL, 0, AchieveDef::MAX_BOSS_RANK - $finish_num);
    }

    /**
     * @param $uid
     * @param $finish_num int 擂台赛冠军次数
     */
    public static function updateOlympicChampionNum($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::OLYMPIC_CHAMPION_NUM, 0, $finish_num);
    }

    /**
     * @param $uid
     * @param $finish_num int 主角学习技能个数
     */
    public static function updateActorLearnSkill($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::ACTOR_LEARN_SKILL, 0, $finish_num);
    }

    /**
     * @param $uid
     * @param $finish_num int 主角提高技能等级
     */
    public static function updateActorIncSkillLev($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::ACTOR_INC_SKILL_LEV, 0, $finish_num);
    }

    /**
     * @param $uid
     * @param $finish_num int 主角橙卡个数
     */
    public static function updateOrangeCard($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::ORANGE_CARD, 0, $finish_num);
    }

    /**
     * 神兵种类(相同的模板id算一件)
     * @param $uid
     * @param $finish_num int 神兵的模板id
     */
    public static function updateGodWeaponKind($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::GOD_WEAPON_KIND, 0, $finish_num);
    }

    /**
     * 获得1件某品质的神兵，同装备
     * @param $uid
     * @param $finish_num int 新增神兵的品质
     */
    public static function updateGodWeaponQuality($uid, $finish_num)
    {
        AchieveObj::getObj($uid)->updateType(AchieveDef::GOD_WEAPON_QUALITY, 0, $finish_num);
    }

    /**
     * 蓝色或者紫色神兵数量
     * @param $uid
     * @param $finish_type int 神兵品质
     * @param $finish_num int 新增蓝色神兵的数量（传累加值）
     */
    public static function updateGodWeaponNum($uid, $finish_type, $finish_num)
    {
        if($finish_type == ItemDef::ITEM_QUALITY_BLUE)
        {
            AchieveObj::getObj($uid)->updateType(AchieveDef::BLUE_GOD_WEAPON_NUM, 0, $finish_num);
        }else if($finish_type == ItemDef::ITEM_QUALITY_PURPLE)
        {
            AchieveObj::getObj($uid)->updateType(AchieveDef::PURPLE_GOD_WEAPON_NUM, 0, $finish_num);
        }
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */