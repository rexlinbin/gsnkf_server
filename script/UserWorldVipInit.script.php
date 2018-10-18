<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserWorldVipInit.script.php 196656 2015-09-06 09:37:32Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/UserWorldVipInit.script.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-06 09:37:32 +0000 (Sun, 06 Sep 2015) $
 * @version $Revision: 196656 $
 * @brief 
 *  
 **/
class UserWorldVipInit extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$special = array();
		
		if( !isset( $arrOption[0] ) )
		{
			echo "err, para: check|do ";
			return;
		}
		
		$choice = $arrOption[0];
		
		if(!defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$serverId = Util::getServerIdOfConnection();
		}
		
		$arrFieldUser = array('pid','uid','base_goldnum');
		$arrFieldOrder = array('distinct uid as uid');
		if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			$arrFieldUser[] = 'server_id';
		}
		
		$finishNum = 0;
		$bbpayitemfinishnum = 0;
		$errNum = 0;

		$offset = 0;
		$table = User4BBpayDao::tblBBpay;
		while ( true )
		{
			if( !empty( $special ) )
			{
				$uidArr = $special;
			}
			else
			{
				$partUsersOrder = self::getGoldOrderInfoArr($table, $offset, DataDef::MAX_FETCH, $arrFieldOrder);
				if( empty( $partUsersOrder ) )
				{
					if( $table == User4BBpayDao::tblBBpayItem)
					{
						break;
					}
					else 
					{
						$table = User4BBpayDao::tblBBpayItem;
						$offset = 0;
						Logger::info('now change table to t_bbpay_item 1');
						continue;
					}
				}
				$uidArr = Util::arrayExtract($partUsersOrder, 'uid');
			}
		
			$partUsersInfo = EnUser::getArrUser($uidArr, $arrFieldUser, true);
			foreach ( $uidArr as $index => $oneuid )
			{
				if( !isset($partUsersInfo[$oneuid]['base_goldnum']) )
				{
					Logger::fatal('err! have gold order, but no user info, uid: %s',$oneuid );
					continue;
				}
				$sumGold = User4BBpayDao::getSumGoldByUid($oneuid);
				//$sumGold += $partUsersInfo[$oneuid]['base_goldnum'];//安全一点
				$onePid = $partUsersInfo[$oneuid]['pid'];
				if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
				{
					if( !isset( $partUsersInfo[$oneuid]['server_id'] ) )
					{
						Logger::fatal('err! have gold order, but no server_id info, uid: %s',$oneuid );
						continue;
					}
					$serverId = $partUsersInfo[$oneuid]['server_id'];
				}
				$vals = array(
						'pid' => $onePid,
						'server_id' => $serverId,
						'base_goldnum' => $sumGold,
				);
					
				$tryNum = 0;
				while ( $tryNum < 3 )
				{
					try
					{
						if( $choice == 'do' )
						{
							UserWorldDao::updateUserWorld( $vals, array());
						}
						Logger::info( 'update cross user success, pid: %s, serverId: %s, base_goldnum: %s, uid: %s', $onePid, $serverId, $sumGold, $oneuid );
						$finishNum ++;
						break;
					}
					catch ( Exception $e )
					{
						Logger::fatal( 'update cross user fail once, pid: %s, serverId: %s, base_goldnum: %s in num: %s, uid: %s', $onePid, $serverId, $sumGold, $tryNum, $oneuid );
						$tryNum ++;
						if( $tryNum == 3 )
						{
							Logger::fatal( 'update cross user finally fail!!!, pid: %s, serverId: %s, base_goldnum: %s, uid: %s ', $onePid, $serverId, $sumGold, $oneuid);
							$errNum ++;
							break;
						}
					}
				}
			}
		
			if( !empty( $special ) )
			{
				break;
			}
			else
			{
				if( count( $uidArr ) < DataDef::MAX_FETCH )
				{
					if( $table == User4BBpayDao::tblBBpayItem )
					{
						break;
					}
					else
					{
						$table = User4BBpayDao::tblBBpayItem;
						$offset = 0;
						Logger::info('now change table to t_bbpay_item 2');
						continue;
					}
				}
				else
				{
					$offset += DataDef::MAX_FETCH;
				}
			}
		
		}

		
		
		Logger::info('server done, finish num( bbpayitem + bbpaygold ): %s, err num: %s', $finishNum, $errNum);
		

		echo "done \n";
	}
	
	function getGoldOrderInfoArr( $table, $offset, $limit, $arrFields )
	{
		$data = new CData();
		$ret = $data->select( $arrFields )
		->from( $table )
		->where( array('uid','>',0) )
		->orderBy( 'uid' , true )
		->limit($offset, $limit)
		->query();
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */