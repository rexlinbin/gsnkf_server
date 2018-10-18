<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PitObj.class.php 251057 2016-07-11 11:04:56Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/PitObj.class.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-11 11:04:56 +0000 (Mon, 11 Jul 2016) $
 * @version $Revision: 251057 $
 * @brief 
 *  
 **/
class PitObj
{
    protected $domainId = NULL;
    protected $pitId = NULL;
    protected $domainType = NULL;//普通矿区、高级矿区、金币矿区三种
    protected $guildId=NULL;//占领者的军团ID
    /**
     * 矿的基本信息
     * @var Array
     * [
     *     uid:int
     *     pit_id:int
     *     domain_id:int
     *     due_timer:int
     *     occupy_time:int
     *     delay_times:int
     *     total_guards_time:int
     *     domain_type:int
     *     pit_type:int
     * ]
     */
    protected $pitInfo = NULL;
    protected $pitBuffer = NULL;
    private $pitInfoForFront = NULL;
    /**
     * 矿的守卫信息
     * @var array
     * [
     *     uid=>array
     *     [
     *         pit_id:int
     *         domain_id:int
     *         uid:int
     *         guard_time:int
     *         .............
     *     ]
     * ]
     */
    protected $arrGuardInfo = NULL;
    protected $arrGuardBuffer = NULL;
    //如果守卫军被抢去守卫另一个矿 那么它会在另一个矿对象里更新  如果两个地方都更新  会造成数据错误
    protected $arrGuardBeRobbed = array();
    //便于批量更新守卫信息
    protected $quitAllGuard = FALSE;
    //是否要收到的结束资源矿占领
    protected $dueManually = FALSE; 
    
    public function __construct($pitInfo,$arrGuardInfo)
    {
        if(empty($pitInfo))
        {
            throw new FakeException('construct pitobj failed.pitinfo is empty');
        }
        $this->pitInfo = $pitInfo;
        $this->pitBuffer = $pitInfo;
        $this->domainId = $this->pitInfo[TblMineralField::DOMAINID];
        $this->pitId = $this->pitInfo[TblMineralField::PITID];
        $this->domainType = $this->pitInfo[TblMineralField::DOMAINTYPE];
        $this->guildId=$this->pitInfo[TblMineralField::GUILDID];
        $this->arrGuardInfo = $arrGuardInfo;
        $this->arrGuardBuffer = $arrGuardInfo;
        //判断资源矿占领是否到期  如果矿的占领过期了一定时间  手动的结束占领
        $dueTime = MineralLogic::getCaptureDueTime($this->pitInfo);
        if($dueTime + MineralDef::DUE_MANUALLY_NEED_DUETIME < 0)//TODO:过期了一天
        {
            Logger::fatal('need due pit capture manually;domain %d pit %d',$this->domainId,$this->pitId);
//             $this->due();
//             $this->dueManually = TRUE;
        }
        //有玩家守卫   但是没有玩家占领 
        if(!empty($arrGuardInfo) && (empty($this->pitInfo[TblMineralField::UID])))
        {
            Logger::fatal('domain %d pit %d has not captureuser.but has guarduser %s.please fix manually.',
                    $this->domainId,$this->pitId,$arrGuardInfo);
        }
        //判断资源矿守卫是否到期
        foreach($this->arrGuardInfo as $uid => $guardInfo)
        {
            if(empty($guardInfo[TblMineralGuards::DOMAINID]))
            {
                Logger::warning('pit ( domain %d pitid %d ) guardinfo %s error',
                        $this->domainId,$this->pitId,$guardInfo);
                continue;
            }
            $guardTime = $guardInfo[TblMineralGuards::GUARDTIME];
            if($guardTime + MineralLogic::getPitGuardTime()+MineralDef::DUE_MANUALLY_NEED_DUETIME < Util::getTime())
            {
                Logger::fatal('need due pit guard manually.guard %d domain %d pit %d.guardinfo %s',
                        $uid,$this->domainId,$this->pitId,$guardInfo);
//                 $this->dueGuard($uid);
            }
        }
        Logger::trace('pitobj construct pitinfo %s guardinfo %s',$pitInfo,$arrGuardInfo);
    }
    
    public function getPitInfo()
    {
        if(!empty($this->pitInfoForFront))
        {
            return $this->pitInfoForFront;
        }
        $arrPit = array(
                $this->domainId => array(
                        $this->pitId => $this->getDbInfo()
                        ),
                );
        $arrPitInfo = MineralLogic::resetArrPitInfo($arrPit);
        $pitInfo = $arrPitInfo[$this->domainId][$this->pitId];
        $this->pitInfoForFront = $pitInfo;
        return $pitInfo;
    }
    
    public function getDbInfo()
    {
        $pitInfo = $this->pitInfo;
        $pitInfo['guards'] = $this->arrGuardInfo;
        return $pitInfo;
    }
    
    public function getDomainId()
    {
        return $this->domainId;
    }

    public function getPitId()
    {
        return $this->pitId;
    }
    
    public function getCapture()
    {
        return $this->pitInfo[TblMineralField::UID];
    }
    
    public function getDomainType()
    {
        return $this->domainType;
    }
    
    public function getDueTimer()
    {
        return $this->pitInfo[TblMineralField::DUETIMER];
    }
    
    public function getOccupyTime()
    {
        return $this->pitInfo[TblMineralField::OCCUPYTIME];
    }
    
    public function getTotalGuardTime()
    {
        return $this->pitInfo[TblMineralField::TOTALGUARDSTIME];
    }
    
    public function getDelayTimes()
    {
        return $this->pitInfo[TblMineralField::DELAYTIMES];
    }

    public function getCaptureAcquireToNow()
    {
        return MineralLogic::getCaptureAcquire($this->pitBuffer, 
                $this->arrGuardBuffer);
    }
    
    public function getGuardAcquireToNow($uid)
    {
        if(!isset($this->arrGuardBuffer[$uid]))
        {
            return NULL;
        }
        return MineralLogic::getGuardAcquire($this->arrGuardBuffer[$uid]);
    }
    
    public function isInProtectTime()
    {
        if($this->getCapture() == 0)
        {
            return FALSE;
        }
        $protectTime = btstore_get()->MINERAL[$this->domainId]['pits'][$this->pitId][PitArr::PROTECTTIME];
        $captureTime = $this->pitInfo[TblMineralField::OCCUPYTIME];
        if($captureTime+$protectTime > Util::getTime())
        {
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * 到期
     */
    public function due()
    {
        $this->resetPitInfo();
    }
    /**
     * 换矿主(capture的变换方式0-int;int-0;int-int)
     * @param int $newCapture
     */
    public function changeCapture($newCapture)
    {
        $capture = $this->getCapture();
        if(!empty($capture))
        {
            $dueTimer = $this->getDueTimer();
            TimerTask::cancelTask($dueTimer);
        }
        $this->resetPitInfo();
        if(empty($newCapture))
        {
            return;
        }
        $newDueTimer = 0;
        $newDueTime = Util::getTime() + MineralLogic::getPitHarvestTime(
                $this->domainId, $this->pitId, $newCapture);
        $newDueTimer = TimerTask::addTask($newCapture, $newDueTime,
                 'mineral.duePit', array($newCapture,$this->domainId,$this->pitId));
        $this->pitInfo[TblMineralField::UID] = $newCapture;
        $this->pitInfo[TblMineralField::DUETIMER] = $newDueTimer;
        $this->pitInfo[TblMineralField::OCCUPYTIME] = Util::getTime();
       
    }
    
    protected function resetPitInfo()
    {
        $this->pitInfo[TblMineralField::UID] = 0;
        $this->pitInfo[TblMineralField::DELAYTIMES] = 0;
        $this->pitInfo[TblMineralField::TOTALGUARDSTIME] = 0;
        $this->pitInfo[TblMineralField::OCCUPYTIME] = 0;
        $this->pitInfo[TblMineralField::DELAYTIMES] = 0;
        $this->pitInfo[TblMineralField::DUETIMER] = 0;
        
        $this->quitAllGuard();
    }
    
    
    public function delay()
    {    
        $dueTimer = $this->getDueTimer();
        TimerTask::cancelTask($dueTimer);
        
        $capture = $this->getCapture();
        $dueTime = $this->getOccupyTime() 
        		+ MineralLogic::getPitHarvestTime($this->domainId, $this->pitId, $capture);
        
        for ( $i = 0; $i <= $this->pitInfo[TblMineralField::DELAYTIMES]; $i++ )
        {
        		$dueTime += MineralLogic::getDelayTimeOnce($this->pitInfo[TblMineralField::DELAYTIMES]);
        }
        $this->pitInfo[TblMineralField::DELAYTIMES]++;
        
        $newDueTimer = TimerTask::addTask($capture, $dueTime,
        		'mineral.duePit', array($this->getCapture(),$this->domainId,$this->pitId));
        $this->pitInfo[TblMineralField::DUETIMER] = $newDueTimer;   
        
    }
    
    public function addTotalGuardTime($time)
    {
        $this->pitInfo[TblMineralField::TOTALGUARDSTIME] += $time;
    }
    
    protected function quitAllGuard()
    {
        foreach($this->arrGuardInfo as $uid => $guardInfo)
        {
            $this->giveUpGuard($uid);
        }
        $this->quitAllGuard = TRUE;
    }
    
    /**
     * 添加一个守卫
     * @param int $uid
     * @return boolean
     */
    public function addGuard($uid)
    {
        if(isset($this->arrGuardInfo[$uid]))
        {
            return FALSE;
        }
        $dueTime = Util::getTime() + MineralLogic::getPitGuardTime();
        $dueTimer = TimerTask::addTask($uid, $dueTime,
                'mineral.duePitGuard', array($uid,$this->domainId,$this->pitId));
        $guardInfo = array(
                TblMineralGuards::UID => $uid,
                TblMineralGuards::DOMAINID => $this->domainId,
                TblMineralGuards::DUETIMER => $dueTimer,
                TblMineralGuards::GUARDTIME => Util::getTime(),
                TblMineralGuards::PITID => $this->pitId,
                TblMineralGuards::STATUS => GuardType::ISGUARD,
        );
        $this->arrGuardInfo[$uid] = $guardInfo;
        return TRUE;
    }
    
    /**
     * 玩家放弃守卫
     * @param int $uid
     */
    public function giveUpGuard($uid)
    {
        if(!isset($this->arrGuardInfo[$uid]))
        {
            return FALSE;
        }
        $dueTimer = $this->arrGuardInfo[$uid][TblMineralGuards::DUETIMER];
        TimerTask::cancelTask($dueTimer);
        $this->resetGuardInfo($uid);
        return TRUE;
    }
    
    /**
     * 守卫到期了
     * @param int $uid
     */
    public function dueGuard($uid)
    {
        if(!isset($this->arrGuardInfo[$uid]))
        {
            return FALSE;
        }
        $this->resetGuardInfo($uid);
        return TRUE;
    }
    /**
     * 守卫被抢走了
     * @param int $uid
     */
    public function robGuardByOther($uid)
    {
        $this->arrGuardBeRobbed[$uid] = $uid;
        return $this->giveUpGuard($uid);
    }
    
    protected function resetGuardInfo($uid)
    {
        $this->arrGuardInfo[$uid][TblMineralGuards::DUETIMER] = 0;
        $this->arrGuardInfo[$uid][TblMineralGuards::GUARDTIME] = 0;
        $this->arrGuardInfo[$uid][TblMineralGuards::DOMAINID] = 0;
        $this->arrGuardInfo[$uid][TblMineralGuards::PITID] = 0;
        $this->arrGuardInfo[$uid][TblMineralGuards::STATUS] = GuardType::ISNOTGUARD;
    }
    
    public function getGuardCount()
    {
        return count($this->arrGuardInfo);
    }
    
    public function getGuardTime($uid)
    {
        if(!isset($this->arrGuardInfo[$uid]))
        {
            return Util::getTime();
        }
        return $this->arrGuardInfo[$uid][TblMineralGuards::GUARDTIME];
    }
    
    public function getArrGuard()
    {
        return $this->arrGuardInfo;
    }
    
    
    public function save()
    {
        if($this->pitInfo == $this->pitBuffer && 
                ($this->arrGuardBuffer == $this->arrGuardInfo))
        {
            return FALSE;
        }
        //保存pit
        if($this->pitInfo != $this->pitBuffer)
        {
        	$pitInfoForUpdate=array();
        	foreach ($this->pitInfo as $k=>$v)
        	{
        		if ($v!=$this->pitBuffer[$k])
        		{
        			$pitInfoForUpdate[$k]=$v;
        		}
        	}
        	if (!empty($pitInfoForUpdate))
        	{
        		MineralDAO::savePitInfo($this->domainId,$this->pitId,$pitInfoForUpdate);
        	}
        }
        //算矿主资源矿收益
        if($this->pitInfo[TblMineralField::UID] != $this->pitBuffer[TblMineralField::UID] &&
                (!empty($this->pitBuffer[TblMineralField::UID])))
        {
            $preCapture = $this->pitBuffer[TblMineralField::UID];
            $acquire = $this->getCaptureAcquireToNow();
            //算收益 发到奖励中心
            MineralLogic::sendCaptureRewardToCenter($preCapture, $acquire['silver'],$acquire['iron']);
        }
        if($this->quitAllGuard)
        {
           //保存所有守卫信息到数据库
           $arrUid = array();
           foreach($this->arrGuardBuffer as $uid => $guardInfo)
           {
               $arrUid[] = $uid;
           }
           if(!empty($arrUid))
           {
               $arrWhere = array(
                       array(TblMineralGuards::UID, 'IN', $arrUid)
               );
               $arrField = array(
                       TblMineralGuards::DOMAINID => 0,
                       TblMineralGuards::PITID => 0,
                       TblMineralGuards::DUETIMER =>0,
                       TblMineralGuards::STATUS => GuardType::ISNOTGUARD,
                       TblMineralGuards::GUARDTIME => 0
               );
               MineralDAO::updateGuards($arrWhere, $arrField);
           }
        }
        foreach($this->arrGuardInfo as $uid => $guardInfo)
        {
            if(!isset($this->arrGuardBuffer[$uid]) ||
                    ($this->arrGuardBuffer[$uid] != $guardInfo))
            {
                //保存到数据库
                if(!$this->quitAllGuard 
                        && (!isset($this->arrGuardBeRobbed[$uid])))
                {
                    MineralDAO::insertUpdateGuard($guardInfo);
                }
                //算收益 发到奖励中心
                if(isset($this->arrGuardBuffer[$uid]))
                {
                    if(empty($guardInfo[$uid][TblMineralGuards::DOMAINID] ) 
                     && !empty( $this->arrGuardBuffer[$uid][TblMineralGuards::DOMAINID] ))
                    {
                        $acquire = $this->getGuardAcquireToNow($uid);
                        MineralLogic::sendGuardRewardToCenter($uid, $acquire['silver']);
                    }
                }
            }
        }
//         if($this->dueManually && (!empty($this->getCapture())))
//         {
//             RPCContext::getInstance()->executeTask($this->getCapture(),
//                     'mineral.duePitManually', array($this->getCapture(),$this->domainId,$this->pitId));
//         }
        //推消息
        $this->sendMsgOnPitRfr();
        $this->pitBuffer = $this->pitInfo;
        $this->arrGuardBuffer = $this->arrGuardInfo;
        $this->quitAllGuard = FALSE;
        $this->arrGuardBeRobbed = array();
        $this->pitInfoForFront = NULL;
//         $this->dueManually = FALSE;
    }
    
    protected function sendMsgOnPitRfr()
    {
        //给所有此页的玩家       原矿主\现矿主(其他玩家申请守卫，被抢守卫军)\原守卫军\现守卫军（守卫到期、矿主换了） 推消息
    	$this->pitInfoForFront = NULL;//清除缓存， FIXME：目前getPitInfo中的缓存，不能自动失效或者更新。当有修改时，仍然返回缓存的数据
        $pitInfo = $this->getPitInfo();
        RPCContext::getInstance()->sendFilterMessage(
                'resource', $this->domainId,
                PushInterfaceDef::MINERAL_PIT_UPDATE, $pitInfo);
        
        //此资源矿相关的玩家有可能不在此页     要把信息的更新推给他们
        $arrMsgUid = array();
        if($this->pitBuffer[TblMineralField::UID] != 0)
        {
            $uid = $this->pitBuffer[TblMineralField::UID];
            $arrMsgUid[$uid] = $uid;
        }
        if($this->pitInfo[TblMineralField::UID] != 0)
        {
            $uid = $this->pitInfo[TblMineralField::UID];
            $arrMsgUid[$uid] = $uid;
        }
        foreach($this->arrGuardBuffer as $uid => $guardInfo)
        {
            if(empty($uid))
            {
                continue;
            }
            $arrMsgUid[$uid] = $uid;
        }
        foreach($this->arrGuardInfo as $uid => $guardInfo)
        {
            if(empty($uid))
            {
                continue;
            }
            $arrMsgUid[$uid] = $uid;
        }
        //前端反映推迟占领资源矿时  由于推送两个消息造成卡   作此优化       
        $guid = RPCContext::getInstance()->getUid();
        if(RPCContext::getInstance()->getSession(MINERAL_SESSION_NAME::DOMAINID)
                 == $this->domainId)
        {
            unset($arrMsgUid[$guid]);
        }
        RPCContext::getInstance()->sendMsg($arrMsgUid, 
                PushInterfaceDef::MINERAL_PIT_UPDATE, $pitInfo);
    }
    
    /**
     * 加一个接口提供占矿人的guildid
     */
    public function getGuildId()
    {
    	return $this->pitInfo[TblMineralField::GUILDID];
    }
    
    /**
     * 加一个接口提供这个矿的军团占矿历史信息
     */
    public function getGuildInfo($guildId,$pitId=0)
    {
    	if ($pitId!=0&&$this->pitId==$pitId)
    	{
    		$this->refreshGuildInfo();
    	}
    	if (!isset($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$guildId]))
    	{
    		return array();
    	}
    	return $this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$guildId];
    }
    
    /**
     * 矿主变化时候，记录guildInfo
     * @param $newCapture int 新矿主
     */
    public function doWhenChangeOwner($newCapture)
    {
    	$arrUid = array($newCapture);
    	$arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'guild_id'));
    	
    	//guildId
    	$this->doWhenGiveUpOrDue();
    	$this->addGuildBeginTime($arrUserInfo[$newCapture]['guild_id']);
    	$this->pitInfo[TblMineralField::GUILDID]=$arrUserInfo[$newCapture]['guild_id'];
    }
    
    /**
     * 有人弃矿或者资源矿到期
     */
    public function doWhenGiveUpOrDue()
    {
    	 if (!empty($this->pitInfo[TblMineralField::GUILDID]))
    	 {
    	 	$captureGuildInfo=$this->getGuildInfo($this->pitInfo[TblMineralField::GUILDID]);
    	 	if (empty($captureGuildInfo))
    	 	{
    	 		return ;
    	 	}
    	 	end($captureGuildInfo);
    	 	$k=key($captureGuildInfo);
    	 	$this->addGuildEndTime($this->pitInfo[TblMineralField::GUILDID], $k);
    	 	$this->pitInfo[TblMineralField::GUILDID]=0;
    	 }
    }
    
    /**
     * 矿主换军团时候， 记录guildInfo
     * @param $newGuildId
     */
    public function doWhenChangeGuild($newGuildId)
    {
    	$this->doWhenGiveUpOrDue();
    	$this->addGuildBeginTime($newGuildId);
    
    	$this->pitInfo[TblMineralField::GUILDID] = $newGuildId;
    }
    
    /**
     * 设置某军团开始时间
     * @param unknown $guildId
     */
    public function addGuildBeginTime($guildId)
    {
    	 if(empty($guildId))
    	 {
    	 	return;
    	 }
    	 $this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$guildId][]=
    	 	array(MineralDef::GUILD_STARTTIME=>Util::getTime(),);
    }
    /**
     * 设置某军团某段时间的结束时间
     * @param $guildId int 军团id
     * @param $k int 时间段key
     * @throws InterException
     */
    public function addGuildEndTime($guildId, $k)
    {
    	if(empty($guildId))
    	{
    		return;
    	}
    	
    	if(empty($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO]
    			[$guildId][$k][MineralDef::GUILD_STARTTIME]))
    	{
    		throw new InterException("guild:%d k:%d no begin time %s", $guildId, $k,
    				$this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$guildId]);
    	}
    	
    	$this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO]
    	[$guildId][$k][MineralDef::GUILD_ENDTIME] = Util::getTime();
    }
    
    /**
     * 清除超时的数据，防止va过大
     */
    protected function refreshGuildInfo()
    {
    	$now=Util::getTime();
    	if (isset($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO]))
    	{
    		foreach ($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO] as $tmpGuildId=>$tmpGuildInfo)
    		{
    			/*if ($tmpGuildId==$guildId)
    			{
    				continue;
    			}*/
    			foreach ($tmpGuildInfo as $k=>$eachGuildTime)
    			{
    				//两天前的数据清掉
    				if (isset($eachGuildTime[MineralDef::GUILD_ENDTIME])
    						&&$eachGuildTime[MineralDef::GUILD_ENDTIME]<$now-SECONDS_OF_DAY * 2)
    				{
    					unset($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$tmpGuildId][$k]);
    					Logger::trace("guildEndTime 2 days unset :%s for guild:%d", $eachGuildTime, $tmpGuildId);
    				}
    				//防止出现非法情况，开始时间超过3天的也清掉
    				if (isset($eachGuildTime[MineralDef::GUILD_STARTTIME])
    	 					&&$eachGuildTime[MineralDef::GUILD_STARTTIME]<$now-SECONDS_OF_DAY * 3)
    				{
    					unset($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$tmpGuildId][$k]);
    					Logger::trace("guildStartTime 2 days unset :%s for guild:%d", $eachGuildTime, $tmpGuildId);
    				}
    			}
    			if (empty($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$tmpGuildId]))
    			{
    				unset($this->pitInfo[TblMineralField::VA_INFO][TblMineralField::GUILDINFO][$tmpGuildId]);
    			}
    		}
    	}
    
    }
    
   
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */