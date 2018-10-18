<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DivineLogic.class.php 261251 2016-09-07 11:24:20Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/DivineLogic.class.php $
 * @author $Author: GuohaoZheng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-07 11:24:20 +0000 (Wed, 07 Sep 2016) $
 * @version $Revision: 261251 $
 * @brief 
 *  
 **/
class DivineLogic 
{	
	/**
	 * 
	 * @param int $uid 
	 * @return @see
	 */
	public static function getDiviInfo( $uid )
	{
		$diviInst = DivineObj::getInstance( $uid );
		$diviInfo = $diviInst->getDiviInfo();
		if ( empty( $diviInfo ) )
		{
			//保险起见
			throw new InterException( 'fail to get diviinfo of uid: %d' ,$uid );
		}
		return $diviInfo;
	}
	

	public static function divine( $uid , $pos )
	{
		$diviInst = DivineObj::getInstance( $uid );
		self::checkMaxTimes( $diviInst->getDiviTimes() );
		
		//获取所占位置的星星的id
		$curStars = $diviInst->getVaCurStar();
		if ( !isset( $curStars[ $pos ] ) )
		{
			throw new FakeException( 'invalid pos: %d in curStarArr: %s',$pos, $curStars );
		}
		$starId = $curStars[ $pos ];
		
		$aster = btstore_get()->DIVI_ASTER;
		if ( !isset( $aster[ $starId ] ) )
		{
			throw new FakeException( 'invalid starId: %d', $starId );
		}
		$conf = $aster[ $starId ] ;
		
		//查看该星座是否是目标星座并进行相关操作
		$tarStarArr = $diviInst->getVaTarStar();
		$lightedArr = $diviInst->getLightedArr();
// 		$ligntAll = true;
// 		foreach ( $lightedArr as $val )
// 		{
// 			if ( $val == 0 )
// 			{
// 				$ligntAll = false;
// 				break;
// 			}
// 		}
		
// 		if ( $ligntAll )
// 		{
// 			throw new InterException( 'lighted: %s beyond target: %s,', $lightedArr, $tarStarArr );
// 		}
		
		foreach ( $tarStarArr as $tarPos => $tarStarId )
		{
			if ( $starId == $tarStarId && $lightedArr[ $tarPos ] != 1 )
			{
				$diviInst->addTarStarLignted( $tarPos );
				break;
			}
		}
		
		
		$diviInst->finishCur( $conf[ 'integral_num'] );
		EnUser::getUserObj( $uid )->addSilver( $conf[ 'silver_num' ] );
		
		$diviInst->update();
		EnUser::getUserObj( $uid )->update();
		EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_DIVINE, 1);
	}
	

	public static function refreshCurr( $uid )
	{
		$diviInst = DivineObj::getInstance( $uid );
		
		$bag = BagManager::getInstance()->getBag($uid);
		$generalConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_DIVINE_NEED_ITEM]->toArray();
		$needItemTmplId = key( $generalConf );
		Logger::debug('item need in divine is: %s',$needItemTmplId );
		$itemNUm = $bag->getItemNumByTemplateID($needItemTmplId);
		if ( $diviInst->getFreeRefNum() > 0 ) 
		{
			$diviInst->minusFreeRefNum();
		}
		elseif ( $itemNUm >= $generalConf[$needItemTmplId] )
		{
			if (!$bag->deleteItembyTemplateID($needItemTmplId, $generalConf[$needItemTmplId]))
			{
				throw new InterException( 'lack items' );
			}
		}
		else 
		{
			if ( !(EnUser::getUserObj( $uid )->subGold( DivineCfg::REFRESH_COST ,  StatisticsDef::ST_FUNCKEY_DIVI_REFRESH)) )
			{
				throw new FakeException( 'uid: %d lack gold to refresh' , $uid );
			}
		}
		$diviInst->setVaCurStar();
		$bag->update();
		$diviInst->update();
		EnUser::getUserObj( $uid )->update();
	}
	
	public static function checkMaxTimes( $times )
	{
		if ( $times >= DivineCfg::MAX_DIVI_TIMES )
		{
			throw new FakeException( 'divined times: %d reached max: %d' ,$times ,DivineCfg::MAX_DIVI_TIMES );
		}
	}
	
	public static function drawPrize( $uid , $step )
	{
		$diviInst = DivineObj::getInstance( $uid );
		
		if ( $step != $diviInst->getPrizeNum() ) 
		{
			throw new FakeException( 'prize step err' );
		}
			
		self::drawPrizeStep($uid, false, $step);
		
		//return $prizeArr;
	}
	
	
	public static function drawPrizeStep($uid, $all, $needStep = 0)
	{
		$diviInst = DivineObj::getInstance($uid);
		//这个地方刚开始写的2b了。。。
		//$remainPrizeArr = $diviInst->undrewPrize();
	/* 	if ( empty( $remainPrizeArr ))
		{
			throw new FakeException( 'uid: %d has drew all prize' , $uid );
		} */
		
		$conf = btstore_get()->DIVI_PRIZE[ $diviInst->getLevel() ];
		$arrIntegralNeed = $conf[ 'integ_arr' ];
		$prizedNum = $diviInst->getPrizeNum();
		$myIntergral = $diviInst->getIntegral();
		
		foreach ( $arrIntegralNeed as $step => $needIntergral )
		{
			if( $step >= $prizedNum && $myIntergral >= $needIntergral )
			{
				$stepArr[] = $step;
			}
		}
		
		if( empty( $stepArr ) )
		{
			throw new FakeException( 'uid: %d has drew all prize' , $uid );
		}
		
		if( !$all && !in_array( $needStep ,$stepArr ) )
		{
			throw new FakeException( 'no time to gain this one %d', $needStep );//$stepArr = array($needStep);
		}
		elseif( !$all )
		{
			$stepArr = array( $needStep );
		}
		
		if( $diviInst->getLevel() > 1 )
		{
			$prizeArrAll = $diviInst->getNewReward();
		}
		else
		{
			$prizeArrAll = $conf[ 'prize_arr' ];
		}
		
		$allPrizeArr = array();
		foreach ( $stepArr as $oneStep )
		{
			if( !isset( $prizeArrAll[ $oneStep ] ) )
			{
				throw new ConfigException( 'step: %d is not find in config', $oneStep );
			}
			$prizeArr = $prizeArrAll[ $oneStep ];
			$diviInst->addPrizeStep();
			$allPrizeArr[] = $prizeArr;
		}
		
		DivineUtil::reward( $uid, $allPrizeArr );
		
		$diviInst->update();
		BagManager::getInstance()->getBag( $uid )->update();
		EnUser::getUserObj($uid)->update();
	}
	
	public static function upgradePrize( $uid )
	{
		$diviInst = DivineObj::getInstance( $uid );
		$prizeConf = btstore_get()->DIVI_PRIZE;
		if ( !isset( $prizeConf[ $diviInst->getLevel() + 1 ] ) )
		{
			throw new FakeException( 'reach max prize level: %d',  $diviInst->getLevel() );
		}
		$upgConf = $prizeConf[ $diviInst->getLevel() + 1 ] ;
		$upgNeedLevel = $upgConf['need_level'];
		if ( EnUser::getUserObj( $uid )->getLevel() < $upgNeedLevel )
		{
			throw new FakeException( 'uid: %d is lack level to upg ' , $uid );
		}
		$remaimPrizeArr = $diviInst->undrewPrize(); 
		if ( !empty($remaimPrizeArr))
		{
			throw new FakeException( 'uid: %d need to drew all prize to upgrade' , $uid );
		}
		$diviInst->upgrade();
		$diviInst->update();
	}
	
	public static function refPrize($uid)
	{
		$diviInst = DivineObj::getInstance($uid);
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		$vipConf = btstore_get()->VIP[$vip]['resetDivineNum'];
		
		$prizeLevel = $diviInst->getLevel();
		if ( $prizeLevel <= 1 )
		{
			throw new FakeException( 'can not refresh lv: %d too low', $prizeLevel);
		}
		
		$remainReward = $diviInst->undrewPrize();
		$divitimes = $diviInst->getDiviTimes();
		if ($divitimes >= DivineCfg::MAX_DIVI_TIMES && empty($remainReward))
		{
			throw new FakeException( 'can not ref, max times and no reward' );
		}
		
		$allreward = $diviInst->getNewReward();
		$allPrizeNum = count( $allreward );
		$gainNum = $diviInst->getPrizeNum();
		if ( $gainNum >= $allPrizeNum -1 )
		{
			throw new FakeException( 'no need to refresh' );
		}
		
		$refNum = $diviInst->getRefPrizeNum();
		if ( $refNum >= $vipConf[0] )
		{
			throw new FakeException( 'already reach max ref num' );
		}
		
		$needGold = $vipConf[1] + intval( $refNum * $vipConf[2] );
		if ( !$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_DIVI_REF_REWARD) )
		{
			throw new FakeException( 'lack gold' );
		}
		
		$afterRef = $diviInst->refPrize();
		$user->update();
		$diviInst->update();
		
		return $afterRef;
	}
	
	
	
	public static function oneClickDivine($uid)
	{
		// 检查玩家资质并扣费
		self::checkBfOneClickDivine($uid);
		
		Logger::debug("Start one-click-divine...");
		$uObj = EnUser::getUserObj($uid);
		// 满星
		$conf = btstore_get()->DIVI_PRIZE;
		$uLevel = $uObj->getLevel();
		$confId = 1;
		while(true)
		{
			if(!empty($conf[$confId]) && $uLevel > $conf[$confId]['need_level'])
			{
				$confId++;
			}
			else 
			{
				break;
			}
		}
		$conf = $conf[$confId - 1];
		
		// 模拟手动占满4轮目标星座的最理想情况下，所用的占星次数和星数奖励
		// 最理想情况：每次占星都能占到目标星座，那么每一轮都会额外增加 1*4 = 4 星数，每轮结束还有奖励星数；
		// 			  所以所有目标星座占满，共占了 16 次，额外增加 4 * 4 = 16 星数，加上奖励星数 20 + 30 + 35 + 40 = 125（这个具体看配置，当前是这些值）
		// 			 总共增加了 141 星数，占了目标星座 16 次（即最大配置次数），占了当前星座 16 次。
		//			 不过，这里只要使玩家星数满星就可以了。
		$maxIntegral = $conf['max_integral'];
		Logger::debug("Add User $uid 's integral in one-click-divine.");
		$diviObjIns = DivineObj::getInstance($uid);
		$diviObjIns->doOneClickDivine($maxIntegral, DivineCfg::MAX_DIVI_TIMES);
		
		// 更新玩家信息
		$diviObjIns->update();
		EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_DIVINE, DivineCfg::MAX_DIVI_TIMES);
		
		$afterDiviInfo = DivineLogic::getDiviInfo($uid);
		EnAchieve::updateDivine($uid, $afterDiviInfo['integral']);
		
		EnActive::addTask( ActiveDef::DIVINE, DivineCfg::MAX_DIVI_TIMES );
		EnWeal::addKaPoints( KaDef::DIVINE, DivineCfg::MAX_DIVI_TIMES );
		EnMission::doMission($uid, MissionType::DIVINE, DivineCfg::MAX_DIVI_TIMES);
	}
	
	/**
	 * 检查玩家一键占星资格，并扣减相应占星令或者金币
	 * @param int $uid
	 * @throws FakeException
	 * @throws InterException
	 */
	private static function checkBfOneClickDivine($uid)
	{
		$uObj = EnUser::getUserObj($uid);
		
		// 检查玩家等级
		$uLevel = $uObj->getLevel();
		$lmtLevel = btstore_get()->DIVI_PRIZE['oneclick_level'];
		if($uLevel < $lmtLevel)
		{
			throw new FakeException("User $uid 's level below one-click-divine level limit!");
		}
		
		// 检查玩家占星次数是否为 0，否，则不允许一键占星
		$dObjIns = DivineObj::getInstance($uid);
		if(0 != $dObjIns->getDiviTimes())
		{
			throw new FakeException("User $uid has divined, one-click-divine denied!");
		} 
		
		// 检查玩家占星令及金币数，并尝试扣费
		$generalConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_DIVINE_NEED_ITEM]->toArray();
		$needItemTmplId = key( $generalConf );
		Logger::debug('Item need in one-click-divine is: %s',$needItemTmplId );
		$bag = BagManager::getInstance()->getBag($uid);
		$itemNum = $bag->getItemNumByTemplateID($needItemTmplId);
		if($itemNum < DivineCfg::ONECLICK_ITEM_COST)
		{
			$delta = DivineCfg::ONECLICK_ITEM_COST - $itemNum;
			$needGold = $delta * DivineCfg::ONECLICK_GOLD_UNIT_COST;
			if($needGold <= $uObj->getGold())
			{
				// 扣除占星令和金币
				if (!empty($itemNum))
				{
					Logger::info("Substract user $uid 's divi-item %s($itemNum) for one-click-divine.", $needItemTmplId);
					if (!$bag->deleteItembyTemplateID($needItemTmplId, $itemNum))
					{
						throw new InterException("Failed to run one-click-divine, lack items");
					}
				}
				Logger::info("Subscract user $uid 's gold($needGold) for one-click-divine");
				if(!$uObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DIVI_ONECLICK))
				{
					throw new InterException("Failed to substract gold of user $uid in one-click-divine!");
				}
			}
			else
			{
				throw new InterException("User $uid doesn't has enough divine-items or gold, one-click-divine denied!");
			}
		}
		else 
		{
			// 扣除占星令
			Logger::info("Subscract user $uid 's divi-item %s(%d) for one-click-divine", $needItemTmplId, DivineCfg::ONECLICK_ITEM_COST);
			if (!$bag->deleteItembyTemplateID($needItemTmplId, DivineCfg::ONECLICK_ITEM_COST))
			{
				throw new InterException( "Failed to run one-click-divine, lack items" );
			}
		}
		$uObj->update();
		$bag->update();
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */