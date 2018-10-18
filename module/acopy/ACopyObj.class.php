<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ACopyObj.class.php 197890 2015-09-10 09:47:03Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ACopyObj.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-09-10 09:47:03 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197890 $
 * @brief 
 *  
 **/
class ACopyObj
{
	protected $copyInfo = array();
	protected $copyId;
	protected $type = 1;

	public function __construct($uid,$copyId,$copyInfo=array())
	{
		if(empty($copyInfo))
		{
			$copyInfo = ACopyDAO::getActivityCopyInfo($uid, $copyId);
		}
		if(empty($copyInfo))
		{
		    throw new FakeException('copyid %d copyinfo is null',$copyId);
		}
		$this->copyInfo = $copyInfo;
		$this->copyId = $this->copyInfo['copy_id'];
		$this->type = btstore_get()->ACTIVITYCOPY[$copyId]['type'];
		$this->refreshDefeatNum();
	}

	public function getAllDefeatNum()
	{
	    $conf = btstore_get()->ACTIVITYCOPY[$this->copyId]->toArray();
	    if(!empty($conf['pass_num']))
	    {
	        return $conf['pass_num'];
	    }   
	    if(!empty($conf['attack_num']))
	    {
	        return $conf['attack_num'];
	    }  
	}
	/**
	 * 判断此活动当前是否开启
	 */
	public function isOpen()
	{
		return $this->isDuringActivity(Util::getTime());
	}

	/**
	 * 更新副本的可攻击次数
	 */
	public function refreshDefeatNum()
	{
// 	    if($this->isOpen() == FALSE)
// 	    {
// 	        $this->copyInfo['can_defeat_num'] = 0;
// 	        return $this->copyInfo['can_defeat_num'];
// 	    }
	    $last_defeat_time = intval($this->copyInfo['last_defeat_time']);
	    if (!Util::isSameDay($last_defeat_time))
	    {
	        $this->copyInfo['last_defeat_time']	= Util::getTime();
	        $this->copyInfo['can_defeat_num'] = $this->getAllDefeatNum();
	        $this->copyInfo['buy_atk_num'] = 0;
	        //福利活动相关的代码    下面四行
	        $copyId = $this->getCopyId();
	        $wealConf = EnWeal::getWeal(WealDef::ACOPY_NUM);
	        $normalWealRate = 1;
	        if (isset($wealConf[$copyId]))
	        {
	            $normalWealRate = $wealConf[$copyId];
	        }
	        if ($normalWealRate < 1 || ($normalWealRate > 10))
	        {
	            Logger::warning('wealconf for acopy_num is %s',$wealConf);
	            return $this->copyInfo['can_defeat_num'];
	        }
	        $mergeServerWealRate = 1;
	        if ($copyId == ACT_COPY_TYPE::GOLDTREE_COPYID)
	        {
	            $mergeServerWealRate = EnMergeServer::getGoldTreeRewardRate();
	        }
	        else if ($copyId == ACT_COPY_TYPE::EXPTREAS_COPYID)
	        {
	            $mergeServerWealRate = EnMergeServer::getExpTreasureRewardRate();
	        }
	        if ($mergeServerWealRate < 1 || ($mergeServerWealRate > 10))
	        {
	            Logger::warning('mergeserver wealconf for acopy_num is %s',$wealConf);
	            return $this->copyInfo['can_defeat_num'];
	        }
	        $this->copyInfo['can_defeat_num'] *= max($mergeServerWealRate,$normalWealRate);
	    }
	    return $this->copyInfo['can_defeat_num'];
	}
	
	public function getBuyAtkNum()
	{
	    return $this->copyInfo['buy_atk_num'];
	}
	
	public function addBuyAtkNum($num)
	{
	    $this->copyInfo['buy_atk_num'] += $num;
	}
	/**
	 * 攻击一次   减少一次可攻击次数
	 */
	public function subCanDefeatNum()
	{
		Logger::trace('subDefeatNum,old can_defeat_num %s.',$this->copyInfo['can_defeat_num']);
		if($this->copyInfo['can_defeat_num'] < 1)
		{
			return FALSE;
		}
		$this->copyInfo['can_defeat_num'] = intval($this->copyInfo['can_defeat_num']) - 1;
		$this->copyInfo['last_defeat_time'] = Util::getTime();
		Logger::trace('subDefeatNum,new can_defeat_num %s.',$this->copyInfo['can_defeat_num']);
		return TRUE;
	}
	
	public function addCanDefeatNum($num=1)
	{
	    $this->copyInfo['can_defeat_num']+=$num;
	}
	
	public function getCanDefeatNum()
	{
	    return $this->copyInfo['can_defeat_num'];
	}
	/**
	 * 判断某个时间戳是否在活动时间内
	 * @param int $time
	 */
	public function isDuringActivity($timestamp)
	{
	    $openTime = btstore_get()->ACTIVITYCOPY[$this->copyId]['open_time']->toArray();
	    if(empty($openTime))
	    {
	        return TRUE;
	    }
	    $week = date('w',$timestamp);
	    if(!isset($openTime[$week]))
	    {
	        return FALSE;
	    }
	    $date = date ( "Ymd ", $timestamp );
	    $startTime = strtotime($date.$openTime[$week]['start_time']);
	    $endTime = strtotime($date.$openTime[$week]['end_time']);
	    if($timestamp >= $startTime && ($timestamp <= $endTime))
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	/**
	 * 是否能够进入活动
	 * 1.是否在活动时间内
	 * 2.是否还有攻击次数（通关次数）
	 */
	public function canEnterAct()
	{
		//当前活动没有开启
		if($this->isOpen() == false)
		{
			return 'act_no_open';
		}
		if($this->getCanDefeatNum() < 1)
		{
		    return 'no_defeat_num';
		}
		$copyId = $this->getCopyId();
		$needPower = intval(btstore_get()->ACTIVITYCOPY[$copyId]['attack_need_power']);
		if(Enuser::getUserObj()->getCurExecution() < $needPower)
		{
		    return 'no_enough_execution';
		}
        $needLv = btstore_get()->ACTIVITYCOPY[$this->copyId]['need_level'];
        $user = EnUser::getUserObj($this->copyInfo['uid']);
        if($user->getLevel() < $needLv)
        {
            return 'no_enough_level';
        }
		return 'ok';
	}
	
	public function getType()
	{
		return $this->type;
	}
	
	public function getCopyId()
	{
	    return $this->copyId;
	}
	public function getCopyInfo()
	{
	    return $this->copyInfo;
	}
	
	public static function doneBattle($atkRet)
	{
	    $copyId = AtkInfo::getInstance()->getCopyId();
	    $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    $ret = array();
	    if($actObj->getType() == ACT_COPY_TYPE::GOLDTREE)
	    {
	        EnActive::addTask(ActiveDef::ACOPY);
	        EnWeal::addKaPoints(KaDef::ACOPY);
	        $uid = RPCContext::getInstance()->getUid();
	        EnMission::doMission($uid, MissionType::ACOPY);
	        $team2 = $atkRet['team2'];
	        $costHp = 0;
	        $maxHp = 0;
	        if(!isset($team2[0]))
	        {
	            throw new InterException('return of dohero is wrong!');
	        }
	        foreach($team2 as $index => $mstInfo)
	        {
	            $costHp += $mstInfo['costHp'];
	            $maxHp += ($mstInfo['hp'] + $mstInfo['costHp']);
	        }
	        $addExp = max(1, intval($costHp/$maxHp*100));
	        Logger::trace('atkGoldTree addExp %d',$addExp);
	        $actObj->addExp($addExp);
	        $ret['add_exp'] = $addExp;
	    }
	    MyACopy::getInstance()->save();
	    EnUser::getUserObj()->update();
	    AtkInfo::getInstance()->saveAtkInfo();
	    return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */