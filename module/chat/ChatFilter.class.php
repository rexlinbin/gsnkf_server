<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChatFilter.class.php 227570 2016-02-16 01:51:32Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/ChatFilter.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2016-02-16 01:51:32 +0000 (Tue, 16 Feb 2016) $
 * @version $Revision: 227570 $
 * @brief
 *
 **/

/*
  过滤部分频繁刷广告的聊天信息
  内存数据
  
  {
  		lastTime:上一次消息时间
  		lastTrigTime:上一次出发超标时间
  		trigNum:触发相似超标的次数
  		arrMsg:
  		[
  			{
  				t:时间戳
  				m:消息原文
  			}
  		]
  }
  
*/
class ChatFilter
{
	const DATE_SAVE_TIME = SECONDS_OF_DAY;
	
	protected $MAX_MSG_TIME = 3600; //消息保留时间（秒）
	protected $MAX_MSG_NUM = 60;  //最多保留多少条消息的时间信息 debug模式为：6
	protected $MAX_WHOLE_MSG_NUM = 5; //最多保留多少条消息的内容
	protected $THRESH_SIMILAR = 80;   //相似度门限
	protected $MAX_BAN_TIME = 7200;  //重置超门限次数的时间(秒) debug模式为 30
	protected $BASE_BAN_TIME = 60;	//基础禁言时间， BASE_BAN_TIME . 2^trigNum
	
	protected $ip;
	protected $info;
	protected $bakInfo;
	
	private static $arrInstance = array();
	
	
	public static function getInstance( $ip = '')
	{
		if( empty($ip) )
		{
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
		}
	
		if( empty(self::$arrInstance[$ip]) )
		{
			self::$arrInstance[$ip] = new ChatFilter($ip);
		}
		return self::$arrInstance[$ip];
	}
	
	public function __construct ($ip)
	{
		$this->ip = $ip;
		$this->info = self::getIpChatRecord($ip);
		
		if( FrameworkConfig::DEBUG )
		{
			$this->MAX_MSG_TIME = 120;
			$this->MAX_MSG_NUM = 8;
			$this->MAX_BAN_TIME = 180;
		}
		
		if ( empty($this->info) )
		{
			$this->info = array(
				'lastTime' => 0,
				'lastTrigTime' => 0,
				'trigNum' => 0,
				'arrMsg' => array(
				
				),
			);
		}
		if( empty($this->info['lastTrigTime']) )
		{
			$this->info['lastTrigTime'] = $this->info['lastTime'];
		}
		
		$this->bakInfo = $this->info;
		
		$this->refresh();
	}
	
	
	public function refresh()
	{
		/*
		if( count($this->info['arrMsg']) <= $this->MAX_WHOLE_MSG_NUM )
		{
			return;
		}
		*/
		$arrMsg = $this->info['arrMsg'];
		
		if( count($arrMsg) > $this->MAX_MSG_NUM )
		{
			$arrMsg = array_slice($arrMsg, count($arrMsg) - $this->MAX_MSG_NUM);
		}
		
		//只有最后N条留原文
		$simpleNum = count($arrMsg) - $this->MAX_WHOLE_MSG_NUM;
		$num = 0;
		$expireTime = Util::getTime() - $this->MAX_MSG_TIME;
		$timeExpireNum = 0;
		foreach( $arrMsg as $key => $msgInfo )
		{
			if( $num < $simpleNum )
			{
				unset($arrMsg[$key]['m']);
				$num += 1;
				logger::debug('simple msg in %d', $arrMsg[$key]['t']);
			}
			
			if( $arrMsg[$key]['t'] < $expireTime )
			{
				$timeExpireNum += 1;
			}
		}
		if( $timeExpireNum > 0 )
		{
			$arrMsg = array_slice($arrMsg, $timeExpireNum);
		}
		
		logger::debug('allNum:%d, simpleNum:%d, timeExpireNum:%d', count($arrMsg), $simpleNum, $timeExpireNum);

		$this->info['arrMsg'] = $arrMsg;
		
		if( $this->info['trigNum'] > 0 && $this->info['lastTrigTime'] + $this->MAX_BAN_TIME < Util::getTime() )
		{
			$this->info['trigNum'] = 0;
			logger::info('reset trigNum');
		}
	}
	
	public function addMsgRecord($msg)
	{
		$this->info['lastTime'] = Util::getTime();
		$this->info['arrMsg'][] = array(
			't' => Util::getTime(),
			'm' => $msg,
		);
	}
	
	public function filterMsg($msg)
	{
		//不考虑分享战报和聊天
		if( preg_match("/^<fight>[a-zA-Z0-9,]*<fight\/>$/", $msg)
	 		|| preg_match("/^<audio>[a-zA-Z0-9,]*<audio\/>$/", $msg) )
		{
			Logger::info('ignore fight and audio:%s', $msg);
			return false;
		}
		
		$arrMsgStr = array();
		foreach( $this->info['arrMsg'] as $msgInfo )
		{
			if( !empty($msgInfo['m']) )
			{
				$arrMsgStr[] = $msgInfo['m'];
			}
		}
		
		$this->addMsgRecord($msg);
		
		if( count($this->info['arrMsg']) < $this->MAX_MSG_NUM )
		{
			Logger::debug('msgNum:%d ignore', count($this->info['arrMsg']));
			return false;
		}
	
		$maxSimilar = self::getMaxSimilar($arrMsgStr, $msg);
		
		if( $maxSimilar >= $this->THRESH_SIMILAR )
		{
			$this->info['trigNum'] += 1;
			$this->info['lastTrigTime'] = Util::getTime();
			logger::debug('too similar. maxSimilar:%d, msg:%s, info:%s', $maxSimilar, $msg, implode(",", $arrMsgStr));	
		}
		
		if( $this->info['trigNum'] > 0 )
		{
			$canChatTime = $this->info['lastTrigTime'] + $this->getBanTime($this->info['trigNum']-1);
			if( Util::getTime() < $canChatTime )
			{
				logger::warning('cant chat now. trigNum:%d, lastTime:%d, lastTrigTime:%d, canChatTime:%d',
					$this->info['trigNum'], $this->info['lastTime'], $this->info['lastTrigTime'], $canChatTime);
				return true;
			}
		}
		
		return false;
	}
	

	public function update()
	{
		if( $this->info == $this->bakInfo )
		{
			return;
		}
		$key = self::getMemKey($this->ip);
		
		McClient::set($key, $this->info, self::DATE_SAVE_TIME);
		$this->bakInfo = $this->info;
	}
	
	
	
	public function getMaxSimilar($arrStr, $str)
	{
		$maxSimilar = 0;
		foreach( $arrStr as $s )
		{
			$percent = 0;
			similar_text($s, $str, $percent);
			if( $maxSimilar < $percent)
			{
				$maxSimilar = $percent;
			}
		}
		return $maxSimilar;
	}
	
	
	public function getBanTime($trigNum)
	{
		if( $trigNum > 20  )
		{
			$trigNum = 20;
		}
		return ($this->BASE_BAN_TIME) *pow(2, $trigNum);
	}
	
	public static function getMemKey($ip)
	{
		return 'ip_chat_'.ip2long( $ip );
	}
	

	public static function getIpChatRecord($ip)
	{
		$key = self::getMemKey($ip);
		$ret = McClient::get($key);
		return $ret;
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
