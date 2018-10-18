<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnPet.class.php 233059 2016-03-16 10:21:19Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/EnPet.class.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-16 10:21:19 +0000 (Wed, 16 Mar 2016) $
 * @version $Revision: 233059 $
 * @brief 
 *  
 **/
class EnPet
{
	public static function petAdditionForHero( $uid )
	{
		//第10个版本加上的
		if( !EnSwitch::isSwitchOpen(SwitchDef::PET, $uid) )
		{
			return array();
		}
		
		$keeperInst = KeeperObj::getInstance($uid);
		$fightPetId = $keeperInst->getFightPet();
		if( empty( $fightPetId ) )
		{
			$fightPetId =0;
		}
		Logger::debug('fightPetId is :%d',$fightPetId );
		$additionArr = PetLogic::getAdditionArr($uid, $fightPetId);
		Logger::debug('additionArr raw: %s', $additionArr);
		//转化一下
		$additionArr = HeroUtil::adaptAttr( $additionArr );
		
		Logger::debug('additionArr trans: %s', $additionArr);
		return $additionArr;
	}
	

	/**
	 * 添加宠物
	 * @param arr $petTmplArr 要添加的宠物的模板id组
	 * 
	 * @return allAddPetInfo,'fail', 
	 */
	
	public static function addPet($petTmplArr)
	{
		Logger::trace( 'begin pet.addPet , args: %s  ' , $petTmplArr);
		if ( !EnSwitch::isSwitchOpen( SwitchDef::PET ) )
		{
			throw new FakeException( 'pet is not open' );
		}
		//处理方式，可以加就全加，不可以就fail
		$uid = RPCContext::getInstance()->getUid();
		$petMgr = PetManager::getInstance($uid);
		$keeperInst = KeeperObj::getInstance($uid);
		$allpet = $petMgr->getAllPet();
		
		//宠物小窝上限
		$limit = $keeperInst->getKeeperSlot();
		if ( count( $allpet ) >= $limit )
		{
			return 'fail';
		}
		
		//记录下所有新添加的宠物信息返回前段，因为有随机的技能
		$allBookPetBefore = PetLogic::getPetHandbookInfo($uid);
		$allAddPetInfo = array();
		
		foreach ( $petTmplArr as $pettmpl => $petnum )
		{
			for ( $i=0;$i<$petnum;$i++ )
			{
				$allAddPetInfo[] = $petMgr->addNewPet( $pettmpl );
			}
		}
		
		$petMgr->update();
		RPCContext::getInstance()->unsetSession( PetDef::HANDBOOK_SESSION );
		
		Logger::trace( 'end pet.addPet' );
		
		return $allAddPetInfo;
	}
	

	/**
	 * 得到出战宠物的详细信息
	 * @param unknown $uid
	 * @return array(
	 * array (
	 *					'petid' => int,宠物id
	 *					'pet_tmpl' => int, 宠物模板id
	 *					'level' => int ,宠物等级
	 *					'va_pet' => array(
	 *							skillTalent => array(0 => array(id => 0, level => int, status => int)),
	 *							skillNormal => array(0 => array(id => 0level => int, status => int)),
	 *							skillProduct => array(0 => array(id => 0, level => int, status => int)),
	 *					), 宠物技能相关
	 *		),
	 * )
	 */
	public static function getFightPetInfo($uid)
	{
		if( !EnSwitch::isSwitchOpen(SwitchDef::PET, $uid) )
		{
			return array();
		}
		
		$keeperInst = KeeperObj::getInstance($uid);
		$fightPetId = $keeperInst->getFightPet();
		if ( empty($fightPetId) )
		{
			return array();
		}
		$petMgr = PetManager::getInstance($uid);
		$onePetInfo = $petMgr->getOnePetInfo($fightPetId);
		
		if ( empty( $onePetInfo ) )
		{
			return array();
		}
		//$onePetInfo['arrSkill'] = $onePetInfo[PetDef::VAPET];
		$vaPet = $onePetInfo[PetDef::VAPET];

        Logger::trace('vaPet:%s', $vaPet);

		$notNeed = array
		(
				PetDef::EXP, 
				PetDef::SWALLOW,
				PetDef::SKILLPOINT,
				PetDef::TRAINTIME,
				PetDef::DELETE_TIME,
				PetDef::VAPET,
		);
		foreach ( $notNeed as $oneNotNeed )
		{
			if (isset( $onePetInfo[$oneNotNeed] ))
			{
				unset($onePetInfo[$oneNotNeed]);
			}
		}
		
		foreach ( $vaPet['skillTalent'] as $key => $skillInfo )
		{
			//这里和策划是有约定的，只有天赋技能有特殊生效条件
			if ( !PetLogic::skillEfficient($uid, $fightPetId, $skillInfo['id']) )
			{
				unset($vaPet['skillTalent'][$key]);
			}
		}
		$vaPet['skillTalent'] = array_merge($vaPet['skillTalent']);

        $onePetInfo['arrSkill']['skillTalent'] = $vaPet['skillTalent'];
        $onePetInfo['arrSkill']['skillNormal'] = $vaPet['skillNormal'];
        $onePetInfo['arrSkill']['skillProduct'] = $vaPet['skillProduct'];
        $onePetInfo['evolveLevel'] = $petMgr->getEvolveLevel($fightPetId);
        $onePetInfo['confirmed'] = empty($vaPet['confirmed']) ? array() : $vaPet['confirmed'];

        Logger::trace('onePetInfo:%s', $onePetInfo);

		return array( $onePetInfo );
	}

	
	/**
	 * 获取宠物种类
	 * @param unknown $uid
	 * @return number
	 */
	public static function getPetType( $uid )
	{
		$petMgr = PetManager::getInstance($uid);
		$allPetInfo = $petMgr->getAllPet();
		
		$typeArr = array();
		foreach ( $allPetInfo as $onePet )
		{
			if ( !in_array( $onePet[PetDef::PETTMPL] , $typeArr) )
			{
				$typeArr[] = $onePet[PetDef::PETTMPL];
			}
		}
		
		return $typeArr;
	}
	
	/**
	 * 获取紫宠的普通技能数量（普通技能是通过领悟获得）
	 * @param unknown $uid
	 * @return number
	 */
	public static function getAdvPetSkillNum($uid)
	{
		$petMgr = PetManager::getInstance($uid);
		$allPetInfo = $petMgr->getAllPet();
		
		$advPetSkillNum = 0;
		foreach ( $allPetInfo as $onePet )
		{
			$petConf = btstore_get()->PET[$onePet[PetDef::PETTMPL]];
			
			if ($petConf['qulity'] >= PetCfg::ADV_QUALITY)
			{
				$thisPetSkillNum = 0;
				foreach ( $onePet[PetDef::VAPET]['skillNormal'] as $pos => $info )
				{
					if ( $info['id'] != 0)
					{
						$thisPetSkillNum++;
					}
				}
				$advPetSkillNum = $thisPetSkillNum > $advPetSkillNum? $thisPetSkillNum:$advPetSkillNum;
			}
		}
		
		return $advPetSkillNum;
	}
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
