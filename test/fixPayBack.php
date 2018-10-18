<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixPayBack.php 243108 2016-05-17 06:07:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/fixPayBack.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-17 06:07:36 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243108 $
 * @brief 
 *  
 **/
 
class fixPayBack extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		// 配置
		$do = FALSE;
		if (isset($arrOption[0]) && $arrOption[0] == 'do') 
		{
			$do = TRUE;
		}
		$specUid = 0;
		if (isset($arrOption[1]) && is_numeric($arrOption[1])) 
		{
			$specUid = intval($arrOption[1]);
		}
		$startTime = strtotime('2015-12-18 00:00:00');
		$endTime = strtotime('2015-12-18 00:54:59');
		
		
		
		//$startTime = strtotime('2015-11-20 00:00:00');
		//$endTime = strtotime('2015-12-27 00:56:00');
		
		
		// 取数据
		$arrOrder = array();
		$offset = 0;
		$limit = 100;
		while (TRUE)
		{
			$arrField = array('order_id', 'uid', 'gold_num', 'mtime', 'order_type');
			$arrCond = array
			(
					array('order_type', '=', '0'),
					array('mtime', 'BETWEEN', array($startTime, $endTime)),
			);
			$data = new CData();
			$data->select($arrField)->from('t_bbpay_gold');
			foreach ($arrCond as $aCond)
			{
				$data->where($aCond);
			}
			$data->limit($offset, $limit);
			$data->orderBy('uid', 'ASC');
			$data->orderBy('mtime', 'ASC');
			$partOrder = $data->query();
			$arrOrder = array_merge($arrOrder, $partOrder);
			if (count($partOrder) < 100) 
			{
				break;
			}
			else 
			{
				$offset += 100;
			}
		}
		
		// 将符合时间范围内的订单规整为 uid => array(mtime => array())这样的格式
		$arrUidOrder = array();
		foreach ($arrOrder as $aOrder)
		{
			$arrUidOrder[$aOrder['uid']][$aOrder['mtime']] = $aOrder;
		}
		
		// 循环处理每一个玩家的所有订单
		foreach ($arrUidOrder as $aUid => $aArrOrder)
		{
			if ($specUid > 0 && $aUid != $specUid) 
			{
				continue;
			}
			
			$firstPayReward = $this->getRewardFromCenter($aUid, $startTime, RewardSource::FIRST_TOPUP);
			if (!empty($firstPayReward)) // 如果奖励中心有首充建立，则忽略
			{
				if ($this->isRealFirstPay($aUid, $firstPayReward)) 
				{
					$arrReward = array
					(
							'arrItemTpl' => array(101412 => 1),
							RewardDef::TITLE => '首充补发:',
							RewardDef::MSG => '18号凌晨首充异常补发:',
					);
					
					Logger::info('real first pay, uid[%d] get first pay reward at time[%s]', $aUid, strftime('%Y%m%d %H:%M:%S', $firstPayReward['send_time']));
					printf("\n****deal uid[%d] for real first pay**************************************************************\n", $aUid);
					var_dump($arrReward);
					
					if ($do)
					{
						$ret = $this->getRewardFromCenter($aUid, $startTime, RewardSource::SYSTEM_GENERAL);
						if (!empty($ret))
						{
							Logger::warning('uid[%d] already send!!!!!!', $aUid);
							printf("uid[%d] already send!!!!!!\n", $aUid);
							var_dump($ret);
						}
						else
						{
							Logger::info('uid[%d] send now', $aUid);
							printf("uid[%d] send now!!!!!!!!\n", $aUid);
							
							EnReward::sendReward($aUid, RewardSource::SYSTEM_GENERAL, $arrReward);
						}
					}
				}
				else 
				{
					Logger::info('ignore, uid[%d] alread get first pay reward at time[%s]', $aUid, strftime('%Y%m%d %H:%M:%S', $firstPayReward['send_time']));
					printf("****deal uid[%d], ignore, have reward\n", $aUid);
				}
			}
			else // 处理有订单，但是没有首充奖励的玩家
			{
				printf("\n****deal uid[%d]**************************************************************\n", $aUid);
				foreach ($aArrOrder as $aTime => $aOrder)
				{
					Logger::info('uid[%d], order id[%s], mtime[%s], gold_num[%d]', $aUid, $aOrder['order_id'], strftime('%Y%m%d %H:%M:%S', $aOrder['mtime']), $aOrder['gold_num']);
					printf("uid[%d], order id[%s], mtime[%s], gold_num[%d]\n", $aUid, $aOrder['order_id'], strftime('%Y%m%d %H:%M:%S', $aOrder['mtime']), $aOrder['gold_num']);
					printf("\n");
				}
				$minMtime = min(array_keys($aArrOrder));
				Logger::info('uid[%d] first pay time[%s], order id[%s], gold_num[%d]', $aUid, strftime('%Y%m%d %H:%M:%S', $minMtime), $aArrOrder[$minMtime]['order_id'], $aArrOrder[$minMtime]['gold_num']);
				printf("uid[%d] first pay time[%s], order id[%s], gold_num[%d]\n", $aUid, strftime('%Y%m%d %H:%M:%S', $minMtime), $aArrOrder[$minMtime]['order_id'], $aArrOrder[$minMtime]['gold_num']);
				$this->dealOneFirstPay($aUid, $aArrOrder[$minMtime], $startTime, $do);				
			}
		}
	}
	
	public function isRealFirstPay($uid, $firstPayReward)
	{
		$endTime = strtotime('2015-12-18 00:00:00');
		$count = EnUser::getRechargeGoldByTime(0, $endTime, $uid);
		$items = $firstPayReward['va_reward']['arrItemTpl'];
		
		Logger::info('isRealFirstPay : items:%s', $items);
		
		return $count <= 0 && empty($items[101412]);
	}
	
	public function dealOneFirstPay($aUid, $aOrder, $startTime, $do = FALSE)
	{
		$arrReward = $this->getCompensationReward($aOrder['gold_num']);
		
		Logger::info('uid[%d], final reward[%s]', $aUid, $arrReward);
		printf("uid[%d], final reward:\n", $aUid);
		$arrReward[RewardDef::TITLE] = '首充补发:';
		$arrReward[RewardDef::MSG] = '18号凌晨首充异常补发:';
		var_dump($arrReward);
		
		if ($do)
		{
			$ret = $this->getRewardFromCenter($aUid, $startTime, RewardSource::SYSTEM_GENERAL);
			if (!empty($ret)) 
			{
				Logger::warning('uid[%d] already send!!!!!!', $aUid);
				printf("uid[%d] already send!!!!!!\n", $aUid);
				var_dump($ret);
			}
			else 
			{
				Logger::info('uid[%d] send now', $aUid);
				printf("uid[%d] send now!!!!!!!!\n", $aUid);
				EnReward::sendReward($aUid, RewardSource::SYSTEM_GENERAL, $arrReward);
			}
			
		}
	}
	
	public function getCompensationReward($goldNum)
	{
		$oldPayBack = UserLogic::getPayBack($goldNum, FALSE);
		$newPayBack = UserLogic::getPayBack($goldNum, TRUE);
		Logger::info('getCompensationReward : gold num[%d], old pay back[%d], new pay back[%d]', $goldNum, $oldPayBack, $newPayBack);
		printf("gold num[%d], old pay back[%d], new pay back[%d]\n", $goldNum, $oldPayBack, $newPayBack);
		
		$payConf = UserLogic::getPayConf(TRUE);
        $arrReward = $payConf['reward'];
        if ($newPayBack > $oldPayBack) 
        {
        	if (empty($arrReward['gold'])) 
        	{
        		$arrReward['gold'] = 0;
        	}
        	$arrReward['gold'] += intval($newPayBack - $oldPayBack);
        }
        else
        {
        	Logger::warning('getCompensationReward : new is less or equal than old');
        }
        
		return $arrReward;
	}
	
	public function getRewardFromCenter($uid, $startTime, $rewardSource)
	{
		$arrField = array('uid', 'rid', 'source', 'send_time', 'recv_time', 'va_reward');
		$arrCond = array
		(
				array('uid', '=', $uid),
				array('source', '=', $rewardSource),
				array('send_time', '>=', $startTime),
		);
		$data = new CData();
		$data->select($arrField)->from('t_reward');
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$ret = $data->query();
		
		return empty($ret) ? array() : $ret[0];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */