<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixWrongReward.php 247927 2016-06-24 03:04:43Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/fixWrongReward.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-24 03:04:43 +0000 (Fri, 24 Jun 2016) $
 * @version $Revision: 247927 $
 * @brief 
 *  
 **/
 
class fixWrongReward extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$do = false;
		if (!empty($arrOption[0]) && $arrOption[0] == 'do')
		{
			$do = true;
			array_shift($arrOption);
		}
				
		$arrSpecUid = $arrOption;
		
		$arrUserRewardList = $this->getRewardList();
		
		foreach ($arrUserRewardList as $aUid => $arrRewardList)
		{
			if (!empty($arrSpecUid) && !in_array($aUid, $arrSpecUid))
			{
				continue;
			}
				
			if (count($arrRewardList) <= 5)
			{
				$msg = sprintf("uid:%d, reward count:%d, ignore", $aUid, count($arrRewardList));
				$this->mylog($msg);
				continue;
			}
				
			$arrNotRecvReward = array();
			$arrRecvReward = array();
			foreach ($arrRewardList as $aReward)
			{
				$rid = $aReward['rid'];
				$recvTime = $aReward['recv_time'];
				if ($recvTime === 0)
				{
					$arrNotRecvReward[$rid] = $aReward;
				}
				else
				{
					$arrRecvReward[$rid] = $aReward;
				}
			}
				
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession('global.uid', $aUid);
			if ($do)
			{
				Util::kickOffUser($aUid);
			}
				
			if (count($arrRecvReward) <= 5)
			{		
				$arrNeedDeleteReward = array_slice($arrNotRecvReward, 0, count($arrRewardList) - 5);
				$arrNeedDeleteReward = Util::arrayIndex($arrNeedDeleteReward, 'rid');
				$arrNeedDeleteRid = array_keys($arrNeedDeleteReward);
				$msg = sprintf("uid:%d, delete rid:%s", $aUid, var_export($arrNeedDeleteRid, true));
				$this->mylog($msg);
				if ($do)
				{
					$this->deleteRewardFromCenter($aUid, $arrNeedDeleteRid);
				}
			}
			else
			{
			    if (!empty($arrNotRecvReward)) 
			    {
			    	$arrNeedDeleteReward = $arrNotRecvReward;
			    	$arrNeedDeleteReward = Util::arrayIndex($arrNeedDeleteReward, 'rid');
			    	$arrNeedDeleteRid = array_keys($arrNeedDeleteReward);
			    	$msg = sprintf("uid:%d, delete rid:%s", $aUid, var_export($arrNeedDeleteRid, true));
			    	$this->mylog($msg);
			    	if ($do)
			    	{
			    		$this->deleteRewardFromCenter($aUid, $arrNeedDeleteRid);
			    	}
			    }

			    $arrWrongRewardList = array_slice($arrRecvReward, 5);
			    $arrWrongReward = $this->getWrongRewardFromList($arrWrongRewardList);
			    if ($arrWrongReward === FALSE) 
			    {
			    	$msg = sprintf("uid:%d, found none item reward:%s", $aUid, var_export($arrWrongRewardList, true));
			    	$this->mylog($msg, true);
			    	continue;
			    }
				
				$arrLackInfo = $this->deleteRewardFromUser($aUid, $arrWrongReward);
				$lack = false;
				foreach ($arrLackInfo as $lackItem => $lackNum)
				{
					if ($lackNum > 0)
					{
						$lack = true;
						break;
					}
				}
				
				if ($lack) 
				{
					$msg = sprintf("uid:%d, delete lack, reward:%s, lack:%s", $aUid, var_export($arrWrongReward, true), var_export($arrLackInfo, true));
					$this->mylog($msg);
				}
				else 
				{
					$msg = sprintf("uid:%d, delete ok, reward:%s", $aUid, var_export($arrWrongReward, true));
					$this->mylog($msg);
				}
			}	
				
			if ($do)
			{
				EnUser::getUserObj($aUid)->update();
				BagManager::getInstance()->getBag($aUid)->update();
			}
		}
	}
	
	public function getWrongRewardFromList($arrWrongRewardList)
	{
		$arrReward = array();
		
		foreach ($arrWrongRewardList as $aReward)
		{
			$curReward = $aReward['va_reward'];
			foreach ($curReward as $type => $info)
			{
				if ($type == 'arrItemTpl')
				{
					foreach ($info as $itemTplId => $itemNum)
					{
						if (!isset($arrReward[$itemTplId])) 
						{
							$arrReward[$itemTplId] = 0;
						}
						$arrReward[$itemTplId] += $itemNum;
					}
				}
				else
				{
					return FALSE;
				}
			}
		}
		
		return $arrReward;
	}
	
	public function getRewardList()
	{
		$sendTime = strtotime('2016-06-23 12:00:00');
	
		$arrRewardList = array();
	
		for ($i = 0; $i < 10; ++$i)
		{
			$table = 't_reward_' . $i;
			$arrField = array('uid', 'rid', 'source', 'send_time', 'recv_time', 'va_reward');
			
			$compareTime = Util::getTime();
			$currRid = PHP_INT_MAX;
			$offset = 0;
			$limit = 500;
			while ($compareTime >= $sendTime)
			{
				$data = new CData();
				$data->select($arrField)->from($table);
				$arrCond = array
				(
						array('source', 'IN', array(12)),
						array('send_time', '>=', $sendTime),
						array('rid', '<', $currRid),
				);
				foreach ($arrCond as $aCond)
				{
					$data->where($aCond);
				}
				$data->orderBy('rid', false);
				$data->limit($offset, $limit);
				$ret = $data->query();
				$arrRewardList = array_merge($arrRewardList, $ret);
				
				if (count($ret) < $limit) 
				{
					break;
				}
				else 
				{
					$lastReward = end($ret);
					$compareTime = $lastReward['send_time'];
					$currRid = $lastReward['rid'];
				}
				$offset += $limit;
			}
		}
		
		foreach ($arrRewardList as $index => $aReward)
		{
			$uid = $aReward['uid'];
			$rid = $aReward['va_reward'];
			$curReward = $aReward['va_reward'];
			if (isset($curReward["gold"])) 
			{
				$msg = sprintf("uid:%d, rid:%d, include gold,ignore", $uid, $rid);
				$this->mylog($msg);
				unset($arrRewardList[$index]);
			}
		}
	
		$arrRet = array();
		foreach ($arrRewardList as $aReward)
		{
			$aUid = $aReward['uid'];
			if (empty($arrRet[$aUid]))
			{
				$arrRet[$aUid] = array();
			}
			$arrRet[$aUid][] = $aReward;
		}
	
		return $arrRet;
	}

	public function deleteRewardFromCenter($uid, $arrDeleteRid)
	{
		$arrField = array(RewardDef::SQL_DELETE_TIME => Util::getTime());
		RewardDao::updateByArrId($uid, $arrField, $arrDeleteRid);
	}

	public function deleteRewardFromUser($uid, $arrReward)
	{
		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		
		$arrLackInfo = array();
		
		foreach ($arrReward as $itemTplId => $itemNum)
		{
			$curItemNum = $bag->getItemNumByTemplateID($itemTplId);
			$needSubItemNum = min($curItemNum, $itemNum);
			$lackItemNum = $itemNum - $needSubItemNum;
			$bag->deleteItembyTemplateID($itemTplId, $needSubItemNum);
			$arrLackInfo[$itemTplId] = $lackItemNum;
		}
		
		return $arrLackInfo;
	}

	public function mylog($msg, $warn = false)
	{
		printf("%s\n", $msg);
		$warn ? Logger::warning($msg) : Logger::info($msg);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */