<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemManager.class.php 253145 2016-07-25 07:46:20Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ItemManager.class.php $
 * @author $Author: BaoguoMeng $(jhd@babeltime.com)
 * @date $Date: 2016-07-25 07:46:20 +0000 (Mon, 25 Jul 2016) $
 * @version $Revision: 253145 $
 * @brief
 *
 **/

class ItemManager
{
	/**
	 *
	 * ItemManager实例
	 * @var ItemManager
	 */
	private static $mInstance;

	/**
	 *
	 * 维护的items的缓存
	 * @var array(Item)
	 */
	private $mArrItem = array();

	private $mArrOrgItem = array();
	
	/**
	 * 记录新产生的itemId
	 * @var array
	 */
	private $mArrNewItemId = array();
	
	/**
	 * 记录实际插入的物品， 后来在bag中使用此数据判断物品是否是新增的
	 * @var array
	 */
	private $mArrAddItemId = array();

	/**
	 *
	 * 私有构造函数
	 */
	private function __construct(){}

	/**
	 *
	 *  得到ItemManager实例
	 */
	public static function getInstance()
    {
		if(self::$mInstance == null)
		{
			self::$mInstance = new ItemManager();
		}
		return self::$mInstance;
	}

	/**
	 *
	 * 生成物品对象
	 * @param int $itemId
	 * @throws Exception			如果物品类型不存在,则throw Exception
	 *
	 * 注意：新加一种Item的时候，这里加一下，可以实现子类方法的挑战
	 * 
	 * @return Item|ArmItem|ArtificialTreasureItem|BookItem|ChariotItem|DirectItem|DressItem|FeedItem|FightSoulItem|FragmentItem|GiftItem|GodWeaponFragItem|GodWeaponItem|GoodWillItem|HeroFragItem|Item|NormalItem|PetFragItem|PocketItem|RandGiftItem|RuneFragItem|RuneItem|TallyFragItem|TallyItem|TreasFragItem|TreasureItem
	 */
	public function getItem($itemId)
	{
		$item = NULL;
		if ( $itemId <= 0 )
		{
			return $item;
		}

		//如果$this->mArrItem[$itemId] = NULL但是$this->mArrOrgItem[$itemId] != NULL 表示物品在前边的操作中被删除
		if ( isset($this->mArrItem[$itemId]) || isset($this->mArrOrgItem[$itemId]) )
		{
			return $this->mArrItem[$itemId];
		}
		else
		{
			//从数据库中获取物品信息
			$info = ItemStore::getItem($itemId);
			//根据物品类型生成相应的物品对象
			$item = self::__getItem($info);
			if ( $item == NULL )
			{
				Logger::WARNING('item:%d is not exists', $itemId);
				return $item;
			}
			else
			{
				$this->mArrItem[$itemId] = $item;
				$this->mArrOrgItem[$itemId] = serialize($item);
				return $this->mArrItem[$itemId];
			}
		}
	}

	/**
	 *
	 * 生成物品对象组
	 *
	 * @param array(int) $arrItemId
	 *
	 * @throws Exception			如果物品类型不存在,则throw Exception
	 *
	 * @return array
	 * <code>
	 * [
	 * 		itemId:Item
	 * ]
	 * </code>
	 */
	public function getItems($arrItemId)
	{
		$itemIdsBak = $arrItemId;
		//遍历检查是否有空物品或重复物品
		foreach ( $arrItemId as $key => $itemId )
		{
			if ( $itemId <= ItemDef::ITEM_ID_NO_ITEM )
			{
				unset($arrItemId[$key]);
			}
			else if ( isset($this->mArrItem[$itemId]) || isset($this->mArrOrgItem[$itemId]) )
			{
				unset($arrItemId[$key]);
			}
		}

		$itemInfos = array();
		if ( !empty($arrItemId) )
		{
			//从数据库中获取多个物品信息
			$itemInfos = ItemStore::getItems($arrItemId);
		}
		$ret = array();
		foreach ($itemIdsBak as $itemId)
		{
			//当前物品组里有这个物品
			if ( isset($this->mArrItem[$itemId]) || isset($this->mArrOrgItem[$itemId]) )
			{
				$ret[$itemId] = $this->mArrItem[$itemId];
			}
			else if ( $itemId != ItemDef::ITEM_ID_NO_ITEM )
			{
				if ( !isset($itemInfos[$itemId]) )
				{
					Logger::FATAL('item:%s is not exist!', $itemId);
					$ret[$itemId] = NULL;
				}
				else
				{
					//根据物品类型生成相应的物品对象
					$item = self::__getItem($itemInfos[$itemId]);
					$this->mArrItem[$itemId] = $item;
					$this->mArrOrgItem[$itemId] = serialize($item);
					$ret[$itemId] = $item;
				}
			}
		}
		return $ret;
	}

	/**
	 * 根据物品类型生成相应的物品对象
	 * 
	 * @param array $itemInfo 			数据库中物品信息
	 * @throws Exception
	 * @return object $item				物品对象
	 */
	public static  function __getItem($itemInfo, $isArtificial = false)
	{
		if ( empty($itemInfo) )
		{
			return NULL;
		}
		$item = NULL;
		$itemTplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
		$itemType = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TYPE);
		switch ( $itemType )
		{
			case ItemDef::ITEM_TYPE_HEROFRAG:
				$item = new HeroFragItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_ARM:
				$item = new ArmItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_DIRECT:
				$item = new DirectItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_BOOK:
				$item = new BookItem($itemInfo);	
				break;
			case ItemDef::ITEM_TYPE_GIFT:
				$item = new GiftItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_RANDGIFT:
				$item = new RandGiftItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_GOODWILL:
				$item = new GoodWillItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_FRAGMENT:
				$item = new FragmentItem($itemInfo);
				break;	
			case ItemDef::ITEM_TYPE_FEED:
				$item = new FeedItem($itemInfo);
				break;
			case Itemdef::ITEM_TYPE_NORMAL:
				$item = new NormalItem($itemInfo);
				break;
			case Itemdef::ITEM_TYPE_TREASURE:
				if ($isArtificial) 
				{
					$item = new ArtificialTreasureItem($itemInfo);
				}
				else 
				{
					$item = new TreasureItem($itemInfo);
				}
				break;
			case Itemdef::ITEM_TYPE_TREASFRAG:
				throw new InterException('invalid item type:%d', $itemType);
				break;
			case ItemDef::ITEM_TYPE_FIGHTSOUL:
				$item = new FightSoulItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_DRESS:
				$item = new DressItem($itemInfo);
				break;
			case ItemDef::ITEM_TYPE_PETFRAG:
				$item = new PetFragItem($itemInfo);
				break;
            case ItemDef::ITEM_TYPE_GODWEAPON:
                $item = new GodWeaponItem($itemInfo);
                break;
            case ItemDef::ITEM_TYPE_GODWEAPONFRAG:
                $item = new GodWeaponFragItem($itemInfo);
                break;
            case ItemDef::ITEM_TYPE_RUNE:
            	$item = new RuneItem($itemInfo);
            	break;
            case ItemDef::ITEM_TYPE_RUNEFRAG:
            	$item = new RuneFragItem($itemInfo);
            	break;
            case ItemDef::ITEM_TYPE_POCKET:
            	$item = new PocketItem($itemInfo);
            	break;
            case ItemDef::ITEM_TYPE_TALLY:
            	$item = new TallyItem($itemInfo);
            	break;
            case ItemDef::ITEM_TYPE_TALLYFRAG:
            	$item = new TallyFragItem($itemInfo);
            	break;
            case ItemDef::ITEM_TYPE_CHARIOT:
            	$item = new ChariotItem($itemInfo);
            	break;
			default:
				Logger::FATAL('Invalid item type=%d', $itemType);
				throw new Exception('fake');
				break;
		}
		return $item;
	}

	/**
	 *
	 * 得到物品的信息
	 *
	 * @param int $itemId
	 *
	 * @return array		物品的具体信息
	 */
	public function itemInfo($itemId)
	{
		$item = $this->getItem($itemId);
		if ( $item === NULL )
		{
			return array();
		}
		else
		{
			return $item->itemInfo();
		}
	}

	/**
	 *
	 * 得到多个物品的信息
	 * @param array(int) $arrItemId
	 *
	 * @return array		物品的具体信息
	 */
	public function itemInfos($arrItemId)
	{
		$ret = array();
		foreach ( $arrItemId as $itemId )
		{
			$itemInfo = $this->itemInfo($itemId);
			if ( !empty($itemInfo) )
			{
				$ret[$itemId] = $itemInfo;
			}
		}
		return $ret;
	}

	/**
	 *
	 * 掉落物品
	 * 
	 * @param int $dropTplId			掉落物品表模板ID
	 * @return array(int)				掉落的物品的IDs
	 */
	public function dropItem($dropTplId)
	{
		$array = array();
		$items = Drop::dropItem($dropTplId);
		foreach ($items as $itemTplId => $itemNum)
		{
			$array = array_merge($array, $this->addItem($itemTplId, $itemNum));
		}
		Logger::DEBUG('ItemManager:dropItem dropTplId:%d, items:%s', $dropTplId, $array);
		return $array;
	}

	/**
	 *
	 * 掉落多个物品
	 * @param array(int) $arrDropTplId			掉落物品表模板ID array
	 *
	 * @return array(int)						掉落的物品的IDs
	 */
	public function dropItems($arrDropTplId)
	{
		$array = array();
		foreach ($arrDropTplId as $dropTplId)
		{
			$items = $this->dropItem($dropTplId);
			$array = array_merge($array, $items);
		}
		return $array;
	}

	/**
	 *
	 * 增加物品
	 * @param array $arrItemTemplate			物品模板
	 * <code>
	 * {
	 * 		itemTplId : itemNum
	 * }
	 * </code>
	 *
	 * @return array(int) arrItemId			物品ID列表
	 */
	public function addItems($arrItemTemplate)
	{
		$arrItemId = array();
		foreach ( $arrItemTemplate as $itemTplId => $itemNum )
		{
			$arrItemId = array_merge($arrItemId, $this->addItem($itemTplId, $itemNum));
		}
		return $arrItemId;
	}

	/**
	 *
	 * 增加物品
	 * @param int $itemTplId			物品模板ID
	 * @param int $itemNum					物品数量
	 *
	 * @return array(int) arrItemId			物品ID列表
	 */
	public function addItem($itemTplId, $itemNum = 1)
	{
		if( $itemNum < 0 )
		{
			throw new FakeException('decreaseItem failed. id:%d, num:%d', $itemTplId, $itemNum);
		}
		
		$stackable = $this->getItemStackable($itemTplId);
		$arrItemId = array();
		//物品不可叠加
		if ( $stackable == ItemDef::ITEM_CAN_NOT_STACKABLE )
		{
			for ( $i = 0; $i < $itemNum; $i++ )
			{
				$itemText = array();
				switch ( $this->getItemType($itemTplId) )
				{
					case ItemDef::ITEM_TYPE_ARM:
						$itemText = ArmItem::createItem($itemTplId);
						break;
					case ItemDef::ITEM_TYPE_TREASURE:
						$itemText = TreasureItem::createItem($itemTplId);
						break;
					case ItemDef::ITEM_TYPE_FIGHTSOUL:
						$itemText = FightSoulItem::createItem($itemTplId);
						break;
                    case ItemDef::ITEM_TYPE_GODWEAPON:
                        $itemText = GodWeaponItem::createItem($itemTplId);
                        break;
                    case ItemDef::ITEM_TYPE_POCKET:
                    	$itemText = PocketItem::createItem($itemTplId);
                    	break;
                    case ItemDef::ITEM_TYPE_TALLY:
                    	$itemText = TallyItem::createItem($itemTplId);
                    	break;
                    case ItemDef::ITEM_TYPE_CHARIOT:
                    	$itemText=ChariotItem::createItem($itemTplId);
					default:
						break;
				}
				$itemId = $this->__addItem($itemTplId, 1, $itemText);
				$arrItemId[] = $itemId;
			}
		}
		else
		{
			//可叠加的物品应该没有任何的特化属性
			for ( $i = 0; $i < intval($itemNum/$stackable); $i++ )
			{
				$arrItemId[] = $this->__addItem($itemTplId, $stackable);
			}

			if ( $itemNum % $stackable != 0 )
			{
				$arrItemId[] = $this->__addItem($itemTplId, $itemNum % $stackable);
			}
		}
		return $arrItemId;
	}

	private function __addItem($itemTplId, $itemNum, $itemText = array())
	{
		if ( !is_array($itemText) )
		{
			throw new InterException('ItemStore::addItem itemText is not array!');
		}

		$itemId = IdGenerator::nextId(ItemDef::ITEM_SQL_ITEM_ID);
		$this->mArrNewItemId[] = $itemId;//为了查找item_id使用率很低问题添加
		
		$values = array();
		$values[ItemDef::ITEM_SQL_ITEM_ID] = $itemId;
		$values[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID] = $itemTplId;
		$values[ItemDef::ITEM_SQL_ITEM_NUM] = $itemNum;
		$values[ItemDef::ITEM_SQL_ITEM_TIME] = Util::getTime();
		$values[ItemDef::ITEM_SQL_ITEM_TEXT] = $itemText;
		$values[ItemDef::ITEM_SQL_ITEM_DELTIME] = 0;
		//根据物品类型生成相应的物品对象
		$item = self::__getItem($values);
		$this->mArrItem[$itemId] = $item;
		return $itemId;
	}

	/**
	 *
	 * 减少物品
	 * @param int $itemId                   物品ID
	 * @param int $itemNum                  物品数量
	 *
	 * @return boolean
	 */
	public function decreaseItem($itemId, $itemNum)
	{
		if( $itemNum < 0 )
		{
			throw new FakeException('decreaseItem failed. id:%d, num:%d', $itemId, $itemNum);
		}
		//生成物品对象
		$item = $this->getItem($itemId);
		if ( $item === NULL )
		{
			return FALSE;
		}
		//得到物品的叠加属性
		$stackable = $item->getStackable();
		//物品不可叠加
		if ( $stackable == ItemDef::ITEM_CAN_NOT_STACKABLE )
		{
			//物品数量不为一
			if ( $itemNum != ItemDef::ITEM_CAN_NOT_STACKABLE )
			{
				return FALSE;
			}
			else
			{
				//删除物品
				return $this->deleteItem($itemId);
			}
		}
		else
		{
			//获取物品当前的数量
			$number = $item->getItemNum();
			if ( $number < $itemNum )
			{
				return FALSE;
			}
			else if ( $itemNum == $number )
			{
				return $this->deleteItem($itemId);
			}
			else
			{
				$item->setItemNum($number - $itemNum);
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 *
	 * 合并物品
	 *
	 * @param int $srcItemId
	 * @param int $desItemId
	 *
	 * @return boolean
	 */
	public function unionItem($srcItemId, $desItemId)
	{
		//两个位置都必须有物品才可以合并,否则应该是交换操作
		if ( empty($srcItemId) || empty($desItemId) )
		{
			return FALSE;
		}
        //生成物品对象
		$srcItem = $this->getItem($srcItemId);
		$desItem = $this->getItem($desItemId);

		//只有同种物品才可以合并
		if ( $srcItem->getItemTemplateID() != $desItem->getItemTemplateID() )
		{
			return FALSE;
		}
		//得到物品的叠加上限
		$maxStackNum = $srcItem->getStackable();

		Logger::DEBUG('ItemManager::unionItem srcItemId:%s, desItemId:%d.', $srcItemId, $desItemId);
		//达到叠加上限
		if ( $srcItem->getItemNum() + $desItem->getItemNum() > $maxStackNum  )
		{
			//剩余物品（碎片）数量
			$itemNum  = $srcItem->getItemNum() + $desItem->getItemNum() - $maxStackNum ;
			$desItem->setItemNum($maxStackNum );
			$srcItem->setItemNum($itemNum);
		}
		else
		{
			$desItem->setItemNum($srcItem->getItemNum() + $desItem->getItemNum());
			$this->deleteItem($srcItemId);
		}
		return TRUE;
	}

	/**
	 *
	 * 摧毁物品
	 * @param int $itemId
	 *
	 * @return boolean					TRUE 表示摧毁成功, FALSE表示摧毁失败
	 */
	public function destoryItem($itemId)
	{
		//生成物品对象
		$item = $this->getItem($itemId);
		if ( empty($item) )
		{
			return TRUE;
		}
		//物品是否可以摧毁
		if ( $item->canDestory() == TRUE )
		{
			$this->deleteItem($itemId);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 *
	 * 删除物品
	 * @param int $itemId
	 *
	 * @return boolean					TRUE 表示删除成功, FALSE表示删除失败
	 */
	public function deleteItem($itemId)
	{
		unset($this->mArrItem[$itemId]);
		$this->mArrItem[$itemId] = NULL;
		return TRUE;
	}

	/**
	 *
	 * 拆分物品
	 * @param int $itemId
	 * @param int $itemNum
	 *
	 * @return int $itemId
	 */
	public function splitItem($itemId, $itemNum)
	{
		//生成物品对象
		$item = $this->getItem($itemId);
		//得到物品当前数量
		$curItemNum = $item->getItemNum();
		//物品当前数量不足
		if ( $curItemNum < $itemNum )
		{
			return ItemDef::ITEM_ID_NO_ITEM;
		}
		else if ( $curItemNum == $itemNum )
		{
			return $itemId;
		}
		else
		{
			$newItemId = ItemDef::ITEM_ID_NO_ITEM;
			//应该是可叠加物品
			$arrItemIds = $this->addItem($item->getItemTemplateID(), $itemNum);
			if ( count($arrItemIds) != 1 )
			{
				Logger::FATAL('split item failed!itemId:%d, itemTplId:%d', $itemId,
					$item->getItemTemplateID());
				return ItemDef::ITEM_ID_NO_ITEM;
			}
			else
			{
				//新的物品
				$newItemId = $arrItemIds[0];
			}
			$item->setItemNum($curItemNum - $itemNum);
			Logger::DEBUG('split new item:%d!', $newItemId);
			return $newItemId;
		}
	}

	/**
	 *
	 * 得到物品的最大叠加数量
	 *
	 * @param int $itemTplId
	 *
	 * @return int
	 */
	public function getItemStackable($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_STACKABLE);
	}

	/**
	 *
	 * 得到物品的类型
	 *
	 * @param int $itemTplId
	 *
	 * @return int
	 */
	public function getItemType($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TYPE);
	}

	/**
	 *
	 * 得到物品的品质
	 *
	 * @param int $itemTplId
	 *
	 * @return int
	 */
	public function getItemQuality($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_QUALITY);
	}

	/**
	 *
	 * 得到物品模板信息
	 *
	 * @param array(int) $arrItemId
	 *
	 * @return array
	 * <code>
	 * [
	 * 		itemTplId:itemNum
	 * ]
	 * </code>
	 */
	public function getTemplateInfoByItemIds($arrItemId)
	{
		$ret = array();
		$arrItem = $this->getItems($arrItemId);
		foreach ( $arrItem as $item )
		{
			//得到物品模板
			$itemTplId = $item->getItemTemplateId();
			//得到物品数量
			$itemNum = $item->getItemNum();
			if ( isset($ret[$itemTplId]) )
			{
				$ret[$itemTplId] += $itemNum;
			}
			else
			{
				$ret[$itemTplId] = $itemNum;
			}
		}
		return $ret;
	}

	public function rollback()
	{
		$this->mArrItem = unserialize($this->mArrOrgItem);
	}

	public function update()
	{
		foreach ( $this->mArrItem as $itemId => $item )
		{
			//如果是新增的物品
			if ( !isset($this->mArrOrgItem[$itemId]) && !empty($item) )
			{
				//添加物品信息到数据库
				ItemStore::addItem($item->getItemID(), $item->getItemTemplateID(), $item->getItemTime(),
					$item->getItemNum(), $item->getItemText());
				$this->mArrOrgItem[$itemId] = serialize($this->mArrItem[$itemId]);
				
				//检查item_id使用率低问题，临时添加
				$this->mArrAddItemId[] = $itemId;
				
			}
			else if ( isset($this->mArrOrgItem[$itemId]) && empty($item) )
			{
				//物品被删除了
				ItemStore::deleteItem($itemId);
				unset($this->mArrOrgItem[$itemId]);
			}
			else if ( !isset($this->mArrOrgItem[$itemId]) && empty($item))
			{
				//这个物品被add之后又被delete，不用处理
				Logger::trace('itemId:%d ignore', $itemId);
			}
			else
			{
				$values = array();
				$oldItem = unserialize($this->mArrOrgItem[$itemId]);
				//判断属性值是否改变
				if ( $item->getItemNum() != $oldItem->getItemNum() )
				{
					$values[ItemDef::ITEM_SQL_ITEM_NUM] = $item->getItemNum();
				}
				if ( serialize($item->getItemText()) !=
					 serialize($oldItem->getItemText()) )
				{
					$values[ItemDef::ITEM_SQL_ITEM_TEXT] = $item->getItemText();
				}
				if (!empty($values))
				{
					ItemStore::updateItem($itemId, $values);
					$this->mArrOrgItem[$itemId] = serialize($this->mArrItem[$itemId]);
				}
			}
		}
	}
	
	
	
	public function getArrNewItemId()
	{
		return $this->mArrNewItemId;
	}
	
	public function getArrAddItemId()
	{
		return $this->mArrAddItemId;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */