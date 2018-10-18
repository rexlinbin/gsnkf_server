<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Gm.class.php 109918 2014-05-21 10:29:45Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/gm/Gm.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2014-05-21 10:29:45 +0000 (Wed, 21 May 2014) $
 * @version $Revision: 109918 $
 * @brief
 *
 **/
class Gm implements IGm
{

	/* (non-PHPdoc)
	 * @see IGm::reportClientError()
	 */
	public function reportClientError($message)
	{

		$filename = LOG_ROOT . '/' . GmConf::AS_LOG_FILE;
		$file = fopen ( $filename, 'a+' );
		if (empty ( $file ))
		{
			Logger::fatal ( "report client error failed, can't open file:%s", $filename );
			return;
		}

		$context = RPCContext::getInstance ();
		$framework = $context->getFramework ();
		$arrMicro = explode ( " ", microtime () );
		$time = date ( 'Ymd H:i:s' );
		$microtime = sprintf ( "%06d", intval ( 1000000 * $arrMicro [0] ) );
		$log = sprintf ( "[%s %s][logid:%s][client:%s][server:%s][uid:%s]%s\n", $time, $microtime,
				$framework->getLogid (), $framework->getClientIp (), $framework->getServerIp (),
				$context->getUid (), $message );
		fputs ( $file, $log );
		fclose ( $file );
	}

	public function silentUser($uid, $time)
	{
		$user = EnUser::getUserObj ( $uid );
		$user->setBanChatTime($time);
		$user->update ();
	}

	public function getTime()
	{

		return RPCContext::getInstance ()->getFramework ()->getRequestTime ();
	}

	public function newResponse($uid)
	{

		return RPCContext::getInstance ()->sendMsg ( array ($uid ), 're.gm.newMsg', 0 );
	}

	/* (non-PHPdoc)
	 * @see IGm::newBroadCast()
	 */
	public function newBroadCast()
	{
		return RPCContext::getInstance ()->sendMsg ( array (0), 're.chat.getAnnounce', array() );
	}

	/* (non-PHPdoc)
	 * @see IGm::newBroadCastTest()
	 */
	public function newBroadCastTest($uid, $bid)
	{
		$uid = intval($uid);
		return RPCContext::getInstance ()->sendMsg ( array ($uid), 're.chat.getAnnounce', array($bid) );
	}

	
	public function sendSysMail($recieverUid, $subject, $content)
	{
		//如果标题或者内容为空,则返回
		if ( empty($subject) || empty($content) )
		{
			return FALSE;
		}

		$return = MailLogic::sendSysMailByPlatform($recieverUid, $subject, $content);
		
		if ( $return )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * @see IGm::reportScriptResult()
	 */
	public function reportScriptResult($msg)
	{
		$uid = RPCContext::getInstance()->getUid();
		
		if( $uid <= 0 )
		{
			throw new FakeException('invalid uid:%d', $uid);
		}
		
		$now = Util::getTime();

		//配置就写在这里了
		$maxNum = 1024; //保留结果最大个数
		$maxTime = 600; //最大保留时间 
		$mcExpiredTime = 86400;
		
		$key = self::getScriptResultKey($uid);
		$earliestTime = $now - $maxTime;
		
		try 
		{
			$arrRet = McClient::get($key);
			if( empty($arrRet) )
			{
				$arrRet = array();
			}
			$arrRet[] = array(
				'msg' => $msg,
				'time' => $now,
			);
			
			$num = count( $arrRet );
			if( $num > $maxNum)
			{
				$arrRet = array_slice($arrRet, $maxNum - $num);
			}
			
			while(true)
			{
				$value = current($arrRet);
				if( !empty($value) && $value['time'] < $earliestTime )
				{
					array_shift($arrRet);
				}
				else
				{
					break;
				}
			}
			
			$ret = McClient::set($key, $arrRet, $mcExpiredTime);
			if( $ret != 'STORED' )
			{
				Logger::fatal('mc set failed. ret:%s', $ret);
			}
			
		}
		catch (Exception $e)
		{
			Logger::fatal('something wrong. %s', $e->getMessage());
		}

		return 'ok';
		
	}
	
	/**
	 * 脚本后台功能相关
	 * 
	 * 后台获取前端脚本执行结果
	 * 
	 * @param int $uid
	 * @param int $startTime  返回$startTime之后的结果（包括$startTime）
	 */
	public function getScriptResult($uid, $startTime)
	{
		//检查用户是否在线
		$proxy = new ServerProxy ();
		$ret = $proxy->checkUser ( $uid, false );
		if( !$ret  )
		{
			$userInfo = UserDao::getUserByUid($uid, array('uname') );
			if( empty($userInfo) )
			{
				return array(
						'ret' => 'fake',
						'arrMsg' => sprintf('not found uid:%d ', $uid),
				);
			}
			return array(
					'ret' => 'fake',
					'arrMsg' => sprintf('uid:%d, uname:%s not online', $uid, $userInfo['uname']),
			);
		}
		
		$key = self::getScriptResultKey($uid);
		
		$arrMsg = array();
		$ret = 'ok';
		try
		{
			$arrRet = McClient::get($key);
			if( empty($arrRet) )
			{
				$arrRet = array();
			}
			
			foreach($arrRet as $value)
			{
				if( $value['time'] >= $startTime )
				{
					$arrMsg[] = $value;
				}
			}
		}
		catch (Exception $e)
		{
			Logger::fatal('something wrong. %s', $e->getMessage());
			$arrMsg = "something wrong: ".$e->getMessage();
		}
		
		return array(
			'ret' => $ret,
			'arrMsg' => $arrMsg,
		);
	}
	
	public static function getScriptResultKey($uid)
	{
		return "script_ret_$uid";
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */