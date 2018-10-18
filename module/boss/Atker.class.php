<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Atker.class.php 162626 2015-03-20 04:20:16Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/Atker.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-03-20 04:20:16 +0000 (Fri, 20 Mar 2015) $
 * @version $Revision: 162626 $
 * @brief 
 *  
 **/
class Atker
{
	private $atker;
	private $atkerBack;
	private $bossId;
	private $uid;
	private static $instance = NULL;
	
	/**
	 * 
	 * @return Atker
	 */
	public static function getInstance( $uid, $bossId, $ref = true )
	{
		//更新的时候注意不刷新时间，为资源追回
		if ( self::$instance == null)
		{
			self::$instance = new self( $uid, $bossId, $ref );
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
	
	private function __construct( $uid, $bossId, $ref )
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if ( $uid != $this->uid )
		{
			throw new FakeException( 'invalid uid: %d', $this->uid );
		}
		$this->bossId = $bossId;
		$atkerInfo = BossDAO::getBossAttack( $this->bossId, $this->uid );
		if ( empty( $atkerInfo ) )
		{
			$uname = EnUser::getUserObj()->getUname();
			$atkerInfo = array(
					BossDef::BOSS_ID				=>	$this->bossId,
					BossDef::ATK_UID				=>	$this->uid,
					BossDef::ATK_UNAME				=>	$uname,
					BossDef::LAST_ATK_TIME 			=>	0,
					BossDef::LAST_INSPIRE_TIME		=>	0,
					BossDef::LAST_INSPIRE_TIME_GOLD =>	0,
					BossDef::ATK_HP					=>	0,
					BossDef::ATK_NUM				=>	0,
					BossDef::INSPIRE				=>	0,
					BossDef::REVIVE					=>	0,
					BossDef::FLAGS					=>	0,
					BossDef::FORMATION_SWITCH		=>	0,
					BossDef::VA_BOSS_ATK			=> array(),
			);
			BossDAO::initBossAtk( $atkerInfo );
		}
		$this->atker = $atkerInfo;
		$this->atkerBack = $atkerInfo;
		if ( $ref )
		{
			$this->adaptRefresh();
		}
		
	}
	
	private function adaptRefresh()
	{
		$bossId = $this->atker[ BossDef::BOSS_ID ];
		$atkStartTime = BossUtil::getBossStartTime($bossId);
		if ( ( $this->atker[BossDef::LAST_ATK_TIME] < $atkStartTime && $this->atker[BossDef::LAST_ATK_TIME] != 0 ) 
		|| ( $this->atker[BossDef::LAST_INSPIRE_TIME] < $atkStartTime && $this->atker[BossDef::LAST_INSPIRE_TIME] != 0 )
		|| ( $this->atker[BossDef::LAST_INSPIRE_TIME_GOLD] < $atkStartTime && $this->atker[BossDef::LAST_INSPIRE_TIME_GOLD] != 0 ) )
		{
			$this->atker[BossDef::LAST_ATK_TIME]		= 0;
			$this->atker[BossDef::LAST_INSPIRE_TIME]	= 0;
			$this->atker[BossDef::LAST_INSPIRE_TIME_GOLD] = 0;
			$this->atker[BossDef::INSPIRE]				= 0;
			$this->atker[BossDef::REVIVE]				= 0;
			$this->atker[BossDef::ATK_HP]				= 0;
			$this->atker[BossDef::ATK_NUM]				= 0;
			$this->atker[BossDef::FLAGS]				= 0;
			
			$this->update();
		}
	}

	public function getAtkerInfo()
	{
		return $this->atker;
	}
	
	public function getAtkHp()
	{
		return $this->atker[ BossDef::ATK_HP ];
	}
	
	public function inspire( $silver = true )
	{
		if ( $silver )
		{
			$this->atker[BossDef::LAST_INSPIRE_TIME] = Util::getTime();
		}
		else 
		{
			$this->atker[BossDef::LAST_INSPIRE_TIME_GOLD] = Util::getTime();
		}
		$this->atker[ BossDef::INSPIRE ]++;
		
	}
	
	public function setSliverInspireTime()
	{
		$this->atker[BossDef::LAST_INSPIRE_TIME] = Util::getTime();
	}
	
	public function setSubCd( $flag )
	{
		$this->atker[ BossDef::FLAGS ] = $flag;
	}
	
	public function addAtkHp( $atkHp )
	{
		$this->atker[ BossDef::ATK_HP ] += $atkHp;
	}
	
	public function addAtkNum( $num )
	{
		$this->atker[ BossDef::ATK_NUM ] += $num;
	}
	
	public function setAtkTime()
	{
		$this->atker[ BossDef::LAST_ATK_TIME ] = Util::getTime();
	}
	
	public function getAtkTime()
	{
		return $this->atker[BossDef::LAST_ATK_TIME];
	}
	
	public function addReviveNum($num)
	{
		$this->atker[ BossDef::REVIVE ] += $num;
	}
	
	public function getReviveNum()
	{
		return $this->atker[ BossDef::REVIVE ];
	}
	
	public function setFormationSwitch( $switch )
	{
		if( $switch != 0 && $switch != 1 )
		{
			throw new FakeException( 'invalid switch' );
		}
		$this->atker[BossDef::FORMATION_SWITCH] = $switch;
	}
	
	public function setBossFormation( $btlFormation )
	{
		$this->atker[ BossDef::VA_BOSS_ATK ]['formation'][$this->bossId] = $btlFormation;
	}
	
	public function update()
	{
		$updateArr = array();
		foreach ( $this->atker as $key => $val )
		{
			if ( !isset( $this->atkerBack[ $key ] ) )
			{
				throw new InterException( 'key: %s is not set in atker, atker is: %s, back is: %s', $key, $this->atker, $this->atkerBack );
			}
			if ( $this->atker[ $key ] != $this->atkerBack[ $key ] )
			{
				$updateArr[ $key ] = $val;
			}
		}
		if ( empty( $updateArr ) )
		{
			return;
			//throw new InterException( 'no changed, update an egg! atker: %s, atkerBack: %s', 
					//$this->atker, $this->atkerBack );
		}
		BossDAO::updateBossAtk( $this->bossId , $this->uid, $updateArr );
		
		$this->atkerBack = $this->atker;
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */