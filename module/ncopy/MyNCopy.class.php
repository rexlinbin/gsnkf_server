<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyNCopy.class.php 110914 2014-05-26 06:02:25Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/MyNCopy.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-05-26 06:02:25 +0000 (Mon, 26 May 2014) $
 * @version $Revision: 110914 $
 * @brief 
 *  
 **/
//注意MyNCopy的数据成员CopyObj 的使用
//它保存的是当前用户的当前副本（在session中的copyid标识）的副本信息
//如果获取非当前副本的copyObj，并且修改了，调用MyNCopy的save函数式没有用的，
//需要 调用setCopyInfo($copyId,$copyInfo)然后再save
class MyNCopy
{
    /**
     * list of copyinfo
     * @var ArrayObject
     */
    private $buffer    =    array();
	/**
	 * list of copyinfo
	 * @var ArrayObject
	 */
	private $copyList	= array();
	
	private $scoreBuffer = 0;
	private $score = 0;
	/**
	 * 当前玩家的uid
	 * @var int
	 */
	private static $uid		=	0;
	/**
	 * 当前副本对象
	 * @var NCopyObj
	 */
	private $copyObj	= NULL;
	/**
	 * 
	 * @var MyNCopy
	 */
	private static $_instance = NULL;
	/**
	 * 从数据库取出多少副本的数据   只有getCopyList接口调用时  此值才设置为true
	 * @var boolean
	 */
	public static $fetchAllCopyFromDB = FALSE;
	
	private function __construct($uid)
	{
	    self::$uid = $uid;
	    $score = 0;
	    $copyList = array();
	    if(self::$uid == RPCContext::getInstance()->getUid())
	    {
	        $copyList 	= RPCContext::getInstance()->getSession(CopySessionName::COPYLIST);
	        $score = RPCContext::getInstance()->getSession(CopySessionName::NCOPYSCORE);
	    }
		//当session中的copylist是空的  或者 想从数据库中取出所有的副本信息时    去DB中拉数据
		if(empty($copyList) || empty($score) ||
		        (self::$fetchAllCopyFromDB && (count($copyList) > CopyConf::$COPY_NUM_IN_SESSION)))
		{
			if (empty(self::$uid))
			{
				throw new FakeException('Can not get copy info from session!');
			}
			$copyList = NCopyDAO::getAllCopies(self::$uid);
			if(empty($copyList))
			{
			    $this->addFirstCopy();
			}
			$score = $this->computeScore($copyList);
			RPCContext::getInstance()->setSession(CopySessionName::NCOPYSCORE, $score);
			//只保存最远的几个copyinfo到session中  其他只保存copyid
			if(self::$uid == RPCContext::getInstance()->getUid())
			{
			    $copyListInSession = $copyList;
			    $num	=	0;
			    foreach($copyList as $copyId=>$copyInfo)
			    {
			        $num	=	$num+1;
			        if($num <= CopyConf::$COPY_NUM_IN_SESSION)
			        {
			            continue;
			        }
			        $copyListInSession[$copyId] = array('copy_id'=>$copyId);
			    }
			    RPCContext::getInstance()->setSession(CopySessionName::COPYLIST, $copyListInSession);
			}
		}
		$this->buffer    =    $copyList;
		$this->copyList = $copyList;	
		$this->score = $score;
		$this->scoreBuffer = $score;
	}


	private function computeScore($copyList)
	{
	    $score = 0;
	    foreach($copyList as $copyId => $copyInfo)
	    {
	        $score += $copyInfo['score'];
	    }
	    return $score;
	}
	
	public function getScore()
	{
	    if(empty($this->score) && (count($this->copyList) > 1))
	    {
	        throw new InterException('the open copy num is larger than 1,but score is 0.');
	    }
	    return $this->score;
	}
	
	public function addScore($num)
	{
	    if($num <= 0)
	    {
	        Logger::fatal('addscore num is %d.',$num);
	        return $this->score;
	    }
	    $this->score += $num;
	    EnAchieve::updateNCopyScore(self::$uid, $this->score);
	    return $this->score;
	}
	
	/**
	 * 获取本类唯一实例
	 *
	 * @return MyNCopy
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

	/**
	 * 毁掉单例，单元测试对应
	 */
	public static function release()
	{
		if (self::$_instance != null)
		{
			self::$_instance = null;
		}
	}
	/**
	 * 获取所有的普通副本信息
	 * @return array
	 * {
	 * 	copyid=>copyinfo
	 * }
	 */
	public function getAllCopies()
	{
	    $this->checkOpenNewCopy();
	    $this->refreshDefeatNum();
		return $this->copyList;
	}

	public function refreshDefeatNum()
	{
	    foreach($this->copyList as $copyId => $copyInfo)
	    {
	        if(count($copyInfo) <= 1)
	        {
	            continue;
	        }
	        $refreshTime = $copyInfo[NORMAL_COPY_FIELD::REFRESH_ATKNUM_TIME];
	        if(!Util::isSameDay($refreshTime))
	        {
	            $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM] = array();
	            $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM] = array();
	            $copyInfo[NORMAL_COPY_FIELD::REFRESH_ATKNUM_TIME] = util::getTime();
	        }
	        $this->copyList[$copyId] = $copyInfo;
	    }
	}
	
	/**
	 * 获取某个副本的信息
	 * @param int $copyId
	 */
	public function getCopyInfo($copyId)
	{
		if(!isset($this->copyList[$copyId]))
		{
			return array();
		}
		$copyInfo = $this->copyList[$copyId];		
		if(count($copyInfo) > 1)
		{			
			$copyInfo = $this->copyList[$copyId];
		}
		else
		{
		    $copyInfo = NCopyDAO::getCopy(self::$uid, $copyId);
		}
		return $copyInfo;
	}
	
	public function checkOpenNewCopy()
	{
	    if(empty($this->copyList))
	    {
	        $this->addFirstCopy();
	        return;
	    }
	    if(self::$uid != RPCContext::getInstance()->getUid())
	    {
	        return;
	    }
	    //将副本列表安装副本id倒序排序
	    ksort($this->copyList,SORT_NUMERIC);
	    //根据当前所有副本的通关或者进度状态判断是否有需要开启的副本或者据点
	    foreach($this->copyList as $copyId => $copyInfo)
	    {
	        $this->checkOpenNewCopyByCopy($copyId);
	    }
	}
	
	/**
	 * 某个副本通关之后 判断是否有新的副本或者据点开启
	 * @param int $copyId
	 * @return
	 */
	private function checkOpenNewCopyByCopy($copyId)
	{
	    if(!isset($this->copyList[$copyId]))
	    {
	        return;
	    }
	    $copyPassed = FALSE;
	    if(count($this->copyList[$copyId]) <= 1)
	    {
	        $copyPassed = TRUE;
	    }
	    else
	    {
	        $copyObj = $this->getCopyObj($copyId);
	    }
	    $arrBase = btstore_get()->COPY[$copyId]['base'];
	    foreach($arrBase as $index => $baseId)
	    {
	        if(empty($baseId))
	        {
	            continue;
	        }
	        if($copyPassed || ($copyObj->getStatusofBase($baseId) >= BaseStatus::SIMPLEPASS))
	        {
	            $this->checkOpenByBasePass($baseId);
	        }
	    }
	}
	
	/**
	 * ncopy.doBattle之后使用
	 * @param int $baseId
	 * @return array
	 * [
	 *     copyId=>array
	 *         [
	 *             copyId:int
	 *             uid:int
	 *             va_copy_info:array
	 *         ]
	 * ]
	 *
	 */
	public function checkOpenByBasePass($baseId)
	{
	    $newShowBase = btstore_get ()->BASE [$baseId] ['pass_show_base'];
	    $newAtkBase = btstore_get ()->BASE [$baseId] ['pass_open_base'];
	    $arrNewBase = array();
	    $ret = array();
	    foreach($newShowBase as $index => $showBase)
	    {
	        $arrNewBase[$showBase] = BaseStatus::CANSHOW;
	    }
	    foreach($newAtkBase as $index => $atkBase)
	    {
	        $arrNewBase[$atkBase] = BaseStatus::CANATTACK;
	    }
	    if(CopyUtil::isLastBaseOfCopy($baseId) == TRUE)
	    {
	        $newCopyConf = btstore_get()->BASE[$baseId]['pass_open_copy']->toArray();
	        if(!empty($newCopyConf))
	        {
	            $newCopy = $newCopyConf[0];
	            $newCopyFirstBase = CopyUtil::getFirstBaseOfCopy($newCopy);
	            $arrNewBase[$newCopyFirstBase] = BaseStatus::CANATTACK;
	        }
	    }
	    foreach($arrNewBase as $newBase => $baseStatus)
	    {
	        if(empty($newBase))
	        {
	            continue;
	        }
	        $copyId = btstore_get()->BASE[$newBase]['copyid'];
	        //此副本通关了，避免去数据库取数据
	        if(isset($this->copyList[$copyId]) && (count($this->copyList[$copyId]) == 1))
	        {
	            continue;
	        }
	        //无此副本，添加新副本
	        $copyInfo = array();
	        if(!isset($this->copyList[$copyId]))
	        {
	            $level = EnUser::getUserObj(self::$uid)->getLevel();
	            if($level >= btstore_get()->COPY[$copyId]['level_open'])
	            {
	                $va_copy_info['progress'] = array($newBase=>$baseStatus);
	                $copyObj = $this->createNewObj(self::$uid, $copyId,$va_copy_info);
	                $copyInfo = $copyObj->getCopyInfo();
	                NCopyLogic::openNewCopy($copyId);
	            }
	            else
	            {
	                Logger::trace('copy open need level %d.now is %d.',btstore_get()->COPY[$copyId]['level_open'],$level);
	            }
	        }
	        else
	        {
	            $copyObj = $this->getCopyObj($copyId);
	            if($copyObj->updBaseStatus($newBase, $baseStatus) == TRUE)
	            {
	                $copyInfo = $copyObj->getCopyInfo();
	            }
	        }
	        if(!empty($copyInfo))
	        {
	            $ret[$copyId] = $copyInfo;
	            $this->setCopyInfo($copyId, $copyInfo);
	        }
	    }
	    Logger::trace('checkOpenByBasePass baseId %s ret %s.',$baseId,$ret);
	    return $ret;
	}
	
	
	private function addFirstCopy()
	{
		$copyId = CopyConf::$FIRST_NORMAL_COPY_ID;
		$firstBase = btstore_get()->COPY[$copyId]['base'][0];
		$copyObj = self::createNewObj(self::$uid,$copyId ,
		        array('progress'=>array($firstBase=>1)));
		$copyInfo = $copyObj->getCopyInfo();
		$this->saveCopy($copyId, $copyInfo);
		NCopyLogic::initUserCopy($copyId);
		unset($copyInfo['status']);		
		$this->copyList[$copyId] = $copyInfo;
	}
	
	/**
	 * 添加或者更新副本   
	 * @param int $copyId
	 * @param array $info
	 * {
	 * 	'copyid'=>copyid
	 * 	'base'=>array
	 * 			{
	 * 				baseid=>base_status
	 * 			}
	 * }
	 */


	/**
	 * 保存某个副本的信息至数据库
	 * 如果副本信息与buffer中的不同保存到数据库,更新副本信息到session中
	 * 并没有保存到$this->copyList中，在一次请求中会处理多个副本的情况下（如getCopyList会更新copyList，其他请求没有必要更新）
	 * @param int $copyId
	 */
	public function saveCopy($copyId,$copyInfo)
	{	
	    if(count($copyInfo) <= 1)
	    {
	        throw new FakeException('update copy with copyInfo %s.',$copyInfo);
	    }
	    if(self::$uid != RPCContext::getInstance()->getUid())
	    {
	        Logger::warning('how to tigger saving copy for other user.please check!!');
	        return;
	    }
		$ret = NCopyDAO::saveCopy($copyInfo);
		//更新到session中的copy.copylist     如果copyid不在copylist中   不保存到session中
		$copyList = RPCContext::getInstance()->getSession(CopySessionName::COPYLIST);
		if(!isset($copyList[$copyId]) || (count($copyList[$copyId]) > 1))
		{
			$copyList[$copyId] = $copyInfo;
			$this->copyList[$copyId] = $copyInfo;
			RPCContext::getInstance()->setSession(CopySessionName::COPYLIST, $copyList);
		}
		//更新到buffer中
		if(!isset($this->buffer[$copyId]))
		{
		    RPCContext::getInstance()->sendMsg(array(self::$uid),
		    					            PushInterfaceDef::NCOPY_ADD_NEW_COPY, array($copyInfo));
		}
		$this->buffer[$copyId]    =    $copyInfo;
		return $ret;
	}
	
	public function save()
	{
	    if($this->copyObj != NULL)
	    {
	        $this->copyList[$this->copyObj->getCopyId()]    =    $this->copyObj->getCopyInfo();
	    }
	    if($this->score > $this->scoreBuffer
	            && (self::$uid == RPCContext::getInstance()->getUid()))
	    {
	        RPCContext::getInstance()->setSession(CopySessionName::NCOPYSCORE, $this->score);
	        $this->scoreBuffer == $this->score;
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
	        $this->saveCopy($copyId,$copyInfo);
	    }
	}
	
	/**
	 *	获取普通副本对象
	 * @param int $copyId
	 * @return NormalCopyObj|NULL
	 */
	public function getCopyObj($copyId)
	{
	    if($this->copyObj != NULL)
	    {
	        if($this->copyObj->getCopyId() == $copyId)
	        {
	            return $this->copyObj;
	        }
	    }
		$copyInfo = $this->getCopyInfo($copyId);
		if(!empty($copyInfo))
		{
			$copyObj = new NCopyObj(self::$uid, $copyId,$copyInfo);
			//当前用户的当前副本
			if($copyId == RPCContext::getInstance()->getSession(CopySessionName::COPYID) && 
			        (self::$uid == RPCContext::getInstance()->getUid()))
			{
			    $this->copyObj = $copyObj;
			}
			return $copyObj;
		}
		return NULL;
	}
	
	public function setCopyInfo($copyId,$copyInfo)
	{
	    if(empty($copyInfo))
	    {
	        return;
	    }
	    $this->copyList[$copyId] = $copyInfo;
	}

	public static function createNewObj($uid, $copy_id, $va_copy_info)
	{
	    $va_copy_info[NORMAL_COPY_FIELD::VA_DEFEAT_NUM] = array();
	    $va_copy_info[NORMAL_COPY_FIELD::VA_RESET_NUM] = array();
	    $copyinfo = array (
	            NORMAL_COPY_FIELD::UID => $uid,
	            NORMAL_COPY_FIELD::COPYID => $copy_id,
	            NORMAL_COPY_FIELD::SCORE => 0,
	            NORMAL_COPY_FIELD::PRIZEDNUM => 0,
	            NORMAL_COPY_FIELD::REFRESH_ATKNUM_TIME => Util::getTime(),
	            NORMAL_COPY_FIELD::VA_COPY_INFO => $va_copy_info,
	            'status' => Datadef::NORMAL );
	    $copyObj = new NCopyObj ( $uid, $copy_id, $copyinfo );
	    return $copyObj;
	}
	
	public function isCopyPassed($copyId)
	{
	    if(!isset($this->copyList[$copyId]))
	    {
	        return FALSE;
	    }
	    if(count($this->copyList[$copyId]) <= 1)
	    {
	        return TRUE;
	    }
	    $copyObj = $this->getCopyObj($copyId);
	    if($copyObj->isCopyPassed())
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */