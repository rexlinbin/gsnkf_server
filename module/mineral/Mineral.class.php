<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mineral.class.php 238381 2016-04-14 11:57:55Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/Mineral.class.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-14 11:57:55 +0000 (Thu, 14 Apr 2016) $
 * @version $Revision: 238381 $
 * @brief 
 *  
 **/
class Mineral
{
    private $uid = NULL;
    
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
    /* (non-PHPdoc)
     * 是占领无人占领的资源矿  会打守护资源矿的部队
    * @see IMineral::capturePit()
    */
    public function capturePit($domainId, $pitId) {
        // TODO Auto-generated method stub
        Logger::debug('Mineral.capturePit start.params:%s,%s.',$domainId,$pitId);
        list($domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId))
        {
            $capRet	=	MineralLogic::captureGoldPit($this->uid,$domainId, $pitId);
        }
        else
        {
            $capRet	=	MineralLogic::capturePitNotOccupied($this->uid,$domainId, $pitId);
        }
        Logger::debug('Mineral.capturePit end.Result:%s.',$capRet);
        return $capRet;
    }
    
    /**
     * 在策划配置时间之外使用金币抢夺别人的资源矿
     * @param $domainId
     * @param $pitId
     * @return array
     */
    public function grabPitByGold($domainId,$pitId)
    {
        Logger::debug('Mineral.grabPitByGold start.params:%s,%s.',$domainId,$pitId);
        list($domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId))
        {
            $capRet = MineralLogic::grabGoldPitByGold($this->uid,$domainId, $pitId);
        }
        else
        {
            $capRet	=	MineralLogic::grabPitByGold($this->uid,$domainId, $pitId);
        }
        Logger::debug('Mineral.grabPitByGold end.Result:%s.',$capRet);
        return $capRet;
    }
    
    public function grabPit($domainId,$pitId)
    {
        Logger::debug('Mineral.grabPit start.params:%s,%s.',$domainId,$pitId);
        list($domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId))
        {
            $capRet = MineralLogic::grabGoldPit($this->uid,$domainId, $pitId);
        }
        else
        {
            $capRet	=	MineralLogic::grabPit($this->uid,$domainId, $pitId);
        }
        Logger::debug('Mineral.grabPit end.Result:%s.',$capRet);
        return $capRet;
    }
    
    /* (non-PHPdoc)
     * 放弃资源矿
    * @see IMineral::giveUpPit()
    */
    public function giveUpPit($domainId, $pitId) {
        // TODO Auto-generated method stub
        Logger::debug('Mineral.giveUpPit start.params:%s,%s.',$domainId,$pitId);
        list($domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        $giveUpRet	=	MineralLogic::giveUpPit($this->uid,$domainId, $pitId);
        Logger::debug('Mineral.giveUpPit end.Result:%s.',$giveUpRet);
        return $giveUpRet;
    }
    
    /* (non-PHPdoc)
     * @see IMineral::getPitsByDomain()
    */
    public function getPitsByDomain($domainId) {
        // TODO Auto-generated method stub
        Logger::debug('Mineral.getPitsByDomain start.params:%s.',$domainId);
        list($domainId) = Util::checkParam(__METHOD__, func_get_args());
        if(EnSwitch::isSwitchOpen(SwitchDef::MINERAL) == FALSE)
        {
            throw new FakeException('Mineral switch is not open!');
        }
        $domainPits	=	MineralLogic::getPitByDomain($domainId);
        Logger::trace('setsession resource %s.',$domainId);
        RPCContext::getInstance()->setSession(MINERAL_SESSION_NAME::DOMAINID, $domainId);
        RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::MINERAL);
        Logger::debug('Mineral.getPitsByDomain end.Result:%s.',$domainPits);
        return $domainPits;
    }
    
    /* (non-PHPdoc)
     * @see IMineral::getSelfPitsInfo()
    */
    public function getSelfPitsInfo() {
        // TODO Auto-generated method stub
        Logger::debug('Mineral.getSelfPitsInfo start.');
        $selfPits	=	MineralLogic::getSelfPitsInfo($this->uid);
        Logger::debug('Mineral.getSelfPitsInfo end.Result:%s.',$selfPits);
        return $selfPits;
    }
    
    /* (non-PHPdoc)
     * @see IMineral::exploreRes()
    */
    public function explorePit($pitType=0) {
        // TODO Auto-generated method stub
        Logger::debug('Mineral.explorePit start.');
        $exploreInfo	=	MineralLogic::explorePit($pitType);
        if(!empty($exploreInfo))
        {
            $firstPitInfo = current($exploreInfo);
            Logger::trace('setsession resource %s.',$firstPitInfo[TblMineralField::DOMAINID]);
            RPCContext::getInstance()->setSession(MINERAL_SESSION_NAME::DOMAINID, $firstPitInfo[TblMineralField::DOMAINID]);
            RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::MINERAL);
        }
        Logger::debug('Mineral.explorePit end.Result:%s.',$exploreInfo);
        return $exploreInfo;
    }
    
    
    public function duePit($uid,$domainId,$pitId)
    {
        Logger::debug('Mineral.duePit start.');
        list($uid,$domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        $ret	=	MineralLogic::duePit($uid, $domainId, $pitId);
        Logger::debug('Mineral.duePit end.Result:%s.',$ret);
        return $ret;
    }
    
    public function getDomainIdOfUser($uid,$domainType)
    {
        list($uid,$domainType) = Util::checkParam(__METHOD__, func_get_args());
        $domainId = MineralDAO::getDomainIdOfUser($uid,$domainType);
        return $domainId;
    }
    
    public function occupyPit($domainId, $pitId)
    {
        Logger::trace('Mineral::occupyPit Start. domainId:%d, pitId:%d', $domainId, $pitId);
        list($domainId, $pitId) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId))
        {
            throw new FakeException('can not guard domain %d.it is gold domain.',$domainId);
        }
        $ret = MineralLogic::occupyPit($this->uid,$domainId, $pitId);
        Logger::trace('Mineral::occupyPit End. domainId:%d, pitId:%d', $domainId, $pitId);
        return $ret;
    }
    
    function abandonPit($domainId, $pitId)
    {
        Logger::trace('Mineral::abandonPit Start. domainId:%d, pitId:%d', $domainId, $pitId);
        list($domainId, $pitId) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId))
        {
            throw new FakeException('can not abandon guard domain %d.it is gold domain.',$domainId);
        }
        $ret = MineralLogic::abandonPit($this->uid,$domainId, $pitId);
        Logger::trace('Mineral::abandonPit End. domainId:%d, pitId:%d', $domainId, $pitId);
        return $ret;
    }
    
    function robGuards($domainId1, $pitId1, $tuid)
    {
        Logger::trace('Mineral::robGuards Start. domainId1:%d, pitId1:%d, tuid:%d', $domainId1, $pitId1, $tuid);
        list($domainId1, $pitId1, $tuid) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId1))
        {
            throw new FakeException('can not rob guard of domain %d.it is gold domain.',$domainId1);
        }
        $ret = MineralLogic::robGuards($this->uid,$domainId1, $pitId1, $tuid);
        Logger::trace('Mineral::robGuards End. domainId1:%d, pitId1:%d, tuid:%d', $domainId1, $pitId1, $tuid);
        return $ret;
    }
    
    function delayPitDueTime($domainId, $pitId)
    {
        Logger::trace('Mineral::delayPit Start. domainId:%d, pitId:%d', $domainId, $pitId);
        list($domainId, $pitId) = Util::checkParam(__METHOD__, func_get_args());
        $ret = MineralLogic::delayPitDueTime($domainId, $pitId, $this->uid);
        Logger::trace('Mineral::delayPit End. domainId:%d, pitId:%d', $domainId, $pitId);
        return $ret;
    }
    
    public function leave()
    {
        RPCContext::getInstance()->unsetSession(MINERAL_SESSION_NAME::DOMAINID);
        RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
    }
    
    public function duePitGuard($uid,$domainId,$pitId)
    {
        Logger::trace('mineral.duePitGuard start.params uid %d domainid %d pitid %d',$uid, $domainId, $pitId);
        list($uid,$domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        if(MineralLogic::isGoldDomain($domainId))
        {
            throw new FakeException('can not due guard of domain %d for user %d.it is gold domain.',$domainId,$uid);
        }
        $ret = MineralLogic::endPitGuard($uid, $domainId, $pitId);
        return $ret;
    }
    
    public function duePitManually($uid,$domainId,$pitId)
    {
        Logger::info('mineral.duePitManually start.params uid %d domainid %d pitid %d',$uid, $domainId, $pitId);
        list($uid,$domainId,$pitId) = Util::checkParam(__METHOD__, func_get_args());
        MineralLogic::duePit($uid, $domainId, $pitId);
    }
    
    public function getRobLog()
    {
        return MineralLogic::getRobLog();
    }
    
    public function updateRobLog($robInfo)
    {
        MineralLogic::updateRobLog($robInfo);
    }
    
    /**
     * 提供一个接口给玩家退出公会和加入公会时使用
     */
    public static function changeGuild($newGuildId,$uid=0)
    {
    	if($newGuildId < 0)
    	{
    		return;
    	}
    	if($uid == 0)
    	{
    		$uid = RPCContext::getInstance()->getUid();
    	}
    	if($uid == 0)
    	{
    		return;
    	}
    	RPCContext::getInstance()->executeTask($uid, 'mineral.doChangeGuild', array($uid, $newGuildId), false);
    }
   
    public function doChangeGuild($uid,$newGuildId)
    {
		Logger::trace("mineral.doChangeGuild start,uid:%d,guild id:%d.",$uid,$newGuildId);
		
		MineralLogic::doChangeGuild($uid, $newGuildId);
		
		Logger::trace("mineral.doChangeGuild end.");
    	
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */