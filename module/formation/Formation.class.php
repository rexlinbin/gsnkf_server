<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Formation.class.php 219287 2016-01-05 06:05:37Z ShijieHan $
 * 
 **********************************************************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/Formation.class.php $
 * @author $Author: ShijieHan $(lanhongyu@babeltime.com)
 * @date $Date: 2016-01-05 06:05:37 +0000 (Tue, 05 Jan 2016) $
 * @version $Revision: 219287 $
 * @brief 
 * 
 **/



class Formation implements IFormation
{
	/**
	 * (non-PHPdoc)
	 * @see IFormation::getFormation()
	 */
	public function getFormation()
	{
		$uid = RPCContext::getInstance()->getUid();
		$myFormation = EnFormation::getFormationObj($uid);
		$arrRet = $myFormation->getFormation();
		
		$formation = array();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; $i++)
		{
			if (isset($arrRet[$i]))
			{
				$formation[] = $arrRet[$i];
			}
			else
			{
				$formation[] = 0;
			}
		}
		
		return $formation;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::getSquad()
	 */
	public function getSquad()
	{
		$uid = RPCContext::getInstance()->getUid();
		$myFormation = EnFormation::getFormationObj($uid);
		$arrRet = $myFormation->getSquad();
		
		$squad = array();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; $i++)
		{
			if (isset($arrRet[$i]))
			{
				$squad[] = $arrRet[$i];
			}
			else
			{
				$squad[] = 0;
			}
		}
		
		return $squad;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::addHero()
	 */
	public function addHero($hid, $index)
	{
		$uid = RPCContext::getInstance()->getUid();
		if (EnSwitch::isSwitchOpen(SwitchDef::SQUAD) == false)
		{
			throw new FakeException('user:%d does not open the formation', $uid);
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->addHero($hid, $index);
		$arrRet = $myFormation->getFormation();
		
		$formation = array();
		for ($i = 0; $i < FormationDef::FORMATION_SIZD; $i++)
		{
			if (isset($arrRet[$i]))
			{
				$formation[] = $arrRet[$i];
			}
			else
			{
				$formation[] = 0;
			}
		}
		
		$myFormation->update();
		
		return $formation;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::delHero()
	 */
	public function delHero($hid)
	{
		$uid = RPCContext::getInstance()->getUid();
		if (EnSwitch::isSwitchOpen(SwitchDef::SQUAD) == false)
		{
			throw new FakeException('user:%d does not open the formation', $uid);
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->delHero($hid);
		$myFormation->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::setFormation()
	 */
	public function setFormation($formation)
	{
		$uid = RPCContext::getInstance()->getUid();
		if (EnSwitch::isSwitchOpen(SwitchDef::SQUAD) == false)
		{
			throw new FakeException('user:%d does not open the formation', $uid);
		}
		
		foreach($formation as $key => $value)
		{
			if($value == 0)
			{
				unset($formation[$key]);
			}
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->setFormation($formation);
		$myFormation->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::getExtra()
	 */
	public function getExtra()
	{
		$uid = RPCContext::getInstance()->getUid();
		$myFormation = EnFormation::getFormationObj($uid);
		$arrRet = $myFormation->getExtra();
		
		$extra = array();
		for($i = 0; $i < FormationDef::EXTRA_SIZD; $i++)
		{
			if( isset($arrRet[$i]) )
			{
				$extra[] = $arrRet[$i];
			}
			else
			{
				$extra[] = $myFormation->isExtraOpen($i) ? 0 : -1;
			}
		}
		
		return $extra;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::addExtra()
	 */
	public function addExtra($hid, $index)
	{
		$uid = RPCContext::getInstance()->getUid();
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->addExtra($hid, $index);
		$myFormation->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::delExtra()
	 */
	public function delExtra($hid, $index)
	{
		$uid = RPCContext::getInstance()->getUid();
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->delExtra($hid, $index);
		$myFormation->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::openExtra()
	 */
	public function openExtra($index)
	{
		$uid = RPCContext::getInstance()->getUid();
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->openExtra($index);
		$myFormation->update();
		
		EnUser::getUserObj($uid)->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::getAttrExtra()
	 */
	public function getAttrExtra()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
		{
			throw new FakeException('user:%d does not open the attr extra', $uid);
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$arrRet = $myFormation->getAttrExtra();
		
		$attrExtraConf = btstore_get()->SECOND_FRIEND->toArray();
		$maxCount = FormationDef::ATTR_EXTRA_SIZE;
		if ($maxCount < count($attrExtraConf))
		{
			$maxCount = count($attrExtraConf);
		}
		
		$attrExtra = array();
		for ($i = 0; $i < $maxCount; ++$i)
		{
			if (isset($arrRet[$i]))
			{
				$attrExtra[] = $arrRet[$i];
			}
			else
			{
				if (!$myFormation->isAttrExtraValid($i)) 
				{
					$attrExtra[] = -1;
				}
				else 
				{
					$attrExtra[] = $myFormation->isAttrExtraOpen($i) ? 0 : -1;
				}
			}
		}
		
		return $attrExtra;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::addAttrExtra()
	 */
	public function addAttrExtra($hid, $index)
	{
		$uid = RPCContext::getInstance()->getUid();
		if (!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
		{
			throw new FakeException('user:%d does not open the attr extra', $uid);
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->addAttrExtra($hid, $index);
		$myFormation->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::delAttrExtra()
	 */
	public function delAttrExtra($hid, $index)
	{
		$uid = RPCContext::getInstance()->getUid();
		if (!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
		{
			throw new FakeException('user:%d does not open the attr extra', $uid);
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->delAttrExtra($hid, $index);
		$myFormation->update();
		
		return 'ok';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IFormation::openAttrExtra()
	 */
	public function openAttrExtra($index)
	{
		$uid = RPCContext::getInstance()->getUid();
		if (!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
		{
			throw new FakeException('user:%d does not open the attr extra', $uid);
		}
		
		$myFormation = EnFormation::getFormationObj($uid);
		$myFormation->openAttrExtra($index);
		$myFormation->update();
		
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		return 'ok';
	}
	
	public function getWarcraftInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		if(!EnSwitch::isSwitchOpen( SwitchDef::WARCRAFT, $uid ))
		{
			throw new FakeException( 'switch not open' );
		}
		return WarcraftLogic::getWarcraftInfo($uid);
	}
	
	/* (non-PHPdoc)
	 * @see IFormation::craftLevelup()
	 */
	public function craftLevelup($craftId) 
	{
		$uid = RPCContext::getInstance()->getUid();
		if(!EnSwitch::isSwitchOpen( SwitchDef::WARCRAFT, $uid ))
		{
			throw new FakeException( 'switch not open' );
		}
		WarcraftLogic::craftLevelup($uid, $craftId);
	}

	/* (non-PHPdoc)
	 * @see IFormation::setWarcraft()
	 */
	public function setCurWarcraft($craftId) 
	{
		$uid = RPCContext::getInstance()->getUid();
		if(!EnSwitch::isSwitchOpen( SwitchDef::WARCRAFT, $uid ))
		{
			throw new FakeException( 'switch not open' );
		}
		WarcraftLogic::setCurWarcraft($uid, $craftId);
	}

    public function getAttrExtraLevel()
    {
        $uid = RPCContext::getInstance()->getUid();
        if(!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
        {
            throw new FakeException("user:%d does not open the attr extra", $uid);
        }

        return EnFormation::getAttrExtraLevel($uid);
    }

    public function strengthAttrExtra($index)
    {
        $uid = RPCContext::getInstance()->getUid();
        if(!EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
        {
            throw new FakeException("user:%d does not open the attr extra", $uid);
        }

        $myFormation = EnFormation::getFormationObj($uid);
        if($myFormation->isAttrExtraOpen($index) == false)
        {
            throw new FakeException("the index:%d not open yet", $index);
        }

        $attrExtraConf = btstore_get()->SECOND_FRIEND;
        $curAttrExtraLv = $myFormation->getAttrExtraLvByIndex($index);
        if($curAttrExtraLv >= $attrExtraConf[$index + 1]['maxLv'])
        {
            throw new FakeException("level:%d reach limit:%d", $curAttrExtraLv, $attrExtraConf[$index + 1]['maxLv']);
        }

        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);

        $attrExtraUpLvConf = btstore_get()->SECOND_FRIENDS_LVUP;
        $needSilver = $attrExtraUpLvConf[$curAttrExtraLv + 1]['costSilver'];
        $costItem = $attrExtraUpLvConf[$curAttrExtraLv + 1]['costItem'];
        if($userObj->subSilver($needSilver) == false)
        {
            throw new FakeException("subSilver failed need:%d", $needSilver);
        }
        if($bag->deleteItemsByTemplateID($costItem) == false)
        {
            throw new FakeException("del item:%s from bag failed", $costItem);
        }

        $myFormation->addAttrExtraLvOfIndex($index);

        $userObj->update();
        $bag->update();
        $myFormation->update();
        $userObj->modifyBattleData();

        return 'ok';
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */