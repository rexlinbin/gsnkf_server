<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RetrieveLogic.class.php 259930 2016-09-01 09:32:03Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/RetrieveLogic.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-09-01 09:32:03 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259930 $
 * @brief 
 *  
 **/
 
class RetrieveLogic
{
	public static function getRetrieveInfo($uid)
	{
		Logger::trace('RetrieveLogic::getRetrieveInfo param[uid:%d] begin...', $uid);
		
		if (!self::isRetrieveOpen($uid)) 
		{
			Logger::trace('RetrieveLogic::getRetrieveInfo retrieve not open');
			return array();
		}
		
		self::refreshInfo($uid);
		
		$retrieveObj = RetrieveObj::getInstance($uid);
		
		$supplyInfo = $retrieveObj->getSupplyInfo();
		
		$bossRet = EnBoss::getRetrieveInfo($uid, RetrieveConf::RETRIEVE_BOSS_ID);
		$olympicRet = OlympicLogic::getRetrieveInfo($uid);
		$countrywarRet = EnCountryWar::getRetrieveInfo($uid);
		
		if (FALSE === $bossRet && FALSE === $olympicRet && FALSE === $countrywarRet && ( empty( $supplyInfo ) || empty( $supplyInfo['sum'] ) ) )
		{
			Logger::trace('RetrieveLogic::getRetrieveInfo retrieve nothing, return.');
			return array();
		}
		
		$ret = array();
		
		// 是否有世界BOSS资源可以追回
		if (is_array($bossRet))
		{
			list($beforeEndTime, $nextStartTime) = $bossRet;
			if ($retrieveObj->canRetrieve(RetrieveDef::BOSS, $beforeEndTime)) 
			{
				$ret[] = array('type' => RetrieveDef::BOSS, 'endTime' => $nextStartTime);
			}
		}
		
		//是否有擂台赛资源可以追回
		if (is_array($olympicRet)) 
		{
			list($beforeEndTime, $nextStartTime) = $olympicRet;
			if ($retrieveObj->canRetrieve(RetrieveDef::OLYMPIC, $beforeEndTime))
			{
				$ret[] = array('type' => RetrieveDef::OLYMPIC, 'endTime' => $nextStartTime);
			}
		}
		
		// 是否有国战的资源追回
		if (is_array($countrywarRet))
		{
			list($beforeEndTime, $nextStartTime) = $countrywarRet;
			if ($retrieveObj->canRetrieve(RetrieveDef::COUNTRYWAR, $beforeEndTime))
			{
				$ret[] = array('type' => RetrieveDef::COUNTRYWAR, 'endTime' => $nextStartTime);
			}
		}
		
		if ( !empty( $supplyInfo ) && count( $supplyInfo['reward'] ) < $supplyInfo['sum'] )
		{
		    $endTime = intval( strtotime( date( "Y-m-d", Util::getTime() ) ) ) + SECONDS_OF_DAY - 1;
		    $ret[] = array(
		        'type' => RetrieveDef::SUPPLY,
		        'endTime' => $endTime,
		        'num' => $supplyInfo['sum'] - count( $supplyInfo['reward'] ),
		    );
		}
		
		Logger::trace('RetrieveLogic::getRetrieveInfo param[uid:%d] ret[retrieveInfo:%s] end...', $uid, $ret);
		return $ret;
	}
	
	public static function retrieve($uid, $arrType, $isGold, $isAll)
	{
		if (!self::isRetrieveOpen($uid))
		{
			throw new FakeException('RetrieveLogic::retrieve retrieve not open');
		}
			
		$ret = array();
		foreach ($arrType as $type)
		{
			$ret[$type] = self::_retrieve($uid, $type, $isGold, $isAll);
		}
		Logger::trace('RetrieveLogic::retrieve param[uid:%d，arrType:%s,isGold:%d] ret[ret:%s] end...', $uid, $arrType, $isGold, $ret);
		return $ret;
	}
	
	private static function _retrieve($uid, $type, $isGold, $isAll)
	{
	    $num = 1;
		$retrieveRet = NULL;
		if (RetrieveDef::BOSS == $type) 
		{
			$retrieveRet = EnBoss::getRetrieveInfo($uid, RetrieveConf::RETRIEVE_BOSS_ID);
		}
		else if (RetrieveDef::OLYMPIC == $type) 
		{
			$retrieveRet = OlympicLogic::getRetrieveInfo($uid);
		}
		else if (RetrieveDef::COUNTRYWAR == $type)
		{
			$retrieveRet = EnCountryWar::getRetrieveInfo($uid);
		}
		else if ( RetrieveDef::SUPPLY == $type )
		{
		    $num = 1;
		    
		    if ( !$isGold )
		    {
		        throw new FakeException("supply can not be retrieved by silver");
		    }
		    
		    $supplyInfo = RetrieveObj::getInstance($uid)->getSupplyInfo();
		    if ( empty( $supplyInfo ) )
		    {
		        throw new FakeException("empty supplyInfo");
		    }
		    
		    $canBackNum = $supplyInfo['sum'] - count( $supplyInfo['reward'] );
		    
		    if ( $isAll )
		    {
		        $num = $canBackNum;
		    }
		    
		    if ( $num > $canBackNum )
		    {
		        throw new FakeException("not enough. num:%d canBackNum:%d supplyInfo:%s ", $num, $canBackNum, $supplyInfo);
		    }
		}
		else 
		{
			throw new FakeException('unknown retrieve type:%s', $type);
		}
		
		$retrieveObj = RetrieveObj::getInstance($uid);
        
		if ( RetrieveDef::BOSS == $type
		    || RetrieveDef::OLYMPIC == $type
		    || RetrieveDef::COUNTRYWAR == $type )
		{
		    if (FALSE === $retrieveRet)
		    {
		        return 'nothing';
		    }
		    
		    list($beforeEndTime, $nextStartTime) = $retrieveRet;
		    
		    if (!$retrieveObj->canRetrieve($type, $beforeEndTime))
		    {
		        return 'already';
		    };
		}
		
		$userObj = EnUser::getUserObj($uid);
		$statType = 0;
		if (RetrieveDef::BOSS == $type) 
		{
			$statType = StatisticsDef::ST_FUNCKEY_RETRIEVE_BOSS;
		}
		else if (RetrieveDef::OLYMPIC == $type)
		{
			$statType = StatisticsDef::ST_FUNCKEY_RETRIEVE_OLYMPIC;
		}
		else if ( RetrieveDef::COUNTRYWAR == $type )
		{
			$statType = StatisticsDef::ST_FUNCKEY_RETRIEVE_COUNTRYWAR;
		}
		else 
		{
		    $statType = StatisticsDef::ST_FUNCKEY_RETRIEVE_SUPPLY;
		}
		
		if ($isGold)
		{
		    $needGold = intval(btstore_get()->RETRIEVE[$type][RetrieveCsvTag::GOLD]) * $num ;
		    
			if (FALSE === $userObj->subGold( $needGold, $statType))
			{
				return 'lack';
			}
		}
		else
		{
			$subSilver = 0;
			$silverConfig = btstore_get()->RETRIEVE[$type][RetrieveCsvTag::SILVER]->toArray();
			if ($silverConfig[0] == 1) // 银币为固定值
			{
				$subSilver = intval($silverConfig[1]);
			}
			else // 银币和等级相关
			{
				$subSilver = intval($silverConfig[1]) * $userObj->getLevel();
			}
			
			if (FALSE === $userObj->subSilver($subSilver))
			{
				return 'lack';
			}
		}
		
		$rewardType = ($isGold ? RetrieveCsvTag::GOLD_REWARD : RetrieveCsvTag::SILVER_REWARD);
		$reward = btstore_get()->RETRIEVE[$type][$rewardType]->toArray();
		
		foreach ( $reward as $key => $value )
		{
		    $reward[$key][2] *= $num;
		}
		
		RewardUtil::reward3DArr($uid, $reward, $statType);
		$userObj->update();
		BagManager::getInstance()->getBag($uid)->update();
		
		if ( RetrieveDef::BOSS == $type
		    || RetrieveDef::OLYMPIC == $type
		    || RetrieveDef::COUNTRYWAR == $type )
		{
		    $retrieveObj->updateRetrieveTime($type);
		}
		else if ( RetrieveDef::SUPPLY == $type )
		{
		    $supplyInfo = $retrieveObj->getSupplyInfo();
		    for ( $i = 0; $i < $num ; $i++ )
		    {
		        $supplyInfo['reward'][] = intval( Util::getTime() );
		    }
		    
		    $retrieveObj->setSupplyInfo($supplyInfo);
		}
		else 
		{
		    Logger::fatal("unknown type:%d ", $type);
		}
		
		$retrieveObj->update();
		
		return 'ok';
	}
	
	public static function checkType($arrType, $isGold)
	{
		if (empty($arrType)) 
		{
			throw new FakeException('retrieve type is empty');
		}
		
		foreach ($arrType as $type)
		{
			if (!in_array($type, RetrieveDef::$RETRIEVE_TYPE)) 
			{
				throw new FakeException('invalid retrieve type:%s', $type);
			}
		}
		
		if ( !$isGold )
		{
		    if ( in_array(RetrieveDef::SUPPLY, $arrType) )
		    {
		        throw new FakeException("supply can not be retrieved by silver.");
		    }
		}
	}

	public static function isRetrieveOpen($uid)
	{
		// 是否开启资源追回
		if (!EnSwitch::isSwitchOpen(SwitchDef::RETRIEVE)) 
		{
			Logger::trace('RetrieveLogic::isRetrieveOpen uid[%d] retrieve switch not open, can not retrieve', $uid);
			return FALSE;
		}
		
		// 如果是第一天创建账号，则不能资源追回
		$userObj = EnUser::getUserObj($uid);
		$createTime = $userObj->getCreateTime();
		if (Util::isSameDay($createTime)) 
		{
			Logger::trace('RetrieveLogic::isRetrieveOpen uid[%d] create today, can not retrieve', $uid);
			return FALSE;
		}
		
		return TRUE;
	}
	
	public static function refreshInfo($uid)
	{
	    $retrieveObj = RetrieveObj::getInstance($uid);
	    
	    $supplyInfo = $retrieveObj->getSupplyInfo();
	    
	    $needUdt = FALSE;
	    
	    if ( empty( $supplyInfo ) || FALSE == Util::isSameDay($supplyInfo['time']) )
	    {
	        $sum = count( ActivityConf::$SUPPLY_TIME_ARR );
	        $arrExec = EnUser::getExtraInfo('va_exec');
	        
	        $supplyNum = 0;
	        if ( !empty( $arrExec ) )
	        {
	            if ( TRUE == Util::isSameDay($arrExec[0]) )
	            {
	                $supplyNum = $sum;
	            }
	            else 
	            {
	                $zeroToday = intval( strtotime( date("Y-m-d", Util::getTime()) ) );
	                foreach ( $arrExec as $time )
	                {
	                    if ( $time >= $zeroToday - SECONDS_OF_DAY )
	                    {
	                        $supplyNum ++;
	                    }
	                }
	            }
	        }
	        
	        $supplyInfo = array(
	            'time' => intval( Util::getTime() ),
	            'sum' => $sum - $supplyNum,
	            'reward' => array(),
	        );
	        
	        $retrieveObj->setSupplyInfo($supplyInfo);
	        
	        $needUdt = TRUE;
	    }
	    
	    if ( $needUdt )
	    {
	        $retrieveObj->update();
	    }
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */