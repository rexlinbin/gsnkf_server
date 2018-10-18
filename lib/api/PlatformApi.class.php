<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PlatformApi.class.php 175402 2015-05-28 08:50:11Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/api/PlatformApi.class.php $
 *
 * @author $Author: BaoguoMeng $(dh0000@babeltime.com)
 *         @date $Date: 2015-05-28 08:50:11 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175402 $
 *          @brief
 *         
 *         
 */
class PlatformApi {
	private $md5Key;
	private $server_addr;
	public function __construct() {
		$this->md5Key = PlatformApiConfig::MD5KEY;
		$this->server_addr = PlatformApiConfig::$SERVER_ADDR;
	}
	public function users($method, $array = array()) {
		$params = $array;
		$params ['action'] = $method;
		$params ['ts'] = time ();
		$params ['project'] = PlatformApiConfig::PROJECT_ID;
		switch ($method) {
			case 'addRole' :
			case 'roleLvUp':
			case 'getGiftByCard' :
			case 'getActivityData' :
			case 'getTeamAll':
			case 'getServerByTeamId':
			case 'getTeamByServerId':
			case 'getNameAll':
			case 'sendmsg':
			case 'addTrustDevice':
			case 'getTeamAllNear':
			case 'getServerByTeamIdNear':
			case 'getTeamByServerIdNear':
				break;
			default :
				throw new InterException ( 'invalid platform method:%s', $method );
		}
		return $this->post_request ( $method, $params );
	}
	protected function post_request($method, $params, $addr = 'user') {
		ksort ( $params );
		$tmp = '';
		foreach ( $params as $key => &$val ) {
			if (in_array ( $key, array (
					'pid',
					'action',
					'ts' 
			) )) {
				$tmp .= $key . $val;
			}
		}
		$params ['sig'] = md5 ( $tmp . $this->md5Key );
		$params ['logid'] = RPCContext::getInstance ()->getFramework ()->getLogid ();
		
		if (is_array ( $this->server_addr [$method] )) {
			$loopTimes = count ( $this->server_addr [$method] );
			if (! isset ( $this->server_addr [$method] [0] )) {
				Logger::fatal ( 'server_addr notset. %s error.', $method );
				throw new Exception ( 'error' );
			}
			$maxConfNum = $loopTimes - 1;
			$targetURLKey = rand ( 0, $maxConfNum );
			$usedURLKey = array (
					$targetURLKey 
			);
			$targetURL = $this->server_addr [$method] [$targetURLKey];
		} elseif (is_string ( $this->server_addr [$method] )) {
			$targetURL = $this->server_addr [$method];
			$loopTimes = 1;
		} else {
			Logger::fatal ( 'server_addr type. %s error.', $method );
			throw new Exception ( 'server_addr type error' );
		}
		
		Logger::debug ( "%s:%s params:%s", $method, $this->server_addr [$method], $params );
		
		$proxy = new HTTPClient ( $targetURL );
		switch ($method) {
			case 'addRole' :
			case 'roleLvUp' :
			case 'getGiftByCard' :
			case 'getActivityData' :
			case 'getTeamAll':
			case 'getServerByTeamId':
			case 'getTeamByServerId':
			case 'getNameAll':
			case 'sendmsg':
			case 'addTrustDevice':
			case 'getTeamAllNear':
			case 'getServerByTeamIdNear':
			case 'getTeamByServerIdNear':
				break;
			default :
				throw new InterException ( 'invalid platform method:%s', $method );
		}
		$postData = http_build_query ( $params );
		if ($loopTimes == 1) {
			$res = $proxy->post ( $postData );
		} else {
			for($i = 0; $i < $loopTimes * 5; $i ++) {
				try {
					$res = $proxy->post ( $postData );
					break;
				} catch ( Exception $e ) {
					if ($loopTimes == count ( $usedURLKey )) {
						break;
						Logger::fatal ( 'PlatformApi %s all url error. ', $method );
						throw new Exception ( 'all url error' );
					}
					$newURLKey = rand ( 0, $maxConfNum );
					if (! in_array ( $newURLKey, $usedURLKey ) && isset ( $this->server_addr [$method] [$newURLKey] )) {
						$proxy->resetTargetURL ( $this->server_addr [$method] [$newURLKey] );
						$usedURLKey [] = $newURLKey;
					}
				}
			}
		}
		if (! isset ( $res )) {
			Logger::fatal ( 'no res. %s error.', $method );
			throw new Exception ( 'no res error' );
		}
		switch ($method) {
			case 'addRole' :
			case 'roleLvUp':
			case 'sendmsg':
			case 'addTrustDevice':
				break;
			
			case 'getGiftByCard' :
			case 'getActivityData' :
			case 'getTeamAll':
			case 'getServerByTeamId':
			case 'getTeamByServerId':
			case 'getNameAll':
			case 'getTeamAllNear':
			case 'getServerByTeamIdNear':
			case 'getTeamByServerIdNear':
				$res = unserialize ( $res );
				break;
		}
		return $res;
	}
}
