<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

class SetCityWarForTest extends BaseScript
{
	protected static $dbHost = '192.168.1.91';
	protected static $dbName = 'pirate001';
	
	protected function executeScript ($arrOption)
	{	
		//到报名的间隔时间0.5分钟
		$gapStartSignup = 30;
		//报名2.5分钟
		$signupDuration = 150; 
		//报名到准备1分钟
		$gapSignupPrepare = 60;
		//准备2.5分钟
		$prepareDuration = 150;
		//战斗1.5分钟
		$attackDuration = 90;
		//战斗结束到发奖间隔时间0.5分钟
		$gapBattleReward = 30;
		//发奖时间2.5分钟
		$rewardDuration = 150;
		//默认一轮900秒，15分钟
		
		if( isset( $arrOption[0] ) )
		{
			$signupDuration = intval($arrOption[0]);
		}
		if( isset( $arrOption[1] ) )
		{
			$gapSignupPrepare = intval($arrOption[1]);
		}
		if( isset( $arrOption[2] ) )
		{
			$prepareDuration = intval($arrOption[2]);
		}
		if( isset( $arrOption[3] ) )
		{
			$attackDuration = intval($arrOption[3]);
		}
		if( isset( $arrOption[4] ) )
		{
			$rewardDuration = intval($arrOption[4]);
		}
		
		$gapSignupBattle = $gapSignupPrepare + $prepareDuration;
		$gapAttackAttack = $prepareDuration + $attackDuration;
		$battleDuration = $attackDuration + $prepareDuration + $attackDuration;
		$roundDuration = $gapStartSignup + $signupDuration + $gapSignupPrepare + $prepareDuration*2 + $attackDuration*2 + $gapBattleReward + $rewardDuration;
		printf("roundDuration:%d, gapStartSignup:%d, signupDuration:%d, gapSignupBattle:%d, prepareDuration:%d, gapAttackAttack:%d, attackDuration:%d, battleDuration:%d, gapBattleReward:%d, rewardDuration:%d\n",
			$roundDuration, $gapStartSignup, $signupDuration, $gapSignupBattle, $prepareDuration, $gapAttackAttack, $attackDuration, $battleDuration, $gapBattleReward, $rewardDuration);
		
		$arrMap = array(
				'ROUND_DURATION' => $roundDuration,
				'GAP_START_SIGNUP' => $gapStartSignup,
				'SIGNUP_DURATION' => $signupDuration,
				'GAP_SIGNUP_BATTLE' => $gapSignupBattle,
				'PREPARE_DURATION' => $prepareDuration,
				'GAP_ATTACK_ATTACK' => $gapAttackAttack,
				'ATTACK_DURATION' => $attackDuration,
				'BATTLE_DURATION' => $battleDuration,
				'GAP_BATTLE_REWARD' => $gapBattleReward,
				'REWARD_DURATION' => $rewardDuration,
		);
		
		foreach( $arrMap as $key => $value )
		{
			popen("/bin/sed -i '/$key/{s/[0-9-]\+/$value/;}' /home/pirate/rpcfw/conf/CityWar.cfg.php", 'r');
		}
		
		$this->dealTimer();
		$this->dealCityWar();
		
		printf("done\n");
	}
	
	public function dealCityWar()
	{
		$dbHost = self::$dbHost;
		$dbName = self::$dbName;
		system("mysql -upirate -padmin -h $dbHost -D$dbName -e 'delete from t_city_war'" );
		system("mysql -upirate -padmin -h $dbHost -D$dbName -e 'delete from t_city_war_attack'" );
		system("mysql -upirate -padmin -h $dbHost -D$dbName -e 'delete from t_city_war_user'" );
	}
	
	public function dealTimer()
	{
		$data = new CData();
		$arrField = array (
				'tid',
				'uid',
				'status',
				'execute_count',
				'execute_method',
				'execute_time',
				'va_args',
		);
		$arrTimerInfo = $data->select ( $arrField )->from ( 't_timer' )
			->where ( 'status', '=',TimerStatus::UNDO )
			->where ( 'execute_method', 'LIKE', 'citywar.%' )
			->query ();
		
		foreach ( $arrTimerInfo as $timerInfo )
		{
			TimerTask::cancelTask($timerInfo['tid']);
			$msg = sprintf("method:%s, time:%s, tid:%d, exist. cancel it",
					$timerInfo['execute_method'], date('Y-m-d H:i:s', $timerInfo['execute_time']), $timerInfo['tid']);
			printf("%s\n", $msg);
			Logger::fatal('%s', $msg);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */