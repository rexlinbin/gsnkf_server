<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Bag.class.php 250248 2016-07-06 09:32:12Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/bag/Bag.class.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief
 *
 **/




class Bag implements IBag
{
	/**
	 *
	 * 用户UID
	 * @var int
	 */
	private $mUid;	
	
	/**
	 * 背包数组
	 * @var SimpleBag[]
	 */
	private $mBagList;
	
	/**
	 * 背包数组(不包括临时背包）
	 * @var array SimpleBag[]
	 */
	private $mBagListNoTmp;
	
	/**
	 * 临时背包
	 * @var SimpleBag
	 */
	private $mBagTmp;

	/**
	 * 是否执行出错
	 * @var boolean
	 */
	private $error = false;

	/**
	 *
	 * 物品管理器对象
	 * @var ItemManager
	 */
	private $mManager;

	
	public function Bag()
	{
		$this->mUid = RPCContext::getInstance()->getSession(BagDef::SESSION_USER_ID);
		if ( $this->mUid == 0 )
		{
			throw  new FakeException('invalid uid:%d', $this->mUid);			
		}
		$this->mManager = ItemManager::getInstance();
		
		//获取当前用户所有的背包数据
		$bagData = $this->getBagData();
		
		$arrGridMaxNum = array();
		foreach (BagDef::$BAG_GRID_START as $key => $value)
		{
			if (array_key_exists($key, BagDef::$BAG_INIT_GRID))
			{
				$arrGridMaxNum[$key] = count($bagData[$key]);
			}
			else 
			{
				$arrGridMaxNum[$key] = BagDef::MAX_GRID;
			}
		}
		
		$isInit = true;//用户刚刚创建，背包中还没有东西
		//创建背包对象
		foreach ($bagData as $key => $value)
		{			
			$isInit = $isInit && empty($value);
			$this->mBagList[$key] = new SimpleBag($this->mUid, $key, $value, BagDef::$BAG_GRID_START[$key], $arrGridMaxNum[$key] );
		} 
		
		//某些背包是要限制格子数的。 数据库中属于背包的格子 即为实际打开的格子。所以需要初始化那些初始默认开启的格子
		foreach (BagDef::$BAG_INIT_GRID as $key => $value)
		{
			$this->mBagList[$key]->initBag($value);
		}
		
		$this->mBagListNoTmp = $this->mBagList;
		unset($this->mBagListNoTmp[BagDef::BAG_TMP]);
		$this->mBagTmp = $this->mBagList[BagDef::BAG_TMP];
		
		//如果用户是刚刚创建，还没有东西，就给点初始物品
		if($isInit)
		{
			Logger::info('add init items:%s', BagConf::$INIT_ARR_ITEM);
			$this->addItemsByTemplateID( BagConf::$INIT_ARR_ITEM );
		}
		
		//确保自己在BagManager中。作用： 把通过Framework创建的bag对象放到bagManager中
		BagManager::getInstance()->setBag($this->mUid, $this);
	}

	/* (non-PHPdoc)
	 * @see IBag::bagInfo()
	 */
	public function bagInfo() 
	{		
		$itemIds = array();
		foreach ( $this->mBagList as $bagName => $bag)
		{
			$itemIds = array_merge($itemIds, $bag->getAllData());
		}
		//批量拉取物品信息进缓存
		$this->mManager->getItems($itemIds);
		
		$returnData = array();
		
		//各个背包中的物品信息
		foreach ( $this->mBagListNoTmp as $bagName => $bag)
		{
			$returnData[$bagName] = $bag->getBagInfo();
		}
		
		//临时背包中可能有装备背包中,道具背包,宝物背包的数据
		$bagTmpInfo = $this->mBagTmp->getBagInfo();
		$num = count($bagTmpInfo);
		if( $num > 0 )
		{
			Logger::warning('there %d item in tmp bag', $num);
			foreach ($bagTmpInfo as $gid => $itemInfo)
			{
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				$itemType = $item->getItemType();
				$bagName = $this->getBagNameByItemType($itemType);
				$returnData[$bagName][$gid] = $itemInfo; 		
			}
		}
		
		//简化信息
		foreach ($returnData as $bagName => $arrItemInfo)
		{
			$returnData[$bagName] = $this->simplifyBagInfo($arrItemInfo);
		}
						
		//背包分段信息
		$returnData['gridStart'] = BagDef::$BAG_GRID_START;
		
		//各个背包的最大格子数
		$returnData['gridMaxNum'] = array();
		foreach (BagDef::$BAG_GRID_START as $bagName => $value)
		{
			$returnData['gridMaxNum'][$bagName] = $this->mBagList[$bagName]->getMaxGridNum();
		}	
		
		$this->update();
		
		return $returnData;
	}
	
	public function simplifyBagInfo($arrItemInfo)
	{
		foreach ($arrItemInfo as $key => $itemInfo)
		{
			unset($itemInfo[ItemDef::ITEM_SQL_ITEM_TIME]);
			if ($itemInfo[ItemDef::ITEM_SQL_ITEM_NUM] == 1)
			{
				unset($itemInfo[ItemDef::ITEM_SQL_ITEM_NUM]);
			}
			if (empty($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT]))
			{
				unset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT]);
			}
			$arrItemInfo[$key] = $itemInfo;
		}
		return $arrItemInfo;
	}

	/* (non-PHPdoc)
	 * @see IBag::gridInfo()
	 */
	public function gridInfo($gid)
	{
		$bagName = self::getBagNameByGid($gid);
		return $this->mBagList[$bagName]->getGridInfo($gid);
	}

	/* (non-PHPdoc)
	 * @see IBag::gridInfos()
	 */
	public function gridInfos($gids)
	{
		$returnData = array();
		foreach ( $gids as $gid )
		{
			$returnData[$gid] = $this->gridInfo($gid);
		}
		return $returnData;
	}

	/* (non-PHPdoc)
	 * @see IBag::useItem()
	 */
	public function useItem($gid, $itemId, $itemNum, $check = 0, $merge = 0)
	{
		//1. 参数检查
		$gid = intval($gid);
		$itemId = intval($itemId);
		$itemNum = intval($itemNum);
		
		if ( $itemNum <= 0 )
		{
			throw new FakeException('invalid itemNum:%d', $itemNum);
		}
		//根据前端传的check标志位(是否批量使用的第1次请求)，只在第1次请求和使用物品数量超过50的时候进行背包满检查
		$useNum = RPCContext::getInstance()->getSession(BagDef::SESSION_USE_NUM);
		$useNum = $check ? $itemNum : $useNum + $itemNum;
		RPCContext::getInstance()->setSession(BagDef::SESSION_USE_NUM, $useNum);
		$check = $check || $useNum > BagConf::BAG_USE_LIMIT ? true : false;

		//2. 检查物品，背包数据		
		$bagName = self::getBagNameByGid($gid);		
		if ( $itemId != $this->mBagList[$bagName]->getItemIdByGid($gid) )
		{
			throw new FakeException('invalid para gid:%d or itemId:%d', $gid, $itemId);
		}
		
		$item = $this->mManager->getItem($itemId);		
		if ( $item === NULL )
		{
			//背包中有一个物品，在系统中不存在
			throw new InterException('fix me! invalid itemId:%d in bag', $itemId);
		}		
		
		$itemType = $item->getItemType();
		if (key_exists($itemType, ItemDef::$USE_LIMIT_TYPES) && $itemNum > ItemDef::$USE_LIMIT_TYPES[$itemType]) 
		{
			throw new FakeException('use item:%d beyond limit %d', $itemId, ItemDef::$USE_LIMIT_TYPES[$itemType]);
		}
		//物品是否足够
		if ( $this->decreaseItem($itemId, $itemNum) == false )
		{
			throw new FakeException('use item:%d need num for %d', $itemId, $itemNum);
		}

		//如果是碎片物品,则需要当前的数量等于所需碎片数量才能使用
		if ( in_array($itemType, ItemDef::$FRAG_VALID_TYPES))
		{
			$fragNum = $item->getFragNum();
			if ( $itemNum % $fragNum != 0 )
			{
				throw new FakeException('use fragment/herofrag item invalid, itemId:%d, itemNum:%d must be N * %d', $itemId, $itemNum, $fragNum);
			}
			else
			{
				$itemNum /= $fragNum;
			}
		}
		
		//3. 检查使用要求
		$useReqInfo = $item->useReqInfo();
		$useAcqInfo = $item->useAcqInfo();
		$itemTemplateId = $item->getItemTemplateID();
		if ( empty($useReqInfo) && empty($useAcqInfo) )
		{
			throw new ConfigException("itemId:%d itemTemplateId:%d is not exist", $itemId, $itemTemplateId);			
		}
		//开金箱子需要特殊条件
		$userObj = EnUser::getUserObj();
		list($needVip, $needLevel) = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_OPEN_GOLD_BOX]->toArray();
		if (in_array($itemTemplateId, NormalConfigDef::$BOX_ARR)
		&& $userObj->getVip() < $needVip && $userObj->getLevel() < $needLevel)
		{
			throw new FakeException("user can not open box");
		}

		$ret = 'ok';
		
		//物品使用需求
		if ( !empty($useReqInfo) )
		{
			//使用需要消耗silver
			if ( !empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER]) )
			{
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER] * $itemNum;
				if ( $userObj->subSilver($need) == false )
				{
					throw new FakeException('use item:%d need silver:%d', $itemId, $need);
				}
			}
			//使用需要消耗gold
			if ( !empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD]) )
			{
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD] * $itemNum;
				if ( $userObj->subGold($need, StatisticsDef::ST_FUNCKEY_BAG_USEITEM_COST) == false )
				{
					throw new FakeException('use item:%d need gold:%d', $itemId, $need);					
				}
			}
			//检测延迟使用时间
			if ( !empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME]) )
			{
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME];
				if ( Util::getTime() - $item->getItemTime() < $need )
				{
					throw new FakeException('use item:%d need wait time:%d', $itemId, $need);				
				}
			}
			//使用消耗物品
			if ( !empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]) )
			{
				$items = array();
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS];
				foreach ( $need as $key => $value )
				{
					$items[$key] = $value * $itemNum;
				}
				if ( $this->deleteItemsByTemplateID($items) == false )
				{
					throw new FakeException('use item:%d need item:%s', $itemId, $items);
				}
			}
			//检测用户等级
			if ( !empty( $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL] ) )
			{
				$userLevel = $userObj->getLevel();
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL]->toArray();
				if ( $userLevel < $need[0] || $userLevel >= $need[1] )
				{
					throw new FakeException('use item:%d need user level in [%d, %d)', $itemId, $need[0], $need[1]);
				}
			}
		}
		$pet = array();
		$drop = array();
		$useItemAddGold = FALSE;
		//4. 物品使用得到
		if ( !empty($useAcqInfo) )
		{
			//使用得到银两
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_SILVER] * $itemNum;
				if ( $userObj->addSilver($gain) == false )
				{
					throw new InterException('add silver:%d failed', $gain);
				}
			}
			//使用得到金币
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_GOLD] * $itemNum;
				if ( $userObj->addGold($gain, StatisticsDef::ST_FUNCKEY_BAG_USEITEM_GET) == false )
				{
					throw new InterException('add gold:%d failed', $gain);
				}
				
				if ($itemType == ItemDef::ITEM_TYPE_DIRECT && $item->isAddVipExp()) 
				{
					$useItemAddGold = TRUE;
					$userObj->addUseItemGold($gain);
					Logger::info('useItem add vip exp[%d]', $gain);
				}
			}
			//使用得到行动力
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXECUTION] * $itemNum;
				if ( $userObj->addExecution($gain) == false )
				{
					throw new InterException('add execution:%d failed', $gain);
				}
			}
			//使用得到将魂
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL]) ) 
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_SOUL] * $itemNum;
				if ( $userObj->addSoul($gain) == false )
				{
					throw new InterException('add soul:%d failed', $gain);
				}
			}
			//使用得到耐力
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_STAMINA] * $itemNum;
				if ( $userObj->addStamina($gain) == false )
				{
					throw new InterException('add stamina:%d failed', $gain);
				}
			}
			//使用得到经验
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXP]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_EXP] * $itemNum;
				if ( $userObj->addExp($gain) == false )
				{
					throw new FakeException('add exp:%d failed', $gain);
				}
			}
			//使用得到声望
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PRESTIGE]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PRESTIGE] * $itemNum;
				if ( $userObj->addPrestige($gain) == false )
				{
					throw new FakeException('add prestige:%d failed', $gain);
				}
			}
			//使用得到赤卷天书
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_BOOK]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_BOOK] * $itemNum;
				if ( $userObj->addBookNum($gain) == false )
				{
					throw new FakeException('add book:%d failed', $gain);
				}
			}
			//使用增加竞技次数
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE]) )
			{
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_CHALLENGE] * $itemNum;
				if (EnArena::addChallengeNum($userObj->getUid(), $gain) == false)
				{
					throw new FakeException('add challenge num:%d failed', $gain);
				}
			}
			//使用得到宠物
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET]) )
			{
				$pets = array();
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_PET];
				foreach ( $gain as $key => $value )
				{
					$pets[$key] = $value * $itemNum;
				}	
				$pet = EnPet::addPet($pets);	
				if ( $pet == 'fail' )
				{
					throw new FakeException('full bag, pet tpls:%s', $pets);	
				}	
			}
			//使用得到物品
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS]) )
			{
				$items = array();
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS];
				foreach ( $gain as $key => $value )
				{
					$items[$key] = $value * $itemNum;
				}	
				if ( $this->addItemsByTemplateID($items, !$check) == false )
				{
					$ret = 'bagfull';
				}
			}
			//使用得到英雄
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO]) )
			{
				if ($check && $userObj->getHeroManager()->hasTooManyHeroes()) 
				{
					$ret = 'herofull';
				}
				$heroes = array();
				$arrHero = array();
				$gain = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_HERO];
				foreach ( $gain as $key => $value )
				{					
					$heroes[$key] = $value * $itemNum;
					$heroQuality = Creature::getHeroConf($key, CreatureAttr::STAR_LEVEL);	
					if (!isset($arrHero[$heroQuality]))
					{
						$arrHero[$heroQuality] = 0;
					}
					$arrHero[$heroQuality] += $value * $itemNum;			
				}		
				$userObj->getHeroManager()->addNewHeroes($heroes);
				Logger::trace('add heros:%s', $heroes);
			}
			//使用掉落物品
			if ( !empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP]) )
			{
				$dropId = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP];
				$dropSpecial = array();
				$specialInfo = array();
				if (!empty($useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP_SPECIAL])) 
				{
					$dropSpecial = $useAcqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_DROP_SPECIAL]->toArray();
					$specialInfo = EnUser::getExtraInfo(UserExtraDef::SPECIAL);
					$dropSpecial[] = isset($specialInfo[$itemTemplateId]) ? $specialInfo[$itemTemplateId] : 0;
				}
				//如果是开金箱子
				$arrDropId = array();
				if (in_array($itemTemplateId, NormalConfigDef::$GOLD_BOX_ARR))
				{
					//获得用户开金箱子的次数
					$args = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GOLDBOX_SERIAL];
					$openNum = EnUser::getExtraField(UserExtraDef::USER_EXTRA_FIELD_OPEN_GOLD_NUM);
					for ( $i = 0; $i < $itemNum; $i++ )
					{
						$openNum ++;
						$isSpecialDrop = Util::isSpecialDrop($args, $openNum);
						if ($isSpecialDrop)
						{
							$spDropId = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GOLDBOX_DROP];
							$openNum = 0;
							$arrDropId[] = $spDropId;
						}
						else
						{
							$arrDropId[] = $dropId;
						}
					}
					EnUser::setExtraField(UserExtraDef::USER_EXTRA_FIELD_OPEN_GOLD_NUM, $openNum);
				}
				else 
				{
					for ( $i = 0; $i < $itemNum; $i++ )
					{
						$arrDropId[] = $dropId;
					}
				}
				Logger::trace('arrDropId:%s', $arrDropId);
				$drop = EnUser::drop($userObj->getUid(), $arrDropId, true, !$check, $check, false, $dropSpecial);
				if (isset($drop['ret']) && $drop['ret'] != 'ok')
				{
					$ret = $drop['ret'];
				}
				if (!empty($drop[DropDef::DROP_TYPE_STR_ITEM]) 
				&& !empty(btstore_get()->ITEMS[$itemTemplateId][ItemDef::ITEM_ATTR_NAME_USE_CHAT])) 
				{
					ChatTemplate::openBox($userObj->getTemplateUserInfo(), $itemTemplateId, $drop[DropDef::DROP_TYPE_STR_ITEM]);
				}
				if (in_array($itemTemplateId, NormalConfigDef::$BOX_ARR))
				{
					EnDesact::doDesact($userObj->getUid(), DesactDef::OPEN_BOX, $itemNum);
				}
				if (!empty($drop['special'])) 
				{
					$dropSpecial = $drop['special'];
					unset($drop['special']);
					$specialInfo[$itemTemplateId] = $dropSpecial[count($dropSpecial) - 1];
					EnUser::setExtraInfo(UserExtraDef::SPECIAL, $specialInfo);
				}
				Logger::info('use item drop:%s', $drop);
			}
		}
		else
		{
			Logger::fatal('use item get nothing. itemId:%d, templateId:%d', $itemId, $itemTemplateId);
		}
		
		if ($ret == 'ok') 
		{
			// 如果使用物品没有问题，则判断是否需要合并物品
			if ($merge) 
			{
				$this->mergeItem($itemTemplateId);
			}
			
			// 使用特殊道具可能获得
			if ($useItemAddGold) 
			{
				// 获得玩家累积vip经验：累积充值 + 使用特定道具获得的金币
				$uid = RPCContext::getInstance()->getUid();
				$useItemGold = $userObj->getUseItemGold();
				$sumGold = User4BBpayDao::getSumGoldByUid($uid);
				$sumGold += $useItemGold;
				 
				// 计算新vip，保存旧vip
				$oldVip = $userObj->getVip();
				$newVip = 0;
				foreach (btstore_get()->VIP as $vipInfo)
				{
					if ($vipInfo['totalRecharge'] > $sumGold)
					{
						break;
					}
					else
					{
						$newVip = $vipInfo['vipLevel'];
					}
				}
				
				if ($newVip > $oldVip)
				{
					$userObj->setVip($newVip);
					RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::USER_UPDATE_USER_INFO, array('gold_num' => $userObj->getGold(), 'vip' => $userObj->getVip()));
					RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_VIP, $newVip);//TODO 如果后面update失败了，这里session实际已经设置上啦
				
					ChatTemplate::sendSysVipLevelUp1($userObj->getTemplateUserInfo(), $newVip);
					MailTemplate::sendVip($userObj->getUid(), $newVip);
					ChatTemplate::sendBroadcastVipLevelUp2($userObj->getTemplateUserInfo(), $newVip);
				}
			}
			
			//更新数据
			$userObj->update();
			$this->update();
			if ($itemType == ItemDef::ITEM_TYPE_HEROFRAG) 
			{
				foreach ($arrHero as $quality => $num)
				{
					EnAchieve::updateHeroFrag($this->mUid, $quality, $num);
				}
			}
		}
		
		return array(
				'ret' => $ret,
				'pet' => $pet,
				'drop' => $drop,
		);
	}
	
	/**
	 * 根据物品模板id，及其堆叠上限，合并多个没有达到堆叠上限的物品，这种情况一般在策划改变了堆叠上限的时候会发生
	 * 
	 * @param number $itemTemplateId
	 * @throws InterException
	 * @return boolean 是否合并成功 !!!调用者需要根据返回值在外层进行update!!!
	 */
	public function mergeItem($itemTemplateId)
	{
		// 不可叠加的物品，不能合并
		$maxStackSize = ItemAttr::getItemAttr($itemTemplateId, ItemDef::ITEM_ATTR_NAME_STACKABLE);
		if ($maxStackSize == ItemDef::ITEM_CAN_NOT_STACKABLE)
		{
			return FALSE;
		}
		
		// 如果物品的个数小于等于1个，没必要合并
		$arrItemId = $this->getItemIdsByTemplateID($itemTemplateId);
		if (count($arrItemId) <= 1) 
		{
			return FALSE;
		}
		
		// 获得所有物品对象，去掉已经达到堆叠上限的物品
		$arrItemObj = $this->mManager->getItems($arrItemId);
		foreach ($arrItemObj as $aItemId => $aItemObj)
		{
			if (NULL == $aItemObj)
			{
				throw new InterException('fix me! invalid itemId:%d in bag', $aItemId);
			}
			
			if ($aItemObj->getItemNum() == $maxStackSize)
			{
				unset($arrItemObj[$aItemId]);
				continue;
			}
		}
		
		// 如果没有达到堆叠上限的物品数量个数小于等于1，也没必要合并
		if (count($arrItemObj) <= 1) 
		{
			return FALSE;
		}
		
		// 所有碎片数量,直接删除原来的物品，新加一个物品，这样可能会多用几个id，但是简单一点
		$allItemNum = 0;
		foreach ($arrItemObj as $aItemId => $aItemObj)
		{
			$allItemNum += $aItemObj->getItemNum();
			$this->deleteItem($aItemId);
			Logger::info('MERGE ITEM : delete old item id[%d], item tpl id[%d], item num[%d]', $aItemId, $itemTemplateId, $aItemObj->getItemNum());
		}
		$this->addItemByTemplateID($itemTemplateId, $allItemNum);
		$arrNewItemId = $this->getItemIdsByTemplateID($itemTemplateId);
		Logger::info('MERGE ITEM : add new item id[%s], item tpl id[%d], item num[%d]', $arrNewItemId, $itemTemplateId, $allItemNum);
		
		return TRUE;
	}

	public function useGift($gid, $itemId, $optionId, $itemNum, $check = 0)
	{
		//1. 参数检查
		$gid = intval($gid);
		$itemId = intval($itemId);
		$optionId = intval($optionId);
		$itemNum = intval($itemNum);
		if ($gid <= 0 || $itemId <= 0 || $optionId < 0)
		{
			throw new FakeException('invalid gid:%d itemId:%d optionId:%d', $gid, $itemId, $optionId);
		}
		//根据前端传的check标志位(是否批量使用的第1次请求)，只在第1次请求和使用物品数量超过50的时候进行背包满检查
		$useNum = RPCContext::getInstance()->getSession(BagDef::SESSION_GIFT_USE_NUM);
		$useNum = $check ? $itemNum : $useNum + $itemNum;
		RPCContext::getInstance()->setSession(BagDef::SESSION_GIFT_USE_NUM, $useNum);
		$check = $check || $useNum > BagConf::BAG_USE_LIMIT ? true : false;
		
		//2. 检查物品，背包数据
		$bagName = self::getBagNameByGid($gid);
		if ($itemId != $this->mBagList[$bagName]->getItemIdByGid($gid))
		{
			throw new FakeException('invalid para gid:%d or itemId:%d', $gid, $itemId);
		}
	
		//检查物品是否存在
		$item = $this->mManager->getItem($itemId);
		if ($item === NULL)
		{
			throw new InterException('fix me! invalid itemId:%d in bag', $itemId);
		}
		
		//检查物品是否礼物类型
		$itemType = $item->getItemType();
		if ($itemType != ItemDef::ITEM_TYPE_GIFT) 
		{
			throw new FakeException('itemId:%d type:%d is not a gift', $itemId, $itemType);
		}
		
		//检查物品是否有可选项
		$options = $item->getOptions();
		if (empty($options)) 
		{
			throw new FakeException('itemId:%d has no options', $itemId);
		}
		
		//检查是否有这个选项
		if (!isset($options[$optionId])) 
		{
			throw new FakeException('itemId:%d has no optionId:%d in options:%s', $itemId, $optionId, $options);
		}

		//检查物品是否足够
// 		$itemNum = 1;
		if ($this->decreaseItem($itemId, $itemNum) == false)
		{
			throw new FakeException('item:%d need num for %d', $itemId, $itemNum);
		}
		
		//3. 检查使用要求
		$useReqInfo = $item->useReqInfo();
		$userObj = EnUser::getUserObj();
		//物品使用需求
		if (!empty($useReqInfo))
		{
			//使用需要消耗silver
			if (!empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER]))
			{
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_SILVER] * $itemNum;
				if ($userObj->subSilver($need) == false)
				{
					throw new FakeException('no enough silver:%d', $need);
				}
			}
			//使用需要消耗gold
			if (!empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD]))
			{
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_GOLD] * $itemNum;
				if ($userObj->subGold($need, StatisticsDef::ST_FUNCKEY_BAG_USEITEM_COST) == false)
				{
					throw new FakeException('no enough gold:%d', $need);
				}
			}
			//检测延迟使用时间
			if (!empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME]))
			{
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_DELAY_TIME];
				if (Util::getTime() - $item->getItemTime() < $need)
				{
					throw new FakeException('no enough wait time:%d', $need);
				}
			}
			//使用消耗物品
			if (!empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS]))
			{
				$items = array();
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_ITEMS];
				foreach ($need as $key => $value)
				{
					$items[$key] = $value * $itemNum;
				}
				if ($this->deleteItemsByTemplateID($items) == false)
				{
					throw new FakeException('no enough item:%s', $items);
				}
			}
			//检测用户等级
			if (!empty($useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL]))
			{
				$userLevel = $userObj->getLevel();
				$need = $useReqInfo[ItemDef::ITEM_ATTR_NAME_USE_REQ_USER_LEVEL]->toArray();
				if ($userLevel < $need[0] || $userLevel >= $need[1])
				{
					throw new FakeException('need user level in [%d, %d)', $need[0], $need[1]);
				}
			}
		}
		
		//4. 物品使用得到
// 		$option = $options[$optionId];
		for ($num = 0; $num < $itemNum; ++$num)
		{
			$option[] = $options[$optionId];
		}
		
		//5.检查背包满
		if ($check)
		{
			$isFull = $this->isFull();	
			if ($isFull)
			{
				throw new FakeException('corresponding bag is full');
			}
		}
		
// 		RewardUtil::reward3DArr($userObj->getUid(), array($option), StatisticsDef::ST_FUNCKEY_BAG_USEITEM_GET);
		RewardUtil::reward3DArr($userObj->getUid(), $option, StatisticsDef::ST_FUNCKEY_BAG_USEITEM_GET, true);
		$userObj->update();
		$this->update();
	
		return 'ok';
	}
	
	private function sellItem($gid, $itemId, $itemNum)
	{
		//1. 参数检查
		$gid = intval($gid);
		$itemId = intval($itemId);
		$itemNum = intval($itemNum);
		if (empty($gid) || empty($itemId) || empty($itemNum)) 
		{
			throw new FakeException('invalid gid:%d itemId:%d itemNum:%d', $gid, $itemId, $itemNum);
		}
		if ( $itemNum <= 0 )
		{
			throw new FakeException('invalid itemNum:%d', $itemNum);
		}
		
		//2. 检查物品，背包数据
		$bagName = self::getBagNameByGid($gid);
		if ( $itemId != $this->mBagList[$bagName]->getItemIdByGid($gid) )
		{
			throw new FakeException('invalid gid:%d or itemId:%d', $gid, $itemId);
		}
		
		$item = $this->mManager->getItem($itemId);
		if ( $item === NULL )
		{
			//背包中有一个物品，在系统中不存在
			throw new InterException('fixed me! invalid itemId:%d in bag', $itemId);
		}
		
		if ($item->canSell() == false) 
		{
			throw new FakeException('itemId:%d can not be selled', $itemId);
		}

		if ($item->getItemNum() < $itemNum) 
		{
			throw new FakeException('itemId:%d is not enough for sell num:%d', $itemId, $itemNum);
		}
		//蓝色以上不能卖:装备，宝物
		$itemType = $item->getItemType();
		if (in_array($itemType, ItemDef::$SELL_INVALID_TYPES) 
		&& $item->getItemQuality() >= ItemDef::ITEM_QUALITY_BLUE) 
		{
			throw new FakeException('itemId:%d quality is too large to sell', $itemId);
		}
		
		$sellInfo = $item->sellInfo();
		$sellType = $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_TYPE];
		$sellPrice = $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE] * $itemNum;
		//调用User模块,增加金钱相关
		$user = EnUser::getUserObj();
		switch ( $sellType )
		{
			case ItemDef::ITEM_SELL_TYPE_SILVER:
				if ( $user->addSilver($sellPrice) == FALSE )
				{
					throw new FakeException('add silver failed!');
				}
				break;
			default:
				throw new ConfigException('invalid sell type:%d', $sellType);
				break;
		}
		
		//从背包里删除
		$ret = $this->decreaseItem($itemId, $itemNum);
		if( ! $ret )
		{
			throw new InterException('delete itemId:%d from bag failed', $itemId);
		}
	}
	
	public function sellItems($gids)
	{
		if (empty($gids))
		{
			throw new FakeException('gids is empty!');
		}
		if (!is_array($gids)) 
		{
			throw new FakeException('gids is not array!');
		}
		//先准备好所有物品
		$arrItemId = array();
		foreach ($gids as $value)
		{
			$arrItemId[] = $value[1]; 
		}
		$this->mManager->getItems($arrItemId);
		foreach ($gids as $value)
		{
			$ret = $this->sellItem($value[0], $value[1], $value[2]);
		}
		$user = EnUser::getUserObj();
		//更新背包数据
		$this->update();
		//更新用户数据
		$user->update();
		
		return 'ok';
	}
	
	/* (non-PHPdoc)
	 * @see IBag::destoryItem()
	 */
	public function destoryItem($gid, $itemId) 
	{
		//格式化输入
		$gid = intval($gid);
		$itemId = intval($itemId);

		//检测是否存在这个物品
		$bagName = self::getBagNameByGid($gid);		
		if ( $itemId != $this->mBagList[$bagName]->getItemIdByGid($gid) )
		{
			throw new FakeException('invalid gid:%d or itemId:%d', $gid, $itemId);
		}

		if ( $this->mManager->destoryItem($itemId) == false )
		{
			throw new FakeException('destory item:%d from manager failed', $itemId);
		}

		$this->mBagList[$bagName]->removeItemByGid($gid);
		$this->update();
		
		Logger::trace('destory item:%d', $itemId);
		
		return 'ok';
		
	}

	
	/* (non-PHPdoc)
	 * @see IBag::openGrid()
	 * 现在只能开启装备背包,道具背包,宝物背包的格子
	 */
	public function openGridByGold($gridNum, $bagType)
	{
		$gridNum = intval($gridNum);
		
		//输入参数是否合法
		if ( $gridNum <= 0 || $gridNum != BagConf::BAG_UNLOCK_GRID)
		{
			throw new FakeException('invalid gridNum:%d, gridNum must be %d', $gridNum, BagConf::BAG_UNLOCK_GRID);
		}
		
		if (!key_exists($bagType, BagDef::$BAG_OPEN_GRID)) 
		{
			throw new FakeException('invalid bagType:%d', $bagType);
		}
		
		$bagName = BagDef::$BAG_OPEN_GRID[$bagType][0];
		$initGridNum = BagDef::$BAG_OPEN_GRID[$bagType][1];
		
		//先扣钱,一次开5个，初始是25，递增是25
		$curGridNum = $this->mBagList[$bagName]->getMaxGridNum();
		$needGold = BagConf::BAG_UNLOCK_GRID * BagConf::BAG_UNLOCK_GOLD
		 + ($curGridNum - $initGridNum) * BagConf::BAG_UNLOCK_GOLD_STEP; 
		
		$userObj = EnUser::getUserObj();
		$costType = BagDef::$BAG_OPEN_GRID[$bagType][2];
		if ( $userObj->subGold($needGold, $costType) == false )
		{
			throw new FakeException('no enough gold');
		}
		
		//再开格子
		$this->mBagList[$bagName]->openGrid(BagConf::BAG_UNLOCK_GRID); 

		//保存
		$userObj->update();
		$this->update();	

		return 'ok';
	}

	/* (non-PHPdoc)
	 * @see IBag::openGridByItem()
	 */
	public function openGridByItem($gridNum, $bagType)
	{
		$gridNum = intval($gridNum);

		//输入参数是否合法
		if ( $gridNum <= 0 || $gridNum != BagConf::BAG_UNLOCK_GRID)
		{
			throw new FakeException('invalid gridNum:%d, gridNum must be %d!', $gridNum, BagConf::BAG_UNLOCK_GRID);
		}
		
		if (!key_exists($bagType, BagDef::$BAG_OPEN_GRID)) 
		{
			throw new FakeException('invalid bagType:%d', $bagType);
		}
		
		$bagName = BagDef::$BAG_OPEN_GRID[$bagType][0];

		if ( $this->deleteItembyTemplateID(BagConf::BAG_UNLOCK_ITEM_ID, 1) == false )
		{
			throw new FakeException('delete unlock item failed');
		}

		$this->mBagList[$bagName]->openGrid(BagConf::BAG_UNLOCK_GRID); 

		//更新背包数据
		$this->update();
		
		return 'ok';
	}
	
	public function getBestItems($itemType = ItemDef::ITEM_TYPE_ARM)
	{
		$arrRet = array();
		$bagName = ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
		
		//先把物品信息都准备好
		$itemIds = array_merge($this->mBagList[$bagName]->getAllData(),
				$this->mBagTmp->getAllData());
		$this->mManager->getItems($itemIds);
		
		$bagInfo = $this->mBagList[$bagName]->getBagInfo();
		//临时背包中可能有装备背包中的数据
		$tmpBagInfo = $this->mBagTmp->getBagInfo();
		if(count($tmpBagInfo))
		{
			Logger::warning('there %d item in tmp bag', count($tmpBagInfo));
			foreach ($tmpBagInfo as $gid => $itemInfo)
			{
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				if ($item->getItemType() == $itemType)
				{
					$bagInfo[$gid] = $itemInfo;
				}
			}
		}
		
		//按物品的具体类型分类，获得itemObject
		$arrItem = array();
		foreach ($bagInfo as $gid => $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$item = $this->mManager->getItem($itemId);
			if ($itemType == ItemDef::ITEM_TYPE_TREASURE
			&& $item->isNoAttr())
			{
				continue;
			}
			$type = $item->getType();
			$arrItem[$type][] = $item;
		}
		//排序
		foreach ($arrItem as $type => $items)
		{
			$sort = array();
			$sortArray = array();
			foreach ($items as $item)
			{
				$score = $item->getScore();
				$level = $item->getLevel();
				$itemId = $item->getItemID();
				switch ($itemType)
				{
					//宝物的排序规则是使用评分
					case ItemDef::ITEM_TYPE_TREASURE:
						$sort = array(
								'score' => SortByFieldFunc::DESC,
						);
						$sortArray[] = array(
								'score' => $score,
								'id' => $itemId,
						);
						break;
					//装备和战魂的排序规则是使用评分和等级
					case ItemDef::ITEM_TYPE_ARM:
					case ItemDef::ITEM_TYPE_FIGHTSOUL:
						$sort = array(
								'score' => SortByFieldFunc::DESC,
								'level' => SortByFieldFunc::DESC,
						);
						$sortArray[] = array(
								'score' => $score,
								'level' => $level,
								'id' => $itemId,
						);
						break;
					default:
						throw new FakeException('invalid item type:%d', $itemType);
				}
			}
			$sortCmp = new SortByFieldFunc($sort);
			usort($sortArray, array($sortCmp, 'cmp'));
			$arrRet[$type] = $sortArray[0]['id'];
		}
		
		//如果是战魂物品，额外还需要排序一次
		if ($itemType == ItemDef::ITEM_TYPE_FIGHTSOUL) 
		{
			$sortArray = array();
			foreach ($arrRet as $type => $itemId)
			{
				$item = $this->mManager->getItem($itemId);
				$sortArray[] = array(
						'score' => $item->getScore(),
						'sort' => $item->getSort(),
						'level' => $item->getLevel(),
						'id' => $item->getItemID(),
				);
			}
			//按quality降序，sort升序
			$sortCmp = new SortByFieldFunc(
					array('score' => SortByFieldFunc::DESC,
						  'sort' => SortByFieldFunc::ASC,
						  'level' => SortByFieldFunc::DESC));
			usort($sortArray, array($sortCmp, 'cmp'));
			$oldRet = $arrRet;
			$arrRet = array();
			foreach ($sortArray as $key => $array)
			{
				$itemId = $array['id'];
				$type = array_search($itemId, $oldRet);
				$arrRet[$type] = $itemId;
			}
		}
		
		return $arrRet;
	}
	
	public function getBestQuality($itemType = ItemDef::ITEM_TYPE_ARM)
	{
		$arrRet = array();
		$bagName = ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
		
		//先把物品信息都准备好
		$itemIds = array_merge($this->mBagList[$bagName]->getAllData(),
				$this->mBagTmp->getAllData());
		$this->mManager->getItems($itemIds);
		
		$bagInfo = $this->mBagList[$bagName]->getBagInfo();
		//临时背包中可能有装备背包中的数据
		$tmpBagInfo = $this->mBagTmp->getBagInfo();
		if(count($tmpBagInfo))
		{
			Logger::warning('there %d item in tmp bag', count($tmpBagInfo));
			foreach ($tmpBagInfo as $gid => $itemInfo)
			{
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				if ($item->getItemType() == $itemType)
				{
					$bagInfo[$gid] = $itemInfo;
				}
			}
		}
		
		$quality = 0;
		foreach ($bagInfo as $itemInfo)
		{
			$itemTplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
			$itemQuality = $this->mManager->getItemQuality($itemTplId);
			$quality = $quality >= $itemQuality ? $quality : $itemQuality;
		}
		return $quality;
	}
	
	public function getBestLevel($itemType = ItemDef::ITEM_TYPE_ARM)
	{
		$arrRet = array();
		$bagName = ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
	
		//先把物品信息都准备好
		$itemIds = array_merge($this->mBagList[$bagName]->getAllData(),
				$this->mBagTmp->getAllData());
		$this->mManager->getItems($itemIds);
	
		$bagInfo = $this->mBagList[$bagName]->getBagInfo();
		//临时背包中可能有装备背包中的数据
		$tmpBagInfo = $this->mBagTmp->getBagInfo();
		if(count($tmpBagInfo))
		{
			Logger::warning('there %d item in tmp bag', count($tmpBagInfo));
			foreach ($tmpBagInfo as $gid => $itemInfo)
			{
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				if ($item->getItemType() == $itemType)
				{
					$bagInfo[$gid] = $itemInfo;
				}
			}
		}
	
		$level = 0;
		foreach ($bagInfo as $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$item = $this->mManager->getItem($itemId);
			$itemLevel = $item->getLevel();
			$level = $level >= $itemLevel ? $level : $itemLevel;
		}
		return $level;
	}
	
	public function getBestEvolve()
	{
		$arrRet = array();
		$itemType = ItemDef::ITEM_TYPE_TREASURE;
		$bagName = ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
	
		//先把物品信息都准备好
		$itemIds = array_merge($this->mBagList[$bagName]->getAllData(),
				$this->mBagTmp->getAllData());
		$this->mManager->getItems($itemIds);
	
		$bagInfo = $this->mBagList[$bagName]->getBagInfo();
		//临时背包中可能有装备背包中的数据
		$tmpBagInfo = $this->mBagTmp->getBagInfo();
		if(count($tmpBagInfo))
		{
			Logger::warning('there %d item in tmp bag', count($tmpBagInfo));
			foreach ($tmpBagInfo as $gid => $itemInfo)
			{
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				if ($item->getItemType() == $itemType)
				{
					$bagInfo[$gid] = $itemInfo;
				}
			}
		}
	
		$evolve = 0;
		foreach ($bagInfo as $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$item = $this->mManager->getItem($itemId);
			$itemEvolve = $item->getEvolve();
			$evolve = $evolve >= $itemEvolve ? $evolve : $itemEvolve;
		}
		return $evolve;
	}

	/**
	 *
	 * 增加物品(批量)
	 *
	 * @param array(int) $itemIds		物品IDs
	 * @param boolean $inTmpBag			是否添加到临时背包中,DEFAULT=false
	 *
	 * @return boolean
	 */
	public function addItems($itemIds, $inTmpBag = false)
	{
		$itemTypes = array();
		$items = $this->mManager->getItems($itemIds);
		foreach ($items as $item)
		{
			$itemType = $item->getItemType();
			$itemTypes[$itemType] = 0;
		}
		
		if(!$inTmpBag && $this->checkFull($itemTypes))
		{
			Logger::warning('itemIds:%s', $itemIds);
			return false;
		}
		
		foreach ($itemIds as $itemId)
		{
			if ( $this->addItem($itemId, true) == false )
			{
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * 增加物品
	 *
	 * @param int $itemId			物品ID
	 * @param boolean $inTmpBag		是否添加到临时背包中,DEFAULT=false
	 *
	 * @return boolean
	 */
	public function addItem($itemId, $inTmpBag = false)
	{
		$item = $this->mManager->getItem($itemId);
		//如果物品不存在，直接返回false
		if ( $item === NULL )
		{
			return false;
		}
		
		$itemType = $item->getItemType();
		$bagName = self::getBagNameByItemType($itemType);
		
		if(!$inTmpBag && $this->isFull($bagName))
		{
			Logger::warning('add to bag:%s failed, is full. item:%d', $bagName, $itemId);
			return false;
		}
		
		$ret = $this->mBagList[$bagName]->addItem($itemId);
		
		if( $ret == false )
		{			
			Logger::trace('add to bag:%s failed, try add to tmp bag', $bagName);
			$ret = $this->mBagTmp->addItem($itemId);
		}
		
		//理论上按照现在的逻辑，是不太可能出现失败的
		if( $ret == false)
		{
			Logger::fatal('add item failed. item id:%d, type:%d, uid:%d', $itemId, $itemType, $this->mUid);
		}
		
		return $ret;		
	}

	/**
	 *
	 * 移除物品(从背包里移除)
	 *
	 * @param int $itemId
	 * @return boolean
	 */
	public function removeItem( $itemId )
	{
		list($bagName, $gid) = $this->findItemByItemId($itemId);
		
		if($gid == BagDef::INVALID_GRID_ID)
		{
			Logger::warning('not found itemId:%d, uid:%d', $itemId, $this->mUid);
			return false;
		}
		$ret = $this->mBagList[$bagName]->removeItemByGid($gid);
		
		Logger::debug('remove item:%d from bag:%s', $itemId, $bagName);
		
		return $ret;
	}

	/**
	 *
	 * 删除物品(直接从系统中删除)
	 *
	 * @param int $itemId
	 * @param int $inTmpBag
	 *
	 * @return boolean
	 */
	public function deleteItem( $itemId )
	{
		list($bagName, $gid) = $this->findItemByItemId($itemId);
		
		if($gid == BagDef::INVALID_GRID_ID)
		{
			Logger::warning('not found itemId:%d, uid:%d', $itemId, $this->mUid);
			return false;
		}
		
		if ( $this->mManager->deleteItem($itemId) == false )
		{
			Logger::warning('delete from manager failed. itemId:%d, uid:%d', $itemId, $this->mUid);
			return false;
		}
				
		$ret = $this->mBagList[$bagName]->removeItemByGid($gid);
		
		Logger::debug('delete item:%d from bag:%s', $itemId, $bagName);
		
		return $ret;
		
	}

	/**
	 *
	 * 减少物品
	 *
	 * @param int $itemId					物品id
	 * @param int $itemNum					物品数量
	 *
	 * @return boolean
	 *
	 */
	public function decreaseItem($itemId, $itemNum)
	{
		list($bagName, $gid) = $this->findItemByItemId($itemId);
				
		if ( $gid == BagDef::INVALID_GRID_ID )
		{
			Logger::trace('cant find itemId:%d', $itemId);
			return false;
		}

		if ( $this->mManager->decreaseItem($itemId, $itemNum) == false )
		{
			Logger::warning('decreaseItem from manager failed. itemId:%d, uid:%d', $itemId, $this->mUid);
			return false;
		}

		//如果物品的数量刚好减到0， 就会被删掉
		$item = $this->mManager->getItem($itemId);		
		if ( $item === NULL )
		{
			$ret = $this->mBagList[$bagName]->removeItemByGid($gid);
			if( $ret == false)
			{
				Logger::fatal('it cant be true. lost item:%d when remove it', $itemId);
			}
		}
		else
		{
			$this->mBagList[$bagName]->notifyModifyByGrid($gid);
		}
	
		return true;
	}

	/**
	 *
	 * 增加物品
	 * @param int $itemTemplateId 					物品模板ID
	 * @param int $itemNum							物品数量
	 * @param boolean $inTmpBag					是否添加到临时背包中，默认false
	 *
	 * @see 如果需要发送公告,则不该使用该函数
	 *
	 * @return boolean
	 */
	public function addItemByTemplateID($itemTemplateId, $itemNum, $inTmpBag = false)
	{
		$itemType = $this->mManager->getItemType($itemTemplateId);
		$bagName = $this->getBagNameByItemType($itemType);
		
		if(!$inTmpBag && $this->isFull($bagName))
		{
			Logger::warning('add by template failed, bag:%s is full. tplId:%d, num:%d', $bagName, $itemTemplateId, $itemNum);
			return false;
		}
		
		//如果是可叠加的物品，先尝试往背包中已有的物品上加
		$realAddNum = $this->mBagList[$bagName]->addItemTpl($itemTemplateId, $itemNum);
		if( $realAddNum >= $itemNum)
		{
			Logger::trace('all add to stackable item');
			return true;
		}
		$itemNum =  $itemNum - $realAddNum;
		
		$arrItemId = $this->mManager->addItem($itemTemplateId , $itemNum);
		return $this->addItems($arrItemId, true);
	}

	/**
	 *
	 * 增加物品(批量)
	 *
	 * @param array $items
	 * <code>
	 * [
	 * 		itemTemplateId :int => itemNum:int
	 * ]
	 * <code>
	 * @param boolean $inTmpBag					是否添加到临时背包中
	 *
	 * @see 如果需要发送公告,则不该使用该函数
	 *
	 * @return boolean
	 */
	public function addItemsByTemplateID($items, $inTmpBag = false)
	{
		$itemTypes = array();
		foreach ($items as $itemTemplateId => $itemNum)
		{
			$itemType = $this->mManager->getItemType($itemTemplateId);
			$itemTypes[$itemType] = 0;
		}
		
		if(!$inTmpBag && $this->checkFull($itemTypes))
		{
			Logger::warning('item tpl:%s', $items);
			return false;
		}
			
		if ( is_array($items) || get_class($items) == 'BtstoreElement' )
		{
			foreach ( $items as $itemTemplateId => $itemNum )
			{
				if ( $this->addItemByTemplateID($itemTemplateId , $itemNum, true) == false )
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 *
	 * 删除物品
	 *
	 * @param int $itemTemplateId 
	 * @param int $itemNum
	 *
	 * @return boolean
	 */
	public function deleteItembyTemplateID($itemTplId , $itemNum)
	{
		if ( $itemNum <= 0 )
		{
			return false;
		}
		$itemType = $this->mManager->getItemType($itemTplId);
		$bagName = self::getBagNameByItemType($itemType);
		
		//先把物品信息一次性拉过来
		$arrItemId = array_merge($this->mBagTmp->getAllData(), $this->mBagList[$bagName]->getAllData());
		$this->mManager->getItems($arrItemId);		
				
		//先从临时背包中删 
		$numInTmp = $this->mBagTmp->getItemNumByTemplateId($itemTplId);
		if($numInTmp > 0)
		{
			$delNumFromTmp = $numInTmp < $itemNum ? $numInTmp :  $itemNum;
			Logger::trace('delete templateId:%d from tmp bag', $delNumFromTmp);			
			$ret = $this->mBagTmp->deleteItembyTemplateID($itemTplId, $delNumFromTmp);
			if( ! $ret )
			{
				Logger::warning('delete templateId:%d from tmp bag failed', $delNumFromTmp);
				return false;
			}
			
			$itemNum -= $delNumFromTmp;
		}
		if($itemNum <= 0)
		{
			return true;
		}		
		
		$ret = $this->mBagList[$bagName]->deleteItembyTemplateID($itemTplId, $itemNum);
		
		return $ret;
	}

	/**
	 *
	 * 删除物品(批量)
	 *
	 * @param array $items
	 * <code>
	 * [
	 * 		itemTemplateId :int => itemNum:int
	 * ]
	 * <code>
	 *
	 * @return boolean
	 */
	public function deleteItemsByTemplateID($items)
	{
		if ( is_array($items) || get_class($items) == 'BtstoreElement' )
		{
			foreach ( $items as $itemTemplateId => $itemNum )
			{
				if ( $this->deleteItembyTemplateID($itemTemplateId , $itemNum) == false )
				{
					return false;
				}
			}
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 返回某个物品所在格子
	 * @param int $itemId
	 * @return int gid
	 */
	public function getGidByItemId($itemId)
	{
		list($bagName, $gid) = $this->findItemByItemId($itemId);
		
		return $gid;
	}
	
	/**
	 * 背包中是否有某个物品存在于背包
	 * @param int $itemId
	 * @return boolean
	 */
	public function isItemExist($itemId)
	{
		list($bagName, $gid) = $this->findItemByItemId($itemId);
		
		return $gid != BagDef::INVALID_GRID_ID;
	}
	
	protected function checkFull($itemTypes)
	{
		foreach ($itemTypes as $itemType => $value)
		{
			$bagName = self::getBagNameByItemType($itemType);
			if ($this->isFull($bagName) == true)
			{
				Logger::warning('add to bag:%s failed, which is full.', $bagName);
				return true;
			}
		}
		return false;
	}
	
	
	/**
	 * 背包是否满。 目前只有部分背包有满的状态
	 * @param string $bagName
	 * @return bool
	 */
	public function isFull($bagName = '')
	{
		if( empty($bagName) )
		{
			$flag = false;
			foreach (BagDef::$BAG_INIT_GRID as $bagName => $value)
			{
				$flag = $flag || $this->mBagList[$bagName]->isFull();
			}
			return $flag;
		}
			
		return $this->mBagList[$bagName]->isFull();
	}
	
	/**
	 * 根据物品模板id，判断对应背包是否满
	 */
	public function isFullByTemplate($arrTplId)
	{
		$itemTypes = array();
		foreach ($arrTplId as $tplId)
		{
			$itemType = $this->mManager->getItemType($tplId);
			$itemTypes[$itemType] = 0;
		}
		return $this->checkFull($itemTypes);
	}
	
	/**
	 *
	 * 得到某类物品的物品id组
	 *
	 * @param int $itemTplIdId
	 * @return int
	 */
	public function getItemIdsByTemplateID($itemTplId)
	{
		$itemType = $this->mManager->getItemType($itemTplId);
		$bagName = self::getBagNameByItemType($itemType);
	
		//先把物品信息一次性拉过来
		$arrItemId = array_merge($this->mBagTmp->getAllData(), $this->mBagList[$bagName]->getAllData());
		$arrItem = $this->mManager->getItems($arrItemId);
		
		//遍历过滤
		$arrItemId = array();
		foreach ($arrItem as $item)
		{
			if ($item !== NULL && $item->getItemTemplateID() == $itemTplId)
			{
				$arrItemId[] = $item->getItemID();
			}
		}
		Logger::debug('get itemIds:%s of item templateId:%d', $arrItemId, $itemTplId);
		return $arrItemId;
	}
	
	/**
	 *
	 * 得到某类物品的数量
	 *
	 * @param int $itemTemplateId 
	 *
	 * @return int
	 */
	public function getItemNumByTemplateID($itemTplId)
	{		
		$itemType = $this->mManager->getItemType($itemTplId);
		$bagName = self::getBagNameByItemType($itemType);
		
		//先把物品信息一次性拉过来
		$arrItemId = array_merge($this->mBagTmp->getAllData(), $this->mBagList[$bagName]->getAllData());
		$this->mManager->getItems($arrItemId);
				
		$numInTmp = $this->mBagTmp->getItemNumByTemplateId($itemTplId);			
		$numInNorm = $this->mBagList[$bagName]->getItemNumByTemplateId($itemTplId);
		
		Logger::debug('get num of item templateId:%d. numInNorm:%d, numInTmp:%d', $itemTplId, $numInNorm, $numInTmp);		
		return $numInTmp + $numInNorm;
	}

	/**
	 *
	 * 得到某类物品的ids
	 *
	 * @param int $itemType				物品类型
	 *
	 * @return array(int)					物品id数组
	 */
	public function getItemIdsByItemType($itemType)
	{		
		$bagName = self::getBagNameByItemType($itemType);
				
		$arrInTmp = $this->mBagTmp->getArrItemIdByItemType($itemType);			
		$arrInNorm = $this->mBagList[$bagName]->getArrItemIdByItemType($itemType);
		
		$arrItemId = array_merge($arrInTmp, $arrInNorm);
		
		Logger::debug('get itemIds of type:%d. arrInNorm:%s, arrInTmp:%s', $itemType, $arrInNorm, $arrInTmp);		
		return $arrItemId;
	}
	
	/**
	 *
	 * 得到某类物品的模板ids
	 *
	 * @param int $itemType				物品类型
	 *
	 * @return array(int)					物品id数组
	 */
	public function getItemTplIdsByItemType($itemType)
	{
		$arrItemTplId = array();
		$bagName = ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
		
		//先把物品信息都准备好
		$itemIds = array_merge($this->mBagList[$bagName]->getAllData(),
				$this->mBagTmp->getAllData());
		$this->mManager->getItems($itemIds);
		
		$bagInfo = $this->mBagList[$bagName]->getBagInfo();
		$tmpBagInfo = $this->mBagTmp->getBagInfo();
		if(count($tmpBagInfo))
		{
			Logger::warning('there %d item in tmp bag', count($tmpBagInfo));
			foreach ($tmpBagInfo as $gid => $itemInfo)
			{
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				if ($item->getItemType() == $itemType)
				{
					$bagInfo[$gid] = $itemInfo;
				}
			}
		}
		$arrItemTplId = Util::arrayExtract($bagInfo, ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID);
		return array_unique($arrItemTplId);
	}

	/**
	 *
	 * 掉落物品(批量)
	 *
	 * @param array(int) $arrDropTplId				掉落表模板IDs
	 * @param boolean $inTmpBag						是否放入临时背包
	 *
	 * @see 如果需要发送公告,则不该使用该函数
	 *
	 * @return boolean
	 */
	public function dropItems($arrDropTplId, $inTmpBag = false )
	{
		if(!$inTmpBag && $this->isFull())
		{
			Logger::warning('drop failed, bag is full. arrDropId:%s', $arrDropTplId);
			return false;
		}
				
		foreach ( $arrDropTplId as $dropTplId )
		{
			if ( $this->dropItem($dropTplId, true) == false )
			{
				return false;
			}
		}
		return true;
	}

	/**
	 *
	 * 掉落物品
	 *
	 * @param int $dropTplId				掉落表ID
	 * @param boolean $inTmpBag				是否添加到临时背包里
	 * @return boolean
	 */
	public function dropItem($dropTplId, $inTmpBag = false )
	{
		if(!$inTmpBag && $this->isFull())
		{
			Logger::warning('drop failed, bag is full. dropId:%d', $dropTplId);
			return false;
		}
		
		$itemIds = $this->mManager->dropItem($dropTplId);
		$deleted = false;
		foreach ($itemIds as $itemId)
		{
			if ( $deleted == false )
			{
				if ( $this->addItem($itemId, true) == false )
				{
					$this->mManager->deleteItem($itemId);
					$deleted = true;
				}
			}
			else
			{
				$this->mManager->deleteItem($itemId);
			}
		}
		return !$deleted;
	}

	/**
	 *
	 * 清空背包
	 *
	 */
	public function clearBag()
	{
		foreach( $this->mBagList as $bag)
		{
			$bag->clearBag();
		}
		return true;
	}

	/**
	 *
	 * 将脏数据回滚
	 */
	public function rollback()
	{
		$erro = false;
		foreach( $this->mBagList as $bag)
		{
			if($bag->rollback())
			{
				$erro = true;
			}
		}
		
		if($erro)
		{
			$this->mManager->rollback();
		}		
	}

	private function findItemByItemId($itemId)
	{
		foreach($this->mBagList as $bagName => $bag)
		{
			$gid = $bag->getGidByItemId($itemId);
			if( $gid != BagDef::INVALID_GRID_ID )
			{
				return array($bagName, $gid);
			} 
		}
		return array(NULL, BagDef::INVALID_GRID_ID);
	}

	/**
	 *
	 * 得到背包数据
	 *
	 * @return array
	 */
	private function getBagData()
	{
		$flag = true;
		$arrBagData = array();
		foreach (BagDef::$BAG_IN_SESSION as $key => $value)
		{
			$arrBagData[$key] = RPCContext::getInstance()->getSession($value);
			if (!isset($arrBagData[$key]))
			{
				$flag = false;
			}
		}
		
		//如果session中存在,则使用session中的数据
		if ($flag == true)
		{
			Logger::trace('get bag data from session');
			return $arrBagData;
		}
		
		foreach (BagDef::$BAG_IN_SESSION as $key => $value)
		{
			$arrBagData[$key] = array();
		}

		$select = array(BagDef::SQL_ITEM_ID, BagDef::SQL_GID);
		$where = array(BagDef::SQL_UID, '=', $this->mUid);
		$returnData = BagDAO::selectBag($select, $where);
		foreach ($returnData as $value)
		{
			$gid = intval($value[BagDef::SQL_GID]);
			$itemId = intval($value[BagDef::SQL_ITEM_ID]);
			
			$bagName = self::getBagNameByGid($gid);
			$arrBagData[$bagName][$gid] = $itemId;					
		}
		
		foreach (BagDef::$BAG_IN_SESSION as $key => $value)
		{
			RPCContext::getInstance()->setSession($value, $arrBagData[$key]);
		}
		
		return $arrBagData;
	}


	protected function checkTmpBag()
	{
		$arrGrd = $this->mBagTmp->getAllData();
		$this->mManager->getItems($arrGrd);
		$arrFullBag = array();
		foreach($arrGrd as $gid => $itemId)
		{
			if($itemId == ItemDef::ITEM_ID_NO_ITEM)
			{
				continue;
			}
			$item = $this->mManager->getItem($itemId);
			if ( $item === NULL )
			{
				$this->mBagTmp->removeItemByGid($gid);
				Logger::fatal('fixed invalid item! user:%d has invalid item:%d in gid:%d!',
					 $this->mUid , $itemId, $gid);
				continue;
			}
			$itemType = $item->getItemType();
			$bagName = $this->getBagNameByItemType($itemType);
			
			if( in_array($bagName, $arrFullBag) )
			{
				continue;
			}
			
			if( !$this->mBagListNoTmp[$bagName]->isFull() )
			{
				$ret = $this->mBagListNoTmp[$bagName]->addItem($itemId);
				if($ret)
				{
					$this->mBagTmp->removeItemByGid($gid);
					Logger::debug('move item:%d from %s to tmp bag', $itemId, $bagName);
				}
				else
				{
					Logger::fatal('impossible. move item:%d from %s to tmp bag failed', $itemId, $bagName);
				}							
			}
			else
			{
				$arrFullBag[] = $bagName;
			}
		}		
	}

	/**
	 *
	 * 更新背包数据
	 *
	 * @return @grid
	 */
	public function update()
	{
		//把临时背包中的东西尽量往其他背包中放
		$this->checkTmpBag();
		
		$modifyInfo = $this->mBagTmp->update();			
		foreach( $this->mBagListNoTmp as $bag )
		{
			$modifyInfo = $modifyInfo + $bag->update();
		}
		
		$this->mManager->update();
		$arrAddItemId = $this->mManager->getArrAddItemId();
		
		foreach (BagDef::$BAG_IN_SESSION as $key => $value)
		{
			RPCContext::getInstance()->setSession($value, $this->mBagList[$key]->getAllData());
		}
		
		if( !empty($modifyInfo) )
		{
			RPCContext::getInstance()->sendMsg(array($this->mUid), BagDef::FRONT_CALLBACK_UPDATE, array($modifyInfo));
			$arrItemId = array();
			foreach ($modifyInfo as $gid => $itemInfo)
			{
				if (empty($itemInfo))
				{
					continue;
				}
				$arrItemId[] = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			}
			$this->mManager->getItems($arrItemId);
			$modify = array();
			foreach ($modifyInfo as $gid => $itemInfo)
			{
				if (empty($itemInfo)) 
				{
					continue;
				}
				$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
				$item = $this->mManager->getItem($itemId);
				if ($item == NULL) 
				{
					continue;
				}
				$itemType = $item->getItemType();
				if ($itemType == ItemDef::ITEM_TYPE_GODWEAPON && $item->isExpStone()) 
				{
					continue;
				}
				$itemQuality = $item->getItemQuality();
				$modify[$itemType]['item'][] = $item->getItemTemplateID();
				if (!isset($modify[$itemType]['quality'])) 
				{
					$modify[$itemType]['quality'] = 0;
				}
				$modify[$itemType]['quality'] = max($modify[$itemType]['quality'], $itemQuality);
				if (in_array($itemId, $arrAddItemId))
				{
					if (!isset($modify[$itemType]['num'][$itemQuality]))
					{
						$modify[$itemType]['num'][$itemQuality] = 0;
					}
					$modify[$itemType]['num'][$itemQuality] += $item->getItemNum();
				}
				else 
				{
					$modify[$itemType]['num'][$itemQuality] = 0;
				}
			}
			if (!empty($modify[ItemDef::ITEM_TYPE_ARM])) 
			{
				ItemInfoLogic::updateArmBook($modify[ItemDef::ITEM_TYPE_ARM]['item']);
				EnAchieve::updateEquipColor($this->mUid, $modify[ItemDef::ITEM_TYPE_ARM]['quality']);
			}
			if (!empty($modify[ItemDef::ITEM_TYPE_TREASURE])) 
			{
				ItemInfoLogic::updateTreasBook($modify[ItemDef::ITEM_TYPE_TREASURE]['item']);
				EnAchieve::updateEquipSuit($this->mUid, $modify[ItemDef::ITEM_TYPE_TREASURE]['quality']);
				foreach ($modify[ItemDef::ITEM_TYPE_TREASURE]['num'] as $quality => $num)
				{
					if ($quality == ItemDef::ITEM_QUALITY_BLUE) 
					{
						EnNewServerActivity::updateBlueTreasure($this->mUid, $num);
					}
					if ($quality == ItemDef::ITEM_QUALITY_PURPLE) 
					{
						EnNewServerActivity::updatePurpleTreasure($this->mUid, $num);
					}
				}
			}
			if (!empty($modify[ItemDef::ITEM_TYPE_GODWEAPON])) 
			{
				ItemInfoLogic::updateGodWeaponBook($modify[ItemDef::ITEM_TYPE_GODWEAPON]['item']);
				EnAchieve::updateGodWeaponQuality($this->mUid, $modify[ItemDef::ITEM_TYPE_GODWEAPON]['quality']);
				foreach ($modify[ItemDef::ITEM_TYPE_GODWEAPON]['num'] as $quality => $num)
				{
					EnAchieve::updateGodWeaponNum($this->mUid, $quality, $num);
				}
				foreach ($modify[ItemDef::ITEM_TYPE_GODWEAPON]['item'] as $itemTplId)
				{
					EnAchieve::updateGodWeaponKind($this->mUid, $itemTplId);
				}
			}
			if (!empty($modify[ItemDef::ITEM_TYPE_DRESS])) 
			{
				foreach ($modify[ItemDef::ITEM_TYPE_DRESS]['item'] as $itemTplId)
				{
					EnDressRoom::getNewDress($itemTplId);
				}
				EnAchieve::updateDressNum($this->mUid, array_sum($modify[ItemDef::ITEM_TYPE_DRESS]['num']));
			}
			if (!empty($modify[ItemDef::ITEM_TYPE_TALLY])) 
			{
				ItemInfoLogic::updateTallyBook($modify[ItemDef::ITEM_TYPE_TALLY]['item']);
			}
			if (!empty($modify[ItemDef::ITEM_TYPE_CHARIOT]))
			{
				ItemInfoLogic::updateChariotBook($modify[ItemDef::ITEM_TYPE_CHARIOT]['item']);
			}
		}
		
		return $modifyInfo;
	}
	
	public static function getBagNameByItemType($itemType)
	{
		if( isset( ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType] ) )
		{
			return ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
		}		
		
		throw new InterException('itemType:%d not in any bag', $itemType);
	}
	
	public static function getBagNameByGid($gid)
	{
		if( $gid >= BagDef::GRID_END)
		{
			throw new InterException('invalid grid:%d', $gid);
		}
		
		$arrGridStart = array_reverse(BagDef::$BAG_GRID_START, true);
		
		foreach ($arrGridStart as $bagName => $gridStart)
		{
			if ($gid >= $gridStart) 
			{
				return $bagName;
			}
		}
			
		throw new InterException('invalid grid:%d', $gid);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */