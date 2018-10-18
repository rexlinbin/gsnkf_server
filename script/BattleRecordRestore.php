	<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleRecordRestore.php 239674 2016-04-21 12:20:13Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/BattleRecordRestore.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2016-04-21 12:20:13 +0000 (Thu, 21 Apr 2016) $
 * @version $Revision: 239674 $
 * @brief 
 *  
 **/

/**
 * @example btscript gamexxx BattleRecordRestore.php uid uname op[search|check|fix] [brid|date]...
 * 
 * @see help()
 * @author wuqilin
 *
 */
/**
 * 重生的小伙伴无法恢复不了属性
 * @author wuqilin
 *
 */
class BattleRecordRestore extends BaseScript
{
	
	private static $arrConfPath = array(
			'./config',		
			'/home/pirate/static/config'
	);
	
	
	private static $mapItemTypeInt2Str = array(

	); //在构造函数中会根据arrDataObj生成
	
	//需要处理的数据
	private static $arrDataObj = array(
		'hero' => array(
				'type' => 'hero',
				'defStrictModel' => 1,
		),
		'pet' => array(
				'type' => 'pet',
				'defStrictModel' => 1,
		),
		'arming' => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictArm',
				'bagName' => 'arm',
				'equipName' => 'arming',
				'dealFunc' => 'updateArm',
				'itemTypeInt' => 2,
				'gainFunc' => 'getGainOfArm',
		),
		'treasure' => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictTreasure',
				'bagName' => 'treas',
				'equipName' => 'treasure',
				'dealFunc' => 'updateTreasure',
				'itemTypeInt' => 11,
				'gainFunc' => 'getGainOfTreasure',
		),
		'dress' => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictDress',
				'bagName' => 'dress',
				'equipName' => 'dress',
				'dealFunc' => 'updateDress',
				'itemTypeInt' => 14,
				'gainFunc' => 'getGainOfDress',
		),
		'godWeapon' => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictGodWeapon',
				'bagName' => 'godWp',
				'equipName' => 'godWeapon',
				'dealFunc' => 'updateGodWeapon',
				'itemTypeInt' => 16,
				'gainFunc' => 'getGainOfGodWeapon',
		),
		'fightSoul' => array(
				'type' => 'equip',
				'defStrictModel' => 0,
				'strictFunc' => 'strictFightsoul',
				'bagName' => 'fightSoul',
				'equipName' => 'fightSoul',
				'dealFunc' => 'updateFightSoul',
				'itemTypeInt' => 13,
		),
		'skillbook' => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictSkillbook',
				'bagName' => '',
				'equipName' => 'skillBook',
				'dealFunc' => 'updateSkillbook',
				//'itemTypeInt' => ,
		),
		'rune' => array(
				'type' => 'equip',
				'defStrictModel' => 0,
				'strictFunc' => 'strictRune',
				'bagName' => 'rune',
				'equipName' => '',
				'dealFunc' => 'updateRune',
				'itemContainerName' => 'treasureInlay',
				'itemTypeInt' => 18,
				'gainFunc' => 'getGainOfRune',
		),
		'pocket' => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictPocket',
				'bagName' => 'pocket',
				'equipName' => 'pocket',
				'dealFunc' => 'updatePocket',
				'itemTypeInt' => 20,
		),
		
		'tally'  => array(
				'type' => 'equip',
				'defStrictModel' => 1,
				'strictFunc' => 'strictTally',
				'bagName' => 'tally',
				'equipName' => 'tally',
				'dealFunc' => 'updateTally',
				'itemTypeInt' => 21,
				'gainFunc' => 'getGainOfTally',
		),
	);
	
	
	private static $foundErro = false;
	
	private static $foundWarn = false;
	
	private $mUid;
	
	private $mUname;
	
	private $mUserLvRecord;		//战报中的玩家等级
	
	/*
	private $mArrArm = array();  		//武器
	private $mArrTreasure = array(); 	//宝物
	private $mArrDress = array();		//时装
	private $mArrFightsoul = array();	//战魂
	private $mArrSkillbook = array();	//技能书
	private $mArrGodWeapon = array();	//神兵
	private $mArrRune = array();
	private $mArrPocket = array();
	*/
	//将上面所有的装备都放在一个数组中，方便统一处理
	private $mAllEquip = array();
	
	
	private $mArrPet = array();
	private $mArrLostPet = array();	//战报中有，但是被删掉的宠物和宠物现在数据库中的数据
	private $mArrModifyPet = array();
	
	private $mArrHero = array();
	private $mArrLittleFriend = array();
	private $mArrAttrFriend = array();
	
	private $mAllHeroInfoInRecord = array();
	
	private $mArrModifyItem = array();   //这里保存需要修改的物品va信息，和lostItem的va信息（修改后的结果）
	
	private $mArrLostItemInfo = array(); //这里存的是:在战报中有，但现在没有的物品，和这些物品现在在数据库中的信息
	
	
	private $mArrLostUnusedLittleFriend = array(); 	//战报中的数据。 处理方式：直接添加一个unused hero
	
	private $mArrLostUnusedHero = array();			//战报中的数据。 处理方式：添加一个unused hero
	
	private $mArrLostLittleFriend = array();		//数据库中的数据。处理方式：充值删除状态。如果武将level!=1, evolve_level!=0会有获利。
	
	private $mArrLostHero = array();				//数据库中的数据。处理方式：充值删除状态。如果武将level!=1, evolve_level!=0会有获利。
	
	private $mArrLostUnusedAttrFriend = array();
	
	private $mArrLostAttrFriend = array();
	
	private $mArrModifyHero = array();				//需要修改的数据 {level=>int, evolve_level=>int }。处理方式：修改对应属性。会有获利。
	
	private $mArrGainByHero = array(); //处理盗号时，估计出来的获益
	
	private $mArrGainByItem = array();
	
	private $mArrNeedDelItem = array();
	
	private $mArrOrangeHid = array();
	
	public function help()
	{
		echo("help:\n");
		echo("btscript gamexxx BattleRecordRestore.php uid uname op[search|check|fix] [brid|date]...\n");
		echo("/home/pirate/programs/php/bin/php /home/pirate/rpcfw/lib/ScriptRunner.php -g gamexxx -d piratexxx -f BattleRecordRestore.php ...\n");
		echo("【1】获取战报：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 search `date -d '2014-02-16 00:00:00' +%s `\n");
		echo("【2】获取数据：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 get  82976\n");
		echo("【3】检查数据：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 check  152568c29fd17eb2\n");
		echo("【4】处理数据：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 fix    152568c29fd17eb2 [strict_hero_arm_treasure_dress] [ignore_*]\n");
		echo("【5】封   号：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 ban  24(hour)\n");
		echo("【6】解   封：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 unban\n");
		echo("【7】扣银币 ：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 subsilver num\n");
		echo("【8】扣将魂 ：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 subsoul num\n");
		echo("【9】扣魂玉：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 subjewel num\n");
		echo("【10】扣天宫：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 subtg num\n");
		echo("【11】扣物品 ：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 subitem tplId num\n");
		echo("【12】扣武将：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 subhero tplId num\n");
		echo("【13】修改物品：btscript game001 BattleRecordRestore.php 51846 娜芙亚琪娜 setitem type[arm|fightSoul|treasure] ...\n");
		
		echo(" 【部分物品id】\n");
		echo("     经验银马: 501001\n");
		echo("     经验银书: 502001\n");
		echo("     宝物精华: 501010\n");
		echo("     时装精华: 60016\n");
		echo("     洗炼石 : 60007\n");
		echo("     进阶丹 : 60002\n");
		echo("     \n");
		
		echo(" 【部分武将id】\n");
		echo("     经验熊猫: 40001\n");
		echo("\n");
		
	}
	

	protected function executeScript ($arrOption)
	{
		//检查参数
		if( count($arrOption) < 3 )
		{
			$this->help();
			return;
		}
		
		foreach( self::$arrDataObj as $name => $conf )
		{
			if( isset($conf['itemTypeInt']) )
			{
				self::$mapItemTypeInt2Str[ $conf['itemTypeInt'] ] = $name;
			}
		}
		
		$uid = intval( $arrOption[0] );
		$uname = $arrOption[1];
		$op = $arrOption[2];
		$extra = implode(' ', array_slice($arrOption, 3));
		
		//检查用户信息
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$userObj = EnUser::getUserObj($uid);
		if( $userObj->getUname() != $uname )
		{
			self::info("uid:%d, uname:%s not match %s", $uid, $userObj->getUname(), $uname);
			return;
		}
		
		self::info('uid:%d, uname:%s, pid:%s, op:%s, extra:%s', 
				$uid, $uname, BabelCrypt::encryptNumber( $userObj->getPid() ), $op, $extra);
		
		$this->mUid = $uid;
		$this->mUname = $uname;
		
		switch ($op)
		{
			case 'search':
				$maxDateStr = $arrOption[3];
				$minDateStr = empty($arrOption[4]) ? '' : $arrOption[4];
				$arrRet = $this->searchBrid($uid, $uname, $minDateStr, $maxDateStr);
				if( empty($arrRet) )
				{
					self::info('not found brid');
					break;
				}
				$msg = '';
				foreach( $arrRet as $ret )
				{
					$msg .= sprintf("brid:%d, time:%s, tpl:%s\n", 
							$ret['brid'], date('Y-m-d H:i:s', $ret['time']), $ret['tpl']);
				}
				self::info('search brid result');
				self::info('%s', $msg);
				break;
				
			case 'get':
				$brid = $this->dealBrid( $arrOption[3] );
				$ret = $this->getBattleData($uid, $uname, $brid);
				self::info('%s', $ret['msg']);
				break;
				
			case 'check':
				$brid = $this->dealBrid( $arrOption[3] );
				$mode = isset($arrOption[4] ) ? $arrOption[4] : 'normal';
				$ignore = isset($arrOption[5] ) ? $arrOption[5] : 'none';
				$this->checkBattleData($uid, $uname, $brid, false, $mode, $ignore);
				break;
			
			case 'fix':
				$brid = $this->dealBrid( $arrOption[3] );
				$mode = isset($arrOption[4] ) ? $arrOption[4] : 'normal';
				$ignore = isset($arrOption[5] ) ? $arrOption[5] : 'none';
				$this->checkBattleData($uid, $uname, $brid, true, $mode, $ignore);
				break;
				
			case 'ban':
				$banHour = intval( $arrOption[3] );
				$this->banUser($uid, $uname, $banHour);
				break;
				
			case 'unban':
				$this->unBanUser($uid, $uname);
				break;
			
			case 'subsilver':
				$num = intval( $arrOption[3] );
				$this->subSilver($uid, $uname, $num);
				break;
				
			case 'subsoul':
				$num = intval( $arrOption[3] );
				$this->subSoul($uid, $uname, $num);
				break;
			
			case 'subjewel':
				$num = intval( $arrOption[3] );
				$this->subJewel($uid, $uname, $num);
				break;
			
			case 'subtg':
				$num = intval( $arrOption[3] );
				$this->subTg($uid, $uname, $num);
				break;
			case 'subtally':
				$num = intval( $arrOption[3] );
				$this->subTally($uid, $uname, $num);
				break;
			
			case 'subitem':
				$tplId = intval( $arrOption[3] );
				$num = intval( $arrOption[4] );
				$this->subItem($uid, $uname, $tplId, $num);
				break;
			
			case 'subhero':
				$tplId = intval( $arrOption[3] );
				$num = intval( $arrOption[4] );
				$this->subHero($uid, $uname, $tplId, $num);
				break;
				
			case 'setitem':
				$this->setItemData($uid, $uname, $arrOption[3]);
				break;
			
			default:
				self::info('invalid op:%s', $op);
		}
		
		printf("done\n");
		
	}
	
	public function subSilver($uid, $uname, $num)
	{	
		
		Util::kickOffUser($uid);
		
		$userObj = EnUser::getUserObj($uid);
		
		$curNum = $userObj->getSilver();
		
		$subNum = min($curNum, $num);
		
		printf("sub silver. uid:%d, uname:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
			$uid, $uname, $curNum, $subNum, $num - $subNum);
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		if( $userObj->subSilver($subNum) == false )
		{
			self::fatal('uid:%d no enough silver');
			return;
		}
		
		$userObj->update();
		
		self::info('sub silver. uid:%d, subNum:%d, left:%d', $uid, $subNum, $userObj->getSilver() );
	}
	
	public function subSoul($uid, $uname, $num)
	{
		Util::kickOffUser($uid);
		
		$userObj = EnUser::getUserObj($uid);
		
		$curNum = $userObj->getSoul();
		
		$subNum = min($curNum, $num);
		
		printf("sub soul. uid:%d, uname:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
			$uid, $uname, $curNum, $subNum, $num - $subNum);
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		if( $userObj->subSoul($subNum) == false )
		{
			self::fatal('uid:%d no enough silver');
			return;
		}
		
		$userObj->update();
		
		self::info('sub soul. uid:%d, subNum:%d, left:%d', $uid, $subNum, $userObj->getSoul() );
	}
	
	public function subJewel($uid, $uname, $num)
	{
		Util::kickOffUser($uid);
	
		$userObj = EnUser::getUserObj($uid);
	
		$curNum = $userObj->getJewel();
	
		$subNum = min($curNum, $num);
	
		printf("sub jewel. uid:%d, uname:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
		$uid, $uname, $curNum, $subNum, $num - $subNum);
	
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
	
		if( $userObj->subJewel($subNum) == false )
		{
			self::fatal('uid:%d no enough silver');
			return;
		}
	
		$userObj->update();
	
		self::info('sub jewel. uid:%d, subNum:%d, left:%d', $uid, $subNum, $userObj->getJewel() );
	}
	

	public function subTg($uid, $uname, $num)
	{
		Util::kickOffUser($uid);
	
		$userObj = EnUser::getUserObj($uid);
	
		$curNum = $userObj->getTgNum();
	
		$subNum = min($curNum, $num);
	
		printf("sub tg. uid:%d, uname:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
		$uid, $uname, $curNum, $subNum, $num - $subNum);
	
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
	
		if( $userObj->subTgNum($subNum) == false )
		{
			self::fatal('uid:%d no enough silver');
			return;
		}
	
		$userObj->update();
	
		self::info('sub tg. uid:%d, subNum:%d, left:%d', $uid, $subNum, $userObj->getTgNum() );
	}

	public function subTally($uid, $uname, $num)
	{
		Util::kickOffUser($uid);
	
		$userObj = EnUser::getUserObj($uid);
	
		$curNum = $userObj->getTallyPoint();
	
		$subNum = min($curNum, $num);
	
		printf("sub tally. uid:%d, uname:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
		$uid, $uname, $curNum, $subNum, $num - $subNum);
	
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
	
		if( $userObj->subTallyPoint($subNum) == false )
		{
			self::fatal('uid:%d no enough tally');
			return;
		}
	
		$userObj->update();
	
		self::info('sub tally. uid:%d, subNum:%d, left:%d', $uid, $subNum, $userObj->getTallyPoint() );
	}
	
	public function subItem($uid, $uname, $tplId, $num)
	{
		Util::kickOffUser($uid);
		
		$bag = BagManager::getInstance()->getBag($uid);
		
		$curNum = $bag->getItemNumByTemplateID($tplId);
		
		$subNum = min($curNum, $num);
		
		printf("sub item. uid:%d, uname:%s, tplId:%d, itemName:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
				$uid, $uname, $tplId, self::getItemName($tplId), $curNum, $subNum, $num - $subNum);
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		if ( $bag->deleteItembyTemplateID($tplId, $subNum) == false )
		{
			self::fatal('uid:%d, delete itemTpl:%d, num:%d failed', $uid, $tplId, $subNum);
			return;
		}
		
		$bag->update();
		
		self::info('sub item. uid:%d, tplId:%d, subNum:%d, left:%d', $uid, $tplId, $subNum, $bag->getItemNumByTemplateID($tplId));
	}
	
	public function subHero($uid, $uname, $htid, $num)
	{
		Util::kickOffUser($uid);
		
		$userObj = EnUser::getUserObj($uid);
		
		$heroMgr = $userObj->getHeroManager();
		
		printf("uid:%d, uname:%d, all htid:%d\n", $uid, $uname, $htid);
		$arrHeroInfo = $heroMgr->getAllHero();
		$arrUnusedHero = $userObj->getAllUnusedHero();
		$arrUnused = array();
		foreach($arrHeroInfo as $hid => $heroInfo)
		{
			if( $heroInfo['htid']  == $htid )
			{
				if( isset( $arrUnusedHero[$hid]) )
				{
					if( EnFormation::isHidInAll($hid, $uid))
					{
						printf("hid:%d in formation cant del\n", $hid);
					}
					else 
					{
						$arrUnused[] = $hid;
						printf("unused hero. htid:%d, hid:%d, level:%d, evolve:%d\n", $htid, $hid, $heroInfo['level'], $heroInfo['evolve_level']);
					}
					
				}
				else
				{
					printf("htid:%d, hid:%d, level:%d, evolve:%d\n", $htid, $hid, $heroInfo['level'], $heroInfo['evolve_level']);
				}
			}
		}
		
		$curNum = count( $arrUnused );
		
		$subNum = min($curNum, $num);
		
		printf("sub hero. uid:%d, uname:%s, htid:%d, heroName:%s, curNum:%d, subNum:%d, lack:%d  (y|n)\n",
			$uid, $uname, $htid, self::getHeroName($htid), $curNum, $subNum, $num - $subNum);
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		$arrDelHid = array_slice($arrUnused, 0, $subNum);
		foreach( $arrDelHid as $hid )
		{
			$userObj->delUnusedHero($hid);
			self::info('uid:%d, del unsed hero hid:%d, htid:%d, level:%d', $uid, $hid,  $arrHeroInfo[$hid]['htid'], $arrHeroInfo[$hid]['level'] );
		}
		
		$userObj->update();
		
		self::info('sub hero. uid:%d, htid:%d, subNum:%d', $uid, $htid, $subNum);
		
	}
	
	public function setItemData($uid, $uname, $typeStr)
	{
		$itemType = 0;
		switch($typeStr)
		{
			case 'fightSoul':
				$itemType = ItemDef::ITEM_TYPE_FIGHTSOUL;
				break;
			case 'treasure':
				$itemType = ItemDef::ITEM_TYPE_TREASURE;
				break;
			case 'arm':
				$itemType = ItemDef::ITEM_TYPE_ARM;
				break;
			default:
				self::fatal('not support setItemData type:%s', $typeStr);
				return;
		}
		
		Util::kickOffUser($uid);
		
		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		
		$arrItemIdInBag = $bag->getItemIdsByItemType($itemType);
		
		$arrItemIdInFormation = array();
		
		$arrHid = EnFormation::getArrHidInFormation($uid);
		foreach ($arrHid as $hid)
		{
			$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
			$arrItemIdInFormation = array_merge($arrItemIdInFormation, $heroObj->getAllEquipId() );
		}
		
		
		$arrItemByType = array();
		$allItemId = array_merge($arrItemIdInFormation, $arrItemIdInBag);
		ItemManager::getInstance()->getItems( $allItemId  );
		
		$msg = sprintf("uid:%d, uname:%s, itemType:%s \n", $uid, $uname, $typeStr);
		foreach( $allItemId as $itemId )
		{
			if($itemId == 0)
			{
				continue;
			}
			$itemObj = ItemManager::getInstance()->getItem($itemId);
			if( empty($itemObj) )
			{
				self::fatal('cant find itemId:%d', $itemId);
				return;
			}
			if( $itemObj->getItemType() == $itemType )
			{
				$arrItemByType[] = $itemId;
				
				if( in_array( $itemId, $arrItemIdInBag ) )
				{
					$msg .= sprintf("\t[in  bag]");
				}
				else if( in_array( $itemId, $arrItemIdInFormation ) )
				{
					$msg .= sprintf("\t[in hero]");
				}
				else 
				{
					self::fatal('cant be true');
					return;
				}
				$msg .= sprintf("itemId:%d, tplId:%d, itemName:%s", 
						$itemId, $itemObj->getItemTemplateID(), self::getItemName($itemObj->getItemTemplateID()) );
				switch( $typeStr )
				{
					case 'fightSoul':
						$msg .= sprintf(" exp:%d, level:%d\n", $itemObj->getExp(), $itemObj->getLevel() );
						break;
					case 'treasure':
						$msg .= sprintf(" exp:%d, level:%d, evolve:%d\n", $itemObj->getExp(), $itemObj->getLevel(), $itemObj->getEvolve() );
						break;
					case 'arm':
						$msg .= sprintf(" level:%d, xilianshi:%d\n", 
									$itemObj->getLevel(), 
									self::getXilianshiOfArmPotence($itemObj->getItemTemplateID(), $itemObj->getPotence() ) );
						break;
				}
			}
		}
		self::info("%s", $msg);
		
		$arrOption = array();
		$helpMsg = '';
		switch( $typeStr )
		{
			case 'fightSoul':
				$arrOption = array("item", "level");
				$helpMsg = sprintf("input: item 123 level 123");
				break;
			case 'treasure':
				$arrOption = array("item", "level", "evolve");
				$helpMsg = sprintf("input: item 123 level 123  evolve 123");
				break;
			case 'arm':
				$arrOption = array("item", "level", "resetpotence");
				$helpMsg = sprintf("input: item 123 level 123  resetpotence");
				break;
		}
		while(true)
		{
			printf("%s\n", $helpMsg);
			$ret = trim(fgets(STDIN));
			if( $ret == 'n' )
			{
				printf("ignore\n");
				return;
			}
			$arrArgs = self::getArgs($arrOption, $ret);
			if( !isset( $arrArgs['item'] ) || count( $arrArgs ) < 2 )
			{
				printf("invalid input!! \n%s\n", var_export($arrArgs,true));
				continue;
			}
			break;
		}
		
		$itemId = intval( $arrArgs['item'] );
		$itemObj = ItemManager::getInstance()->getItem($itemId);
		
		$itemPos = 0;
		if( in_array($itemId, $arrItemIdInBag) )
		{
			$itemPos = 1;
		}
		else if( in_array($itemId, $arrItemIdInFormation) )
		{
			$itemInBag = 2;
		}
		else
		{
			printf("invalid itemId:%d\n", $itemId);
			return;
		}
		$itemTplId = $itemObj->getItemTemplateID();

		switch ($typeStr)
		{
			case 'fightSoul':
				$msg = sprintf("set itemId:%d, itemTpl:%d, itemName:%s\n", $itemId, $itemTplId, self::getItemName($itemTplId));
				if( isset($arrArgs['level']) )
				{
					$level = intval( $arrArgs['level'] );
					$exp = $itemObj->getUpgradeValue($level);
					$itemObj->setLevel($level);
					$itemObj->setExp($exp);
					$msg .= sprintf("\tset level:%d, exp:%d\n", $level, $exp);
				}
				else
				{
					$msg = sprintf("do nothing");
					return;
				}
				break;
			case 'treasure':
				$msg = sprintf("set itemId:%d, itemTpl:%d, itemName:%s\n", $itemId, $itemTplId, self::getItemName($itemTplId));
				if( isset($arrArgs['level']) || isset($arrArgs['evolve'])  )
				{
					if( isset($arrArgs['level']) )
					{
						$level = intval( $arrArgs['level'] );
						$exp = $itemObj->getUpgradeValue($level);
						$itemObj->setLevel($level);
						$itemObj->setExp($exp);
						
						$msg .= sprintf("\tset level:%d, exp:%d\n", $level, $exp);
					}
					
					if( isset($arrArgs['evolve']) )
					{
						$evolve = intval( $arrArgs['evolve'] );
						$itemObj->setEvolve( $evolve );
					
						$msg .= sprintf("\tset evolve:%d\n", $evolve);
					}
				}
				else
				{
					$msg = sprintf("do nothing");
					return;
				}
				break;
			case 'arming':
				$msg = sprintf("set itemId:%d, itemTpl:%d, itemName:%s\n", $itemId, $itemTplId, self::getItemName($itemTplId));
				if( isset($arrArgs['level'])  || isset($arrArgs['resetpotence']) )
				{
					if(isset($arrArgs['level']) )
					{
						$level = intval( $arrArgs['level'] );
						$itemObj->setLevel($level);
						$itemObj->setReinforceCost(0);//TODO:cost怎么处理
						$msg .= sprintf("\tset level:%d, cost:0!!\n", $level);
					}
					
					if( isset($arrArgs['resetpotence']) )
					{
						$itemObj->setPotence( array() );
							
						$msg .= sprintf("\tresetpotence\n");
					}
				}
				else
				{
					$msg = sprintf("do nothing");
					return;
				}
				break;
		}
		self::info("%s", $msg);
		printf("input: y|n\n");
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			printf("ignore\n");
			return;
		}
		
		if($itemPos == 1)
		{
			$bag->update();
		}
		else
		{
			ItemManager::getInstance()->update();
			self::info('reset battle data');
			$userObj->modifyBattleData();
			$userObj->getBattleFormation();
			$userObj->update();
		}
		$itemInfo  = $itemObj->getItemText();
		
		self::info('uid:%d, uname:%s, result:%s', $uid, $uname, var_export($itemInfo,true) );
	}
	
	
	public function banUser($uid, $uname, $banHour)
	{
		$banTime = Util::getTime() + $banHour*3600;
		
		printf("ban uid:%d, hour:%d, date:%s, (y|n)\n", $uid, $banHour, date('Y-m-d H:i:s', $banTime ));
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			return;
		}
		
		Util::kickOffUser($uid);

		$userObj = EnUser::getUserObj($uid);
		$userObj->ban($banTime, '账号已封停恢复数据中');
		$userObj->update();

		self::info('ban uid:%d, hour:%d, date:%s', $uid, $banHour, date('Y-m-d H:i:s', $banTime ) );		
	}
	
	public function unBanUser($uid)
	{
		printf("unban uid:%d. (y|n)\n", $uid );
		
		$ret = trim(fgets(STDIN));
		if( $ret != 'y' )
		{
			return;
		}
		
		Util::kickOffUser($uid);
		$userObj = EnUser::getUserObj($uid);
		$userObj->unsetBan();
		$userObj->update();
		
		self::info('unban uid:%d', $uid );
	}
	
	/**
	 * 
	 * @param int $uid
	 * @param string $uname
	 * @param int $brid
	 * @param bool $fix 
	 * @param bool $mode  处理模式。normal: xxx，strict：只归还丢失的物品。修改属性的物品不做处理
	 */
	public function checkBattleData($uid, $uname, $brid, $fix = false, $mode = 'normal', $ignore = 'none')
	{
		//参数检查
		if( $mode != 'normal' && substr($mode, 0, 6) != 'strict' )
		{
			self::info('invalid model:%s. (normal|strict)', $mode);
			return;
		}
		
		
		$arrBattleInfo = $this->getBattleData($uid, $uname, $brid);
		Logger::info('arrBattleInfo:%s', var_export($arrBattleInfo, true) );
		
		/*
		$this->mArrArm = array();
		$this->mArrTreasure = array();
		$this->mArrDress = array();
		$this->mArrFightsoul = array();
		$this->mArrSkillbook = array();
		$this->mArrGodWeapon = array();
		$this->mArrRune = array();
		*/
		$this->mArrHero = array();
		$this->mArrLittleFriend = array();
		$this->mArrAttrFriend = array();
		$this->mArrModifyItem = array();
		$this->mArrLostItemInfo = array();
		$this->mArrModifyHero = array();
		$this->mArrLostHero = array();
		$this->mArrLostUnusedHero = array();
		$this->mArrLostLittleFriend = array();
		$this->mArrLostUnusedLittleFriend = array();
		$this->mArrLostAttrFriend = array();
		$this->mArrLostUnusedAttrFriend = array();
		
		$this->mUserLvRecord = $arrBattleInfo['level'];
		
		foreach (self::$arrDataObj as $name => $conf )
		{
			if(  isset( $arrBattleInfo['arrItem'][$name] ) )
			{
				$this->mAllEquip[$name] = $arrBattleInfo['arrItem'][$name];
			}
		}
		
		
		if(  isset( $arrBattleInfo['arrPet'] ) )
		{
			$this->mArrPet = $arrBattleInfo['arrPet'];
		}
		
		$this->mArrHero = $arrBattleInfo['arrHero'];
		$this->mArrLittleFriend = $arrBattleInfo['arrLittleFriend'];
		$this->mArrAttrFriend = $arrBattleInfo['attrFriend'];
		
		$this->mAllHeroInfoInRecord = $this->mArrHero + $this->mArrAttrFriend + $this->mArrLittleFriend;
		
		$arrAllItemInRecord = array();
		foreach(  $arrBattleInfo['arrItem'] as $arr )
		{
			$arrAllItemInRecord = array_merge($arrAllItemInRecord, $arr);
		}
		$arrAllItemInRecord = Util::arrayIndex($arrAllItemInRecord, 'item_id');
		
		$this->dealItemInBag();
		
		$this->dealItemInHero();
		
		$this->dealLostItem();
		
		$this->dealHero();
		
		$this->dealLittleFriend();
		
		$this->dealAttrHero();
		
		$this->dealPet();
		
		self::info("deal oragne arming");
		//处理消耗橙装合成橙装
		$orangeItemInfo = '';
		$needCompSilver = 0;
		$allPotenceItem = array();
		foreach( $this->mArrLostItemInfo as $itemId => $infoInDb )
		{
			$itemTplId = $infoInDb['item_template_id'];
			$itemQuality = ItemManager::getInstance()->getItemQuality($itemTplId);
			$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			
			Logger::debug("itemId:%d, itemTplId:%d, itemName:%s\n", 
						$itemId, $itemTplId, self::getItemName($itemTplId) );
			if( $itemQuality >= ItemDef::ITEM_QUALITY_ORANGE )
			{
				if ( $itemType != 2 )
				{
					continue;
				}
				unset( $this->mArrLostItemInfo[$itemId] );
				unset( $this->mArrModifyItem[$itemId] );
				
				$level = $infoInDb['va_item_text']['armReinforceLevel'];
				$cost = isset($infoInDb['va_item_text']['armReinforceCost']) ?  $infoInDb['va_item_text']['armReinforceCost'] : 0;
				$arrPotenceItem = isset($infoInDb['va_item_text']['armPotence']) ? self::getArmPotenceResolve($itemTplId, $infoInDb['va_item_text']['armPotence']) : array();
				$strPotenceItem = '';
				foreach($arrPotenceItem as $tplId => $num)
				{
					$strPotenceItem .= sprintf('%s(%d):%d; ', self::getItemName($tplId), $tplId, $num);
					if( isset($allPotenceItem[$tplId]) )
					{
						$allPotenceItem[$tplId] += $num;
					}
					else
					{
						$allPotenceItem[$tplId] = $num;
					}
				}
						
				$orangeItemInfo .= sprintf("itemId:%d, itemTplId:%d, itemName:%s, level:%d, cost:%d, potence:%s\n", 
						$itemId, $itemTplId, self::getItemName($itemTplId), $level, $cost, $strPotenceItem );
				
				$needCompSilver += $cost;
			}
		}
		if ( !empty( $orangeItemInfo ) )
		{
			$orangeItemInfo .= sprintf("allSilver:%d  allXilianshi:%d,  xilianshiInBag:%d\n", 
					$needCompSilver, 
					isset($allPotenceItem[60007]) ? $allPotenceItem[60007]: 0,
					BagManager::getInstance()->getBag($uid)->getItemNumByTemplateID(60007) );
		}
		
		$arrLostHeroKey = array('mArrLostHero', 'mArrLostLittleFriend', 'mArrLostAttrFriend');
		foreach( $arrLostHeroKey as $key  )
		{
			$arrHero = $this->$key;
			foreach( $arrHero as $hid => $infoInDb )
			{
				if( isset( $infoInDb['va_hero']['transfer']) && $infoInDb['va_hero']['transfer'] > 0  )
				{
					$tranHtid = $infoInDb['va_hero']['transfer'];
					self::info('hid:%d, htid:%d, name:%s transfer to htid:%d, name:%s',
							$hid, $infoInDb['htid'], self::getHeroName($infoInDb['htid']),
							$tranHtid, self::getHeroName($tranHtid));
			
					unset( $arrHero[$hid] );
					unset( $this->mArrModifyHero[$hid] );
				}
			}
			$this->$key = $arrHero;
		}
		
		//打印差异信息
		$msg = $this->genChangeMsg($arrAllItemInRecord);
		self::info("=======================================================\n%s\n", $msg);
		
		if( substr($mode, 0, 6) == 'strict' )
		{
			$arrStrict = NULL;
			$arrValide = array_keys(self::$arrDataObj);
			$arrStr = explode('_', $mode);
			array_shift($arrStr);
			foreach($arrStr as $str)
			{
				if( in_array($str, $arrValide) )
				{
					$arrStrict[$str] = 1;
				}
				else 
				{
					self::info('invalid strict op:%s, mode%s', $str, $mode);
					return;
				}
			}
			//如果指定了要严格处理某些数据，那么剩余的就认为是不需要严格处理的
			if( !empty($arrStrict) )
			{
				foreach( $arrValide as $key )
				{
					if( !isset( $arrStrict[$key] ) )
					{
						$arrStrict[$key] = 0;
					}
				}
			}
			
			$arrIgnore = NULL;
			$arrStr = explode('_', $ignore);
			array_shift($arrStr);
			foreach($arrStr as $str)
			{
				if( in_array($str, $arrValide) )
				{
					$arrIgnore[$str] = 1;
				}
				else
				{
					self::info('invalid ignore op:%s, mode%s', $str, $ignore);
					return;
				}
			}
				
			$this->modifyChange($arrStrict, $arrIgnore);
			
			$msg = $this->genChangeMsg($arrAllItemInRecord);
			self::info("=======================================================after strict modify\n%s\n", $msg);
		}
		
		if ( !empty($orangeItemInfo) )
		{
			self::info("orange item:\n%s", $orangeItemInfo);
		}
		
		
		//发现错误时，不能执行后面的操作
		if( self::$foundErro )
		{
			self::info('something wrong. please check it!');
			return;
		}
		
		if( self::$foundWarn )
		{
			self::info('warning. please confirm. y|n');
			$ret = trim(fgets(STDIN));
			if( $ret != 'y' )
			{
				self::info('stop process');
				return;
			}
		}
		
		self::info("=======================================================");
		$this->getGainByHero();
		$this->getGainByItem();
		$this->getGainByPet();
		$this->someInfo($uid);
		
		
		$userObj = EnUser::getUserObj($this->mUid);
		$heroMgr = $userObj->getHeroManager();
			
		if($fix)
		{
			
			printf("fix this user in mode: %s, ignore:%s (y|n)\n", $mode, $ignore);
			$ret = trim(fgets(STDIN));
			self::info("choose :%s", $ret);
			if( $ret != 'y' )
			{
				return;
			}
			self::info('going to fix data');
			
			Util::kickOffUser($this->mUid);
						
			//处理物品
			foreach( $this->mArrLostItemInfo as $itemId => $infoInDb )
			{
				$itemTplId = $infoInDb['item_template_id'];
				$itemVaInfo = $infoInDb['va_item_text'];
				if ( isset( $this->mArrModifyItem[$itemId] ) )
				{
					$itemVaInfo = $this->mArrModifyItem[$itemId];
				}
				if ( isset( $itemVaInfo['treasureInlay'] )
					&& count( $infoInDb['va_item_text']['treasureInlay'] ) > 0 )
				{
					$itemVaInfo['treasureInlay'] = array();
					$this->mArrModifyItem[$itemId] = $itemVaInfo;
					self::info('lost item has inlay, remove them. itemId:%d, tplId:%d, name:%s', 
							$itemId, $itemTplId, self::getItemName($itemTplId));
				}
				
				$this->resetItem($itemId);
			}
			
			foreach( $this->mArrModifyItem as $itemId => $modifyVa )
			{
				$itemObj = ItemManager::getInstance()->getItem($itemId);
				$itemObj->setItemText($modifyVa);
				Logger::info('update itemId:%d, va:%s', $itemId, $modifyVa);
			}
			
			//需要放在奖励中心的物品id
			$arrLostItemId = array_keys( $this->mArrLostItemInfo);
			
			//处理武将
			foreach( $this->mArrLostUnusedLittleFriend as $hid => $htid )
			{
				$userObj->addUnusedHero($hid, $htid);
				Logger::info('add unused little friend. hid:%d, htid:%d', $hid, $htid);
			}
			foreach( $this->mArrLostLittleFriend as $hid => $htid )
			{
				$this->resetHero($this->mUid, $hid);
			}
			
			foreach( $this->mArrLostUnusedAttrFriend as $hid => $infoInRecord )
			{
				$htid = $infoInRecord['htid'];
				$userObj->addUnusedHero($hid, $htid);
				Logger::info('add unused attr friend. hid:%d, htid:%d', $hid, $htid);
			}
			foreach( $this->mArrLostAttrFriend as $hid => $infoInDb )
			{
				$this->resetHero($this->mUid, $hid);
			}
				
			foreach( $this->mArrLostUnusedHero as $hid => $infoInRecord )
			{
				$htid = $infoInRecord['htid'];
				$userObj->addUnusedHero($hid, $htid);
				Logger::info('add unused hero. hid:%d, htid:%d', $hid, $htid);
			}
			
			foreach( $this->mArrLostHero as $hid => $infoInDb )
			{
				$this->resetHero($this->mUid, $hid);
			}
			
			$arrModifyEvolveLevel = array();
		
			foreach( $this->mArrModifyHero as $hid => $modifyInfo )
			{
				$heroObj = $heroMgr->getHeroObj($hid);
				//丢掉的hero在普通模式下还是补个等级
				if( isset( $modifyInfo['level'] )  )
				{
					$preSoul = $heroObj->getSoul();
					$preLevel = $heroObj->getLevel();
						
					$expTblId = $heroObj->getConf(CreatureAttr::EXP_ID);
					$expTbl	= btstore_get()->EXP_TBL[$expTblId];
						
					$heroObj->addSoul( $expTbl[ $modifyInfo['level'] ] - $preSoul );
						
					Logger::info('add soul. hid:%d, pre:%d, cur:%d', $hid, $preSoul, $heroObj->getSoul());
						
					if( $heroObj->getLevel() !=  $modifyInfo['level'] )
					{
						self::fatal('modify hero failed. hid:%d, preSoul:%d, curSoul:%d, preLevel:%d, curLevel:%d',
								$hid, $preSoul, $heroObj->getSoul(), $preLevel, $heroObj->getLevel() );
						$heroObj->rollback();
						continue;
					}
						
					unset($modifyInfo['level']);
				}
			
				if( isset( $modifyInfo['evolve_level'] )  )
				{
					$arrModifyEvolveLevel[$hid] = $modifyInfo['evolve_level'];
					Logger::info('need modify evolve_level. hid:%d, pre:%d, cur:%d', $hid, $modifyInfo['evolve_level'], $heroObj->getEvolveLv());
					//$heroObj->setEvolveLevel($modifyInfo['evolve_level']);
				}
			}
		
			foreach( $this->mArrLostPet as $petId => $infoInDb )
			{
				$this->resetPet($this->mUid, $petId);
			}
			foreach( $this->mArrModifyPet as $petId => $modifyInfo )
			{
				Logger::info('update pet. petId:%d, modify:%s', $petId, $modifyInfo);
				PetDAO::updatePet($petId, $modifyInfo);
			}
			

			
			self::info('reset battle data');
			$userObj->modifyBattleData();
			$userObj->getBattleFormation();
			$userObj->update();
			foreach( $arrModifyEvolveLevel as $hid => $evolveLevel )
			{
				$arrField = array( 'evolve_level' => $evolveLevel );
				HeroDao::update($hid, $arrField);
				Logger::info('modify evolve_level. hid:%d, evolve_level:%d', $hid, $evolveLevel);
			}
			
			if( !empty($arrLostItemId) )
			{
				$arrReward = array(
					RewardType::ARR_ITEM_ID => $arrLostItemId,
					RewardDef::TITLE => '系统恢复',
					RewardDef::MSG => '---',
				);
				EnReward::sendReward($this->mUid, RewardSource::SYSTEM_GENERAL, $arrReward);
			}
			
			$this->delExtraItem();
			ItemManager::getInstance()->update();
			
		}
		
	}
	
	public function delExtraItem()
	{
		if( empty($this->mArrNeedDelItem) )
		{
			Logger::info('no item to del');
			return;
		}
		$bag = BagManager::getInstance()->getBag($this->mUid);
		
		self::info('going to sub item:');
		foreach( $this->mArrNeedDelItem as $itemTplId => $delNum )
		{
			self::info("\titemTpl:%d, itemName:%s, num:%d", $itemTplId, self::getItemName($itemTplId), $delNum);
		}
		printf("delete these item (y|n)\n");
		$ret = trim(fgets(STDIN));
		self::info("choose :%s", $ret);
		if( $ret != 'y' )
		{
			self::info('ignore');
			return;
		}
		foreach( $this->mArrNeedDelItem as $itemTplId => $delNum )
		{
			if( $delNum > 0 )
			{
				$ret = $bag->deleteItembyTemplateID($itemTplId, $delNum);
				if( !$ret )
				{
					self::fatal('delete itemTpl:%d, failed');
				}
			}
		}
		self::info('delete extra item done');
		$bag->update();
	}
	
	public function genChangeMsg($arrAllItemInRecord)
	{

		Logger::debug('mArrLostItemInfo:%s', $this->mArrLostItemInfo);
		Logger::debug('mArrModifyItem:%s', $this->mArrModifyItem);
		Logger::debug('mArrLostUnusedHero:%s', $this->mArrLostUnusedHero);
		Logger::debug('mArrLostHero:%s', $this->mArrLostHero);
		Logger::debug('mArrModifyHero:%s', $this->mArrModifyHero);
		Logger::debug('mArrLostLittleFriend :%s', $this->mArrLostLittleFriend );
		Logger::debug('mArrLostUnusedLittleFriend :%s', $this->mArrLostUnusedLittleFriend);
		Logger::debug('mArrLostAttrFriend :%s', $this->mArrLostAttrFriend );
		Logger::debug('mArrLostUnusedAttrFriend :%s', $this->mArrLostUnusedAttrFriend);
		
		
		$msg = '';
		//对比战报和目前数据的结果
		$arrModifyItem = $this->mArrModifyItem;
		foreach( $this->mArrLostItemInfo as $itemId => $infoInDb )
		{
			$itemTplId = $infoInDb['item_template_id'];
			$itemName = self::getItemName($itemTplId);
			if( isset( $arrModifyItem[$itemId] ) )
			{
				$msg .= sprintf("lose item. itemId:%d, tplId:%d, name:%s\n", $itemId, $itemTplId, $itemName);
				$msg .= $this->getItemChangeInfo($arrAllItemInRecord[$itemId]['va_item_text'], $infoInDb['va_item_text'], $arrModifyItem[$itemId]);
		
				unset($arrModifyItem[$itemId]);
			}
			else
			{
				$msg .= sprintf("lose item. itemId:%d, tplId:%d, name:%s. info:%s\n", $itemId, $itemTplId, $itemName, serialize($infoInDb['va_item_text']) );
			}
		}
		$arrItemObj = ItemManager::getInstance()->getItems( array_keys($arrModifyItem) );
		foreach(  $arrModifyItem as  $itemId => $modifyVa )
		{
			$itemTplId = $arrItemObj[$itemId]->getItemTemplateID();
				
			$msg .= sprintf("change item. itemId:%d, tplId:%d, name:%s\n", $itemId, $itemTplId, self::getItemName($itemTplId) );
			$msg .= $this->getItemChangeInfo($arrAllItemInRecord[$itemId]['va_item_text'], $arrItemObj[$itemId]->getItemText(), $modifyVa);
		}
		
		$msg .= "\n";
		
		$arrModifyHero = $this->mArrModifyHero;
		
		foreach( $this->mArrLostUnusedLittleFriend as $hid => $htid )
		{
			$heroName = self::getHeroName($htid);
			$msg .= sprintf("lose unused little friend. hid:%d, htid:%d, name:%s\n", $hid, $htid, $heroName);
		}
		
		foreach( $this->mArrLostLittleFriend as $hid => $infoInDb )
		{
			$htid = $infoInDb['htid'];
			$heroName = self::getHeroName($htid);
			$msg .= sprintf("lose little friend. hid:%d, htid:%d, name:%s, level:%d, evolve:%d\n", $hid, $htid, $heroName, $infoInDb['level'], $infoInDb['evolve_level']);
		}
		
		
		foreach( $this->mArrLostUnusedAttrFriend as $hid => $infoInRecord )
		{
			$htid = $infoInRecord['htid'];
			$heroName = self::getHeroName($htid);
			$msg .= sprintf("lose unused attr friend. hid:%d, htid:%d, name:%s\n", $hid, $htid, $heroName);
		}
		
		foreach( $this->mArrLostAttrFriend as $hid => $infoInDb )
		{
			$htid = $infoInDb['htid'];
			$heroName = self::getHeroName($htid);
			if( isset( $arrModifyHero[$hid] ) )
			{
				$msg .= sprintf("lose attr hero. hid:%d, htid:%d, name:%s, delTime:%s\n",
						$hid, $htid, $heroName, date('Y-m-d H:i:s', $infoInDb['delete_time'] ));
				$msg .= $this->getHeroChangeInfo($this->mAllHeroInfoInRecord[$hid], $infoInDb, $arrModifyHero[$hid]);
					
				unset($arrModifyHero[$hid]);
			}
			else
			{
				$msg .= sprintf("lose attr hero. hid:%d, htid:%d, name:%s, level:%d, evolve_level:%d, delTime:%s\n",
						$hid, $htid, $heroName, $infoInDb['level'], $infoInDb['evolve_level'],
						date('Y-m-d H:i:s', $infoInDb['delete_time'] ) );
			}
		}
		
		foreach( $this->mArrLostUnusedHero as $hid => $infoInRecord )
		{
			$htid = $infoInRecord['htid'];
			$heroName = self::getHeroName($htid);
			$msg .= sprintf("lose unused hero. hid:%d, htid:%d, name:%s\n", $hid, $htid, $heroName);
		}
		
		foreach( $this->mArrLostHero as $hid => $infoInDb )
		{
			$htid = $infoInDb['htid'];
			$heroName = self::getHeroName($htid);
			if( isset( $arrModifyHero[$hid] ) )
			{
				$msg .= sprintf("lose hero. hid:%d, htid:%d, name:%s, delTime:%s\n", 
						$hid, $htid, $heroName, date('Y-m-d H:i:s', $infoInDb['delete_time'] ));
				$msg .= $this->getHeroChangeInfo($this->mAllHeroInfoInRecord[$hid], $infoInDb, $arrModifyHero[$hid]);
					
				unset($arrModifyHero[$hid]);
			}
			else
			{
				$msg .= sprintf("lose hero. hid:%d, htid:%d, name:%s, level:%d, evolve_level:%d, delTime:%s\n",
						$hid, $htid, $heroName, $infoInDb['level'], $infoInDb['evolve_level'], 
						date('Y-m-d H:i:s', $infoInDb['delete_time'] ) );
			}
		}
		
		foreach( $arrModifyHero as $hid => $modifyInfo )
		{
			$heroObj = EnUser::getUserObj($this->mUid)->getHeroManager()->getHeroObj($hid);
			$htid = $heroObj->getHtid();
			$msg .= sprintf("change hero. hid:%d, htid:%d, name:%s\n", $hid, $htid, self::getHeroName($htid));
			$msg .= $this->getHeroChangeInfo($this->mAllHeroInfoInRecord[$hid], $heroObj->getInfo(), $modifyInfo);
		}
		
		foreach( $this->mArrLostPet as $petId => $infoInDb )
		{
			$petTplId = $infoInDb['pet_tmpl'];
			$petName = self::getPetName($petTplId);
			$msg .= sprintf("lose pet. petId:%d, petTplId:%d, name:%s, delTime:%s\n",
					$petId, $petTplId, $petName, date('Y-m-d H:i:s', $infoInDb['delete_time'] ));
		}
		
		$arrPetId = array_keys($this->mArrModifyPet);
		$arrPetData = self::getArrPetFromDb($arrPetId);
		foreach ($this->mArrModifyPet as $petId => $modifyInfo)
		{
			$infoInRecord = $this->mArrPet[$petId];
			$petTplId = $infoInRecord['pet_tmpl'];
			$petName = self::getPetName($petTplId);
			$msg .= sprintf("change pet. petId:%d, petTplId:%d, name:%s\n", $petId, $petTplId, $petName);
			$msg .= $this->getPetChangeInfo($infoInRecord, $arrPetData[$petId], $modifyInfo);
		}
		return $msg;
		
	}
	
	public function modifyChange($arrStrict = array(), $arrIgnore = NULL)
	{

		foreach( self::$arrDataObj as $name => $conf )
		{
			if ( !isset( $arrStrict[$name] ) )
			{
				$arrStrict[$name] = $conf['defStrictModel'];
			}
		}
		
		$this->info('strict mode:%s', $arrStrict);
		$this->info('ignore:%s', $arrIgnore);
			
		
		if( isset( $arrStrict['hero'] ) && $arrStrict['hero'] ) 
		{
			$arrLostHero = $this->mArrLostLittleFriend + $this->mArrLostHero + $this->mArrLostAttrFriend;
			$arrModifyHero = $this->mArrModifyHero;
			
			$arrHid = array_keys( $arrModifyHero + $arrLostHero );
			$arrHeroData = self::getArrHeroFromDb($arrHid);

			foreach( $arrModifyHero as $hid => $modifyInfo )
			{
				$infoInDb = $arrHeroData[$hid];
				$htid = $infoInDb['htid'];
				
				if ( isset($this->mArrOrangeHid[$hid]) )
				{
					self::info('modify for restore of orange hero. hid:%d, htid:%d, name:%s', $hid, $htid, self::getHeroName($htid));
					continue;
				}
				
				//只是有属性变化的武将不做处理
				if( !isset( $arrLostHero[$hid]  ) )
				{
					unset($this->mArrModifyHero[$hid]);
					Logger::info('ignore modify. hid:%d, htid:%d, name:%s', $hid, $htid, self::getHeroName($htid));
					continue;
				}
				
				if( $infoInDb['evolve_level'] == 0  )
				{
					unset( $this->mArrModifyHero[$hid]['evolve_level'] );
				}
				else 
				{
					//理论上不应该走到这里。现在的需要先重生才能分解
					self::warn('evolve_level should be 0. hid:%d, htid:%d, name:%s evolve_level:%d', 
							$hid, $htid, self::getHeroName($htid), $infoInDb['evolve_level']);
					$this->mArrModifyHero[$hid]['evolve_level'] = 0;
				}
			}
			foreach( $arrLostHero as $hid => $heroInfoInDb )
			{
				$htid = $heroInfoInDb['htid'];
				
				if ( isset($this->mArrOrangeHid[$hid]) )
				{
					Logger::info('reset for restore of orange hero. hid:%d, htid:%d, name:%s', $hid, $htid, self::getHeroName($htid));
					continue;
				}
				
				if( isset( $arrModifyHero[$hid]  ) )
				{
					Logger::debug('ignore hid:%d', $hid);
					continue;
				}
				if( $heroInfoInDb['evolve_level'] > 0 )
				{
					self::warn('evolve_level should be 0. hid:%d, evolve_level:%d', $hid, $heroInfoInDb['evolve_level']);
					$this->mArrModifyHero[$hid] = array(
						'evolve_level' => 0
					);
				}
			}
		}
		
		if( isset( $arrStrict['pet'] ) && $arrStrict['pet'] )
		{
			$arrLostPet = $this->mArrLostPet;
			$arrModifyPet = $this->mArrModifyPet;
			
			foreach($arrLostPet as $petId => $petInfoInDb)
			{
				$petTplId = $petInfoInDb['pet_tmpl'];
				if(  isset($arrModifyPet[$petId])  )
				{
					Logger::debug('ignore petId:%d', $petId);
					continue;
				}
				$petConf = btstore_get()->PET[$petTplId]->toArray();
				$initSkillSlot = $petConf['initSkillSlot'];
				$initSkillPos = array();
				for( $i = 0; $i < $initSkillSlot; $i++  )
				{
					$initSkillPos[] = array(
							'id' => 0,'level' =>0, 'status' => PetDef::SKILL_UNLOCK,
					);
				}
				$vaPet = $petInfoInDb['va_pet'];
				$vaPet['skillNormal'] = $initSkillPos;
				$this->mArrModifyPet[$petId] = array(
					'skill_point' => $petConf['initSkillPoint'],
					'level' => 1,
					'exp' => 0,
					'swallow' => 0, 
					'va_pet' => $vaPet,
				);
			}
			
		}
		
		$arrModifyItem = $this->mArrModifyItem;
		$arrLostItem = $this->mArrLostItemInfo;
		$arrItemId = array_keys($arrModifyItem + $arrLostItem);
		$arrItemData = self::getArrItemFromDb($arrItemId);
		

		foreach(  $arrItemId as $itemId )
		{
			$infoInDb = $arrItemData[$itemId];
			$itemTplId = $infoInDb['item_template_id'];
			$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			
			if ( !isset( self::$mapItemTypeInt2Str[$itemType] ) )
			{
				$this->fatal('invalid itemType:%d', $itemType);
				continue;
			}
			$itemTypeStr = self::$mapItemTypeInt2Str[$itemType];
			
			//处理ignore的情况
			if ( isset( $arrIgnore[$itemTypeStr] ) && $arrIgnore[$itemTypeStr] )
			{
				if ( isset($this->mArrModifyItem[$itemId]) )
				{
					unset($this->mArrModifyItem[$itemId]);
					self::info('ignore modify item. itemId:%d, itemTplId:%d, itemName:%s', $itemId,$itemTplId,self::getItemName($itemTplId));
				}
				if ( isset($this->mArrLostItemInfo[$itemId]) )
				{
					unset($this->mArrLostItemInfo[$itemId]);
					self::info('ignore lost item. itemId:%d, itemTplId:%d, itemName:%s', $itemId,$itemTplId,self::getItemName($itemTplId));
				}
				continue;
			}
			
			
			//非严格模式不用处理
			if ( !isset( $arrStrict[$itemTypeStr] ) ||  $arrStrict[$itemTypeStr] == false )
			{
				Logger::info('itemType:%s not in strict model, ignore. itemId:%d', $itemTypeStr, $itemId);
				continue;
			}
			
			//只是有属性变化的不做处理
			if( !isset( $arrLostItem[$itemId] ) && isset( $arrModifyItem[$itemId] )  )
			{
				unset($this->mArrModifyItem[$itemId]);
				Logger::info('ignore modify. itemId:%d, itemTpl:%d, name:%s',
						$itemId, $itemTplId, self::getItemName($itemTplId));
				continue;
			}

			if( isset( $arrModifyItem[$itemId]) )
			{
				$vaInfo = $arrModifyItem[$itemId];
			}
			else 
			{
				$vaInfo = $arrLostItem[$itemId]['va_item_text'];
			}
			
			$vaBefore = $vaInfo;
					
			
			if ( !isset( self::$arrDataObj[$itemTypeStr]['strictFunc'] ) )
			{
				self::fatal('invalid itemType:$s, itemId:%d, itemTplId:%d, name:%s', $itemTypeStr, $itemId, $itemTplId, self::getItemName($itemTplId) );
				return;
			}
			$vaInfo = call_user_func_array ( array ($this, self::$arrDataObj[$itemTypeStr]['strictFunc'] ), 
						array($itemId, $itemTplId, $vaInfo ) );
			
			if( $vaInfo != $vaBefore )
			{
				$this->mArrModifyItem[$itemId] = $vaInfo;
			}
		}
	}
	
	public function strictArm($itemId, $itemTplId, $vaInfo)
	{
		if( !empty( $vaInfo['armPotence'] ) )
		{
			unset($vaInfo['armPotence']);
			Logger::info('reset armpotence. itemId:%d', $itemId);
		}
		return $vaInfo;
	}
	public function strictTreasure($itemId, $itemTplId, $vaInfo)
	{
		$vaInfo['treasureLevel'] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL;
		$vaInfo['treasureExp'] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EXP;
		$vaInfo['treasureEvolve'] = 0;
		$vaInfo['treasureDevelop'] = -1;
		Logger::info('reset treasure. itemId:%d', $itemId);
		return $vaInfo;
	}
	public function strictDress($itemId, $itemTplId, $vaInfo)
	{
		$vaInfo['dressLevel'] = 0;
		return $vaInfo;
	}
	public function strictGodWeapon($itemId, $itemTplId, $vaInfo)
	{
		$vaInfo['reinForceLevel'] = GodWeaponDef::INIT_REINFORCE_LEVEL;
		$vaInfo['reinForceExp'] = 0;
		$vaInfo['reinForceCost'] = 0;
		$initEvolveNum = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM);
		if(!empty($initEvolveNum))
		{
			$vaInfo['evolveNum'] = $initEvolveNum;
		}
		else
		{
			$vaInfo['evolveNum'] = GodWeaponDef::INIT_EVOLVE_NUM;
		}
		if( !empty( $vaInfo['confirmed'] ) )
		{
			unset($vaInfo['confirmed']);
			Logger::info('reset confirmed. itemId:%d', $itemId);
		}
		return $vaInfo;
	}
	public function strictFightsoul($itemId, $itemTplId, $vaInfo)
	{
		$vaInfo['fsLevel'] = FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_LEVEL;
		$vaInfo['fsExp'] = FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_LEVEL;
		return $vaInfo;
	}
	public function strictSkillbook($itemId, $itemTplId, $vaInfo)
	{
		self::fatal('no skill book, cant strict');
		return $vaInfo;
	}
	
	public function strictRune($itemId, $itemTplId, $vaInfo)
	{
		//nothing to do 
		return $vaInfo;
	}
	
	public function strictPocket($itemId, $itemTplId, $vaInfo)
	{
		$vaInfo['pocketLevel'] = PocketDef::ITEM_ATTR_NAME_POCKET_INIT_LEVEL;
		$vaInfo['pocketExp'] = PocketDef::ITEM_ATTR_NAME_POCKET_INIT_EXP;
		return $vaInfo;
	}

	
	public function strictTally($itemId, $itemTplId, $vaInfo)
	{
		$vaInfo['tallyLevel'] = ItemDef::ITEM_ATTR_NAME_TALLY_INIT_LEVEL;
		$vaInfo['tallyExp'] = ItemDef::ITEM_ATTR_NAME_TALLY_INIT_EXP;
		$vaInfo['tallyEvolve'] = ItemDef::ITEM_ATTR_NAME_TALLY_INIT_EVOLVE;
		$vaInfo['tallyDevelop'] = ItemDef::ITEM_ATTR_NAME_TALLY_INIT_DEVELOP;
		
		return $vaInfo;
	}
	

	public function getGainByHero()
	{
		self::info("\ncaculate gain by hero");
		$allHero = $this->mArrLostAttrFriend + $this->mArrLostLittleFriend + $this->mArrLostHero + $this->mArrModifyHero;
		if( empty ($allHero ) )
		{
			self::info("no gain by hero\n");
			return;
		}
		$arrHid = array_keys( $allHero );
		
		$data = new CData();
		$arrRet = $data->select(HeroDef::$HERO_FIELDS)->from(HeroDao::TBL_HERO)
						->where('hid', 'IN', $arrHid)->query();
		
		$arrHeroData = Util::arrayIndex($arrRet, 'hid');
		
		$arrHeroInfo = array();
		
		$arrLostHero = array_merge( $this->mArrLostLittleFriend,  $this->mArrLostHero, $this->mArrLostAttrFriend);
		$arrLostHero = Util::arrayIndex($arrLostHero, 'hid');
		foreach( $arrLostHero as $hid => $heroInfo)
		{
			$arrHeroInfo[$hid] = array(
					'hid' => $hid,
					'htid' => $heroInfo['htid'],
					'preSoul' => 0,
					'preEvolve' => 0,
					'curSoul' => $arrHeroData[$hid]['soul'],
					'curEvolve' => $arrHeroData[$hid]['evolve_level'],
			);
		}
		
		foreach( $this->mArrModifyHero as $hid => $heroInfo)
		{
			if(  !isset($heroInfo['level'])  )
			{
				$heroInfo['level'] = $arrHeroData[$hid]['level'];
			}
			if( $heroInfo['level'] < $arrHeroData[$hid]['level'] )
			{
				self::fatal('invalid modify level. hid:%d, infoInDb:%s, infoModify', $hid, $arrHeroData[$hid], $heroInfo);
				continue;
			}
			if(  !isset($heroInfo['evolve_level'])  )
			{
				$heroInfo['evolve_level'] = $arrHeroData[$hid]['evolve_level'];
			}
			if( $heroInfo['evolve_level'] < $arrHeroData[$hid]['evolve_level'] )
			{
				self::fatal('invalid modify evolve_level. hid:%d, infoInDb:%s, infoModify', $hid, $arrHeroData[$hid], $heroInfo);
				continue;
			}
				
			$expTblId = Creature::getCreatureConf( $arrHeroData[$hid]['htid'], CreatureAttr::EXP_ID);
			$expTbl	= btstore_get()->EXP_TBL[$expTblId];
			if( isset( $arrHeroInfo[$hid] ) )
			{
				$arrHeroInfo[$hid] = array(
						'hid' => $hid,
						'htid' => $arrHeroInfo[$hid]['htid'],
						'preSoul' => 0,
						'preEvolve' => 0,
						'curSoul' => $expTbl[ $heroInfo['level'] ],
						'curEvolve' => $heroInfo['evolve_level'],
				);
			}
			else
			{
				if( $expTbl[ $heroInfo['level'] ] <   $arrHeroData[$hid]['soul'] )
				{
					self::fatal('invalid soul');
					continue;
				}
				$arrHeroInfo[$hid] = array(
						'hid' => $hid,
						'htid' => $arrHeroData[$hid]['htid'],
						'preSoul' => $arrHeroData[$hid]['soul'],
						'preEvolve' => $arrHeroData[$hid]['evolve_level'],
						'curSoul' => $expTbl[ $heroInfo['level'] ],
						'curEvolve' => $heroInfo['evolve_level'],
				);
			}
		}		

		$resolverConf = btstore_get()->RESOLVER[ResolverDef::RESOLVER_TYPE_ONLY_HERO];
		$arrGainByHero = array();
		foreach( $arrHeroInfo as $hid => $heroModifyInfo )
		{
			$htid = $arrHeroData[$hid]['htid'];
			$gain = array(
				'soul' => 0,
				'silver' => 0,
				'jewel' => 0,
				'arrItem' => array(),
				'arrHero' => array(),
			);
			$soul = 0;
			if( isset( $arrLostHero[$hid] ) )
			{
				$soul = Creature::getCreatureConf(  $htid, CreatureAttr::SOUL);
			}
			
			if( $heroModifyInfo['curSoul'] > $heroModifyInfo['preSoul']  ) 
			{
				$soul += $heroModifyInfo['curSoul'] - $heroModifyInfo['preSoul'];
			}
			
			if( $soul > 0 )
			{
				$gain['soul'] = intval( $soul  * ($resolverConf['soul_ratio'] / UNIT_BASE) );
				$gain['silver'] = intval( Creature::getCreatureConf($htid, CreatureAttr::LVLUP_RATIIO)/100 * $soul );
				$gain['silver'] = intval( $gain['silver'] * ( $resolverConf['silver_ratio']/UNIT_BASE));
				if(  isset( $arrLostHero[$hid] ) )
				{
					$gain['jewel'] = Creature::getCreatureConf($htid, CreatureAttr::JEWEL_NUM);
				}
			}
			
			if( $heroModifyInfo['curEvolve'] > $heroModifyInfo['preEvolve']  )
			{
		
				for( $i = $heroModifyInfo['preEvolve']; $i < $heroModifyInfo['curEvolve']; $i++)
				{
					$evlTblId = HeroLogic::getEvolveTbl( $htid, $i);
					$evlTblConf = btstore_get()->HERO_CONVERT[$evlTblId];
					foreach($evlTblConf['arrNeedItem'] as $itemTplId => $itemNum)
					{
						if(!isset($gain['arrItem'][$itemTplId]))
						{
							$gain['arrItem'][$itemTplId] = 0;
						}
						$gain['arrItem'][$itemTplId] += $itemNum;
					}
					foreach($evlTblConf['arrNeedHero'] as $index => $heroConf)
					{
						$heroTplId = $heroConf[0];
						$heroLevel = $heroConf[1];
						$heroNum = $heroConf[2];
						if($heroLevel != 1)
						{
							//目前都只是消耗1级的武将，且策划说不会改
							self::fatal('not support need hero level:%d != 1', $heroLevel);
						}
						
						if(!isset($gain['arrHero'][$heroTplId]))
						{
							$gain['arrHero'][$heroTplId] = 0;
						}
						$gain['arrHero'][$heroTplId] += $heroLevel;
					}
		
				}
			}
			$arrGainByHero[$hid] = $gain;
		}
		
		$allGain = array(
				'soul' => 0,
				'silver' => 0,
				'jewel' => 0,
				'arrItem' => array(),
				'arrHero' => array(),
		);
		foreach( $arrGainByHero as $hid => $gain )
		{
			$htid = $arrHeroData[$hid]['htid'];
	
			$msg = sprintf("hid:%d, htid:%d, name:%s\n", $hid, $htid, self::getHeroName($htid) );
			if( $gain['soul'] > 0  )
			{
				$msg .= sprintf("\tsoul:%d, silver:%d, jewel:%d\n", $gain['soul'], $gain['silver'], $gain['jewel']);
				
				$allGain['soul'] += $gain['soul'];
				$allGain['silver'] += $gain['silver'];
				$allGain['jewel'] += $gain['jewel'];
			}

			foreach( $gain['arrItem'] as $itemTplId => $num )
			{
				$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $itemTplId, self::getItemName($itemTplId), $num);
				if( !isset($allGain['arrItem'][$itemTplId]) )
				{
					$allGain['arrItem'][$itemTplId] = 0;
				}
				$allGain['arrItem'][$itemTplId] += $num;
			}
			
			foreach( $gain['arrHero'] as $heroTplId => $num )
			{
				$msg .= sprintf("\thtid:%d, heroName:%s, num:%d\n", $heroTplId, self::getHeroName($heroTplId), $num);
				if( !isset($allGain['arrHero'][$heroTplId]) )
				{
					$allGain['arrHero'][$heroTplId] = 0;
				}
				$allGain['arrHero'][$heroTplId] += $num;
			}
			//self::info("%s", $msg);
			Logger::info('%s', $msg);
		}
		
		$msg = sprintf("\nall gain by hero\n" );
		$msg .= sprintf("\tsoul:%d, silver:%d, jewel:%d\n", $allGain['soul'], $allGain['silver'], $allGain['jewel']);
	
		foreach( $allGain['arrItem'] as $itemTplId => $num )
		{
			$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $itemTplId, self::getItemName($itemTplId), $num);
		}
			
		foreach( $allGain['arrHero'] as $heroTplId => $num )
		{
			$msg .= sprintf("\thtid:%d, heroName:%s, num:%d\n", $heroTplId, self::getHeroName($heroTplId), $num);
		}
		self::info("%s\n", $msg);		
		
	}
	
	
	public function getGainByItem()
	{
		self::info("\ncaculate gain by item");
		/**
			装备只处理洗练石
			宝物处理：宝物精华，宝物经验
			时装处理：时装精华
		 */
	
		$arrItemInfo = array(
		);
		//收集提供了计算收益函数的物品信息
		foreach( self::$arrDataObj as $name => $conf )
		{
			if( isset($conf['gainFunc']) )
			{
				$arrItemInfo[$name] = array();
			}
		}
		
		foreach( $this->mArrLostItemInfo as $itemId => $infoInDb )
		{
			$itemTplId = $infoInDb['item_template_id'];
			$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			if( !isset( self::$mapItemTypeInt2Str[$itemType] ) || !isset( $arrItemInfo[ self::$mapItemTypeInt2Str[$itemType] ] )   )
			{
				Logger::info('ignore itemId:%d, itemTplId:%d, itemType:%d', $itemId, $itemTplId, $itemType);
				continue;
			}
			
			$itemTypeStr = self::$mapItemTypeInt2Str[$itemType];
			
			$arrItemInfo[$itemTypeStr][$itemId] = array(
				'itemTplId' => $itemTplId,
				'pre' => array(),
				'cur' => $infoInDb['va_item_text'],
			);
		}
		
		foreach( $this->mArrModifyItem as $itemId => $modifyVa )
		{
			if( isset(  $this->mArrLostItemInfo[$itemId]  ) )
			{
				Logger::debug('itemId:%d lost and modify:%s', $itemId, $modifyVa);
				$itemTplId = $this->mArrLostItemInfo[$itemId]['item_template_id'];
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				if( !isset( self::$mapItemTypeInt2Str[$itemType] ) || !isset( $arrItemInfo[ self::$mapItemTypeInt2Str[$itemType] ] ) )
				{
					Logger::info('ignore itemId:%d, itemTplId:%d, itemType:%d', $itemId, $itemTplId, $itemType);
					continue;
				}
				$itemTypeStr = self::$mapItemTypeInt2Str[$itemType];
				$arrItemInfo[$itemTypeStr][$itemId] = array(
						'itemTplId' => $itemTplId,
						'pre' => array(),
						'cur' => $modifyVa,
				);
			}
			else 
			{
				$itemData = self::getItemFromDb($itemId);
				$itemTplId = $itemData['item_template_id'];
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				
				if( !isset( self::$mapItemTypeInt2Str[$itemType] ) || !isset( $arrItemInfo[ self::$mapItemTypeInt2Str[$itemType] ] ) )
				{
					Logger::info('ignore itemId:%d, itemTplId:%d, itemType:%d', $itemId, $itemTplId, $itemType);
					continue;
				}
				
				$itemTypeStr = self::$mapItemTypeInt2Str[$itemType];
				
				$arrItemInfo[$itemTypeStr][$itemId] = array(
						'itemTplId' => $itemTplId,
						'pre' => $itemData['va_item_text'],
						'cur' => $modifyVa,
				);
			}
		}

		$allGain = array(
			'treasureExp' => 0,
			'godweaponExp' => 0,
			'tg' => 0,
			'tally_point' => 0,
			'jewel' => 0,
			'silver' =>0,
			'arrItem' => array(),
		);
		
		foreach( $arrItemInfo as $name => $value )
		{
			if ( !isset( self::$arrDataObj[$name]['gainFunc'] ) )
			{
				self::warn('not found gainFunc for %s, %s', $name, $value);
				continue;
			}
			$arrGain = call_user_func_array ( array ($this, self::$arrDataObj[$name]['gainFunc'] ), 
						array( $value ) );
			foreach( $arrGain as $key => $gain )
			{
				if( $key == 'arrItem')
				{
					foreach( $gain as $id => $n)
					{
						if( !isset($allGain['arrItem'][$id]) )
						{
							$allGain['arrItem'][$id] = 0;
						}
						$allGain['arrItem'][$id] += $n;
					}
				}
				else
				{
					if( !isset( $allGain[$key] ) )
					{
						self::fatal('cant deal gain:%s', $key);
						return;
					}
					$allGain[$key] += $gain;
				}
			}
		}
		
		$msg = sprintf("all gain by item\n" );
		$msg .= sprintf("\ttreasureExp:%d\n", $allGain['treasureExp']);
		$msg .= sprintf("\tgodweaponExp:%d\n", $allGain['godweaponExp']);
		$msg .= sprintf("\ttg:%d\n", $allGain['tg']);
		$msg .= sprintf("\tjewel:%d\n", $allGain['jewel']);
		$msg .= sprintf("\ttally_point:%d\n", $allGain['tally_point']);
		
		foreach( $allGain['arrItem'] as $itemTplId => $num )
		{
			$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $itemTplId, self::getItemName($itemTplId), $num);
		}
		$msg .= sprintf("\nresolve gain\n" );
		$allAvgNum = 0;
		$arrResolveGetItem = array();
		foreach( $this->mArrLostItemInfo as $itemId => $infoInDb )
		{
			$itemTplId = $infoInDb['item_template_id'];
			$itemType = ItemManager::getInstance()->getItemType($itemTplId);
			$itemTypeStr = self::$mapItemTypeInt2Str[$itemType];
			if($itemTypeStr == 'arming' 
					&& ItemManager::getInstance()->getItemQuality($itemTplId) >= ItemDef::ITEM_QUALITY_PURPLE )
			{
				$resolveId = btstore_get()->ITEMS[$itemTplId]['armExchangeId'];
				$arrRet = self::getResolveValues($resolveId);
				if(empty($arrRet))
				{
					continue;
				}
				//$msg .= sprintf("\titemTpl:%d, itemName:%s\n", $itemTplId, self::getItemName($itemTplId));
				//$msg .= sprintf("\t\tminValue:%d, maxValue:%d, avgNum:%d\n", 
				//			$arrRet['minValue'], $arrRet['maxValue'], $arrRet['avgNum']);
				foreach($arrRet['arrDropInfo'] as $dropInfo)
				{
					$id = $dropInfo['itemTplId'];
					//$msg .= sprintf("\t\titemTplId:%d, name:%s, value:%d\n", $id, self::getItemName($id), $dropInfo['value']);
					
					if( !isset( $arrResolveGetItem[$id] ) )
					{
						$arrResolveGetItem[$id] = $dropInfo;
					}
				}
				
				$allAvgNum += $arrRet['avgNum'];
			}
		}
		$msg .= sprintf("allAvgNum:%d\n", $allAvgNum);
		if($allAvgNum > 0)
		{
			$bagMgr = BagManager::getInstance()->getBag($this->mUid);
			$arrCurNum = array();
			$allCurNum = 0;
			foreach($arrResolveGetItem as $itemTplId => $info)
			{
				$curNum = $bagMgr->getItemNumByTemplateID($itemTplId);
				$allCurNum += $curNum;
				$arrCurNum[$itemTplId] = $curNum;
			}
			foreach($arrResolveGetItem as $itemTplId => $info)
			{
				$curNum = $arrCurNum[$itemTplId];
				$delNum = round($curNum/$allCurNum*$allAvgNum);
				$subNum = $delNum > $curNum ? $curNum : $delNum;
				if( !isset($this->mArrNeedDelItem[$itemTplId]) )
				{
					$this->mArrNeedDelItem[$itemTplId] = 0;
				}
				$this->mArrNeedDelItem[$itemTplId] += $subNum;
				$msg .= sprintf("\tname:%s, value:%d, curNum:%d, itemTplId:%d %d\n",
						self::getItemName($itemTplId), $info['value'], $curNum, $itemTplId, $delNum);
			
			}
		}
			
		self::info("%s\n", $msg);
							
	}
	
	public function getGainOfArm( $arrItemInfo )
	{
		$arrGain = array(
				'arrItem' => array(),
		);
		foreach( $arrItemInfo as $itemId => $itemModifyInfo )
		{
			$itemTplId = $itemModifyInfo['itemTplId'];
			$pre = $itemModifyInfo['pre'];
			$cur = $itemModifyInfo['cur'];
		
			if ( empty( $cur['armPotence'] ) )
			{
				Logger::debug('no armPotence');
				continue;
			}
		
			$arrItem = self::getArmPotenceResolve($itemTplId, $cur['armPotence']);
			$arrItemPre = self::getArmPotenceResolve($itemTplId, empty($pre['armPotence']) ? array() : $pre['armPotence']  );
		
			foreach($arrItemPre as $tplId => $num)
			{
				if( !isset( $arrItem[$tplId] ) || $arrItem[$tplId]  < $num )
				{
					self::fatal('cant deal this problem. itemId:%d, itemTpl:%d, itemName:%s',
							$itemId, $itemTplId, self::getItemName($itemTplId));
					continue;
				}
				$arrItem[$tplId] -= $num;
				if( $arrItem[$tplId] <= 0 )
				{
					unset($arrItem[$tplId]);
				}
			}
		
			if( !empty($arrItem) )
			{
				foreach( $arrItem as $tplId => $num )
				{
					$msg = sprintf("itemId:%d, itemTplId:%d, itemName:%s,\n", $itemId, $itemTplId, self::getItemName($itemTplId) );
					$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $tplId, self::getItemName($tplId), $num);
		
					if( !isset( $arrGain['arrItem'][$tplId] ) )
					{
						$arrGain['arrItem'][$tplId] = 0;
					}
					$arrGain['arrItem'][$tplId] += $num;
	
					Logger::info('%s', $msg);
				}
			}
		}
		
		return $arrGain;
	}
	public function getGainOfTreasure( $arrItemInfo )
	{
		$arrGain = array(
				'treasureExp' => 0,
				'arrItem' => array(),
		);
		foreach( $arrItemInfo as $itemId => $itemModifyInfo )
		{
			$itemTplId = $itemModifyInfo['itemTplId'];
			$pre = $itemModifyInfo['pre'];
			$cur = $itemModifyInfo['cur'];
		
			$exp = 0;
			if( !isset($pre['treasureExp']) )
			{
				$pre['treasureExp'] = 0;
			}
			if( $cur['treasureExp'] > $pre['treasureExp'] )
			{
				$exp = $cur['treasureExp'] - $pre['treasureExp'];
			}
		
			if( !isset(  $cur['treasureEvolve'] ) )
			{
				$cur['treasureEvolve'] = 0;
			}
			if( !isset(  $pre['treasureEvolve'] ) )
			{
				$pre['treasureEvolve'] = 0;
			}
			
			$arrItem = array();
			if( isset( $this->mArrLostItemInfo[$itemId] ) )
			{
				$arrItem = ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_RESOLVE)->toArray();
				$exp += ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_BASE);
			}
			
			if( $cur['treasureEvolve'] > $pre['treasureEvolve'] )
			{
				for ($i = $pre['treasureEvolve']; $i < $cur['treasureEvolve']; $i++)
				{
					$expend = ItemAttr::getItemAttr( $itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND);
					$conf = $expend[$i + 1]->toArray();
					if (!empty($conf['item']))
					{
						foreach ($conf['item'] as $tplId => $num)
						{
							if (!isset($arrItem[$tplId]))
							{
								$arrItem[$tplId] = 0;
							}
							$arrItem[$tplId] += $num;
						}
					}
				}
			}
			if( $exp > 0 || !empty($arrItem) )
			{
				$msg = sprintf("itemId:%d, itemTplId:%d, itemName:%s\n", $itemId, $itemTplId, self::getItemName($itemTplId) );
				$msg .= sprintf("\texp:%d\n", $exp);
				foreach($arrItem as $tplId => $num)
				{
					$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $tplId, self::getItemName($tplId), $num);
					if (!isset($arrGain['arrItem'][$tplId]))
					{
						$arrGain['arrItem'][$tplId] = 0;
                    }
					$arrGain['arrItem'][$tplId] += $num;
				}
				$arrGain['treasureExp'] += $exp;
		
				Logger::info('%s', $msg);
			}
		}
		
		return $arrGain;
	}
	public function getGainOfDress( $arrItemInfo )
	{
		$arrGain = array(
				'arrItem' => array(),
		);

		foreach( $arrItemInfo as $itemId => $itemModifyInfo )
		{
			$itemTplId = $itemModifyInfo['itemTplId'];
			$pre = $itemModifyInfo['pre'];
			$cur = $itemModifyInfo['cur'];
		
			if( !isset(  $cur['dressLevel']  ) )
			{
				$cur['dressLevel'] = 0;
			}
			if( !isset( $pre['dressLevel'] ) )
			{
				$pre['dressLevel'] = 0;
			}
		
			$arrCost = ItemAttr::getItemAttr( $itemTplId, ItemDef::ITEM_ATTR_NAME_DRESS_COST);
			$arrItem = array();
			for ($i = $cur['dressLevel']; $i > $pre['dressLevel']; $i--)
			{
				$cost = $arrCost[$i];
				foreach ($cost['item'] as $tplId => $num)
				{
					if (!isset($arrItem[$tplId]))
					{
						$arrItem[$tplId] = 0;
					}
					$arrItem[$tplId] += $num;
				}
			}
			$resolve = ItemAttr::getItemAttr( $itemTplId, ItemDef::ITEM_ATTR_NAME_DRESS_RESOLVE);
			foreach ($resolve as $tplId => $num)
			{
				if (!isset($arrItem[$tplId]))
				{
					$arrItem[$tplId] = 0;
				}
				$arrItem[$tplId] += $num;
			}
		
			$msg = sprintf("itemId:%d, itemTplId:%d, itemName:%s, preLevel:%d, curLevel:%d\n",
			$itemId, $itemTplId, self::getItemName($itemTplId),  $pre['dressLevel'], $cur['dressLevel'] );
    		foreach($arrItem as $tplId => $num)
			{
				$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $tplId, self::getItemName($tplId), $num);
				if (!isset($arrGain['arrItem'][$tplId]))
				{
					$arrGain['arrItem'][$tplId] = 0;
        		}
				$arrGain['arrItem'][$tplId] += $num;
			}
		
    		Logger::info('%s', $msg);
		}
		
		return $arrGain;
	}
	public function getGainOfGodWeapon( $arrItemInfo )
	{
		$arrGain = array(
				'godweaponExp' => 0,
				'arrItem' => array(),
		);


		foreach( $arrItemInfo as $itemId => $itemModifyInfo )
		{
			$itemTplId = $itemModifyInfo['itemTplId'];
			$pre = $itemModifyInfo['pre'];
			$cur = $itemModifyInfo['cur'];
		
			$exp = 0;
			if( !isset($pre['reinForceExp']) )
			{
				$pre['reinForceExp'] = 0;
			}
			if( $cur['reinForceExp'] > $pre['reinForceExp'] )
			{
				$exp = $cur['reinForceExp'] - $pre['reinForceExp'];
			}
		
			if( !isset(  $cur['evolveNum'] ) )
			{
				$cur['evolveNum'] = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM);
			}
			if( !isset(  $pre['evolveNum'] ) )
			{
				$pre['evolveNum'] = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM);
			}
		
			$arrItem = array();
			if( $cur['evolveNum'] > $pre['evolveNum'] )
			{
				$arrEvolveId = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID);
		
				for ($i = $pre['evolveNum']; $i < $cur['evolveNum']; $i++)
				{
					if(!isset($arrEvolveId[$i]))
					{
						self::fatal('not found evolveId for evolveNum:%d, itemTplId:%d', $i, $itemTplId);
                		return;
					}
					$evolveId = $arrEvolveId[$i];
					$transferInfo = btstore_get()->GOD_WEAPON_TRANSFER[$evolveId];
		
					if(isset($transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY]))
					{
						$arrNeedGodAmy = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY];
						foreach($arrNeedGodAmy as $needGodAmy)
						{
							$id = $needGodAmy[0];
							if ( isset( $arrItem[$id]  ) )
							{
								$arrItem[$id] += 1;
							}
							else
							{
								$arrItem[$id] = 1;
							}
						}
					}
		
					if(isset($transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID]))
					{
						$resolveItem = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID];
						foreach($resolveItem as $id => $num)
						{
							if ( isset( $arrItem[$id]  ) )
							{
								$arrItem[$id] += $num;
							}
							else
							{
								$arrItem[$id] = $num;
							}
						}
					}
				}
			}
		
			//神兵的分解获益不好计算，直接打印一个神兵吧
			if ( isset($this->mArrLostItemInfo[$itemId]) )
			{
				if( isset($arrItem[$itemTplId]) )
				{
					$arrItem[$itemTplId] += 1;
				}
				else
				{
					$arrItem[$itemTplId] = 1;
				}
			}
		
			if( $exp > 0 || !empty($arrItem) )
			{
				$msg = sprintf("itemId:%d, itemTplId:%d, itemName:%s\n", $itemId, $itemTplId, self::getItemName($itemTplId) );
				$msg .= sprintf("\texp:%d\n", $exp);
				foreach($arrItem as $tplId => $num)
				{
					$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $tplId, self::getItemName($tplId), $num);
					if (!isset($arrGain['arrItem'][$tplId]))
					{
						$arrGain['arrItem'][$tplId] = 0;
					}
					$arrGain['arrItem'][$tplId] += $num;
				}
				$arrGain['godweaponExp'] += $exp;

				Logger::info('%s', $msg);
			}
		}
		
		return $arrGain;
	}
	public function getGainOfRune( $arrItemInfo )
	{
		$arrGain = array(
				'tg' => 0,
		);
		foreach( $arrItemInfo as $itemId => $itemModifyInfo )
		{
			if ( isset( $this->mArrLostItemInfo[$itemId] ) )
			{
				$itemTplId = $itemModifyInfo['itemTplId'];
				$arrGain['tg'] += ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_RUNE_RESOLVE);
			}
		}
		return $arrGain;
	}
	
	public function getGainOfTally( $arrItemInfo )
	{
		$arrGain = array(
				'tally_point' => 0,
				'jewel' => 0,
				'silver' =>0,
				'arrItem' => array(),
		);
	
		foreach( $arrItemInfo as $itemId => $itemModifyInfo )
		{
			$itemTplId = $itemModifyInfo['itemTplId'];
			$pre = $itemModifyInfo['pre'];
			$cur = $itemModifyInfo['cur'];
			
			$gain = array(
					'tally_point' => 0,
					'jewel' => 0,
					'silver' =>0,
					'arrItem' => array(),
			);
			if( isset($this->mArrLostItemInfo[$itemId]) )
			{
				$curValue = self::getValueOfTally($itemId, $itemTplId, $cur, true);
				$gain = self::array_add($gain, $curValue);
			}
			else
			{
				$preValue = self::getValueOfTally($itemId, $itemTplId, $pre, false);
				$curValue = self::getValueOfTally($itemId, $itemTplId, $cur, false);
				$gain = self::array_add($gain, self::array_sub($curValue, $preValue));
			}
			
			if( isset( $gain['item']) )
			{
				$gain['arrItem'] = $gain['item'];
				unset($gain['item']);
			}
			
			if( empty($gain) )
			{
				$msg = sprintf("itemId:%d, itemTplId:%d, itemName:%s no gain!!!\n", $itemId, $itemTplId, self::getItemName($itemTplId) );
			}
			else
			{
				$msg = sprintf("itemId:%d, itemTplId:%d, itemName:%s, pre:%s, cur:%s\n", 
						$itemId, $itemTplId, self::getItemName($itemTplId), var_export($pre,true), var_export($cur,true) );
				$msg .= sprintf("\ttally_point:%d\n", $gain['tally_point']);
				$msg .= sprintf("\tjewel:%d\n", $gain['jewel']);
				$msg .= sprintf("\tsilver:%d\n", $gain['silver']);
				foreach($gain['arrItem'] as $tplId => $num)
				{
					$msg .= sprintf("\titemTpl:%d, itemName:%s, num:%d\n", $tplId, self::getItemName($tplId), $num);
				}
				$arrGain = self::array_add($arrGain, $gain);
			}
			Logger::info('%s', $msg);
		}
		
		return $arrGain;				
	}
	
	public static function getValueOfTally($itemId, $itemTplId, $vaInfo, $isLost)
	{
		$values = array();
		$values[ItemDef::ITEM_SQL_ITEM_ID] = $itemId;
		$values[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID] = $itemTplId;
		$values[ItemDef::ITEM_SQL_ITEM_NUM] = 1;
		$values[ItemDef::ITEM_SQL_ITEM_TIME] = Util::getTime();
		$values[ItemDef::ITEM_SQL_ITEM_TEXT] = $vaInfo;
		$values[ItemDef::ITEM_SQL_ITEM_DELTIME] = 0;
		
		$itemObj = new TallyItem($values);
		
		$sumExp = 0;
		$sumGold = 0;
		$sumSilver = 0;
		$arrCost = array();
	
		$sumExp += $itemObj->getExp();
		$sumGold += $itemObj->getRebornCost();
		$sumSilver += $itemObj->getUpgradeCostSum();
		$arrCost = array_merge($arrCost, $itemObj->getDevelopCostSum());
		$arrCost = array_merge($arrCost, $itemObj->getEvolveCostSum());

		$arrAdd = Resolve::trans3D2Arr($arrCost);
		if (!empty($sumExp))
		{
			foreach (ItemDef::$TALLY_EXP_ITEMS as $itemTplId)
			{
				$itemValue = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_TALLYEXP);
				$itemNum = intval($sumExp / $itemValue);
				$sumExp -= $itemValue * $itemNum;
				if ($itemNum > 0)
				{
					if (!isset($arrAdd['item'][$itemTplId]))
					{
						$arrAdd['item'][$itemTplId] = 0;
					}
					$arrAdd['item'][$itemTplId] += $itemNum;
				}
			}
		}
		if (!empty($sumSilver))
		{
			if (!isset($arrAdd['silver']))
			{
				$arrAdd['silver'] = 0;
			}
			$arrAdd['silver'] += $sumSilver;
		}
		
		if($isLost)
		{
			$arrAdd['tally_point'] = $itemObj->getResolvePoint();
		}
		
		return $arrAdd;
	}

	
	public function getGainByPet()
	{

		self::info("\ncaculate gain by pet");
		$allPet = $this->mArrLostPet;
		if( empty ($allPet ) )
		{
			self::info("no gain by pet\n");
			return;
		}
		$msg = sprintf("all gain by pet\n" );
		foreach($this->mArrLostPet as $petId => $pet )
		{
			$petTplId = $pet['pet_tmpl'];
			$msg .= sprintf("\tpetId:%d, tplId:%d, name:%s, level:%d, skillPoint:%d\n", 
								$petId, $petTplId, self::getPetName($petTplId), $pet['level'], $pet['skill_point']);
		}
		self::info("%s\n", $msg);
	}
	
	
	public function someInfo($uid)
	{
		$arrItemIplId = array(
				501001,502001,501010,60016, 60007, 60002
		);
		$msg = sprintf("some item info\n");
		
		$userObj = EnUser::getUserObj($uid);
		$msg .= sprintf("\tsilver:%d\n", $userObj->getSilver());
		$msg .= sprintf("\tsoul:%d\n", $userObj->getSoul());
		$msg .= sprintf("\tjewel:%d\n", $userObj->getJewel());
		if ( in_array('tg_num', UserDef::$USER_FIELDS) )
		{
			$msg .= sprintf("\ttg:%d\n", $userObj->getTgNum());
		}
		$msg .= "\n";
		
		$bag = BagManager::getInstance()->getBag($uid);
		foreach( $arrItemIplId as $tplId)
		{
			if( !isset( btstore_get()->ITEMS[$tplId] ) )
			{
				$msg .= sprintf("\t itemTpl:%d, \t itemName:%s, not exist in this version\n", 
					$tplId, self::getItemName($tplId));
				continue;
			}
			$msg .= sprintf("\t itemTpl:%d, \t itemName:%s, \t itemNum:%d\n", 
					$tplId, self::getItemName($tplId), $bag->getItemNumByTemplateID($tplId));
		}
		
		self::info("%s\n", $msg);
	}
	
	
	public function getItemChangeInfo($infoInRecord, $infoInDb, $infoChange)
	{
		$msg = "\tinfoInRecord:\n";
		foreach( $infoInRecord as $key => $value )
		{
			if ( is_int( $value) )
			{
				$msg .= sprintf("\t\t%s:%d\n", $key, $value);
			}
			else
			{
				$msg .= sprintf("\t\t%s:%s\n", $key, serialize($value));
			}
		}
		
		$msg .= "\tinfoInDb:\n";
		foreach( $infoInDb as $key => $value )
		{
			if ( is_int( $value) )
			{
				$msg .= sprintf("\t\t%s:%d\n", $key, $value);
			}
			else
			{
				$msg .= sprintf("\t\t%s:%s\n", $key, serialize($value));
			}
		}
		
		$msg .= "\tinfoChange:\n";
		foreach( $infoChange as $key => $value )
		{
			if ( is_int( $value) )
			{
				$msg .= sprintf("\t\t%s:%d\n", $key, $value);
			}
			else
			{
				$msg .= sprintf("\t\t%s:%s\n", $key, serialize($value));
			}
		}
		return $msg;	
	}
	public function getHeroChangeInfo($infoInRecord, $infoInDb, $infoChange)
	{
		$record = array();
		if( isset($infoChange['level']) )
		{
			$record['level'] = $infoInRecord['level'];
			$db['level'] = $infoInDb['level'];
		}
		if( isset($infoChange['evolve_level']) )
		{
			$record['evolve_level'] = $infoInRecord['evolve_level'];
			$db['evolve_level'] = $infoInDb['evolve_level'];
		}
		
		$msg = "\tinfoInRecord:\n";
		foreach( $record as $key => $value )
		{
			$msg .= sprintf("\t\t%s:%d\n", $key, $value);
		}
		
		$msg .= "\tinfoInDb:\n";
		foreach( $db as $key => $value )
		{
			$msg .= sprintf("\t\t%s:%d\n", $key, $value);
		}
		
		$msg .= "\tinfoChange:\n";
		foreach( $infoChange as $key => $value )
		{
			$msg .= sprintf("\t\t%s:%d\n", $key, $value);
		}
		return $msg;
		
	}
	
	public function getPetChangeInfo($infoInRecord, $infoInDb, $infoChange)
	{
		$record = array();
		$db = array();
		if( isset($infoChange['skill_point']) )
		{
			$db['skill_point'] = $infoInDb['skill_point'];
		}
		if( isset($infoChange['level']) )
		{
			$db['level'] = $infoInDb['level'];
		}
		if( isset($infoChange['exp']) )
		{
			$db['exp'] = $infoInDb['exp'];
		}
		if( isset($infoChange['swallow']) )
		{
			$db['swallow'] = $infoInDb['swallow'];
		}
		if( isset($infoChange['va_pet']) )
		{
			$sum = 0;
			foreach($infoChange['va_pet']['skillNormal'] as $value)
			{
				$sum += $value['level'];
			}
			$infoChange['normalSkillNum'] = $sum;
			unset($infoChange['va_pet']);
			$sum = 0;
			foreach($infoInDb['va_pet']['skillNormal'] as $value)
			{
				$sum += $value['level'];
			}
			$db['normalSkillNum'] = $sum;
		}
		
		
		$msg = "\tinfoInRecord:\n";
		foreach( $record as $key => $value )
		{
			$msg .= sprintf("\t\t%s:%s\n", $key, $value);
		}
		
		$msg .= "\tinfoInDb:\n";
		foreach( $db as $key => $value )
		{
			$msg .= sprintf("\t\t%s:%s\n", $key, $value);
		}
		
		$msg .= "\tinfoChange:\n";
		foreach( $infoChange as $key => $value )
		{
			$msg .= sprintf("\t\t%s:%s\n", $key, $value);
		}
		return $msg;
	
	}
	
	public function dealHero()
	{
		$arrHid = array_keys( $this->mArrHero );
	
		$arrHeroData = self::getArrHeroFromDb($arrHid);
		
		$userObj = EnUser::getUserObj($this->mUid);
		$arrUnusedHero = $userObj->getAllUnusedHero();

		$heroInitInfo = HeroLogic::getInitData(0, 0, 0);
		foreach( $this->mArrHero as $hid => $infoPre  )
		{
			if( !isset( $arrHeroData[$hid] ) )
			{
				if( isset( $arrUnusedHero[$hid] ) )
				{
					continue;
				}
				if( $infoPre['level'] != $heroInitInfo['level'] 
					|| ( isset($infoPre['evolve_level']) && $infoPre['evolve_level'] != $heroInitInfo['evolve_level'] ) )
				{
					self::fatal('count find hid:%d', $hid);
				}
				else
				{
					$this->mArrLostUnusedHero[$hid] = $infoPre;	
				}
				continue;
			}
			
			$infoCur = $arrHeroData[$hid];
			if( $infoCur['uid'] != $this->mUid )
			{
				self::fatal('hid:%d not belong uid:%d, but belong to uid:%d', $this->mUid, $infoCur['uid'] );
				continue;
			}
			
			if( $infoCur['delete_time'] > 0 )
			{
				foreach(HeroDef::$ALL_EQUIP_TYPE as $equipName)
				{
					if( empty($infoCur['va_hero'][$equipName]) )
					{
						continue;
					}
					foreach( $infoCur['va_hero'][$equipName] as $pos => $itemId)
					{
						if( $itemId != 0 )
						{
							self::fatal('lost hero has equip. hid:%d, equip:%s, pos:%d, itemId:%d', $hid, $equipName, $pos, $itemId);
						}
					}
				}
				$this->mArrLostHero[ $hid ] = $infoCur;
			}			
			
			$modify = array();			
			
			if( HeroUtil::isMasterHtid($infoCur['htid']) )
			{
				if( isset($infoPre['evolve_level']) && $infoCur['evolve_level'] < $infoPre['evolve_level'] )
				{
					self::info('master hero evolve_level decrease. hid:%d, new:%d, old:%d', $hid,  $infoCur['evolve_level'], $infoPre['evolve_level']);
				}
				if( $infoCur['level'] < $infoPre['level'] )
				{
					self::fatal('master hero level decrease. hid:%d, new:%d, old:%d', $hid,  $infoCur['level'], $infoPre['level']);
				}
			}
			else
			{
				$alreadySet = false;
				if( $infoCur['htid'] != $infoPre['htid'] )
				{
					$ret = $this->dealHeroChangeHtid($hid, $infoPre, $infoCur);
					if( isset($ret['ignore']) && $ret['ignore']  )
					{
						self::info('ignore hid:%d', $hid);
						continue;
					}
					$alreadySet = $ret['alreadySet'];
					$modify = $ret['modify'];
					
				}
				if ( false == $alreadySet )
				{
					if( isset($infoPre['evolve_level']) &&  $infoCur['evolve_level'] < $infoPre['evolve_level'] )
					{
						$modify['evolve_level'] =  $infoPre['evolve_level'];
					}
					if( $infoCur['level'] < $infoPre['level'] )
					{
						$modify['level'] =  $infoPre['level'];
					}
				}
			}
			
			
			if( !empty( $modify ) )
			{
				$this->mArrModifyHero[$hid] = $modify;
			}
		}
	}
	
	public function dealLittleFriend()
	{
		if( empty( $this->mArrLittleFriend ) )
		{
			Logger::info('no little friend');
			return;
		}
		$arrHid = array_keys($this->mArrLittleFriend );

		$arrHeroData = self::getArrHeroFromDb($arrHid);

		$userObj = EnUser::getUserObj($this->mUid);
		$arrUnusedHero = $userObj->getAllUnusedHero();
	
		foreach( $this->mArrLittleFriend as $hid => $infoPre  )
		{
			$htid = $infoPre['htid'];
			if( !isset( $arrHeroData[$hid] ) )
			{
				if( isset( $arrUnusedHero[$hid] ) )
				{
					continue;
				}
				
				$this->mArrLostUnusedLittleFriend[ $hid ] = $htid;
				Logger::debug('lost unused littlt friend. hid:%d, htid:%d', $hid, $htid);
				continue;
			}
			$infoCur = $arrHeroData[$hid];
				
			if( $infoCur['uid'] != $this->mUid )
			{
				self::fatal('hid:%d not belong uid:%d, but belong to uid:%d', $this->mUid, $infoCur['uid'] );
				continue;
			}
				
			if( $infoCur['delete_time'] > 0 )
			{
				$this->mArrLostLittleFriend[ $hid ] = $infoCur;
			}
		}
	}
	
	
	public function dealAttrHero()
	{
		$arrHid = array_keys( $this->mArrAttrFriend );
	
		$arrHeroData = self::getArrHeroFromDb($arrHid);
	
		$userObj = EnUser::getUserObj($this->mUid);
		$arrUnusedHero = $userObj->getAllUnusedHero();
	
		$heroInitInfo = HeroLogic::getInitData(0, 0, 0);
		foreach( $this->mArrAttrFriend as $hid => $infoPre  )
		{
			if( !isset( $arrHeroData[$hid] ) )
			{
				if( isset( $arrUnusedHero[$hid] ) )
				{
					continue;
				}
				if( $infoPre['level'] != $heroInitInfo['level']
				|| ( isset($infoPre['evolve_level']) && $infoPre['evolve_level'] != $heroInitInfo['evolve_level'] ) )
				{
					self::fatal('count find hid:%d', $hid);
				}
				else
				{
					$this->mArrLostUnusedAttrFriend[$hid] = $infoPre;
				}
				continue;
			}
				
			$infoCur = $arrHeroData[$hid];
			if( $infoCur['uid'] != $this->mUid )
			{
				self::fatal('hid:%d not belong uid:%d, but belong to uid:%d', $this->mUid, $infoCur['uid'] );
				continue;
			}
				
			if( $infoCur['delete_time'] > 0 )
			{
				$this->mArrLostAttrFriend[ $hid ] = $infoCur;
			}
				
			$modify = array();
				

			$alreadySet = false;
			if( $infoCur['htid'] != $infoPre['htid'] )
			{
				$ret = $this->dealHeroChangeHtid($hid, $infoPre, $infoCur);
				if( isset($ret['ignore']) && $ret['ignore']  )
				{
					self::info('ignore hid:%d', $hid);
					continue;
				}
				$alreadySet = $ret['alreadySet'];
				$modify = $ret['modify'];
					
			}
			if ( false == $alreadySet )
			{
				if( isset($infoPre['evolve_level']) &&  $infoCur['evolve_level'] < $infoPre['evolve_level'] )
				{
					$modify['evolve_level'] =  $infoPre['evolve_level'];
				}
				if( $infoCur['level'] < $infoPre['level'] )
				{
					$modify['level'] =  $infoPre['level'];
				}
			}
		
			if( !empty( $modify ) )
			{
				$this->mArrModifyHero[$hid] = $modify;
			}
			Logger::info('hid:%d modify:%s', $hid, $modify);
		}
	}
	
	
	public function dealHeroChangeHtid($hid, $infoPre, $infoCur)
	{
		$alreadySet = false;
		$modify = array();
		
		
		/*
		 * htid发生改变的情况
		* 【1】变身。 此时正常处理
		* 【2】进化，紫卡变橙卡。此时卡必然在，不处理即可
		* 【3】重生，橙卡变紫卡。此时卡可能不在了，不管在不在，变成+7紫卡即可，等级取战报中主角等级
		* 【4】橙卡变红卡
		* 【5】橙卡以上的卡向下变到紫卡以上的卡
		*/
		$infoStr = sprintf('hid:%d, recordHtid:%s(%d), curHtid:%s(%d)',
				$hid, self::getHeroName($infoPre['htid']), $infoPre['htid'], self::getHeroName($infoCur['htid']), $infoCur['htid'] );
			
		if ( Creature::getCreatureConf( $infoCur['htid'], CreatureAttr::STAR_LEVEL ) >= 6  )
		{
			if(  Creature::getCreatureConf( $infoPre['htid'], CreatureAttr::STAR_LEVEL ) >= 6 )
			{
				//[4]
				if( Creature::getCreatureConf( $infoPre['htid'], CreatureAttr::STAR_LEVEL ) < Creature::getCreatureConf( $infoCur['htid'], CreatureAttr::STAR_LEVEL )  )
				{
					self::info('change to upper hero. %s', $infoStr);
					return array(
							'ignore' => true,
					);
				}
				
				if( $infoCur['delete_time'] > 0 )
				{
				    self::fatal('orange hero is deleted. %s  deleteTime:%s', $infoStr, date('Y-m-d H:i:s', $infoCur['delete_time']) );
				}
				else
				{
				    self::info('up orange hero change to lower hero. %s', $infoStr);
				}
				//self::fatal('orange hero change htid. %s', $infoStr);
			}
			else
			{
				//[2]
				$alreadySet = true;
				if( $infoCur['delete_time'] > 0 )
				{
					self::fatal('orange hero is deleted. %s  deleteTime:%s', $infoStr, date('Y-m-d H:i:s', $infoCur['delete_time']) );
				}
				self::info('purple hero to orange hero. %s', $infoStr);
			}
		}
		else
		{
			if(  Creature::getCreatureConf( $infoPre['htid'], CreatureAttr::STAR_LEVEL ) >= 6 )
			{
				//[3]
				$alreadySet = true;
				if ( $infoCur['evolve_level'] != 7 )
				{
					$modify['evolve_level'] = 7;
				}
					
				if(  $infoCur['level'] < $infoPre['level'] )
				{
					$modify['level'] = $infoPre['level'];
				}
				$this->mArrOrangeHid[$hid] = 1;
				self::info('orange hero to purple hero. %s', $infoStr);
			}
			else
			{
				//[1]
				//do nothing
				self::info('purple hero change htid. %s', $infoStr);
			}
		}
		
		return array(
			'alreadySet' => $alreadySet,
			'modify' => $modify,
		);
	}
	
	public function dealPet()
	{
		if( empty( $this->mArrPet ) )
		{
			Logger::info('no pet');
			return;
		}
		$arrPetId = array_keys($this->mArrPet );
		
		$arrPetData = self::getArrPetFromDb($arrPetId);
		
	
		foreach( $this->mArrPet as $petId => $infoPre  )
		{
			if ( !isset($arrPetData[$petId]) )
			{
				self::fatal('not found petId:%s', $petId);
				continue;
			}
			
			$infoInDb = $arrPetData[$petId];
			
			if( $infoInDb['uid'] != $this->mUid )
			{
				self::fatal('petId:%d not belong uid:%d, but belong to uid:%d', $petId, $this->mUid, $infoInDb['uid'] );
				continue;
			}
			
			if( $infoInDb['delete_time'] > 0 )
			{
				$this->mArrLostPet[ $petId ] = $infoInDb;
			}
		}
	}
	
	/**
	 * 处理背包中的数据
	 * 
	 */
	public function dealItemInBag()
	{
		$arrInfo = BagManager::getInstance()->getBag()->bagInfo();
		
		Logger::info('dealItemInBag start');
		
		foreach( self::$arrDataObj as $name => $conf )
		{
			if( !empty($conf['bagName']) &&  isset($arrInfo[ $conf['bagName'] ])  )
			{
				self::info('dealItemInBag: %s', $conf['bagName']);
				$this->dealArrItem( $arrInfo[ $conf['bagName'] ] );
			}
		}
		Logger::info('dealItemInBag end');
	}
	
	public function dealItemInHero()
	{
		Logger::info('dealItemInHero start');
		
		$arrHeroObj = EnUser::getUserObj($this->mUid)->getHeroManager()->getAllHeroObjInSquad();
		foreach( $arrHeroObj as $hid => $heroObj )
		{
			Logger::info('dealItemInHero. hid:%d', $hid);
			$heroInfo = $heroObj->getInfo();
			$equipInfo = $heroInfo['equip'];
			
			foreach( self::$arrDataObj as $name => $conf )
			{
				if( !empty($conf['equipName']) &&  isset($equipInfo[ $conf['equipName'] ])  )
				{
					Logger::info('dealItemInHero: hid:%d, %s ', $hid, $conf['equipName']);
					$this->dealArrItem( $equipInfo[ $conf['equipName'] ] );
				}
			}
		}
		
		Logger::info('dealItemInHero end');
	}
	
	/**
	 * 处理被删掉的物品
	 */
	public function dealLostItem()
	{
		Logger::info('dealLostItem start');
		
		$arrItemId = array();
		foreach($this->mAllEquip as $arrEquip)
		{
			$arrItemId = array_merge( $arrItemId, array_keys($arrEquip) );
		}
		
		if( empty($arrItemId) )
		{
			Logger::info('no lost item');
			return;
		}
		
		$arrItemInfo = $this->getArrItemFromDb($arrItemId);
		
		foreach( $arrItemId as $itemId )
		{
			if( !isset( $arrItemInfo[$itemId] ) )
			{
				self::fatal('cant find itemId:%d', $itemId);
			}
			if( $arrItemInfo[$itemId][ItemDef::ITEM_SQL_ITEM_DELTIME] == 0  )
			{
				self::fatal('itemId:%d not delete. please check it', $itemId);
			}
		}
		
		$this->mArrLostItemInfo = $arrItemInfo;
		
		$this->dealArrItem($arrItemInfo);
		
		Logger::info('dealLostItem end');
	}
	
	/**
	 * 处理现在玩家身上的物品（包括背包，武将等上的）
	 * 对比这些物品的数据，并进行相关处理
	 * @param array $arrItemInfo
	 */
	public function dealArrItem( $arrItemInfo )
	{
		foreach($arrItemInfo as $itemInfo)
		{
			if( empty($itemInfo) )
			{
				continue;
			}
			
			$itemId = $itemInfo['item_id'];
			
			
			$deal = false;
			foreach( self::$arrDataObj as $name => $conf )
			{
				if( isset( $conf['dealFunc'] ) 
					&& isset( $this->mAllEquip[$name][$itemId] ) )
				{
					call_user_func_array ( array ($this, $conf['dealFunc'] ), array($itemId, $this->mAllEquip[$name][$itemId]['va_item_text'] ) );
					unset( $this->mAllEquip[$name][$itemId] );
					Logger::debug('deal itemId:%d', $itemId);
					$deal = true;
					break;
				}
			}
			
			if( !$deal ) 
			{
				Logger::debug('ignore itemId:%d, itemTpl:%d', $itemId, $itemInfo['item_template_id']);
			}
			
			foreach( self::$arrDataObj as $name => $conf )
			{
				if( isset( $conf['itemContainerName'] )
				&& isset( $itemInfo['va_item_text'][ $conf['itemContainerName'] ] ) 
				&& isset( $conf['dealFunc'] )  )
				{
					foreach ( $itemInfo['va_item_text'][$conf['itemContainerName']] as $inlayItemInfo )
					{
						$inlayItemId = $inlayItemInfo['item_id'];
						if ( isset($this->mAllEquip[$name][$inlayItemId]) )
						{
							call_user_func_array ( array ($this, $conf['dealFunc'] ), array($inlayItemId, $this->mAllEquip[$name][$inlayItemId]['va_item_text'] ) );
							unset( $this->mAllEquip[$name][$inlayItemId] );
						}
					}
				}
			}
		}
		
	}
	
	public function updateArm($itemId, $itemVaPre)
	{
		$itemData = self::getItemFromDb($itemId);
		
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
		
		$modify = false;

		//处理强化相关		
		if( $itemVaCur['armReinforceLevel'] < $itemVaPre['armReinforceLevel'] )
		{
			$itemVaCur['armReinforceLevel'] = $itemVaPre['armReinforceLevel'];
			$modify = true;
		}
		if( isset($itemVaCur['armReinforceCost']) && $itemVaCur['armReinforceCost'] <  $itemVaPre['armReinforceCost'] )
		{
			$itemVaCur['armReinforceCost'] = $itemVaPre['armReinforceCost'];
			$modify = true;
		}
		
		//处理潜能  只有当当前潜能为0时才处理了，否则不好处理
		if ( empty($itemVaCur['armPotence']) && !empty($itemVaPre['armPotence']) )
		{
			$itemVaCur['armPotence'] = $itemVaPre['armPotence'];
			//$itemVaCur['armFixedPotence'] = array();
			unset($itemVaCur['armFixedPotence']);
			$modify = true;
		}
		
		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
			Logger::debug('itemId:%d has modify', $itemId);
		}
	}
	
	public function updateTreasure($itemId, $itemVaPre)
	{
				
		$itemData = self::getItemFromDb($itemId);
		
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
		
		$modify = false;
		
		//处理强化相关
		if( $itemVaCur['treasureLevel'] < $itemVaPre['treasureLevel'] )
		{
			$itemVaCur['treasureLevel'] = $itemVaPre['treasureLevel'];
			$modify = true;
		}
		if( $itemVaCur['treasureExp'] <  $itemVaPre['treasureExp'] )
		{
			$itemVaCur['treasureExp'] = $itemVaPre['treasureExp'];
			$modify = true;
		}
		
		//处理精炼
		if(isset($itemVaPre['treasureEvolve']) && isset( $itemVaCur['treasureEvolve']) 
				&& $itemVaCur['treasureEvolve'] < $itemVaPre['treasureEvolve'] )
		{
			$itemVaCur['treasureEvolve'] = $itemVaPre['treasureEvolve'];
			$modify = true;
		}
		
		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
		}
	
	}
	
	public function updateDress($itemId, $itemVaPre)
	{
		$itemData = self::getItemFromDb($itemId);
		
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
		
		$modify = false;
		
		if( !isset(  $itemVaCur['dressLevel']  ) )
		{
			$itemVaCur['dressLevel'] = 0;
		}
		if( !isset( $itemVaPre['dressLevel'] ) )
		{
			$itemVaPre['dressLevel'] = 0;
		}
		//处理是时装等级
		if(  $itemVaCur['dressLevel'] < $itemVaPre['dressLevel'] )
		{
			$itemVaCur['dressLevel'] = $itemVaPre['dressLevel'];
			$modify = true;
		}

		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
			Logger::debug('dress:%d has modify', $itemId);
		}
	}
	
	public function updateFightSoul($itemId, $itemVaPre)
	{
		$itemData = self::getItemFromDb($itemId);
		
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
		
		$modify = false;
		
		//处理强化相关
		if( $itemVaCur['fsLevel'] != $itemVaPre['fsLevel'] )
		{
			$itemVaCur['fsLevel'] = $itemVaPre['fsLevel'];
			$modify = true;
		}
		if( $itemVaCur['fsExp'] != $itemVaPre['fsExp'] )
		{
			$itemVaCur['fsExp'] = $itemVaPre['fsExp'];
			$modify = true;
		}

		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
		}
	}
	
	public function updateGodWeapon($itemId, $itemVaPre)
	{
	
		/**
		 * va_item_text:array   物品扩展信息
		 * [
		 *      'reinForceLevel':int 强化等级
		 *      'reinForceCost':int 强化费用银币(炼化返还用)
		 *      'reinForceExp':int  强化经验(炼化返还用)
		 *      'evolveNum':int 进化次数
		 * ]
		 */
		$itemData = self::getItemFromDb($itemId);
	
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
	
		$modify = false;
	
		//处理强化相关
		if( $itemVaCur['reinForceLevel'] < $itemVaPre['reinForceLevel'] )
		{
			$itemVaCur['reinForceLevel'] = $itemVaPre['reinForceLevel'];
			$modify = true;
		}
		if( $itemVaCur['reinForceExp'] <  $itemVaPre['reinForceExp'] )
		{
			$itemVaCur['reinForceExp'] = $itemVaPre['reinForceExp'];
			$modify = true;
		}
		if( $itemVaCur['reinForceCost'] <  $itemVaPre['reinForceCost'] )
		{
			$itemVaCur['reinForceCost'] = $itemVaPre['reinForceCost'];
			$modify = true;
		}
	
		//处理进化
		if( $itemVaCur['evolveNum'] < $itemVaPre['evolveNum'] )
		{
			$itemVaCur['evolveNum'] = $itemVaPre['evolveNum'];
			$modify = true;
		}
		
		if ( empty($itemVaCur['confirmed']) && !empty($itemVaPre['confirmed']) )
		{
			$itemVaCur['confirmed'] = $itemVaPre['confirmed'];
			$modify = true;
		}
	
		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
		}
	
	}
	
	
	public function updateRune($itemId, $itemVaPre)
	{
		//nothing todo
	}
	
	public function updateSkillbook($itemId, $itemVaPre)
	{
		self::fatal('not support skillbook');
	}
	

	public function updatePocket($itemId, $itemVaPre)
	{
		/*
		  va_item_text:array	物品扩展信息
		  {
		  	  	'pocketLevel':等级
		  		'pocketExp':总经验值
		  }
		 */
		$itemData = self::getItemFromDb($itemId);
	
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
	
		$modify = false;
	
		//处理强化相关
		if( $itemVaCur['pocketLevel'] != $itemVaPre['pocketLevel'] )
		{
			$itemVaCur['pocketLevel'] = $itemVaPre['pocketLevel'];
			$modify = true;
		}
		if( $itemVaCur['pocketExp'] != $itemVaPre['pocketExp'] )
		{
			$itemVaCur['pocketExp'] = $itemVaPre['pocketExp'];
			$modify = true;
		}
	
		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
		}
	}
	
	
	public function updateTally($itemId, $itemVaPre)
	{
	
		/**
		 * va_item_text:array   物品扩展信息
		 * {
		 *      'tallyLevel':等级
		 *      'tallyExp':总经验值
		 *      'tallyEvolve':精炼等级
		 *      'tallyDevelop':进阶等级
		 * }
		 */

		$itemData = self::getItemFromDb($itemId);
	
		if( empty($itemData) )
		{
			self::fatal('not found itemId:%d', $itemId);
			return;
		}
		$itemVaCur = $itemData['va_item_text'];
	
		$modify = false;
	

		if( $itemVaCur['tallyLevel'] < $itemVaPre['tallyLevel'] )
		{
			$itemVaCur['tallyLevel'] = $itemVaPre['tallyLevel'];
			$modify = true;
		}
		if( $itemVaCur['tallyExp'] <  $itemVaPre['tallyExp'] )
		{
			$itemVaCur['tallyExp'] = $itemVaPre['tallyExp'];
			$modify = true;
		}

		if( $itemVaCur['tallyEvolve'] < $itemVaPre['tallyEvolve'] )
		{
			$itemVaCur['tallyEvolve'] = $itemVaPre['tallyEvolve'];
			$modify = true;
		}
	
		if( $itemVaCur['tallyDevelop'] < $itemVaPre['tallyDevelop'] )
		{
			$itemVaCur['tallyDevelop'] = $itemVaPre['tallyDevelop'];
			$modify = true;
		}
	
		if ( $modify  )
		{
			$this->mArrModifyItem[$itemId] = $itemVaCur;
		}
	}
	
	
	public function getBattleData($uid, $uname, $brid)
	{
		$formationInfo = $this->getBattleDataOfUid($uid, $uname, $brid);
		
		Logger::info('battleRecord:%s', $formationInfo);
		
		$arrHero = $formationInfo['arrHero'];
		$arrPet = array();
		if( isset($formationInfo['arrPet']) )
		{
			$arrPet = Util::arrayIndex($formationInfo['arrPet'], 'petid');
		}
		
		$msg = sprintf('infomation in brid:%d, uid:%d, uname:%s', $brid, $uid, $uname);
		$arrItemInfo = array();
		$arrLittleFriend = array();
		if( !empty( $formationInfo['littleFriend'] ) )
		{
			/**
			 * 小伙伴的数据结构改变过一次
			 * 之前是：{hid=>htid}
			 * 改成：[ {hid=>int, htid=>int, position=>int} ]
			 */
			if( isset($formationInfo['littleFriend'][0]) )
			{
				$arrLittleFriend = Util::arrayIndex( $formationInfo['littleFriend'], 'hid');
			}
			else 
			{
				foreach( $formationInfo['littleFriend'] as $hid => $htid )
				{
					$arrLittleFriend[ $hid ] = array(
						'hid' => $hid,
						'htid' => $htid,
					);
				}
				
			}
		}
		$arrAttrFriend = array();
		if (  !empty( $formationInfo['attrFriend'] ) )
		{
			$arrAttrFriend = Util::arrayIndex($formationInfo['attrFriend'], 'hid');
		}
		$arrHero = Util::arrayIndex($arrHero, 'hid');
		$msg .= sprintf("hero:\n");
		foreach( $arrHero as $hero )
		{
			$msg .= sprintf("hid:%d, htid:%d, name:%s, evolve:%d, pos:%d\n", 
						$hero['hid'], $hero['htid'], self::getHeroName($hero['htid']), $hero['evolve_level'], $hero['position']);
			foreach(  $hero['equipInfo'] as $equipName => $equipInfo )
			{
				$msg .= sprintf( "%s\n", $equipName );
				foreach( $equipInfo as $pos => $itemInfo )
				{
					if( empty( $itemInfo  ) )
					{
						continue;
					}
					
					switch($equipName)
					{
						case 'arming':
							//潜能在战报中和数据库中不太一样
							if (  !empty($itemInfo['va_item_text']['armPotence']) )
							{
								foreach ( $itemInfo['va_item_text']['armPotence'] as $attrId => $attrValue )
								{
									$itemInfo['va_item_text']['armPotence'][$attrId] = $attrValue *
									Potence::getPotenceAttrValue( ItemAttr::getItemAttr($itemInfo['item_template_id'], ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE), $attrId);
								}
							}
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']),
									$itemInfo['va_item_text']['armReinforceLevel'] );
			
							break;
							
						case 'treasure':
							if( !isset($itemInfo['va_item_text']['treasureEvolve']) )
							{
								$itemInfo['va_item_text']['treasureEvolve'] = 0;
							}
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d, evovle:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']),
									$itemInfo['va_item_text']['treasureLevel'],
									$itemInfo['va_item_text']['treasureEvolve'] );
							if ( isset( $itemInfo['va_item_text']['treasureInlay'] ) )
							{
								foreach( $itemInfo['va_item_text']['treasureInlay'] as $inlyInfo  )
								{
									$arrItemInfo['rune'][$inlyInfo['item_id']] = $inlyInfo;
									$msg .= sprintf("\trune: tpl:%d, name:%s", $inlyInfo['item_template_id'],
												self::getItemName($inlyInfo['item_template_id']) );
								}
							}
							break;
						
						case 'dress':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']) );
							break;
							
						case 'fightSoul':
									$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']), $itemInfo['va_item_text']['fsLevel'] );
							break;
														
						case 'skillBook':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']) );
							break;
						case 'godWeapon':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s\n",
							$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
							self::getItemName($itemInfo['item_template_id']) );
							break;
							
						case 'pocket':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d, exp:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']),
									$itemInfo['va_item_text']['pocketLevel'],
									$itemInfo['va_item_text']['pocketExp']  );
							break;
							
						case 'tally':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d, exp:%d, evolve:%d, develop:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']),
									$itemInfo['va_item_text']['tallyLevel'],
									$itemInfo['va_item_text']['tallyExp'],
									$itemInfo['va_item_text']['tallyEvolve'],
									$itemInfo['va_item_text']['tallyDevelop']  );
							break;
						default:
							self::fatal('invalid equipName:%s', $equipName);
							break;
					}
					if( !empty($itemInfo) )
					{
						$arrItemInfo[$equipName][$itemInfo['item_id']] = $itemInfo;
					}
				}
			}
			
		}
		
		$msg .= sprintf("little friend:\n");
		foreach($arrLittleFriend as $hero)
		{
			$msg .= sprintf("hid:%d, htid:%d, name:%s\n", $hero['hid'], $hero['htid'], self::getHeroName($hero['htid']) );
		}
		
		$msg .= sprintf("attr friend:\n");
		foreach($arrAttrFriend as $hero)
		{
			$msg .= sprintf("hid:%d, htid:%d, name:%s, lv:%d, ev:%d\n", 
					$hero['hid'], $hero['htid'], self::getHeroName($hero['htid']),$hero['level'], $hero['evolve_level']);
		}
		
		$msg .= sprintf("pet:\n");
		foreach($arrPet as $pet)
		{
			$msg .= sprintf("pet_id:%d, tplId:%d, name:%s\n", $pet['petid'], $pet['pet_tmpl'], self::getPetName($pet['pet_tmpl']));
		}
		
		return array(
			'msg' => $msg,
			'arrItem' => $arrItemInfo,
			'arrHero' => $arrHero,
			'arrLittleFriend' => $arrLittleFriend,
			'attrFriend' => $arrAttrFriend,
			'arrPet' => $arrPet,
			'level' => $formationInfo['level'],
		);
	}
	
	public function searchBrid($uid, $uname, $minDate, $maxDate, $limit = 10)
	{
		if( empty($minDate) )
		{
			$minDate = 0;
		}
		
		if( empty($maxDate) )
		{
			$maxDate = PHP_INT_MAX;
		}
		
		self::info('search brid. uid:%d, uname:%s, minDate:%s, maxDate:%s',
				$uid, $uname, date('Y-m-d H:i:s', $minDate), date('Y-m-d H:i:s', $maxDate));
		
		$arrFromMail = $this->searchBridFromMail($uid, $uname, $minDate, $maxDate);
		
		$arrFromArena = $this->searchBridFromArena($uid, $uname, $minDate, $maxDate);
		
		$arrInfo = array_merge($arrFromMail, $arrFromArena);
		
		$cmpFunc = function  ($a, $b)
		{
			if ($a['time'] < $b['time'] )
			{
				return 1;
			}
			return -1;
		};
		
		usort($arrInfo, $cmpFunc);
		
		return $arrInfo;
		
	}
	public function searchBridFromArena($uid, $uname, $minDate, $maxDate, $limit = 10)
	{

		$arrField = array(
			'id',
			'attack_uid',
			'defend_uid',
			'attack_time',
			'attack_replay',
		);
		
		$data = new CData();
		$arrAtk = $data->select( $arrField )->from('t_arena_msg')
				->where( 'attack_uid', '=', $uid)
				->where( 'attack_time', 'BETWEEN', array($minDate, $maxDate) )
				->orderBy( 'attack_time', false)
				->limit(0, $limit)->query();
		
		$arrDef = $data->select( $arrField )->from('t_arena_msg')
				->where( 'defend_uid', '=', $uid)
				->where( 'attack_time', 'BETWEEN', array($minDate, $maxDate) )
				->orderBy( 'attack_time', false)
				->limit(0, $limit)->query();
				
		$rcmp = function  ($msg1, $msg2)
		{
			if ($msg1['id'] < $msg2['id'])
			{
				return 1;
			}
			return -1;
		};
		
		$arrMsg = array_merge($arrAtk, $arrDef);
		usort($arrMsg, $rcmp);
		
		$arrMsg = array_slice($arrMsg, 0, $limit);
		
		$arrInfo = array();
		foreach($arrMsg as $msg)
		{
			$arrInfo[] = array(
					'brid' => $msg['attack_replay'],
					'time' => $msg['attack_time'],
					'tpl' => 'arena_msg',
			);
		}
		return $arrInfo;
	}
	public function searchBridFromMail($uid, $uname, $minDate, $maxDate, $limit = 10)
	{
		
		$data = new CData();
		$arrField = array(
				MailDef::MAIL_SQL_ID,
				MailDef::MAIL_SQL_RECIEVER,
				MailDef::MAIL_SQL_TEMPLATE_ID,
				MailDef::MAIL_SQL_RECV_TIME,
				MailDef::MAIL_SQL_EXTRA,
		);
		$arrRet = $data->select( $arrField )->from(MailDef::MAIL_SQL_TABLE)
					->where( MailDef::MAIL_SQL_RECIEVER, '=', $uid)
					->where( MailDef::MAIL_SQL_TYPE, '=', MailType::BATTLE_MAIL )
					->where( MailDef::MAIL_SQL_RECV_TIME, 'BETWEEN', array($minDate, $maxDate) )
					->orderBy( MailDef::MAIL_SQL_RECV_TIME, false)
					->limit(0, $limit)->query();
		
		
		
		$mailTplIdClass = new ReflectionClass('MailTemplateID');
		$arrTplName =  $mailTplIdClass->getConstants();
		$arrTplName = array_flip($arrTplName);

		
		$arrInfo = array();
		foreach( $arrRet as $ret )
		{
			$mailTplId = $ret[MailDef::MAIL_SQL_TEMPLATE_ID];
			if( !isset( $arrTplName[$mailTplId] ) )
			{
				self::info('not found mail template:%d', $mailTplId);
				continue;
			}
			$arrInfo[] = array(
					'brid' => $ret[MailDef::MAIL_SQL_EXTRA]['replay'],
					'time' => $ret[MailDef::MAIL_SQL_RECV_TIME],
					'tpl' => $arrTplName[$mailTplId],
			);
		}
		
		Logger::debug('searchBridFromMail result:%s', $arrInfo);
		
		return $arrInfo;
		
	}
	
	
	public function getBattleDataOfUid($uid, $uname, $brid)
	{
		$data =  BattleDao::getRecord($brid);
		
		$battleData = Util::amfDecode($data, true);
		
		if(  $battleData['team1']['uid'] == $uid)
		{
			$formationInfo = $battleData['team1'];
		}
		else if(  $battleData['team2']['uid'] == $uid)
		{
			$formationInfo = $battleData['team2'];
		}
		else
		{
			self::fatal('uid:%d not in the battleRecord', $uid);
		}
		
		Logger::info('uid1:%d, uname1:%s, uid2:%d, uname2:%s', 
				$battleData['team1']['uid'], $battleData['team1']['name'],
				$battleData['team2']['uid'], $battleData['team2']['name']);
		
		return $formationInfo;
	}
	
	public function dealBrid($brid)
	{
		if( strlen($brid) >= 16 )
		{
			$ret = BabelCrypt::decryptNumber($brid);
			if ( empty($ret) )
			{
				self::fatal('invalid brid:%s', $brid);
			}
			return $ret;
		}
		else
		{
			return intval($brid);
		}
	}
	
	public static  function getItemFromDb($itemId)
	{
		$arrRet = self::getArrItemFromDb(array($itemId));
		if( empty($arrRet) )
		{
			return array();
		}
		return $arrRet[$itemId];
	}
	
	public static function getArrItemFromDb($arrItemId)
	{
		if( empty($arrItemId) )
		{
			return array();
		}
		$arrField = array(
				ItemDef::ITEM_SQL_ITEM_ID,
				ItemDef::ITEM_SQL_ITEM_NUM,
				ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID,
				ItemDef::ITEM_SQL_ITEM_TIME,
				ItemDef::ITEM_SQL_ITEM_DELTIME,
				ItemDef::ITEM_SQL_ITEM_TEXT,
		);		
		
		$arrItemInfo = array();

		$arrArrItemId = array_chunk($arrItemId, CData::MAX_FETCH_SIZE);
		foreach ($arrArrItemId as $arrItemId)
		{
			$wheres = array(
					array(ItemDef::ITEM_SQL_ITEM_ID, 'IN', $arrItemId)
			);
			$arrRet = ItemDAO::selectItem($arrField, $wheres);
			
			$arrItemInfo = array_merge($arrItemInfo, $arrRet);
		}
		
		$arrItemInfo = Util::arrayIndex($arrItemInfo, ItemDef::ITEM_SQL_ITEM_ID);
		return $arrItemInfo;
	}
	
	public static function getHeroFromDb($hid)
	{
		$arrRet = self::getArrHeroFromDb( array($hid) );
		if( empty($arrRet) )
		{
			return array();
		}
		return $arrRet[$hid];
	}
	public static function getArrHeroFromDb($arrHid)
	{
		if( empty($arrHid) )
		{
			return array();
		}
		$data = new CData();
		$arrRet = $data->select(HeroDef::$HERO_FIELDS)->from(HeroDao::TBL_HERO)
			->where('hid', 'IN', $arrHid)->query();
		
		$arrHeroData = Util::arrayIndex($arrRet, 'hid');
		return $arrHeroData;
	}
	public static function getPetFromDb($petId)
	{
		$arrRet = self::getArrPetFromDb(array($petId));
		if( empty($arrRet) )
		{
			return array();
		}
		return $arrRet[$petId];
	}
	public static function getArrPetFromDb($arrPetId)
	{
		$arrField = array(
				'petid',
				'uid',
				'pet_tmpl',
				'level',
				'exp',
				'skill_point',
				'swallow',
				'traintime',
				'delete_time',
				'va_pet',
		);

		if( empty($arrPetId) )
		{
			return array();
		}
		$data = new CData();
		$arrRet = $data->select( $arrField )->from('t_pet')
			->where('petid', 'IN', $arrPetId)->query();
		
		$arrPetData = Util::arrayIndex($arrRet, 'petid');
		return $arrPetData;
	}
	private function resetItem($itemId)
	{
		Logger::info('resetItem. itemId:%d', $itemId);
		$values = array (ItemDef::ITEM_SQL_ITEM_DELTIME => 0);
		$where = array(ItemDef::ITEM_SQL_ITEM_ID, '=', $itemId);
		$return = ItemDAO::updateItem($where, $values);
	}
	
	private function resetHero($uid, $hid)
	{
		Logger::info('resetHero. uid:%d, hid:%d', $uid, $hid);
		
		$arrField = array(
			'delete_time' => 0,
		);
		$where = array("hid", "=", $hid);
		$data = new CData();
		$ret = $data->update(HeroDao::TBL_HERO)
						->set($arrField)
						->where('uid', '=', $uid)
						->where('hid', '=', $hid)
						->where('delete_time', '>', 0)
						->query();
		
		if ( $ret[DataDef::AFFECTED_ROWS] != 1 )
		{
			self::fatal('resetHero failed. uid:%d, hid:%d', $uid, $hid);
		}
	}
	
	private function resetPet($uid, $petId)
	{
		Logger::info('resetPet. uid:%d, petId:%d', $uid, $petId);
		
		$arrField = array(
				'delete_time' => 0,
		);
		$where = array("petid", "=", $petId);
		$data = new CData();
		$ret = $data->update('t_pet')
			->set($arrField)
			->where('uid', '=', $uid)
			->where('petid', '=', $petId)
			->where('delete_time', '>', 0)
			->query();
		
		if ( $ret[DataDef::AFFECTED_ROWS] != 1 )
		{
			self::fatal('resetPet failed. uid:%d, petId:%d', $uid, $petId);
		}
	}
	
	public static function array_add($arr1,$arr2)
	{
		foreach($arr2 as $key => $value)
		{
			if(!isset($arr1[$key]))
			{
				$arr1[$key] = $value;
				continue;
			}
			if(is_int($value))
			{
				$arr1[$key] += $value;
			}
			else if(is_array($value))
			{
				foreach($value as $tid => $num)
				{
					if(!isset($arr1[$key][$tid]))
					{
						$arr1[$key][$tid] = 0;
					}
					$arr1[$key][$tid] += $num;
				}
			}
		}
		return $arr1;
	}
	
	public static function array_sub($arr1, $arr2)
	{
		foreach($arr2 as $key => $value)
		{
			if(!isset($arr1[$key]))
			{
				self::warn('not found key:%s in arr1', $key);
				continue;
			}
				
			if(is_int($value))
			{
				$arr1[$key] -= $value;
				if( $arr1[$key] < 0)
				{
					self::warn('key:%s in arr1 less then arr2. arr1:%s, arr2:%s', $key, $arr1, $arr2);
					unset($arr1[$key]);
				}
			}
			else if(is_array($value))
			{
				foreach($value as $tid => $num)
				{
					if(!isset($arr1[$key][$tid]))
					{
						self::warn('not found key:%s tid:%d in arr1', $key, $tid);
						continue;
					}
					$arr1[$key][$tid] -= $num;
					if( $arr1[$key][$tid] < 0 )
					{
						unset($arr1[$key][$tid]);
						self::warn('key:%s tid:%d in arr1 less then arr2. arr1:%s, arr2:%s', $key, $tid, $arr1, $arr2);
					}
				}
			}
		}
		return $arr1;
	}
	
	
	public static function getConfPath()
	{
		foreach( self::$arrConfPath as $confPath )
		{
			if( is_dir($confPath) )
			{
				return $confPath;
			}
		}
		return '';
	}
	
	public static function getItemName($itemTplId)
	{
		$confPath = self::getConfPath();
		if( empty( $confPath ) )
		{
			return '物品XXX';
		}
		$ret = exec("grep -E '^$itemTplId,' $confPath/item_* | awk  -F ',' '{print $3}' ");
		
		if( empty($ret) )
		{
			return '物品XXX';
		}
		
		$name = iconv('GBK', 'UTF-8', $ret);
		return $name;
	}
	public static function getHeroName($htid)
	{
		$confPath = self::getConfPath();
		if( empty( $confPath ) )
		{
			return '武将XXX';
		}
		$ret = exec("grep -E '^$htid,' $confPath/heroes.csv | awk  -F ',' '{print $3}' ");
		if( empty($ret) )
		{
			return '武将XXX';
		}
		
		$name = iconv('GBK', 'UTF-8', $ret);
		return $name;
	}
	public static function getPetName($tplId)
	{
		$confPath = self::getConfPath();
		if( empty( $confPath ) )
		{
			return '宠物XXX';
		}
		$ret = exec("grep -E '^$tplId,' $confPath/pet.csv | awk  -F ',' '{print $3}' ");
		if( empty($ret) )
		{
			return '宠物XXX';
		}
		
		$name = iconv('GBK', 'UTF-8', $ret);
		return $name;
	}
	public static function getXilianshiOfArmPotence($itemTplId, $armPotence)
	{
		$arrItem = self::getArmPotenceResolve($itemTplId, $armPotence);
		if( empty($arrItem) )
		{
			return 0;
		}
		if ( !isset($arrItem[60007]) )
		{
			self::fatal('no xilianshi. %s', $arrItem);
			return 0;
		}
		return $arrItem[60007];
	}
	public static function getArmPotenceResolve($itemTplId, $armPotence)
	{
		$sum = 0;
		foreach ( $armPotence as $attrId => $attrValue)
		{
			if ($attrValue < 0)
			{
				$attrValue = 0;
			}
			$sum += $attrValue;
		}
			
		$armResolve = ItemAttr::getItemAttr( $itemTplId, ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE);
	
		$arrItem = array();
		if (!empty($armResolve))
		{
			foreach ($armResolve as $tplId => $itemValue)
			{
				break;
			}
			$itemNum = intval($sum / $itemValue);
			if (!empty($itemNum))
			{
				$arrItem = array($tplId => $itemNum);
			}
		}
		return $arrItem;
	}
	
	/**
	 * 计算分解可能掉落什么东西
	 * @param int $resolveId
	 * @return array
	 */
	public static function getResolveValues($resolveId)
	{
		if( !isset(btstore_get()->ARM_RESOLVE[$resolveId]) )
		{
			self::fatal('invalid resolverId:%d', $resolveId);
			return array();
		}
		$resolve = btstore_get()->ARM_RESOLVE[$resolveId];

		$value = $resolve['armResolveValue'];
		$args = $resolve['armResolveArgs'];
		$num = $resolve['armResolveNum'];
		
		$arrDropItemTplId = array();
		foreach( $resolve['armResolveDrops'] as $dropId)
		{
			$ret = Drop::getDropInfo($dropId);
			if( count($ret) != 1 || !isset($ret[0]) )
			{
				self::fatal('invalid dropId:%d', $dropId);
				return array();
			}
			$arrDropItemTplId = array_merge($arrDropItemTplId, $ret[0]);
		}
		$arrDropItemTplId = array_merge( array_unique($arrDropItemTplId) );
		
		$sumValue = 0;
		$arrDropInfo = array();
		foreach($arrDropItemTplId as $itemTplId)
		{
			$arrDropInfo[] = array(
				'itemTplId' => $itemTplId,
				'value' => btstore_get()->ITEMS[$itemTplId]['value'],
			);
			$sumValue += btstore_get()->ITEMS[$itemTplId]['value'];
		}
		
		$avgNum = intval( $value/$sumValue*count($arrDropItemTplId) );
		
		return array(
			'minValue' => $args[2] * $value / 10000,
			'maxValue' => $value,
			'avgNum' => $avgNum,
			'arrDropInfo' => $arrDropInfo
		);
	}

	public static function getArgs($arrOption, $str)
	{
		$arr = preg_split("/[\s]+/", $str);
		$arrResult = array();
		$wait = null;
		foreach($arr as $v)
		{
			if( in_array($v, $arrOption) )
			{
				$wait = $v;
				$arrResult[$wait] = array();
			}
			else if( isset($wait) )
			{
				$arrResult[$wait] = $v;
				$wait = null;
			}
			else
			{
				return array();
			}
		}
		return $arrResult;
	}
	
	public static function baseLog($level, $arrArg)
	{
	
		foreach ( $arrArg as $idx => $arg )
		{
			if ($arg instanceof BtstoreElement)
			{
				$arg = $arg->toArray ();
			}
			if (is_array ( $arg ))
			{
				$arrArg [$idx] = var_export ( $arg, true );
			}
		}
		$msg = call_user_func_array ( 'sprintf', $arrArg );
	
		switch ($level)
		{
			case 'info':
				printf("%s\n", $msg);
				Logger::log(Logger::L_INFO, $arrArg, 2);
				break;
			case 'warn':
				printf("[WARN]%s\n", $msg);
				Logger::log(Logger::L_WARNING, $arrArg, 2);
				break;
			case 'fatal':
				printf("[ERROR]%s\n", $msg);
				Logger::log(Logger::L_WARNING, $arrArg, 2);
				break;
		}
	
	}
	

	public static function info()
	{
		$arrArg = func_get_args ();
		self::baseLog('info', $arrArg);
	}
	public static function warn()
	{
		$arrArg = func_get_args ();
		self::baseLog('warn', $arrArg);
		self::$foundWarn = true;
	}
	public static function fatal()
	{
		$arrArg = func_get_args ();
		self::baseLog('fatal', $arrArg);
		
		self::$foundErro = true;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
