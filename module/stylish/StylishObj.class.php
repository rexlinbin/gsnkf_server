<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StylishObj.class.php 242914 2016-05-16 08:08:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/StylishObj.class.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-16 08:08:39 +0000 (Mon, 16 May 2016) $
 * @version $Revision: 242914 $
 * @brief 
 *  
 **/
class StylishObj
{
	private $uid = NULL;
	private $data = NULL;
	private $dataModify = NULL;
	
	private static $_instance = array();
	
	private function __construct($uid = 0)
	{
		if (empty($uid))
		{
			$uid = RPCContext::getInstance()->getUid();
			if (empty($uid))
			{
				throw new FakeException('uid in session is null.');
			}
		}
		$this->uid = $uid;
		$this->data = $this->dataModify = StylishDao::select($this->uid);
		if (empty($this->dataModify)) 
		{
			$this->init();
		}
		$this->refresh();
	}
	
	public static function getInstance($uid = 0)
	{
		if(!isset(self::$_instance[$uid]))
		{
			$Instance = new self($uid);
			self::$_instance[$uid] = $Instance;
			return $Instance;
		}
	
		return self::$_instance[$uid];
	}
	
	public static function release($uid)
	{
		if (isset(self::$_instance[$uid]))
		{
			unset(self::$_instance[$uid]);
		}
	}
	
	public function init()
	{
		$this->dataModify = array(
				StylishDef::FIELD_UID => $this->uid,
				StylishDef::FIELD_VA_TITLE => array(),
		);
	}
	
	public function refresh()
	{
		$user = EnUser::getUserObj($this->uid);
		$titleConf = btstore_get()->TITLE;
		foreach ($this->getTitle() as $id => $info)
		{
			//限时激活的称号，到达截止时间需要取消激活，如果装备在用户上需要卸下
			if (!empty($titleConf[$id][StylishDef::TITLE_LAST_TIME])) 
			{
				$deadline = $info[StylishDef::TIME] + $info[StylishDef::NUM] * $titleConf[$id][StylishDef::TITLE_LAST_TIME];
				if ($deadline <= Util::getTime()) 
				{
					$this->delTitle($id);
					if ($user->getTitle() == $id) 
					{
						if ($this->uid == RPCContext::getInstance()->getUid()) 
						{
							$user->setTitle(0);
							$user->update();
						}
						$user->modifyBattleData();
					}
				}
			}
		}
	}
	
	public function update()
	{
		if($this->uid != RPCContext::getInstance()->getUid())
		{
			Logger::info('Not in the uid:%d session', $this->uid);
		}
		
		if ($this->data != $this->dataModify) 
		{
			if (empty($this->data)) 
			{
				StylishDao::insert($this->dataModify);
			}
			else 
			{
				$arrField = array();
				foreach ($this->dataModify as $key => $value)
				{
					if ($this->data[$key] != $value) 
					{
						$arrField[$key] = $value;
					}
				}
				StylishDao::update($this->uid, $arrField);
			}
			$this->data = $this->dataModify;
		}
	}
	
	public function getTitleInfo()
	{
		$ret = array();
		$titleConf = btstore_get()->TITLE;
		//永久激活的截止时间为0.限时激活的截止时间根据开始时间+持续时间*激活次数计算
		foreach ($this->getTitle() as $id => $info)
		{
			if (empty($titleConf[$id][StylishDef::TITLE_LAST_TIME])) 
			{
				$ret[$id] = 0;
			}
			else 
			{
				$ret[$id] = $info[StylishDef::TIME] + $info[StylishDef::NUM] * $titleConf[$id][StylishDef::TITLE_LAST_TIME];
			}
		}
		return $ret;
	}
	
	public function getActiveTitle()
	{
		$ret = array();
		//目前在激活状态的称号
		foreach ($this->getTitle() as $id => $info)
		{
			if (!empty($info[StylishDef::NUM])) 
			{
				$ret[] = $id;
			}
		}
		return $ret;
	}
	
	public function getTitle()
	{
		if (!isset($this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE])) 
		{
			return array();
		}
		return $this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE];
	}
	
	public function addTitle($id, $num = 1)
	{
		//激活次数累加
		if (!isset($this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id])) 
		{
			$this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id][StylishDef::NUM] = 0;
		}
		if (empty($this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id][StylishDef::NUM])) 
		{
			$this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id][StylishDef::TIME] = Util::getTime();
		}
		$this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id][StylishDef::NUM] += $num;
	}
	
	public function delTitle($id)
	{
		if (isset($this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id])) 
		{
			$this->dataModify[StylishDef::FIELD_VA_TITLE][StylishDef::TITLE][$id][StylishDef::NUM] = 0;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */