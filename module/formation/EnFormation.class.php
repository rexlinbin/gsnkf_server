<?php
/**********************************************************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFormation.class.php 246060 2016-06-12 09:15:43Z MingTian $
 *
 **********************************************************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/EnFormation.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2016-06-12 09:15:43 +0000 (Sun, 12 Jun 2016) $
 * @version $Revision: 246060 $
 * @brief
 *         
 **/

/**********************************************************************************************************************
 * Class       : EnFormation
 * Description : 阵型内部接口类
 * Inherit     :
 **********************************************************************************************************************/

class EnFormation 
{
	/**
	 * uid => MyFormation
	 *
	 * @var MyFormation[]
	 */
	private static $arrFormationObj = array();
	
	/**
	 * 获取某个用户的阵型/阵容对象
	 * @return MyFormation
	 */
	public static function getFormationObj ($uid)
	{
		if (!isset(self::$arrFormationObj[$uid]))
		{
			self::$arrFormationObj[$uid] = new MyFormation($uid);
		}
		return self::$arrFormationObj[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrFormationObj = array();
		}
		else if (isset(self::$arrFormationObj[$uid]))
		{
			unset(self::$arrFormationObj[$uid]);
		}
	}
	
	/**
	 * 在创建角色时调用，初始化玩家的阵型信息
	 */
	public static function initFormation($arrUserInfo)
	{
		$uid = $arrUserInfo['uid'];
		$masterHid = $arrUserInfo['master_hid'];
		$initPos = btstore_get()->FORMATION['initPos'];
	
		if (!in_array($initPos, MyFormation::getArrOpenPos($arrUserInfo['level'])))
		{
			throw new ConfigException('invalid formation config. initpos:%d is not open', $initPos);
		}
	
		$values = array(
				'uid' => $uid,
				'craft_id' => 0,//===========阵法添加
				'va_formation' => array(
						'formation' => array(
								$masterHid => array(
										'index' => 0,
										'pos' => $initPos
								)
						),
						'extra' => array()
				),
		);
	
		FormationDao::insert($values);
	
		return $values;
	}
	
	public static function setFormation($uid, $formation)
	{
		$myFormation = EnFormation::getFormationObj($uid);
		$changed = $myFormation->setFormation($formation);
		$myFormation->update();
		
		return $changed;
	}
	
	/**
	 * 获得阵型中的武将hid数组
	 *
	 * @param int $uid        	
	 * @return array 
	 * array
	 * {
	 * 		pos => hid   每个有武将的位置=>对应的武将
	 * }
	 */
	public static function getArrHidInFormation($uid) 
	{
		$uid = intval($uid);
		$myFormation = EnFormation::getFormationObj($uid);
		return $myFormation->getFormation();
	}
	
	/**
	 * 获得阵容中的武将hid数组
	 *
	 * @param int $uid
	 * @return array
	 * array
	 * {
	 * 		index => hid   每个有武将的位置=>对应的武将
	 * }
	 */
	public static function getArrHidInSquad($uid)
	{
		$uid = intval($uid);
		$myFormation = EnFormation::getFormationObj($uid);
		return $myFormation->getSquad();
	}
	
	/**
	 * 获得小伙伴里的武将hid数组
	 *
	 * @param int $uid
	 * @return array
	 * array
	 * {
	 * 		index => hid   每个有武将的位置=>对应的武将
	 * }
	 */
	public static function getArrHidInExtra($uid)
	{
		$uid = intval($uid);
		$myFormation = EnFormation::getFormationObj($uid);
		return $myFormation->getExtra();
	}
	
	/**
	 * 获得属性小伙伴里的武将hid数组
	 *
	 * @param int $uid
	 * @return array
	 * array
	 * {
	 * 		index => hid   每个有武将的位置=>对应的武将
	 * }
	 */
	public static function getArrHidInAttrExtra($uid)
	{
		$uid = intval($uid);
		$myFormation = EnFormation::getFormationObj($uid);
		return $myFormation->getAttrExtra();
	}
	
	public static function getArrHidInAll($uid)
	{
		$uid = intval($uid);
		$myFormation = EnFormation::getFormationObj($uid);
		$squad = $myFormation->getSquad();
		$extra = $myFormation->getExtra();
		$attrExtra = $myFormation->getAttrExtra();
		return array_merge($squad, $extra, $attrExtra);
	}
	
	public static function getArrUserSquad($arrUser)
	{
		$arrSquad = array();
		if (empty($arrUser)) 
		{
			return $arrSquad;
		}
		$ret = FormationDao::getByArrUid($arrUser);
		foreach ($ret as $info)
		{
			$squad = array();
			foreach($info['va_formation']['formation'] as $hid => $value)
			{
				$squad[$value['index']] = $hid;
			}
			$arrSquad[$info['uid']] = $squad;
		}
	
		return $arrSquad;
	}
	
	/**
	 * 检查hid是否在uid的阵型上
	 */
	public static function isHidInFormation($hid, $uid)
	{
		$arrHid = self::getArrHidInFormation($uid);
		return in_array($hid, $arrHid);
	}
	
	/**
	 * 检查uid的阵型里是否有htid类型的英雄
	 */
	public static function isHtidInFormation($htid, $uid)
	{
	    $heroMng = EnUser::getUserObj($uid)->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObjInSquad();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        if($heroObj->getHtid() == $htid)
	        {
	            return TRUE;
	        }
	    }
		return FALSE;
	}
	
	public static function isBaseHtidInFormation($baseHtid,$uid)
	{
	    $heroMng = EnUser::getUserObj($uid)->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObjInSquad();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        if($heroObj->getBaseHtid() == $baseHtid)
	        {
	            return TRUE;
	        }
	    }
	    return FALSE;
	}
		
	/**
	 * 检查hid是否在uid的阵容上
	 */
	public static function isHidInSquad($hid, $uid)
	{
		$arrHid = self::getArrHidInSquad($uid);
		return in_array($hid, $arrHid);
	}
	
	/**
	 * 检查hid是否在uid的小伙伴里
	 */
	public static function isHidInExtra($hid, $uid)
	{
		$arrHid = self::getArrHidInExtra($uid);
		return in_array($hid, $arrHid);
	}
	
	/**
	 * 检查hid是否在uid的属性小伙伴里
	 * 
	 * @param int $hid
	 * @param int $uid
	 * @return boolean
	 */
	public static function isHidInAttrExtra($hid, $uid)
	{
		$arrHid = self::getArrHidInAttrExtra($uid);
		return in_array($hid, $arrHid);
	}
	
	public static function isHidInAll($hid, $uid)
	{
		$arrHid = self::getArrHidInAll($uid);
		return in_array($hid, $arrHid);
	}
	
	/**
	 * 获取某个用户当前使用的阵型信息
	 *
	 * @param int $uid
	 * @return array
	 * array
	 * {
	 * 		pos => hid   每个有武将的位置 =＞　对应的武将
	 * }
	 */
	public static function getArrHeroObjInFormation($uid)
	{
	    $arrHid = self::getArrHidInFormation($uid);
	    $arrExtra = self::getArrHidInExtra($uid);
	    $arrAttrExtra = self::getArrHidInAttrExtra($uid);
	
	    $userObj = EnUser::getUserObj($uid);
	    $heroManager = $userObj->getHeroManager();
	
	    $arrCreature = array ();
	    $arrLittleFriend = array();
	    $arrAttrFriend = array();
	    foreach($arrHid as $pos => $hid)
	    {
	        $arrCreature[$pos] = $heroManager->getHeroObj($hid);
	    }
	    $arrHero = $arrCreature;
	    foreach ($arrExtra as $hid)
	    {
	    	$arrHero[] = $heroManager->getHeroObj($hid);
	    	$arrLittleFriend[] = $heroManager->getHeroObj($hid);
	    }
	    foreach ($arrAttrExtra as $hid)
	    {
	    	$arrHero[] = $heroManager->getHeroObj($hid);
	    	$arrAttrFriend[] = $heroManager->getHeroObj($hid);
	    }
	    $unionProfit = self::getUnionProfitByFmt($arrHero,$uid);
	    $craftProfit = self::getWarcraftProfit($uid);
	    $attrSplitExtraProfit = self::getAttrExtraProfit($uid, TRUE);
        $attrExtraPosProfit = self::getAttrExtraPosProfit($uid);
	    foreach($arrHid as $pos => $hid)
	    {
	        $arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$hid]);
	        if( isset( $craftProfit[$pos] ) )
	        {
	        	$arrCreature[$pos]->setAddAttr( HeroDef::ADD_ATTR_BY_CRAFT, $craftProfit[$pos] );
	        }
	        
	        // 属性小伙伴加成
	        $arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_ATTR_EXTRA, Util::arrayAdd2V($attrSplitExtraProfit));
	        
	        // 属性小伙伴解锁给阵上武将加成
	        $arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_ATTR_EXTRA_POS, $attrExtraPosProfit);
	    }
	    
	    // 根据阵上的武将和助战军的武将的觉醒能力给阵上武将加成
	    $arrAddAttrConf = array();
	    $arrAddAttrConf = array_merge($arrAddAttrConf, self::getAddAttrConfByAwakeAbilityForFmt(FormationDef::HERO_TYPE_FORMATION, $arrCreature));
	    $arrAddAttrConf = array_merge($arrAddAttrConf, self::getAddAttrConfByAwakeAbilityForFmt(FormationDef::HERO_TYPE_ATTR_FRIEND, $arrAttrFriend));
	    Logger::trace('getArrHeroObjInFormation : arrAddAttrConf for formation:%s', $arrAddAttrConf);
	    foreach ($arrCreature as $pos => $aHero)
	    {
	    	// 获取当前阵上武将的国家和性别
	    	$curCountry = $aHero->getCreatureConf($aHero->getHtid(), CreatureAttr::COUNTRY);
	    	$curGender = $aHero->getCreatureConf($aHero->getHtid(), CreatureAttr::GENDER);
	    	
	    	// 循环所有的加成配置，获得有效的加成
	    	$arrEffectAddAttr = array();
	    	foreach ($arrAddAttrConf as $aConf)
	    	{
	    		$country = $aConf[1];
	    		$gender = $aConf[2];
	    		$attrId = $aConf[3];
	    		$attrValue = $aConf[4];
	    		
	    		// 过滤国家
	    		if ($country != 0 && $country != $curCountry) 
	    		{
	    			continue;
	    		}
	    		
	    		// 过滤性别
	    		if ($gender != 0 && $gender != $curGender) 
	    		{
	    			continue;
	    		}
	    		
	    		if (!isset($arrEffectAddAttr[$attrId])) 
	    		{
	    			$arrEffectAddAttr[$attrId] = 0;
	    		}
	    		$arrEffectAddAttr[$attrId] += intval($attrValue);
	    	}
	    	
	    	Logger::trace('getArrHeroObjInFormation : cur hid[%d], cur htid[%d], cur pos[%d], cur country[%d], cur gender[%d], addAttrByAwaikForFmt[%s]', $aHero->getHid(), $aHero->getHtid(), $pos, $curCountry, $curGender, $arrEffectAddAttr);
	    	
	    	// 给该武将加成
	    	$arrEffectAddAttr = HeroUtil::adaptAttr($arrEffectAddAttr);
	    	$arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_AWAIK_FOR_FMT, $arrEffectAddAttr);
	    }
	
	    return array($arrCreature,$attrSplitExtraProfit);
	}
	
	/**
	 * 获取某个用户当前使用的属性小伙伴obj，已经算过羁绊
	 *
	 * @param int $uid
	 * @return array
	 * array
	 * {
	 * 		index => hid   每个有武将的位置 =＞　对应的武将
	 * }
	 */
	public static function getArrHeroObjInAttrExtra($uid)
	{
		// 获取武将hid
		$arrHid = self::getArrHidInFormation($uid);
		$arrExtra = self::getArrHidInExtra($uid);
		$arrAttrExtra = self::getArrHidInAttrExtra($uid);
		
		$userObj = EnUser::getUserObj($uid);
		$heroManager = $userObj->getHeroManager();
		
		// 批量拉取装备到缓存
		if (count($arrHid) > FormationDef::FORMATION_SIZD)
		{
			throw new InterException('must less than %d', FormationDef::FORMATION_SIZD + 1);			
		}
		$arrEquipId = array();
		foreach ($arrHid as $hid)
		{
			$heroObj = $heroManager->getHeroObj($hid);
			$arrEquipId = array_merge($arrEquipId, $heroObj->getAllEquipId());
		}
		$arrEquipItem = ItemManager::getInstance()->getItems($arrEquipId);
		
		// 给属性小伙伴加上羁绊
		$arrCreature = array();
		$arrHero = array();
		foreach($arrHid as $pos => $hid)
		{
			$arrHero[] = $heroManager->getHeroObj($hid);
		}
		foreach ($arrExtra as $hid)
		{
			$arrHero[] = $heroManager->getHeroObj($hid);
		}
		foreach ($arrAttrExtra as $index => $hid)
		{
			$arrCreature[$index] = $heroManager->getHeroObj($hid);
			$arrHero[] = $heroManager->getHeroObj($hid);
		}
		$unionProfit = self::getUnionProfitByFmt($arrHero, $uid);
		foreach($arrAttrExtra as $index => $hid)
		{
			$arrCreature[$index]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$hid]);
		}
		
		return $arrCreature;
	}

	/**
	 * 获得怪物小队的战斗阵型信息
	 *
	 * @param int $armyId
	 * @return array $battleInfo
	 */
	public static function getMonsterBattleFormation($armyId, $baseLv = 1, $arrLvs = null)
	{
		$teamId = btstore_get()->ARMY[$armyId]['teamid'];
		$arrRet = self::getMonsterFormationInfo($teamId, $arrLvs, $baseLv);
		//敌方的攻击属性
		$battleInfo = array('name' => "$armyId",//army的名字去掉，前端自己获得。btstore_get()->ARMY[$armyId]['name']
				'level' => btstore_get()->ARMY[$armyId]['level'],
				'isPlayer' => false,
				'flag' => 0,
				'uid' => $armyId,
				'fightForce' => $arrRet['fightForce'],
				'arrHero' => $arrRet['arrHero']);
		return $battleInfo;
	}
	
	
	/**
	 * 返回怪物小队的阵型信息
	 *
	 * @param int $teamId        	 怪物小队ID
	 * @param array $arrLvs        	
	 * @throws Exception
	 */
	public static function getMonsterFormationInfo($teamId, $arrLvs,$baseLv=1) 
	{
		// 如果没有找到这个部队信息，则出错返回
		$army = btstore_get()->TEAM[$teamId];
		if (empty($army)) 
		{
			throw new FakeException('get army：%d from TEAM faield', $teamId);
		}
		Logger::debug('army:%d is %s.', $teamId, $army->toArray());
				
		$arrCreature = array();
		for($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i) 
		{
			if (!empty($army['fmt'][$i])) 
			{
				$arrCreature[$i] = new Creature($army['fmt'][$i]);
				if(!empty($arrLvs))
				{
					$arrCreature[$i]->setLevel($arrLvs[$i]);
				}
				$arrCreature[$i]->setAddAttrByBaseLv($baseLv);
			}
		}
		$unionProfit = self::getUnionProfitByFmt($arrCreature);
		foreach($arrCreature as $pos => $creatureObj)
		{
		    $arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$creatureObj->getHid()]);
		}

		$arrHero = self::changeObjToInfo($arrCreature);
		$fightForce = 0;
		foreach($arrCreature as $creatureObj)
		{
			$fightForce += $creatureObj->getFightForce();
		}
		
		return array(
				'arrHero' => $arrHero,
				'fightForce' => $fightForce,
		);
	}
	
	public static function getBattleFormationByArrHtid($uid, $arrHtid, $baseLv = 1, $arrLvs = null, $arrEvlLvs = null, $arrAttr = null)
	{
		$arrRet = self::getFormationInfoByArrHtid($uid, $arrHtid, $arrLvs, $arrEvlLvs, $arrAttr, $baseLv);
		$battleInfo = array
		(
				'uid' => $uid,
				'name' => EnUser::getUserObj($uid)->getUname(),
				'level' => EnUser::getUserObj($uid)->getLevel(),
				'isPlayer' => TRUE,
				'flag' => 0,
				'fightForce' => $arrRet['fightForce'],
				'arrHero' => $arrRet['arrHero']
		);
		return $battleInfo;
	}
	
	public static function getFormationInfoByArrHtid($uid, $arrHtid, $arrLvs, $arrEvlLvs, $arrAttr, $baseLv = 1)
	{
		// obj
		$arrCreature = array();
		for($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
		{
			if (!empty($arrHtid[$i]))
			{
				$aHeroInfo = HeroLogic::getInitData($uid, 10000000 + $arrHtid[$i], $arrHtid[$i]);
				if (!empty($arrEvlLvs))
				{
					$aHeroInfo['evolve_level'] = $arrEvlLvs[$i];
				}
				$replaceInitAttr = array();
				if (!empty($arrAttr) && isset($arrAttr[$i]))
				{
					$replaceInitAttr = $arrAttr[$i];
				}
				$arrCreature[$i] = new ArtificialHeroObj($aHeroInfo, $replaceInitAttr);
				if(!empty($arrLvs))
				{
					$arrCreature[$i]->setLevel($arrLvs[$i]);
				}
				$arrCreature[$i]->setAddAttrByBaseLv($baseLv);
			}
		}
		
		// 羁绊
		$unionProfit = self::getUnionProfitByFmt($arrCreature);
		foreach($arrCreature as $pos => $creatureObj)
		{
			$arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$creatureObj->getHid()]);
		}
		
		// info
		$arrHeroInfo = self::changeObjToInfo($arrCreature);
		
		// 战力
		$fightForce = 0;
		foreach($arrCreature as $creatureObj)
		{
			$fightForce += $creatureObj->getFightForce();
		}
		
		return array
		(
				'arrHero' => $arrHeroInfo,
				'fightForce' => $fightForce,
		);
	}

	/**
	 * 获取怪物小队的阵容
	 */
	public static function getMonsterSquad($armyId)
	{
		$teamId = btstore_get()->ARMY[$armyId]['teamid'];
		$team = btstore_get()->TEAM[$teamId];
		if (empty($team))
		{
			throw new FakeException('get army：%d from TEAM faield', $teamId);
		}
		$squad = array();
		for($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
		{
			if (!empty($team['fmt'][$i]))
			{
				$squad[] = $team['fmt'][$i];
			}
		}
		return $squad;
	}
	
	public static function getNpcBattleFormation($armyId, $arrHeroPos, $uid, $baseLv=1)
	{
		$arrHero = self::getNpcFormationInfo($armyId, $arrHeroPos, $uid, $baseLv);
		$userObj = EnUser::getUserObj($uid);
		$battleInfo = array(
				'uid' => $uid,
				'name' => $userObj->getUname(),
				'level' => $userObj->getLevel(),
				'isPlayer' => true,
				'fightForce' => $userObj->getFightForce(),
				'arrHero' => $arrHero,
		);
		
		return $battleInfo;
	}

	/**
	 * 获取NPC部队的阵型信息
	 *
	 * @param int $armyId          NPC部队ID
	 * @param array $arrHeroPos 	 用户设置的武将位置
	 * @return array $arrCreature  NPC部队对象数组
	 * @throws Exception
	 */
	public static function getNpcFormationInfo($armyId, $arrHeroPos, $uid, $baseLv=1)
	{
		$teamId = intval(btstore_get()->ARMY[$armyId]['npc_team_id']);
		// 如果没有找到这个NPC部队信息，则出错返回
		$army = btstore_get()->TEAM[$teamId];
		if (!isset($army))
		{
			throw new FakeException('get army:%d from TEAM faield', $teamId);
		}
		$userObj = EnUser::getUserObj();
		$heroMng = $userObj->getHeroManager();
		$arrCreature = array();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; ++$i)
		{
			//配置要求为空的位置，你不能放东西； 配置要求你放东西的位置，你一定要放上东西
			if (($army['fmt'][$i] == 0 && $arrHeroPos[$i] != 0) ||
				($army['fmt'][$i] == 1 && empty($arrHeroPos[$i])))
			{
				throw new FakeException('formation is different between %s and %s.', $army['fmt'], $arrHeroPos);
			}			
			
			if (intval($army['fmt'][$i]) == 1)
			{
				$arrCreature[$i] = $heroMng->getHeroObj($arrHeroPos[$i]);
			}
			else if (intval($army['fmt'][$i]) != 0)
			{
				$arrCreature[$i] = new Creature($army['fmt'][$i]);
				$arrCreature[$i]->setAddAttrByBaseLv($baseLv);
			}
		}
		$unionProfit = self::getUnionProfitByFmt($arrCreature,$uid);
		foreach($arrCreature as $pos => $creatureObj)
		{
		    $arrCreature[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$creatureObj->getHid()]);
		}

		return self::changeObjToInfo($arrCreature);
	}
	
	/**
	 * 将英雄对象转化为英雄数组
	 *
	 * @param array $arr        	
	 */
	public static function changeObjToInfo($arr) 
	{
		$arrCreature = array();
		for($i = 0; $i < FormationDef::FORMATION_SIZD; ++ $i) 
		{
			if (isset($arr[$i]) && ($arr[$i] instanceof Creature)) 
			{
				$arrCreature[$i] = $arr[$i]->getBattleInfo();
				$arrCreature[$i]['position'] = $i;		
			}
		}
		return $arrCreature;
	}
	
	/**
	 * 获得一组武将对阵上武将加成的配置，会根据武将当前类型过滤 
	 * 
	 * @param int $type				武将的类型：包括 阵上武将，助战军，小伙伴
	 * @param array $arrHero  		所有武将obj
	 */
	public static function getAddAttrConfByAwakeAbilityForFmt($heroType, $arrHero)
	{
		$allConf = array();
		
		foreach ($arrHero as $aHero)
		{
			$arrAddAttrConf = $aHero->getAddAttrConfByAwakeAbilityForFmt();
			
			// 根据武将当前的类型，过滤掉不符合条件的加成
			foreach ($arrAddAttrConf as $index => $aConf)
			{
				$type = $aConf[0];
				if ($type != 0 && $type != $heroType) 
				{
					unset($arrAddAttrConf[$index]);
				}
			}
			
			$allConf = array_merge($allConf, $arrAddAttrConf);
		}
		
		return $allConf;
	}

	/**
	 * 
	 * @param array $formation   array of creature obj
	 */
	public static function getUnionProfitByFmt($formation, $uid=0)
	{
	    $ret = array();
	    //获取阵型中的所有英雄的htid, baseHtid, equip
	    $arrHtid = array();
	    $arrBaseHtid = array();
	    $arrEquip = array();
	    $arrQuality = array();
	    foreach ($formation as $obj)
	    {
	        $hid = $obj->getHid();
	        $arrHtid[$hid] = $obj->getHtid();
	        $arrBaseHtid[$hid] = $obj->getBaseHtid();
	        $arrEquip[$hid] = $obj->getAllEquipId();
	        $arrItem = ItemManager::getInstance()->getItems($arrEquip[$hid]);
	        foreach ($arrEquip[$hid] as $key => $itemId)
	        {
	            if ($itemId == 0)
	            {
	            	unset($arrEquip[$hid][$key]);
	                continue;
	            }
	            $itemObj = $arrItem[$itemId];
	            if (empty($itemObj))
	            {
	            	Logger::fatal('not found item:%d', $itemId);
	            	unset($arrEquip[$hid][$key]);
	            	continue;
	            }
	            $itemTplId = $itemObj->getItemTemplateID();
	            $itemType = $itemObj->getItemType();
	            $arrEquip[$hid][$key] = $itemTplId;
	            if (ItemDef::ITEM_TYPE_TREASURE == $itemType && !$itemObj->isNoAttr()) 
	            {
	            	$itemQuality = $itemObj->getItemQuality();
	            	if (TreasureDef::TREASURE_TYPE_HORSE == $itemObj->getType()) 
	            	{
	            		$arrQuality[3][$hid][] = $itemQuality;
	            	}
	            	if (TreasureDef::TREASURE_TYPE_BOOK == $itemObj->getType()) 
	            	{
	            		$arrQuality[4][$hid][] = $itemQuality;
	            	}
	            }
	            if (ItemDef::ITEM_TYPE_GODWEAPON == $itemType 
	            && $itemObj->getEvolveNum() < $itemObj->getFriendOpen()) 
	            {
	            	unset($arrEquip[$hid][$key]);
	            }
	        }
	    }
	    Logger::trace('arrtreasure quality:%s', $arrQuality);
	    
	    $addUnion = !empty($uid) ? EnUnion::getAddUnion($uid) : array();
	    foreach ($arrEquip as $hid => $arrEquipId)
	    {
	        $unionProfit = Creature::getCreatureConf($arrHtid[$hid], CreatureAttr::UNION_PROFIT);
	        if (empty($unionProfit))
	        {
	            $ret[$hid] = array();
	            continue;
	        }
	        $arrAttr = array();
	        foreach ($unionProfit as $unionId)
	        {
	        	if (empty($unionId)) 
	        	{
	        		continue;
	        	}
	            $attrInfo = btstore_get()->UNION_PROFIT[$unionId];
	            if (empty($attrInfo))
	            {
	                throw new ConfigException('union profit:%d is empty!', $unionId);
	            }
	            $flag = true;
	            if (!in_array($unionId, $addUnion)) 
	            {
	            	$arrCond = $attrInfo['arrCond'];
	            	foreach ($arrCond as $type => $arrId)
	            	{
	            		if ($type == 1)
	            		{
	            			foreach ($arrId as $id)
	            			{
	            				if ($id == 0 && (!empty($uid)))
	            				{
	            					$id = EnUser::getUserObj($uid)->getHeroManager()->getMasterHeroObj()->getBaseHtid();
	            				}
	            				if (!in_array($id, $arrBaseHtid))
	            				{
	            					$flag = false;
	            					break;
	            				}
	            			}
	            		}
	            		else if ($type == 2)
	            		{
	            			foreach ($arrId as $id)
	            			{
	            				if (!in_array($id, $arrEquipId))
	            				{
	            					$flag = false;
	            					break;
	            				}
	            			}
	            		}
	            		else if ($type == 3 || $type == 4)
	            		{
	            			foreach ($arrId as $id)
	            			{
	            				if (!isset($arrQuality[$type][$hid])
	            				|| !in_array($id, $arrQuality[$type][$hid]))
	            				{
	            					$flag = false;
	            					break;
	            				}
	            			}
	            		}
	            		else if ($type == 5)
	            		{
	            			$intersect = array_intersect($arrEquipId, $arrId->toArray());
	            			if (empty($intersect))
	            			{
	            				$flag = false;
	            			}
	            		}
	            		if($flag == false)
	            		{
	            			break;
	            		}
	            	}
	            	// 名将星数要求
	            	if ( $flag == true
	            	&& (!empty($attrInfo['starLevel']))
	            	&& (!empty($uid)))
	            	{
	            		$starLevel = $attrInfo['starLevel']->toArray();
	            		$level = EnStar::getUserStarLevel($uid, key($starLevel));
	            		if ($level < current($starLevel))
	            		{
	            			$flag = false;
	            		}
	            	}
	            }
	            if ($flag == true)
	            {
	            	Logger::trace("hid:%d union id:%d", $hid, $unionId);
	                $arrAttr[] = $attrInfo['arrAttr'];
	            }
	        }
	        // 统计每个属性的总加成值
	        $arrAttr = Util::arrayAdd2V($arrAttr);
	    
	        // 将属性id转成属性名称
	        $ret[$hid] = HeroUtil::adaptAttr($arrAttr);
	    }
	    
	    return $ret;
	}
	
	//===========阵法添加
	public static function getWarcraftProfit( $uid )
	{
		$formationObj = self::getFormationObj($uid);
		$curWarcraftId = $formationObj->getCurWarcraft();
		$allWarCraftInfo = $formationObj->getWarcraft();
		$warcraftProfitInfo = WarcraftLogic::getWarcraftProfit( $curWarcraftId, $allWarCraftInfo );
		
		return $warcraftProfitInfo;
	}

    /**
     * 属性小伙伴位置的解锁加成
     * @param $uid
     */
    public static function getAttrExtraPosProfit($uid)
    {
        $myFormation = EnFormation::getFormationObj($uid);
        $attrExtraOpen = $myFormation->getAttrExtraOpen();
        $ret = array();

        foreach($attrExtraOpen as $openIndex)
        {
            //属性小伙伴位置提供的解锁加成
            $arrAttrExtraPositionExtraProfit = self::getAttrExtraPositionExtraProfitByIndex($uid, $openIndex);

            foreach($arrAttrExtraPositionExtraProfit as $attrName => $attrValue)
            {
                if(isset($ret[$attrName]))
                {
                    $ret[$attrName] += $attrValue;
                }
                else
                {
                    $ret[$attrName] = $attrValue;
                }
            }
        }

        return $ret;
    }

	/**
	 * 属性小伙伴的属性加成
	 * 
	 * @param int $uid
	 */
	public static function getAttrExtraProfit($uid, $split = FALSE)
	{
		Logger::trace('EnFormation::getAttrExtraProfit param[uid:%d] begin...', $uid);
		
		$arrAttrExtraHeroObj = self::getArrHeroObjInAttrExtra($uid);
		
		$ret = array();	
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$arrAttrExtra = self::getArrHidInAttrExtra($uid);
		$arrAttrExtraTempleteConf = btstore_get()->SECOND_FRIEND_TEMPLETE->toArray();
		foreach ($arrAttrExtra as $index => $hid)
		{
			if (!isset($attrExtraConf[$index + 1]))
			{
				throw new ConfigException('invalid attr extra index:%d, not in conf:%s.', $index, $attrExtraConf);
			}
			
			$addAttrConf = $attrExtraConf[$index + 1]['attr'];			
			$hero = $arrAttrExtraHeroObj[$index];
			$battleInfo = $hero->getNakedBattleInfo();
			Logger::trace('EnFormation::getAttrExtraProfit index:%d, hid:%d, battleInfo:%s', $index, $hid, $battleInfo);
			foreach ($addAttrConf as $aAttr)
			{
				if (count($aAttr) != 3)
				{
					throw new ConfigException('invalid arr config:%s', $aAttr);
				}
				
				$addFrom = $aAttr[0];
				$addTo = $aAttr[1];
				$addRate = intval($aAttr[2]);
				if (!isset($arrAttrExtraTempleteConf[$addFrom])) 
				{
					throw new ConfigException('invalid add source:%d, not found in templete conf:%s', $addFrom, $arrAttrExtraTempleteConf);
				}
				
				$curConf = $arrAttrExtraTempleteConf[$addFrom];
				$formula = $curConf['formula'];
				$addBase = 0;
				if ($formula == 1) // 计算血量公式
				{
					$addBase = intval($battleInfo[PropertyKey::MAX_HP]);
				}
				else if ($formula == 2) // 计算其他公式
				{
					$base = $curConf['base'];
					if (!empty($base) && !isset($battleInfo[$base]))
					{
						throw new InterException('not found attr:%s in battle info:%s', $base, $battleInfo);
					}
					$base = empty($base) ? 0 : intval($battleInfo[$base]);
					
					$add = $curConf['add'];
					if (!empty($add) && !isset($battleInfo[$add]))
					{
						throw new InterException('not found attr:%s in battle info:%s', $add, $battleInfo);
					}
					$add = empty($add) ? 0 : intval($battleInfo[$add]);
					
					$final = $curConf['final'];
					if (!empty($final) && !isset($battleInfo[$final]))
					{
						throw new InterException('not found attr:%s in battle info:%s', $final, $battleInfo);
					}
					$final = empty($final) ? 0 : intval($battleInfo[$final]);
					
					$addBase = $base * (1 + $add / UNIT_BASE) + $final;
					Logger::trace('EnFormation::getAttrExtraProfit index:%d, hid:%d, base:%d, add:%d, final:%d, addBase:%d', $index, $hid, $base, $add, $final, $addBase);
				}
				else 
				{
					throw new ConfigException('invalid formula id:%d', $formula);
				}
				
				$addValue = $addBase * $addRate / UNIT_BASE;
				Logger::trace('EnFormation::getAttrExtraProfit index:%d, hid:%d, addFrom:%s, addTo:%s, addBase:%d, addRate:%d, addValue:%d', $index, $hid, $addFrom, $addTo, $addBase, $addRate, $addValue);

				if ($split) 
				{
					if (isset($ret[$hid][$addTo])) 
					{
						$ret[$hid][$addTo] += $addValue;
					}
					else 
					{
						$ret[$hid][$addTo] = $addValue;
					}
				}
				else 
				{
					if (isset($ret[$addTo]))
					{
						$ret[$addTo] += $addValue;
					}
					else
					{
						$ret[$addTo] = $addValue;
					}
				}
			}

            //属性小伙伴位置提供的成长加成
            $arrAttrExtraPositionUpProfit = self::getAttrExtraPositionUpProfitByIndex($uid, $index);
            if ($split)
            {
                foreach($arrAttrExtraPositionUpProfit as $attrName => $attrValue)
                {
                    if(isset($ret[$hid][$attrName]))
                    {
                        $ret[$hid][$attrName] += $attrValue;
                    }
                    else
                    {
                        $ret[$hid][$attrName] = $attrValue;
                    }
                }
            }
            else
            {
                foreach($arrAttrExtraPositionUpProfit as $attrName => $attrValue)
                {
                    if(isset($ret[$attrName]))
                    {
                        $ret[$attrName] += $attrValue;
                    }
                    else
                    {
                        $ret[$attrName] = $attrValue;
                    }
                }
            }
		}

        if($split)
        {
            foreach($ret as $hid => $eachHidAttr)
            {
                $ret[$hid] = Util::array2Int($eachHidAttr, FALSE);
            }
        }
        else
        {
            $ret = Util::array2Int($ret, FALSE);
        }

		//不用adapt啦，配置中已经是字符串的值啦
		//$ret = HeroUtil::adaptAttr($ret);
			
		Logger::trace('EnFormation::getAttrExtraProfit param[uid:%d] ret[%s] end...', $uid, $ret);
		return $ret;
	}

    /**
     * 获得某个属性小伙伴助战位置提供的成长属性
     * @param $uid
     * @param $index int 位置
     * @throws $exception e
     * @return array
     * {
     *      AttrName(属性字符串) => value
     * }
     */
    public static function getAttrExtraPositionUpProfitByIndex($uid, $index)
    {
        $myFormation = EnFormation::getFormationObj($uid);
        //当前位置的等级
        $curAttrExtraLv = $myFormation->getAttrExtraLvByIndex($index);
        if($curAttrExtraLv == 0)
        {
            return array();
        }

        $arrProfit = array();
        $attrExtraConf = btstore_get()->SECOND_FRIEND;
        if (!isset($attrExtraConf[$index + 1]))
        {
            throw new ConfigException('invalid attr extra index:%d, not in conf:%s.', $index, $attrExtraConf);
        }

        self::calUpAttr($arrProfit, $uid, $index, $curAttrExtraLv);

        Logger::trace('EnFormation::getAttrExtraPositionUpProfitByIndex param[uid:%d] arrProfit[%s] end...', $uid, $arrProfit);
        return $arrProfit;
    }

    /**
     * 获得某个属性小伙伴助战位置提供的解锁属性
     * @param $uid
     * @param $index int 位置
     * @throws $exception e
     * @return array
     * {
     *      AttrName(属性字符串) => value
     * }
     */
    public static function getAttrExtraPositionExtraProfitByIndex($uid, $index)
    {
        $myFormation = EnFormation::getFormationObj($uid);
        //当前位置的等级
        $curAttrExtraLv = $myFormation->getAttrExtraLvByIndex($index);
        if($curAttrExtraLv == 0)
        {
            return array();
        }

        $attrExtraConf = btstore_get()->SECOND_FRIEND;
        if (!isset($attrExtraConf[$index + 1]))
        {
            throw new ConfigException('invalid attr extra index:%d, not in conf:%s.', $index, $attrExtraConf);
        }
        //解锁属性
        $extraAttrConf = $attrExtraConf[$index + 1]['extraAttr']->toArray();

        $arrProfit = array();
        //配置解析时候已按照等级升序排
        foreach($extraAttrConf as $level => $levelExtraConf)
        {
            if($curAttrExtraLv < $level)
            {
                break;
            }
            foreach($levelExtraConf as $attrName => $attrValue)
            {
                if(isset($arrProfit[$attrName]))
                {
                    $arrProfit[$attrName] += $attrValue;
                }
                else
                {
                    $arrProfit[$attrName] = $attrValue;
                }
            }
        }

        Logger::trace('EnFormation::getAttrExtraPositionExtraProfitByIndex param[uid:%d] arrProfit[%s] end...', $uid, $arrProfit);
        return $arrProfit;
    }

    /**
     * 计算佳林特殊配表的属性
     * @param $arrProfit array 属性数组
     * @param $uid
     * @param $index int 位置
     * @param $curAttrExtraLv int 当前位置等级
     * @throws InterException
     * @throws ConfigException
     */
    public static function calUpAttr(&$arrProfit, $uid, $index, $curAttrExtraLv)
    {
        $attrExtraConf = btstore_get()->SECOND_FRIEND;
        $arrAttrExtraTempleteConf = btstore_get()->SECOND_FRIEND_TEMPLETE->toArray();

        $arrAttrExtraHeroObj = self::getArrHeroObjInAttrExtra($uid);
        $hero = $arrAttrExtraHeroObj[$index];
        $battleInfo = $hero->getNakedBattleInfo();
        //提升属性
        $upAttrConf = $attrExtraConf[$index + 1]['upAttr']->toArray();
        foreach($upAttrConf as $eachUpAttrConf)
        {
            if(count($eachUpAttrConf) != 3)
            {
                throw new ConfigException('invalid attr config:%s', $eachUpAttrConf);
            }

            $addFrom = $eachUpAttrConf[0];
            $addTo = $eachUpAttrConf[1];
            $addRate = intval($eachUpAttrConf[2]);
            if (!isset($arrAttrExtraTempleteConf[$addFrom]))
            {
                throw new ConfigException('invalid add source:%d, not found in templete conf:%s', $addFrom, $arrAttrExtraTempleteConf);
            }

            $curConf = $arrAttrExtraTempleteConf[$addFrom];
            $formula = $curConf['formula'];
            $addBase = 0;
            if ($formula == 1) // 计算血量公式
            {
                $addBase = intval($battleInfo[PropertyKey::MAX_HP]);
            }
            else if ($formula == 2) // 计算其他公式
            {
                $base = $curConf['base'];
                if (!empty($base) && !isset($battleInfo[$base]))
                {
                    throw new InterException('not found attr:%s in battle info:%s', $base, $battleInfo);
                }
                $base = empty($base) ? 0 : intval($battleInfo[$base]);

                $add = $curConf['add'];
                if (!empty($add) && !isset($battleInfo[$add]))
                {
                    throw new InterException('not found attr:%s in battle info:%s', $add, $battleInfo);
                }
                $add = empty($add) ? 0 : intval($battleInfo[$add]);

                $final = $curConf['final'];
                if (!empty($final) && !isset($battleInfo[$final]))
                {
                    throw new InterException('not found attr:%s in battle info:%s', $final, $battleInfo);
                }
                $final = empty($final) ? 0 : intval($battleInfo[$final]);

                $addBase = $base * (1 + $add / UNIT_BASE) + $final;
                Logger::trace('EnFormation::calUpAttr index:%d, base:%d, add:%d, final:%d, addBase:%d', $index, $base, $add, $final, $addBase);
            }
            else
            {
                throw new ConfigException('invalid formula id:%d', $formula);
            }

            $addValue = $curAttrExtraLv * $addBase * $addRate / UNIT_BASE;
            Logger::trace('EnFormation::calUpAttr index:%d, addFrom:%s, addTo:%s, addBase:%d, addRate:%d, addValue:%d', $index, $addFrom, $addTo, $addBase, $addRate, $addValue);

            if(isset($arrProfit[$addTo]))
            {
                $arrProfit[$addTo] += $addValue;
            }
            else
            {
                $arrProfit[$addTo] = $addValue;
            }
        }
    }
	
	/**
	 * 某个小伙伴的位置（从0开始）在阵法看来是不是符合条件的，不要传跟阵法不相关的位置，会返回false
	 * @param int $uid
	 * @param int $pos 小伙伴位置 
	 * @param bool $isSecondFriend 是否是第二套小伙伴，默认FALSE
	 * @return bool
	 *
	 */
	public static function isPosValidByCraft( $uid, $pos, $isSecondFriend = FALSE )
	{
		$formationObj = self::getFormationObj($uid);
		$allWarcraftInfo = $formationObj->getWarcraft();
		
		//这里在外面统一检查等级了
		//$userLevel = EnUser::getUserObj( $uid )->getLevel();
		return WarcraftLogic::isPosValidByCraft($pos, $allWarcraftInfo, $isSecondFriend);
	}
	
	public static function getCraftInfo( $uid )
	{
		$formationObj = self::getFormationObj($uid);
		return WarcraftLogic::getWarcraftInfo($uid);
	}
	
	public static function  getCurCraftId( $uid )
	{
		$formationObj = self::getFormationObj($uid);
		$curCraftId = $formationObj->getCurWarcraft();
		return $curCraftId;
	}
	
	public static function getCurCraftLevelId( $uid )
	{
	     $formationObj = self::getFormationObj($uid);
	     $curCraftId = $formationObj ->getCurWarcraft();
	     if( empty( $curCraftId ) )
	     {
	     	$level = 0;
	     }
	     else 
	     {
	     	$level = 1;
	     	$allWarCraftInfo = $formationObj->getWarcraft();
	     	if( isset( $allWarCraftInfo[ $curCraftId ] ) )
	     	{
	     		$level =  $allWarCraftInfo[ $curCraftId ]['level'];
	     	}
	     }
	     
	     return array('id' => $curCraftId, 'level' => $level );
	     	
	}

    public static function getAttrExtraLevel($uid)
    {
        if(!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA, $uid))
        {
            return array();
        }

        $myFormation = self::getFormationObj($uid);
        //给前端返回所有位置的等级，已开启没强化的就返回0
        $arrRet = $myFormation->getAttrExtraLevel();

        $attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
        $maxCount = FormationDef::ATTR_EXTRA_SIZE;
        if ($maxCount < count($attrExtraConf))
        {
            $maxCount = count($attrExtraConf);
        }

        $attrExtraLv = array();
        for($i = 0; $i < $maxCount; ++$i)
        {
            if(isset($arrRet[$i]))
            {
                $attrExtraLv[] = $arrRet[$i];
            }
            else
            {
                if(!$myFormation->isAttrExtraValid($i))
                {
                    $attrExtraLv[] = -1;
                }
                else
                {
                    $attrExtraLv[] = $myFormation->isAttrExtraOpen($i) ? 0 : -1;
                }
            }
        }

        return $attrExtraLv;
    }
    
    public static function getMasterTalentInfo($uid)
    {
    	$userObj = EnUser::getUserObj($uid);
    	$heroMgr = $userObj->getHeroManager();
    	$masterHeroObj = $heroMgr->getMasterHeroObj();
    	$masterTalentInfo = $masterHeroObj->getMasterTalentInfo();
    	
    	return $masterTalentInfo;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */