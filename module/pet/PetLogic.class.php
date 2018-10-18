<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PetLogic.class.php 248679 2016-06-29 03:49:24Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/PetLogic.class.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-06-29 03:49:24 +0000 (Wed, 29 Jun 2016) $
 * @version $Revision: 248679 $
 * @brief 
 *  
 **/
class PetLogic
{
	public static function getAllPet( $uid  )
	{
		$petMger = PetManager::getInstance( $uid );
		$allPet = $petMger->getAllPet();
		Logger::debug( 'allpet in getAllPet: %s', $allPet );
		
		return $allPet;
	}
	

	public static function getKeeperInfo( $uid )
	{
		$keeperInst = KeeperObj::getInstance($uid);
		
		return $keeperInst->getKeeperInfo();
	}
	
	public static function getPetConf( $uid,$petid )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$petTmpl = $petInfo[PetDef::PETTMPL];
		$petConf = btstore_get()->PET;
		if ( !isset( $petConf[$petTmpl] ) )
		{
			throw new ConfigException( 'no such pettmpl: %d, petid: %d', $petTmpl, $petid );
		}
		return 	$petConf[$petTmpl];	
	}
	
	public static function checkItemValid( $itemId )
	{
		$feedItem = ItemManager::getInstance()->getItem( $itemId );
		if ( $feedItem == null )
		{
			throw new FakeException( 'itemId: %d is invalid' , $itemId );
		}
		
		if ( $feedItem->getItemType() !=  ItemDef::ITEM_TYPE_FEED )
		{
			throw new FakeException( 'itemId: %d isnot feeditem' , $itemId );
		}
	}
	
	public static function feedPetByItem( $uid , $petid, $itemId, $itemNum )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		//获取喂养经验
		$feedExp = self::getItemExp($itemId, $itemNum);
		$ratio = self::getCriRio();
		$feedExp = $feedExp * $ratio;
		if ( self::canAddExp( $uid , $petid ) == 0 )
		{
			throw new FakeException( 'petid: %d has reached maxLv', $petid );
		}
		$petMgr->addExp($petid, $feedExp);
		//self::addExpToPet( $uid, $petid, $feedExp );
		//删除物品
		$bag = BagManager::getInstance()->getBag( $uid );
		if ( !$bag->decreaseItem( $itemId, $itemNum ) ) 
		{
			throw new FakeException( 'decrease num: %d, itemId: %d failed' , $itemNum , $itemId );
		}
		
		$bag->update();
		$petMgr->update();	
		//有可能暴击，所以返回给前端经验
		return $feedExp;
	}
	
	public static function getCriRio()
	{
		$ratio = 1;
		$confCost = btstore_get()->PET_COST[ 1 ];
		$randNum = rand( 1 , PetCfg::RATIO_BASE );
		if ( $randNum < $confCost[ 'itemFeedCriRto' ] )
		{
			$ratioArr = Util::backSample( $confCost[ 'itemFeedWeightArr' ], 1);
			$ratio = intval( $confCost[ 'itemFeedWeightArr' ][$ratioArr[ 0 ]][ 'ratio' ] );
			$ratio = $ratio/PetCfg::RATIO_BASE;
		}
		if ( $ratio < 1 )
		{
			throw new FakeException( 'ratio: %d should not < 1', $ratio );
		}
		return $ratio;
	}
	
	public static function canAddExp( $uid , $petid )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
	
		$petConf = self::getPetConf($uid, $petid);
		$expTblId = $petConf['expTbl'];
		$expTbl = btstore_get()->EXP_TBL[ $expTblId ];
	
		if ( !isset( $expTbl[$petInfo[ 'level' ] + 1] ) )
		{
			return 0;
		}
		if ( $petInfo[PetDef::LEVEL] >= $userLevel )
		{
			return 0;
		}
	
		$maxLvInTbl = 0;
		foreach ( $expTbl as $lv => $needExp )
		{
			if ($lv > $maxLvInTbl)
			{
				$maxLvInTbl = $lv;
			}
		}
	
		$maxUpLv = $userLevel<$maxLvInTbl? $userLevel:$maxLvInTbl;
		$petExpNow = $petInfo[PetDef::EXP];
		$needTotalExp = $expTbl[$maxUpLv];
	
		$canAddExp = $needTotalExp - $petExpNow;
	
		return $canAddExp;
	}
	
	public static function feedToLimitation( $uid , $petid )
	{
		$petMgr = PetManager::getInstance( $uid );
		$canAddExp = self::canAddExp( $uid , $petid );
		if ( $canAddExp == 0 )
		{
			throw new FakeException( 'petid: %d has reached maxLv', $petid );
		}
	
		//获取拥有的宠物饲料
		$bag = BagManager::getInstance()->getBag( $uid );
		$itemIds = $bag->getItemIdsByItemType( ItemDef::ITEM_TYPE_FEED );
		if ( empty( $itemIds ) )
		{
			throw new FakeException( 'uid: %d has no feedItem', $uid );
		}
		//排序并获得数量
		$itemIdWithExp = array();
		$itemMgr = ItemManager::getInstance();
		foreach ( $itemIds as $eachId )
		{
			$itemInstance = $itemMgr->getItem( $eachId );
				
			$itemIdWithExp[ $eachId ][ 'itemExp' ] = self::getItemExp( $eachId );
			$itemIdWithExp[ $eachId ][ 'itemId' ] = $eachId;
			$itemIdWithExp[ $eachId ][ 'itemNum' ] = $itemInstance->getItemNum();
		}
		$itemIdArr =  Util::arrayExtract( $itemIdWithExp , 'itemId' );
		$itemExpArr = Util::arrayExtract( $itemIdWithExp , 'itemExp' );
		array_multisort( $itemExpArr , SORT_DESC , $itemIdArr , SORT_ASC , $itemIdWithExp );
		Logger::debug('after multisort, result: %s',$itemIdWithExp );
	
		//排好序了，经验要给前端，因为有暴击
		$reachLimit = false;
		$criTimes = 0;
		$addPoints = 0;
		//要喂到刚刚好，开始循环喂
		$addExpFromItem = 0;
		foreach ( $itemIdWithExp as $key => $item )
		{
			while ( $itemIdWithExp[ $key ][ 'itemNum' ]> 0 )
			{
				if ( $addExpFromItem >= $canAddExp )
				{
					$reachLimit = true;
					break;
				}
				//消耗物品，给宠物加经验
				$itemIdWithExp[ $key ][ 'itemNum' ]--;
				if ( !$bag->decreaseItem( $itemIdWithExp[ $key ][ 'itemId' ], PetCfg::CONSUME_NUM ) )
				{
					throw new FakeException( 'nosuch item, itemId: %d' ,  $itemIdWithExp[ $key ][ 'itemId' ]);
				}
				$ratio = 1;
				$ratio = self::getCriRio();
				if ( $ratio > 1 )
				{
					$criTimes++;
				}
				$feedExp = $itemIdWithExp[ $key ][ 'itemExp' ] * $ratio;
				$addExpFromItem += $feedExp;
			}
			//判定是否到了极限了
			if( $reachLimit )
			{
				break;
			}
		}
		$petMgr->addExp($petid, $addExpFromItem);
		//self::addExpToPet($uid, $petid, $addExpFromItem);
		
		//更新宠物和用户及背包
		$bag->update();
		$petMgr->update();
	
		//返回给前端这次的一键喂养
		return array( 'totalExp' => $addExpFromItem, 'criTimes' => $criTimes ) ;
	}
	
	
	public static function getItemExp( $itemId , $itemNum = 1 )
	{
		self::checkItemValid( $itemId );
		$item = ItemManager::getInstance()->getItem( $itemId );
		
		return ( $item->getFeedExp())* $itemNum ;
	}
	
	public static function addExpToPet( $uid, $petid , $exp )
	{
		$petMgr = PetManager::getInstance( $uid );
		$addPoints = $petMgr->addExp($petid, $exp);
		//更新发起方做
		return $addPoints;
	}
	
	public static  function openSquandSlot($uid , $flag)
	{
		$keeperInst = KeeperObj::getInstance($uid);
		$vaKeeper = $keeperInst->getVaKeeper();
		$squandSlotNum = count( $vaKeeper['setpet']);
		$userVip = EnUser::getUserObj($uid)->getVip();
		$vipConf = btstore_get()->VIP[$userVip];
		if ( $squandSlotNum >= $vipConf['maxPetFence'] )
		{
			throw new FakeException( 'squandSlotNum: %d already reach max: %d',$squandSlotNum, $vipConf['maxPetFence'] );
		}
		$nextSlot = $squandSlotNum ;
		
		$petCostConf = btstore_get()->PET_COST[1]['squandSlotOpenArr'];
		if (!isset( $petCostConf[$nextSlot] ))
		{
			throw new FakeException( '%d reach max squand slot', $nextSlot );
		}
		
		$user = EnUser::getUserObj( $uid );
		$bagObj = BagManager::getInstance()->getBag($uid);
		if ($flag == 0) //使用金币
		{
			$needgold = $petCostConf[$nextSlot][1];
			if ( $needgold <= 0 )
			{
				throw new ConfigException('need gold: %d', $needgold);
			}
			
			if ( !$user->subGold( $needgold , StatisticsDef::ST_FUNCKEY_PET_OPEN_SQUAND_SLOT) )
			{
				throw new FakeException( 'lack gold, need %d', $needgold );
			}
		}
		else //使用物品
		{
			$itemInfo = btstore_get()->PET_COST[1]['openSquandSlotCostItems'];
			$itemtmpId = $itemInfo[0];
			$itemnum = $itemInfo[1];
			if (!$bagObj->deleteItembyTemplateID($itemtmpId, $itemnum))
			{
				throw new FakeException( 'this user doesnt have enough items, need %d,num %d', $itemtmpId,$itemnum);
			}
		}
		$bagObj->update();
		$user->update();
		$keeperInst->openSquandSlot();
		$keeperInst->update();
		
		return 'ok';
	}
	
	public static function squandUpPet($uid, $petid, $pos)
	{
		self::checkPetBelongTo($petid, $uid);//在这个函数中调了一下petmgr 
		$keeperInst = KeeperObj::getInstance($uid);
		$vaKeeper = $keeperInst->getVaKeeper();
		if (!isset($vaKeeper['setpet'][$pos]))
		{
			throw new FakeException( 'pos: %d id not opened, all opened are: %s', $pos, $vaKeeper['setpet'] );
		}
		if ( $vaKeeper['setpet'][$pos]['status'] == 1 )
		{
			throw new FakeException( 'pet: %d is in fight', $vaKeeper['setpet'][$pos]['petid'] );
		}
		
		$petMgr = PetManager::getInstance( $uid );
		$allPet = $petMgr->getAllPet();
		Logger::debug('all pet are: %s', $allPet);
		Logger::debug('vaKeeper are: %s',$vaKeeper );
		
		$petConf = btstore_get()->PET;
		foreach ($vaKeeper['setpet'] as $key => $squandInfo)
		{
			Logger::debug('$squandInfo : %s', $squandInfo);
			$willUpPetTmpl = $allPet[$petid][PetDef::PETTMPL];
			
			if ( $key != $pos && $squandInfo['petid']!= 0 
			&&  $petConf[$willUpPetTmpl]['petType'] == $petConf[$allPet[$squandInfo['petid']][PetDef::PETTMPL]]['petType'] )
			{
				throw new FakeException( 'same pettmpl, petid: %d, petid: %d',$squandInfo['petid'], $petid  );
			}
		}
		if (!empty( $vaKeeper['setpet'][$pos]['petid'] ))
		{
			//下阵之前refresh一下宠物经验，一定要在下一个宠物上阵之前
			$petMgr->adaptPetExp();
		}
		
		$keeperInst->squandUpPet($petid,$pos);
		$petMgr->setTrainTime($petid, Util::getTime());
		
		$petMgr->update();
		$keeperInst->update();
	}
	
	public static function squandDownPet($uid, $petid)
	{
		$petMgr = PetManager::getInstance($uid);//这是必须的
		
		$keeperInst = KeeperObj::getInstance($uid);
		$vaKeeper = $keeperInst->getVaKeeper();
		
		$find = false;
		foreach ( $vaKeeper['setpet'] as $pos => $info )
		{
			if ( $petid == $info['petid'] )
			{
				$find = true;
				$realPos = $pos;
				break;
			}
		}
		
		if (!$find)
		{
			throw new FakeException( '$petid: %d not squand, all opened are: %s', $petid, $vaKeeper['setpet'] );
		}
		if ( $vaKeeper['setpet'][$realPos]['status'] == 1 || empty( $vaKeeper['setpet'][$realPos]['petid'] ) )
		{
			throw new FakeException( 'pet: %d is in fight or no pet', $vaKeeper['setpet'][$realPos]['petid'] );
		}
		
		$petMgr->update();
		$keeperInst->squandDownPet($realPos);
		$keeperInst->update();
	}
	
	
	public static function checkPetBelongTo( $petid,$uid )
	{
		//玩家自己调用，是高效的
		$petMgr = PetManager::getInstance( $uid );
		$allPet = $petMgr->getAllPet();
		if ( !isset( $allPet[$petid] ) )
		{
			throw new FakeException( 'petid: %d not belong to uid: %d', $petid, $uid );
		}
	}
	
	public static function fightUpPet( $uid, $petid )
	{
		self::checkPetBelongTo($petid, $uid);//必须的
		
		$keeperInst = KeeperObj::getInstance($uid);
		$vaKeeper = $keeperInst->getVaKeeper();
		
		$squanded = false;
		$fighted = false;
		foreach ( $vaKeeper['setpet'] as $pos => $squandInfo )
		{
			if ($squandInfo['petid'] == $petid)
			{
				$squanded = true;
				if ( $squandInfo['status'] == 1 )
				{
					$fighted = true;
				}
			}
		}
		
		if (!$squanded)
		{
			throw new FakeException( 'petid: %d is not squanded', $petid );
		}
		
		if ($fighted)
		{
			throw new FakeException( 'petid: %d already fighted', $petid );
		}
		
		$keeperInst->fightUpPet( $petid );
		$fightForce = self::calcuPetFightforce($uid, $petid);
		$keeperInst->setPetFightforce($petid, $fightForce);
		$keeperInst->update();
		
		$user= EnUser::getUserObj($uid);
		$user->modifyBattleData();
	}
	
	public static function swallowPetArr( $uid,$petid,$bepetidArr )
	{
		if ( empty( $bepetidArr ) )
		{
			return;
		}
        $petMgr = PetManager::getInstance($uid);

        foreach($bepetidArr as $tmpPetid)
        {
            $evolveLevel = $petMgr->getEvolveLevel($tmpPetid);
            if($evolveLevel > 0)
            {
                throw new FakeException("pet:%d evolve > 0, cant sell", $tmpPetid);
            }
        }

        $washReturnNum = 0; //洗练返还数量
		foreach ( $bepetidArr as $key => $bepetid )
		{
            $washReturnNum += self::calPetWashReturn($uid, $bepetid);
			$ret = self::swallowPet($uid, $petid, $bepetid);
		}

		$petMgr->update();
        $bag = BagManager::getInstance()->getBag($uid);
        $bag->addItemByTemplateID(PetDef::WASH_RETURN_STONE, $washReturnNum, true);
        $bag->update();

		//return $petMgr->getOnePetInfo($petid);
		return array(
		    'petinfo'=>$petMgr->getOnePetInfo($petid),
		    'item'=>array(PetDef::WASH_RETURN_STONE=>$washReturnNum),
		);
	}
	
	public static function swallowPet( $uid,$petid,$bepetid )
	{
		//被吞噬的宠物在驯养和出战状态下不能能被吞噬
		if ( $petid == $bepetid )
		{
			throw new FakeException( 'petid and bepetid is same %d', $petid );
		}
		
		self::checkPetBelongTo($petid, $uid);
		self::checkPetBelongTo($bepetid, $uid);
		
		$vaKeeper = KeeperObj::getInstance($uid)->getVaKeeper();
		foreach ( $vaKeeper['setpet'] as $squandPet => $squandPetInfo )
		{
			if ( $bepetid == $squandPetInfo['petid'] )
			{
				throw new FakeException( 'petid: %d is in squandOrFight %s:', $bepetid, $vaKeeper['setpet'] );
			}
		}
		
		$petmgr = PetManager::getInstance($uid);
		
		$petInfo = $petmgr->getOnePetInfo($petid);
		$bePetInfo = $petmgr->getOnePetInfo( $bepetid );
		$petTmpl = $petInfo[PetDef::PETTMPL];
		$petConf = btstore_get()->PET[$petTmpl];

		if ( $petTmpl != $bePetInfo[PetDef::PETTMPL] )
		{
			self::swallowDifferentPet($uid, $petid, $bepetid);
			return;
			throw new FakeException( 'tpmlid different, petid: %d, tmpl: %d, bepetid: %d, tmpl: %d',$petid, $petTmpl, $bepetid, $bePetInfo[PetDef::PETTMPL] );
		}
		
		$petlevel = $petInfo[PetDef::LEVEL];
		
		$bePetExp = $bePetInfo[PetDef::EXP];
		
		$haveSwallowed = $petInfo[PetDef::SWALLOW];
		$canSwallowNum = 0;
		$canSwallowArr = $petConf['canSwallowArr'];
		foreach ( $canSwallowArr as $canInde => $canSwallowInfo )
		{
			if ( $petlevel < $canSwallowInfo[0] )
			{
				$canSwallowNum = $canSwallowInfo[1] - $haveSwallowed;
				break;
			}
		}
		
		//吞噬的数量相当于被吞噬的宠物的吞噬数+1
		$bePetSwallowedNum = $bePetInfo[PetDef::SWALLOW];
		if ( $canSwallowNum < $bePetSwallowedNum + 1 )
		{
			throw new FakeException( 'petid: %d is full, beswallow id %d', $petid,$bepetid );
		}
		
		$addPoint = $petConf['swallowAddPoint']*($bePetSwallowedNum + 1);
		
		$petmgr->deletePet($bepetid);
		$petmgr->addSkillPoint($petid,$addPoint);
		$petmgr->addSwallowNum($petid, $bePetSwallowedNum + 1); 
		
		if ( self::canAddExp($uid, $petid) != 0 )
		{
			//这里也会有可能加技能点
			self::addExpToPet($uid, $petid, $bePetExp);
		}
	}
	
	public static function swallowDifferentPet( $uid,$petid,$bepetid )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$bePetInfo = $petMgr->getOnePetInfo($bepetid);
		
		if (self::canAddExp($uid, $petid) == 0)
		{
			throw new FakeException( 'swallow diff pet, petid: %d exp full',$petid );
		}
		$swallowExp = $bePetInfo[PetDef::EXP];
		if ( $swallowExp <= 0 )
		{
			throw new FakeException( 'bepetid:%d have no exp', $bepetid );
		}
		
		$petMgr->addExp($petid, $swallowExp);
		$petMgr->deletePet( $bepetid );
	}

    /**
     * 计算有洗练属性的宠物的返还
     * @param $uid
     * @param $petId
     * @return int 返还数量
     */
    public static function calPetWashReturn($uid, $petId)
    {
        $petMgr = PetManager::getInstance($uid);
        $petInfo = $petMgr->getOnePetInfo($petId);

        if(empty($petInfo[PetDef::VAPET]['confirmed']))
        {
            return 0;
        }

        $petTplId = $petInfo[PetDef::PETTMPL];
        $petConf = btstore_get()->PET[$petTplId];
        if(empty($petConf['washReturn']))
        {
            return 0;
        }

        /**
         * 1 计算洗练的消耗
         */
        $confirmed = $petInfo[PetDef::VAPET]['confirmed'];
        $totalWashValue = 0;
        foreach($confirmed as $attrId => $attrValue)
        {
            $totalWashValue += $attrValue;
        }

        $itemNumConf = $petConf['itemNum']->toArray(); //已按照key排序

        $arrKey = array_keys($itemNumConf);
        sort($arrKey);

        $needReturnNum = 0;
        for($i = 0; $i < count($arrKey) - 1; ++$i)
        {
            if($arrKey[$i + 1] > $totalWashValue)
            {
                $needReturnNum += ($totalWashValue - $arrKey[$i]) * $itemNumConf[$arrKey[$i]];
                break;
            }

            $needReturnNum += ($arrKey[$i + 1] - $arrKey[$i]) * $itemNumConf[$arrKey[$i]];
        }

        $needReturnNum /= $petConf['washReturn'];

        return intval($needReturnNum);
    }
	
	public static function collectProduction($uid,$petid)
	{
		self::checkPetBelongTo($petid, $uid);
		$keeperInst = KeeperObj::getInstance($uid);
		$vaKeeper = $keeperInst->getVaKeeper();
		$setPet = $vaKeeper['setpet'];
		$setTime = Util::getTime();
		
		$petSquandUpOrFightUp = false;
		$petPos = 0;
		foreach ( $setPet as $pos => $setpetInfo )
		{
			if ( $setpetInfo['petid'] == $petid )
			{
				$petSquandUpOrFightUp = true;
				$setTime = $setpetInfo['producttime'];
				$petPos = $pos;
				break;
			}
		}
		if ( !$petSquandUpOrFightUp )
		{
			throw new FakeException( 'petid: %d not squandup or fightup',$petid );
		}
		
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$petVa = $petInfo[PetDef::VAPET];
		
		$productSkillId = $petVa['skillProduct'][0]['id'];
		if ( empty( $productSkillId ) )
		{
			throw new FakeException( 'petid: %d no product skill',$petid );
		}
		
		
		$productSkillIdLv = $petVa['skillProduct'][0]['level'];
		if (!self::skillEfficient($uid,$petid,$productSkillId))
		{
			throw new FakeException( 'petid: %d skillid: %d not efficient',$petid, $productSkillId );
		}
		
		if ( !empty( $petVa['skillTalent'] ) )
		{
			foreach ( $petVa['skillTalent'] as $talentSkillKey => $talentSkillInfo)
			{
				if ( !empty($talentSkillInfo['id'] ) )
				{
					if(self::skillEfficient($uid,$petid,$talentSkillInfo['id']))
					{
						Logger::debug('talent skillid: %d is efficient', $talentSkillInfo['id']);
						$talent = btstore_get()->PETSKILL[$talentSkillInfo['id']];
						$productSkillIdLv += $talent['productSkillLvInc'];
					}
					
				}
			}
		}
		
		$skillInfo = btstore_get()->PETSKILL[ $productSkillId ];
		
		if ( Util::getTime() - $setTime < $skillInfo['productSkillCdArr'][$productSkillIdLv] )
		{
			throw new FakeException( 'settime: %d, need: %d', $setTime, $skillInfo['productSkillCdArr'][$productSkillIdLv] );
		}
		
		$keeperInst->setProductTime( $petPos, Util::getTime() );
		
		//shiyu,修复数据，为了更及时是排名更加及时的优化，
		$fightPetId = $keeperInst->getFightPet();
		if( $petid == $fightPetId )
		{
			$fightForce = self::calcuPetFightforce($uid, $petid);
			$keeperInst->setPetFightforce($petid, $fightForce);
		}
		
		$production = $skillInfo['productSkillArr'];
		
  	    //key是从0开始的，宠物技能等级是从1开始的
		if ( empty( $productSkillIdLv ) ) 
		{
			throw new ConfigException( 'lv is empty, allskill: %s', $petInfo );
		}
		$realProduction = array();
		foreach ( $production as $key => $productionForOneLevel )
		{
			if ( $productSkillIdLv >= $key+1 )
			{
				$realProduction = $productionForOneLevel;
			}
		}
 	
		Logger::debug('reward for pet is:%s',$realProduction);
		//发奖励
		RewardUtil::reward3DArr($uid, array($realProduction), StatisticsDef::ST_FUNCKEY_PET_PRODUCTION);
		
		$keeperInst->update();
		$user = EnUser::getUserObj($uid);
		$user->update();
		$bag = BagManager::getInstance()->getBag($uid);
		$bag->update();
	}
	
	public static function collectAllProduction($uid)
	{
	    $keeperInst = KeeperObj::getInstance($uid);
	    $vaKeeper = $keeperInst->getVaKeeper();
	    $setPet = $vaKeeper['setpet'];
	    $petMgr = PetManager::getInstance($uid);
	    
	    $petPos = 0;
	    $canCollectPetArr = array();
	    $realProduction = array();
	    foreach ( $setPet as $pos => $setpetInfo )
	    {
	        $petid = $setpetInfo['petid'];
	        if (empty($petid))
	        {
	            continue;
	        }
	        $setTime = $setpetInfo['producttime'];
	        $petPos = $pos;
	        
	        $petInfo = $petMgr->getOnePetInfo($petid);
	        $petVa = $petInfo[PetDef::VAPET];
	         
	        $productSkillId = $petVa['skillProduct'][0]['id'];
	        
	        if ( empty( $productSkillId ) )
	        {
	            continue;
	        }
	        
	        $productSkillIdLv = $petVa['skillProduct'][0]['level'];
	        if (!self::skillEfficient($uid,$petid,$productSkillId))
	        {
	            continue;
	        }
	         
	        if ( !empty( $petVa['skillTalent'] ) )
	        {
	            foreach ( $petVa['skillTalent'] as $talentSkillKey => $talentSkillInfo)
	            {
	                if ( !empty($talentSkillInfo['id'] ) )
	                {
	                    if(self::skillEfficient($uid,$petid,$talentSkillInfo['id']))
	                    {
	                        Logger::debug('talent skillid: %d is efficient', $talentSkillInfo['id']);
	                        $talent = btstore_get()->PETSKILL[$talentSkillInfo['id']];
	                        $productSkillIdLv += $talent['productSkillLvInc'];
	                    }
	                }
	            }
	        }
	        
	        $skillInfo = btstore_get()->PETSKILL[ $productSkillId ]->toArray();
	        
	        if ( Util::getTime() - $setTime < $skillInfo['productSkillCdArr'][$productSkillIdLv] )
	        {
	            continue;
	        }
	        
	        $keeperInst->setProductTime( $petPos, Util::getTime() );
	         
	        //shiyu,修复数据，为了更及时是排名更加及时的优化，
	        $fightPetId = $keeperInst->getFightPet();
	        if( $petid == $fightPetId )
	        {
	            $fightForce = self::calcuPetFightforce($uid, $petid);
	            $keeperInst->setPetFightforce($petid, $fightForce);
	        }
	         
	        $production = $skillInfo['productSkillArr'];
	         
	        //key是从0开始的，宠物技能等级是从1开始的
	        if ( empty( $productSkillIdLv ) )
	        {
	            throw new ConfigException( 'lv is empty, allskill: %s', $petInfo );
	        }
	        $preRealProduction = array();
	        foreach ( $production as $key => $productionForOneLevel )
	        {
	            if ( $productSkillIdLv >= $key+1 )
	            {
	                $preRealProduction = $productionForOneLevel;
	            }
	        }
	        
	        $realProduction = array_merge($realProduction,array($preRealProduction));
	        $canCollectPetArr[] = $petid;
	    }
	    
	    if (empty($canCollectPetArr))
	    {
	        throw new FakeException("have none production to collection!!!");
	    }
	    
	    Logger::debug('reward for pet is:%s',$realProduction);
	    $return = array('err' => '', 'petIdsArr' => array());
	    $return['petIdsArr'] = $canCollectPetArr;
	    //发奖励
	    try{
	        RewardUtil::reward3DArr($uid, $realProduction, StatisticsDef::ST_FUNCKEY_PET_PRODUCTION);
	    }
	    catch (Exception $e)
	    {
	        Logger::warning("something wrong !err is %s",$e->getTraceAsString());
	        $return['err'] =  'addProErr';
	        return $return;
	    }
	    
	    $keeperInst->update();
	    $user = EnUser::getUserObj($uid);
	    $user->update();
	    $bag = BagManager::getInstance()->getBag($uid);
	    $bag->update();
	    
	    $return['err'] = 'ok';
	    return $return;
	}
	
	public static function skillEfficient( $uid, $petid,$skillId )
	{
		//空技能是无效的,外边应该拦住了
		if ( empty($skillId) ) 
		{
			Logger::warning('empty skillId in skillEfficient');
			return false;
		}
		//根据配置表的生效条件和自身的情况来判定技能是否生效
		$skillConf = btstore_get()->PETSKILL[$skillId];
		if (  $skillConf['specialNeed'] == PetDef::NO_SPECIAL)
		{
			//没有特殊要求，直接生效
			return true;
		}
		
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		
		$productSkills = $petInfo[PetDef::VAPET]['skillProduct'];
		$normalSkills = $petInfo[PetDef::VAPET]['skillNormal'];
		$talentSkill = $petInfo[PetDef::VAPET]['skillTalent'];
		$allSkills = array_merge( $normalSkills, $talentSkill, $productSkills );
		$allSkillIdsArr = Util::arrayExtract($allSkills, 'id');

		//有特殊技能，取出生效条件
		$specialCondArr = $skillConf['specialNeedCond'];
		$petVa = $petInfo[PetDef::VAPET];
		Logger::debug( 'petva : %s, specialneed are: %s ', $petVa, $specialCondArr );
		//技能生效条件之技能连携
		if ( $skillConf['specialNeed'] == PetDef::SPECIAL_SKILLARR )
		{
			foreach ( $specialCondArr as $key => $skillId )
			{
				if ( !in_array( $skillId , $allSkillIdsArr) ) 
				{
					return false;
				}
			}
		}
		//技能生效条件之宠物连携
		elseif ( $skillConf['specialNeed'] == PetDef::SPECIAL_PETARR )
		{
			//后期改的宠物羁绊需求
			$allPetTmpl = self::getPetHandbookInfo($uid);
			Logger::debug('allPetTmpl: %s',$allPetTmpl );
			foreach ( $specialCondArr as $key2 => $petTid )
			{
				if (!in_array($petTid, $allPetTmpl))
				{
					return false;
				}
			}
		}
		return true;
	}
	
	public static function getAdditionArr($uid,$petid = 0)
	{
		$additioinArr = array();
		if( $petid != 0 )
		{
			//得到这个宠物的所有技能
			$petMgr = PetManager::getInstance($uid);
			$petInfo = $petMgr->getOnePetInfo($petid);
			
			$productSkills = $petInfo[PetDef::VAPET]['skillProduct'];
			$normalSkills = $petInfo[PetDef::VAPET]['skillNormal'];
			
			/********************************************/
			//所有普通技能的等级通过进阶加成
			$curEvolveLevel = $petMgr->getEvolveLevel($petid);
			$evolveSkillConf = btstore_get()->PET[$petInfo[PetDef::PETTMPL]]['evolveSkill']->toArray();
			
			$evolveAddLevel = 0;
			foreach ($evolveSkillConf as $l => $addNum)
			{
			    if ($curEvolveLevel >= $l)
			    {
			        $evolveAddLevel += $addNum;
			    }
			}
			foreach($normalSkills as $index => $eachSkill)
			{
			    $level = $eachSkill['level'];
			    $newlevel = $level + $evolveAddLevel;
			    $normalSkills[$index]['level'] = $newlevel;
			}
			/********************************************/
			
			$talentSkill = $petInfo[PetDef::VAPET]['skillTalent'];
			$allSkills = array_merge( $normalSkills, $talentSkill);
			$allSkills = array_merge( $productSkills ,$allSkills );
			Logger::debug('all skills are: %s,p: %s,n: %s,t: %s',$allSkills,$productSkills, $normalSkills,$talentSkill );
			//根据判定条件取出所有的有效技能
			$effectSkills = array();
			foreach ( $allSkills as $skillKey => $skillInfo )
			{
				Logger::debug('now check skillinfo: %s', $skillInfo);
				$skillId= $skillInfo['id'];
				if (!empty($skillId)&&self::skillEfficient($uid, $petid, $skillId))
				{
					$effectSkills[] = $skillInfo;
				}
			}
			Logger::debug('effect skills are: %s', $effectSkills);
			
			$allPet = $petMgr->getAllPet();
			//所有技能对普通技能提供的等级加成
			$addLevel = 0;
			$petSkillConf = btstore_get()->PETSKILL;
			foreach ( $effectSkills as $effectKey => $effectSkill )
			{
				$oneSkillConf = array();
				$oneSkillConf = $petSkillConf[ $effectSkill['id'] ];
				if ( !empty( $oneSkillConf['normalSkillLvInc'] ) )
				{
					$addLevel += $oneSkillConf['normalSkillLvInc'];
				}
			}
			
			//遍历所有有效技能，计算对英雄的属性加成
			foreach ( $effectSkills as $effectKey2 => $effectSkill2 )
			{
				$addArr = $petSkillConf[ $effectSkill2['id'] ]['skillValueIncArr'];
				$exlevel = 0;
				//只有普通技能才享受额外的等级加成
				if ( isset( $effectSkill2['status'] ) )//TODO
				{
					$exlevel = $addLevel;
				}
				foreach ( $addArr as $addIndex => $addInfo )
				{
					if ( isset( $additioinArr[ $addInfo[0]] ) )
					{
						$additioinArr[ $addInfo[0] ] += $addInfo[1]*( $effectSkill2['level']+$exlevel );
					}
					else
					{
						$additioinArr[ $addInfo[0] ] = $addInfo[1]*( $effectSkill2['level']+$exlevel );
					}
				}
					
			}

            Logger::trace("before evolve additioinArr:%s", $additioinArr);

			//宠物进阶属性加成
			$petTplId = $petInfo[PetDef::PETTMPL];
			$evolveAttrConf = btstore_get()->PET[$petTplId]['evolveAttr'];
            Logger::trace("evolveAttrConf:%s", $evolveAttrConf);
			foreach($evolveAttrConf as $tmpLevel => $tmpAttrConf)
			{
				if($curEvolveLevel >= $tmpLevel)
				{
					foreach($tmpAttrConf as $eachAttr)
					{
						if( isset( $additioinArr[$eachAttr[0]] ) )
						{
							$additioinArr[$eachAttr[0]] += $eachAttr[1];
						}
						else
						{
							$additioinArr[$eachAttr[0]] = $eachAttr[1];
						}
					}
				}
			}

            Logger::trace("after evolve additioinArr:%s", $additioinArr);

			$washAttrConf = btstore_get()->PET[$petTplId]['washAttr'];
            $washLimit = 0; //宠物洗练价值上限
            if(!empty(btstore_get()->PET[$petTplId]['washValue'][$curEvolveLevel]))
            {
                $washLimit = btstore_get()->PET[$petTplId]['washValue'][$curEvolveLevel];
            }
			//宠物洗练属性的加成
			if(!empty($petInfo[PetDef::VAPET]['confirmed']))
			{
				$confirmed = $petInfo[PetDef::VAPET]['confirmed'];
				foreach($confirmed as $attrId => $attrValue)
				{
                    $attrValue = $attrValue > $washLimit ? $washLimit : $attrValue;
					if( isset( $additioinArr[$attrId] ) )
					{
						$additioinArr[$attrId] += intval($attrValue / $washAttrConf[$attrId]['value']);
					}
					else
					{
						$additioinArr[$attrId] = intval($attrValue / $washAttrConf[$attrId]['value']);
					}
				}
			}
            Logger::trace("after wash additioinArr:%s", $additioinArr);

		}
		
		
		$allPetTmpl = self::getPetHandbookInfo($uid);
		Logger::debug( 'all tmpl is: %s', $allPetTmpl );
		
		$petConf = btstore_get()->PET;
		foreach ( $allPetTmpl as $petTmpl )
		{
			$handBookAddition = $petConf[$petTmpl]['handbookAdditionArr'];
			foreach ($handBookAddition as $handbookAddIndex => $handbookInfo )
			{
				if ( isset( $additioinArr[ $handbookInfo[0]] ) )
				{
					$additioinArr[ $handbookInfo[0] ] += $handbookInfo[1];
				}
				else 
				{
					$additioinArr[ $handbookInfo[0] ] = $handbookInfo[1];
				}
			}
			
		}
		
		return $additioinArr;
		
	}
	
	
	public static function openKeeperSlot($uid,$prop, $num)
	{
		$keeperInst = KeeperObj::getInstance($uid);
		$keeperSlot = $keeperInst->getKeeperSlot();
		$petCostConf = btstore_get()->PET_COST[1];
		$user= EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		if( $prop == 1 )
		{
			$itemIdNeed = BagConf::BAG_UNLOCK_ITEM_ID;
			if( !$bag->deleteItembyTemplateID($itemIdNeed, $num ))
			{
				throw new FakeException( 'sub item failed' );
			}
		}
		elseif($prop == 0)
		{
			$openedTimes = ($keeperSlot-$petCostConf['initKeeperSlot'])/$petCostConf['keeperSlotNumPerOpen'];
			$openedTimes = intval($openedTimes);
			if ( $openedTimes < 0 )
			{
				throw new ConfigException( 'init keeperslot changed' );
			}
			$a = $petCostConf['openKeeperGoldBase']+ $openedTimes * $petCostConf['openKeeperGoldInc'];
			$b = $petCostConf['openKeeperGoldBase']+($openedTimes + $num -1)* $petCostConf['openKeeperGoldInc'];
			
			$needGold = intval( ($a+$b)*$num/2 );
			
			if ( !$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_OPEN_KEEPER_SLOT) )
			{
				throw new FakeException( 'lack gold' );
			}
		}
		else
		{
			throw new  FakeException( 'invalid arg' );
		}
		
		$keeperInst->openKeeperSlot( $num*$petCostConf['keeperSlotNumPerOpen'] );
		
		$user->update();
		$bag->update();
		$keeperInst->update();
	}
	
	public static function learnSkill( $uid, $petid )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		if ( $petInfo[PetDef::SKILLPOINT] < 1 )
		{
			throw new FakeException( 'lack 1 skill point' );
		}
		
		$petConf = btstore_get()->PET[$petInfo[PetDef::PETTMPL]]->toArray();

		$normalSkillArr = $petInfo[PetDef::VAPET]['skillNormal'];
		$normalSkillCount = count( $normalSkillArr );
		
		$makeUpWeightArr = array();
		$allNewSkill = $petConf['canLrnSkills'];
		$cannotLrnNewSkill = array();

        $failNum = $petMgr->getFailNum($petid);
        $skillDarkCell = btstore_get()->PET_COST[1]['skillDarkCell'];
        $ifOpenDarkCell = false;//是否开启技能暗格
        if($failNum >= $skillDarkCell)
        {
            $ifOpenDarkCell = true;
        }

        $rollSkillIndex = -1; //要升级的技能id的index
		foreach ( $normalSkillArr as $skillPos => $skillInfo  )
		{
			if (empty( $skillInfo['id'] ))
			{
				$makeUpWeightArr[$skillPos] = $petConf['skillUpWeightArr'][0];
			}
			else 
			{
				$makeUpWeightArr[$skillPos] = $petConf['skillUpWeightArr'][$skillInfo['level']];
				if (  in_array( $skillInfo['id'] , $allNewSkill) )
				{
					$cannotLrnNewSkill[] = $skillInfo['id'];
				}

                //找到要升级的skill
                if($ifOpenDarkCell == true)
                {
                    if($skillInfo['level'] >= $petConf['lrnSkillLvLimit'])
                    {
                        continue;
                    }
                    $rollSkillIndex = $skillPos;
                }
			}
		}

        Logger::trace("PetLogic::learnSkill rollSkillIndex:%d ifOpenDarkCell:%d", $rollSkillIndex, $ifOpenDarkCell ? 1 : 0);
		
		$canLrnWeightArr = array();
		
		foreach ( $allNewSkill as $oneSkillKey => $skillId )
		{
			if ( in_array( $skillId , $cannotLrnNewSkill) )
			{
				unset( $allNewSkill[$oneSkillKey ] );
			}
			else 
			{
				$petskill = btstore_get()->PETSKILL[$skillId];
				$canLrnWeightArr[$skillId] = array( 'id' => $skillId,'weight' => $petskill['skillWeight'] );
			}
			
		}
		
		$makeUpWeightArr[$normalSkillCount] = $petConf['skillSlotOpenWeightArr'][$normalSkillCount];
		
		$petMgr->subSkillPoint( $petid );

        if($ifOpenDarkCell == false || $rollSkillIndex == -1)
        {
            $rollRet = Util::noBackSample( $makeUpWeightArr , 1);
            $rollKey = $rollRet[0];
        }
		else
        {
            $rollKey = $rollSkillIndex;
        }

		//专为造假
		$learnSuccess = true;
		$petFake = RPCContext::getInstance()->getSession( 'pet.fake' );
		if ( $petFake == 1 )
		{
			if( $normalSkillCount >= $petConf['skillSlotLimit'] )
			{
				Logger::warning('in fake process, petid: %d has normalskills count: %d', $petid,$normalSkillCount );
				$learnSuccess = false;
			}
			else 
			{
				$petMgr->openSkillSlot( $petid );
			}
			RPCContext::getInstance()->setSession( 'pet.fake' , 2);
		}
		elseif ($petFake == 2)
		{
			if( !isset( $normalSkillArr[0]['id'] ) || $normalSkillArr[0]['id'] != 0 )
			{
				$learnSuccess = false;
			}
			else 
			{
				//TODO 注意：！！！ 这里的依赖较多，表里的宠物不能有初始技能，宠物的技能栏位上限不能<= 初始栏位
				$petMgr->addNewNormalSkill($petid, 0, $skillId );
			}
			RPCContext::getInstance()->unsetSession( 'pet.fake' );
		}
		//专为造假
		
		else
		{
			//如果roll到了开启技能栏
			if ( $rollKey == $normalSkillCount )
			{
				//超过上限了
				if( $normalSkillCount >= $petConf['skillSlotLimit'] )
				{
					$learnSuccess = false;
				}
				else 
				{
					//增加技能栏
					$petMgr->openSkillSlot( $petid );
				}
				
			}
			//如果roll到了技能升级
			elseif ( !empty( $normalSkillArr[$rollKey]['id'] ) )
			{
				//技能达到上限了
				if ( $normalSkillArr[$rollKey]['level'] >= $petConf['lrnSkillLvLimit'] )
				{
					$learnSuccess = false;
				}
				else 
				{
					$petMgr->skillLvUp( $petid, $rollKey );
				}
				
			}
			else
			{
				//如果要领悟新技能但可领悟的技能为空
				if ( empty( $canLrnWeightArr ) )
				{
					$learnSuccess = false;
				}
				else 
				{
					$skillRollRet = Util::noBackSample( $canLrnWeightArr , 1);
					$petMgr->addNewNormalSkill($petid, $rollKey, $skillRollRet[0] );
				}
				
			}
		}

        if ( !$learnSuccess )
        {
            $petMgr->setFailNum($petid, $failNum+1);
        }
        else
        {
            $petMgr->setFailNum($petid, 0);
        }

		$petMgr->update();
		
		$user = EnUser::getUserObj($uid);
		$keeperInst = KeeperObj::getInstance($uid);
		$fightPetId = $keeperInst->getFightPet();
		if( $petid == $fightPetId )
		{
			$user->modifyBattleData();
			$fightForce = self::calcuPetFightforce($uid, $petid);
			$keeperInst->setPetFightforce($petid, $fightForce);
			$keeperInst->update();
		}
		
		if ( !$learnSuccess )
		{
			return 'fail';
		}

		$nowPetInfo = $petMgr->getOnePetInfo($petid);
		
		//为满足成就系统的需求
		if( $petConf['qulity'] >= PetCfg::ADV_QUALITY)
		{
			$thisPetSkillNum = 0;
			foreach ( $nowPetInfo[PetDef::VAPET]['skillNormal'] as $pos => $info )
			{
				if ( $info['id'] != 0)
				{
					$thisPetSkillNum++;
				}
			}
			EnAchieve::updatePetSkill($uid,$thisPetSkillNum );
		}
		
		return $nowPetInfo;
	}
	
	public static function resetSkill( $uid, $petid )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$normalSkillArr = $petInfo[PetDef::VAPET]['skillNormal'];
		
		$petLevel = $petInfo[PetDef::LEVEL];
		$pettmpl = $petInfo[PetDef::PETTMPL];
		$petConf = btstore_get()->PET[$pettmpl];
		$needGold = intval($petConf['resetGold']);//intval(($petLevel* $petConf['resetGold'])/100);
		$user = EnUser::getUserObj($uid);
		if ( !$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_PET_RESET) )
		{
			throw new FakeException( 'lack gold' );
		}
		
		$nowNormalSkills = $petMgr->resetNormalSkill( $petid );
		if ($petConf['skillPointLvInterval'] == 0)
		{
			throw new ConfigException( 'petid: %d skillPointLvInterval ==0', $petid );
		}
		Logger::debug('petinfo: %s, petconf: %s', $petInfo, $petConf);
		$nowPoints = $petInfo[PetDef::SKILLPOINT];
		$addPointsByLvNum = intval( $petLevel/$petConf['skillPointLvInterval'] );
		
		$allPoints = $petConf['initSkillPoint'] + $addPointsByLvNum*$petConf['skillPointInc'] + $petInfo[PetDef::SWALLOW]*$petConf['swallowAddPoint'];
		$allSkillLv = 0;
		foreach ( $nowNormalSkills as $pos => $skillInfo )
		{
			$allSkillLv += $skillInfo['level'];
		}
		$lockPoints = $allSkillLv + count( $nowNormalSkills ) - $petConf['initSkillSlot'] ;
		
		$backPoints = $allPoints - $nowPoints - $lockPoints;
		
		Logger::debug('calculate: allPoints: %d, nowPoints: %d, lockPoints: %d', $allPoints,$nowPoints,$lockPoints);
		if ($backPoints < 0)
		{
			throw new InterException( 'calsulate err, allPoints: %d, nowPoints: %d, lockPoints: %d', $allPoints,$nowPoints,$lockPoints );
		}
		$petMgr->addSkillPoint($petid, $backPoints);
		
		$user->update();
		$petMgr->update();
		
		$keeperInst = KeeperObj::getInstance($uid);
		$fightPetId = $keeperInst->getFightPet();
		if( $petid == $fightPetId )
		{
			$user->modifyBattleData();
			$fightForce = self::calcuPetFightforce($uid, $petid);
			$keeperInst->setPetFightforce($petid, $fightForce);
			$keeperInst->update();
		}
		
		$nowPetInfo = $petMgr->getOnePetInfo($petid);
		
        return $nowPetInfo;
	}
	
	public static function lockSkillSlot( $uid, $petid, $skillId )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$normalSkillArr = $petInfo[PetDef::VAPET]['skillNormal'];
		
		$hasSkill = false;
		$pos = null;
		$lockedNum = 0;
		foreach ( $normalSkillArr as $normalPos => $normalSkillInfo )
		{
			if ($normalSkillInfo['status'] == PetDef::SKILL_LOCK)
			{
				$lockedNum++;
			}
			if ($normalSkillInfo['id'] == $skillId)
			{
				$hasSkill = true;
				$pos = $normalPos;
			}
		}
		if ( !$hasSkill )
		{
			throw new FakeException( 'no skillId: %d, all skills are: %s', $skillId, $normalSkillArr );
		}
		
		if ( $normalSkillArr[$pos]['status'] == 1 )
		{
			throw new FakeException( 'do not relock pos: %d, all skill are: %s', $pos, $normalSkillArr );
		}
		
		$petConf = btstore_get()->PET_COST[1];
		$pet = btstore_get()->PET[$petInfo[PetDef::PETTMPL]];
		
		if( $lockedNum >= $pet['lockNum'] )
		{
			throw new FakeException( 'locked num %d already reach max', $lockedNum);	
		}
		
		if ( $lockedNum >= count( $petConf['lockSkillCostArr'] ) )
		{
			throw new FakeException( '$lockedNum: %d> max',$lockedNum  );
		}
		$needGold = $petConf['lockSkillCostArr'][$lockedNum+1];
		$user = EnUser::getUserObj($uid);
		if ( !$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_PET_LOCKSKILL) )
		{
			throw new FakeException( 'lack gold' );
		}
		
		$petMgr->lockSkillSlot( $petid, $pos );
		
		$user->update();
		$petMgr->update();
	}
	
	public static function unlockSkillSlot( $uid, $petid, $skillId )
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		$normalSkillArr = $petInfo[PetDef::VAPET]['skillNormal'];
		
		$hasSkill = false;
		$pos = null;
		foreach ( $normalSkillArr as $normalPos => $normalSkillInfo )
		{
			if ($normalSkillInfo['id'] == $skillId)
			{
				$hasSkill = true;
				$pos = $normalPos;
			}
		}
		if ( !$hasSkill )
		{
			throw new FakeException( 'no skillId: %d, all skills are: %s', $skillId, $normalSkillArr );
		}
		
		$petMgr->unlockSkillSlot( $petid, $pos );
		$petMgr->update();
	}
	
	
	public static function sellPet($uid, $petidArr)
	{
        $petMgr = PetManager::getInstance($uid);
        foreach($petidArr as $petid)
        {
            $evolveLevel = $petMgr->getEvolveLevel($petid);
            if($evolveLevel > 0)
            {
                throw new FakeException("pet:%d evolve > 0, cant sell", $petid);
            }
        }

        $washReturnNum = 0; //洗练返还数量
		foreach ( $petidArr as $index => $petid )
		{
            $washReturnNum += self::calPetWashReturn($uid, $petid);
			self::sellOnePet($uid, $petid);
		}
		$user = EnUser::getUserObj($uid);
		
		//更新
		$petMgr->update();
		$user->update();

        $bag = BagManager::getInstance()->getBag($uid);
        $bag->addItemByTemplateID(PetDef::WASH_RETURN_STONE, $washReturnNum, true);
        $bag->update();
        
        return array('item'=>array(PetDef::WASH_RETURN_STONE=>$washReturnNum));
	}
	
	/**
	 * 没有更新
	 * @param unknown $uid
	 * @param unknown $petid
	 * @throws FakeException
	 * @throws ConfigException
	 */
	public static function sellOnePet($uid, $petid)
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		
		if( empty($petInfo))
        {
            throw new FakeException('no petid: %d',$petid);
        }

		if ( $petInfo[PetDef::LEVEL] >20 )
		{
			throw new FakeException( 'petid:%d, level:%d > 20', $petid,$petInfo[PetDef::LEVEL] );
		}
		
		$vaKeeper = KeeperObj::getInstance($uid)->getVaKeeper();
		foreach ( $vaKeeper['setpet'] as $squandPet => $squandPetInfo )
		{
			if ( $petid == $squandPetInfo['petid'] )
			{
				throw new FakeException( 'petid: %d is in squandOrFight %s:', $petid, $vaKeeper['setpet'] );
			}
		}
		
		$petMgr->deletePet($petid);
		
		$petTmpl = $petInfo[PetDef::PETTMPL];
		$conf = btstore_get()->PET[$petTmpl];
		$sellPriceArr = $conf['petPrice'];
		$finalPrice = $sellPriceArr[0] + ($petInfo[PetDef::LEVEL] - 1)*$sellPriceArr[1];
		if ( $finalPrice < 0 )
		{
			throw new ConfigException( 'sell pet gain silver: %d', $finalPrice );
		}
		$user = EnUser::getUserObj($uid);
		$user->addSilver($finalPrice);
		
		//$petMgr->update();
		//$user->update();
	}
	
	public static function calcuPetFightforce( $uid, $petid )
	{
		//得到这个宠物的所有技能
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petid);
		
		$productSkills = $petInfo[PetDef::VAPET]['skillProduct'];
		$normalSkills = $petInfo[PetDef::VAPET]['skillNormal'];
		
		/********************************************/
		//所有普通技能的等级通过进阶加成
		$curEvolveLevel = $petMgr->getEvolveLevel($petid);
		$evolveSkillConf = btstore_get()->PET[$petInfo[PetDef::PETTMPL]]['evolveSkill']->toArray();
			
		$evolveAddLevel = 0;
		foreach ($evolveSkillConf as $l => $addNum)
		{
		    if ($curEvolveLevel >= $l)
		    {
		        $evolveAddLevel += $addNum;
		    }
		}
		foreach($normalSkills as $index => $eachSkill)
		{
		    $level = $eachSkill['level'];
		    $newlevel = $level + $evolveAddLevel;
		    $normalSkills[$index]['level'] = $newlevel;
		}
		/********************************************/
		
		$talentSkill = $petInfo[PetDef::VAPET]['skillTalent'];
		$allSkills = array_merge( $normalSkills, $talentSkill);
		$allSkills = array_merge( $productSkills ,$allSkills );
		Logger::debug('all skills are: %s,p: %s,n: %s,t: %s',$allSkills,$productSkills, $normalSkills,$talentSkill );
		//根据判定条件取出所有的有效技能
	 	$effectSkills = array();
		foreach ( $allSkills as $skillKey => $skillInfo )
		{
			Logger::debug('now check skillinfo: %s', $skillInfo);
			$skillId= $skillInfo['id'];
			if (!empty($skillId) && self::skillEfficient($uid, $petid, $skillId)) //跟策划确认不论技能是否有效，都加战斗力
			{
				$effectSkills[] = $skillInfo;
				
			}
		}
		Logger::debug('effect skills are: %s', $effectSkills); 
		
		//$allPet = $petMgr->getAllPet();
		//所有技能对普通技能提供的等级加成
		$addLevel = 0;
		$petSkillConf = btstore_get()->PETSKILL->toArray();
 		foreach ( $effectSkills as $effectKey => $effectSkill )
		{
			$oneSkillConf = array();
			$oneSkillConf = $petSkillConf[ $effectSkill['id'] ];
			if ( !empty( $oneSkillConf['normalSkillLvInc'] ) )
			{
				$addLevel += $oneSkillConf['normalSkillLvInc'];
			}
		} 
		
		//遍历所有技能，不管有效没效，计算战斗力
		$finalFightForce = 0;
		foreach ( $effectSkills as $effectKey2 => $effectSkill2 )
		{
			if( empty($effectSkill2['id'] ) )
			{
				continue;
			}
			
			$fightForce = $petSkillConf[ $effectSkill2['id'] ]['skillFight'];
			$exlevel = 0;
			//只有普通技能才享受额外的等级加成
			if ( isset( $effectSkill2['status'] ) )//TODO 这里是依靠有没有status来判定是不是普通技能的
			{
				$exlevel = $addLevel;
			}
			
			$finalFightForce += $fightForce * ( $effectSkill2['level']+$exlevel );
		}

        $petCostConf = btstore_get()->PET_COST[1];
        //宠物进阶对战斗力的增加值
        $evolveFightForceConf = $petCostConf['evolveFightForce']; //已排序
        $evolveLevel = $petMgr->getEvolveLevel($petid);

        $addFightForce1 = 0;
        foreach($evolveFightForceConf as $level => $eachAddFightForce)
        {
            if($evolveLevel - 1 < $level)
            {
                break;
            }
            $addFightForce1 += $eachAddFightForce;
        }
        Logger::trace("petId:%d evolveLevel:%d addFightForce1:%d", $petid, $evolveLevel, $addFightForce1);
        $finalFightForce += $addFightForce1;

        //宠物洗练对战斗力的增加值
        $potentialityFightForceConf = $petCostConf['potentialityFightForce'];
        $confirmed = empty($petInfo[PetDef::VAPET]['confirmed']) ? array() : $petInfo[PetDef::VAPET]['confirmed'];
        $totalWashValue = 0;

        $petTplId = $petInfo[PetDef::PETTMPL];
        $curEvolveLevel = $petMgr->getEvolveLevel($petid);
        $washLimit = 0; //宠物洗练价值上限
        if(!empty(btstore_get()->PET[$petTplId]['washValue'][$curEvolveLevel]))
        {
            $washLimit = btstore_get()->PET[$petTplId]['washValue'][$curEvolveLevel];
        }
        foreach($confirmed as $attrId => $attrValue)
        {
            $attrValue = $attrValue > $washLimit ? $washLimit : $attrValue;
            $totalWashValue += $attrValue;
        }
		$addFightForce2 = intval($totalWashValue * $potentialityFightForceConf / 10);
        Logger::trace('petId:%d addFightForce2:%d totalWashValue:%d confirmed:%s', $petid, $addFightForce2, $totalWashValue, $confirmed);
        $finalFightForce += $addFightForce2;

		return $finalFightForce;
	}
	
	public static function getRankList()
	{
		$uid = RPCContext::getInstance()->getUid();
		$keeperInst = KeeperObj::getInstance($uid);
		$fightPetId = $keeperInst->getFightPet();
		if( !empty($fightPetId) )
		{
			$petFightForce = self::calcuPetFightforce($uid, $fightPetId);
			$keeperInst->setPetFightforce($fightPetId, $petFightForce);
			$keeperInst->update();
		}
		
		$rankList = PetDAO::getRankList();
		Logger::debug('ranklist is %s', $rankList);
		
		if( empty( $rankList ) )
		{
			return array();
		}
		
		$petIdArr = array();
		foreach ( $rankList as $rankUid => $rankInfo )
		{
			$fightUpPetId = 0;
			foreach ( $rankInfo[PetDef::VAKEEPER]['setpet'] as $index => $posInfo )
			{
				if( $posInfo['status'] == 1 )
				{
					$fightUpPetId = $posInfo['petid'];
					break;
				}
			}
			if( $fightUpPetId <= 0 )
			{
				unset( $rankList[$rankUid] );
			}
			else 
			{
				$petIdArr[] = $fightUpPetId;
			}
			
			unset( $rankList[$rankUid][PetDef::VAKEEPER] );
		}
		
		$rankPetInfoArr = PetDAO::getRankPetInfo( $petIdArr );

        //过滤掉宠物不生效的天赋技能
        /*foreach($rankPetInfoArr as $uid => $petInfo)
        {
            if(isset($petInfo[PetDef::VAPET]['skillTalent']))
            {
                $petid = $petInfo[PetDef::PET_ID];
                foreach($petInfo[PetDef::VAPET]['skillTalent'] as $skillKey => $skillInfo)
                {
                    $skillId= $skillInfo['id'];
                    if (empty($skillId) || !self::skillEfficient($uid, $petid, $skillId))
                    {
                        unset($rankPetInfoArr[$uid][PetDef::VAPET]['skillTalent'][$skillKey]);
                    }
                }
            }
        }*/
		
		$uidArr = array_keys( $rankList );
		$userInfoArr = EnUser::getArrUserBasicInfo($uidArr, array('uid','uname','guild_id'));
		
		//至此，$rankList为合法的排名，带战力值
		//$rankPetInfoArr为这些宠物的信息，
		//$userInfoArr为这些宠物的拥有者的信息，开始进行合并
		Logger::debug( 'rankInfo %s, userInfo %s, petInfo %s', $rankList, $userInfoArr, $rankPetInfoArr );
		
		$rankListFinal = array();
        $arrRankUid = array();
		foreach ( $rankList as $rankUid => $rankInfo )
		{
			if( !isset( $userInfoArr[$rankUid] ) || !isset( $rankPetInfoArr[$rankUid] ) )
			{
				throw new InterException( 'user info not found or pet info not found, rankInfo %s, userInfo %s, petInfo %s', $rankList, $userInfoArr, $rankPetInfoArr );
			}
			$merge = array_merge( $rankInfo, $rankPetInfoArr[$rankUid]  );
			$rankListFinal[$rankUid] = array_merge( $merge,$userInfoArr[$rankUid] );
			unset( $rankListFinal[$rankUid][PetDef::KEEPERSLOT]);
			unset( $rankListFinal[$rankUid][PetDef::PET_ID]);
			unset( $rankListFinal[$rankUid][PetDef::SKILLPOINT]);
			unset( $rankListFinal[$rankUid][PetDef::SWALLOW]);
			unset( $rankListFinal[$rankUid][PetDef::TRAINTIME]);
			unset( $rankListFinal[$rankUid][PetDef::DELETE_TIME]);
			unset( $rankListFinal[$rankUid][PetDef::VAPET]);
			$arrRankUid[] = $rankUid;
		}
        $arrRankUser = EnUser::getArrUser($arrRankUid, array('uid', 'guild_id'));
        $arrGuildId = Util::arrayExtract($arrRankUser, 'guild_id');
        $arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_NAME));

        $rankListFinal = array_merge( $rankListFinal );
		//接下来是拉取自己的排名
		$myUid = RPCContext::getInstance()->getUid();
		$myRank = -1;
		foreach ( $rankListFinal as $rankIndex => $rankInfo )
		{
            //加一个排序 rank 字段
            $rankInfo['rank'] = $rankIndex + 1;
            //军团名
            if(!empty($rankInfo['guild_id']) && (isset($arrGuildInfo[$rankInfo['guild_id']])))
            {
                $rankInfo['guild_name'] = $arrGuildInfo[$rankInfo['guild_id']][GuildDef::GUILD_NAME];
            }
            //宠物技能相关
            //$petInfo = EnPet::getFightPetInfo($rankInfo['uid']);
           // $rankInfo['va_pet'] = $petInfo[0]['arrSkill'];
            $rankListFinal[$rankIndex] = $rankInfo;
			if( $rankInfo['uid'] == $myUid )
			{
				$myRank = $rankInfo['rank'];
			}
		}

		if( $myRank < 0  )
		{
			$keeperInst = KeeperObj::getInstance($myUid);
			$myFightPet = $keeperInst->getFightPet();
			if( !empty( $myFightPet ) )
			{
				//这里不要取及时的，要取keeper里的
				$keeperInfo = $keeperInst ->getKeeperInfo();
				$fightForce = $keeperInfo[PetDef::PET_FIGHTFORCE];//self::calcuPetFightforce($myUid,$myFightPet );
				//这里宠物的准确排名不好取，跟策划商定，战斗力相同的宠物名次一样
				$myRank = PetDAO::getPetRank( $fightForce );
                $myRank += 1;
				/*if( $myRank <=50 )
				{
					//取排名的问题， 前50就是按照战斗力取了50个，然后针对这50个在按照规则进行排名，但是取自己名次
					//的时候，只能是数一下有多少人排名比我大，那么+1就是我的排名，那么如果我的战力和列表里的最小战力相等的话
					//我的排名<=50 但是没上榜，这里造了一下假，这样的玩家置成51名就行了
					$myRank = 51;
				}*/
			}
            else
            {
                //玩家没有上阵宠物
                Logger::debug('have no fightPet');
            }
		}
        /*$petManager = PetManager::getInstance();
        $allPet = $petManager->getAllPet();
        //玩家没有宠物
        if(empty($allPet))
        {
            $myRank = -2;
        }*/
		
		return array( 'myRank' => $myRank, 'rankList' => $rankListFinal );
	}
	
	static function getPetHandbookInfo($uid)
	{
		$guid = RPCContext::getInstance()->getUid();
		if( $guid == $uid )
		{
			$handbookinfoSession = RPCContext::getInstance()->getSession( PetDef::HANDBOOK_SESSION );
			if( !empty( $handbookinfoSession ) )
			{
				return $handbookinfoSession['info'];
			}
		}
		$offset = 0;
		$all = array();
		while(true)
		{
			$ret = PetDAO::getPetArrIncludeDeleted($uid, $offset);
			if( empty( $ret ) )
			{
				break;
			}
			$all = array_merge( $all, $ret );
			if( count( $ret ) < DataDef::MAX_FETCH )
			{
				break;
			}
			$offset += count( $ret );
		}
		
		$info = Util::arrayExtract($all, PetDef::PETTMPL);
		$info = array_unique( $info );
		$info = array_merge( $info );
		if( $guid == $uid )
		{
			RPCContext::getInstance()->setSession( PetDef::HANDBOOK_SESSION , array('info' => $info));
		}
		
		return $info;
	}

	public static function evolve($uid, $petId)
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petId);

		if(empty($petInfo))
		{
			throw new FakeException("have no pet:%d", $petId);
		}

		$petTplId = $petInfo[PetDef::PETTMPL];
		$petConf = btstore_get()->PET[$petTplId];
		$ifEvolve = $petConf['ifEvolve'];
		if($ifEvolve == 0)
		{
			throw new FakeException("the pet:%d can not evolve:%d", $petId, $ifEvolve);
		}
		//最大可进阶等级
		$maxEvolveLevel = $petConf['maxEvolveLevel'];
		//宠物当前进阶等级
		$curEvolveLevel = $petMgr->getEvolveLevel($petId);
		if($curEvolveLevel >= $maxEvolveLevel)
		{
			throw new FakeException("the pet:%d evolveNum:%d greater than maxEvolveLevel:%d", $petId, $curEvolveLevel, $maxEvolveLevel);
		}

		//进阶需要的宠物等级限制
		$evolveLevel = $petConf['evolveLevel'];
		if(!isset($evolveLevel[$curEvolveLevel + 1]))
		{
			throw new ConfigException("error config evolveLevel:%d for level:%d", $evolveLevel, $curEvolveLevel + 1);
		}
		$needLv = $evolveLevel[$curEvolveLevel + 1];
		if($petInfo[PetDef::LEVEL] < $needLv)
		{
			throw new FakeException("level:%d not reached needLevel:%d ", $petInfo[PetDef::LEVEL], $needLv);
		}

		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);

		$needItem = $petConf["evolveCost"][$curEvolveLevel]->toArray();
		if(!empty($needItem))
		{
			RewardUtil::delMaterial($uid, $needItem, StatisticsDef::ST_FUNCKEY_PET_EVOLVE);
		}

		$petMgr->addEvolveLevel($petId);

        //清一下战斗缓存
        EnUser::getUserObj()->modifyBattleData();

		$bag->update();
		$userObj->update();
		$petMgr->update();

		return 'ok';
	}

	public static function ensure($uid, $petId)
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petId);

        if(empty($petInfo))
        {
            throw new FakeException("have no pet:%d", $petId);
        }

		if(empty($petInfo[PetDef::VAPET]['toConfirm']))
		{
			throw new FakeException("toConfirm attr is empty");
		}
		if( isset($petInfo[PetDef::VAPET]['confirmed'])
			&& $petInfo[PetDef::VAPET]['confirmed'] == $petInfo[PetDef::VAPET]['toConfirm'])
		{
			throw new FakeException("equal confirmed");
		}

		$petMgr->confirm($petId);
		$petMgr->update();
        EnUser::getUserObj()->modifyBattleData();

		return 'ok';
	}

	public static function exchange($uid, $petId1, $petId2)
	{
		$user = EnUser::getUserObj($uid);
		$needGold = btstore_get()->PET_COST[1]['exchangeCostGold'];
		if($user->subGold($needGold, StatisticsDef::ST_FUNCKEY_PET_EXCHANGE_ATTR) == false)
		{
			throw new FakeException('sub gold:%d failed', 100);
		}

        $petMgr = PetManager::getInstance($uid);
        $pet1Info = $petMgr->getOnePetInfo($petId1);
        $pet2Info = $petMgr->getOnePetInfo($petId2);

        if(empty($pet1Info) || empty($pet2Info))
        {
            throw new FakeException("have no pet1:%d or pet2:%d", $petId1, $petId2);
        }

        if( $petMgr->getEvolveLevel($petId1) == 0 && $petMgr->getEvolveLevel($petId2) == 0
            && empty($pet1Info[PetDef::VAPET]['confirmed']) && empty($pet2Info[PetDef::VAPET]['confirmed']) )
        {
            throw new FakeException("two white board");
        }

        $pet1EvolveCost = self::calPetEvolveCost($uid, $petId1);
        $pet2EvolveCost = self::calPetEvolveCost($uid, $petId2);
        $needReturnSilver = $pet1EvolveCost['silver'] + $pet2EvolveCost['silver'];
        $needReturnItem = Util::arrayAdd2V(array($pet1EvolveCost['item'], $pet2EvolveCost['item']));

        Logger::info("PetLogic::exchange petId1:%d, petId2:%d, needReturnSilver:%d needReturnItem:%s",
            $petId1, $petId2, $needReturnSilver, $needReturnItem);

		$petMgr->exchange($petId1, $petId2);

        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj->addSilver($needReturnSilver);
        $bag->addItemsByTemplateID($needReturnItem, true);

		$petMgr->update();

        $bag->update();
        $userObj->update();
        EnUser::getUserObj()->modifyBattleData();
        return 'ok';
	}

    /**
     * 计算宠物进阶消耗
     * @param $uid
     * @param $petId
     * @return array
     */
    public static function calPetEvolveCost($uid, $petId)
    {
        $petMgr = PetManager::getInstance($uid);
        $petEvolveLevel = $petMgr->getEvolveLevel($petId);
        $petInfo = $petMgr->getOnePetInfo($petId);
        $petEvolveCostConf = btstore_get()->PET[$petInfo[PetDef::PETTMPL]]["evolveCost"];

        $needReturnSilver = 0;
        $needReturnItem = array();

        for($i = 0; $i < $petEvolveLevel; ++$i)
        {
            $arrNeed = $petEvolveCostConf[$i];
            foreach($arrNeed as $eachNeed)
            {
                switch($eachNeed[0])
                {
                    case RewardConfType::SILVER:
                        $needReturnSilver += $eachNeed[2];
                        break;
                    case RewardConfType::ITEM_MULTI:
                        if(isset($needReturnItem[$eachNeed[1]]))
                        {
                            $needReturnItem[$eachNeed[1]] += $eachNeed[2];
                        }
                        else
                        {
                            $needReturnItem[$eachNeed[1]] = $eachNeed[2];
                        }
                        break;
                }
            }
        }

        return array(
            'silver' => $needReturnSilver,
            'item' => $needReturnItem,
        );
    }

	public static function wash($uid, $petId, $grade, $num, $ifForce)
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petId);

        if(empty($petInfo))
        {
            throw new FakeException("have no pet:%d", $petId);
        }

		$petTplId = $petInfo[PetDef::PETTMPL];
		if(!empty($petInfo[PetDef::VAPET]['toConfirm']) && $ifForce == 0)
		{
			throw new FakeException("toConfirm not empty, please ensure or giveUp first");
		}

        $petConf = btstore_get()->PET[$petTplId];
        $ifEvolve = $petConf['ifEvolve'];
        //洗练的判断条件和进阶相同
        if($ifEvolve == 0)
        {
            throw new FakeException("the pet:%d can not wash:%d", $petId, $ifEvolve);
        }

		$bag = BagManager::getInstance()->getBag($uid);

		/**
		 * 1 计算洗练的消耗
		 */
		$confirmed = empty($petInfo[PetDef::VAPET]['confirmed']) ? array() : $petInfo[PetDef::VAPET]['confirmed'];
		$totalWashValue = 0;
		foreach($confirmed as $attrId => $attrValue)
		{
			$totalWashValue += $attrValue;
		}

		if(!isset($petConf['washItem'][$grade]))
		{
			throw new ConfigException("no washItem:%s for petTplId:%d grade:%d", $petConf['washItem'], $petTplId, $grade);
		}
		$costItem = $petConf['washItem'][$grade];
		$itemNumConf = $petConf['itemNum']; //已按照key排序

		$costNum = 0;
		foreach($itemNumConf as $needWashValue => $tmpNum)
		{
            if($totalWashValue >= $needWashValue)
            {
                $costNum = $costNum > $tmpNum ? $costNum : $tmpNum;
            }
		}
		if($bag->deleteItembyTemplateID($costItem, $costNum * $num) == false)
		{
			throw new FakeException("delete item:%d failed, not enough num:%d", $costItem, $costNum * $num);
		}

		$toConfirm = array();
		/**
		 * 分步骤洗练
		 */
		//先随机出来,洗练属性的数量
		$washNumConf = $petConf['washNum'][$grade]->toArray();
		$apples = Util::noBackSample($washNumConf, 1);
		if(empty($apples))
		{
			throw new ConfigException("sample empty");
		}
		$apple = $apples[0];
		if($apple > 0)
		{
			//随机出来 洗练的属性
			$arrWashAttr = Util::noBackSample($petConf['washAttr']->toArray(), $apple);
			//为每一个属性, 随机出数值
			$washWaveConf = $petConf['washWave'][$grade]; //洗练波动
			$curEvolveLevel = $petMgr->getEvolveLevel($petId);
			$washLimit = $petConf['washValue'][$curEvolveLevel]; //宠物洗练价值上限

			foreach($arrWashAttr as $attrId)
			{
                $curConfirmAttrValueOfAttrId = $petMgr->getCurConfirmAttrValueOfAttrId($petId, $attrId);
				$minAttrValue = max(min($curConfirmAttrValueOfAttrId - $washWaveConf[1] * $num, $washLimit), 0);
				$maxAttrValue = min($curConfirmAttrValueOfAttrId + $washWaveConf[2] * $num, $washLimit);
				$toConfirm[$attrId] = rand($minAttrValue, $maxAttrValue);
			}
		}

        //清一下战斗缓存
        EnUser::getUserObj()->modifyBattleData();
		$petMgr->setToConfirm($petId, $toConfirm);
		$bag->update();
		$petMgr->update();

		return $toConfirm;
	}

	public static function giveUp($uid, $petId)
	{
		$petMgr = PetManager::getInstance($uid);
		$petInfo = $petMgr->getOnePetInfo($petId);

        if(empty($petInfo))
        {
            throw new FakeException("have no pet:%d", $petId);
        }

		if(empty($petInfo[PetDef::VAPET]['toConfirm']))
		{
			throw new FakeException("toConfirm attr is empty");
		}

		$petMgr->unSetToConfirm($petId);
		$petMgr->update();

		return 'ok';
	}

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
