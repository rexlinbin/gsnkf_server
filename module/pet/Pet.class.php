<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pet.class.php 248607 2016-06-28 11:38:59Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/Pet.class.php $
 * @author $Author: ShuoLiu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-06-28 11:38:59 +0000 (Tue, 28 Jun 2016) $
 * @version $Revision: 248607 $
 * @brief 
 *  
 **/
class Pet implements IPet
{
	private $uid = 0;

	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		if ( empty( $this->uid ) )
		{
			throw new FakeException( 'uid: %d ,is invalid' );
		}
		if ( !EnSwitch::isSwitchOpen( SwitchDef::PET ) )
		{
			throw new FakeException( 'pet is not open' );
		}
	}
	
	public function getAllPet()
	{
		Logger::trace( 'begin pet.getAllPet ' );
		
		$petsInfo = PetLogic::getAllPet( $this->uid );
		foreach ( $petsInfo as $key => $val )
		{
			if ( isset( $val[ 'uid' ] ) ) 
			{
				unset( $petsInfo[ $key ][ 'uid' ] );
			}
				
		}
		$keeperInfo = PetLogic::getKeeperInfo( $this->uid );
		foreach ( $keeperInfo[PetDef::VAKEEPER]['setpet'] as $pos => $posInfo  )
		{
			if ( !empty( $posInfo['petid'] ) )
			{
				$petidInPos =  $posInfo['petid'];
				$keeperInfo[PetDef::VAKEEPER]['setpet'][$pos]['traintime'] = $petsInfo[$petidInPos][PetDef::TRAINTIME];
			}
			else 
			{
				$keeperInfo[PetDef::VAKEEPER]['setpet'][$pos]['traintime'] = 0;
			}
		}
		
		$ret = array(
			'petInfo' => $petsInfo,
			'keeperInfo' => $keeperInfo,
		);
		
		Logger::trace( 'end pet.getAllPet' );
		
		return $ret;
	}
	
	public function feedPetByItem( $petid, $itemId, $num )
	{
		Logger::trace( 'begin pet.feedPetByItem , args: %d ,%d,%d ' , $petid, $itemId, $num );
		
		if ( $petid < 0 || $itemId < 0 || $num <= 0 )
		{
			throw new FakeException( 'args invalid' );
		}
		$exp = PetLogic::feedPetByItem( $this->uid, $petid, $itemId, $num );
		
		Logger::trace( 'end pet.feedPetByItem' );
		
		return array( 'expFeed' => $exp );
	}
	

	public function feedToLimitation( $petid )
	{
		Logger::trace( 'begin pet.feedToLimitation, args: %d ' , $petid );
		
		if ( $petid < 0 )
		{
			throw new FakeException( 'args invalid' );
		}
		$feedArr = PetLogic::feedToLimitation( $this->uid, $petid );
		
		Logger::trace( 'end pet.feedToLimitation' );
		
		//$feedArr中包含有总的喂养经验（不是实际加的经验）以及暴击的次数
		return array( 'feedArr' => $feedArr);
	}
	
	/* (non-PHPdoc)
	 * @see IPet::openPetSlot()
	 */
	public function openKeeperSlot( $prop, $num = 1  ) 
	{
		Logger::trace( 'begin pet.openKeeperSlot, args: %d ' , $num );
		
		if ( $num < 0 )
		{
			throw new FakeException( 'args invalid' );
		}
		
		PetLogic::openKeeperSlot( $this->uid,$prop, $num );
		Logger::trace( 'end pet.openKeeperSlot' );
		return 'ok';
	}

	/* (non-PHPdoc)
	 * @see IPet::swallowPet()
	 */
	public function swallowPetArr($petid, $bepetidArr) 
	{
		if ( $petid <= 0  )
		{
			throw new FakeException( 'args <0' );
		}
		if ( empty( $bepetidArr ) )
		{
			throw new FakeException( 'no bepetid' );
		}
		return PetLogic::swallowPetArr( $this->uid, $petid, $bepetidArr );
	}

	/* (non-PHPdoc)
	 * @see IPet::learnSkill()
	 */
	public function learnSkill($petid) 
	{
		if ( $petid <= 0  )
		{
			throw new FakeException( 'args <0' );
		}
		return PetLogic::learnSkill( $this->uid, $petid );
	}

	/* (non-PHPdoc)
	 * @see IPet::resetSkill()
	 */
	public function resetSkill($petid) 
	{
		if ( $petid <= 0  )
		{
			throw new FakeException( 'args <0' );
		}
		return PetLogic::resetSkill( $this->uid, $petid );
	}

	/* (non-PHPdoc)
	 * @see IPet::openSquandSlot()
	*/
	public function openSquandSlot( $flag = 0) 
	{
		Logger::trace( 'begin pet.openSquandSlot'  );
		
		PetLogic::openSquandSlot( $this->uid , $flag);
		
		Logger::trace( 'end pet.openSquandSlot' );
	}
	
	/* (non-PHPdoc)
	 * @see IPet::squandUpPet()
	 */
	public function squandUpPet($petid, $pos) 
	{
		if ( $petid <= 0 || $pos < 0  )
		{
			throw new FakeException( 'args <0' );
		}
		PetLogic::squandUpPet($this->uid, $petid,$pos);
	}
 
	/* (non-PHPdoc)
	 * @see IPet::squandDownPet()
	*/
	public function squandDownPet($pos) 
	{
		if ( $pos < 0  )
		{
			throw new FakeException( 'args <0' );
		}
		PetLogic::squandDownPet($this->uid, $pos);
	}
	
	/* (non-PHPdoc)
	 * @see IPet::fightUpPet()
	 */
	public function fightUpPet($petid) 
	{
		if ( $petid <= 0  )
		{
			throw new FakeException( 'args <0' );
		}
		PetLogic::fightUpPet($this->uid, $petid);
	}

	/* (non-PHPdoc)
	 * @see IPet::getProduction()
	 */
	public function collectProduction($petid) 
	{
		if ( $petid < 0 )
		{
			throw new FakeException( 'neg args:%d', $petid );
		}
		
		PetLogic::collectProduction($this->uid, $petid);
	}
	
	/* !CodeTemplates.overridecomment.nonjd!
	 * @see IPet::collectAllProduction()
	*/
	public function collectAllProduction ()
	{
	    // TODO 自动生成的方法存根
	    return PetLogic::collectAllProduction($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IPet::lockSkillSlot()
	 */
	public function lockSkillSlot( $petid, $skillId) 
	{
		if ( $petid <= 0 || $skillId < 0  )
		{
			throw new FakeException( 'args <0' );
		}
		PetLogic::lockSkillSlot( $this->uid,$petid, $skillId );
	}
	
	/* (non-PHPdoc)
	 * @see IPet::unlockSkillSlot()
	 */
	public function unlockSkillSlot($petid, $skillId)
	{
		if ( $petid <= 0 || $skillId < 0  )
		{
			throw new FakeException( 'args <0' );
		}
		PetLogic::unlockSkillSlot($this->uid,$petid, $skillId);
	}
	/* (non-PHPdoc)
	 * @see IPet::sellPet()
	 */
	public function sellPet($petidArr) 
	{
		if ( empty( $petidArr )  )
		{
			throw new FakeException( 'sell nothing?' );
		}
		return PetLogic::sellPet($this->uid,$petidArr);
	}
	/* (non-PHPdoc)
	 * @see IPet::getRankList()
	 */
	public function getRankList() 
	{
		$rankList = PetLogic::getRankList();
		
		return $rankList;
	}

	
	public function getPetInfoForRank( $uid )
	{
		Logger::trace( 'begin pet.getPetInfoForRank ' );
		
		$petsInfo = PetLogic::getAllPet( $uid );
		foreach ( $petsInfo as $key => $val )
		{
			if ( isset( $val[ 'uid' ] ) ) 
			{
				unset( $petsInfo[ $key ][ 'uid' ] );
			}
				
		}
		
		$petArrNeed = array();
		$keeperInfo = PetLogic::getKeeperInfo( $uid );
		foreach ( $keeperInfo[PetDef::VAKEEPER]['setpet'] as $pos => $posInfo  )
		{
			if ( !empty( $posInfo['petid'] ) && $posInfo['status'] == 1)
			{
				$petArrNeed[ $posInfo['petid'] ] = array (
					'pet_tmpl' => $petsInfo[ $posInfo['petid'] ][PetDef::PETTMPL],
					'va_pet' => $petsInfo[ $posInfo['petid']][PetDef::VAPET],
					'pet_fightforce' => $keeperInfo[PetDef::PET_FIGHTFORCE],
					'level' => $petsInfo[ $posInfo['petid']][PetDef::LEVEL],
				);
				
				foreach ( $petArrNeed[$posInfo['petid']]['va_pet']['skillTalent'] as $key => $skillInfo )
				{
					//这里和策划是有约定的，只有天赋技能有特殊生效条件
					if ( !PetLogic::skillEfficient($uid, $posInfo['petid'], $skillInfo['id']) )
					{
						unset($petArrNeed[$posInfo['petid']]['va_pet']['skillTalent'][$key]);
					}
				}
			}
		}
		
/* 		$ret = array(
			'petInfo' => $petsInfo,
			'keeperInfo' => $keeperInfo,
		);
		 */
		Logger::trace( 'end pet.getPetInfoForRank' );
		
		return $petArrNeed;
	}
	/* (non-PHPdoc)
	 * @see IPet::getPetHandbookInfo()
	 */
	public function getPetHandbookInfo() 
	{
		return PetLogic::getPetHandbookInfo($this->uid);
	}

	public function evolve($petId)
	{
		Logger::trace("Pet::evolve start. petId:%d", $petId);
        $this->checkEvolveSwitch();
		if(is_numeric($petId) == false || $petId <= 0)
		{
			throw new FakeException("error param petId:%d", $petId);
		}
		return PetLogic::evolve($this->uid, $petId);
	}

	public function wash($petId, $grade, $num=1, $ifForce=0)
	{
		Logger::trace("Pet::wash petId:%d", $petId);
        $this->checkEvolveSwitch();
		if(is_numeric($petId) == false || $petId <= 0 || !in_array($grade, array(1, 2, 3))
			|| is_numeric($num) == false || $num <= 0 || $num > 10 || !in_array($ifForce, array(0, 1))
		)
		{
			throw new FakeException("invalid petId:%d grade:%d num:%d", $petId, $grade, $num);
		}
		return PetLogic::wash($this->uid, $petId, $grade, $num, $ifForce);
	}

	public function exchange($petId1, $petId2)
	{
        $this->checkEvolveSwitch();
		if(is_numeric($petId1) == false || $petId1 <= 0 || is_numeric($petId2) == false || $petId2 <= 0)
		{
			throw new FakeException("invalid petId1:%d petId2:%d", $petId1, $petId2);
		}
		Logger::trace("Pet::exchange petId1:%d, petId2:%d", $petId1, $petId2);
        return PetLogic::exchange($this->uid, $petId1, $petId2);
	}

	public function ensure($petId)
	{
		Logger::trace("Pet::ensure petId:%d", $petId);
        $this->checkEvolveSwitch();
		if(is_numeric($petId) == false || $petId <= 0)
		{
			throw new FakeException("invalid petId:%d", $petId);
		}
		return PetLogic::ensure($this->uid, $petId);
	}

	public function giveUp($petId)
	{
		Logger::trace("pet::giveUp petId:%d", $petId);
        $this->checkEvolveSwitch();
		if(is_numeric($petId) == false || $petId <= 0)
		{
			throw new FakeException("invalid petId:%d", $petId);
		}
		return PetLogic::giveUp($this->uid, $petId);
	}

    public function checkEvolveSwitch()
    {
        if (EnSwitch::isSwitchOpen(SwitchDef::PETEVOLVE) == false)
        {
            throw new FakeException("pet evolve not open");
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
