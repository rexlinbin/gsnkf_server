<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RewardUtilTest.php 203206 2015-10-19 11:00:53Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/test/RewardUtilTest.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-10-19 11:00:53 +0000 (Mon, 19 Oct 2015) $
 * @version $Revision: 203206 $
 * @brief 
 *  
 **/
class RewardUtilTest extends PHPUnit_Framework_TestCase
{
	private $user;
	private $uid;
	private $utid;
	private $pid;
	private $uname;

	protected function setUp()
	{
		parent::setUp ();
		$this->pid = 40000 + rand(0,9999);
		$this->utid = 1;
		$this->uname = 't' . $this->pid;
		$ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
		$users = UserLogic::getUsers( $this->pid );
		$this->uid = $users[0]['uid'];
		Logger::debug( 'uid is::%s', $this->uid );
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown ();
	}

	public function test_allreward_0()
	{
		$console = new Console();
		$console->level( 80 );
		$console->gold( 1000 );
		GuildLogic::createGuild($this->uid, $this->uname, true, '12', '12', 'babel');
		
		
		$user = EnUser::getUserObj($this->uid);
		$bag = BagManager::getInstance()->getBag( $this->uid );
		$heroMgr = $user->getHeroManager();
		$fragseizeInst = FragseizeObj::getInstance( $this->uid );
		$guildMember = GuildMemberObj::getInstance( $this->uid );
		$guildId = EnGuild::getGuildId( $this->uid );
		$guildObj = GuildObj::getInstance($guildId);
		$passObj = PassObj::getInstance( $this->uid );
		
		$arr[1] = $user->getSilver();
		$arr[2] = $user->getSoul();
		$arr[3] = $user->getGold();
		$arr[5] = $user->getStamina();
		
		$arr[7][] = $bag->getItemNumByTemplateID( 50001 );
		$arr[7][] = $bag->getItemNumByTemplateID( 410001 );
		
		$arr[11] = $user->getJewel();
		$arr[12] = $user->getPrestige();
		
		$arr[13][] = $heroMgr->getHeroNumByHtid( 10026 );
		$arr[13][] = $heroMgr->getHeroNumByHtid( 10063 );
		
		$arr[14] = $fragseizeInst->getFragsByTid( 501301 );
		
		$arr[15] = $guildMember->getContriTotal();
		//$arr[16] = $guildObj->;
		//$arr[17] = cop;
		
		$arr[18] = $guildMember->getGrainNum();
		$arr[19] = $passObj->getCoin();
		
		$console->reward();

		$arrB[1] = $user->getSilver();
		$arrB[2] = $user->getSoul();
		$arrB[3] = $user->getGold();
		$arrB[5] = $user->getStamina();
		
		$arrB[7][] = $bag->getItemNumByTemplateID( 50001 );
		$arrB[7][] = $bag->getItemNumByTemplateID( 410001 );
		
		$arrB[11] = $user->getJewel();
		$arrB[12] = $user->getPrestige();
		
		$arrB[13][] = $heroMgr->getHeroNumByHtid( 10026 );
		$arrB[13][] = $heroMgr->getHeroNumByHtid( 10063 );
		
		$arrB[14] = $fragseizeInst->getFragsByTid( 501301 );
		
		$arrB[15] = $guildMember->getContriTotal();
		//$arrB[16] = $guildObj->;
		//$arrB[17] = cop;
		
		$arrB[18] = $guildMember->getGrainNum();
		$arrB[19] = $passObj->getCoin();
		
		foreach ( $arrB as $index => $val )
		{
			var_dump( "check index $index" );
			var_dump( "$val, $arr[$index]"  );
			if( $index == 7 || $index == 13 )
			{
				$this->assertTrue( $val[0] - $arr[$index][0] == 2 );
				$this->assertTrue( $val[1] - $arr[$index][1] == 2 );
			}
			elseif( $index == 14 )
			{
				Logger::debug('info1:%s',$val );
				$this->assertTrue( !empty( $val ) );
				Logger::debug('info2:%s', $arr[$index] );
				//$this->assertTrue( empty( $arr[$index] ) );
			}
			else 
			{
				$this->assertTrue( ($val - $arr[$index]) >= 2 );
			}
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */