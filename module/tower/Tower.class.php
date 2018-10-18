<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Tower.class.php 255251 2016-08-09 07:30:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/Tower.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-09 07:30:26 +0000 (Tue, 09 Aug 2016) $
 * @version $Revision: 255251 $
 * @brief 
 *  
 **/
class Tower implements ITower
{
    private $uid = 0;
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
	/* (non-PHPdoc)
	 * @see ITower::getTowerInfo()
	 */
	public function getTowerInfo() {
		// TODO Auto-generated method stub
		Logger::debug('enter Tower.getTowerInfo.');
		$towerInfo = TowerLogic::getTowerInfo($this->uid);
		Logger::debug('Tower.getTowerInfo end.Result:%s.',$towerInfo);
		return $towerInfo;
	}

	/* (non-PHPdoc)
	 * @see ITower::buyDefeatNum()
	 */
	public function buyDefeatNum($type=1) {
		// TODO Auto-generated method stub
		Logger::debug('Tower.buyDefeatNum start.');
		$type = intval( $type );
		$spend = TowerLogic::buyDefeatNum($this->uid, $type);
		Logger::debug('Tower.buyDefeatNum end.');
		return $spend;
	}

	/* (non-PHPdoc)
	 * @see ITower::defeatMonster()
	 */
	public function defeatMonster($level, $armyId, $type=1) {
		// TODO Auto-generated method stub
		Logger::debug('Tower.defeatMonster start.params:level %s,armyid %s, type:%s.',$level,$armyId, $type);
		$level = intval( $level );
		$armyId = intval( $armyId );
		$type = intval( $type );
		
		if ( $level <= 0 || $armyId <= 0 || $type <= 0 )
		{
		    throw new FakeException("param err. level:%d, armyId:%d, type:%d.", $level, $armyId, $type);
		}
		
		$ret = TowerLogic::doBattle($level, $armyId, $this->uid, $type);
		Logger::debug('Tower.defeatMonster end.Result:%s.',$ret);
		return $ret;
	}


    /* (non-PHPdoc)
	 * @see ITower::enterLevel()
	 */
	public function enterLevel($level, $type=1) {
		// TODO Auto-generated method stub
		Logger::trace('Tower.enterLevel start.');//初始化atkInfo
		$level = intval( $level );
		$type = intval( $type );
		
		if ( $level <= 0 || $type <= 0 )
		{
		    throw new FakeException("param err. level:%d, type:%d.", $level, $type);
		}
		TowerLogic::enterLevel($level, $this->uid, $type);
		Logger::trace('Tower.enterLevel end.');
		return 'ok';
	}
	
	public function leaveTower()
	{
		return 'ok';
	}
	
	public function leaveTowerLv($towerLv)
	{
	    if(AtkInfo::getInstance($this->uid)->getCopyId() != $towerLv)
	    {
	        Logger::warning('the copyid in memcache is %d.the param towerlv is %d.',AtkInfo::getInstance()->getCopyId(),$towerLv);
	        return 'error';
	    }
	    AtkInfo::getInstance()->delAtkInfo();
	    return 'ok';
	}
	
	public function resetTower($type=1)
	{
	    Logger::trace('Tower.resetTower start');
	    $type = intval( $type );
	    $ret = TowerLogic::resetTower($this->uid, $type);
	    Logger::trace('Tower.resetTower end.towerinfo is %d.',MyTower::getInstance()->getTowerInfo());
	    return $ret;
	}
	
	public function sweep($startLv,$endLv, $type=1)
	{
	    Logger::trace('Tower.sweep start.params startlv %d.endlv %d.',$startLv,$endLv);
	    $startLv = intval( $startLv );
	    $endLv = intval( $endLv );
	    $type = intval( $type );
	    
	    if ( $startLv <= 0 || $endLv <= 0 || $type <= 0 )
	    {
	        throw new FakeException("param err. startLv:%d, endLv:%d, type:%d.", $startLv, $endLv, $type);
	    }
	    
	    $towerInfo = TowerLogic::sweep($startLv, $endLv, $this->uid, $type);
	    Logger::trace('Tower.sweep end.result %s.',$towerInfo);
	    return $towerInfo;
	}
	
	public function endSweep($type=1)
	{
	    Logger::trace('Tower.endSweep start');
	    $type = intval( $type );
	    $ret = TowerLogic::endSweep($this->uid, $type);
	    Logger::trace('Tower.endSweep end.result %s.',$ret);
	    return $ret;
	}
	
	public function getTowerRank($rankNum)
	{
	    Logger::trace('Tower.getTowerRank start.param ranknum %d.',$rankNum);
	    list($rankNum) = Util::checkParam(__METHOD__, func_get_args());
	    $ret = TowerLogic::getTowerRank($rankNum, $this->uid);
	    Logger::trace('Tower.getTowerRank end.');
	    return $ret;
	}
	
	public function reviveCard($towerLv,$cardId)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::TOWER) == FALSE)
	    {
	        throw new FakeException("switch TOWER is not open.");
	    }
	    $baseLevel = BaseLevel::SIMPLE;
	    $baseId = btstore_get()->TOWERLEVEL[$towerLv]['base_id'];
	    $ret = CopyUtil::reviveCard($baseId, $baseLevel, $cardId);
	    Logger::debug('NCopy.reviveCard end.Result:%s.',$ret);
	    return $ret;
	}
	
	public function enterSpecailLevel($towerLvId)
	{
	    TowerLogic::enterSpecailLevel($towerLvId, $this->uid);
	    return 'ok';
	}
	
	public function defeatSpecialTower($towerLvId,$armyId,$fmt=array())
	{
	    $ret = TowerLogic::defeatSpecialTower($towerLvId, $armyId, $this->uid, $fmt);
	    return $ret;
	}
	
	public function buyAtkNum($num, $type=1)
	{
	    $num = intval( $num );
	    $type = intval( $type );
	    
	    if ( $num <= 0 || $type <= 0 )
	    {
	        throw new FakeException("param err. num:%d, type:%d.", $num, $type);
	    }
	    
        TowerLogic::buyAtkNum($num, $type);
	    return 'ok';
	}
	
	public function buySpecialTower($num=1)
	{
	    $num = intval($num);
	    $ret = TowerLogic::buySpecialTower($this->uid, $num);
	    return $ret;
	}
	
	public function sweepByGold($endLv=0, $type=1)
	{
	    Logger::trace('tower.sweepByGold param endlv %d',$endLv);
	    $endLv = intval($endLv);
	    $type = intval( $type );
	    $ret = TowerLogic::sweepByGold($this->uid,$endLv, $type);
	    return $ret;
	}
	
	public function getShopInfo()
	{
	    $ret = TowerLogic::getShopInfo($this->uid);
	    return $ret;
	}
	
	public function buy($id, $num = 1)
	{
	    $id = intval( $id );
	    $num = intval( $num );
	    
	    $ret = TowerLogic::buy($this->uid, $id, $num);
	    return $ret;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */