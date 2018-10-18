<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DivineObj.class.php 259385 2016-08-30 07:52:08Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/divine/DivineObj.class.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-30 07:52:08 +0000 (Tue, 30 Aug 2016) $
 * @version $Revision: 259385 $
 * @brief 
 *  
 **/
class DivineObj
{

	private $diviInfo;
	private $diviInfoBack;
	private $uid;
	private static $instance = NULL;
	
	/**
	 * 获取唯一实例
	 * @return DivineObj
	 */
	public static function getInstance( $uid=0 )
	{
		if ( self::$instance == null)
		{
			self::$instance = new self( $uid );
		}
		return self::$instance;
	}
	
	public static function release()
	{
		Logger::trace("DivineObj-release!");
		if (self::$instance != null)
		{
			self::$instance = null;
		}
	}

	
	private function __construct( $uid )
	{
		Logger::trace("DivineObj-construction for user $uid");
		if ( !EnSwitch::isSwitchOpen( SwitchDef::DIVINE ) )
		{
			throw new FakeException( 'divine is not switched' );
		}
		$guid = RPCContext::getInstance()->getUid();
		if ( $uid != $guid )
		{
			throw new FakeException( 'not cur user: %d', $uid);
		}
			$this->uid = $guid;
			$this->diviInfo = RPCContext::getInstance()->getSession( DivineDef::$DIVI_SESSION_KEY );
			if ( empty( $this->diviInfo ) )
			{
				$this->diviInfo = DivineDao::getDiviInfo( $this->uid );
				if ( empty( $this->diviInfo ) )
				{
					$this->diviInfo = $this->initDivi( $this->uid );
				}
				if ( empty( $this->diviInfo ) )
				{
					throw new SysException( 'uid:%d, ini divine err , may fail to insert to db' );
				}
				RPCContext::getInstance()->setSession( DivineDef::$DIVI_SESSION_KEY , $this->diviInfo );
			}
			
		//专为造假
		// ！！！新手引导模式（DivineDef::FAKE = 1）+ 旧的'refresh_time'（与当前时间不是同一天）的情况下，会导致玩家领完奖之后，占星数据被重置，但是已经领取的奖励仍然有效，策划说这个属于正常逻辑。 MingmingZhu 20160830
		if ( !isset( $this->diviInfo[ 'va_divine' ][ DivineDef::FAKE  ] ) )
		{
			Logger::trace("DivineObj adaptRefresh now.");
			$this->adaptRefresh();//刷新
			Logger::trace("DivineObj adaptRefresh finished.");
		}
		elseif ( $this->diviInfo[ 'va_divine' ][ DivineDef::FAKE  ] != 1 )
		{
			throw new InterException( 'fake in va is: %d, not 1', $this->diviInfo[ 'va_divine' ][ DivineDef::FAKE  ]  );
		}
		
		//备份
		$this->diviInfoBack = $this->diviInfo;
	}
	
	public function initDivi( $uid )
	{
		Logger::trace("DivineObj-initDivi now.");
		//专为造假
		return $this->fakeInitDivi( $uid );
		
		$targetStars =DivineUtil::refreshTargStars( DivineCfg::INI_PRIZE_LEVEL );
		$currentStars = DivineUtil::refreshCurStars();
		$ligntArrNum = count( $targetStars );
		$lightedArr = array_fill( 0 ,$ligntArrNum , 0);
		
		$INI_VALUES = array(
				'uid' 						=> $uid,
				'divi_times' 				=> 0,
				'refresh_time' 				=> Util::getTime(),
				'free_refresh_num' 			=> DivineCfg::INI_FREE_REFRESH_NUM,
				'prize_step' 				=> 0,
				'target_finish_num' 		=> 0,
				'integral' 					=> 0,
				'prize_level' 				=> 1,
				'ref_prize_num'				=> 0,
				'va_divine' 				=> array( 
						DivineDef::TARGET => $targetStars,
						DivineDef::CURRENT => $currentStars,
						DivineDef::LIGHTED => $lightedArr,
				),
		);
		DivineDao::addNewDivine( $INI_VALUES );
		return $INI_VALUES;
	}
	
	public function fakeInitDivi( $uid )
	{
		Logger::trace("DivineObj-fakeInitDivi now.");
		$targetStars =DivineUtil::refreshTargStars( DivineCfg::INI_PRIZE_LEVEL );
		$targetStars = array(1,2,3,4);
		$allAsters = array();
		$sampleArr = btstore_get()->DIVI_ASTER[ 'sample_arr' ];
		foreach ( $sampleArr as $id => $val )
		{
			$allAsters[] = $id;
		}
		foreach ( $targetStars as $key => $val )
		{
			$targetStars[ $key ] = $allAsters[ $key ];
		}
		$currentStars = DivineUtil::refreshCurStars();
		$fakePos = 1;
		foreach ( DivineCfg::$fakePosArr as $val )
		{
			if ( $targetStars[ $val ] != 1 )
			{
				$fakePos = $val;
				break;
			}
		}
		//$currentPos = rand( 0 , DivineCfg::CURRENT_STARS_NUM-1);
		$currentStars[ $fakePos ] = $targetStars[ $fakePos ];
		$ligntArrNum = count( $targetStars );
		$lightedArr = array_fill( 0 ,$ligntArrNum , 0);
		
		$INI_VALUES = array(
				'uid' 						=> $uid,
				'divi_times' 				=> 0,
				'refresh_time' 				=> Util::getTime(),
				'free_refresh_num' 			=> DivineCfg::INI_FREE_REFRESH_NUM,
				'prize_step' 				=> 0,
				'target_finish_num' 		=> 0,
				'integral' 					=> 0,
				'prize_level' 				=> 1,
				'ref_prize_num'				=> 0,
				'va_divine' 				=> array( 
						DivineDef::TARGET 	=> $targetStars,
						DivineDef::CURRENT 	=> $currentStars,
						DivineDef::LIGHTED 	=> $lightedArr,
						DivineDef::FAKE 	=> 1,
				),
		);
		DivineDao::addNewDivine( $INI_VALUES );
		Logger::trace("DivineObj-fakeInitDivi finished.");
		return $INI_VALUES;
	}
	
	public function adaptRefresh()
	{
		if ( !Util::isSameDay( $this->diviInfo[ 'refresh_time' ]  ) )
		{
			Logger::trace("DivineObj-adaptRefresh, `refresh_time` is not the same day of today! Begin to undrewPrize and refresh!");
			$remainPrizeArr = $this->undrewPrize();
			
			//这把奖励存了下来，先进行了占星的刷新，再进行奖励的发放，这样修改成立的条件是，在refresh的时候已经update了
			$this->refresh();
			if ( !empty( $remainPrizeArr ) )
			{
				DivineUtil::sendToRewardCenter( $this->uid , $remainPrizeArr );
			}
		}
	}
	
	public function undrewPrize()
	{
		Logger::trace("DivineObj-undrewPrize start...");
		$prizeNumTotal = 0;
		$allConf = btstore_get()->DIVI_PRIZE;
		if ( !isset( $allConf[ $this->diviInfo[ 'prize_level' ] ] ) )
		{
			throw new ConfigException( 'Dear cehua: user has reached lv: %d while conf is not set!, 
					so tell zhanshiyu if you would like to amend data or just reset the conf if you prefer, thanks', 
					$this->diviInfo[ 'prize_level' ]);
		}
		$prizeConf = $allConf[ $this->diviInfo[ 'prize_level' ]][ 'integ_arr' ];
		if ( empty( $prizeConf ) ) 
		{
			throw new InterException( 'no data of divi conf integ_arr' );
		}
		
		foreach ( $prizeConf as $val )
		{
			if ( $this->diviInfo[ 'integral' ] >= intval( $val ) )
			{
				$prizeNumTotal++;
			}
			else
			{
				break;
			}
		}
		$remainPrize = array();
		if ( $this->diviInfo[ 'prize_step' ] < $prizeNumTotal )
		{
			//没有领完，返回没有领取的奖励
			if ( $this->diviInfo['prize_level']  == 1)//TODO 1级的一定不roll
			{
				$prizeArr = btstore_get()->DIVI_PRIZE[ $this->diviInfo[ 'prize_level' ]][ 'prize_arr' ];
			}
			else 
			{
				$prizeArr = $this->getNewReward();
			}
			
			if ( empty( $prizeArr ) )
			{
				throw new InterException( 'no data from conf or va lv is: %d ',$this->diviInfo[ 'prize_level' ] );
			}
			foreach ( $prizeArr as $key => $val )
			{
				if ( empty( $val ) )
				{
					throw new ConfigException( 'no data in divi conf prize_arr step: %d' , $$key );
				}
				if ( $key >= $this->diviInfo[ 'prize_step' ] && $key <= $prizeNumTotal-1 )
				{
					$remainPrize[] = $val;
				}
			}
			Logger::trace("DivineObj-undrewPrize finished.");
			return $remainPrize;
		}
		else if ( $this->diviInfo[ 'prize_step' ] == $prizeNumTotal )
		{
			Logger::trace("DivineObj-undrewPrize finished.");
			return $remainPrize;
		}
		else
		{
			//做个检查吧
			throw new InterException
			( 'divi prize times: %d beyond the limitation: %d' , $this->diviInfo[ 'prize_step' ] ,$prizeNumTotal );
		}
	}
	
	public function refresh()
	{
		Logger::trace("DivineObj-refresh finished.");
		$tarArr = DivineUtil::refreshTargStars(  $this->diviInfo[ 'prize_level' ]);
		$ligntArrNum = count( $tarArr );
		$lightedArr = array_fill( 0 ,$ligntArrNum , 0);
		$this->diviInfo[ 'refresh_time' ] 			= Util::getTime();
		$this->diviInfo[ 'divi_times' ] 			= 0;
		$this->diviInfo[ 'free_refresh_num' ] 		= DivineCfg::RESET_FREE_REFNUM;
		$this->diviInfo[ 'prize_step' ] 			= 0 ;
		$this->diviInfo[ 'target_finish_num' ] 		= 0 ;
		$this->diviInfo[ 'integral' ] 				= 0 ;
		$this->diviInfo[ 'ref_prize_num' ]			= 0 ;
		$this->diviInfo[ 'va_divine' ][ DivineDef::TARGET ] = $tarArr;
		$this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ] = $lightedArr;
		if ($this->diviInfo['prize_level'] > 1)
		{
			$this->diviInfo[ 'va_divine' ][ DivineDef::NEWREWRD ] = DivineUtil::refreshReward( $this->diviInfo['prize_level'] );
		}
		
		self::update( $this->diviInfo );
	}
	
	
	public function getDiviInfo()
	{
		return $this->diviInfo;
	}
	
	public function getDiviTimes()
	{
		return $this->diviInfo[ 'divi_times' ];
	}
	
	public function getVaCurStar()
	{
		return $this->diviInfo['va_divine'][ DivineDef::CURRENT ];
	}
	
	public function getVaTarStar()
	{
		return $this->diviInfo['va_divine'][ DivineDef::TARGET ];
	}
	
	public function getLightedArr()
	{
		return $this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ];
	}

	public function getNewReward()
	{
		$newRewardArr = array();
		
		$level = $this->diviInfo['prize_level'];
		
		if ( !isset( $this->diviInfo['va_divine'][DivineDef::NEWREWRD] ) )
		{
			throw new InterException( 'user have no new reward, prize lv:%d', $level );
		}
		$newRewardList = $this->diviInfo['va_divine'][DivineDef::NEWREWRD];
		
		$prizeConf = btstore_get()->DIVI_PRIZE[$level];
		
		foreach ( $newRewardList as $posInVa => $posInConf )
		{
			$newRewardArr[$posInVa] = array(
					$prizeConf['newReward'][$posInVa][$posInConf]['type'],
					$prizeConf['newReward'][$posInVa][$posInConf]['val'],
					$prizeConf['newReward'][$posInVa][$posInConf]['num'],
			);
			
		}
		
		return $newRewardArr;
	}
	
	public function getTarIntegral()
	{
		$finishTimes =  $this->diviInfo[ 'target_finish_num' ] ;
		$prizeLevel = self::getLevel();
		if ( !isset( btstore_get()->DIVI_PRIZE[ $prizeLevel ] [ 'tar_aster_arr' ][$finishTimes][ 1 ] ) )
		{
			throw new ConfigException( 'no integral of finish times: %s',  $this->diviInfo[ 'target_finish_num' ]);
		}
		return  btstore_get()->DIVI_PRIZE[ $prizeLevel ] [ 'tar_aster_arr' ][$finishTimes][ 1 ]; 
	}
	
	public function getFreeRefNum()
	{
		return $this->diviInfo[ 'free_refresh_num' ];
	}
	
	public function getPrizeNum()
	{
		return $this->diviInfo[ 'prize_step' ];
	}
	
	public function getIntegral()
	{
		return $this->diviInfo[ 'integral' ];
	}
	
	public function getLevel()
	{
		return $this->diviInfo[ 'prize_level' ];
	}
	
	public function getRefPrizeNum()
	{
		return $this->diviInfo[ 'ref_prize_num' ];
	}
	
	public function minusFreeRefNum( $num = 1 )
	{
		$this->diviInfo[ 'free_refresh_num' ]-= $num;
	}
	
	public function upgrade()
	{
		$this->diviInfo[ 'prize_level' ]++;
		//$this->diviInfo[ 'integral' ] = DivineCfg::RESET_INTEGRAL;
		//$this->diviInfo[ 'prize_step' ] = DivineCfg::RESET_PRIZE_STEP;
		$this->diviInfo['va_divine'][DivineDef::NEWREWRD] = DivineUtil::refreshReward($this->diviInfo[ 'prize_level' ]);
	}

	public function addPrizeStep( $step = 1 )
	{
		//专为造假
		if ( isset( $this->diviInfo[ 'va_divine' ][ DivineDef::FAKE ] ) )
		{
			unset( $this->diviInfo[ 'va_divine' ][ DivineDef::FAKE ] );
		}
		$this->diviInfo[ 'prize_step' ] += $step;
	}
	
	public function addTarStarLignted( $lightPos )
	{
		$this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ][ $lightPos ] = 1;
		$lightedNum = count( $this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ] );
		foreach ( $this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ] as $val )
		{
			if ( $val ==0  )
			{
				return;
			}
		}
		$integralNum = self::getTarIntegral();
		self::finishTar($integralNum);
	}
	
	public function setVaCurStar()
	{
		$this->diviInfo[ 'va_divine' ][ DivineDef::CURRENT ] = DivineUtil::refreshCurStars();
		
		//专为造假
		if ( isset( $this->diviInfo[ 'va_divine' ][ DivineDef::FAKE ] ))
		{
			$unLightIndex = -1;
			$lightedArr = $this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ];
			
			foreach ( DivineCfg::$fakePosArr as $val )
			{
				if ( $lightedArr[ $val ] != 1 )
				{
					$unLightIndex = $val;
					break;
				}
			}
			
			$currentPos = $unLightIndex; //rand( 0 , DivineCfg::CURRENT_STARS_NUM-1 );
			if ( $unLightIndex != -1 )
			{
				$this->diviInfo[ 'va_divine' ][ DivineDef::CURRENT ][ $currentPos ] = 
				$this->diviInfo[ 'va_divine' ][ DivineDef::TARGET ][ $unLightIndex ];
			}
		}
	}
	
	public function setVaTarStar( $level , $num )
	{
		$this->diviInfo[ 'va_divine' ][ DivineDef::TARGET ] = DivineUtil::refreshTargStars( $level, $num );
	}
	
	public function finishCur( $integralNum )
	{
		$this->diviInfo[ 'divi_times' ]++;
		$this->diviInfo[ 'integral' ] += $integralNum;
		$this->setVaCurStar();
	}
	
	public function finishTar( $integralNum )
	{
		$this->diviInfo[ 'integral' ] += $integralNum;
		$this->diviInfo[ 'target_finish_num' ]++;
		self::setVaTarStar( $this->diviInfo[ 'prize_level' ] , $this->diviInfo[ 'target_finish_num' ]  );
		$newTarNum = count( $this->diviInfo[ 'va_divine' ][ DivineDef::TARGET ] );
		$this->diviInfo[ 'va_divine' ][ DivineDef::LIGHTED ] = array_fill( 0 , $newTarNum, 0);
	}

	public function refPrize()
	{
		if ( !isset( $this->diviInfo[ 'va_divine' ][ DivineDef::NEWREWRD ] ) )
		{
			throw new InterException( 'undefine newreward' );
		}
		$this->diviInfo[ 'ref_prize_num' ] ++;
		$newerReward = DivineUtil::refreshReward( $this->diviInfo['prize_level'] );
		$peizeStep = $this->diviInfo['prize_step'];
		foreach ( $this->diviInfo[ 'va_divine' ][ DivineDef::NEWREWRD ] as $key => $reward )
		{
			if ( $key >= $peizeStep )
			{
				$this->diviInfo[ 'va_divine' ][ DivineDef::NEWREWRD ][$key] = $newerReward[$key];
			}
		}
		
		return $this->diviInfo[ 'va_divine' ][ DivineDef::NEWREWRD ];
	}
	
	public function update()
	{
		$updateFields = array();
		
		if ( empty( $this->diviInfoBack ) )
		{
			$updateFields = $this->diviInfo;
		}
		else 
		{
			foreach ( $this->diviInfo as $key => $val )
			{
				if ( $this->diviInfo[ $key ] != $this->diviInfoBack[ $key ] )
				{
					$updateFields[ $key ] = $val;
				}
			}
		}
		
		if ( empty( $updateFields ) )
		{
			Logger::info( 'nothing change in Divi' );
			//没有数据改变，无需setsession
			return ;
		}
		else 
		{
			Logger::trace("DivineObj-update, update Dao start...");
			DivineDao::updateDiviInfo($this->uid, $updateFields);
			Logger::trace("DivineObj-update, update Dao finished.");
		}
		if ( RPCContext::getInstance()->getUid() == $this->uid )
		{
			Logger::trace("DivineObj-update, set session start...");
			RPCContext::getInstance()->setSession( DivineDef::$DIVI_SESSION_KEY , $this->diviInfo );
			Logger::trace("DivineObj-update, set session finished.");
		}
	}
	
	public function doOneClickDivine($integral, $diviTimes)
	{
		$this->diviInfo['integral'] = $integral;
		$this->diviInfo['divi_times'] = $diviTimes;
		$this->diviInfo['target_finish_num'] = $diviTimes;
		$this->diviInfo['free_refresh_num'] = 0;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */