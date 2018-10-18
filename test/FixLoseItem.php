<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixLoseItem.php 243108 2016-05-17 06:07:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/FixLoseItem.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-17 06:07:36 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243108 $
 * @brief 
 *  
 **/
 
class FixLoseItem extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$fix = false;
		if( $arrOption[0] == 'do'  )
		{
			$fix = true;
		}
		$uid = intval( $arrOption[1] );
		$hid = intval( $arrOption[2] );
		array_shift($arrOption);
		array_shift($arrOption);
		array_shift($arrOption);
		$arrItemId = $arrOption;

		$ret = UserDao::getUserByUid($uid, array('pid', 'uname','last_login_time') );
		if( empty($ret) )
		{
			Logger::warning("not found uid:%d", $uid);
			return;
		}

		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);

		$ret = self::getByHid($hid, array('uid', 'htid'));
		if( empty($ret) )
		{
			Logger::warning("not found hid:%d", $hid);
			return;
		}
		if( $uid != $ret['uid'] )
		{
			Logger::warning('uid not match. uid:%d, hid:%d, uidofHid:%d', $uid, $hid, $ret['uid']);
			return;
		}
		$htid = $ret['htid'];

		ItemManager::getInstance()->getItems($arrItemId);
		foreach( $arrItemId as $key => $itemId )
		{
			$itemId = intval($itemId);
			$arrItemId[$key] = $itemId;
			$itemObj = ItemManager::getInstance()->getItem($itemId);
			if( $itemObj == NULL )
			{
				Logger::warning('not found itemId:%d, uid:%d, hid:%d', $itemId, $uid, $hid);
				return;
			}
			if( EnUser::isCurUserOwnItem($itemId) )
			{
				Logger::warning('itemId:%d in uid:%d, hid:%d', $itemId, $uid, $hid);
				return;
			}
		}

		Logger::info('need fix uid:%d, hid:%d, htid:%d, arrItemId:%s', $uid, $hid, $htid, implode(' ', $arrItemId));

		$bagObj = BagManager::getInstance()->getBag($uid);
		$ret = $bagObj->addItems($arrItemId, true);
		if(!$ret)
		{
			Logger::warning('addItem failed. uid:%d', $uid);
			return;
		}

		if($fix)
		{
			Util::kickOffUser($uid);
			$bagObj->update();
			Logger::info('do fix uid:%d, hid:%d, htid:%d, arrItemId:%s', $uid, $hid, $htid, implode(' ', $arrItemId));
		}

		printf("done\n");
	}


	public static function getByHid($hid, $arrField)
	{
		$where = array('hid', '=', $hid);
		$data = new CData();
		$arrRet = $data->select($arrField)->from('t_hero')
		->where($where)->query();
		if (!empty($arrRet))
		{
			return $arrRet[0];
		}
		return null	;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */