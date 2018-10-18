<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyECopy.class.php 181604 2015-06-30 07:46:25Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/MyECopy.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-06-30 07:46:25 +0000 (Tue, 30 Jun 2015) $
 * @version $Revision: 181604 $
 * @brief 
 *  
 **/
/**
 * 精英副本各个状态的开启
 * 1.可显示：普通副本通关或者前一个精英副本开启可攻击状态
 * 2.可攻击：普通副本通关并且前一个精英副本通关
 */
class  MyECopy
{
    private $buffer    =    array();
	private $ecopyInfo = array();
	private static $uid;
	private static $instance = NULL;

	/**
	 * 更新精英副本的last_defeat_time 和  defeat_num
	 */
	private function __construct($uid)
	{
	    self::$uid = $uid;
	    $ecopyInfo = array();
	    if(self::$uid == RPCContext::getInstance()->getUid())
	    {
		    $ecopyInfo = RPCContext::getInstance()->getSession(CopySessionName::ECOPYLIST);
	    }
		if(empty($ecopyInfo))
		{
			if (empty(self::$uid))
			{
				throw new FakeException('Can not get copy info from session!');
			}
			$ecopyInfo = ECopyDAO::getEliteCopyInfo(self::$uid);
			if(self::$uid == RPCContext::getInstance()->getUid())
			{
			    RPCContext::getInstance()->setSession(CopySessionName::ECOPYLIST, $ecopyInfo);
			}
		}	
		$this->buffer    = $ecopyInfo;
		$this->ecopyInfo = $ecopyInfo;
		$this->refreshDefeatNum();
	}
	/**
	 *
	 * @return MyECopy
	 */
	public static function getInstance($uid = 0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
		if (self::$instance instanceof self && ($uid == self::$uid))
		{
		    return self::$instance;
		}
		self::$instance = new self($uid);
		return self::$instance;
	}

	public static function release()
	{
	    if (self::$instance != null)
	    {
	        self::$instance = null;
	    }
	}
	
	public function refreshDefeatNum()
	{
	    if(empty($this->ecopyInfo))
	    {
	        return;
	    }
		// 如果上次攻击的时间是今天之前
		if (!Util::isSameDay($this->ecopyInfo['last_defeat_time']))
		{

			$this->ecopyInfo['last_defeat_time']	= Util::getTime();
			$this->ecopyInfo['can_defeat_num']		= CopyConf::$CHALLANGE_TIMES;
			$this->ecopyInfo['buy_atk_num'] = 0;
		}
		return $this->ecopyInfo['can_defeat_num'];
	}

	public function openAnyCopy()
	{
	    if(empty($this->ecopyInfo))
	    {
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public function getEliteCopyInfo()
	{
	    $this->checkOpenNewCopy();
		return $this->ecopyInfo;
	}
	
	public function addBuyAtkNum($num)
	{
	    $this->ecopyInfo['buy_atk_num'] += $num;
	}
	
	public function getBuyAtkNum()
	{
	    return $this->ecopyInfo['buy_atk_num'];
	}
	
	private function getInitEcopyInfo()
	{
	    $ecopyInfo = array(
	            'uid'=>self::$uid,
	            'last_defeat_time'=>Util::getTime(),
	            'can_defeat_num'=>CopyConf::$CHALLANGE_TIMES,
	            'buy_atk_num' => 0,
	            'va_copy_info'=>array('progress'=>array()),
	            'status'=>DataDef::NORMAL
	        );
	    return $ecopyInfo;
	}
	
	public function checkOpenNewCopy()
	{
	    if(self::$uid != RPCContext::getInstance()->getUid())
	    {
	        return;
	    }
	    if(empty($this->ecopyInfo) || (!isset($this->ecopyInfo['va_copy_info']['progress'])))
	    {
	        $this->ecopyInfo = $this->getInitEcopyInfo();
	    }
	    $arrCopyConf = btstore_get()->ELITECOPY;
	    foreach($arrCopyConf as $copyId => $copyConf)
	    {
	        if(empty($copyId))
	        {
	            continue;
	        }
	        $copyStatus = $this->getStatusofCopy($copyId);
	        if($copyStatus == EliteCopyStatus::PASS || 
	                ($copyStatus == EliteCopyStatus::CANATTACK))
	        {
	            continue;
	        }
	        else if($copyStatus == EliteCopyStatus::CANSHOW)
	        {
	            $needNCopy = intval(btstore_get()->ELITECOPY[$copyId]['pre_open_copy']);
	            if(!empty($needNCopy))
	            {
	                if(MyNCopy::getInstance()->isCopyPassed($needNCopy) == FALSE)
	                {
	                    continue;
	                }
	            }
	            $preEcopy = intval(btstore_get()->ELITECOPY[$copyId]['pre_copy']);
	            if(empty($preEcopy) || ($this->getStatusofCopy($preEcopy) == EliteCopyStatus::PASS))
	            {
	                $this->setCopyStatus($copyId, EliteCopyStatus::CANATTACK);
	            }
	        }
	        else if($copyStatus == EliteCopyStatus::NOTOPEN)
	        {
	            $needNCopy = intval(btstore_get()->ELITECOPY[$copyId]['pre_open_copy']);
	            $nCopyPassed = TRUE;
	            if(!empty($needNCopy))
	            {
	                if(MyNCopy::getInstance()->isCopyPassed($needNCopy) == FALSE)
	                {
	                    $nCopyPassed = FALSE;
	                }
	            }
	            if($nCopyPassed)
	            {
	                $this->setCopyStatus($copyId, EliteCopyStatus::CANSHOW);
	            }
	            $preEcopy = intval(btstore_get()->ELITECOPY[$copyId]['pre_copy']);
	            if(!empty($preEcopy))
                {
                    if($this->getStatusofCopy($preEcopy) >= EliteCopyStatus::CANATTACK)
                    {
                        $this->setCopyStatus($copyId, EliteCopyStatus::CANSHOW);
                    }
                    if($this->getStatusofCopy($preEcopy) == EliteCopyStatus::PASS && ($nCopyPassed))
                    {
                        $this->setCopyStatus($copyId, EliteCopyStatus::CANATTACK);
                    }
                }
	        }
	    }
	}
	
	public function checkOpenByNCopyPass($ncopyId)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::ELITECOPY) == FALSE)
	    {
	        return array();
	    }
	    $arrEcopy = btstore_get()->COPY[$ncopyId]['pass_open_elite']->toArray();
	    if(empty($arrEcopy))
	    {
	        return array();
	    }
	    $ecopyInfo = $this->ecopyInfo;
	    foreach($arrEcopy as $index => $ecopyId)
	    {
	        $this->addNewEliteCopy($ecopyId);
	    }
	    if($ecopyInfo != $this->ecopyInfo)
	    {
	        return $this->ecopyInfo;
	    }
	    return array();
	}
		
	public function isCopyOpen($copyId)
	{
	    $progress = $this->ecopyInfo['va_copy_info']['progress'];
	    if(isset($progress[$copyId]))
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	public function setCopyStatus($copyId,$copyStatus)
	{		
		$progress = $this->ecopyInfo['va_copy_info']['progress'];
		if($copyId == CopyConf::$FIRST_ELITE_COPY_ID && ($copyStatus == EliteCopyStatus::CANSHOW))
		{
		    $this->ecopyInfo['va_copy_info']['progress'][$copyId] = EliteCopyStatus::CANATTACK;
		    return TRUE;
		}
		//如果DB中存在这个精英副本  才开启攻击状态   否则不开启
		if(!isset($progress[$copyId]) || 
		        (isset($progress[$copyId]) && ($progress[$copyId] < $copyStatus)))
		{
			$this->ecopyInfo['va_copy_info']['progress'][$copyId] = $copyStatus;
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * 成功通关，去掉一次挑战次数
	 * @return int 当前可以通过此事
	 */
	public function subCanDefeatNum($num=1)
	{
	    Logger::trace('ecopyinfo %s.',$this->ecopyInfo);
		if(intval($this->ecopyInfo['can_defeat_num']) < $num)
		{
			return FALSE;
		}
		$this->ecopyInfo['can_defeat_num'] -= $num;
		$this->ecopyInfo['last_defeat_time'] = Util::getTime();
		return TRUE;
	}
	
	public function addCanDefeatNum($num)
	{
	    $this->ecopyInfo['can_defeat_num'] += $num;
	}
	
	/**
	 * 通关了副本copy_id   返回开启或者更新的副本id以及状态
	 * @param int $copyId
	 */
	public function passCopy($copyId)
	{
		$progress = $this->ecopyInfo['va_copy_info']['progress'];
		$this->setCopyStatus($copyId, EliteCopyStatus::PASS);
		$this->checkOpenNewCopy();
		if($this->ecopyInfo['va_copy_info']['progress'] != $progress)
		{
		    return $this->ecopyInfo['va_copy_info']['progress'];
		}
		return array();
	}
	public function save()
	{
	    if($this->ecopyInfo != $this->buffer)
	    {
	        ECopyDAO::save(self::$uid, $this->ecopyInfo);
	        $this->buffer    =    $this->ecopyInfo;
	        if(self::$uid == RPCContext::getInstance()->getUid())
	        {
	            RPCContext::getInstance()->setSession(CopySessionName::ECOPYLIST, $this->ecopyInfo);
	        }
	    }
	}

	public function getCanDefeatNum()
	{
	    $this->refreshDefeatNum();
	    return $this->ecopyInfo['can_defeat_num'];
	}
	
	public function canDefeat($copyId)
	{
		//是否有攻击次数
		$this->refreshDefeatNum();
		if($this->ecopyInfo['can_defeat_num'] < 1)
		{
			return 'no_defeat_num';
		}
		if($this->getStatusofCopy($copyId) <= EliteCopyStatus::CANSHOW)
		{
			return 'not_open';
		}
		$needPower = intval(btstore_get()->ELITECOPY[$copyId]['need_power']);
		if(Enuser::getUserObj()->getCurExecution() < $needPower)
		{
			return 'not_enough_execution';
		}
		return 'ok';
	}
	
	public function getStatusofCopy($copyId)
	{
	    if(empty($this->ecopyInfo))
	    {
	        return EliteCopyStatus::NOTOPEN;
	    }
		$progress = $this->ecopyInfo['va_copy_info']['progress'];
		if(!isset($progress[$copyId]))
		{
		    return EliteCopyStatus::NOTOPEN;
		}
		return $progress[$copyId];
	}
	
	
	public function addNewEliteCopy($copyId)
	{
	    if(empty($this->ecopyInfo))
	    {
	        $this->ecopyInfo = $this->getInitEcopyInfo();
	    }
	    //设置精英副本的状态
	    $this->setCopyStatus($copyId, EliteCopyStatus::CANSHOW);
	    //设置前置精英副本的状态
	    $preEcopy = btstore_get()->ELITECOPY[$copyId]['pre_copy'];
	    if(!empty($preEcopy) &&
	            (isset($this->ecopyInfo['va_copy_info']['progress'][$preEcopy])))
	    {
	        if($this->getStatusofCopy($preEcopy) == EliteCopyStatus::PASS)
	        {
	            $this->setCopyStatus($copyId, EliteCopyStatus::CANATTACK);
	        }
	    }
	    if($this->getStatusofCopy($copyId) >= EliteCopyStatus::CANATTACK)
	    {
	        $nextEcopy = btstore_get()->ELITECOPY[$copyId]['pass_open_next'];
	        if(!empty($nextEcopy))
	        {
	            $this->setCopyStatus($nextEcopy, EliteCopyStatus::CANSHOW);
	        }
	    }
	    return $this->ecopyInfo;
	}
	
	public static function saveByOtherModule()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    if(self::$instance == NULL || (self::$uid != $uid))
	    {
	        Logger::trace('no instance or no instance of current user.');
	        return;
	    }
	    self::getInstance()->save();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */