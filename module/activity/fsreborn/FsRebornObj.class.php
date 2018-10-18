<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FsRebornObj.class.php 200753 2015-09-28 06:20:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/fsreborn/FsRebornObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-28 06:20:54 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200753 $
 * @brief 
 *  
 **/
class FsRebornObj
{							
	private $uid = 0;
	private $info = NULL;							// 修改数据
	private $infoBak = NULL; 						// 原始数据
	private static $arrInstance = array();			// 单例数组
	
	/**
	 * 获取本类的实例
	 *
	 * @param int $uid
	 * @return FsRebornObj
	*/
	public static function getInstance($uid)
	{
		if (!isset(self::$arrInstance[$uid]))
		{
			self::$arrInstance[$uid] = new self($uid);
		}
		return self::$arrInstance[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrInstance = array();
		}
		else if (isset(self::$arrInstance[$uid]))
		{
			unset(self::$arrInstance[$uid]);
		}
	}
	
	public function __construct($uid)
	{
		if($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		$info = FsRebornDao::select($uid);
		if (empty($info))
		{
			$info = $this->init($uid);
		}
		$this->uid = $uid;
		$this->info = $info;
		$this->infoBak = $info;
		$this->refresh();
	}
	
	public function init($uid)
	{
		$arrField = array(
				FsRebornDef::FIELD_UID => $uid,
				FsRebornDef::FIELD_NUM => 0,
				FsRebornDef::FIELD_REFRESH_TIME => Util::getTime(),
		);
	
		return $arrField;
	}
	
	public function refresh()
	{
		$refreshTime = $this->getRefreshTime();
		if (!FsRebornLogic::isInCurRound($refreshTime))
		{
			$this->info = $this->init($this->uid);
		}
	}
	
	public function getNum()
	{
		return $this->info[FsRebornDef::FIELD_NUM];
	}
	
	public function addNum($num)
	{
		$this->info[FsRebornDef::FIELD_NUM] += $num;
	}
	
	public function getRefreshTime()
	{
		return $this->info[FsRebornDef::FIELD_REFRESH_TIME];
	}
	
	public function update()
	{
		if($this->uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $this->uid);
		}
		if ($this->info != $this->infoBak)
		{
			FsRebornDao::insertOrUpdate($this->info);
			$this->infoBak = $this->info;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */