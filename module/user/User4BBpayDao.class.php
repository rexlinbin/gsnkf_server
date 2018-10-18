<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: User4BBpayDao.class.php 246779 2016-06-17 04:00:21Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/User4BBpayDao.class.php $
 * @author $Author: GuohaoZheng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-06-17 04:00:21 +0000 (Fri, 17 Jun 2016) $
 * @version $Revision: 246779 $
 * @brief 
 *  
 **/

class User4BBpayDao
{
	const tblUser = 't_user';
	const tblBBpay = 't_bbpay_gold';
	const tblBBpayItem = 't_bbpay_item';
	
	/*
	 * 注意：使用此接口时需要完整传入参数，否则首充记录会被清掉
	 */
	public static function update($uid, $orderId, $addGold, $addGoldExt, $payBack, $qid, $orderType, $level, $useItemGold = 0, $arrChargeInfo = array())
	{
		$batch = new BatchData();
		$userData = $batch->newData();
		
		$allGold = $addGold + $addGoldExt + $payBack;
		if ($allGold>0)
		{
			$opGold = new IncOperator($allGold);
		}
		else
		{
			$opGold = new DecOperator($allGold);
		}
		
		// 设置vip, $addGoldExt不加vip
		$sumGold = self::getSumGoldByUid($uid);
		$sumGold += $addGold;
		
		// 加上使用道具增加金币而获得的vip经验
		$sumGold += $useItemGold;
		
		$vip = 0;
		foreach (btstore_get()->VIP as $vipInfo)
		{
			if ($vipInfo['totalRecharge'] > $sumGold)
			{
				break;
			}
			else
			{
				$vip = $vipInfo['vipLevel'];
			}
		}
				
		//给用户加金币, 设置vip等级
		$userData->update(self::tblUser)->set(array('gold_num'=>$opGold, 'vip'=>$vip, 'va_charge_info' => $arrChargeInfo))->where('uid', '=', $uid)->query();

		//往t_bbpay_gold添加订单
		$bbpayField = array('order_id' => $orderId, 'uid' => $uid, 
			'gold_num'=>$addGold, 'gold_ext'=>$addGoldExt, 'status'=>1, 
			'mtime'=>Util::getTime(), 'level'=>$level);
		if (!empty($qid))
		{
			$bbpayField['qid'] = $qid;
		}
		$bbpayField['order_type'] = $orderType;
		$bbpayData = $batch->newData();
		$bbpayData->insertInto(self::tblBBpay)->values($bbpayField)->query();
		
		$batch->query();
		
		//worldVip==
		if( defined('PlatformConfig::WORLD_VIP') && PlatformConfig::WORLD_VIP > WorldDef::WORLD_VIP_CLOSE )
		{
			$user = EnUser::getUserObj($uid);
			$pid = $user->getPid();
			UserWorldDao::updateUserWorldGold( $pid, $sumGold );
		}
		
		return $vip;
	}
	
	public static function update4setVip($uid, $vip, $orderId, $needGold)
	{
		$batch = new BatchData();
		$userData = $batch->newData();
						
		//给用户 设置vip等级
		$userData->update(self::tblUser)->set(array('vip'=>$vip))->where('uid', '=', $uid)->query();

		//往t_bbpay_gold添加订单
		$bbpayField = array('order_id' => $orderId, 'uid' => $uid, 
			'gold_num'=>$needGold, 'status'=>1, 'mtime'=>Util::getTime());
		$bbpayData = $batch->newData();
		$bbpayData->insertInto(self::tblBBpay)->values($bbpayField)->query();
		
		$batch->query();
		
	}
	
	public static function getSumGoldByUid($uid, $needBaseNum = 1)
	{
		$data = new CData();
		$ret = $data->select(array('sum(gold_num)'))->from(self::tblBBpay)->where('uid', '=', $uid)->query();
		$chargeGold = 0;
		if (!empty($ret))
		{
			$chargeGold =  intval($ret[0]['sum(gold_num)']);
		}
		$ret = $data->select(array('sum(gold_num)'))->from(self::tblBBpayItem)->where('uid', '=', $uid)->query();
		if (!empty($ret))
		{
		    $chargeGold +=  intval($ret[0]['sum(gold_num)']);
		}
		//worildVip==
		if( 1== $needBaseNum )
		{
			$user = EnUser::getUserObj($uid);
			$baseGold = $user->getBaseGoldNum();
			$chargeGold += $baseGold;
		}
		
		return $chargeGold;
	}
	
	public static function getByOrderId($orderId, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblBBpay)->where('order_id', 'LIKE', $orderId)->query();
		if (!empty($ret))
		{
			return $ret[0];
		}
		return $ret;
	}
	
	public static function getByItemOrderId($orderId, $arrField)
	{
	    $data = new CData();
	    $ret = $data->select($arrField)->from(self::tblBBpayItem)->where('order_id', 'LIKE', $orderId)->query();
	    if (!empty($ret))
	    {
	        return $ret[0];
	    }
	    return $ret;
	}
	
	public static function getArrOrder($arrField, $beginTime, $endTime, $offset, $limit, $orderType)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from('t_bbpay_gold')->where('mtime', 'between', array($beginTime, $endTime))
				->where('status', '=', '1')->where('order_type', '=', $orderType)
				->orderBy('mtime', true)->orderBy('order_id', true)->limit($offset, $limit)
				->query();
		return $ret;
	}
	
	public static function getArrItemOrder($arrField, $beginTime, $endTime, $offset, $limit)
	{
	    $data = new CData();
	    $ret = $data->select($arrField)->from(self::tblBBpayItem)->where('mtime', 'between', array($beginTime, $endTime))
            	    ->where('status', '=', '1')
            	    ->orderBy('mtime', true)->orderBy('order_id', true)->limit($offset, $limit)
            	    ->query();
	    return $ret;
	}
	
	public static function getArrOrderByUid($uid, $orderType, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblBBpay)->where('uid', '=', $uid)
			->where('order_type', '=', $orderType)->query();
		return $ret;
	}
	
	public static function getArrItemOrderAllType($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblBBpayItem)->where('uid', '=', $uid)->query();
		return $ret;
	}
	
	public static function getArrOrderAllType($uid, $arrField)
	{
		$data = new CData();
		$ret = $data->select($arrField)->from(self::tblBBpay)->where('uid', '=', $uid)->query();
		return $ret;		
	}
	
	public static function getSumGoldByTime($time1, $time2, $uid, $includeItem=FALSE)
	{
	    $sumGold = 0;
		$data = new CData();
		$ret = $data->select(array('sum(gold_num)'))->from(self::tblBBpay)
			->where('uid', '=', $uid)
			->where('mtime', 'BETWEEN', array($time1,$time2) )
			->query();
		if (!empty($ret[0]['sum(gold_num)']))
		{
			$sumGold += $ret[0]['sum(gold_num)'];
		}
		if($includeItem)
		{
    		$ret = $data->select(array('sum(gold_num)'))->from(self::tblBBpayItem)
        		->where('uid', '=', $uid)
        		->where('mtime', 'BETWEEN', array($time1,$time2) )
        		->query();
    
    		if (!empty($ret[0]['sum(gold_num)']))
    		{
    		    $sumGold += $ret[0]['sum(gold_num)'];
    		}
		}
		return intval($sumGold);
	}
	
	

	public static function isPay($uid, $startTime=0, $endTime=PHP_INT_MAX)
	{
		$data = new CData();
		$arrOrderType = array(
				OrderType::ERROR_FIX_ORDER, OrderType::FULI_ORDER, OrderType::NORMAL_ORDER
		);
		$ret = $data->select(array('uid', 'order_id'))->from(self::tblBBpay)
					->where('uid', '=', $uid)
					->where('order_type', 'in', $arrOrderType)
					->where('mtime', 'BETWEEN', array($startTime, $endTime))
					->limit(0, 1)
					->query();
		if (empty($ret))
		{
			return false;
		}
		return true;
	}
	
	/**
	 * 获取玩家在某一个时间段内的所有订单
	 * @param int $time1
	 * @param int $time2
	 * @param int $uid
	 * @param bool $includeItem
	 */
	public static function getArrOrderByTime($time1, $time2, $uid, $includeItem=TRUE)
	{
		$data = new CData();
		$goldOrder = $data->select(array('order_id','gold_num','mtime'))
							->from(self::tblBBpay)
							->where('uid', '=', $uid)
							->where('mtime', 'BETWEEN', array($time1,$time2) )
							->query();
		$itemOrder = array();
		if($includeItem)
		{
			$itemOrder = $data->select(array('order_id','gold_num','mtime'))
							->from(self::tblBBpayItem)
							->where('uid', '=', $uid)
							->where('mtime', 'BETWEEN', array($time1,$time2) )
							->query();
		}
		return array_merge($goldOrder,$itemOrder);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */