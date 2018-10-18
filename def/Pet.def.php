<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pet.def.php 230249 2016-03-01 10:36:25Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Pet.def.php $
 * @author $Author: ShijieHan $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-01 10:36:25 +0000 (Tue, 01 Mar 2016) $
 * @version $Revision: 230249 $
 * @brief 
 *  
 **/
class PetDef
{
	
	const PETID = 'petid';
	const PETTMPL = 'pet_tmpl';
	const LEVEL = 'level';
	const EXP = 'exp';
	const SKILLPOINT = 'skill_point';
	const SWALLOW = 'swallow';
	const SKILLSLOT = 'skill_slot';
	const VAPET = 'va_pet';
	const PET_DBSTATUS  = 'status_db';
	const DELETE_TIME = 'delete_time';
	const TRAINTIME = 'traintime';

    const UID = 'uid';
	const KEEPERSLOT = 'keeper_slot';
	const PET_FIGHTFORCE = 'pet_fightforce';
	const VAKEEPER = 'va_keeper'; 
	
	const OPEN_SQUAND_SLOT_LEVEL = 0;
	const OPEN_SQUAND_SLOT_GOLD = 1;
	
	const OK = 0;
	const DELETE = 1;
	
	const SKILL_UNLOCK = 0;
	const SKILL_LOCK   = 1;
	
	const NO_SPECIAL = 0;
	const SPECIAL_SKILLARR = 1;
	const SPECIAL_PETARR = 2;
	
	const NORMAL_CONF_INDEX = 8;
	
	public static $petType = array(
			self::ALL_TYPE,
			self::NORMAL_PET,
			self::DISTINCT_PET,
	);
	
	const ALL_TYPE = 'allType';
	
	const NORMAL_PET = 'nomalPet';
	
	const DISTINCT_PET = 'distinctPet';
	
	//sessionKey
	const PET_SESSION = 'pet.allInfo';
	
	//自动生成id
	const PET_ID = 'petid';
	
	//喂养的暴击类型
	const CRI_ITEM = 0;
	
	const PET_TRAIN_TIME = 60;
	
	const HANDBOOK_SESSION = 'pet.handbook';

    const WASH_RETURN_STONE = 60108;

}

class KeeperDef
{
	const KEEPER_SESSION = 'keeper.info';
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */