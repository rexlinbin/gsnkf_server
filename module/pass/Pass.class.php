<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pass.class.php 259855 2016-09-01 05:04:30Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/Pass.class.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-01 05:04:30 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259855 $
 * @brief 
 *  
 **/

class Pass implements IPass
{
	private $uid = NULL;
	
	function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if( empty( $this->uid ) )
		{
			throw new FakeException( 'empty uid' );
		}
		
 		if( !EnSwitch::isSwitchOpen( SwitchDef::PASS ) )
		{
			throw new FakeException( 'switch not open, pass' );
		} 
	}
	/* (non-PHPdoc)
	 * @see IPass::enter()
	 */
	public function enter() 
	{
		$passInfo = PassLogic::enter( $this->uid );
		$passInfo['percentBase'] = PassCfg::FULL_PERCENT;
		
		if( !isset( $passInfo['va_pass'][PassDef::VA_UNION] ) )
		{
			$passInfo['va_pass'][PassDef::VA_UNION] = PassLogic::calculateUnion($this->uid, array());
		}
		else
		{
			$passInfo['va_pass'][PassDef::VA_UNION] = PassLogic::calculateUnion($this->uid, $passInfo['va_pass'][PassDef::VA_UNION]);
		}
		
		if( !empty( $passInfo['va_pass'][PassDef::VA_FORMATION] ) )
		{
			for ($i = 0; $i < FormationDef::FORMATION_SIZD; $i++)
			{
			if ( !isset( $passInfo['va_pass'][PassDef::VA_FORMATION][$i] ) )
			{
			$passInfo['va_pass'][PassDef::VA_FORMATION][$i] = 0;
			}
		
			}
		}
		
		if( !empty( $passInfo['va_pass'][PassDef::VA_BENCH] ) )
		{
			for ($i = 0; $i < PassCfg::VICE_NUM; $i++)
			{
				if ( !isset( $passInfo['va_pass'][PassDef::VA_BENCH][$i] ) )
				{
					$passInfo['va_pass'][PassDef::VA_BENCH][$i] = 0;
				}
		
			}
		}
		
		if( !empty( $passInfo['va_pass'][PassDef::VA_HEROINFO] ) )
		{
			foreach (  $passInfo['va_pass'][PassDef::VA_HEROINFO] as $hid => $info)
			{
				$passInfo['va_pass'][PassDef::VA_HEROINFO][$hid] = PassLogic::securityUnset( $info , PassDef::$unsetHeroField );
			}
				
		}
		
		if( empty($passInfo['va_pass'][PassDef::VA_SWEEPINFO]))
		{
			$passInfo['va_pass'][PassDef::VA_SWEEPINFO] = array(
				'count' => 0,
				'isSweeped' => false,
				'buyChest' => 0,
				'buyBuff' => 0
			);
		}
		return $passInfo;
	}

 	/* (non-PHPdoc)
	 * @see IPass::setHero()
	*/
/*	public function setHero($hidArr) 
	{
		PassLogic::setHero( $this->uid, $hidArr );
	}
	 */
	/* (non-PHPdoc)
	 * @see IPass::getRankList()
	 */
	public function getRankList() 
	{
		/* if( PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			throw new FakeException( 'handsoff time' );
		}
		 */
		return PassLogic::getRankList( $this->uid );
	}

	/* (non-PHPdoc)
	 * @see IPass::getOpponentList()
	 */
	public function getOpponentList($id) 
	{
		if( PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			throw new FakeException( 'handsoff time' );
		}
		PassLogic::checkBase( $this->uid, $id );
		$ret = PassLogic::getOpponentList( $this->uid, $id );
		foreach ( $ret as $degree => $info )
		{
			if( isset( $info['fightForce'] ) )
			{
				$ret[$degree]['fightForce'] = intval( $info['fightForce'] );
			}
		}
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see IPass::dealChest()
	 */
	public function dealChest($id, $isLuxury, $num = 1) 
	{
		if( PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			throw new FakeException( 'handsoff time' );
		}
		if( $isLuxury != 0 && $isLuxury != 1 )
		{
			throw new FakeException( 'invalid para' );
		}
		PassLogic::checkBase( $this->uid, $id );
		return passlogic::dealChest( $this->uid, $id, $isLuxury, $num);
	}

	/* (non-PHPdoc)
	 * @see IPass::leaveLuxuryChest()
	 */
	public function leaveLuxuryChest($id) 
	{
		if( PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			throw new FakeException( 'handsoff time' );
		}
		PassLogic::checkBase( $this->uid, $id );
		passlogic::leaveLuxuryChest( $this->uid , $id );		
	}

	/* (non-PHPdoc)
	 * @see IPass::attack()
	 */
	public function attack( $id, $degree, $viceHidArr ) 
	{
		
		if( $degree != PassCfg::DEGREE_SIMPLE 
		&& $degree != PassCfg::DEGREE_NOMAL
		&& $degree != PassCfg::DEGREE_HARD )
		{
			throw new FakeException( 'invalid arg: %s', $degree );
		}
		if( PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			throw new FakeException( 'handsoff time' );
		}
		PassLogic::checkBase( $this->uid, $id);
		$ret = PassLogic::attack( $this->uid, $id, $degree, $viceHidArr );
		$ret['va_pass'] = PassLogic::securityUnset( $ret['va_pass'], PassDef::$unsetFieldInVaForFront);
		
		if( !empty( $ret['va_pass'][PassDef::VA_FORMATION] ) )
		{
			for ($i = 0; $i < FormationDef::FORMATION_SIZD; $i++)
			{
				if ( !isset( $ret['va_pass'][PassDef::VA_FORMATION][$i] ) )
				{
					$ret['va_pass'][PassDef::VA_FORMATION][$i] = 0;
				}
		
			}
		}	

		if( !empty( $ret['va_pass'][PassDef::VA_BENCH] ) )
		{
			for ($i = 0; $i < PassCfg::VICE_NUM; $i++)
			{
				if ( !isset( $ret['va_pass'][PassDef::VA_BENCH][$i] ) )
				{
					$ret['va_pass'][PassDef::VA_BENCH][$i] = 0;
				}
		
			}
		}
		
		if( !empty( $ret['va_pass'][PassDef::VA_HEROINFO] ) )
		{
			foreach (  $ret['va_pass'][PassDef::VA_HEROINFO] as $hid => $info)
			{
				$ret['va_pass'][PassDef::VA_HEROINFO][$hid] = PassLogic::securityUnset( $info , PassDef::$unsetHeroField );
			}
				
		}
		
		if( !isset( $ret['va_pass'][PassDef::VA_UNION] ) )
		{
			throw new InterException( 'invalid' );//$passInfo['va_pass'][PassDef::VA_UNION] = PassLogic::calculateUnion($this->uid, array());
		}
		$ret['va_pass'][PassDef::VA_UNION] = PassLogic::calculateUnion($this->uid, $ret['va_pass'][PassDef::VA_UNION]);
		
		
		EnActive::addTask( ActiveDef::PASS );
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see IPass::dealBuff()
	 */
	public function dealBuff($id, $pos, $hidArr) 
	{
		if( ( $pos < 0 || $pos > 2 ) && $pos != PassDef::LEAVE_BUFF )
		{
			throw new FakeException( 'invalid para: %s', $pos );
		}
		if( PassLogic::isHandsOffTime( Util::getTime() ) )
		{
			throw new FakeException( 'handsoff time' );
		}
		PassLogic::checkBase( $this->uid, $id);
		PassLogic::dealBuff( $this->uid, $id, $pos, $hidArr );
	}
	
	/* (non-PHPdoc)
	 * @see IPass::setPassFormation()
	*/
	public function setPassFormation( $passFormation, $bench = null ) 
	{
		if( empty( $passFormation ) )
		{
			throw new FakeException( 'empty formation info' );
		}
		if( $bench === null )
		{
			Logger::fatal('para problem');
			return;
		}
		
		return PassLogic::setPassFormation( $this->uid, $passFormation, $bench );
	}
	
	
	/* (non-PHPdoc)
	 * @see IPass::getShopInfo()
	 */
	public function getShopInfo()
	{
		Logger::trace('Pass.getShopInfo begin...');
	
		$shop = new PassShop($this->uid);
		if ($shop->needSysRefresh())
		{
			$shop->refreshGoodsList(TRUE);
		}
	
		$shopInfo = $shop->getShopInfo();
		if(empty($shopInfo['goods_list']))
		{
			$shop->refreshGoodsList();
			$shopInfo = $shop->getShopInfo();
		}
		$shop->update();
	
		Logger::trace('Pass.getShopInfo ret[shopInfo:%s] end...', $shopInfo);
		return $shopInfo;
	}
	
	/* (non-PHPdoc)
	 * @see IPass::buyGoods()
	 */
	public function buyGoods($goodsId)
	{
		Logger::trace('Pass.buyGoods param[goodsId:%d] begin...', $goodsId);
	
		if(empty($goodsId))
		{
			throw new FakeException('Pass.buyGoods error params goodsId[%d]', $goodsId);
		}
	
		$shop = new PassShop($this->uid);
		$goodsList = $shop->getGoodsList();
		if(!in_array($goodsId, $goodsList))
		{
			throw new FakeException('Pass.buyGoods can not buy goodsId[%d] because not in goodsList[%s]', $goodsId, $goodsList);
		}
	
		$buyRet = $shop->exchange($goodsId);
		$shop->update();
	
		Logger::trace('Pass.buyGoods param[goodsId:%d] ret[buyRet:%s]end...', $goodsId, $buyRet);
		return $buyRet;
	}
	
	/* (non-PHPdoc)
	 * @see IPass::refreshGoodsList()
	 */
	public function refreshGoodsList($isSysRfr = FALSE)
	{
		Logger::trace('Pass.refreshGoodsList begin...');
	
		$shop = new PassShop($this->uid);
		
		if ($isSysRfr) 
		{
			if ($shop->needSysRefresh()) 
			{
				$shop->refreshGoodsList(TRUE);
			}
			else 
			{
				throw new FakeException('Pass.refreshGoodsList sys rfr request, but can not sys request');
			}
		}
		else 
		{
			$freeRfrNum = btstore_get()->PASS_SHOP[PassShopCsvTag::FREE_REFRESH] - $shop->getFreeRfrNum();
			if ($freeRfrNum  > 0)
			{
				// 如果剩余免费刷新次数不为0，则优先使用免费刷新
				$shop->freeRfrGoodsList();
			}
			else 
			{
				$shopConfig = btstore_get()->PASS_SHOP;
				$usrTotalRfrNum = $shop->getUsrRfrNum(PassDef::TYPE_RFR_GOLD) + $shop->getUsrRfrNum(PassDef::TYPE_RFR_STONE);
				$tatalLimit = intval($shopConfig[PassShopCsvTag::REFRESH_LIMIT]);
				if ($usrTotalRfrNum >= $tatalLimit)
				{
					throw new FakeException('Pass.refreshGoodsList usr refresh num[%d] reach limit[%d]', $usrTotalRfrNum, $tatalLimit);
				}
				
				// 先尝试使用“神兵刷新石”进行刷新
				$uid = RPCContext::getInstance()->getUid();
				$rfrStoneTemplId = intval($shopConfig[PassShopCsvTag::USR_REFRESH_STONE]['templ_id']);
				$rfrStoneCost = intval($shopConfig[PassShopCsvTag::USR_REFRESH_STONE]['cost_num']);
				$bag = BagManager::getInstance()->getBag($uid);
				$usrRfrStoneNum = $bag->getItemNumByTemplateID($rfrStoneTemplId);
				if($usrRfrStoneNum >= $rfrStoneCost)
				{
					Logger::trace("Pass.refreshGoodsList user $uid spent refresh-stone，cost: $rfrStoneCost.");
					if(!$bag->deleteItembyTemplateID($rfrStoneTemplId, $rfrStoneCost))
					{
						throw new InterException("Pass.refreshGoodsList failed, user $uid lack item $rfrStoneTemplId");
					}
					$bag->update();
					$shop->usrRfrGoodsList(PassDef::TYPE_RFR_STONE);
				}
				else 
				{
					Logger::trace("Pass.refreshGoodsList user $uid doesn't has enough refresh-stone, try to spend gold to refresh.");
					$usrRfrNum = $shop->getUsrRfrNum(PassDef::TYPE_RFR_GOLD);
			
					// 根据今天刷新次数得到刷新消耗金币
					$costConfig = btstore_get()->PASS_SHOP[PassShopCsvTag::USR_REFRESH_COST]->toArray();
					$index = 0;
					$needGold = 0;
					foreach ($costConfig as $num => $cost)
					{
						++$index;
						if ($usrRfrNum + 1 <= $num || $index == count($costConfig))
						{
							$needGold = intval($cost);
							break;
						}
					}
					Logger::trace('Pass.refreshGoodsList usrRfrNum[%d] needGold[%d]', $usrRfrNum + 1, $needGold);
			
					$userObj = EnUser::getUserObj($this->uid);
					if(FALSE === $userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_PASS_SHOP_REFRESH_COST))
					{
						throw new FakeException('Pass.refreshGoodsList sub gold[%d] failed', $needGold);
					}
					$userObj->update();
					$shop->usrRfrGoodsList(PassDef::TYPE_RFR_GOLD);
				}
			}
			
		}
		
		$shopInfo = $shop->getShopInfo();
		$shop->update();
	
		Logger::trace('Pass.refreshGoodsList ret[shopInfo:%s] end...', $shopInfo);
		return $shopInfo;
	}
	/* (non-PHPdoc)
	 * @see IPass::buyAttackNum()
	 */
	public function buyAttackNum( $num ) 
	{
		PassLogic::buyAttackNum( $this->uid, $num );
		
	}


	public function sweep($buyChest, $buyBuff)
	{
		return PassLogic::sweep($this->uid, $buyChest, $buyBuff);
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */