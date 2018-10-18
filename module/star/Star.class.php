<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Star.class.php 164385 2015-03-31 03:30:13Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/Star.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-03-31 03:30:13 +0000 (Tue, 31 Mar 2015) $
 * @version $Revision: 164385 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : Star
 * Description : 名将系统对外接口实现类
 * Inherit     : IStar
 **********************************************************************************************************************/

class Star implements IStar
{
	/**
	 * 用户id
	 * @var $uid
	 */
	private $uid;
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::getAllStarInfo()
	 */
	public function getAllStarInfo()
	{
		Logger::trace('Star::getAllStarInfo Start.');
		
		$ret = StarLogic::getAllStarInfo($this->uid);
		$ret['athena'] = EnAthena::getSkillList($this->uid);
		
		Logger::trace('Star::getAllStarInfo End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::addFavorByGift()
	 */
	public function addFavorByGift($sid, $giftTid, $giftNum)
	{
		Logger::trace('Star::addFavorByGift Start.');

		if($sid <= 0 || $giftTid <= 0 || $giftNum <= 0 || $giftNum <= 0)
		{
			throw new FakeException('Err para, star_id:%d gift_tid:%d gift_num:%d!', $sid, $giftTid, $giftNum);
		}

		$ret = StarLogic::addFavorByGift($this->uid, $sid, $giftTid, $giftNum);
		
		Logger::trace('Star::addFavorByGift End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::addFavorByAllGifts()
	 */
	public function addFavorByAllGifts($sid)
	{
		Logger::trace('Star::addFavorByAllGifts Start.');

		if($sid <= 0)
		{
			throw new FakeException('Err para, star_id:%d!', $sid);
		}
		
		$ret = StarLogic::addFavorByAllGifts($this->uid, $sid);
		
		Logger::trace('Star::addFavorByAllGifts End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::addFavorByGold()
	 */
	public function addFavorByGold($sid)
	{
		Logger::trace('Star::addFavorByGold Start.');

		if($sid <= 0)
		{
			throw new FakeException('Err para, star_id:%d!', $sid);
		}

		$ret = StarLogic::addFavorByGold($this->uid, $sid);
		
		Logger::trace('Star::addFavorByGold End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::addFavorByAct()
	 */
	public function addFavorByAct($sid, $actId)
	{
		Logger::trace('Star::addFavorByAct Start.');

		if($sid <= 0 || $actId <= 0)
		{
			throw new FakeException('Err para, star_id:%d act_id:%d!', $sid, $actId);
		}
		
		$ret = StarLogic::addFavorByAct($this->uid, $sid, $actId);
		
		Logger::trace('Star::addFavorByAct End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::answer()
	 */
	public function answer($sid, $trigerId, $optionId)
	{
		Logger::trace('Star::answer Start.');

		if($sid <= 0 || $trigerId <= 0 || $optionId <= 0)
		{
			throw new FakeException('Err para, sid:%d trigerId:%d optionId:%d', $sid, $trigerId, $optionId);
		}
		
		$ret = StarLogic::answer($this->uid, $sid, $trigerId, $optionId);
		
		Logger::trace('Star::answer End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::swap()
	 */
	public function swap($sida, $sidb)
	{
		Logger::trace('Star::swap Start.');

		if($sida <= 0 || $sidb <= 0)
		{
			throw new FakeException('Err para, sida:%d sidb:%d', $sida, $sidb);
		}
		
		$ret = StarLogic::swap($this->uid, $sida, $sidb);
		
		Logger::trace('Star::swap End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::draw()
	 */
	public function draw($sid)
	{
		Logger::trace('Star::draw Start.');
		
		if($sid <= 0)
		{
			throw new FakeException('Err para, sid:%d', $sid);
		}
		
		$ret = StarLogic::draw($this->uid, $sid);
		
		Logger::trace('Star::draw End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::shuffle()
	 */
	public function shuffle($sid)
	{
		Logger::trace('Star::shuffle Start.');
		
		if($sid <= 0)
		{
			throw new FakeException('Err para, sid:%d', $sid);
		}
		
		$ret = StarLogic::shuffle($this->uid, $sid);
		
		Logger::trace('Star::shuffle End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::getReward()
	 */
	public function getReward($sid)
	{
		Logger::trace('Star::getReward Start.');
		
		if($sid <= 0)
		{
			throw new FakeException('Err para, sid:%d', $sid);
		}
		
		$ret = StarLogic::getReward($this->uid, $sid);
		
		Logger::trace('Star::getReward End.');
		
		return $ret;
	}

	/**
	 * (non-PHPdoc)
	 * @see IStar::changeSkill()
	 */
	public function changeSkill($sid)
	{
		Logger::trace('Star::changeSkill Start.');
		
		if($sid < 0)
		{
			throw new FakeException('Err para, sid:%d', $sid);
		}
		
		$ret = StarLogic::changeSkill($this->uid, $sid);
		
		Logger::trace('Star::changeSkill End.');
		
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IStar::quickDraw()
	 */
	public function quickDraw($sid)
	{
		Logger::trace('Star::quickDraw Start.');
		
		if($sid <= 0)
		{
			throw new FakeException('Err para, sid:%d', $sid);
		}
		
		$ret = StarLogic::quickDraw($this->uid, $sid);
		
		Logger::trace('Star::quickDraw End.');
		
		return $ret;
	}
} 
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */