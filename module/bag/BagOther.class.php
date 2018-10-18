<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BagOther.class.php 113039 2014-06-09 07:14:10Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/BagOther.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2014-06-09 07:14:10 +0000 (Mon, 09 Jun 2014) $
 * @version $Revision: 113039 $
 * @brief
 *
 **/

class BagOther
{
	private $mUid;

	private $mArrItemId = array();

	private $mArrItemIdInTmp = array();

	public function BagOther($uid)
	{
		$this->mUid = $uid;
	}

	/**
	 *
	 * 增加物品
	 *
	 * @param array(int) $item_ids					物品IDS
	 * @param boolean $in_tmp_bag					是否放入临时背包
	 *
	 * @return boolean								TRUE表示成功
	 *
	 */
	public function addItems($item_ids, $in_tmp_bag = FALSE)
	{
		foreach ( $item_ids as $item_id )
		{
			$this->addItem($item_id, $in_tmp_bag);
		}
		return TRUE;
	}

	/**
	 *
	 * 增加物品
	 *
	 * @param int $item_id							物品IDS
	 * @param boolean $in_tmp_bag					是否放入临时背包
	 *
	 * @return boolean								TRUE表示成功
	 */
	public function addItem($item_id, $in_tmp_bag = FALSE )
	{
		if ( in_array($item_id, $this->mArrItemId ) )
		{
			Logger::FATAL('already add item_id:%d', $item_id);
			return FALSE;
		}
		else
		{
			if ( $in_tmp_bag == FALSE )
			{
				$this->mArrItemId[] = $item_id;
			}
			else
			{
				$this->mArrItemIdInTmp[] = $item_id;
			}
		}
		return TRUE;
	}
	
	
	/**
	 *
	 * 更新别人的背包数据
	 *
	 * @return NULL
	 */
	public function update()
	{
		if ( empty($this->mArrItemId) && empty($this->mArrItemIdInTmp) )
		{
			return;
		}
		ItemManager::getInstance()->update();
		RPCContext::getInstance()->executeTask(
			$this->mUid,
			'user.addItemsOtherUser',
			array($this->mUid, $this->mArrItemId, $this->mArrItemIdInTmp),
			FALSE
		);
		Logger::INFO("user.addItemsOtherUser uid:%d item_id:%s item_id_in_tmp:%s",
			$this->mUid, $this->mArrItemId, $this->mArrItemIdInTmp);
		$this->mArrItemId = $this->mArrItemIdInTmp = array();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */