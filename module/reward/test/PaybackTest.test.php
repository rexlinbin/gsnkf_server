<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PaybackTest.test.php 89907 2014-02-13 13:08:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/test/PaybackTest.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-02-13 13:08:30 +0000 (Thu, 13 Feb 2014) $
 * @version $Revision: 89907 $
 * @brief 
 *  
 **/
class PayBackTest extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	public function executeScript($arrOption)
	{
		$arrItemTpl = array(
 				self::getItemTpl(ItemDef::ITEM_TYPE_FEED) => 3,
 				self::getItemTpl(ItemDef::ITEM_TYPE_FRAGMENT) => 3,
		);
		$arryinfo=array(
				RewardType::GOLD => 100,
				RewardType::SILVER => 200,
				RewardType::SOUL => 300,
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
				PayBackDef::PAYBACK_TYPE => 1,
				PayBackDef::PAYBACK_MSG => 'test payback from script',
		);

		//mktime(hour,minute,second,month,day,year,is_dst)
		$time1=mktime(12,0,0,10,23,2012);
		$time2=mktime(15,0,0,10,23,2012);
		$time3=mktime(13,20,59,10,23,2012);
		$time4=mktime(16,30,10,10,23,2012);
		$time5=mktime(11,20,59,02,12,2014);
		$time6=mktime(12,30,10,10,23,2015);

  		$paybak=new PayBack();

// 		//插入两条条补偿信息
// 		$ret=self::addPayback($time5, $time6, $arryinfo);
// 		$ret=self::addPayback($time5 - 200, $time6 + 200, $arryinfo);
// 		var_dump($time5);
// 		var_dump($time6);
// 		var_dump(Util::getTime());

  		$proxy = new ServerProxy();
  		
// 		//修改一条补偿信息
// 		$newary=$arryinfo;
// 		$newary[RewardType::SOUL] =2000;
// 		$proxy->modifyPayBackInfo($time5, $time6, $newary);

		//根据指定的开始、结束时间，查询对应的补偿信息
// 		$ret = $proxy->getPayBackInfoByTime($time5, $time6);
//  		var_dump($ret);

		//获得指定ID的补偿信息
// 		$ret=$paybak->getPayBackInfoById(206);
// 		var_dump($ret);

		//开启某个补偿
// 		$ret=$proxy->openPayBackInfo(821);
// 		var_dump($ret);
		
//  		self::setTestUser();
// 		$ridone = self::sendReward();
// 		$ridtwo = self::sendReward();
//  		$paybackid = 201;
//  		self::getRewardList();
		
		//领取系统补偿
// 		self::gainReward( $paybackid );

		//系统补偿和普通奖励一起领
// 		self::gainRewardArr( array( $paybackid, $ridone, $ridtwo ) );
// 		self::getRewardList();
		//关闭某个补偿
		$ret=$proxy->closePayBackInfo(821);
		var_dump($ret);

	}
	
	public static function getItemTpl($itemType)
	{
		$allItemConf = btstore_get()->ITEMS->toArray();
	
		$itemTplId = 0;
		foreach($allItemConf as $id => $itemConf)
		{
			if($itemConf[ItemDef::ITEM_ATTR_NAME_TYPE] == $itemType)
			{
				$itemTplId = $id;
				break;
			}
		}
		return $itemTplId;
	}
	public static function setTestUser()
	{
		$data = new CData();
		$ret = $data->select(array('uid'))->from('t_user')
		->where(array('status', '!=', UserDef::STATUS_DELETED))
		->orderBy('last_login_time', false)->limit(0, 1)->query();
		if(empty($ret))
		{
			echo "no user for test\n";
			exit();
		}
		$uid = $ret[0]['uid'];
		
		RPCContext::getInstance ()->setSession ( 'global.uid', $uid );
	}
	public static function getRewardList()
	{
		$uid = RPCContext::getInstance()->getUid();
		$ret = RewardLogic::getRewardList($uid, 0,4);
		var_dump( $ret );
	}
	
	public static function sendReward()
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$arrItemTpl = array(
				self::getItemTpl(ItemDef::ITEM_TYPE_FEED) => 3,
				self::getItemTpl(ItemDef::ITEM_TYPE_HEROFRAG) => 3,
		);
		$arryinfo=array(
				RewardType::GOLD => 100,
				RewardType::SILVER => 200,
				RewardType::SOUL => 300,
				RewardType::ARR_ITEM_ID => ItemManager::getInstance()->addItems($arrItemTpl),
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
				RewardDef::EXT_DATA => array( 'message' => 'payBack test' ),
		);
		$rid = RewardLogic::sendReward( $uid , RewardSource::DIVI_REMAIN, $arryinfo);
		return $rid;
	}
	
	public static function gainReward( $rid )
	{
		$uid = RPCContext::getInstance()->getUid();
		RewardLogic::receiveReward($uid, $rid);
	}
	
	public static function gainRewardArr( $ridArr )
	{
		$uid = RPCContext::getInstance()->getUid();
		RewardLogic::receiveByRidArr($uid, $ridArr);
	}
	
	public static function setNewUser()
	{
		$pid = 40000 + rand(0,9999);
		$utid = 1;
		$uname = 't' . $pid;
		$ret = UserLogic::createUser($pid, $utid, $uname);
		$users = UserLogic::getUsers( $pid );
		$uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $uid);
	}
	
	public static function addPayback($timeStart, $timeEnd, $arrInfo)
	{
		$proxy = new ServerProxy();
		$proxy->addPayBackInfo($timeStart, $timeEnd, $arrInfo);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */