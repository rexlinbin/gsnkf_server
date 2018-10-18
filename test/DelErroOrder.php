<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

/**
 * 删除错单
 * @author wuqilin
 *
 */
class DelErroOrder extends BaseScript
{


	protected function executeScript($arrOption)
	{
		
		$uid = intval( $arrOption[0] );
		$specOrderId = '';
		if( isset( $arrOption[1]  ) )
		{
			$specOrderId = $arrOption[1];
		}
		
		$ret = UserDao::getUserByUid($uid, array('pid', 'uname','last_login_time') );
		if( empty($ret) )
		{
			printf("not found uid:%d\n", $uid);
			return;
		}
		$pid = $ret['pid'];
		$uname = $ret['uname'];
		$lastLoginTime = $ret['last_login_time'];
		
		printf("found uid:%d, pid:%d, uname:%s, last_login_time:%s\n", $uid, $pid, $uname, date('Y-m-d H:i:s', $lastLoginTime));
		
		
		$arrField = array(
				'order_id',
				'gold_num',
				'gold_ext',
				'from_unixtime(mtime) as mtime',
				'order_type'
		);
		$arrRet = User4BBpayDao::getArrOrderAllType($uid, $arrField);
		var_dump($arrRet);
		
		$needGold = 0;
		$realGold = 0;
		$arrTestOrderId = array();
		foreach($arrRet as $ret)
		{
			$trueOrder = true;
			if ( !empty($specOrderId) )
			{
				if( $ret['order_id'] == $specOrderId )
				{
					$trueOrder = false;
				}
			}
			else
			{
				if( $ret['order_type'] == OrderType::FULI_ORDER || $ret['order_type'] == OrderType::ERROR_FIX_ORDER)
				{
					$trueOrder = false;
				}
			}
			
			if ( $trueOrder ) 
			{
				$realGold += $ret['gold_num'];
			}
			else 
			{
				$needGold += $ret['gold_num'] + $ret['gold_ext'];
				$arrTestOrderId[] = $ret['order_id'];
				printf("found bad order. order_id:%s, gold_num:%d, gold_ext:%d time:%s\n",  
					$ret['order_id'], $ret['gold_num'], $ret['gold_ext'], $ret['mtime'] );
			}
		}
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);
		
		$curGold = $userObj->getGold();
		$curVip = $userObj->getVip();
		
		$subGold = min($needGold, $curGold);
		
		$setVip = 0;
		foreach (btstore_get()->VIP as $vipInfo)
		{
			if ($vipInfo['totalRecharge'] > $realGold)
			{
				break;
			}
			else
			{
				$setVip = $vipInfo['vipLevel'];
			}
		}
		
		$msg = sprintf("fix user. needGold:%d, realGold:%d curGold:%d, subGold:%d, curVip:%d, setVip:%d (y|n)\n",
				$needGold, $realGold, $curGold, $subGold, $curVip, $setVip);
		printf("%s", $msg);
		Logger::info('%s', $msg);
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			return;
		}
		
		
		Util::kickOffUser($uid);
	
		$userObj->subGold($subGold, 0);
		$userObj->setVip($setVip);
		
		$userObj->update();
		
		
		$data = new CData();
		$arrValue = array(
			'uid' => 1,
		);
		
		foreach( $arrTestOrderId as $orderId )
		{
			$data->update('t_bbpay_gold')->set($arrValue)
				->where('order_id', '==', $orderId)
				->where('uid', '=', $uid)
				//->where('order_type', 'in', array( OrderType::FULI_ORDER, OrderType::ERROR_FIX_ORDER ) )
				->query();
		}
		
		
		printf("done\n");
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */