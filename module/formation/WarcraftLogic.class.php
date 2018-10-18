<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WarcraftLogic.class.php 159566 2015-02-28 09:26:32Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/formation/WarcraftLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-02-28 09:26:32 +0000 (Sat, 28 Feb 2015) $
 * @version $Revision: 159566 $
 * @brief 
 *  
 **/

class WarcraftLogic
{
	
	public static function getWarcraftInfo( $uid )
	{
		$formationObj = EnFormation::getFormationObj($uid);
		return array(
				'craft_id' => $formationObj->getCurWarcraft(),
				'warcraft' => $formationObj->getWarcraft(),
		);
	} 
	
	public static function isWarCraftOpen( $uid )
	{
		/* if ( !EnSwitch::isSwitchOpen( SwitchDef::WARCRAFT, $uid ) ) 
		{
			return false ;
		} */
		$formationObj = EnFormation::getFormationObj($uid);
		$curCraftId = $formationObj->getCurWarcraft();
		if( empty( $curCraftId ) )
		{
			return false;
		}
		return true;
	}
	
	public static function craftLevelup( $uid, $craftId )
	{
		$warcraftConf = btstore_get()->WARCRAFT;
		$warcraftLevelupConf = btstore_get()->WARCRAFT_LEVELUP;
		if( !isset( $warcraftConf[$craftId] ) )
		{
			throw new ConfigException( 'no config for craftId %d', $craftId );
		}
		
		$formationObj = EnFormation::getFormationObj($uid);
		$warcraft = $formationObj->getWarcraft();
		$curLevel = 1;//初始等级
		if( isset( $warcraft[$craftId] ) )
		{
			$curLevel = $warcraft[$craftId]['level'];
		}
		
		//需要的是物品
		$needMaterial = $warcraftLevelupConf[ $curLevel ]['needItem'];
		$needSilver = $warcraftLevelupConf[ $curLevel ]['needSilver'];
		//解析的时候检查一下物品不要配重复的id
		$user = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		foreach ( $needMaterial as $itemId => $itemNum )
		{
			if(!$bag->deleteItembyTemplateID($itemId, $itemNum))
			{
				throw new FakeException( 'lack item: %d, num: %d',$itemId, $itemNum );
			}
		}
		if( !$user->subSilver($needSilver) )
		{
			throw new FakeException( 'lack silver' );
		}
		
		$warcraft[$craftId]['level'] = $curLevel+1;
		$formationObj->setWarcraft($warcraft);
		
		$bag->update();
		$user->update();
		$formationObj->update();
		
		$user->modifyBattleData();
		
	}
	
	public static function setCurWarcraft( $uid, $craftId )
	{
		$warcraftConf = btstore_get()->WARCRAFT;
		if( !isset( $warcraftConf[$craftId] ) )
		{
			throw new ConfigException( 'no config for craftId %d', $craftId );
		}
		
		$formationObj = EnFormation::getFormationObj($uid);
		$formationObj->setCurWarcraft($craftId);
		
		$formationObj->update();
		
		$user= EnUser::getUserObj($uid);
		$user->modifyBattleData();
	}
	
	
	//=========以下的两个是纯逻辑的方法
	
	public static function getWarcraftProfit( $curWarcraftId, $allCraft )
	{
		if( $curWarcraftId == 0 )
		{
			return array();
		}
		$warcraftConf = btstore_get()->WARCRAFT;
		
		//所有阵法加成
		$craftUnionInfo = self::profitByCraftUnion($allCraft);
		$craftUnionProfit = $craftUnionInfo['profitArr']; 
		$craftUnionRatio = $craftUnionInfo['allCraftRatio']/UNIT_BASE;
		
		//单个阵法加成
		$curLevel = 1;
		if( isset( $allCraft[$curWarcraftId] ) )
		{
			$curLevel = $allCraft[$curWarcraftId]['level'];
		}
		
		$profitOrder = array( 0,1,2,3,4,5 );
		$profitConf = array();
		if( isset( $warcraftConf[$curWarcraftId] ) )
		{
			$profitConf = $warcraftConf[$curWarcraftId]['craftProfitArr'];
		}
		
		foreach ( $profitOrder as $pos )
		{
			$profitRaw = array();
			if( isset( $profitConf[$pos] ) )
			{
				foreach ( $profitConf[$pos] as $key => $oneAdditionInfo )
				{
					if(!isset( $profitRaw[$oneAdditionInfo[0]] ) )
					{
						$profitRaw[$oneAdditionInfo[0]] = 0;
					}
					
					$profitRaw[$oneAdditionInfo[0]] += 
					intval(  ( $oneAdditionInfo[1] + $oneAdditionInfo[2]*($curLevel-1) ) * (1 + $craftUnionRatio)); 
				}
				
				$profitArr[$pos] = $profitRaw;
				Logger::debug( 'single profit pos is :%s  profit: %s',$pos, $profitArr[$pos]  );
			}
			
			foreach ( $craftUnionProfit as $profitKey => $profitValue )
			{
				if( !isset( $profitArr[$pos][$profitKey] ) )
				{
					$profitArr[$pos][$profitKey] = 0;
				}
				$profitArr[$pos][$profitKey] += $profitValue;
			}
			Logger::debug( 'after union profit pos is :%s  profit: %s',$pos, $profitArr[$pos]  );
		}
		
		if( empty( $profitArr ) )
		{
			return array();
		}
		else 
		{
			foreach ( $profitArr as $onePos => $posProfitInfo )
			{
				$profitArr[$onePos] = HeroUtil::adaptAttr( $posProfitInfo );
			}
		}
		
		return $profitArr;
		
	}
	
	public static function profitByCraftUnion( $allCraft )
	{
		$craftUnionConf = btstore_get()->WARCRAFT_UNION->toArray();
		$warcraftConf = btstore_get()->WARCRAFT;
		
		$allCraftRatio = 0;
		$rawProfit = array();
		foreach ( $craftUnionConf as $id => $uniionInfo )
		{
			$num = 0;
			foreach ( $warcraftConf as $craftId => $craftInfo )
			{
				$curLevel = 1;
				if( isset( $allCraft[$craftId] ) )
				{
					$curLevel = $allCraft[$craftId]['level'];
				}
				if( $curLevel >= $uniionInfo[ 'numAndLevel' ][1] )
				{
					$num++;
				}
			}
			if( $num >= $uniionInfo[ 'numAndLevel' ][0] )
			{
				$rawProfit = array_merge( $rawProfit, $uniionInfo['profit'] );
				$allCraftRatio += $uniionInfo['allCraftRatio'];
			}
			else 
			{
				//这里有策划的规则
				break;
			}
		}
		
		$profitArr = array();
		foreach ( $rawProfit as $index => $oneRawProfit )
		{
			if( empty( $oneRawProfit ) || empty( $oneRawProfit[0] ) )
			{
				continue;
			}
			if( isset( $profitArr[ $oneRawProfit[0] ] ) )
			{
				$profitArr[ $oneRawProfit[0] ] += $oneRawProfit[1];
			}
			else 
			{
				$profitArr[ $oneRawProfit[0] ] = $oneRawProfit[1];
			}
		}
		
		return array('profitArr' => $profitArr,'allCraftRatio' => $allCraftRatio);
		/**
		if( empty( $profitArr ) )
		{
			return array();
		}
		else 
		{
			return $profitArr;
		}*/
	}
	
	public static function isPosValidByCraft( $pos, $allWarcraftInfo, $isSecondFriend = FALSE )
	{
		//这里有策划的保证， 虽然阵法相关的小伙伴与之前的小伙伴的开始不是线性的，但是阵法相关的是线性的
		$warcraftConf = btstore_get()->WARCRAFT;
		$asistNeedCraft = btstore_get()->FORMATION['arrExtraNeedCraft'];
		
		// 如果是第二套小伙伴，则取第二套小伙伴的配置
		if ($isSecondFriend) 
		{
			$asistNeedCraft = btstore_get()->FORMATION['arrAttrExtraNeedCraft'];
		}
		
		if( isset( $asistNeedCraft[ $pos ] ) )
		{
			$asistPosInfoNeed = $asistNeedCraft[ $pos ];
		}
		
		if( empty( $asistPosInfoNeed ) )
		{
			//这个要保证已经在外面检查过了 确实是需要阵法信息的
			return false;
		}
		
		$num = 0;
		foreach ( $warcraftConf as $warcraftId => $warcraftInfo )
		{
			$level = 0;
			if ( isset( $allWarcraftInfo[$warcraftId] ) )
			{
				$level = $allWarcraftInfo[$warcraftId]['level'];
			}
			if( $level >= $asistPosInfoNeed[2] )
			{
				$num++;
			}
		}
		
		if( $num >= $asistPosInfoNeed[1]  )
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */