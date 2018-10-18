<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Fragseize.class.php 258614 2016-08-26 09:04:39Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/fragseize/Fragseize.class.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-26 09:04:39 +0000 (Fri, 26 Aug 2016) $
 * @version $Revision: 258614 $
 * @brief 
 *  
 **/
class Fragseize implements IFragseize
{
	private $uid;
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if ( empty( $this->uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $this->uid );
		}
		if (EnSwitch::isSwitchOpen(SwitchDef::ROBTREASURE) == false)
		{
			throw new FakeException('user:%d not open fragseize', $this->uid);
		}
	}

	public function getSeizerInfo()
	{
		Logger::trace('begin getSeizerInfo');
		$ret = FragseizeLogic::getSeizerInfo( $this->uid );
		$rettmp = array();
		if ( !empty( $ret ) )
		{
			foreach ( $ret as $id => $num )
			{
				if ( $num > 0 )
				{
					$rettmp[] = array( FragseizeDef::FRAG_ID => $id, FragseizeDef::FRAG_NUM => $num );
				}
			}
		}
		$whiteEndTime = FragseizeLogic::getWhiteFlagEndTime( $this->uid );
		$return = array(
				'frag' => $rettmp,
				'white_end_time' => $whiteEndTime,
		);
		Logger::trace('end getSeizerInfo');
		return $return;
	}
	
	public function getRecRicher( $fragId, $num )
	{
		Logger::trace('begin getRecRicher');
		if ( empty( $fragId) || $num < 1 || $num > FragseizeConf::MAX_REQUEST_MATE )
		{
			throw new FakeException( 'invalid args' );
		}
		FragseizeLogic::checkFragId($fragId);
		$ret = FragseizeLogic::getRecRicher( $this->uid , $fragId, $num);
		Logger::trace('end getRecRicher');
		return $ret;
	}

	public function seizeRicher( $seizedUid, $fragId, $isNPC = 0 )
	{
		Logger::trace('begin seizeRicher');

		if ( ($isNPC != 1 && $isNPC != 0) || empty( $seizedUid ) || empty( $fragId ) )
		{
			throw new FakeException( 'invalid args' );
		}
		FragseizeLogic::checkFragId($fragId);
		$ret = FragseizeLogic::seize( $this->uid, $seizedUid, $fragId, $isNPC );
		if ( is_array($ret) )//一键夺宝添加
		{
			if( isset( $ret['card'][0] )  )
			{
				$ret['card'] = $ret['card'][0];
			}
			EnActive::addTask( ActiveDef::FRAGSEIZE );
			EnWeal::addKaPoints( KaDef::FRAGSEIZE );
			EnAchieve::updateSeize($this->uid, 1);
			EnMission::doMission($this->uid, MissionType::FRAGSIZE,1);
			EnDesact::doDesact($this->uid, DesactDef::FRAGSEIZE, 1);
			
			// 夺宝次数统计 - 夺宝没有记录总次数，次数自己累加
			EnFestivalAct::notify($this->uid, FestivalActDef::TASK_FRAG_NUM, 1);
			//进行夺宝num次
			EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_FRAGSEIZE, 1);
		}
		Logger::trace('end seizeRicher');
		return $ret;
	}

	public function fuse( $treasureId, $num = 1 )
	{
		Logger::trace('begin fuse');
		if ( empty( $treasureId ) )
		{
			throw new FakeException( 'invalid args' );
		}
		FragseizeLogic::checkTreasureId($treasureId);
		
		$succ = FragseizeLogic::fuse( $this->uid , $treasureId, $num);
		Logger::trace('end fuse');
		return $succ;
	}
	
	public function whiteFlag( $type )
	{
		Logger::trace('begin whiteFlag');
		if ( $type != FragseizeDef::WHITE_BYGOLD && $type != FragseizeDef::WHITE_BYITEM )
		{
			throw new FakeException( 'no such type: %d', $type );
		}
		
		FragseizeLogic::whiteFlag( $this->uid, $type );
		
		Logger::trace('end whiteFlag');
	}
	
	public function quickSeize($uid,$fragId,$seizeTimes)
	{
		if ( $uid <= 0 || $fragId <= 0 )
		{
			throw new FakeException( 'args err: %d %d %d',$uid,$fragId, $seizeTimes);
		}
		if( $seizeTimes > 10 )
		{
			$seizeTimes = 10;
			//throw new FakeException( 'can not beyond 10 times : %d', $seizeTimes );
		}
		$ret = FragseizeLogic::quickSeize($this->uid,$uid, $fragId,$seizeTimes);
		
		if( $ret != 'fail' )
		{
			$doNum = $ret['donum'];
			EnActive::addTask( ActiveDef::FRAGSEIZE,$doNum );
			EnWeal::addKaPoints( KaDef::FRAGSEIZE,$doNum );
			EnAchieve::updateSeize($this->uid, $doNum);
			EnMission::doMission($this->uid,MissionType::FRAGSIZE, $doNum);
			EnDesact::doDesact($this->uid, DesactDef::FRAGSEIZE, $doNum);
			
			// 夺宝次数统计 - 夺宝没有记录总次数，次数自己累加
			EnFestivalAct::notify($this->uid, FestivalActDef::TASK_FRAG_NUM, $seizeTimes);
			//进行夺宝num次
			EnWelcomeback::updateTask(WelcomebackDef::TASK_TYPE_FRAGSEIZE, $seizeTimes);
		}

		
		return $ret;
	}

	public function oneKeySeize($treasureId, $ifUse = 0)
	{
		if($treasureId <=0 || in_array($ifUse, array(1, 0)) == false)
		{
			throw new FakeException("invalid param:[%s] for function:[%s]", func_get_args(),  __FUNCTION__);
		}

        if(EnUser::getUserObj($this->uid)->getLevel()
            < btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_ONE_KEY_SEIZE][1])
        {
            throw new FakeException("level too lower, not open");
        }

		return FragseizeLogic::oneKeySeize($this->uid, $treasureId, $ifUse);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */