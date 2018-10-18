<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OpenMonthlyCard.php 122234 2014-07-22 12:56:17Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/OpenMonthlyCard.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-07-22 12:56:17 +0000 (Tue, 22 Jul 2014) $
 * @version $Revision: 122234 $
 * @brief 
 *  
 **/
class OpenMonthlyCard extends BaseScript
{
	protected function executeScript($arrOption)
	{
		//yueyu
		$startTime0 = strtotime('2014-07-18 11:00:00');
		//app
		$startTime2 = strtotime('2014-07-21 11:00:00');
		$goldNum = 300;
		
		$pid = $arrOption[0];
		$userInfo = UserDao::getArrUserByArrPid(array($pid), array('uid'));
		if(empty($userInfo) || empty($userInfo[0]['uid']))	
		{
			return;
		}
		$uid = $userInfo[0]['uid'];
		$fix = false;
		if(isset($arrOption[1]) &&  $arrOption[1] == 'fix')
		{
			$fix = true;
		}
		$group = $this->group;
		if (substr($group, 0, 7) != 'game400') 
		{
			return ;
		}
		$game = substr($group, 7, 1);
		$startTime = $game == 0 ? $startTime0 : $startTime2;
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$user = EnUser::getUserObj($uid);
		$uname = $user->getUname();
		echo "uname:$uname\n";
		Logger::info('uname:%s', $uname);
		$orders = self::getArrOrderByUid($uid, $goldNum, $startTime);
		if (empty($orders)) 
		{
			echo "user:$uid charge gold no more than $goldNum\n";
			Logger::info('user charge gold no more than %d', $goldNum);
			return ;
		}
		
		$cardId = DiscountCardDef::MONTHLYCATD_ID;
		$cardInst = MonthlyCardObj::getInstance($uid, $cardId);
		$cardInfo = $cardInst->getCardInfo();
		if(!empty($cardInfo))
		{
			echo "user:$uid has bought monthly card already\n";
			Logger::info('user has bought monthly card already');
			return;
		}
		echo "user:$uid can open monthly card\n";
		$cardInst->buyCard();
		$cardInst->setGiftStatus(MONTHCARD_GIFTSTATUS::HASGIFT);
		if ($fix) 
		{
			$cardInst->save();
			echo "open monthly card success, $group, pid:$pid, uid:$uid, uname:$uname\n";
			Logger::info('open monthly card success, %s, pid:%s, uid:%d, uname:%s', $group, $pid, $uid, $uname);
		}
		echo "done\n";
	}
	
	public static function getArrOrderByUid($uid, $goldNum, $startTime)
	{
		$data = new CData();
		$ret = $data->select(array('order_id'))
					->from('t_bbpay_gold')
					->where('gold_num', '>=', $goldNum)
					->where('mtime', '>=', $startTime)
					->where('status', '=', '1')
					->where('order_type', '=', 0)
					->orderBy('mtime', true)
					->orderBy('order_id', true)
					->query();
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */