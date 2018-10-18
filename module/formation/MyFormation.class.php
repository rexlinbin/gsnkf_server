<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyFormation.class.php 214643 2015-12-09 02:32:24Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/MyFormation.class.php $
 * @author $Author: ShijieHan $(wuqilin@babeltime.com)
 * @date $Date: 2015-12-09 02:32:24 +0000 (Wed, 09 Dec 2015) $
 * @version $Revision: 214643 $
 * @brief 
 *  
 **/

/**
 * 我的阵容(squad)和我的阵型(formation)
 * 
 * 这两个数据都存放在t_hero_formation中的va_formation中。 
 * 格式如下：
 * 	formation
 * 	{
 * 		hid => array{index => xxx, pos => xxx}
 * 	}
 *  index：hid在阵容中的位置
 *  pos：hid在阵型中的位置
 *  
 *  小伙伴们
 *  extra
 *  {
 *      hid
 *  }
 * 
 * @author wuqilin
 *
 */

class MyFormation
{
	private $uid = 0;
	
	private $data = NULL;
	
	private $dataModify = NULL;
	
	function __construct($uid)
	{
		if ($uid <= 0)
		{
			throw new FakeException('invalid uid:%d', $uid);
		}
	
		if ($uid == RPCContext::getInstance()->getUid())
		{
			$data = RPCContext::getInstance()->getSession(FormationDef::SESSION_KEY_FORMATION);
			if (!empty($data))
			{
				$this->data = $data;
			}
		}
	
		if (empty($this->data))
		{
			$this->data = FormationDao::getByUid($uid);
			if ($uid == RPCContext::getInstance()->getUid())
			{
				RPCContext::getInstance()->setSession(FormationDef::SESSION_KEY_FORMATION, $this->data);
			}
		}
	
		if (empty($this->data))
		{
			throw new InterException('not found formation for uid:%d', $uid);
		}
		$this->uid = $uid;
		$this->dataModify = $this->data;
	}
	
	public function getFormation()
	{
		$formation = array();
		foreach ($this->dataModify['va_formation']['formation'] as $hid => $value)
		{
			$formation[$value['pos']] = $hid;
		}
		return $formation;
	}
	
	public function getSquad()
	{
		$squad = array();
		foreach ($this->dataModify['va_formation']['formation'] as $hid => $value)
		{
			$squad[$value['index']] = $hid;
		}
		return $squad;
	}
	
	public function addHero($hid, $index)
	{
		$userObj = EnUser::getUserObj($this->uid);
		$userLevel = $userObj->getLevel();
		if ($index < 0 || $index >= self::getSquadSize($userLevel))
		{
			throw new FakeException('invalid index:%d', $index);
		}
		if ($this->checkExist($hid, $index, 'formation') == true)
		{
			throw new FakeException('hid:%d already exist', $hid);
		}
		
		//目标位置是否已有武将
		$oldHid = 0;	
		//新武将在阵型中的位置
		$pos = -1; 
		//已使用的阵型位置
		$arrUsedPos = array();	
		foreach ($this->dataModify['va_formation']['formation'] as $key => $value)
		{
			if ($value['index'] == $index)
			{
				$oldHid = $key;
				$pos = $value['pos'];
			}
			$arrUsedPos[] = $value['pos'];
		}
		
		//为新武将在阵型中找一个位置
		if ($oldHid > 0)
		{
			Logger::debug('replace old:%d, new:%d, index:%d, pos:%d', $oldHid, $hid, $index, $pos);	
			$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
			$oldHeroObj = $userObj->getHeroManager()->getHeroObj($oldHid);
		    if($heroObj->isEquiped() || $oldHeroObj->isEquiped())
		    {
		        HeroLogic::changeEquip($oldHid, $hid, HeroDef::$ALL_EQUIP_TYPE);
		    }
			$this->delHero($oldHid);
		}
		else 
		{			
			$arrOpenPos = self::getArrOpenPos($userLevel);
			foreach($arrOpenPos as $value)
			{
				if (!in_array($value, $arrUsedPos))
				{
					$pos = $value;
					break;
				}
			}
			Logger::debug('add hid:%d, index:%d, pos:%d', $hid, $index, $pos);
		}
		if ($pos < 0)
		{
			//理论上，阵容中有位置，阵型中就应该有位置
			throw new InterException('no pos for hid:%d, uid:%d', $hid, $this->uid);
		}
		
		//放置新的武将
		$this->dataModify['va_formation']['formation'][$hid] = array(
				'index' => $index,
				'pos' => $pos,
		);
		
		//通知其他模块		
		$userObj->modifyBattleData();
		$userObj->getHeroManager()->updateSession();
	}
	
	public function delHero($hid)
	{	
		//hid需要存在
		if (!isset($this->dataModify['va_formation']['formation'][$hid]))
		{
			throw new FakeException('hid:%d no found', $hid);
		}
		
		//主角不让下阵
		$userObj = EnUser::getUserObj($this->uid);
		$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
		if ($heroObj->isMasterHero() == true) 
		{
			throw new FakeException('cant del master hero. level:%d', $userObj->getLevel());			
		}
		
		//删掉武将
		unset($this->dataModify['va_formation']['formation'][$hid]);
		Logger::debug('del hid:%d from formation', $hid);
		
		//通知其他模块
		$userObj->modifyBattleData();
		$userObj->getHeroManager()->updateSession();
	}
	
	/**
	 * 设置阵型，需要给出完整的阵型数据.
	 * @param array $formation
	 * array
	 * {
	 * 		pos=>hid
	 * }
	 * @throws Exception
	 */
	public function setFormation($formation)
	{
		//1. 参数检查
		$heroNum = count($this->dataModify['va_formation']['formation']);
		if (count($formation) != $heroNum)
		{
			throw new FakeException('invalid formation size.pre formation %s. set formation %s', $this->dataModify['va_formation']['formation'], $formation);
		}
		if (count(array_unique($formation)) != $heroNum)
		{
			throw new FakeException('duplicate in formation:%d', $formation);
		}
		
		//2. 检查新的阵型是否合法
		$userObj = EnUser::getUserObj($this->uid);
		$arrOpenPos = self::getArrOpenPos($userObj->getLevel());
		$changed = false;
		foreach ($formation as $pos => $hid)
		{
			if (!isset($this->dataModify['va_formation']['formation'][$hid]))
			{
				throw new FakeException('hid:%d not in squad', $hid);
			}
			if (!in_array($pos, $arrOpenPos))
			{
				throw new FakeException('pos:%d not open, opened:%s.', $pos, $arrOpenPos);
			}			
			if ($this->dataModify['va_formation']['formation'][$hid]['pos'] != $pos)
			{
				$changed = true;
				$this->dataModify['va_formation']['formation'][$hid]['pos'] = $pos;
			}
		}
		if (!$changed)
		{
			Logger::warning('no change');
			return $changed;
		}

		$isCraftOpenOrNot = WarcraftLogic::isWarCraftOpen($this->uid);//EnSwitch::isSwitchOpen( SwitchDef::WARCRAFT,  );
		$userObj->changeFormation($formation,$isCraftOpenOrNot);
		EnAchieve::updateHeroFormation($this->uid, count($this->dataModify['va_formation']['formation']));
		return $changed;
	}
	
	public function getExtra()
	{
		return $this->dataModify['va_formation']['extra'];
	}
	
	public function addExtra($hid, $index)
	{
		$userObj = EnUser::getUserObj($this->uid);
		if ($index < 0 || $index >= self::getExtraSize($userObj->getLevel()))
		{
			throw new FakeException('invalid index:%d', $index);
		}
		if ($this->checkExist($hid, $index, 'extra') == true)
		{
			throw new FakeException('hid:%d already exist', $hid);
		}
		if ($this->isExtraOpen($index) == false)
		{
			throw new FakeException('index:%d is not open', $index);
		}
		
		$userObj->getHeroManager()->getHeroObj($hid);
		$this->dataModify['va_formation']['extra'][$index] = $hid;
		Logger::debug('add hid:%d in extra', $hid);
		
		$userObj->modifyBattleData();
		$userObj->getHeroManager()->updateSession();
		EnAchieve::updateFriendFormation($this->uid, count($this->dataModify['va_formation']['extra']));
	}
	
	public function delExtra($hid, $index)
	{
		$userObj = EnUser::getUserObj($this->uid);
		if ($index < 0 || $index >= self::getExtraSize($userObj->getLevel()))
		{
			throw new FakeException('invalid index:%d', $index);
		}
		if (!in_array($hid, $this->dataModify['va_formation']['extra']))
		{
			throw new FakeException('hid:%d no found', $hid);
		}
		if ($hid != $this->dataModify['va_formation']['extra'][$index]) 
		{
			throw new FakeException('invalid index:%d and hero:%d', $index, $hid);
		}
		
		unset($this->dataModify['va_formation']['extra'][$index]);
		Logger::debug('del hid:%d from extra', $hid);
		
		$userObj->modifyBattleData();
		$userObj->getHeroManager()->updateSession();
	}
	
	public function getExtopen()
	{
		if (!isset($this->dataModify['va_formation']['extopen']))
		{
			return array();
		}
		return $this->dataModify['va_formation']['extopen'];
	}
	
	public function isExtraOpen($index)
	{
		$extopen = $this->getExtopen();
		$arrExtraNeedGold = btstore_get()->FORMATION['arrExtraNeedGold'];
		$arrExtraNeedCraft = btstore_get()->FORMATION['arrExtraNeedCraft'];
		if (!in_array($index, $extopen) 
		&& (isset($arrExtraNeedGold[$index]) || isset( $arrExtraNeedCraft[$index] ) ) )
		{
			return false; 
		}
		else
		{
			return true;
		}
	}
	
	public function openExtra($index)
	{
		$userObj = EnUser::getUserObj($this->uid);
		if ($index < 0 || $index >= self::getExtraSize($userObj->getLevel()))
		{
			throw new FakeException('invalid index:%d', $index);
		}
		if ($this->isExtraOpen($index) == true) 
		{
			throw new FakeException('extra index:%d is open already', $index);
		}
		$formationConf = btstore_get()->FORMATION;
		if( isset( $formationConf['arrExtraNeedGold'][$index] ) )
		{
			$cost = $formationConf['arrExtraNeedGold'][$index];
			if ($userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_FORMATION_OPEN_EXTRA) == false)
			{
				throw new FakeException('no enough gold for:%d', $cost);
			}
		}
		elseif ( isset( $formationConf['arrExtraNeedCraft'][$index] ) ) 
		{
			if(!EnFormation::isPosValidByCraft($this->uid, $index))
			{
				throw new FakeException( 'index: %d not satisfy the req', $index );
			}
		}
		else 
		{
			throw new FakeException( 'invalid pos: %s, neighter gold nor craft need ', $index );
		}
		
		$this->dataModify['va_formation']['extopen'][] = $index;
	}
	
	public function getAttrExtra()
	{
		if (!isset($this->dataModify['va_formation']['attr_extra']))
		{
			return array();
		}
		return $this->dataModify['va_formation']['attr_extra'];
	}
	
	public function addAttrExtra($hid, $index)
	{
		if (!$this->isAttrExtraOpen($index))
		{
			throw new FakeException('attr extra index:%d is not open when add', $index);
		}
		
		if ($this->checkExist($hid, $index, 'attr_extra'))
		{
			throw new FakeException('hid:%d already exist', $hid);
		}
		
		$userObj = EnUser::getUserObj($this->uid);
		$userObj->getHeroManager()->getHeroObj($hid);//验证这个武将存在，如果不存在里面会抛异常
		$this->dataModify['va_formation']['attr_extra'][$index] = $hid;
		Logger::debug('add hid:%d in index:%d of attr extra', $hid, $index);
		
		$userObj->modifyBattleData();
		$userObj->getHeroManager()->updateSession();
	}
	
	public function delAttrExtra($hid, $index)
	{
		if (!$this->isAttrExtraOpen($index))
		{
			throw new FakeException('attr extra index:%d is not open when del', $index);
		}
		
		if (!in_array($hid, $this->dataModify['va_formation']['attr_extra']))
		{
			throw new FakeException('hid:%d not found, attr extra:%s', $hid, $this->dataModify['va_formation']['attr_extra']);
		}
		
		if ($hid != $this->dataModify['va_formation']['attr_extra'][$index])
		{
			throw new FakeException('invalid index:%d and hero:%d, attr extra:%s', $index, $hid, $this->dataModify['va_formation']['attr_extra']);
		}
		
		unset($this->dataModify['va_formation']['attr_extra'][$index]);
		Logger::debug('del hid:%d from attr extra', $hid);
		
		$userObj = EnUser::getUserObj($this->uid);
		$userObj->modifyBattleData();
		//$userObj->getHeroManager()->updateSession();
	}
	
	public function openAttrExtra($index)
	{
		if ($this->isAttrExtraOpen($index))
		{
			throw new FakeException('attr extra index:%d is open already', $index);
		}
		
		$userObj = EnUser::getUserObj($this->uid);
		$bag = BagManager::getInstance()->getBag($this->uid);
		$arrCost = btstore_get()->SECOND_FRIEND[$index + 1]['cost']->toArray();
		foreach ($arrCost as $aCost)
		{
			if (count($aCost) != 3) 
			{
				throw new ConfigException('invalid cost config:%s', $aCost);
			}
			
			$costType = intval($aCost[0]);
			if (1 == $costType) //金币 
			{
				$needGold = intval($aCost[2]);
				if (!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_FORMATION_OPEN_ATTR_EXTRA))
				{
					throw new FakeException('not enough gold:%d when open attr extra.', $needGold);
				}
			}
			else if (2 == $costType) //银币
			{
				$needSilver = intval($aCost[2]);
				if (!$userObj->subSilver($needSilver)) 
				{
					throw new FakeException('not enough silver:%d when open attr extra.', $needSilver);
				}
			}
			else if (3 == $costType) //物品
			{
				$needItem = intval($aCost[1]);
				$needItemCount = intval($aCost[2]);
				if (!$bag->deleteItembyTemplateID($needItem, $needItemCount))
				{
					throw new FakeException('not enough item:%d count:%d when open attr extra.', $needItem, $needItemCount);
				}
			}
			else 
			{
				throw new ConfigException('invalid cost type:%d', $costType);
			}
		}
		
		$this->dataModify['va_formation']['attr_extra_open'][] = $index;
	}
	
	public function getAttrExtraOpen()
	{
		if (!isset($this->dataModify['va_formation']['attr_extra_open']))
		{
			return array();
		}
		return $this->dataModify['va_formation']['attr_extra_open'];
	}
	
	public function isAttrExtraValid($index)
	{
		$curAttrExtraSize = $this->getAttrExtraSize(EnUser::getUserObj($this->uid)->getLevel());
		if ($index < 0 || $index >= $curAttrExtraSize)
		{
			return FALSE;
		}
		return TRUE;
	}
	
	public function isAttrExtraOpen($index)
	{
		if (!$this->isAttrExtraValid($index))
		{
			throw new FakeException('invalid attr extra index:%d because of level.', $index);
		}
	
		//SECOND_FRIEND的下标从1开始，而index是从0开始
		$attrExtraConf = btstore_get()->SECOND_FRIEND;
		if (!isset($attrExtraConf[$index + 1])) 
		{
			throw new ConfigException('invalid attr extra index:%d, not in conf:%s.', $index, $attrExtraConf);
		}
		
		$arrAttrExtraOpen = $this->getAttrExtraOpen();
		if (!empty($attrExtraConf[$index + 1]['cost']) && !in_array($index, $arrAttrExtraOpen))
		{
			return FALSE;
		}
	
		return TRUE;
	}
	
	public function checkExist($hid, $index, $field)
	{
		$oldHid = 0;
		$formation = $this->getSquad();
		if ($field == 'formation' && isset($formation[$index])) 
		{
			$oldHid = $formation[$index];
		}
		$extra = $this->getExtra();
		if ($field == 'extra' && isset($extra[$index])) 
		{
			$oldHid = $extra[$index];
		}
		$attrExtra = $this->getAttrExtra();
		if ($field == 'attr_extra' && isset($attrExtra[$index])) 
		{
			$oldHid = $attrExtra[$index];
		}
		$arrHid = array_merge($formation, $extra, $attrExtra);
	
		if (in_array($hid, $arrHid))
		{
			return true;
		}
		$heroManager = EnUser::getUserObj($this->uid)->getHeroManager();
		$baseHtid = $heroManager->getHeroObj($hid)->getBaseHtid();
		foreach ($arrHid as $value)
		{
			if ($value == $oldHid) 
			{
				continue;
			}
			if ($heroManager->getHeroObj($value)->getBaseHtid() == $baseHtid)
			{
				return true;
			}
		}
		return false;
	}
	
	public function update()
	{
		//目前只能在自己的连接中改自己的数据
		$guid = RPCContext::getInstance()->getUid() ;
		if( $guid != $this->uid)
		{
			throw new InterException('cant update formation in other connection. uid:%d session.uid:%d', $this->uid, $guid);
		}
		
		//关键：检查数据合法性
		$userObj = EnUser::getUserObj($this->uid);
		$heroManager = $userObj->getHeroManager();
		//检查阵型和阵容
		$info = $this->dataModify['va_formation']['formation'];
		$arrOpenPos = self::getArrOpenPos($userObj->getLevel());
		$squadSize = self::getSquadSize($userObj->getLevel());
		$mapIndex = array();
		$mapPos = array();
		foreach($info as $hid => $value)
		{
			$pos = $value['pos'];
			$index = $value['index'];
			if (!in_array($pos, $arrOpenPos) || $index >= $squadSize)
			{
				throw new InterException('invalid pos or index. squadSize:%d, arrOpenPos:%s formation:%s', $squadSize, $arrOpenPos, $info);
			}
			if (isset($mapPos[$pos]) || isset($mapIndex[$index]))
			{
				throw new InterException('duplicated pos or index. formation:%s', $info);
			}
			$mapPos[$pos] = 1;
			$mapIndex[$index] = 1;
			$heroManager->getHeroObj($hid);
		}
		//检查我的小伙伴们
		$extra = $this->getExtra();
		$extopen = $this->getExtopen();
		$extraSize = self::getExtraSize($userObj->getLevel());
		foreach($extra as $index => $hid)
		{
			if ($this->isExtraOpen($index) == false)
			{
				throw new InterException('invalid index. extraSize:%d extopen:%s extra:%s', $extraSize, $extopen, $extra);
			}
			$heroManager->getHeroObj($hid);
		}
		
		// 检查我的属性小伙伴们
		$attrExtra = $this->getAttrExtra();
		$attrExtraOpen = $this->getAttrExtraOpen();
		$attrExtraSize = $this->getAttrExtraSize($userObj->getLevel());
		foreach ($attrExtra as $index => $hid)
		{
			if ($this->isAttrExtraOpen($index) == false) 
			{
				throw new InterException('invalid index. attrExtraSize:%d attrExtraOpen:%s attrExtra:%s', $attrExtraSize, $attrExtraOpen, $attrExtra);
			}
			$heroManager->getHeroObj($hid);
		}
					
		$arrField = array();
		foreach ($this->data as $key => $value)
		{
			if ($this->dataModify[$key]!= $value)
			{
				$arrField[$key] = $this->dataModify[$key];
			}
		}
		if(! empty($arrField) )
		{
			FormationDao::update($this->dataModify['uid'], $arrField);
			$this->data = $this->dataModify;
			if ($this->uid == RPCContext::getInstance()->getUid())
			{
				RPCContext::getInstance()->setSession(FormationDef::SESSION_KEY_FORMATION, $this->dataModify);
			}
		}
	}
	
	public static function getSquadSize($userLevel)
	{		
		$arrNumNeedLevel = btstore_get()->FORMATION['arrNumNeedLevel'];
		
		$openNum = 0;
		foreach( $arrNumNeedLevel as $level => $num)
		{
			if( $userLevel < $level)
			{
				break;
			}
			if( $num > FormationDef::FORMATION_SIZD)
			{
				throw new ConfigException('invalid config. arrNumNeedLevel:%s', $arrNumNeedLevel);
			}
			$openNum = $num;
		}
		return $openNum;
	} 
	
	public static function getExtraSize($userLevel)
	{
		$arrExtraNeedLevel = btstore_get()->FORMATION['arrExtraNeedLevel'];
	
		$openNum = 0;
		foreach( $arrExtraNeedLevel as $level => $num)
		{
			if( $userLevel < $level)
			{
				break;
			}
			if( $num > FormationDef::EXTRA_SIZD)
			{
				throw new ConfigException('invalid config. arrExtraNeedLevel:%s', $arrExtraNeedLevel);
			}
			$openNum = $num;
		}
		return $openNum;
	}
	
	public function getAttrExtraSize($userLevel)
	{
		$count = 0;
		
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$maxCount = FormationDef::ATTR_EXTRA_SIZE;
		if ($maxCount < count($attrExtraConf))
		{
			$maxCount = count($attrExtraConf);
		}
		
		$arrAttrExtraNeedCraft = btstore_get()->FORMATION['arrAttrExtraNeedCraft']->toArray();
		for ($i = 0; $i < $maxCount; ++$i)
		{
			if (!isset($arrAttrExtraNeedCraft[$i])) 
			{
				throw new ConfigException('no index:%d in conf:%s', $i, $arrAttrExtraNeedCraft);
			}
			
			if (EnFormation::isPosValidByCraft($this->uid, $i, TRUE)) 
			{
				++$count;
			}
		}
		
		return $count;
	}
	
	public static function getArrOpenPos($userLevel)
	{
		$arrOpenSeq = btstore_get()->FORMATION['arrOpenSeq']->toArray();
		$arrOpenNeedLevel = btstore_get()->FORMATION['arrOpenNeedLevel'];
		
		$openNum = 0;
		foreach( $arrOpenNeedLevel as $level )
		{
			if( $userLevel < $level)
			{
				break;
			}
			if( $arrOpenSeq[$openNum] >= FormationDef::FORMATION_SIZD)
			{
				throw new ConfigException('invalid config. arrOpenSeq:%s', $arrOpenSeq);
			}
			$openNum++;
		}
		
		return array_slice($arrOpenSeq, 0, $openNum);
	}
	
	//===========阵法添加
	public function getWarcraft()
	{
		if( isset( $this->dataModify['va_formation']['warcraft'] ) )
		{
			return $this->dataModify['va_formation']['warcraft'];
		}
		else
		{
			return array();
		}
	}
	
	public function setWarcraft( $warcraftInfo )
	{
		$this->dataModify['va_formation']['warcraft'] = $warcraftInfo;
	}
	
	public function getCurWarcraft()
	{
		return $this->dataModify['craft_id'];
	}
	
	
	public function setCurWarcraft( $craftId )
	{
		$this->dataModify['craft_id'] = $craftId;
	}
	
	public function getAttrExtraLevel()
    {
        if(!isset($this->dataModify['va_formation']['attr_extra_lv']))
        {
            return array();
        }
        return $this->dataModify['va_formation']['attr_extra_lv'];
    }

    public function getAttrExtraLvByIndex($index)
    {
        $arrLv = $this->getAttrExtraLevel();
        return empty($arrLv[$index]) ? 0 : $arrLv[$index];
    }

    public function addAttrExtraLvOfIndex($index)
    {
        $curLv = $this->getAttrExtraLvByIndex($index);
        $this->dataModify['va_formation']['attr_extra_lv'][$index] = $curLv + 1;
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */