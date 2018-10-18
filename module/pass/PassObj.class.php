<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PassObj.class.php 228163 2016-02-18 08:48:48Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/PassObj.class.php $
 * @author $Author: DuoLi $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-02-18 08:48:48 +0000 (Thu, 18 Feb 2016) $
 * @version $Revision: 228163 $
 * @brief 
 *  
 **/
class PassObj
{
	private static $instance = NULL;
	
	private $passInfo = array();
	private $passInfoBak = array();
	private $uid = NULL;
	
	public static function getInstance( $uid )
	{
		if( isset( self::$instance[$uid] ) )
		{
			return self::$instance[$uid];	
		}
		else
		{
			self::$instance[$uid] = new PassObj( $uid );
			return self::$instance[$uid];
		}
	}
	
	public static function releaseInstance( $uid )
	{
		if( isset( self::$instance[$uid] ) )
		{
			unset( self::$instance[$uid] );
		}
	}
	
	private function __construct( $uid )
	{
		$this->uid = $uid;
		
		if( empty( $this->passInfo ) )
		{
			$this->passInfo = PassDao::getPassInfo($uid, PassDef::$allFields);
			if( empty( $this->passInfo ) )
			{
				$this->passInfo = $this->initPass($uid);
			}
			if( empty( $this->passInfo ) )
			{
				throw new InterException( 'still no info after all process, uid: %s',$uid );
			}
		}
		
		$this->passInfoBak = $this->passInfo;
		$this->refreshPassByDay();
		$this->refreshIfDone();
	}
	
	private function initPass( $uid )
	{
		$initInfo = PassCfg::$initInfo;
		$initInfo['uid'] = $uid;
		PassDao::insertPassInfo($uid, $initInfo);
		
		return $initInfo;
	}
	
	private function refreshPassByDay()
	{
		$curDay = date( "Ymd", Util::getTime() );
		$offset = strtotime( $curDay.PassCfg::HANDSOFF_BEGINTIME )+PassCfg::HANDSOFF_LASTTIME - strtotime( $curDay."000000" );
		
		if( !Util::isSameDay(  $this->passInfo['refresh_time'], $offset ) )
		{
			$this->passInfo['refresh_time'] = Util::getTime();
			$this->passInfo['luxurybox_num'] = 0;
			$this->passInfo['cur_base'] = 0;
			$this->passInfo['reach_time'] = 0;
			$this->passInfo['pass_num'] = 0;
			$this->passInfo['point'] = 0;
			$this->passInfo['star_star'] = 0;
			$this->passInfo['buy_num'] = 0;
			$this->passInfo['lose_num'] = 0;
			$sweepInfo = self::getSweepInfo();
			$sweepInfo['isSweeped'] = false; //隔夜才能刷新扫荡
			$this->passInfo['va_pass'] = array('sweepInfo' => $sweepInfo);
			Logger::trace( 'refresh by day, last refresh time: %s', $this->passInfo['refresh_time'] );
		}
	}
	
	public function refreshIfDone()
	{
		if( $this->allBaseDone())
		{
			return;
		}
		
		if( $this->baseIsDone() )
		{
			$this->passInfo['luxurybox_num'] = 0;
			$this->passInfo['cur_base'] = self::getNextBase( $this->passInfo['cur_base'] );
			if( isset( $this->passInfo['va_pass'][PassDef::VA_OPPINFO] ) )
			{
				$this->passInfo['va_pass'][PassDef::VA_OPPINFO] = array();
			}
			if( isset( $this->passInfo['va_pass'][PassDef::VA_CHESTSHOW] ) )
			{
				$this->passInfo['va_pass'][PassDef::VA_CHESTSHOW] = array();
			}
			if( isset( $this->passInfo['va_pass'][PassDef::VA_BUFFSHOW] ) )
			{
				$this->passInfo['va_pass'][PassDef::VA_BUFFSHOW] = array();
			}
			Logger::trace( 'refresh cos done, after refresh: %s', $this->passInfo );
		}
	}
	
	
	public function getPassInfo()
	{
		return $this->passInfo;
	}

	public function getBase()
	{
		return $this->passInfo['cur_base'];
	}

	public function getPassNum()
	{
		return $this->passInfo['pass_num'];
	}
	
	public function getPoint()
	{
		return $this->passInfo['point'];
	}
	
	public function getCoin()
	{
		return $this->passInfo['coin'];
	}
	
	public function getReachTime()
	{
		return $this->passInfo['reach_time'];
	}

	public function getLuxuryNum()
	{
		return $this->passInfo['luxurybox_num'];
	}
	
	public function getRewardTime()
	{
		return $this->passInfo['reward_time'];
	}
	
	public function getBuyNum()
	{
		return $this->passInfo['buy_num'];
	}
	
	public function getLoseNum()
	{
		return $this->passInfo['lose_num'];
	}
	
	public function getVa()
	{
		return $this->passInfo['va_pass'];
	}
	
	public function getVaParticular( $vaKey )
	{
		if( isset( $this->passInfo['va_pass'][$vaKey] ) )
		{
			return $this->passInfo['va_pass'][$vaKey];
		}
		return array();
	}
	
	public function getEquip( $hidArr )
	{
		$equioInfoArr = array();
		foreach ( $hidArr as $index => $hid )
		{
			if( isset( $this->passInfo['va_pass'][PassDef::VA_HEROINFO][$hid][PassDef::EQUIP] ) )
			{
				$equioInfoArr[$hid] =  $this->passInfo['va_pass'][PassDef::VA_HEROINFO][$hid][PassDef::EQUIP];
			}
			else 
			{
				$equioInfoArr[$hid] = array();
			}
		}
		
		return $equioInfoArr;
	}
	
	
	public function addPassNum( $num )
	{
		$this->passInfo['pass_num'] += $num;
	}
	
	public function addPoint( $point )
	{
		$this->passInfo['point'] += $point;
		$this->passInfo['reach_time'] = Util::getTime();
	}
	
	public function getStar()
	{
		return $this->passInfo['star_star'];
	}
	
	public function addStar( $star )
	{
		$this->passInfo['star_star'] += $star;
	}
	
	public function subStar( $star )
	{
		if( $star < 0 )
		{
			throw new FakeException( 'sub minus !!' );
		}
		if( $this->passInfo['star_star'] < $star )
		{
			return false;
		}
		$this->passInfo['star_star'] -= $star;
		
		return true;
	}
	
	public function addLuxuryNum( $num )
	{
		$this->passInfo['luxurybox_num'] += $num;
	}
	

	public function subCoin( $num )
	{
		if( $num < 0 )
		{
			throw new FakeException( 'sub minus !!' );
		}
		if( $this->passInfo['coin'] < $num )
		{
			return false;
		}
		$this->passInfo['coin'] -= $num;
	
		return true;
	}
	
	public function addCoin( $num )
	{
		$this->passInfo['coin'] += $num;
	}
	
	public function addLoseNum($num)
	{
		$this->passInfo['lose_num'] += $num;
	}
	public function addBuyNum( $num )
	{
		$this->passInfo['buy_num'] += $num;
	}
	
	public function setVaParticular( $key, $info )
	{
		if( $key == PassDef::VA_BENCH || $key == PassDef::VA_FORMATION )
		{
			$info = PassLogic::filterFormation($info);
		}
		$this->passInfo['va_pass'][$key] = $info;
	}

	
	public function setRewardTime( $time )
	{
		$this->passInfo['reward_time'] = $time;
	}

	public function update()
	{
		$passNum = $this->passInfo['pass_num'];
		$curBaseId = $this->passInfo['cur_base'];
		$passNumInConf = self::getPassNumByConf( $curBaseId );
		if( $passNum > $passNumInConf )
		{
			throw new InterException( 'pass num not valid yours: %s, confs: %s, curBase: %s ', $passNum, $passNumInConf, $curBaseId );
		}
		
		if( $this->passInfo == $this->passInfoBak )
		{
			Logger::debug( 'nothing change, no need to update, info: %s', $this->passInfo );
			return;
		}
		
		$updateInfo = $this->passInfo;
		PassDao::updatePassInfo( $updateInfo['uid'] , $updateInfo);
		$this->passInfoBak = $this->passInfo;
		
	}
	
	public function allBaseDone()
	{
		$lastBaseId = self::getLastBase();
		if( $lastBaseId == $this->passInfo['cur_base']
			&& $this->baseIsDone() )
		{
			return true;
		}
		return false;
	}
	
	public function baseIsDone()
	{
		if( $this->passInfo['cur_base'] == 0 )
		{
			return true;
		}
		
		if( !$this->baseIsPass() )
		{
			return false;
		}
		
		if( !$this->chestIsDone() )
		{
			return false;
		}
		
		if( !$this->buffIsDone() )
		{
			return false;
		}
		
		return true;
		
	}
	
	public function baseIsPass()
	{
		$passNum = $this->passInfo['pass_num'];
		$curBaseId = $this->passInfo['cur_base'];
		$baseConf = btstore_get()->PASS_BASE->toArray();
		
		$passNumInConf = self::getPassNumByConf( $curBaseId );
		
		if( $passNum == $passNumInConf )
		{
			return true;
		}
		elseif( $passNum == $passNumInConf - 1 )
		{
			return false;
		}
		else
			throw new InterException( 'pass num not valid yours: %s, confs: %s, curBase: %s ', $passNum, $passNumInConf, $curBaseId );
	
	}
	
	public function chestIsDone()
	{
		if( $this->freeChestIsDone() && $this->goldChestIsDone() )
		{
			return true;
		}
		
		return false;
	}
	
	public function freeChestIsDone()
	{
		if( !isset( $this->passInfo['va_pass']['chestShow']['freeChest'] )
			|| $this->passInfo['va_pass']['chestShow']['freeChest'] == PassDef::CHEST_STATUS_DEAL )
		{
			return true;
		}
		
		return false;
	}
	
	public function goldChestIsDone()
	{
		if( !isset( $this->passInfo['va_pass']['chestShow']['goldChest'] )
		|| $this->passInfo['va_pass']['chestShow']['goldChest'] == PassDef::CHEST_STATUS_DEAL )
		{
			return true;
		}
		
		return false;
		
	}
	
	public function buffIsDone()
	{
	
		if( empty( $this->passInfo['va_pass']['buffShow'] ) )
		{
			return true;
		}
		
		foreach ( $this->passInfo['va_pass']['buffShow'] as $pos => $buffInfo )
		{
			if( $buffInfo['status'] == PassDef::BUFF_STATUS_UNDEAL )
			{
				return false;
			}
		}
		
		return true;
	}
	
	public function particularBuffIsDone( $pos )
	{
		if( empty( $this->passInfo['va_pass']['buffShow'][$pos] ) )
		{
			return true;
		}
		
		if( $this->passInfo['va_pass']['buffShow'][$pos]['status'] == PassDef::BUFF_STATUS_DEAL )
		{
			return true;
		}
		
		return false;
	}
	
	public static function getNextBase( $id )
	{
		$baseConf = btstore_get()->PASS_BASE->toArray();
		if( $id <= 0 )
		{
			return key( $baseConf );
		}
		
		$find = false;
		foreach ( $baseConf as $baseId => $baseInfo )
		{
			$nextId = $baseId;
			if( $find )
			{
				break;
			}
			if( $baseId == $id )
			{
				$find = true;
			}
		}
		
		return $nextId;
	}
	
	public static function getLastBase()
	{
		$baseConf = btstore_get()->PASS_BASE;
		$lastBaseId = 0;
		foreach ( $baseConf as $baseId => $baseInfo )
		{
			$lastBaseId = $baseId;
		}
		
		return $lastBaseId;
	}
	
	public function heroIsSet()
	{
		if( empty( $this->passInfo['va_pass']['heroInfo'] ) )
		{
			return false;
		}
		else
			return true;
	}
	
	public function allHeroDead()
	{
		if( empty( $this->passInfo['va_pass']['heroInfo'] ) )
		{
			return false;
			//这个地方逻辑是这样的，为空的话说明今天还一关也没打，所以英雄肯定没死
		}
		
		$all = $this->getHeroNum();
		$dead = $this->getDeadHeroNum();
		
		if( $dead == $all )
		{
			return true;
		}
		else if( $dead < $all )
		{
			return false;
		}
		else
		{
			throw new FakeException( 'fu*k..., dead num: %d, all num: %d', $dead, $all );
		}
	}
	
	public function getHeroNum()
	{
		if( empty( $this->passInfo['va_pass']['heroInfo'] ) )
		{
			return 0;
		}
	
		return count( $this->passInfo['va_pass']['heroInfo'] );
	}
	
	
	public function getDeadHeroNum()
	{
		if( empty( $this->passInfo['va_pass']['heroInfo'] ) )
		{
			return PassCfg::MAX_HERO_NUM;
		}
	
		$deadNum = 0;
		foreach (  $this->passInfo['va_pass']['heroInfo']  as $hid => $info  )
		{
			if( $info[PassDef::HP_PERCENT] <=0 )
			{
				$deadNum++;
			}
		}
	
		return $deadNum;
	}
	
	public function getPassNumByConf( $baseIdCheck )
	{
		//如果当前的这一关都打通了的话一共通关了多少关
		$baseConf = btstore_get()->PASS_BASE;
		$find = false;
		$passNumInConf = 0;
		foreach ( $baseConf as $baseId => $baseInfo )
		{
			$passNumInConf++;
			if( $baseIdCheck == $baseId )
			{
				$find = true;
				break;
			}
		}
		if( !$find )
		{
			throw new InterException( 'invalid id: %s, not found in conf', $baseIdCheck );
		}

		return $passNumInConf;
	
	}
	
	public function getSweepInfo()
	{
		if(empty($this->passInfo['va_pass'][PassDef::VA_SWEEPINFO]))
		{
			return array(
				'count' => 0,
				'isSweeped' => false,
				'buyChest' => 0,
				'buyBuff' => 0
			);
		}
		else
		{
			return $this->passInfo['va_pass'][PassDef::VA_SWEEPINFO];
		}
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */