<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyACopy.class.php 245319 2016-06-02 11:39:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/MyACopy.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-06-02 11:39:26 +0000 (Thu, 02 Jun 2016) $
 * @version $Revision: 245319 $
 * @brief 
 *  
 **/
class MyACopy
{
    private $buffer    =    array();
	//list of activity_info
	private $copyList = array();
	private static $uid;
	private static $_instance = NULL;
	/**
	 * 
	 * @var ACopyObj
	 */
	private $copyObj = NULL;

	//session中的活动列表key是act.actlist
	public function __construct($uid)
	{
	    self::$uid = $uid;
	    $copyList = array();
	    if(self::$uid == RPCContext::getInstance()->getUid())
	    {
	        $copyList = RPCContext::getInstance()->getSession(CopySessionName::ACOPYLIST);
	    }
		if(empty($copyList))
		{
			if (empty(self::$uid))
			{
				throw new FakeException('Can not get copy info from session!');
			}
			$copyList = ACopyDAO::getActivityCopyList(self::$uid);
			if(self::$uid == RPCContext::getInstance()->getUid())
			{
			    RPCContext::getInstance()->setSession(CopySessionName::ACOPYLIST,$copyList);
			}
		}		
		$this->copyList = $copyList;
		$this->buffer    =    $copyList;
	}
	public function getActivityCopyList()
	{
	    $this->checkOpenNewCopy();
	    $this->refreshDefeatNum();
		$ret = array();
		if(!empty($this->copyList))
		{
			$ret = $this->copyList;
		}
		return $ret;
	}
	/**
	 * @return MyACopy
	 */
	public static function getInstance($uid = 0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
		if (self::$_instance instanceof self && (self::$uid == $uid))
		{
		    return self::$_instance;
		}
		self::$_instance = new self($uid);
		return self::$_instance;
	}
	
	public static function release()
	{
	    if(self::$_instance != NULL)
	    {
	        self::$_instance = NULL;
	    }
	}

	public function checkOpenNewCopy()
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::ACTCOPY))
	    {
	        $conf = btstore_get()->ACTIVITYCOPY;
	        foreach($conf as $copyId => $conf)
	        {
	            if(isset($this->copyList[$copyId]))
	            {
	                continue;
	            }
	            $this->addNewACopy($copyId);
	        }
	    }
	}
	
// 	public function checkOpenNewCopy()
// 	{
// 	    $arrCopyConf = btstore_get()->ACTIVITYCOPY->toArray();
// 	    $acopyList = $this->copyList;
// 	    $level = EnUser::getUserObj()->getLevel();
// 	    $newCopies = array();
// 	    foreach($arrCopyConf as $copyId => $conf)
// 	    {
// 	        if(isset($acopyList[$copyId]))
// 	        {
// 	            continue;
// 	        }
// 	        $canOpen = TRUE;
// 	        $needNCopy = $conf['pre_pass_ncopy'];
// 	        $needLevel = $conf['need_level'];
// 	        if(!empty($needNCopy) && (MyNCopy::getInstance()->isCopyPassed($needNCopy)))
// 	        {
// 	            $canOpen = FALSE;
// 	        }
// 	        if(!empty($needLevel) && ($needLevel > $level))
// 	        {
// 	            $canOpen = FALSE;
// 	        }
// 	        if($canOpen)
// 	        {
// 	            $copyInfo = $this->addNewACopy($copyId);
// 	            if(!empty($copyInfo))
// 	            {
// 	                $newCopies[$copyId] = $copyInfo;
// 	            }
// 	        }
// 	    }
// 	    return $newCopies;
// 	}
	
	public function refreshDefeatNum()
	{
	    if(empty($this->copyList))
	    {
	        return;
	    }
	    foreach($this->copyList as $copyId => $copyInfo)
	    {
	        $copyObj = $this->getActivityCopyObj($copyId);
	        $this->copyList[$copyId] = $copyObj->getCopyInfo();
	    }
	}
	
	public function checkOpenByNCopyPass($ncopyId)
	{
	    $arrAcopy = btstore_get()->COPY[$ncopyId]['pass_open_actcopy']->toArray();
	    if(empty($arrAcopy))
	    {
	        return array();
	    }
	    $ret = array();
	    foreach($arrAcopy as $index => $acopy)
	    {
	        $acopyInfo = $this->addNewACopy($acopy);
	        if(!empty($acopyInfo))
	        {
	            $ret[$acopy] = $acopyInfo;
	        }
	    }
	    return $ret;
	}
	
	public function getActivityCopyInfo($copyId)
	{
		$ret = array();
		if(isset($this->copyList[$copyId]))
		{
			$ret = $this->copyList[$copyId];
		}
		else
		{
			$ret = ACopyDAO::getActivityCopyInfo(self::$uid, $copyId);
			if(!empty($ret))
			{
				$this->copyList[$copyId] = $ret;
			}
		}
		return $ret;
	}
	/**
	 * 获取活动对象
	 * @param int $act_id
	 * @return ACopyObj
	 */
	public function getActivityCopyObj($copyId)
	{
	    if($this->copyObj != NULL && ($this->copyObj->getCopyId() == $copyId))
	    {
	        return $this->copyObj;
	    }
	    if($this->copyObj != NULL)
	    {
	        $this->copyList[$this->copyObj->getCopyId()] = $this->copyObj->getCopyInfo();
	    }
		//如果不存在此活动  返回null
		if(!isset($this->copyList[$copyId]))
		{
			Logger::warning('activity copylist %s has no such copyObj with copyid %s.',$this->copyList,$copyId);
			return NULL;
		}
		$activityObj = new ACopyObj(self::$uid,$copyId,$this->copyList[$copyId]);
		//获取活动类别
		$act_type = $activityObj->getType();
		//获取活动的具体信息
		$act_info = $this->copyList[$copyId];
		//根据不同的活动类别初始化活动对象
		switch($act_type)
		{
				//摇钱树活动对象
			case ACT_COPY_TYPE::GOLDTREE:
			    $activityObj = new GoldTree(self::$uid, $copyId, $act_info);
				break;
			//经验宝物对象
			case ACT_COPY_TYPE::EXPTREASURE:
			    $activityObj = new ExpTreasure(self::$uid, $copyId, $act_info);
			    break;
				//是男人就守关活动对象
			case ACT_COPY_TYPE::EXPHERO:
			    $activityObj = new ExpHero(self::$uid, $copyId, $act_info);
				break;
			case ACT_COPY_TYPE::EXPUSER:
				$activityObj = new ExpUser(self::$uid, $copyId,$act_info);
				break;
			case ACT_COPY_TYPE::DESTINY:
			    $activityObj = new ADestiny(self::$uid, $copyId, $act_info);
			    break;
		}
		$this->copyObj = $activityObj;
		return $activityObj;
	}
	public static function getTypeofActivityCopy($copyId)
	{
		if(isset(btstore_get()->ACTIVITYCOPY[$copyId]))
		{
			return intval(btstore_get()->ACTIVITYCOPY[$copyId]['type']);
		}
		return -1;
	}
	
	public function saveCopy($copyId)
	{
        if(isset($this->buffer[$copyId]) && ($this->buffer[$copyId] == $this->copyList[$copyId]))
        {
            return;
        }
	    ACopyDAO::saveActivityCopy(self::$uid, $copyId, $this->copyList[$copyId]);
	    $this->buffer[$copyId] = $this->copyList[$copyId];
	    //更新到session中的copy.actlist
	    if(self::$uid == RPCContext::getInstance()->getUid())
	    {
		   RPCContext::getInstance()->setSession(CopySessionName::ACOPYLIST,$this->copyList);
	    }
	}
	
	public function save()
	{
	    if($this->copyObj != NULL)
	    {
	        $this->copyList[$this->copyObj->getCopyId()]    =    $this->copyObj->getCopyInfo();
	    }
	    if($this->buffer === $this->copyList)
	    {
	        return false;
	    }
	    foreach($this->copyList as $copyId => $copyInfo)
	    {
	        if(isset($this->buffer[$copyId]) && ($this->buffer[$copyId] === $copyInfo))
	        {
	            continue;
	        }
	        $this->saveCopy($copyId);
	    }
	}
	
	public function addNewACopy($copyId)
	{
	    //获取活动副本信息
	    $actCopyInfo = $this->getActivityCopyInfo($copyId);
	    //如果不存在此副本  才添加
	    if(empty($actCopyInfo))
	    {
	        $actCopyInfo = array(
	                'uid'=>self::$uid,
	                'copy_id'=>$copyId,
	                'last_defeat_time'=>0,
	                'can_defeat_num'=>0,
	                'buy_atk_num' => 0,
	                'va_copy_info'=>array(),
	                'status'=>DataDef::NORMAL
	        );
	        $acopyObj = new ACopyObj(self::$uid, $copyId, $actCopyInfo);
	        $actCopyInfo = $acopyObj->getCopyInfo();
            $this->copyList[$copyId] = $actCopyInfo;
	        return $actCopyInfo;
	    }
	    return array();
	}
	
	public static function saveByOtherModule()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    if(self::$_instance == NULL || (self::$uid != $uid))
	    {
	        Logger::trace('no instance or no instance of current user.');
	        return;
	    }
	    self::getInstance()->save();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */