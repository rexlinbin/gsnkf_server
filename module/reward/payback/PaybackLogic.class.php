<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PaybackLogic.class.php 259963 2016-09-02 02:53:28Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/payback/PaybackLogic.class.php $
 * @author $Author: GuohaoZheng $(yangwenhai@babeltime.com)
 * @date $Date: 2016-09-02 02:53:28 +0000 (Fri, 02 Sep 2016) $
 * @version $Revision: 259963 $
 * @brief 
 *  
 **/
class PaybackLogic 
{
	
	/**
	 * 插入一条补偿信息
	 * @param int $timeStart
	 * @param int $timeEnd
	 * @param array $arrInfo 
	 * @return boolean
	 */
	public static function insertPayBackInfo($timeStart, $timeEnd, $arrInfo, $isOpen = 0, $db = '')
	{
		//检查补偿数据是否合法
		$dataValid = true;
		foreach($arrInfo as $key => $value)
		{
			switch($key)
			{
				case RewardType::ARR_ITEM_TPL:
				case RewardType::ARR_HERO_TPL:
				case RewardType::ARR_TF_TPL:
					if(!is_array($value))
					{
						Logger::fatal('invalid reward. arrItemId need array. info:%s', $arrInfo);
						return false;
					}
					break;					
				case RewardType::GOLD:				
				case RewardType::SILVER:				
				case RewardType::SOUL:
				case RewardType::PRESTIGE:
				case RewardType::JEWEL:
				case RewardType::COPOINT:
				case RewardType::EXE:
				case RewardType::STAMINA:
				case RewardType::SILVER:
				case RewardType::GUILD_CONTRI:
				case RewardType::GUILD_EXP:
				case RewardType::HORNOR:
				case RewardType::GRAIN:
				case RewardType::COIN:
				case RewardType::ZG:
				case RewardType::TG:
				case RewardType::WM:
				case RewardType::HELL_POINT:
				case RewardType::FAME_NUM:
				case RewardType::CROSS_HONOR:
				case RewardType::JH:
				case RewardType::TALLY_POINT:
				case RewardType::BOOK_NUM:
				case RewardType::HELL_TOWER:
				    $arrInfo[$key] = intval( $value );
					break;
				case PayBackDef::PAYBACK_TYPE:
				case PayBackDef::PAYBACK_MSG:
				case PayBackDef::PAYBACK_TITLE:
					break;
				default:
					throw new InterException('invalid payback key:%s', $key);
					return false;
			}
		}
		
		if (!isset($arrInfo[PayBackDef::PAYBACK_TYPE]))
		{
			$arrInfo[PayBackDef::PAYBACK_TYPE] = PayBackType::SYSTEM;
			Logger::warning('insertPayBackInfo type err. startime:%d endtime:%d', $timeStart, $timeEnd);
		}
		
		if (!isset($arrInfo[PayBackDef::PAYBACK_MSG]))
		{
			$arrInfo[PayBackDef::PAYBACK_MSG] = '';
			Logger::info('insertPayBackInfo msg err! startime:%d endtime:%d', $timeStart, $timeEnd);
		}
		
		if (!isset($arrInfo[PayBackDef::PAYBACK_TITLE]))
		{
		    $arrInfo[PayBackDef::PAYBACK_TITLE] = '';
		    Logger::info('insertPayBackInfo title err! startime:%d endtime:%d', $timeStart, $timeEnd);
		}
		
		$arrField = array(
				PayBackDef::PAYBACK_SQL_TIME_START => $timeStart,
				PayBackDef::PAYBACK_SQL_TIME_END => $timeEnd,
				PayBackDef::PAYBACK_SQL_IS_OPEN => $isOpen,
				PayBackDef::PAYBACK_SQL_ARRY_INFO => $arrInfo );
		
		return PayBackDAO::insertIntoPayBackInfoTable($arrField, $db);
	}
	
	/**
	 * 更新一条补偿信息
	 * @param int $timeStart
	 * @param int $timeEnd
	 * @param array $arrInfo
	 * @return bool
	 */
	public static function updatePayBackInfo($timeStart, $timeEnd, $arrInfo)
	{
		$set = array(PayBackDef::PAYBACK_SQL_ARRY_INFO => $arrInfo);
		$wheres  =	array(
						array (PayBackDef::PAYBACK_SQL_TIME_START, '=', $timeStart),
						array (PayBackDef::PAYBACK_SQL_TIME_END, '=', $timeEnd)
				);
		return PayBackDAO::updatePayBackInfoTable($set, $wheres);
	}
	
	/**
	 * 根据指定的时间段，查询对应的补偿信息,这个主要是给后端的人用
	 * @param int  $timeStart
	 * @param int $timeEnd
	 * @return array
	 */
	public static function getPayBackInfoByTime($timeStart, $timeEnd)
	{
		$ret=array();
		$wheres  =array(array (PayBackDef::PAYBACK_SQL_TIME_START, '=', $timeStart),
				array (PayBackDef::PAYBACK_SQL_TIME_END, '=', $timeEnd));
		$arrField = array(	PayBackDef::PAYBACK_SQL_PAYBACK_ID,
				PayBackDef::PAYBACK_SQL_TIME_START,
				PayBackDef::PAYBACK_SQL_IS_OPEN,
				PayBackDef::PAYBACK_SQL_TIME_END,
				PayBackDef::PAYBACK_SQL_ARRY_INFO);
		return PayBackDAO::getFromPayBackInfoTable($arrField, $wheres);
	}
	
	/**
	 * 根据指定的时间段(补偿开始时间>=$timeStart 并且 补偿结束时间<=$timeEnd)，查询对应的补偿信息,这个主要是给后端的人用
	 * @param int  $timeStart
	 * @param int $timeEnd
	 * @return array
	 */
	public static function getArrPayBackInfoByTime($timeStart,$timeEnd)
	{
	    $ret=array();
	    $wheres  =array(array (PayBackDef::PAYBACK_SQL_TIME_START, '>=', $timeStart),
	            array (PayBackDef::PAYBACK_SQL_TIME_END, '<=', $timeEnd));
	    $arrField = array(	PayBackDef::PAYBACK_SQL_PAYBACK_ID,
	            PayBackDef::PAYBACK_SQL_TIME_START,
	            PayBackDef::PAYBACK_SQL_IS_OPEN,
	            PayBackDef::PAYBACK_SQL_TIME_END,
	            PayBackDef::PAYBACK_SQL_ARRY_INFO);
	    return PayBackDAO::getFromPayBackInfoTable($arrField, $wheres);
	}

	
	/**
	 * 设置某个补偿的开关（0关 1开）
	 * @param int $paybackId
	 * @param int $isopen
	 */
	public static function setPayBackOpenStatus($paybackId, $isopen)
	{
		$isopen= ($isopen>0)?1:0;
		$set=array(PayBackDef::PAYBACK_SQL_IS_OPEN =>$isopen);
		$wheres=array(array (PayBackDef::PAYBACK_SQL_PAYBACK_ID, '=', $paybackId));
		return PayBackDAO::updatePayBackInfoTable($set, $wheres);
	}
	
	/**
	 * 查看某个补偿的开关情况
	 * @param int $paybackId
	 * @return int
	 */
	public static function getPayBackOpenStatus($paybackId)
	{
		$ret=array();
		$selinfo = array(PayBackDef::PAYBACK_SQL_IS_OPEN);
		$wheres = array(array (PayBackDef::PAYBACK_SQL_PAYBACK_ID, '=', $paybackId));
		$ret=PayBackDAO::getFromPayBackInfoTable($selinfo, $wheres);
		if (isset($ret[0]))
		{
			return $ret[0];
		}
		return $ret;
	}
	
	/**
	 * 根据补偿id的array获得补偿信息，在sql语句里做了条件检查
	 * @param array $idarray 前端发过来的，要求进行补偿的id
	 */
	public static function getPayBackInfoByIds($arrId)
	{
		$curtime =  Util::getTime();
		$wheres  =array(
				array (PayBackDef::PAYBACK_SQL_PAYBACK_ID, 'IN', $arrId),
				array (PayBackDef::PAYBACK_SQL_IS_OPEN, '>', 0));
		$arrField = array(	PayBackDef::PAYBACK_SQL_PAYBACK_ID,
				PayBackDef::PAYBACK_SQL_TIME_START,
				PayBackDef::PAYBACK_SQL_IS_OPEN,
				PayBackDef::PAYBACK_SQL_TIME_END,
				PayBackDef::PAYBACK_SQL_ARRY_INFO);
		return PayBackDAO::getFromPayBackInfoTable($arrField, $wheres);
	}
	
	/*
	 * 检查这个玩家所有领过的补偿
	*/
	public static function getAllPayBackUserInfoByUid($uid)
	{
		$ret=array();
		$wheres   =array(array (PayBackDef::PAYBACK_SQL_UID, '=', $uid));
		$selinfo =array (PayBackDef::PAYBACK_SQL_PAYBACK_ID);
		
		$offset = 0;
		for ($i = 0; $i < 65535; $i++)
		{
			$result = PayBackDAO::getFromPayBackUserTable($selinfo, $wheres, $offset, DataDef::MAX_FETCH);
			$ret = array_merge($ret, $result);
			if (count($result) < DataDef::MAX_FETCH)
			{
				break;
			}
			$offset += DataDef::MAX_FETCH;
		}
		
		return $ret;
	}
	
	/**
	 * 获得当前时间段内可用的补偿信息列表
	 */
	public static function getCurAvailablePayBackInfoList($usergotid)
	{
		$ret=array();
		$curtime = Util::getTime();
		$wheres  =array(array (PayBackDef::PAYBACK_SQL_TIME_START, '<=', $curtime),
				array (PayBackDef::PAYBACK_SQL_TIME_END, '>=', $curtime),
				array (PayBackDef::PAYBACK_SQL_IS_OPEN, '>', 0));
		$selinfo =array (PayBackDef::PAYBACK_SQL_PAYBACK_ID,
						 PayBackDef::PAYBACK_SQL_TIME_START,
						 PayBackDef::PAYBACK_SQL_TIME_END,
						 PayBackDef::PAYBACK_SQL_ARRY_INFO);
		
		$offset = 0;
		$ret = array();
		for ($i = 0; $i < 65535; $i++)
		{
			$result = PayBackDAO::getFromPayBackInfoTable($selinfo, $wheres, $offset, DataDef::MAX_FETCH);
			$ret = array_merge($ret, $result);
			if (count($result) < DataDef::MAX_FETCH)
			{
				break;
			}
			$offset += DataDef::MAX_FETCH;
		}
		
		foreach ($ret as $k => $v)
		{
			if (in_array($v[PayBackDef::PAYBACK_SQL_PAYBACK_ID], $usergotid))
			{
				unset($ret[$k]);
			}
		}
		
		return $ret;
	}
	
	/**
	 * 标记$uid领取了补偿 $arrId
	 * @param int $uid
	 * @param int $paybackId 补偿对应的id
	 * @return boolean
	 */
	public static function insertPayBackUser($uid, $arrId)
	{
		foreach($arrId as $paybackId)
		{
			$arrField = array(
					PayBackDef::PAYBACK_SQL_UID=> $uid,
					PayBackDef::PAYBACK_SQL_PAYBACK_ID => $paybackId,
					PayBackDef::PAYBACK_SQL_TIME_EXECUTE=>Util::getTime());
			$ret = PayBackDAO::insertIntoPayBackUserTable($arrField);
			if(!$ret)
			{
				return $ret;
			}
		}
		return true;
	}
	

	/**
	 * 检查时间参数是不是对的
	 * @param int $timeStart
	 * @param int $timeEnd
	 * @param string $info
	 * @return boolean
	 */
	public  static  function checkTimeValidate( $timeStart, $timeEnd, $info)
	{
		if (Empty($timeStart))
		{
			Logger::debug('%s failed ! startime is empty',$info);
			return false;
		}
		if (Empty($timeEnd))
		{
			Logger::debug('%s failed ! endtime is empty',$info);
			return false;
		}
		if ( !is_numeric ( $timeStart ) || 	!is_numeric ( $timeEnd )||
			 intval($timeStart) <= 0 ||  intval($timeEnd) <=0  	||
			 intval($timeStart) >= intval($timeEnd) )
		{
			Logger::warning('%s failed ! startime:%d endtime:%d',$info, $timeStart,$timeEnd);
			return false;
		}
		return true;
	}

	
	public static function  getAvailablePayBack($uid)
	{
		$arrRet = self::getAllPayBackUserInfoByUid($uid);
		$arrGotId = array();
		foreach ( $arrRet as $val )
		{
			$arrGotId[] = $val[PayBackDef::PAYBACK_SQL_PAYBACK_ID];
		}
		$arrInfo = self::getCurAvailablePayBackInfoList($arrGotId);
		$returnData = array();
		foreach ( $arrInfo as $val )
		{
			//转化成奖励中心的形式
			$returnData[] = array(
					RewardDef::SQL_RID => $val[PayBackDef::PAYBACK_SQL_PAYBACK_ID],
					RewardDef::SQL_SOURCE => RewardSource::SYSTEM_COMPENSATION,
					RewardDef::SQL_SEND_TIME => $val[PayBackDef::PAYBACK_SQL_TIME_START],
					RewardDef::EXPIR_TIME => $val[ PayBackDef::PAYBACK_SQL_TIME_END ],
					RewardDef::SQL_RECV_TIME => 0,
					RewardDef::SQL_DELETE_TIME => 0,
					RewardDef::SQL_VA_REWARD => $val[PayBackDef::PAYBACK_SQL_ARRY_INFO],
			);
		}
		return $returnData;
	}
	
	public static function getAvailableByArrId($uid, $arrId)
	{
		if(empty($arrId) )
		{
			return array();
		}
		$arrRet = self::getAvailablePayBack($uid);
		$arrRet = Util::arrayIndex($arrRet, RewardDef::SQL_RID);
		
		$returnData = array();
		foreach($arrId as $id)
		{
			if( empty($arrRet[$id]) )
			{
				Logger::fatal('id:%d not available now', $id);
				continue;
			}
			$returnData[] = $arrRet[$id];
		}
		return $returnData;
	}
	
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */