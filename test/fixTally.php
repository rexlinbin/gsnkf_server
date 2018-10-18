<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixTally.php 243108 2016-05-17 06:07:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/fixTally.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-17 06:07:36 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243108 $
 * @brief 
 *  
 **/
 
class fixTally extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$do = FALSE;
		if (isset($arrOption[0]) && $arrOption[0] == 'do') 
		{
			$do = TRUE;
		}
		
		$arrSpecUid = array();
		
		$arrUid2ServerId = $this->getUserServerId();
		
		$arrHeroInfo = $this->getHeroList();
		
		foreach ($arrHeroInfo as $aHid => $aHeroInfo)
		{
			$aUid = $aHeroInfo['uid'];
			if (!empty($arrSpecUid) && !in_array($aUid, $arrSpecUid)) 
			{
				$msg = sprintf("uid:%d ignore\n", $aUid);
				printf($msg);
				Logger::info($msg);
				continue;
			}
			
			$aVa = $aHeroInfo['va_hero'];
			if (empty($aVa['tally'])) 
			{
				$msg = sprintf("uid:%d hid:%d empty tally\n", $aUid, $aHid);
				printf($msg);
				Logger::info($msg);
				continue;
			}
			
			if (empty($arrUid2ServerId[$aUid]))
			{
				$msg = sprintf("uid:%d no serverid\n", $aUid);
				printf($msg);
				Logger::warning($msg);
				continue;
			}
			
			$serverId = intval($arrUid2ServerId[$aUid]);
			
			foreach ($aVa['tally'] as $pos => $oldItemId)
			{
				$newItemId = $this->getNewItemId($serverId, $oldItemId);
				if (!empty($newItemId)) 
				{
					$msg = sprintf("serverId:%d, uid:%d, hid:%d, old:%d, new:%d, type:%s", $serverId, $aUid, $aHid, $oldItemId, $newItemId, $do ? "do" : "check");
					printf($msg);
					Logger::notice($msg);
					$aVa['tally'][$pos] = $newItemId;
				}
				else 
				{
					$msg = sprintf("serverId:%d, uid:%d, hid:%d, old:%d, new:0, type:%s", $serverId, $aUid, $aHid, $oldItemId, $do ? "do" : "check");
					printf($msg);
					Logger::warning($msg);
					continue;
				}
			}
			
			if ($do) 
			{
				$this->updateHero($aHid, $aVa);
			}
		}
	}
	
	public function getUserServerId()
	{
		$arrField = array('uid','server_id');
		$data = new CData();
		$data->select($arrField)->from('t_user');
		$data->orderBy('uid', TRUE);
		$arrRet = $data->query();
		
		$ret = array();
		foreach ($arrRet as $aInfo)
		{
			$ret[$aInfo['uid']] = $aInfo['server_id'];
		}
		
		return $ret;
	}
	
	public function getHeroList()
	{
		$arrField = array('hid','uid','va_hero');
		$arrCond = array
		(
				array('level', '>', '1'),//TODO
				array('delete_time', '=', 0),
		);
		
		$offset = 0;
		$limit = 1000;
		$arrRet = array();
		while (TRUE) 
		{
			$data = new CData();
			$data->select($arrField)->from('t_hero');
			foreach ($arrCond as $aCond)
			{
				$data->where($aCond);
			}
			$data->limit($offset, $limit);
			$data->orderBy('hid', TRUE);
			
			$ret = $data->query();
			$arrRet = array_merge($arrRet, $ret);
			if (count($ret) < $limit) 
			{
				break;
			}
			else 
			{
				$offset += $limit;
			}
		}
		
		$arrRet = Util::arrayIndex($arrRet, 'hid');
		
		return $arrRet;
	}
	
	public function getNewItemId($serverId, $oldItemId)
	{
		$newTableId = $oldItemId % 10;
		$newTable = 't_tmp_item_id_' . $serverId . '_' . $newTableId;
		
		$arrField = array('old_id', 'new_id');
		$arrCond = array
		(
				array('old_id', '=', $oldItemId),
		);
		
		$data = new CData();
		$data->select($arrField);
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		$arrRet = $data->query();
		if (empty($arrRet)) 
		{
			return 0;
		}
		
		return $arrRet[0]['new_id'];
	}
	
	public function updateHero($hid, $va)
	{
		HeroDao::update($hid, array('va_hero' => $va));
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */