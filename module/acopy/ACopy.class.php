<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ACopy.class.php 245319 2016-06-02 11:39:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ACopy.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-06-02 11:39:26 +0000 (Thu, 02 Jun 2016) $
 * @version $Revision: 245319 $
 * @brief 
 *  
 **/
class ACopy implements IACopy
{
	/* (non-PHPdoc)
	 * @see IActivityCopy::getCopyList()
	 */
	public function getCopyList() {
		// TODO Auto-generated method stub
		if(EnSwitch::isSwitchOpen(SwitchDef::ACTCOPY) == FALSE)
		{
		    throw new FakeException('ActivityCopy switch not open.');
		}
		$copyList = ACopyLogic::getActivityCopyList();
		return $copyList;
	}

	/* (non-PHPdoc)
	 * @see IActivityCopy::getCopyInfo()
	 */
	public function getCopyInfo($copyId) {
		// TODO Auto-generated method stub
		Logger::trace('acopy.getCopyInfo start.param copyid: %d.',$copyId);
		$copyId = intval($copyId);
		if(empty($copyId))
		{
		    throw new FakeException('error params;copyid %d.',$copyId);
		}
		$copyInfo = ACopyLogic::getActivityCopyInfo($copyId);
		return $copyInfo;
	}

	/* (non-PHPdoc)
	 * @see IActivityCopy::enterBaseLevel()
	 */
	public function enterBaseLevel($copyId, $baseId, $baseLv=BaseLevel::SIMPLE) {
		// TODO Auto-generated method stub
		Logger::trace('acopy.enterBaseLevel start.param copyid %d.baselv %d.',$copyId,$baseLv);
		$copyId = intval($copyId);
		$baseLv = intval($baseLv);
		if(empty($copyId) || empty($baseLv))
		{
		    throw new FakeException('error params.param copyid %d.baselv %d.',$copyId,$baseLv);
		}
		$canEnter = ACopyLogic::enterBaseLevel($copyId, $baseLv);
		return $canEnter;
	}

	/* (non-PHPdoc)
	 * @see IActivityCopy::doBattle()
	 */
	public function atkActBase($copyId,$baseLv,$armyId,$fmt=array()) {
		// TODO Auto-generated method stub
		Logger::trace('ACopy.atkActBase start.params copyid %d,baselv %d,armyid %d,fmt %s.',$copyId,$baseLv,$armyId,$fmt);
		$copyId = intval($copyId);
		$baseLv = intval($baseLv);
		$armyId = intval($armyId);
		if(empty($copyId) || ($baseLv < 0) || empty($armyId) || (!is_array($fmt)))
		{
		    throw new FakeException('error param.params copyid %d,baselv %d,armyid %d,fmt %s.',$copyId,$baseLv,$armyId,$fmt);
		}
		$battleRet = ACopyLogic::atkActBase($copyId,$baseLv,$armyId,$fmt);
		return $battleRet;
	}
	
	/* (non-PHPdoc)
	 * @see IACopy::atkGoldTree()
	*/
	public function atkGoldTree ($copyId,$byItem=0, $fmt = array())
	{
	    // TODO Auto-generated method stub
	    Logger::trace('atkGoldTree start.params copyid:%d fmt:%s.',$copyId,$fmt);
	    $copyId = intval($copyId);
	    if(empty($copyId))
	    {
	        throw  new FakeException('error params.params copyid:%d fmt:%s.',$copyId,$fmt);
	    }
	    $battleRet = ACopyLogic::atkGoldTree($copyId, $fmt, $byItem);
	    Logger::trace('atkGoldTree end.result %s.',$battleRet);
	    return $battleRet;
	}
	
	
	
	public function reviveCard($copyId,$baseLv,$cardId)
	{
		$copyId = intval($copyId);
		$baseLv = intval($baseLv);
		$cardId = intval($cardId);
		if(empty($cardId) || empty($copyId) || ($baseLv < 0))
		{
			throw new FakeException('error param!!!! copyid %s baselv %s.cardid %s.',$copyId,$baseLv,$cardId);
		}
		$base_id = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
		$ret = CopyUtil::reviveCard($base_id, $baseLv, $cardId);
		return $ret;
	}
	public function reFight($copyId,$baseLv)
	{
		$copyId = intval($copyId);
		$baseLv = intval($baseLv);
		if(empty($copyId) || ($baseLv < 0))
		{
			throw new FakeException('error param!!!!copyid %d.baselv %d.',$copyId,$baseLv);
		}
		$base_id = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
		$ret = CopyUtil::reFight($copyId, $base_id, $baseLv);
		return $ret;
	}
	public function leaveBaseLevel($copyId,$baseLv)
	{
		$copyId = intval($copyId);
		$baseLv = intval($baseLv);
		if($copyId <= 0 || ($baseLv < 0))
		{
			throw new FakeException('error param!!!! copyid %d.baselv %d.',$copyId,$baseLv);
		}
		if(!isset(btstore_get()->ACTIVITYCOPY[$copyId]))
		{
		    throw new ConfigException('no such activity copy with copyid %s.',$copyId);
		}
		$base_id = intval(btstore_get()->ACTIVITYCOPY[$copyId]['base_id']);
		$ret = CopyUtil::leaveBaseLevel($copyId, $base_id, $baseLv);
		return $ret;
	}
/* (non-PHPdoc)
     * @see IACopy::atkGoldTreeByGold()
     */
    public function atkGoldTreeByGold ($copyId, $fmt = array())
    {
        Logger::trace('atkGoldTreeByGold start.params copyid:%d fmt:%s.',$copyId,$fmt);
        $copyId = intval($copyId);
        if(empty($copyId))
        {
            throw  new FakeException('error params.params copyid:%d fmt:%s.',$copyId,$fmt);
        }
        $battleRet = ACopyLogic::atkGoldTreeByGold($copyId, $fmt);
        Logger::trace('atkGoldTreeByGold end.result %s.',$battleRet);
        return $battleRet;
    }
/* (non-PHPdoc)
     * @see IACopy::atkExpTreasure()
     */
    public function doBattle ($copyId, $baseId, $armyId, $fmt=array())
    {
        // TODO Auto-generated method stub
        Logger::trace('atkExpTreasure start.params copyid:%d armyId:%d fmt:%s.',$copyId,$armyId,$fmt);
        if(empty($copyId))
        {
            throw  new FakeException('error params.params copyid:%d fmt:%s.',$copyId,$fmt);
        }
        $battleRet = array();
        if(MyACopy::getTypeofActivityCopy($copyId) == ACT_COPY_TYPE::EXPTREASURE)
        {
            $battleRet = ACopyLogic::atkExpTreasure($copyId, $armyId, $fmt);
        }
        else if(MyACopy::getTypeofActivityCopy($copyId) == ACT_COPY_TYPE::EXPHERO)
        {
            $battleRet = ACopyLogic::atkExpHero($copyId, $armyId, $fmt);
        }
        elseif (MyACopy::getTypeofActivityCopy($copyId) == ACT_COPY_TYPE::EXPUSER)
        {
        	$uid = RPCContext::getInstance()->getUid();
        	
        	if (FALSE == EnSwitch::isSwitchOpen(SwitchDef::EXPUSER))
        	{
        		throw new FakeException('EXPUSER is not open!uid: %d.',$uid);
        	}
        	
        	$battleRet = ACopyLogic::atkExpUser($copyId, $baseId, $armyId, $fmt,$uid);
        }
        elseif ( MyACopy::getTypeofActivityCopy($copyId) == ACT_COPY_TYPE::DESTINY )
        {
            $battleRet = ACopyLogic::atkDestiny($copyId, $baseId, $armyId, $fmt);
        }
        Logger::trace('atkExpTreasure end.result %s.',$battleRet);
        return $battleRet;
    }
    
    public function buyGoldTreeAtkNum($num)
    {
        list($num) = Util::checkParam(__METHOD__, func_get_args());
        ACopyLogic::buyGoldTreeAtkNum($num);
        return 'ok';
    }
    
    public function buyExpTreasAtkNum($num)
    {
        list($num) = Util::checkParam(__METHOD__, func_get_args());
        ACopyLogic::buyExpTreasAtkNum($num);
        return 'ok';
    }
    
    public function refreshBattleInfo()
    {
        $acopyInst = MyACopy::getInstance();
        $goldTree = $acopyInst->getActivityCopyObj(ACT_COPY_TYPE::GOLDTREE_COPYID);
        $goldTree->rfrBattleInfo();
        $acopyInst->save();
        return 'ok';
    }
    
    public function setBattleInfoValid($isValid)
    {
        $isValid = intval($isValid);
        $acopyInst = MyACopy::getInstance();
        $goldTree = $acopyInst->getActivityCopyObj(ACT_COPY_TYPE::GOLDTREE_COPYID);
        $battleInfo = $goldTree->getBattleInfo();
        if(empty($battleInfo))
        {
            throw new FakeException('not save battleinfo for goldtree');
        }
        $goldTree->setBattleInfoValid($isValid);
        $acopyInst->save();
        return 'ok';
    }
    
    public function buyExpUserAtkNum($num)
    {
    	list($num) = Util::checkParam(__METHOD__, func_get_args());
    	
    	$uid = RPCContext::getInstance()->getUid();
    	
    	$ret = ACopyLogic::buyExpUserAtkNum($uid, $num);
    	
    	return $ret;
    }
    
    public function buyDestinyAtkNum($num=1)
    {
        list($num) = Util::checkParam(__METHOD__, func_get_args());
        
        $uid = RPCContext::getInstance()->getUid();
         
        $ret = ACopyLogic::buyDestinyNum($uid, $num);
         
        return $ret;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */