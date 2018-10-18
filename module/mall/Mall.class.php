<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mall.class.php 243296 2016-05-17 13:18:20Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mall/Mall.class.php $
 * @author $Author: GuohaoZheng $(tianming@babeltime.com)
 * @date $Date: 2016-05-17 13:18:20 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243296 $
 * @brief 
 *  
 **/
class Mall
{
	protected $uid = 0;
	protected $type = 0;
	protected $data = NULL;
	protected $dataModify = NULL;
	//默认的金币统计类型
	public $addGoldType = 0;
	public $subGoldType = 0;
	
	/**
	 * 构造函数
	 */
	public function __construct($uid, $type, 
		$subGoldType = StatisticsDef::ST_FUNCKEY_MALL_EXCHANGE_COST,
		$addGoldType = StatisticsDef::ST_FUNCKEY_MALL_EXCHANGE_GET)
	{
		// 非用户当前线程，报错
		if ($uid != RPCContext::getInstance()->getUid())
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		// 非有效商城类型，报错
		if (!in_array($type, MallDef::$MALL_VALID_TYPES))
		{
			throw new FakeException('Mall type:%s is not valid!', $type);
		}
		
		$this->uid = $uid;
		$this->type = $type;
		$this->subGoldType = $subGoldType;
		$this->addGoldType = $addGoldType;
	}
	
	public function loadData()
	{
		if ($this->dataModify === null) 
		{
			$data = MallDao::select($this->uid, $this->type);
			$this->data = $data;
			$this->dataModify = $data;
		}
	}
	
	public function refreshData()
	{
		foreach ($this->dataModify[MallDef::ALL] as $exchangeId => $info)
		{
			$num = $this->getExchangeNum($exchangeId);
			//数量为0就清除商品数据
			if (empty($num)) 
			{
				unset($this->dataModify[MallDef::ALL][$exchangeId]);
			}
		}
	}
	
	public function updateData()
	{
		//目前只能在自己的连接中改自己的数据
		$uid = RPCContext::getInstance()->getUid();
		if($uid != $this->uid)
		{
			throw new InterException('Cant update data in other user connection. uid:%d in session, this uid:%d', $uid, $this->uid);
		}
	
		if ($this->dataModify != $this->data)
		{
			$arrField = array(
					MallDef::USER_ID => $this->uid,
					MallDef::MALL_TYPE => $this->type,
					MallDef::VA_MALL => $this->dataModify,
			);
			MallDao::insertOrUpdate($arrField);
		}
		$this->data = $this->dataModify;
		EnUser::getUserObj($this->uid)->update();
		BagManager::getInstance()->getBag($this->uid)->update();
	}
	
	/**
	 * 会刷新商品的购买次数和时间
	 * 
	 * @param int $exchangeId
	 */
	public function getExchangeNum($exchangeId)
	{	
		$this->loadData();
		$time = $this->getExchangeTime($exchangeId);
		
		if (empty($time)) 
		{
			$this->setExchangeNum($exchangeId, 0);
		}
		else 
		{
			//如果是每日刷新类型，需要重置购买次数和时间
			if (in_array($this->type, MallDef::$EVERYDAY_REFRESH_TYPES))
			{
				$exchangeConf = $this->getExchangeConf($exchangeId);
				//修复商品id被策划删除的问题
				if(empty($exchangeConf))
				{
					Logger::warning('not found conf of id:%d, remove it', $exchangeId);
					$this->setExchangeNum($exchangeId, 0);
					return 0;
				}
				$exchangeType = MallDef::REFRESH_EVERYDAY;
				if (!empty($exchangeConf[MallDef::MALL_EXCHANGE_TYPE]))
				{
					//军团商店的军团共享商品的每日刷新类型是4，从不刷新类型是5
					$confType = $exchangeConf[MallDef::MALL_EXCHANGE_TYPE];
					if ($this->type == MallDef::MALL_TYPE_GUILD)
					{
						$exchangeType = $confType <= 3 ? $confType : $confType - 3;
					}
					else 
					{
						$exchangeType = $confType;
					}
				}
				$exchangeOffset = MallDef::REFRESH_OFFSET;
				if (!empty($exchangeConf[MallDef::MALL_EXCHANGE_OFFSET])) 
				{
					$exchangeOffset = $exchangeConf[MallDef::MALL_EXCHANGE_OFFSET];
				}
				if (MallDef::REFRESH_EVERYDAY == $exchangeType
				&& !Util::isSameDay($time, $exchangeOffset))
				{
					$this->setExchangeNum($exchangeId, 0);
					$this->setExchangeTime($exchangeId, 0);
				}
			}
			
			//如果是每周刷新类型，需要重置购买次数和时间
			if (in_array($this->type, MallDef::$EVERYWEEK_REFRESH_TYPES))
			{
				$exchangeConf = $this->getExchangeConf($exchangeId);
				//修复商品id被策划删除的问题
				if(empty($exchangeConf))
				{
					Logger::warning('not found conf of id:%d, remove it', $exchangeId);
					$this->setExchangeNum($exchangeId, 0);
					return 0;
				}
				$exchangeType = MallDef::REFRESH_EVERYWEEK;
				if (!empty($exchangeConf[MallDef::MALL_EXCHANGE_TYPE]))
				{
					$exchangeType = $exchangeConf[MallDef::MALL_EXCHANGE_TYPE];
				}
				if (MallDef::REFRESH_EVERYWEEK == $exchangeType
				&& !Util::isSameWeek($time))
				{
					$this->setExchangeNum($exchangeId, 0);
					$this->setExchangeTime($exchangeId, 0);
				}
			}
			
			//如果是活动刷新类型，需要判断是否在活动时间，然后再重置购买次数和时间
			if (in_array($this->type, MallDef::$ACTIVITY_REFRESH_TYPES))
			{
				if ($this->isInCurRound($time) == false)
				{
					$this->setExchangeNum($exchangeId, 0);
					$this->setExchangeTime($exchangeId, 0);
				}
			}
		}
	
		return $this->dataModify[MallDef::ALL][$exchangeId][MallDef::NUM];
	}
	
	public function setExchangeNum($exchangeId, $num)
	{
		$this->dataModify[MallDef::ALL][$exchangeId][MallDef::NUM] = $num;
	}
	
	public function getExchangeTime($exchangeId)
	{
		if (!isset($this->dataModify[MallDef::ALL][$exchangeId][MallDef::TIME])) 
		{
			$this->setExchangeTime($exchangeId, 0);
		}
		return $this->dataModify[MallDef::ALL][$exchangeId][MallDef::TIME];
	}
	
	public function setExchangeTime($exchangeId, $time)
	{
		$this->dataModify[MallDef::ALL][$exchangeId][MallDef::TIME] = $time;
	}
	
	public function getInfo()
	{	
		$this->loadData();
		if (empty($this->dataModify)) 
		{
			return $this->dataModify;
		}
		$this->refreshData();
		return $this->dataModify[MallDef::ALL];
	}
	
	/**
	 * 兑换
	 * 需求：用户等级，用户vip，限制次数，金币，银币，将魂，魂玉，声望，物品，特殊物品
	 * 获得：金币，银币，将魂，武将，物品，掉落物品
	 * 其他种类都在子类的subExtra里面操作
	 * 物品，武将，其他都是整型
	 * 
	 * @param int $type 商城类型
	 * @param int $exchangeId
	 * @param int $num
	 * @throws FakeException
	 * @throws InterException
	 * @return string
	 */
	public function exchange($exchangeId, $num = 1)
	{
		if (empty($exchangeId) || $num <= 0) 
		{
			throw new FakeException('Invalid para, exchangeId:%d num:%d!', $exchangeId, $num);
		}
		
		$exchangeConf = $this->getExchangeConf($exchangeId);
		if(empty($exchangeConf))
		{
		    throw new FakeException('exchangeId %d conf is empty.',$exchangeId);
		}
		$exchangeReq = $exchangeConf[MallDef::MALL_EXCHANGE_REQ];
		$exchangeAcq = $exchangeConf[MallDef::MALL_EXCHANGE_ACQ];
		
		if (empty($exchangeReq) && empty($exchangeAcq)) 
		{
			throw new ConfigException('Mall:%s exchangeId:%d exchange info is empty!', $this->type, $exchangeId);
		}
		
		$flag = false;
		$user = EnUser::getUserObj($this->uid);
		$bag = BagManager::getInstance()->getBag($this->uid);
		
		$addGoldNum = 0;//记录金币变化，后面做金币统计用
		$subGoldNum = 0;
		
		//需要
		if (!empty($exchangeReq)) 
		{	
			//需要用户等级,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_LEVEL]))
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_LEVEL];
				if ($user->getLevel() < $need)
				{
					throw new FakeException('buy %d need user level:%d', $exchangeId, $need);
				}
			}
			//需要用户VIP,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_VIP]))
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_VIP];
				if ($user->getVip() < $need)
				{
					throw new FakeException('buy %d need user vip:%s', $exchangeId, $need);
				}
			}
			//限制次数,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_NUM]))
			{
				$flag = true;
				$exchangeNum = $this->getExchangeNum($exchangeId);
				$limit = $exchangeReq[MallDef::MALL_EXCHANGE_NUM];
				if ($limit < $exchangeNum + $num) 
				{
					throw new FakeException('buy %d no enough num:%d, limit:%d', $exchangeId, $num, $limit);
				}
			}
			//需要金币,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_GOLD]) || !empty($exchangeReq[MallDef::MALL_EXCHANGE_INCRE]))
			{
				$base = 0;
				if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_GOLD]))
				{
					$base = $exchangeReq[MallDef::MALL_EXCHANGE_GOLD] * $num;
				}
				$add = 0;
				//是否递增,数组：递增数量 =>递增上限
				if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_INCRE]))
				{
					$flag = true;
					$exchangeNum = $this->getExchangeNum($exchangeId);
					$increArr = $exchangeReq[MallDef::MALL_EXCHANGE_INCRE];
					$incre = key($increArr);
					$maxIncre = current($increArr) - $base / $num;
					for ($i = 0; $i < $num; $i++)
					{
						$increSum = ($i + $exchangeNum) * $incre;
						if ($increSum <= $maxIncre) 
						{
							$add += $increSum;
						}
						else 
						{
							$add += $maxIncre;
						}
					}
				}
				$discount = 1;
				//商品是否有vip折扣
				if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_DISCOUNT]))
				{
					$discount = $exchangeReq[MallDef::MALL_EXCHANGE_DISCOUNT][$user->getVip()] * 0.0001;
				}
				$need = ceil(($base + $add) * $discount);
				$subGoldNum += $need;
				if ( $user->subGold($need, 0) == false)//金币统计在后面单独处理
				{
					throw new FakeException('buy %d no enough gold:%d', $exchangeId, $need);
				}
			}
			//需要银币,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_SILVER]))
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_SILVER] * $num;
				if ( $user->subSilver($need) == false )
				{
					throw new FakeException('buy %d no enough silver:%d', $exchangeId, $need);
				}
			}	
			//需要将魂,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_SOUL])) 
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_SOUL] * $num;
				if ( $user->subSoul($need) == false) 
				{
					throw new FakeException('buy %d no enough soul:%d', $exchangeId, $need);
				}
			}
			//需要魂玉,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_JEWEL]))
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_JEWEL] * $num;
				if ( $user->subJewel($need) == false)
				{
					throw new FakeException('buy %d no enough jewel:%d', $exchangeId, $need);
				}
			}
			//需要声望,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_PRESTIGE]))
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_PRESTIGE] * $num;
				if ( $user->subPrestige($need) == false)
				{
					throw new FakeException('buy %d no enough prestige:%d', $exchangeId, $need);
				}
			}
			//需要武将精华,int
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_JH]))
			{
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_JH] * $num;
				if ( $user->subJH($need) == false)
				{
					throw new FakeException('buy %d no enough jh:%d', $exchangeId, $need);
				}
			}
			//需要物品，数组：itemId => itemNum
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_ITEM])) 
			{
				$items = array();
				$need = $exchangeReq[MallDef::MALL_EXCHANGE_ITEM];
				foreach ( $need as $key => $value )
				{
					$items[$key] = $value * $num;
				}
				if ( $bag->deleteItemsByTemplateID($items) == false )
				{
					throw new FakeException('buy %d need item:%s', $exchangeId, $items);
				}
			}
			//需要特殊物品
			if (!empty($exchangeReq[MallDef::MALL_EXCHANGE_EXTRA]))
			{
				if ($this->subExtra($exchangeId, $num) == false)
				{
					throw new FakeException('buy %d no enough extra!', $exchangeId);
				}		
			}		
		}
		$drop = array();
		//获得
		if (!empty($exchangeAcq)) 
		{
			//获得金币,int
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_GOLD]))
			{
				$gain = $exchangeAcq[MallDef::MALL_EXCHANGE_GOLD] * $num;
				$addGoldNum += $gain;
				if ( $user->addGold($gain, 0) == false )//金币统计在后面单独处理
				{
					throw new InterException('add gold:%d failed', $gain);
				}
			}
			//获得银币,int
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_SILVER]))
			{
				$gain = $exchangeAcq[MallDef::MALL_EXCHANGE_SILVER] * $num;
				if ( $user->addSilver($gain) == false )
				{
					throw new InterException('add silver:%d failed', $gain);
				}
			}
			//获得将魂,int
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_SOUL]))
			{
				$gain = $exchangeAcq[MallDef::MALL_EXCHANGE_SOUL] * $num;
				if ( $user->addSoul($gain) == false )
				{
					throw new InterException('add soul:%d failed', $gain);
				}
			}
			//获得武将,数组：htid => hnum
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_HERO]))
			{
				if ($user->getHeroManager()->hasTooManyHeroes())
				{
					throw new FakeException('hero is full!');
				}
				$heros = array();
				$gain = $exchangeAcq[MallDef::MALL_EXCHANGE_HERO];
				foreach ($gain as $key => $value)
				{
					$heros[$key] =  $value * $num;
				}
				$user->getHeroManager()->addNewHeroes($heros);
				Logger::trace('add heros:%s', $heros);
			}
			//获得物品,数组：itemId => itemNum
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_ITEM]))
			{
				$items = array();
				$gain = $exchangeAcq[MallDef::MALL_EXCHANGE_ITEM];
				foreach ( $gain as $key => $value )
				{
					$items[$key] = $value * $num;
				}
				if ( $bag->addItemsByTemplateID($items) == false )
				{
					throw new FakeException('full bag. item tpls:%s', $items);
				}
			}
			//获得宝物碎片，fragId => fragNum
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_TREASFRAG])) 
			{
				$treasFrags = array();
				$gain = $exchangeAcq[MallDef::MALL_EXCHANGE_TREASFRAG];
				foreach ( $gain as $key => $value )
				{
					$treasFrags[$key] = $value * $num;
				}
				EnFragseize::addTreaFrag($this->uid, $treasFrags);
			}
			//获得掉落物品, int
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_DROP]))
			{
				$arrDropId = array();
				for ( $i = 0; $i < $num; $i++ )
				{
					$arrDropId[] = $exchangeAcq[MallDef::MALL_EXCHANGE_DROP];
				}
				$drop = EnUser::drop($user->getUid(), $arrDropId, false, false, true);
			}
			//获得特殊物品
			if (!empty($exchangeAcq[MallDef::MALL_EXCHANGE_EXTRA]))
			{
				if ($this->addExtra($exchangeId, $num) == false)
				{
					throw new FakeException('add extra failed. id:%d  ', $exchangeId);
				}
			}
		}
		
		if ($flag == true)
		{
			$this->setExchangeNum($exchangeId, $exchangeNum + $num);
			$this->setExchangeTime($exchangeId, Util::getTime());
		}
		
		$this->update();
		
		if( $addGoldNum > 0 )
		{
			Statistics::gold4Item($this->addGoldType, $addGoldNum, $exchangeId, $num, $user->getGold() );
		}
		if( $subGoldNum > 0 )
		{
			Statistics::gold4Item($this->subGoldType, -$subGoldNum, $exchangeId, $num, $user->getGold() );
			if ($this->type == MallDef::MALL_TYPE_SHOP) 
			{
			    $uid = RPCContext::getInstance()->getUid();
				EnNewServerActivity::updateProShopCostGold($uid, $subGoldNum);
			}
		}
		
		Logger::info('mall type:%d exchangeId:%d num:%d addGoldNum:%d subGoldNum:%d ', $this->type, $exchangeId, $num, $addGoldNum, $subGoldNum);
		
		
		return array(
				'ret' => 'ok',
				'drop' => $drop,
		);
	}
	
	public function isInCurRound($time)
	{
		//不要做user->update和bag->update
		Logger::FATAL("invoke fall down mall basic class! time=%d!", $time);
		return false;
	}
	
	public function getExchangeConf($exchangeId)
	{
		Logger::FATAL("invoke fall down mall basic class! exchangeId=%d!", $exchangeId);
		return false;
	}
	
	public function subExtra($exchangeId, $num)
	{
		//不要做user->update和bag->update
		Logger::FATAL("invoke fall down mall basic class! exchangeId=%d num=%d!", $exchangeId, $num);
		return false;
	}
	
	public function addExtra($exchangeId, $num)
	{
		//不要做user->update和bag->update
		Logger::FATAL("invoke fall down mall basic class! exchangeId=%d num=%d!", $exchangeId, $num);
		return false;
	}
	
	
	public function update()
	{
		$this->updateData();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */