<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StylishLogic.class.php 243855 2016-05-24 02:29:08Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/StylishLogic.class.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-24 02:29:08 +0000 (Tue, 24 May 2016) $
 * @version $Revision: 243855 $
 * @brief 
 *  
 **/
class StylishLogic
{
	public static function getStylishInfo($uid)
	{
		$stylishObj = StylishObj::getInstance($uid);
		
		$ret = array(
				StylishDef::TITLE => $stylishObj->getTitleInfo(),
		);
		
		Logger::trace('getStylishInfo:%s', $ret);
		
		return $ret;
	}
	
	public static function activeTitle($uid, $id, $itemId, $itemNum)
	{
		Logger::trace('StylishLogic activeTitle, id:%d, itemId:%d', $id, $itemId);
	
		$titleConf = btstore_get()->TITLE;
		if (empty($titleConf[$id]))
		{
			throw new ConfigException('title id:%d is not exist', $id);
		}
	
		//必须使用道具激活
		$itemTplId = $titleConf[$id][StylishDef::TITLE_COST_ITEM];
		if (empty($itemTplId))
		{
			throw new FakeException('title can not active without item');
		}
		
		//必须使用称号对应的道具
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item == NULL || $item->getItemTemplateID() != $itemTplId) 
		{
			throw new FakeException('itemId:%d is not itemTplId:%d', $itemId, $itemTplId);
		}
		
		//永久型称号不能重复激活
		$stylishObj = StylishObj::getInstance($uid);
		if (empty($titleConf[$id][StylishDef::TITLE_LAST_TIME])
		&& in_array($id, $stylishObj->getTitleInfo())) 
		{
			throw new FakeException('title can not active again');
		}
	
		//扣除物品
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->decreaseItem($itemId, $itemNum))
		{
			throw new FakeException('no enough itemTplId:%d', $itemTplId);
		}
	
		$stylishObj->addTitle($id, $itemNum);
	
		$bag->update();
		$stylishObj->update();
		EnUser::getUserObj($uid)->modifyBattleData();
		Logger::info('StylishLogic::activeTitle uid:%d id:%d success.', $uid, $id);
		
		return 'ok';
	}
	
	public static function setTitle($uid, $id)
	{
		$titleConf = btstore_get()->TITLE;
		if (empty($titleConf[$id]))
		{
			throw new ConfigException('title id:%d is not exist', $id);
		}
		
		//目前在激活状态中的称号
		$stylishObj = StylishObj::getInstance($uid);
		if (!in_array($id, $stylishObj->getActiveTitle()))
		{
			throw new FakeException('title id:%d is not active yet', $id);
		}
		
		$user = EnUser::getUserObj($uid);
		$user->setTitle($id);
		$user->update();
		$user->modifyBattleData();
		Logger::info('StylishLogic::setTitle uid:%d id:%d success.', $uid, $id);
		
		return 'ok';
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */