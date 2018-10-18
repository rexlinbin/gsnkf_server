<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimpleBag.class.php 154113 2015-01-21 07:32:35Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/SimpleBag.class.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2015-01-21 07:32:35 +0000 (Wed, 21 Jan 2015) $
 * @version $Revision: 154113 $
 * @brief 
 *  
 **/


class SimpleBag
{
	private $mUid;
	
	private $mName;
	
	/**
	 * 背包中的数据
	 *  gid => itemId
	 * @var array
	 */
	private $mArrGrid;

	/**
	 * 背包中的原始数据
	 * @var array
	 */
	private $mOrgData;
	
	/**
	 * 被修改的数据
	 * @var array
	 */
	private $mArrModify = array();
	
	/**
	 * 最大格子数。
	 * 对于限制格子数的背包，此数等于数据库中对应的行数。
	 * 对于不限制格子数的背包，此数等于系统最大上限BagDef::MAX_GRID
	 * @var int
	 */
	private $mMaxGridNum;

	/**
	 * 本背包的起始格子ID
	 * @var int
	 */
	private $mStartGrid;

	/**
	 * 物品管理器对象
	 * @var ItemManager
	 */
	private $mManager;
	
	/**
	 * 是否执行出错
	 * @var boolean
	 */
	private $error = false;

	public function __construct($uid, $name, $arrGrid, $start, $maxGridNum )
	{
		$this->mUid = $uid;
		$this->mName = $name;
		$this->mOrgData = $arrGrid;
		$this->mArrGrid = $this->mOrgData;
		$this->mStartGrid = $start;
		$this->mMaxGridNum = $maxGridNum;		
		$this->mManager = ItemManager::getInstance();
	}

	public function getBagInfo()
	{
		$returnData = array();
		$arrItemId = array();
		foreach ($this->mArrGrid as $gid => $itemId)
		{
			if ( $itemId != BagDef::ITEM_ID_NO_ITEM )
			{
				if (in_array($itemId, $arrItemId)) 
				{
					$this->setGrid($gid, BagDef::ITEM_ID_NO_ITEM);
					Logger::FATAL('fixed duplicated item! user:%d has duplicated item:%d in gid:%d!',
					$this->mUid, $itemId, $gid);
				}
				else 
				{
					$ret = $this->getGridInfo($gid);
					if(!empty($ret))
					{
						$returnData[$gid] = $ret;
					}
					$arrItemId[] = $itemId;
				}
			}
		}
		
		if( count($this->mArrGrid) > BagDef::WARNING_GRID_NUM )
		{
			Logger::fatal('bag:%s too large. num:%d', $this->mName, count($this->mArrGrid) );
		}

		return $returnData;
	}
	
	public function getMaxGridNum()
	{
		return $this->mMaxGridNum;
	}

	public function getItemIdByGid($gid)
	{
		if( isset( $this->mArrGrid[$gid] ) )
		{
			return $this->mArrGrid[$gid];
		}
		return BagDef::ITEM_ID_NO_ITEM ;
	}
	
	public function getArrGridInfo($arrGrid)
	{
		$returnData = array();
		foreach ( $arrGrid as $gid )
		{
			$returnData[$gid] = $this->getGridInfo($gid);
		}
		return $returnData;
	}
	
	public function getGridInfo($gid)
	{
		$gid = intval($gid);

		if ( !isset($this->mArrGrid[$gid]) 
				|| $this->mArrGrid[$gid] == BagDef::ITEM_ID_NO_ITEM )
		{
			return array();
		}
	
		$itemId = $this->mArrGrid[$gid];
		$item = $this->mManager->getItem($itemId);

		if ( $item === NULL )
		{
			$this->setGrid($gid, BagDef::ITEM_ID_NO_ITEM);
			Logger::FATAL('fixed invalid item! user:%d has invalid item:%d in gid:%d!',
				 $this->mUid, $itemId, $gid);
			return array();
		}
		return $item->itemInfo();	
	}
	
	public function getGidByItemId($itemId)
	{
		foreach ( $this->mArrGrid as $gid => $value )
		{
			if ( $itemId == $value )
			{
				return $gid;
			}
		}
		return BagDef::INVALID_GRID_ID;
	}
	
	public function getItemNumByTemplateId($itemTplId)
	{
		$this->prepareItem();
				
		$num = 0;	
		foreach( $this->mArrGrid as $gid => $itemId )
		{
			if ( $itemId !== BagDef::ITEM_ID_NO_ITEM )
			{
				$item = $this->mManager->getItem($itemId);
				if ( $item !== NULL && $item->getItemTemplateID() == $itemTplId )
				{
					$num += $item->getItemNum();
				}
			}
		}
		return $num;
	}
	
	public function getArrGridByTemplateId($itemTplId)
	{
		$this->prepareItem();
		
		$arrItem = array();
		foreach( $this->mArrGrid as $gid => $itemId )
		{
			if ( $itemId !== BagDef::ITEM_ID_NO_ITEM )
			{
				$item = $this->mManager->getItem($itemId);
				if ( $item !== NULL && $item->getItemTemplateID() == $itemTplId )
				{
					$arrItem[$itemId] = $item->getItemNum();
				}
			}
		}
		asort($arrItem);
		$arrGrid = array();
		foreach ($arrItem as $itemId => $itemNum)
		{
			$gid = self::getGidByItemId($itemId);
			$arrGrid[$gid] = $itemId;
		}
		return $arrGrid;
	}

	public function getArrItemIdByItemType($itemType)
	{
		$this->prepareItem();
		
		$arrItemId = array();
		foreach  ( $this->mArrGrid as $gid => $itemId )
		{
			if ( $itemId !== BagDef::ITEM_ID_NO_ITEM )
			{
				$item = $this->mManager->getItem($itemId);
				if ( $item !== NULL && $item->getItemType() == $itemType )
				{
					$arrItemId[] = $itemId;
				}
			}
		}
		return $arrItemId;
	}
	
	public function getAllData()
	{
		return $this->mArrGrid;
	}
	
	public function isFull()
	{
		if ($this->mMaxGridNum == BagDef::MAX_GRID) 
		{
			return false;
		}
		foreach($this->mArrGrid as $gid => $itemId)
		{
			if( $itemId == ItemDef::ITEM_ID_NO_ITEM )
			{
				return false;
			}
		}
		return true;
	}
	
	/**
	 *
	 * 初始化背包数据
	 */
	public function initBag( $num )
	{
		if( $this->mMaxGridNum >= BagDef::MAX_GRID )
		{
			Logger::warning('bag:%s has no grid limit. no need to init', $this->mName);
			return;
		}			
	
		/**
		 修改背包格子初始化方式 wuqilin 20140219
		 之前通过setGrid接口初始化背包，在update时会插入对应的初始化数据。
		 修改：当不插入物品时，不再插入初始化数据
		 */
		$start = $this->mStartGrid;
		$end = $this->mStartGrid + $num;
		for ( $i = $start; $i < $end; $i++ )
		{
			if( !isset( $this->mArrGrid[$i] ) )
			{
				$this->mArrGrid[$i] = BagDef::ITEM_ID_NO_ITEM;
				$this->mOrgData[$i] = BagDef::ITEM_ID_NO_ITEM;
			}
		}
	
		$this->mMaxGridNum = count($this->mArrGrid);
		Logger::trace('bag:%s max num is %d after init.', $this->mName, $this->mMaxGridNum);			
	}
	
	public function openGrid($gridNum)
	{
		if($this->mMaxGridNum + $gridNum > BagDef::MAX_GRID)
		{
			throw new InterException('fixed me! invlida grid num. bag:%s. cur:%d, add:%d', $this->mName, $this->mMaxGridNum, $gridNum);
		}

		Logger::trace('bag:%s open grid num:%d. cur:%d', $this->mName, $gridNum, $this->mMaxGridNum );
		
		$start = $this->mStartGrid + $this->mMaxGridNum;
		$end = $start + $gridNum;
		for ( $i = $start; $i < $end; $i++ )
		{
			$this->setGrid($i, BagDef::ITEM_ID_NO_ITEM);
		}		
		$this->mMaxGridNum = count($this->mArrGrid);
		
		Logger::trace('cur grid num:%d', $this->mMaxGridNum);
	}
	
	public function removeItemByGid($gid)
	{
		if ( ! isset($this->mArrGrid[$gid]) )
		{
			Logger::fatal('no found gid:%d in bag:%s of uid:%d', $gid, $this->mName, $this->mUid);
			return false;
		} 
		$this->setGrid($gid, BagDef::ITEM_ID_NO_ITEM);
		return true;
	}
	
	public function deleteItembyTemplateID( $itemTplId, $itemNum)
	{
		$this->prepareItem();
		
		if ( $this->getItemNumByTemplateId($itemTplId) < $itemNum )
		{
			Logger::trace('no enough item of template:%d. itemNum:%d', $itemTplId, $itemNum);
			$this->error = true;
			return false;
		}
		
		$arrGrid = self::getArrGridByTemplateId($itemTplId);
		
		foreach( $arrGrid as $gid => $itemId )
		{
			if ( $itemNum <= 0 )
			{
				break;
			}
			if ( $itemId !== BagDef::ITEM_ID_NO_ITEM )
			{
				$item = $this->mManager->getItem($itemId);
				if ( $item !== NULL && $item->getItemTemplateID() == $itemTplId )
				{
					$num = $item->getItemNum();
					if ( $num > $itemNum )
					{
						$this->mManager->decreaseItem($itemId, $itemNum);						
						$itemNum = 0;
						$this->mArrModify[] = $gid;
					}
					else
					{
						$this->mManager->deleteItem($itemId);
						$this->removeItemByGid($gid);
						$itemNum -= $num;
					}
				}
			}
		}
		if ( $itemNum != 0 )
		{
			Logger::fatal('remove item template id:%d, num:%d failed. uid:%d, bag:%s', $itemTplId, $itemNum, $this->mUid, $this->mName);
			$this->error = true;
			return false;
		}
		
		Logger::trace('remove item template id:%d, num:%d succeed', $itemTplId, $itemNum);
		return true;
	}
	
	public function addItem( $itemId )
	{
		Logger::trace('bag:%s add itemId:%d', $this->mName, $itemId);
		
		$item = $this->mManager->getItem($itemId);
		if ( $item === NULL )
		{
			Logger::warning('itemId:%d not exist', $itemId);
			return false;
		}
		$itemType = $item->getItemType();
				
		$toBagName = Bag::getBagNameByItemType($itemType);
		if( $this->mName != $toBagName && $this->mName != BagDef::BAG_TMP )  
		{			
			Logger::fatal('itemType:%d cant add to bag:%s', $itemType, $this->mName);
			return false;
		}
		
		$start = $this->mStartGrid;
		$end = $this->mStartGrid +  count($this->mArrGrid);
		
		//对于可叠加物品和不可叠加物品分开考虑
		if ( $item->getStackable() == ItemDef::ITEM_CAN_NOT_STACKABLE )
		{
			//先从已经打开的格子中找位置放
			for ( $i = $start; $i < $end; $i++ )
			{
				if ( !isset( $this->mArrGrid[$i] ) || $this->mArrGrid[$i] == BagDef::ITEM_ID_NO_ITEM  )
				{
					$this->setGrid($i, $itemId);
					return true;
				}
			}
			//然后看看能不能找个没开启对格子放新东西
			//对于限制格子数的背包（装备背包）是不可能进入这个分支的。
			if( $i <  $this->mStartGrid + $this->mMaxGridNum ) 
			{
				Logger::info('uid:%d, bag:%s, open grid:%d, add itemId:%d', 
									$this->mUid, $this->mName, $i, $itemId);
				$this->setGrid($i, $itemId);
				return true;
			}
		}
		else
		{
			$num = $item->getItemNum();
			$arrStack = array();
			$this->prepareItem();
			//优先往相同的物品上面叠加
			for( $i = $start; $i < $end; $i++ )
			{
				if ( isset( $this->mArrGrid[$i]) && $this->mArrGrid[$i] != BagDef::ITEM_ID_NO_ITEM )
				{
					$tmpItem = $this->mManager->getItem( $this->mArrGrid[$i] );
					if ( $tmpItem != NULL
							&& $tmpItem->getItemTemplateID() == $item->getItemTemplateID()
							&& $tmpItem->getItemNum() < $tmpItem->getStackable() )
					{
						$arrStack[] = $i;
						$num -= $item->getStackable() - $tmpItem->getItemNum();														
					}
				}
				if ( $num <= 0 )
				{
					break;
				}
			}
			//如果没有完全叠加，就找个地方放
			if ( $num > 0 )
			{
				//先找位置放，找到位置才能合并，改数量。 如果找不到位置，怎什么也没干
				$pos = -1;
				for ( $i = $start; $i < $end; $i++ )
				{
					if ( !isset( $this->mArrGrid[$i] ) || $this->mArrGrid[$i] == BagDef::ITEM_ID_NO_ITEM  )
					{						
						$pos = $i;
						break;
					}
				}
				if(  ($pos < 0) && ( $i <  $this->mStartGrid + $this->mMaxGridNum ) )
				{
					Logger::info('uid:%d, bag:%s, open grid:%d, add itemId:%d', 
									$this->mUid, $this->mName, $i, $itemId);
					$pos = $i;
				}
				if($pos >= 0)
				{
					foreach ( $arrStack as $k )
					{
						$this->mManager->unionItem($itemId, $this->mArrGrid[$k]);
						$this->mArrModify[] = $k;
						Logger::DEBUG('add num grid:%d', $k);
					}
					$item->setItemNum($num);
					$this->setGrid($pos, $itemId);
					return true;
				}
			}
			else
			{			
				foreach ( $arrStack as $k )
				{
					$this->mManager->unionItem($itemId, $this->mArrGrid[$k]);
					$this->mArrModify[] = $k;
					Logger::DEBUG('add num grid:%d', $k);
				}
				Logger::trace('bag:%s add itemId:%d success', $this->mName, $itemId);
				return true;
			}
		}	
		
		Logger::trace('add item failed. uid:%d, bag:%s, itemId:%d', $this->mUid, $this->mName, $itemId);
		$this->error = true;
		return false;
	}
	
	/**
	 * 往背包中添加可叠加物品，能加多少就加多少。返回实际加了多少
	 * 
	 * @param int $itemTplId
	 * @param int $num
	 * @return int 
	 */
	public function addItemTpl($itemTplId, $num)
	{
		Logger::trace('bag:%s add itemTplId:%d, num:%d', $this->mName, $itemTplId, $num);
		
		$stackableNum =  $this->mManager->getItemStackable($itemTplId);
		if( $stackableNum == ItemDef::ITEM_CAN_NOT_STACKABLE )
		{
			Logger::trace('itemTplId:%d not stackabel', $itemTplId);
			return 0;
		}
		
		$itemType = $this->mManager->getItemType($itemTplId);
		
		$toBagName = Bag::getBagNameByItemType($itemType);
		if( $this->mName != $toBagName && $this->mName != BagDef::BAG_TMP )
		{
			Logger::fatal('itemType:%d cant add to bag:%s', $itemType, $this->mName);
			return 0;
		}
		
		$start = $this->mStartGrid;
		$end = $this->mStartGrid +  count($this->mArrGrid);
		
		$preNum = $num;
		$this->prepareItem();
		//优先往相同的物品上面叠加
		for( $i = $start; $i < $end; $i++ )
		{
			if ( isset( $this->mArrGrid[$i]) && $this->mArrGrid[$i] != BagDef::ITEM_ID_NO_ITEM )
			{
				$tmpItem = $this->mManager->getItem( $this->mArrGrid[$i] );
				if ( $tmpItem != NULL
				&& $tmpItem->getItemTemplateID() == $itemTplId
				&& $tmpItem->getItemNum() < $stackableNum )
				{
					$addNum = min($stackableNum - $tmpItem->getItemNum(), $num);
					$num -= $addNum;
					$tmpItem->setItemNum($tmpItem->getItemNum() + $addNum);
					$this->mArrModify[] = $i;
				}
			}
			if ( $num <= 0 )
			{
				break;
			}
		}
		
		if($num < 0)
		{
			Logger::fatal('bag:%s add itemTplId:%d, add:%d, left:%d', $this->mName, $itemTplId, $preNum-$num, $num);
			$this->error = true;
			$num = 0;
		}
		Logger::trace('bag:%s add itemTplId:%d, add:%d, left:%d', $this->mName, $itemTplId, $preNum-$num, $num);
		return $preNum-$num;
	}
	
	public function notifyModifyByGrid($gid)
	{
		$this->mArrModify[] = $gid;
	}
	
	private function setGrid($gid, $itemId)
	{
		$this->mArrGrid[$gid] = $itemId;
		$this->mArrModify[] = $gid;
		Logger::trace('set gid:%d, itemId:%d', $gid, $itemId);
  	}
	
	private function canAddGridSelf()
	{
		return $this->mMaxGridNum == BagDef::MAX_GRID;
	}
	
	protected function prepareItem()
	{
		$arrItemId = array_merge($this->mArrGrid);
		$this->mManager->getItems($arrItemId);
	}
	
	public function clearBag()
	{
		foreach ( $this->mArrGrid as $gid => $itemId )
		{
			if ( $itemId != BagDef::ITEM_ID_NO_ITEM )
			{
				$itemObj = ItemManager::getInstance()->getItem($itemId);//删除之前，先获取一下。否则这个物品不会从系统中移除
				$this->removeItemByGid($gid);
				if ( $this->mManager->deleteItem($itemId) == false )
				{
					return false;
				}					
			}
		}
		return true;
	}
	
	/**
	 * 将脏数据回滚
	 * @return bool  如果真的有错，就返回true
	 */
	public function rollback()
	{
		if ( $this->error )
		{
			$this->mArrGrid = $this->mOrgData;
			$this->mArrModify = array();
			$this->error = FALSE;
			
			return true;
		}
		
		return false;
	}
	
	public function update()
	{
		foreach ( $this->mArrGrid as $gid => $itemId )
		{
			if ( !isset( $this->mOrgData[$gid]) || $this->mOrgData[$gid] != $itemId )
			{
				$values = array(
						BagDef::SQL_ITEM_ID => $itemId,
						BagDef::SQL_UID => $this->mUid,
						BagDef::SQL_GID => $gid
						);
				try
				{
					BagDAO::insertOrupdateBag($values);
				}
				catch ( Exception $e)
				{
					Logger::FATAL('update bag failed. uid:%d, gid:%d, itemId:%d',
					$this->mUid, $values[BagDef::SQL_GID], $values[BagDef::SQL_ITEM_ID]);
					throw $e;
				}
			}
		}

		$modifyInfo = $this->getArrGridInfo(array_unique($this->mArrModify));
		Logger::trace('bag:%s modify:%s', $this->mName, $modifyInfo);
		
		$this->mOrgData = $this->mArrGrid;
		$this->mArrModify = array();		
				
		return $modifyInfo;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */