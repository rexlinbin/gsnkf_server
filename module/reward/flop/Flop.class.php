<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Flop.class.php 75047 2013-11-15 06:35:24Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/flop/Flop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-15 06:35:24 +0000 (Fri, 15 Nov 2013) $
 * @version $Revision: 75047 $
 * @brief 
 *  
 **/
class Flop
{
	/**
	 * 用户减银币
	 * 
	 * @param int $uid 用户id
	 * @param int $num 掠夺银币数量
	 * @throws FakeException
	 */
	public function robUserByOther($uid, $num)
	{
		Logger::trace('Flop::robUserByOther Start.');
		
		if ($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}

		// 如果用户不在线，就设置一下session，伪装自己在当前用户的连接中
		$guid = RPCContext::getInstance()->getSession('global.uid');
		if ($guid == null)
		{
			RPCContext::getInstance()->setSession('global.uid', $uid);
		}
		else if ($uid != $guid)
		{
			Logger::fatal('robUserByOther error, uid:%d, guid:%d', $uid, $guid);
			return;
		}
		
		//减银币，不够就减到0
		$user = EnUser::getUserObj($uid);
		$silver = $user->getSilver();
		if ($num > $silver) 
		{
			$num = $silver;
		}
		$user->subSilver($num);
		$user->update();
		Logger::trace('real rob lose:%d', $num);
		//推送消息给前端
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::USER_UPDATE_USER_INFO, array('silver_num' => $user->getSilver()));
		
		Logger::trace('Flop::robUserByOther End.');
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */