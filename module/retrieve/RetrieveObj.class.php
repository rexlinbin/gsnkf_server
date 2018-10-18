<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RetrieveObj.class.php 257926 2016-08-23 09:15:28Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/RetrieveObj.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-23 09:15:28 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257926 $
 * @brief 
 *  
 **/
 
class RetrieveObj
{
	private static $sArrInstance = array();
	private $mObj = array();
	private $mObjModify = array();

	/**
	 * getInstance 获取用户实例
	 *
	 * @param int $uid 用户id
	 * @static
	 * @access public
	 * @return RetrieveObj
	*/
	public static function getInstance($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}

		if (!isset(self::$sArrInstance[$uid]))
		{
			self::$sArrInstance[$uid] = new self($uid);
		}

		return self::$sArrInstance[$uid];
	}

	public static function releaseInstance($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
			{
				throw new FakeException('uid and global.uid are 0');
			}
		}

		if (isset(self::$sArrInstance[$uid]))
		{
			unset(self::$sArrInstance[$uid]);
		}
	}

	private function __construct($uid)
	{
		$this->mObj = $this->getInfo($uid);
		if (empty($this->mObj))
		{
			$this->mObj = $this->createInfo($uid);
		}

		$this->mObjModify = $this->mObj;
	}
	
	public function getInfo($uid)
	{
		$arrCond = array(array(RetrieveDef::TBL_FIELD_UID, '=', $uid));
		$arrBody = RetrieveDef::$RETRIEVE_ALL_FIELDS;
	
		return RetrieveDao::select($arrCond, $arrBody);
	}
	
	public function createInfo($uid)
	{
		$arrRet = array 
		(
				RetrieveDef::TBL_FIELD_UID => $uid,
				RetrieveDef::TBL_FIELD_VA_EXTRA => array(),
		);
		RetrieveDao::insert($arrRet);
	
		return $arrRet;
	}
	
	public function getUid()
	{
		return $this->mObjModify[RetrieveDef::TBL_FIELD_UID];
	}
	
	public function getRetrieveTime($retrieveType)
	{
		if (isset($this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][$retrieveType])) 
		{
			return $this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][$retrieveType];
		}
		
		return 0;
	}
	
	public function canRetrieve($retrieveType, $beforeEndTime)
	{
		return $this->getRetrieveTime($retrieveType) < $beforeEndTime;
	}
	
	public function updateRetrieveTime($retrieveType)
	{
		$this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][$retrieveType] = Util::getTime();
	}
	
	public function getSupplyInfo()
	{
	    if ( !isset( $this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][RetrieveDef::SUPPLY] ) )
	    {
	        $this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][RetrieveDef::SUPPLY] = array();
	    }
	    
	    return $this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][RetrieveDef::SUPPLY];
	}
	
	public function setSupplyInfo($info)
	{
	    $this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][RetrieveDef::SUPPLY] = $info;
	}
	
	public function update()
	{
		$arrField = array();
		foreach ($this->mObj as $key => $value)
		{
			if ($this->mObjModify[$key] != $value)
			{
				$arrField[$key] = $this->mObjModify[$key];
			}
		}
			
		if (empty($arrField))
		{
			Logger::debug('no change');
			return;
		}
	
		Logger::debug("update RetrieveObj uid:%d, changed field:%s", $this->getUid(), $arrField);
	
		$arrCond = array(array(RetrieveDef::TBL_FIELD_UID, '=', $this->getUid()));
		RetrieveDao::update($arrCond, $arrField);
	
		$this->mObj = $this->mObjModify;
	}
	
	public function setRetrieveTimeForConsole($retrieveType, $time)
	{
		$this->mObjModify[RetrieveDef::TBL_FIELD_VA_EXTRA][$retrieveType] = $time;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */