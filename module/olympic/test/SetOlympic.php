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



/**
 * btscript gamexxx SetOlypic.php set 
 * @author wuqilin
 *
 */
class SetOlympic extends BaseScript
{

	public function help()
	{
		printf("set startTime[60 或者 11:00] preSignGap signDuration signFightGap\n");
	}
	protected function executeScript ($arrOption)
	{
		if( count($arrOption) <  1)
		{
			$this->help();
			return;
		}
		$op = $arrOption[0];
		switch ($op)
		{
			case 'set':
				if( count($arrOption) < 5 )
				{
					$this->help();
					return;
				}
				$startTime = $arrOption[1];
				$preSignGap = intval($arrOption[2])*60;
				$signDuration = intval($arrOption[3])*60;
				$signFightGap = intval($arrOption[4])*60;
				//$fightGap = intval($arrOption[5])*60;
				
				$now = time();
				//设定延迟时间
				if( is_numeric($startTime) )
				{
					$startTime = ceil($now/60.0)*60 + intval($startTime*60);
				}
				else if( is_string($startTime) )
				{
					$startTime = strtotime( sprintf('%s %s:00', date('Y-m-d'), $startTime) );
				}
				else
				{
					printf("invalid startTime:%s\n", $startTime);
					return;
				}
				$this->setOlympicTime($startTime, $preSignGap, $signDuration, $signFightGap);
				break;
			case 'reset':
				$startTime = strtotime(date("Y-m-d")) + OlympicStage::PRE_OLYMPIC_START;
				$preSignGap = OlympicStage::PRELIMINARY_MATCH_START - OlympicStage::PRE_OLYMPIC_START;
				$signDuration =  OlympicStage::PRELIMINARY_MATCH_TIME;
				$signFightGap = OlympicStage::PRELIMINARY_FIGHT_GAP;
				//$fightGap = OlympicStage::FIGHT_GAP;
				$this->setOlympicTime($startTime, $preSignGap, $signDuration, $signFightGap);
				break;
			default:
				printf("invalid op:%s\n", $op);
				return;
		}
		
		printf("done\n");
	}
	
	public function setOlympicTime($startTime, $preSignGap, $signDuration, $signFightGap)
	{
		
		
		
		$dayBreak = strtotime(date("Y-m-d"));
		$preStartSecond = $startTime - $dayBreak;
		$signStartSecond = $preStartSecond + $preSignGap;
		
		$fightStartTime = $startTime + $preSignGap + $signDuration + $signFightGap;
		
		$arrTime = array(
			OlympicStage::PRE_OLYMPIC => $startTime,
			OlympicStage::PRELIMINARY_MATCH => $startTime + $preSignGap,
			OlympicStage::OLYMPIC_GROUP => $startTime + $preSignGap + $signDuration,
		);
		for( $stage = OlympicStage::SIXTEEN_FINAL; $stage < OlympicStage::AFTER_OLYMPIC; $stage++ )
		{
			$st = $fightStartTime;
			$i = OlympicStage::SIXTEEN_FINAL;
			while($i < $stage)
			{
				$st += OlympicStage::$ARR_FIGHT_DURATION[$i];
				$i++;
			}
			if( $st >= $dayBreak + 86400 )
			{
				printf("stage:%d next day:%s\n", $stage, date('Y-m-d H:i:s', $st) );
				return;
			}
			$arrTime[$stage] = $st;
		}
		$arrTime[OlympicStage::AFTER_OLYMPIC] = $fightStartTime + array_sum(OlympicStage::$ARR_FIGHT_DURATION);
		

		$tmpFile = '/tmp/crontab.setolympic';
		$bakFile = sprintf('/tmp/crontab.%s', date('Ymd-H-i-s'));

		$ret = exec( sprintf("crontab -l > %s  && cat %s",  $bakFile, $bakFile), $arrRet );
		if( !empty($arrRet[ count($arrRet)-1 ]) )
		{
			$arrRet[] = "";
		}

		$allCronStr = implode( "\n", $arrRet);
		$arrHas = array();
		foreach( $arrTime as $stage => $cronLine)
		{
			if( preg_match(sprintf('/OlympicScript.php %d/', $stage), $allCronStr) )
			{
				$arrHas[] = $stage;
			}
		}

		if( count($arrHas) > 0 )
		{
			if(  count($arrHas) != count($arrTime)  )
			{
				printf("the org cron wrong, pleas check\n");
				return;
			}
			else
			{
				foreach( $arrTime as $stage => $st )
				{
					$patten = sprintf('/[0-9 ]+ (\* \* \* .*\/OlympicScript\.php %d)/', $stage);
					$rep = sprintf('%s $1', date('i H', $st));
					$ret = preg_replace($patten, $rep, $allCronStr);
					
					if( empty($ret) )
					{
						printf("preg_replace erro\n");
						return;
					}
					$allCronStr = $ret;
				}
			}
		}
		else 
		{
			$allCronStr .= "\n#olympic\n";
			foreach($arrTime as $stage => $st)
			{
				$cronLine = sprintf("%s * * *  \$OFFSEBTSCRIPT \$SCRIPT_ROOT/OlympicScript.php %d", date('i H', $st), $stage);
				$allCronStr .= "$cronLine\n";
			}
		}
		printf("allCronStr:\n%s\n", var_export($allCronStr,true));
	
		file_put_contents($tmpFile, $allCronStr);
		
		exec( sprintf("crontab %s",  $tmpFile) );

		popen("/bin/sed -i '/const PRE_OLYMPIC_START/{s/[0-9]\+/$preStartSecond/}' /home/pirate/rpcfw/def/Olympic.def.php", 'r');
		popen("/bin/sed -i '/const PRELIMINARY_MATCH_START/{s/[0-9]\+/$signStartSecond/}' /home/pirate/rpcfw/def/Olympic.def.php", 'r');
		popen("/bin/sed -i '/const PRELIMINARY_MATCH_TIME/{s/[0-9]\+/$signDuration/}' /home/pirate/rpcfw/def/Olympic.def.php", 'r');
		
		popen("/bin/sed -i '/const PRELIMINARY_FIGHT_GAP/{s/[0-9]\+/$signFightGap/}' /home/pirate/rpcfw/def/Olympic.def.php", 'r');
		//popen("/bin/sed -i '/const FIGHT_GAP/{s/[0-9]\+/$fightGap/}' /home/pirate/rpcfw/def/Olympic.def.php", 'r');
		
		
		$offset = GameConf::BOSS_OFFSET;
		printf("game offset:%s\n", $offset);
		printf("olympic start:%s\n", date('Y-m-d H:i:s', $startTime+$offset) );
		printf("sign    start:%s\n", date('Y-m-d H:i:s', $startTime+$preSignGap+$offset) );

	}
	
	public function isNeedCleanDate()
	{
		
	}
}



/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */