<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Pet.test.php 201196 2015-10-09 09:04:28Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pet/test/Pet.test.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-10-09 09:04:28 +0000 (Fri, 09 Oct 2015) $
 * @version $Revision: 201196 $
 * @brief 
 *  
 **/
class PetTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 60000 + rand(0,9999);
		$this->utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
		$console = new Console();
		$console->openSwitch(SwitchDef::PET);
		EnUser::release( $this->uid );
		
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
		EnUser::release();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
		PetManager::release();
		KeeperObj::release();
		RPCContext::getInstance()->unsetSession( 'pet.allInfo' );
		RPCContext::getInstance()->unsetSession( 'keeper.info' );
	}

	/**
	public function test_addPet_0()
	{
		printf( "==========================test_addPet_0 \n" );
		$ret = EnPet::addPet( array( 1=>1 ) );
		$petInst = new Pet();
		$ret = $petInst->getAllPet();
	}
	
	public function test_openSquandSlot_0()
	{
		printf( '==========================test_openSquandSlot_0' );
		$userLevel = EnUser::getUserObj($this->uid )->getLevel();
		$keeperInst = KeeperObj::getInstance( $this->uid );
		$ret = $keeperInst->getVaKeeper();
		var_dump( $ret );
		$slotNum = count( $ret['setpet'] );
		$needLevel = btstore_get()->NORMAL_CONFIG[8][$slotNum][0];
		$console = new Console();
		$console->level( $needLevel );
		$userLevel = EnUser::getUserObj($this->uid )->getLevel();
		var_dump( 'user level now: ' );
		printf( $userLevel );
		
		$ret = $keeperInst->getVaKeeper();
		var_dump( $ret );
	}
	
	public function test_openSquandSlotByGold_0()
	{
		printf( '==========================test_openSquandSlotByGold_0' );
		$user = EnUser::getUserObj( $this->uid );
		$user->addGold( 1000 , StatisticsDef::ST_FUNCKEY_ACTIVE_PRIZE);
		
		$keeperInst = KeeperObj::getInstance( $this->uid );
		$ret = $keeperInst->getVaKeeper();
		var_dump( $ret );
		
		$slotNum = count( $ret['setpet'] );
		
		$pet = new Pet();
		$pet->openSquandSlot( $slotNum );
		$ret = $keeperInst->getVaKeeper();
		var_dump( $ret );
	}
	*/
	public function test_feedPet_0()
	{
		printf( '==========================test_feedPet_0' );
		$console = new Console();
        $console->level( 20 );
		//给用户发点喂宠物的物品
		$bag = BagManager::getInstance()->getBag( $this->uid );
		$bag->addItemByTemplateID( 50001 , 100);
		$bag->update();
		$itemId = $bag->getItemIdsByItemType( ItemDef::ITEM_TYPE_FEED );
		$itemId = $itemId[0];
		
		//喂之
		EnPet::addPet( array( 1=>1 ) );
		$petMgr = PetManager::getInstance($this->uid);
		$allPet = $petMgr->getAllPet();
		$petid = key( $allPet );
		var_dump( $allPet );
		
		$pet = new Pet();
		$pet->feedPetByItem($petid, $itemId, 1);
		$allPet = $petMgr->getAllPet();
		var_dump( $allPet );
		$petInfo = $petMgr->getOnePetInfo($petid);
		
		$this->assertTrue( $petInfo[ 'exp' ] > 0 );
		$this->assertTrue( $petInfo[ 'level' ] >= 0 );
		$this->assertTrue( $bag->getItemNumByTemplateID( 50001 ) == 99 );
	}
	
	
	public function test_feedLimitation_0()
	{
		printf( "==========================test_feedLimitation_0\n" );
		$console = new Console();
        $console->level( 40 );
		//给用户发点喂宠物的物品
		$bag = BagManager::getInstance()->getBag( $this->uid );
		$bag->addItemByTemplateID( 50001 , 2);
		$bag->update();
		//喂之
		EnPet::addPet( array( 1=>1 ) );
		$petMgr = PetManager::getInstance($this->uid);
		$allPet = $petMgr->getAllPet();
		$petid = key( $allPet );
		var_dump( $allPet );
		$pet = new Pet();
		$pet->feedToLimitation( $petid );
		$allPet = $petMgr->getAllPet();
		var_dump( $allPet );
		$petInfo = $petMgr->getOnePetInfo($petid);
		
		//var_dump( $petInfo );
		$this->assertTrue( $petInfo[ 'exp' ] > 0 );
		$this->assertTrue( $petInfo[ 'level' ] >= 0 );
		//有剩下物品
		//$this->assertTrue( $bag->getItemNumByTemplateID( 50001 ) == 1 );
		//var_dump( $bag->getItemNumByTemplateID( 50001 ) );
	}
	/*
	public function test_squandUpPet_0()
	{
		printf( "==========================test_squandUpPet_0\n" );
		EnPet::addPet( array( 1=>1 ) );
		$petMgr = PetManager::getInstance($this->uid);
		$allPet = $petMgr->getAllPet();
		$petid = key( $allPet );
		$pet = new Pet();
		
		$pet->squandUpPet( $petid , 0);
		
		$keeperInst = KeeperObj::getInstance($this->uid);
		$ret = $keeperInst->getVaKeeper();
		var_dump( $ret );
	}
	
	
	public function test_fightUpPet_0()
	{
		printf( "==========================test_fightUpPet_0\n" );
		EnPet::addPet( array( 1=>1 ) );
		$petMgr = PetManager::getInstance($this->uid);
		$allPet = $petMgr->getAllPet();
		$petid = key( $allPet );
		$pet = new Pet();
		
		$pet->squandUpPet( $petid , 0);
		$vaKeeper = KeeperObj::getInstance( $this->uid )->getVaKeeper();
		var_dump( $vaKeeper );
		$pet->fightUpPet($petid);
		$vaKeeper = KeeperObj::getInstance( $this->uid )->getVaKeeper();
		var_dump( $vaKeeper );
	}
	
	public function test_swallowPetArr_0()
	{
		printf( "==========================test_swallowPetArr_0\n" );
		EnPet::addPet( array( 1=>1 ) );
		EnPet::addPet( array( 1=>1 ) );
		
		$petMgr = PetManager::getInstance($this->uid);
		$allPet = $petMgr->getAllPet();
		var_dump( $allPet );
		$petid = key( $allPet );
		next( $allPet );
		$bepetid = key( $allPet );
		
		$pet = new Pet();
		$pet->swallowPetArr($petid, array( $bepetid ));
		
		$allPet = $petMgr->getAllPet();
		var_dump( $allPet );
	}
	
	  public function test_lrnSkill_0()
    {
        printf( "==========================test_lrnSkill_0\n" );
        EnPet::addPet( array( 1=>1 ) );
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
        var_dump( $allPet );
        $petid = key( $allPet );

        $pet = new Pet();
        $pet->learnSkill($petid);

        $allPet = $petMgr->getAllPet();
        var_dump( $allPet );
    }
	
	public function test_lockSkill_0()
    {
        printf( "==========================test_lockSkill_0\n" );
        EnPet::addPet( array( 1=>1 ) );
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
        var_dump( $allPet );
        $petid = key( $allPet );

        $pet = new Pet();
        $pet->learnSkill($petid);

		$pet->lockSkillSlot($petid, 0);
		$allPet = $petMgr->getAllPet();
        var_dump( $allPet );
    }
	
	public function test_unlockSkill_0()
    {
        printf( "==========================test_lockSkill_0\n" );
        EnPet::addPet( array( 1=>1 ) );
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
        var_dump( $allPet );
        $petid = key( $allPet );

        $pet = new Pet();
        $pet->learnSkill($petid);

        $pet->lockSkillSlot($petid, 0);
		$pet->unlockSkillSlot($petid, 0);
        $allPet = $petMgr->getAllPet();
        var_dump( $allPet );
    }
	*/
	/**	
	public function test_resetSkill_0()
    {
        printf( "==========================test_resetSkill_0\n" );
        EnPet::addPet( array( 1=>1 ) );
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
        $petid = key( $allPet );
        $pet = new Pet();
		$console = new Console();
        $console->level( 40 );
        //给用户发点喂宠物的物品
        $bag = BagManager::getInstance()->getBag( $this->uid );
        $bag->addItemByTemplateID( 50001 , 6);
        $bag->update();
		$pet->feedToLimitation( $petid );
        $pet->learnSkill($petid);
		$pet->learnSkill($petid);
		$pet->learnSkill($petid);
		$pet->learnSkill($petid);
		$pet->learnSkill($petid);
		$pet->learnSkill($petid);
		$allPet = $petMgr->getAllPet();
		$skillId = $allPet[$petid][PetDef::VAPET]['skillNormal']['0']['id'];
        $pet->lockSkillSlot($petid, $skillId);
        $allPet = $petMgr->getAllPet();
		var_dump( $allPet );
		$pet->resetSkill($petid);
        var_dump( $allPet );
    }

	 public function test_openKeeperSlot_0()
    {
        printf( "==========================test_openKeeperSlot_0\n" );
		$keeperInst = keeperObj::getInstance($this->uid);
		$ret = $keeperInst->getKeeperInfo();
		var_dump($ret);

        $pet = new Pet()e
		$pet->openKeeperSlot(2);
		
		$ret = $keeperInst->getKeeperInfo();
        var_dump($ret);
    }
	public function test_collection_0()
	{
		printf( "==========================test_collection_0\n" );
        EnPet::addPet( array( 1=>1 ) );
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
		var_dump($allPet);
        $petid = key( $allPet );
        $pet = new Pet();
        $console = new Console();
        $console->level( 40 );
		$pet->squandUpPet($petid, 0);
		$allPet = $petMgr->getAllPet();
        var_dump($allPet);
		$keeperInst = keeperObj::getInstance($this->uid);
		$ret = $keeperInst->getKeeperInfo();
		var_dump($ret);
		$pet->collectProduction($petid);
		$ret = $keeperInst->getKeeperInfo();
        var_dump($ret);
		
	}
	*/
	public function test_additionArr_0()
	{
		printf( "==========================test_additionArr_0\n" );
		$console = new Console();
		$console->openSwitch(SwitchDef::PET);
		$console->level( 80 );
		
		$keeperInst = KeeperObj::getInstance($this->uid);
		$keeperInst->openKeeperSlot( 40 );
/* 		$allPetConf = btstore_get()->PET->toArray();
		foreach ( $allPetConf as $petTmpl => $petConf )
		{
			EnPet::addPet( array( $petTmpl=>1 ) );
		} */
		EnPet::addPet( array( 1=>1 ) );
		
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
        var_dump( $allPet );
        $petid = key( $allPet );
        $pet = new Pet();
        $console = new Console();
        $console->vip(8);
        $console->level( 40 );
        
        $pet->squandUpPet($petid, 0);
        
        $console->petNormalSkill( 0 , 1101);
        $console->petNormalSkill( 0 , 1102);
        Logger::debug('2================');
        $console->petTalentSkill( 0 , 1103, 2);
        $console->petProductSkill( 0 , 2101,3);
        
        $petMgr->release();
        RPCContext::getInstance()->unsetSession( 'pet.allInfo' );
        $petMgr = PetManager::getInstance($this->uid);
        $ret = $petMgr->getAllPet();
        var_dump( $ret );
		$ret = EnPet::petAdditionForHero($this->uid);
		
		printf( "additionresult==========================\n" );
		var_dump($ret);
		printf( "handbookresult==========================\n" );
		$info = $pet->getPetHandbookInfo();
		var_dump($info);
	}
	
	public function test_skill_0()
	{
		printf( "==========================test_skill_0\n" );
		$console = new Console();
		$console->openSwitch(SwitchDef::PET);
		$console->level( 80 );
		
		$keeperInst = KeeperObj::getInstance($this->uid);
		$keeperInst->openKeeperSlot( 40 );
		EnPet::addPet( array( 1=>1 ) );
		
        $petMgr = PetManager::getInstance($this->uid);
        $allPet = $petMgr->getAllPet();
        $petid = key( $allPet );
        $pet = new Pet();
        $console = new Console();
        $console->vip(8);
        $console->level( 40 );
        
        $pet->squandUpPet($petid, 0);
        $pet->fightUpPet($petid);
        
        $console->petProductSkill( 0 , 2011 , 2);
        $ret = EnPet::petAdditionForHero($this->uid);
		var_dump( $ret );
        $console->petNormalSkill(0, 1305);
        $ret = EnPet::petAdditionForHero($this->uid);
        var_dump( $ret );
        
        $petMgr = PetManager::getInstance($this->uid);
        $ret = $petMgr -> getAllPet();
        var_dump( $ret );
	}
	
	public function test_list_0()
	{
		printf( "==========================test_list_0\n" );
		$console = new Console();
		$console->openSwitch(SwitchDef::PET);
		$console->level( 80 );
	
		$keeperInst = KeeperObj::getInstance($this->uid);
		$keeperInst->openKeeperSlot( 40 );
		EnPet::addPet( array( 1=>1 ) );
	
		$rankList = PetLogic::getRankList();
		
		var_dump( $rankList );
	}
	
	public function test_infoForList_0()
	{
		$pet = new Pet();
		$ret = $pet->getPetInfoForRank( $this->uid );
		var_dump( $ret );
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
