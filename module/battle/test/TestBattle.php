<?php

require_once (LIB_ROOT . '/RPCProxy.class.php');

class SimpeHero extends Creature
{
	protected $mArrSetAttr = array();
	
	public function __construct ($htid)
	{
		parent::__construct($htid);
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
}


class TestBattle extends BaseScript
{
	

	function __construct()
	{
	}
	

	protected function executeScript($arrOption)
	{
		//$this->testWithCreature();
		//$ret = $this->testPvp();
		$this->test_battleDamageIncre();
	}
	
	
	
	public function testPvp()
	{
		$teamInfo1 = array(
				array(
						'position' => 0,
						'htid' => 10006,
						'currRage' =>  100,
						),
				array(
						'position' => 1,
						'htid' => 10005,
						),

		);
		$teamInfo2 = array(
				array(
						'position' => 0,
						'htid' => 10006,
				),
				array(
						'position' => 1,
						'htid' => 10005,
				),

		);


		$arrFormation1 = $this->genTeam ( $teamInfo1, 1 );
		$arrFormation2 = $this->genTeam ( $teamInfo2, 2 );
		
		$arrHero1 = BattleUtil::unsetEmpty ( $arrFormation1 ['arrHero'] );
		$arrHero2 = BattleUtil::unsetEmpty ( $arrFormation2 ['arrHero'] );
		
		$arrFormation1 ['arrHero'] = $arrHero1;
		$arrFormation2 ['arrHero'] = $arrHero2;

		$type = 0;
		//结束条件
		$arrEndCondition = array ('dummy' => true );
		
		$arrHero1 = BattleUtil::prepareBattleFormation ( $arrFormation1 );
		$arrHero2 = BattleUtil::prepareBattleFormation ( $arrFormation2 );

		$proxy = new PHPProxy ( 'battle' );
		$ret = $proxy->doHero ( 
				$arrHero1,
				$arrHero2, 
				$type, $arrEndCondition, array('dummy' => true) );

		$replayData = $this->genReplayData($ret, $arrFormation1, $arrFormation2);
		
		$brid = IdGenerator::nextId ( "brid" );
		EnBattle::addRecord( $brid, $replayData );
		
		echo "replayData:[{$brid}]:".var_export( base64_encode($replayData),true)."\n";
		
		file_put_contents( '/tmp/battle_str',  var_export( Util::amfDecode( $replayData,true ) , true) );
	}
	

	private function genTeam($teamInfo, $teamId)
	{

		$arrDefValue= array (
			'maxHp' => 1000, 'level' => 90,
			'hit' => 10000, 'dodge' => 100, 'fatal' => 1000, 'fatalRatio' =>10000, 'parry' => 1000, 
			'reign' => 10, 'intelligence' => 10, 'strength' => 10, 'agile' => 10000,
	
			'currRage' => 0,
			'chaosSkill' => 7,
			'charmSkill' => 9,
			'attackSkill' => 1,
			'arrSkill' => array ( 120100 ), 		
			
			'physicalAttackBase' => 120, 'physicalAttackAddition' => 1, 'physicalAttackRatio' => 10000, 
			'physicalDefendBase' => 20, 'physicalDefendAddition' => 1, 
			'magicAttackBase' => 100, 'magicAttackAddition' => 1, 'magicAttackRatio' => 0, 
			'magicDefendAddition' => 1, 'magicDefendBase' => 20,
			'buffProbResisFaint' => 1000,
			'buffProbResisSilent' => 1000,
			'buffProbResisStopAddRage' => 1000,
			'buffProbResisSubRage' => 1000,
			'buffProbResisParalysis' => 1000,
			'buffProbResisStopAddHp' => 1000,
	
			//'equipInfo' => array(array(),array(),array(),array(),array(),array(),array(),array(),array()), 
		);
		$arrKeyType = array_merge(BattleDef::$ARR_BATTLE_KEY, BattleDef::$ARR_CLIENT_KEY);
		foreach($arrKeyType as $key => $type)
		{
			if( $type == 'int' && !isset($arrDefValue[$key]) )
			{
				$arrDefValue[$key] = 0;
			}
		}
		
		$arrHeroList = array ();
		
		$teamName = "team_$teamId";
		$index = 1;
		foreach($teamInfo as $hero)
		{
			$arrTmp = $arrDefValue;
			$arrTmp ['hid'] =  10000000 + $teamId *10 + $index;
			$arrTmp ['name'] = "hero_".($teamId*10 + $index);		

			foreach($hero as $key => $value)
			{
				$arrTmp[$key] = $value;
			}
			
			if( empty($hero['htid']) )
			{
				$ret = exec("egrep 'rageAtkSkill=\"{$arrTmp['rageSkill']}\"' /home/pirate/battle/data/heroes.xml | awk '{  if (match($0, /htid=\"([0-9]+)\"/)) printf(\"%s\", substr($0,RSTART+6,RLENGTH-7)); else printf(\"err\"); }'");
				if(empty($ret) )
				{
					echo("not found  htid with rageSkill:". $arrTmp['rageSkill'] ."\n");
					$arrTmp ['htid'] = 10018;
				}
				else
				{
					$arrTmp ['htid'] = $ret;
				}
			}	
			else
			{
				$arrTmp['htid'] = $hero['htid'];
			}
			
						
			$arrHeroList [] = $arrTmp;
			$index++;
		}

		$arrFormation = array (
				'name' => $teamName, 
				'uid' => $teamId, 				
				'level' => 90, 
				'flag' => 0, 
				'formation' => 10006, 
				'isPlayer' => true,
				'arrHero' => $arrHeroList,
				 );
		return $arrFormation;
	}

	public function genReplayData($battleRet, $arrFormation1, $arrFormation2)
	{
		$brid = 1;
		$arrClient = $battleRet['client'];
		
		$arrClient["reward"] = array(
					"belly" => 0,
					"prestige" =>0
				);
		$arrClient ['bgId'] = 28;
		$arrClient ['type'] = 8;
		$arrClient ['musicId'] = 38;
		$arrClient ['brid'] = $brid;
		$arrClient ['url_brid'] = BabelCrypt::encryptNumber ( $brid );
		$arrClient ['team1'] = BattleUtil::prepareClientFormation ( $arrFormation1,
				$battleRet ['server'] ['team1'] );
		$arrClient ['team2'] = BattleUtil::prepareClientFormation ( $arrFormation2,
				$battleRet ['server'] ['team2'] );
		$compressed = true;
		$data = Util::amfEncode ( $arrClient, $compressed, 0, BattleDef::BATTLE_RECORD_ENCODE_FLAGS );
		
		//var_dump($arrClient);
		return $data;
	}
	
	private function getRandomRageSkill()
	{

		$arrRageSkill = array (154, 159, 163, 167, 171, 175, 212 );
		$index = rand ( 0, count ( $arrRageSkill ) - 1 );
		return $arrRageSkill [$index];
	}

	private function getUserBattleData($uid)
	{
		$user = EnUser::getUserObj($uid);
		$user->prepareItem4CurFormation();
		$formationID = $user->getCurFormation();

		$userFormation = EnFormation::getFormationInfo($uid);

		$userFormationArr = EnFormation::changeForObjToInfo($userFormation, false);
		$userFormationArr = BattleUtil::unsetEmpty ( $userFormationArr );

		$formation = array('name' => $user->getUname(),
		                            'level' => $user->getLevel(),
		                            'isPlayer' => true,
		                            'flag' => 0,
		                            'formation' => $formationID,
		                            'uid' => $uid,
		                            'arrHero' => $userFormationArr);

		$formation = BattleUtil::prepareClientFormation ( $formation, array());

		$arrHero = BattleUtil::prepareBattleFormation ( $userFormationArr );


		//formation用于生成战报，arrHero用于调用battle模块 $formation['arrHero'] 和 arrHero中有几个重复字段，为了方便没有优化
		return array(
					'formation' => $formation,
					'arrHero' => $arrHero);
	}

	
	
	
	public function getBattleData($uid)
	{
		$user = EnUser::getUserObj($uid);
		$user->prepareItem4CurFormation();
		$formationID = $user->getCurFormation();
		$userFormation = EnFormation::getFormationInfo($uid);
		$userFormationArr = EnFormation::changeForObjToInfo($userFormation, true);

//		$userFormationArr = BattleUtil::unsetEmpty ( $userFormationArr );

		$formation = array('name' => $user->getUname(),
		                            'level' => $user->getLevel(),
		                            'isPlayer' => true,
		                            'flag' => 0,
		                            'formation' => $formationID,
		                            'uid' => $uid,
		                            'arrHero' => $userFormationArr);


		return $formation;
		
	}

	public function testRealPvp()
	{
		$formation1 = self::getBattleData(20178);
		$formation2 = self::getBattleData(23769);

		$bt = new Battle();
		$ret = $bt->doHero($formation1, 
								  $formation2, 
								  0, 
			                      null,
								  null, 
								  array('bgid' => ArenaConf::BATTLE_BJID,
								        'musicId' => ArenaConf::BATTLE_MUSIC_ID, 
										'isKFZ' => true,
								        'type' => BattleType::OLYMPIC),
								  null);
								  
		var_dump($ret['client']);
	}
	
	public function test_battleDamageIncre()
	{
		$arrFormation1 = EnUser::getUserObj(28820)->getBattleFormation();
		$arrFormation2 = EnUser::getUserObj(23223)->getBattleFormation();
		$ret = EnBattle::doHero($arrFormation1, $arrFormation2, 0, NULL, NULL, array('damageIncreType' => 2, 'damageIncreBeginRound' => 1, 'damageIncreCoef' => 1000));
		var_dump($ret);
	}
	
	//--------------------------
	public function testWithCreature()
	{
		$arrHtid1 = array(10001,10002,10003,10004,10005,10006);
		$arrHtid2 = array(20002,10012,10013,10014,10015,10016);
		$arrAttr = array(
			1 => 100000,
			9 => 20000,
		);
		
		$arrAttr = HeroUtil::adaptAttr($arrAttr);
		
		$ret = self::battleWithHtids($arrHtid1, $arrHtid2, $arrAttr, 1);
		
		var_dump($ret);
	}
	
	
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
	
	public static function genFormation($userInfo, $arrHtid, $arrAttr, $hidOffset = 20000000)
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
			$arrCreatureObj[$pos] = new SimpeHero($htid);
			
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
	public static function battleWithHtids($arrHtid1, $arrHtid2, $arrAttr, $roundNum)
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
		
		
		$formation1 = self::genFormation($userInfo1, $arrHtid1, $arrAttr);
		$formation2 = self::genFormation($userInfo2, $arrHtid2, $arrAttr);
	
	
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
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
