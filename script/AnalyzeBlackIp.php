<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AnalyzeBlackIp.php 257347 2016-08-19 11:36:47Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/AnalyzeBlackIp.php $
 * @author $Author: wuqilin $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-19 11:36:47 +0000 (Fri, 19 Aug 2016) $
 * @version $Revision: 257347 $
 * @brief 
 *  
 **/
 
class AnalyzeBlackIp extends BaseScript
{
	public function help()
	{
		printf("param:\n");
		printf("reqLimit:			每个ip每个小时的请求数上限，默认2000\n");
		printf("uidLimit:			每个ip每个小时登陆的uid数上限，默认20\n");
		printf("openDayLimit:		服的开服天数天数，默认3天\n");
		printf("hour:				分析的日志，默认1个小时前\n");
		printf("blackTime:			ip加黑的有效市场，默认12小时\n");
		printf("doFlag:				是否将ip加入db，默认否\n");
	}
	
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		// 命令行格式
		global $argc, $argv;
		$arrLongOp = array
		(
				'reqLimit::',
				'uidLimit::',
				'openDayLimit::',
				'hour::',
				'blackTime::',
				'strict::',
				'doFlag::',
		);
		$arrRet = getopt('f:c:s:g:d:h:', $arrLongOp);

		// 参数的默认值
		$reqLimit = 2000;
		$uidLimit = 20;
		$openDayLimit = 3;
		$hour = 1;
		$blackTime = 12 * 3600;
		$do = 0;
		$strict = 0;
		
		// 获得命令行参数
		foreach ($arrRet as $key => $value)
		{
			switch ($key)
			{
				case 'reqLimit':
					$reqLimit = intval($value);
					printf("set reqLimit:%d\n", $reqLimit);
					break;
				case 'uidLimit':
					$uidLimit = intval($value);
					printf("set uidLimit:%d\n", $uidLimit);
					break;
				case 'openDayLimit':
					$openDayLimit = intval($value);
					printf("set openDayLimit:%d\n", $openDayLimit);
					break;
				case 'hour':
					$hour = intval($value);
					printf("set hour:%d\n", $hour);
					break;
				case 'blackTime':
					$blackTime = intval($value);
					printf("set blackTime:%d\n", $blackTime);
					break;
				case 'strict':
					$strict = intval($value);
					printf("set strict:%d\n", $strict);
					break;
				case 'doFlag':
					$do = intval($value);
					printf("set doFlag:%d\n", $do);
					break;
				default:
					break;
			}
		}
		
		$curTime = Util::getTime();
		
		$logFile = $this->group . ".lcserver.log";
		if( !empty($hour) )
		{
		    $logDate = strftime('%Y%m%d%H', $curTime - $hour * 3600);
		    $logFile = $this->group . ".lcserver.log." . $logDate;
		}
		
		$arrRet = array();
		$cmd = sprintf("/bin/find /home/pirate/lcserver/log -name '%s'", $logFile);
		exec($cmd, $arrRet);
		if (empty($arrRet)) 
		{
			printf("no log file:%s\n", $logFile);
			return ;
		}
		$logFile = $arrRet[0];
		
		$msg = sprintf('param, reqLimit:%d, uidLimit:%d, openDayLimit:%d, hour:%d, blackTime:%d, strict:%d, doFlag:%d, logFile:%s', $reqLimit, $uidLimit, $openDayLimit, $hour, $blackTime, $strict, $do, $logFile);
		$this->mylog($msg);
				
		$daysBetween = Util::getDaysBetween(strtotime(GameConf::SERVER_OPEN_YMD)) + 1;
		if ($daysBetween > $openDayLimit) 
		{
			$msg = sprintf('not in new servers, open day:%d, open day limit:%d', $daysBetween, $openDayLimit);
			$this->mylog($msg);
			return ;
		}
		
		$arrReqRet = array();
		$cmd = sprintf("/bin/grep BACKEND_INFO %s|awk  '{ if(match($0,/INFO\]\[([0-9.]+)/,arr)) {print arr[1]} }'| sort |uniq -c | sort -n -k1 | awk '{if($1>=%d)print $1,$2}'", $logFile, $reqLimit);
		exec($cmd, $arrReqRet);
		$arrReqRet = $this->array2map($arrReqRet);
		
		if (!$strict) 
		{
			$this->addBlackIp($arrReqRet, EnIpBlocker::RULE_LOGIN_REQ_NUM, $blackTime, $do);
		}
		
		$arrUidRet = array();
		$cmd = sprintf("/bin/grep BACKEND_INFO %s|awk  '{ if(match($0,/INFO\]\[([0-9.]+).*uid:([0-9]+)/,arr)) {print arr[1],arr[2]} }'|sort |uniq | awk '{print $1}' | uniq -c | sort -n -k1 | awk '{if($1>=%d)print $1,$2}'", $logFile, $uidLimit);
		exec($cmd, $arrUidRet);
		$arrUidRet = $this->array2map($arrUidRet);
		
		if (!$strict)
		{
			$this->addBlackIp($arrUidRet, EnIpBlocker::RULE_LOGIN_UID_NUM, $blackTime, $do);
		}
		
		if ($strict) 
		{
			$arrReqKey = array_keys($arrReqRet);
			$arrUidKey = array_keys($arrUidRet);
			$arrStrictKey = array_intersect($arrReqKey, $arrUidKey);
			$arrStrictInfo = array();
			foreach ($arrStrictKey as $ip)
			{
				$arrStrictInfo[$ip] = array($arrReqRet[$ip], $arrUidRet[$ip]);
			}
			$this->addBlackIp($arrStrictInfo, EnIpBlocker::RULE_LOGIN_REQ_AND_UID_NUM, $blackTime, $do);
		}
		
		$this->mylog("done");
	}
	
	public function array2map($arrBlackIpInfo)
	{
		$ret = array();
		
		foreach ($arrBlackIpInfo as $aInfo)
		{
			$arrTmp = Util::str2Array($aInfo, ' ');
			$num = intval($arrTmp[0]);
			$ipstr = $arrTmp[1];
			$ret[$ipstr] = $num;
		}
		
		return $ret;
	}
	
	public function addBlackIp($arrBlackIpInfo, $type, $blackTime, $do)
	{
		foreach ($arrBlackIpInfo as $ipstr => $aInfo)
		{
			$reqNum = 
			$msg = sprintf('add black ip for type:%s, ip:%s, info:%s', $type, $ipstr, is_array($aInfo) ? implode(',', $aInfo) : $aInfo);
			$this->mylog($msg);
			if ($do)
			{
				$ip = ip2long($ipstr);
				$weight = EnIpBlocker::BLACK_IP_FOREVER_WEIGHT / 2;
				EnIpBlocker::addBlackIp($ip, $weight, Util::getServerId(), $type, Util::getTime() + $blackTime);
			}
		}
	}
	
	public function mylog($msg, $warn = false)
	{
		printf("%s\n", $msg);
		$warn ? Logger::warning($msg) : Logger::info($msg);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */