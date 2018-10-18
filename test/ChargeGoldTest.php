<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeGoldTest.php 113637 2014-06-11 14:25:22Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/ChargeGoldTest.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-11 14:25:22 +0000 (Wed, 11 Jun 2014) $
 * @version $Revision: 113637 $
 * @brief 
 *  
 **/


/***
 * 给用户充1金。当开放新的vip时，通过这个脚本把那些充值达到要求的用户，vip增加上去
 * 
 * select uid,qid,sum(gold_num) as gold_sum from t_bbpay_gold  group by uid having gold_sum >=300000
 */
class ChargeGoldTest extends BaseScript
{

    protected function executeScript ($arrOption)
    {
    	$arrLine = file($arrOption[0]);

    	$send = false;
    	if( isset($arrOption[1]) && $arrOption[1] == 'send' )
    	{
    		$send = true;
    	}
    	 
    	
    	$goldSum = 300000;
    	$beginTime = strtotime('2014-06-11 21:20:00');
 
    	foreach( $arrLine as $line)
    	{
    		try
    		{
    			$arr = explode("\t", $line);
    			
    			$uid = intval($arr[0]);
    			$qid = intval($arr[1]);

    			$ret = UserDao::getUserByUid($uid, array('pid') );
    			if( empty($ret) )
    			{
    				Logger::warning('not found uid:%d', $uid);
    				continue;
    			}
    			
    			$ret = User4BBpayDao::getSumGoldByUid($uid);
    			if( $ret < $goldSum )
    			{
    				Logger::warning('uid:%d charge:%d', $uid, $ret);
    				continue;
    			} 
    			
    			
    			$data = new CData();
    			$ret = $data->select(array('order_id', 'mtime'))->from('t_bbpay_gold')
			    			->where('uid', '=', $uid)
			    			->where('mtime', '>', $beginTime )
			    			->where('order_type', '=', OrderType::FULI_ORDER)
			    			->query();
    			if( !empty( $ret ) )
    			{
    				Logger::warning('uid:%d already have fuli order. order:%s, time:%s',
    				$uid, $ret[0]['order_id'], date('Y-m-d H:i:s', $ret[0]['mtime']));
    				continue;
    			}
    			
    			if($send)
    			{
    				$orderId = sprintf("TEST_%d", time());
    					
    				$proxy = new ServerProxy();
    				$proxy->addGold($uid, $orderId, 1, 0, $qid, OrderType::FULI_ORDER);
    					
    				Logger::info('add 1 gold. uid:%d, orderId:%s',  $uid, $orderId);
    				sleep(1);
    			}
    			
    		}
    		catch( Exception $e )
    		{
    			Logger::fatal('failed:%s', $e->getMessage() );
    		}
    	}
    	printf("done\n");
    	
    	
    	
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */