<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixcw.php 243108 2016-05-17 06:07:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/fixcw.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-17 06:07:36 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243108 $
 * @brief 
 *  
 **/
 
class fixcw extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 2) 
		{
			Logger::warning('invalid param, need param: server_id, pid, do|check');
		}
		
		$serverId = intval($arrOption[0]);
		$pid = intval($arrOption[1]);
		$do = FALSE;
		if (isset($arrOption[2]) && $arrOption[2] == 'do') 
		{
			$do = TRUE;
		}
		Logger::info('deal serverId[%d], pid[%d], do[%s] begin!', $serverId, $pid, $do ? 'do' : 'check');
		
		$arrField = array('uid', 'uname', 'level', 'vip');
		$arrRet = UserDao::getArrUserByPid($pid, $arrField, $serverId);
		if (empty($arrRet)) 
		{
			Logger::warning('no user info');
			return ;
		}
		
		$userInfo = $arrRet[0];
		Logger::info('user info uid[%d], uname[%s], level[%d], vip[%d]', $userInfo['uid'], $userInfo['uname'], $userInfo['level'], $userInfo['vip']);
		
		$uid = $userInfo['uid'];
		$startTime = strtotime('2015-12-25 10:00:00');
		$msg = '24号国战异常补偿:';
		$arrReward = array
		(
				RewardType::COPOINT => 1800,
				RewardType::ARR_ITEM_TPL => array(20043 => 1),
				RewardDef::TITLE => '国战补偿:',
				RewardDef::MSG => $msg,
		);
		if ($do)
		{
			$ret = $this->getRewardFromCenter($uid, $startTime, RewardSource::SYSTEM_GENERAL, $msg);
			if (!empty($ret))
			{
				Logger::warning('uid[%d] already send at[%s]!!!!!!', $uid, strftime('%Y%m%d %H:%M%S', $ret['send_time']));
			}
			else
			{
				Logger::info('uid[%d] send now', $uid);
				EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $arrReward);
			}
		}
		
		Logger::info('deal serverId[%d], pid[%d], do[%s] done!', $serverId, $pid, $do ? 'do' : 'check');
	}
	
	public function getRewardFromCenter($uid, $startTime, $rewardSource, $msg)
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
		
		foreach ($ret as $aReward)
		{
			if ($aReward['va_reward']['msg'] == $msg) 
			{
				return $aReward;
			}
		}
	
		return array();
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */