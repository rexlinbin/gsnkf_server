<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: KeeperObj.class.php 131388 2014-09-10 11:17:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/KeeperObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-09-10 11:17:30 +0000 (Wed, 10 Sep 2014) $
 * @version $Revision: 131388 $
 * @brief 
 *  
 **/
class KeeperObj
{
	private $keeper = null;
	private $keeperBack = null;
	private $uid;
	private static $m_instance = NULL;
	
	/**
	 * 
	 * @param int $uid
	 * @return  KeeperObj
	 */
	public static function getInstance( $uid )
	{
		if ( !isset( self::$m_instance[$uid] ))
		{
			self::$m_instance[$uid] = new self( $uid );
		}
		return self::$m_instance[$uid];
	}
	
	public static function release()
	{
		//全部release掉
		if (self::$m_instance != null)
		{
			self::$m_instance = null;
		}
	}
	
	public function __construct( $uid )
	{
		$guid = RPCContext::getInstance()->getUid();
		if ( $uid == 0 || $uid == $guid )
		{
			$this->uid = $guid;
			$this->keeper = RPCContext::getInstance()->getSession( KeeperDef::KEEPER_SESSION );
			if ( empty( $this->keeper ) )
			{
				$this->keeper = PetDAO::selectKeeper( $this->uid );
				if ( empty( $this->keeper ) )
				{
					$this->keeper = self::initKeeper();
				}
				RPCContext::getInstance()->setSession( KeeperDef::KEEPER_SESSION , $this->keeper);
			}
		}
		else
		{
			$this->uid = $uid;
			$this->keeper = PetDAO::selectKeeper( $this->uid );
			if ( empty( $this->keeper ) )
				{
					$this->keeper = self::initKeeper();
				}
		}
		
		$this->keeperBack = $this->keeper;
		//检查一下上阵栏位的自动开启
		$this->adaptSquandSlot();
	}
	
	public function initKeeper()
	{
		$initKeeperSlot = btstore_get()->PET_COST[1]['initKeeperSlot'];
		$INITARR = array(
				'uid' => $this->uid,
				PetDef::KEEPERSLOT => $initKeeperSlot,
				PetDef::PET_FIGHTFORCE => 0,
				PetDef::VAKEEPER => array(
						'setpet' => array(),
		),
		);
		PetDAO::addKeeper( $INITARR );
		return $INITARR;
	}
	
	public function adaptSquandSlot()
	{
		$user = EnUser::getUserObj( $this->uid );
		$level = $user->getLevel();
		$squandSlotArr = btstore_get()->PET_COST[1]['squandSlotOpenArr'];
		$userVip = EnUser::getUserObj($this->uid)->getVip();
		$vipConf = btstore_get()->VIP[$userVip];
		
		foreach( $squandSlotArr as $pos => $openConditionArr )
		{
			if ( $level >= $openConditionArr[0] 
			&& !isset( $this->keeper[PetDef::VAKEEPER]['setpet'][$pos] )
			&& count( $this->keeper[PetDef::VAKEEPER]['setpet'] ) < $vipConf['maxPetFence'])
			{
				//当这个栏位的等级已经满足而且还没有开启而且没有超过vip的最高可开启上限，就开启
				$this->keeper[PetDef::VAKEEPER]['setpet'][$pos] = array( 'petid' => 0, 'status' =>
				0,'producttime' => 0 );
			}
			elseif ( isset( $this->keeper[PetDef::VAKEEPER]['setpet'][$pos])  )
			{
				//如果这个栏位玩家已经开了，看序列的下一个
				continue;
			}
			else
			{
				//如果这个没有开并且等级也不满足，跳出来（根据策划要求，栏位肯定是顺序开启的），以下就不用检查了
				break;
			}
		}
	}
	

	public function getKeeperInfo()
	{
		return $this->keeper;
	}
	
	public function getKeeperSlot()
	{
		return 	$this->keeper[PetDef::KEEPERSLOT];
	}
	
	public function getVaKeeper()
	{
		return $this->keeper[PetDef::VAKEEPER];
	}
	
	public function getFightPet()
	{
		Logger::debug('getFightPet are: %s ',$this->keeper[PetDef::VAKEEPER] );
		foreach ( $this->keeper[PetDef::VAKEEPER]['setpet'] as $key => $info )
		{
			if ( !empty( $info['petid'] ) && $info['status'] ==1 )
			{
				return $info['petid'];
			}
		}
		return array();
	}
	
	public function openSquandSlot()
	{
		$this->keeper[PetDef::VAKEEPER]['setpet'][]
		= array('petid' => 0, 'status' => 0, 'producttime' => 0);
	}
	
	public function squandUpPet( $petid, $pos )
	{
		$this->keeper[PetDef::VAKEEPER]['setpet'][$pos] 
		= array('petid' => $petid,'status'=>0,'producttime' =>Util::getTime());
	}
	
	public function squandDownPet($pos)
	{
		$this->keeper[PetDef::VAKEEPER]['setpet'][$pos] 
		= array('petid' => 0,'status'=>0,'producttime' =>0);
	}
	
	public function fightUpPet($petid)
	{
		foreach ( $this->keeper[PetDef::VAKEEPER]['setpet'] as $pos => $squandInfo )
		{
			Logger::debug('setpet: %s',$this->keeper[PetDef::VAKEEPER]['setpet'] );
			if ( $squandInfo['petid'] == $petid ) 
			{
				Logger::debug('petid: %d, squandPetId: %d',$petid,$squandInfo['petid'] );
				$this->keeper[PetDef::VAKEEPER]['setpet'][$pos]['status'] = 1;
			}
			else 
			{
				Logger::debug('petid: %d, squandPetId: %d',$petid,$squandInfo['petid'] );
				$this->keeper[PetDef::VAKEEPER]['setpet'][$pos]['status'] = 0;
			}
		}
	}
	
	public function setProductTime( $pos,$time )
	{
		$this->keeper[PetDef::VAKEEPER]['setpet'][$pos]['producttime'] = $time;
	}
	
	public function openKeeperSlot($keeperSlotNnum)
	{
		$this->keeper[PetDef::KEEPERSLOT]+= $keeperSlotNnum;
	}
	
	public function setPetFightforce( $petid, $fightForce )
	{
		$this->keeper[PetDef::PET_FIGHTFORCE] = $fightForce;
	}
	
	public function setTrainTime($pos,$newTime)
	{
		//$this->keeper[petdef::VAKEEPER]['setpet'][$pos]['traintime'] = $newTime;
	}
	public function update()
	{
		if ( $this->keeper == $this->keeperBack )
		{
			return 'nothing change';
		}
		
		$updateField = array();
		foreach ( $this->keeper as $key => $val )
		{
			if ( $this->keeperBack[ $key ] != $val )
			{
				$updateField[ $key ] = $val;
			}
		}
		if ( empty( $updateField ) )
		{
			throw new InterException( 'updateField should not be empty!' );
		}
		
		PetDAO::updateKeeper( $this->uid, $updateField );
		
		$guid = RPCContext::getInstance()->getUid();
		if ( $guid == $this->uid )
		{
			RPCContext::getInstance()->setSession( KeeperDef::KEEPER_SESSION, $this->keeper );
		}
		
		$this->keeperBack = $this->keeper;
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
