<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: KaObj.class.php 240775 2016-05-03 02:23:55Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/weal/KaObj.class.php $
 * @author $Author: GuohaoZheng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-03 02:23:55 +0000 (Tue, 03 May 2016) $
 * @version $Revision: 240775 $
 * @brief 
 *  
 **/
class KaObj 
{
	private $kaInfo;
	private $kaInfoBack;
	private $uid;
	private static $instance = NULL;
	
	/**
	 * 获取唯一实例
	 * @return KaObj
	 */
	public static function getInstance()
	{
		if ( self::$instance == null)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public static function release()
	{
		if (self::$instance != null)
		{
			self::$instance = null;
		}
	}
	
	private function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		$this->kaInfo = RPCContext::getInstance()->getSession( 'ka.info' );
		if ( empty( $this->kaInfo ) )
		{
			$this->kaInfo = KaDao::getKaInfo( $this->uid );
			if ( empty( $this->kaInfo ) )
			{
				$this->kaInfo = $this->initKa( $this->uid );
			}
			RPCContext::getInstance()->setSession( 'ka.info' , $this->kaInfo);
		}
		
		$this->adaptRefresh();
		$this->kaInfoBack = $this->kaInfo;
	}
	
	public function initKa( $uid )
	{
		$updateArr = array(
				'uid' => $uid,
				'refresh_time' => 0,
				'point_today' => 0,
				'point_add' => 0,
		);
		
		KaDao::updateKaInfo($uid, $updateArr);
		
		return $updateArr;
	}
	
	public function adaptRefresh()
	{
		//依赖于初始化的时候时间为0，要想不依赖 把update拿出来也可以
		$checkTime = $this->kaInfo['refresh_time'];
		
		$kaRfrType = EnWeal::getKaRfrType();
		switch ($kaRfrType)
		{
		    case KaDef::KA_RFR_TYPE_DAY:
		        if ( !Util::isSameDay( $checkTime ) )
		        {
		            $this->resetKa();
		        }
		        break;
		    case KaDef::KA_RFR_TYPE_ACT:
		        $actConf = EnActivity::getConfByName(ActivityName::WEAL);
		        $actStartTime = $actConf['start_time'];
		        if ( $checkTime < $actStartTime )
		        {
		            $this->resetKa();
		        }
		        break;
		    default:
		        Logger::fatal("wrong ka refresh type:%d.", $kaRfrType);
		        return ;
		}
	}
	
	public function resetKa()
	{
	    $this->kaInfo['refresh_time'] = Util::getTime();
	    $this->kaInfo['point_today'] = 0;
	    $this->kaInfo['point_add'] = 0;
	    $this->update();
	}
	
	public function update()
	{
		$updateArr = array();
		if ( empty($this->kaInfoBack) )
		{
			$updateArr = $this->kaInfo;
		}
		else
		{
			foreach ( $this->kaInfo as $key => $val )
			{
				if ( $this->kaInfoBack[$key] != $val )
				{
					$updateArr[$key] = $val;
				}
			}
		}
		
		if ( empty( $updateArr ) )
		{
			return;
		}
		
		KaDao::updateKaInfo( $this->uid , $this->kaInfo);//$updateArr);修复更新的时候打不必要的warning日志的问题
		$this->kaInfoBack = $this->kaInfo;
		RPCContext::getInstance()->setSession( 'ka.info' , $this->kaInfo);
	}
	
	public function getKaInfo()
	{
		return $this->kaInfo;
	}
	
	public function subKaPoint( $points )
	{
		$this->kaInfo['point_today'] -= $points;
	}
	
	public function addKaPoint( $points )
	{
		$this->kaInfo['point_today'] += $points;
		$this->kaInfo['point_add'] += $points;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */