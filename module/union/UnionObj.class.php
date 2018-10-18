<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UnionObj.class.php 241842 2016-05-10 07:38:27Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/UnionObj.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 07:38:27 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241842 $
 * @brief 
 *  
 **/
class UnionObj
{
	private $uid = 0;								// 用户id
	private $union = NULL;							// 修改数据
	private $unionBak = NULL; 						// 原始数据
	private static $arrUnion = array();				// 实例对象数组
	
	/**
	 * 获取本类的实例
	 *
	 * @param int $uid
	 * @return UnionObj
	*/
	public static function getInstance($uid)
	{
		if (!isset(self::$arrUnion[$uid]))
		{
			self::$arrUnion[$uid] = new self($uid);
		}
		return self::$arrUnion[$uid];
	}
	
	public static function release($uid)
	{
		if ($uid == 0)
		{
			self::$arrUnion= array();
		}
		else if (isset(self::$arrUnion[$uid]))
		{
			unset(self::$arrUnion[$uid]);
		}
	}
	
	private function __construct($uid)
	{
		if($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		// 如果在用户当前的线程中，就从session中取数据，否则从数据库取数据
		if ($uid == RPCContext::getInstance()->getUid())
		{
			$info = RPCContext::getInstance()->getSession(UnionDef::SESSION_KEY);
			if(empty($info))
			{
				$info = UnionDao::select($uid);
				if (empty($info))
				{
					$info = $this->init($uid);
				}
				RPCContext::getInstance()->setSession(UnionDef::SESSION_KEY, $info);
			}
		}
		else
		{
			$info = UnionDao::select($uid);
			if (empty($info)) 
			{
				$info = $this->init($uid);
			}
		}
		$this->uid = $uid;
		$this->union = $info;
		$this->unionBak = $info;
	}
	
	public function init($uid)
	{
		$arrField = array(
				UnionDef::FIELD_UID => $uid,
				UnionDef::FIELD_VA_FATE => array(),
				UnionDef::FIELD_VA_LOYAL => array(),
				UnionDef::FIELD_VA_MARTIAL => array(),
		);
	
		return $arrField;
	}
	
	public function getInfo()
	{
		return $this->union;
	}
	
	public function getFateLists()
	{
		if (!isset($this->union[UnionDef::FIELD_VA_FATE][UnionDef::LISTS]))
		{
			return array();
		}
		return $this->union[UnionDef::FIELD_VA_FATE][UnionDef::LISTS];
	}
	
	public function getFate($id)
	{
		if (!isset($this->union[UnionDef::FIELD_VA_FATE][UnionDef::LISTS][$id])) 
		{
			return array();
		}
		return $this->union[UnionDef::FIELD_VA_FATE][UnionDef::LISTS][$id];
	}
	
	public function addFate($id, $tid)
	{
		$this->union[UnionDef::FIELD_VA_FATE][UnionDef::LISTS][$id][] = $tid;
	}
	
	public function getLoyalLists()
	{
		if (!isset($this->union[UnionDef::FIELD_VA_LOYAL][UnionDef::LISTS]))
		{
			return array();
		}
		return $this->union[UnionDef::FIELD_VA_LOYAL][UnionDef::LISTS];
	}
	
	public function getLoyal($id)
	{
		if (!isset($this->union[UnionDef::FIELD_VA_LOYAL][UnionDef::LISTS][$id]))
		{
			return array();
		}
		return $this->union[UnionDef::FIELD_VA_LOYAL][UnionDef::LISTS][$id];
	}
	
	public function addLoyal($id, $tid)
	{
		$this->union[UnionDef::FIELD_VA_LOYAL][UnionDef::LISTS][$id][] = $tid;
	}
	
	public function getMartialLists()
	{
		if (!isset($this->union[UnionDef::FIELD_VA_MARTIAL][UnionDef::LISTS]))
		{
			return array();
		}
		return $this->union[UnionDef::FIELD_VA_MARTIAL][UnionDef::LISTS];
	}
	
	public function getMartial($id)
	{
		if (!isset($this->union[UnionDef::FIELD_VA_MARTIAL][UnionDef::LISTS][$id]))
		{
			return array();
		}
		return $this->union[UnionDef::FIELD_VA_MARTIAL][UnionDef::LISTS][$id];
	}
	
	public function addMartial($id, $tid)
	{
		$this->union[UnionDef::FIELD_VA_MARTIAL][UnionDef::LISTS][$id][] = $tid;
	}
	
	public function update()
	{
		if($this->uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $this->uid);
		}
		
		if ($this->union != $this->unionBak)
		{
			UnionDao::insertOrUpdate($this->union);
			RPCContext::getInstance()->setSession(UnionDef::SESSION_KEY, $this->union);
			$this->unionBak = $this->union;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */