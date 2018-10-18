<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FightHeros.php 158708 2015-02-12 09:07:46Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/FightHeros.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-02-12 09:07:46 +0000 (Thu, 12 Feb 2015) $
 * @version $Revision: 158708 $
 * @brief 
 *  
 **/
require_once (LIB_ROOT . '/RPCProxy.class.php');

class SimpeHero extends Creature
{
	protected $mArrSetAttr = array();
	protected $mHid = 0;
	protected $mEvolve = 0;
	
	public function __construct ($htid, $hid, $evolve)
	{
		parent::__construct($htid);
		$this->mHid = $hid;
		$this->mEvolve = $evolve;
	}
	
	public function init()
	{
		parent::init();
		
		foreach( $this->mArrSetAttr as $key => $value )
		{
			if( !isset( $this->creatureInfo[$key] ) )
			{
				throw new InterException('invalid key:%s', $key);
			}
			$this->creatureInfo[$key] =  $value;
		}
	}
		
	public function setArrAttr($arrAttr)
	{
		$this->mArrSetAttr = $arrAttr;
	}
	
	public function getHid()
	{
		return $this->mHid;
	}
	
 	public function getEvolveLv()
    {
        return $this->mEvolve;
    }
}

class TestBattle 
{
	public static function getAllProfitOfHtid($htid)
	{
		$unionProfit = Creature::getCreatureConf($htid, CreatureAttr::UNION_PROFIT);
		if (empty($unionProfit))
		{
			return array();
		}
		
		$arrAttr = array();
		foreach ($unionProfit as $unionId)
		{
			$attrInfo = btstore_get()->UNION_PROFIT[$unionId];
			$arrAttr[] = $attrInfo['arrAttr'];
		}
		
		$arrAttr = Util::arrayAdd2V($arrAttr);
		
		return HeroUtil::adaptAttr($arrAttr);
	}
	
	public static function genFormation($userInfo, $arrHtid, $arrAttr, $evolve, $hidOffset = 20000000)
	{
		
		$arrCreatureObj = array ();
		$unionProfit = array();
		foreach($arrHtid as $pos => $htid)
		{
			if( empty($htid) )
			{
				continue;
			}
			
			Logger::debug('create SimpleHero:%d', $htid);
			$arrCreatureObj[$pos] = new SimpeHero($htid, $hidOffset + $htid, $evolve);
			
			//$ret = $arrCreatureObj[$pos]->getBattleInfo();
			//Logger::debug('init htid:%d, maxHp:%d', $htid, $ret['maxHp']);
			
			$arrCreatureObj[$pos]->setArrAttr($arrAttr);
			
			//$ret = $arrCreatureObj[$pos]->getBattleInfo();
			//Logger::debug('after setattr. htid:%d, maxHp:%d', $htid, $ret['maxHp']);
			
			//连携全开
			$unionProfit[$htid] = self::getAllProfitOfHtid($htid);
			
			$arrCreatureObj[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_UNIONPROFIT, $unionProfit[$htid]);
		}
	
		$arrCreatureInfo = array();
		for($i = 0; $i < FormationDef::FORMATION_SIZD; ++ $i)
		{
			if (isset ( $arrCreatureObj[$i] ) && ($arrCreatureObj [$i] instanceof Creature) )
			{
				$arrCreatureInfo[$i] = $arrCreatureObj[$i]->getBattleInfo();
				$arrCreatureInfo[$i]['position'] = $i;
			}
		}
		
		$fightForce = 0;
		foreach($arrCreatureInfo as $pos => $heroInfo)
		{
			$fightForce += $heroInfo[PropertyKey::FIGHT_FORCE];
		}
		
		$returnData = array(
				'uid' => $userInfo['uid'],
				'name' => $userInfo['name'],
				'level' => $userInfo['level'],
				'isPlayer' => true,
				'squad' => array(),
				'littleFriend' => array(),
				'arrHero' => $arrCreatureInfo,
				'fightForce' => $fightForce,
		);
		
		return $returnData;
	}
	
	/**
	 *
	 * @param array $arrHtid1
	 * 		{ pos => htid}
	 * @param array $arrHtid2
	 * @param array $arrAttr
	 * 		{ strKey => value }
	 * @param int $roundNum
	 */
	public static function battleWithHtids($arrHtid1, $arrHtid2, $arrAttr, $evolve, $roundNum)
	{
		$userInfo1 = array(
			'uid' => 20001,
			'name' => 'test1',
			'level' => 1,
		);
		$userInfo2 = array(
			'uid' => 20002,
			'name' => 'test2',
			'level' => 1,
		);
		
		
		$formation1 = self::genFormation($userInfo1, $arrHtid1, $arrAttr, $evolve);
		$formation2 = self::genFormation($userInfo2, $arrHtid2, $arrAttr, $evolve);
	
	
		$arrResult = array();
		
		for($i = 0; $i < $roundNum; $i++)
		{
			$ret = EnBattle::doHero($formation1, $formation2);
			
			$arrResult[] = array(
				'brid' => $ret['server']['brid'],
				'appraisal' => $ret['server']['appraisal'],
			);
		}
		
		return $arrResult;
	}
}

class FightHeros extends BaseScript
{
	protected function executeScript($arrOption)
	{
		if (empty($arrOption[0]))
		{
			echo "No input file!\n";
			return;
		}
		$inFileName = $arrOption[0];
		$confList = $this->readInFile($inFileName);
		Logger::info('conf:%s', $confList);
		$outFileName = 'outdata.csv';
		echo "Warning:please inform tianming to clear log later!!!\n";
		
		foreach ($confList as $id => $conf)
		{
			//需要设置的属性
			$arrAttr = HeroUtil::adaptAttr($conf['attr']);
			$arrAttr[PropertyKey::LEVEL] = $conf['level'];
			
			//固定防守方，变化攻击方
			$arrDefendHtid = $conf['defend'];
			$defends = implode(',', $arrDefendHtid);
			$allZuhe = $this->zuhe($conf['attack'], FormationDef::FORMATION_SIZD - count($conf['keepa']));
			$i = 1;
			foreach ($allZuhe as $zuhe)
			{
				$arrAttackHtid = array_merge($conf['keepa'], $zuhe);
				$allPailie = $this->pailie($arrAttackHtid, FormationDef::FORMATION_SIZD);
				echo "count:".count($allPailie)."\n";
				foreach ($allPailie as $pailie)
				{
					echo "record:".$i."\n";
					$attacks = implode(',', $pailie);
					$arrRet = TestBattle::battleWithHtids($arrAttackHtid, $arrDefendHtid, $arrAttr, $conf['evolve'], $conf['round']);
					$arrBrid = Util::arrayExtract($arrRet, 'brid');
					$brids = implode(',', $arrBrid);
					$arrAppraisal = Util::arrayExtract($arrRet, 'appraisal');
					$arrWin = array();
					foreach ($arrAppraisal as $appraisal)
					{
						$arrWin[] = BattleDef::$APPRAISAL[$appraisal] <= BattleDef::$APPRAISAL['D'] ? 1 : 0;
					}
					$wins = implode(',', $arrWin);
					$total = array_sum($arrWin);
					
					$line = $i++.",,".$brids.",,".$wins.",,".$total.",,".$attacks.",,".$defends."\n";
					$this->writeOutFile($outFileName, $line);
				}
			}
		}
		echo "done\n";
	}
	
	public function readInFile($fileName)
	{
		$arrConfKey = array(
				'id' => 0,
				'level' => 1,
				'evolve' => 2,
				'attr' => 3,
				'round' => 4,
				'attack' => 5,
				'defend' => 6,
				'keepa' => 7,
		);
		$arrKeyV1 = array('attack', 'defend', 'keepa');
		$arrKeyV2 = array('attr');
		
		$file = fopen($fileName, 'r');
		$data = fgetcsv($file);
		$data = fgetcsv($file);
		$confList = array();
		while (true)
		{
			$data = fgetcsv($file);
			if (empty($data) || empty($data[0]))
			{
				break;
			}
		
			$conf = array();
			foreach ($arrConfKey as $key => $index)
			{
				if (in_array($key, $arrKeyV2, true))
				{
					if (empty($data[$index]))
					{
						$conf[$key] = array();
					}
					else 
					{
						$arr = $this->str2array($data[$index]);
						$conf[$key] = array();
						foreach ($arr as $value)
						{
							$ary = $this->str2Array($value, '|');
							$conf[$key][$ary[0]] = $ary[1];
						}
					}	
				}
				else if(in_array($key, $arrKeyV1, true))
				{
					if (empty($data[$index]))
					{
						$conf[$key] = array();
					}
					else
					{
						$conf[$key] = $this->str2array($data[$index]);
					}
				}
				else 
				{
					$conf[$key] = intval($data[$index]);
				}
			}
			$confList[$conf['id']] = $conf;
		}
		fclose($file);
		return $confList;
	}
	
	public function writeOutFile($fileName, $line)
	{
		$file = fopen($fileName, 'a+');
		fwrite($file, $line);
		fclose($file);
	}

	public function str2Array($str, $delimiter = ',')
	{
		if(trim($str) == '')
		{
			return array();
		}
		return explode($delimiter, $str);
	}
	
	//M选N全排列组合
	public function pailie($a, $n) 
	{  
		$res = array();
        $this->getCombinesOfPailie($a, $n, 0, array(), 0, $res); 
        return $res; 
    }  
  
    //排列组合
    public function getCombinesOfPailie($a, $n, $begin, $b, $index, &$res) 
    {  
    	//如果够n个数了，输出b数组
        if($n == 0)
        {  
            $this->getAllPailie($b, 0, $res);//得到b的全排列  
            return;  
        }  
              
        for($i = $begin; $i < count($a); $i++)
        {  
            $b[$index] = $a[$i];  
            $this->getCombinesOfPailie($a, $n-1, $i+1, $b, $index+1, $res);  
        }   
    }  
    
    //排列
    public function getAllPailie($a, $index, &$res)
    {   
        if($index == count($a) - 1)
        {  
        	$res[] = $a;
            return;  
        }  
          
        for($i = $index; $i < count($a); $i++)
        {   
        	$this->swap($a, $index, $i);
            $this->getAllPailie($a, $index+1, $res);  
            $this->swap($a, $index, $i); 
        }  
    }  
   
    //交换
    public function swap(&$a, $i, $j) 
    {  
        $temp = $a[$i];  
        $a[$i] = $a[$j];  
        $a[$j] = $temp;  
    }
    
    public function zuhe($a, $n)
    {
    	$res = array();
    	$this->getCombinesOfZuhe($a, $n, 0, array(), 0, $res);
    	return $res;
    }
    
    //组合
    public function getCombinesOfZuhe($a, $n, $begin, $b, $index, &$res)
    {
    	//如果够n个数了，输出b数组
    	if($n == 0)
    	{
    		$res[] = $b;
    		return;
    	}
    
    	for($i = $begin; $i < count($a); $i++)
    	{
	    	$b[$index] = $a[$i];
	    	$this->getCombinesOfZuhe($a, $n-1, $i+1, $b, $index+1, $res);
    	}
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */