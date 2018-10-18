<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TryReward.php 64054 2013-09-11 05:49:14Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/TryReward.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-09-11 05:49:14 +0000 (Wed, 11 Sep 2013) $
 * @version $Revision: 64054 $
 * @brief 
 *  
 **/
class DoBattleNCopy extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		/**
		 * uid
		 * 
		 */
		
		$uid = 0;
		$step = 0;
		$arrRid =0;
		$arrRewardValue =0;
		$arrAllItemId = 0;
		$userValueOk = false;
		$arrAddedItemId = array();
		$op = 'check';
		
		list($retMsg, $changeBag, $arrReOpenRid) = self::reissue($uid, $step, $arrRid, $arrRewardValue, $arrAllItemId, $userValueOk, $arrAddedItemId);
		
	
		if($op == 'fix')
		{
			if($changeBag)
			{
				BagManager::getInstance()->getBag($uid)->update();	
				$retMsg .= sprintf("bag updated\n");
			}
			if( !empty($arrReOpenRid) )
			{
				self::reOpenReward($uid, $arrReOpenRid);
				$retMsg .= sprintf("reward updated\n");
			}
		}
		
		echo $retMsg;
	}


	public static function reissue($uid, $step, $arrRid, $arrRewardValue, $arrAllItemId, $userValueOk=true, $arrAddedItemId=array())
	{
		$retMsg = '';
		$changeBag = false;
		$arrReOpenRid = array();
		list($arrPaybackInfo, $arrRewardInfo, $arrPaybackId, $arrRid) = self::getInfoByArrRid($uid, $arrRid);
		
		if( !empty($arrPaybackId) )
		{
			$retMsg .= sprintf("not fix payback:%s\n", var_export($arrPaybackId, true));
		}
		if(count($arrPaybackInfo) != count($arrPaybackId))
		{
			$retMsg .= sprintf("maybe some payback out of date. arrId:%s, arrInfo:%s\n", 
						var_export($arrPaybackId, true), var_export($arrPaybackInfo,true) );
		}
		if(count($arrRewardInfo) != count($arrRid))
		{
			$retMsg .= sprintf("some reward not found. arrId:%s, arrInfo:%s\n",
						var_export($arrRid, true), var_export($arrRewardInfo,true) );
		}
		
		switch($step)
		{
			case RewardDef::REWARD_STEP_SET_PAYBACK:
				/*
				 * 标记系统补偿时失败
				 * 	结果1：标记失败，玩家爱你没有任何损失，不用处理
				 * 	结果2：标记成功，玩家损失若干系统补偿，这个情况应该极少，人工处理吧
				 */
				$retMsg .= sprintf("not support reissue when failed in set payback. info:%s\n", var_export($arrPaybackId, true));
				
				break;
				
			case RewardDef::REWARD_STEP_SET_REWARD:

				/*
				 * 标记奖励时失败，玩家可能损失系统补偿，和若干奖励
				 * 处理方式：
				 * 	系统补偿不处理
				 * 	将失败的奖励恢复
				 */
				//self::reOpenReward($arrRid);
				$arrReOpenRid = $arrRid;
				$retMsg .= sprintf("reopen reward:%s\n", var_export($arrRid, true) );
				
				break;

			case RewardDef::REWARD_STEP_USER:
				/*
				 * 发用户数值时失败
				 * 结果1：用户数值实际加上， 处理方式：补发物品
				 * 结果2：用户数值没有加上，处理方式：重置奖励
				 */
				if($userValueOk)
				{
					$ret = self::sendRewardItem($uid, $arrRewardValue);
					$retMsg .= sprintf("use value ok, just reissue item:%s\n", var_export($ret, true));
					$changeBag = true;
				}
				else
				{
					//self::reOpenReward($arrRid);
					$arrReOpenRid = $arrRid;
					$retMsg .= sprintf("reopen reward:%s\n", var_export($arrRid, true) );
				}
				break;
				
			case RewardDef::REWARD_STEP_ITEM:	
				/*
				 * 插入新物品失败
				 * 补发物品
				 */
				$ret = self::sendRewardItem($uid, $arrRewardValue);
				$retMsg .= sprintf("insert new item failed, just reissue item:%s\n", var_export($ret, true));
				$changeBag = true;
				break;
			
			case RewardDef::REWARD_STEP_BAG:
				/*
				 * 添加背包失败
				 */
				$arrLostItemId = array_diff($arrAllItemId, $arrAddedItemId);
				$arrMissedItemId = array();
				foreach($arrLostItemId as $itemId)
				{
					$item = ItemManager::getInstance()->getItem($itemId);
					if($item == null)
					{
						$arrMissedItemId[] = $itemId;
					}
				}
				if(!empty($arrMissedItemId))
				{
					$retMsg .= sprintf("cant found itemId:%s\n", var_export($arrMissedItemId, true));
					break;
				}
				$bag = BagManager::getInstance()->getBag();
				if ( $bag->addItems($arrLostItemId) == false )
				{
					$retMsg .= sprintf("oh add item failed itemId:%s\n", var_export($arrLostItemId, true));
					break;
				}
				$changeBag = true;
				break;
			default:
				$retMsg .= sprintf("invalid step:%d", $step);
				break;
		}
	
	
		$retMsg .= "done\n";
		
	
		return array($retMsg, $changeBag, $arrReOpenRid);
	}
	
	public static function sendRewardItem($uid, $arrRewardValue)
	{
		$arrReward = array(
				'arrItemTpl' => $arrRewardValue['arrItemTpl'],
				'arrItemId' => $arrRewardValue['arrItemId'],
		);
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$ret = RewardLogic::reward($uid, $arrReward);
		ItemManager::getInstance()->update();
		$ret = BagManager::getInstance()->getBag($uid)->update();
		
		return $ret;
	}
	
	public static function reOpenReward($uid, $arrRid)
	{
		$arrField = array(
				RewardDef::SQL_RECV_TIME => 0,
				RewardDef::SQL_SEND_TIME => Util::getTime(),
		);
		RewardDao::updateByArrId($uid, $arrField, $arrRid);
	}
	
	
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */