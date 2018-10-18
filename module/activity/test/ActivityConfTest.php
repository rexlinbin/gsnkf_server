<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActivityConfTest.php 135485 2014-10-09 03:34:08Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/ActivityConfTest.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2014-10-09 03:34:08 +0000 (Thu, 09 Oct 2014) $
 * @version $Revision: 135485 $
 * @brief 
 *  
 **/


class LogicMock extends ActivityConfLogic
{
	public static $countGet = 0;
	
	
	public static $arrConf = array(
				array(
						'name' => ActivityName::SPEND,
						'version' => 1,
						'start_time' => 0,
						'end_time' => 123,
						'need_open_time' => 2370503089,
						'data' => 'csv data',
				),
				array(
						'name' => ActivityName::ARENA_REWARD,
						'version' => 1,
						'start_time' => 0,
						'end_time' => 123,
						'need_open_time' => 2370503089,
						'data' => 'csv data',
				),
			); 
	
	public static $arrDecodeResult = array(
				1 => array(
						'k1' => 'v11',
						'k2' => 'v12',
						'k3' => 'v13',
						),
			
				2 => array(
						'k1' => 'v21',
						'k2' => 'v22',
						'k3' => 'v23',
				),
			);
	
	
	public static function getConfFromPlatform($version)
	{
		Logger::debug('getConfFromPlatform:%s', self::$arrConf);
		self::$countGet++;
		return self::$arrConf;
	}
	
	public static function readCsvStr()
	{
		return self::$arrDecodeResult;
	}
}

class ActivityConfTest extends PHPUnit_Framework_TestCase
{

	protected static $pid = 0;
	protected static $uid = 100;
	
	public static function setUpBeforeClass()
	{
		
		RPCContext::getInstance ()->setSession ( 'global.uid', self::$uid );
	}


	protected function setUp()
	{
		//这里需要清除数据库才能测试
		self::clearData();
		ActivityConf::$ARR_READ_CONF_FUNC[ActivityName::SPEND] = 'LogicMock::readCsvStr';
		ActivityConf::$ARR_READ_CONF_FUNC[ActivityName::ARENA_REWARD] = 'LogicMock::readCsvStr';
		ActivityConf::$ARR_READ_CONF_FUNC[ActivityName::SIGN_ACTIVITY] = 'LogicMock::readCsvStr';
	}
	
	protected function tearDown()
	{
		
	}
	
	
	public function testDecodeConf()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		ActivityConf::$ARR_READ_CONF_FUNC[ActivityName::SPEND] = 'EnSpend::readSpendCSV';
		//依赖这个csv文件路径
		$fileContent = file_get_contents('/home/pirate/static/config/xiaofei_leiji.csv');
		/*
		$index = strpos($fileContent, "\n");
		$index = strpos($fileContent, "\n");
        $index = strpos($fileContent, "\n", $index + 1);
        $fileContent  = substr( $fileContent, $index+1);
        */
		$arrConf = array(
				array(
						'name' => ActivityName::SPEND,
						'version' => 1,
						'start_time' => 0,
						'end_time' => 2370503089,
						'need_open_time' => 2370503089,
						'data' => $fileContent,
				),
		);
		
		$ret = ActivityConfLogic::decodeConf($arrConf);
		
		Logger::debug('decodeResult:%s', $ret);
		$this->assertTrue(!empty($ret));
	}
	
	
	public function testInitByFront()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		/*  业务逻辑修改，此用例不可用
		$ret = LogicMock::getConf4Front();
		$this->assertTrue(!empty($ret));
		
		$ret = self::getAllData();
		$this->assertTrue(!empty($ret));
		*/
	}
	
	public function testInitByBackend()
	{
		Logger::debug('======%s======', __METHOD__ );		
		
		/* 业务逻辑修改，此用例不可用
		$ret = LogicMock::getConf4Backend(ActivityName::SPEND, 1);
		$this->assertTrue(!empty($ret));
		
		$ret = self::getAllData();
		$this->assertTrue(!empty($ret));
		*/
	}
	
	public function testRefreshByValidity()
	{
		Logger::debug('======%s======', __METHOD__ );
		
		LogicMock::doRefreshConf(0, true);
		$ret = LogicMock::getConf4Front();
		$curVersion = $ret['version'];
	
		$ret['validity'] = Util::getTime()-1;
		McClient::set(ActivityConfLogic::genMcKey4Front(), $ret);
	
		$arrConf = LogicMock::$arrConf;
		$newVersion = 0;
		foreach(LogicMock::$arrConf as $key => &$value)
		{
			$value['version']++;
			if( $newVersion < $value['version'] )
			{
				$newVersion = $value['version'];
			}
			unset($value);
		}
	
	
		$ret = LogicMock::getConf4Front();
	
		$arrCallback = RPCContext::getInstance()->getCallback();
		$this->assertTrue( !empty($arrCallback) );
		$callback = array_pop($arrCallback);
	
		$method = $callback['args'][1]['method'];
		$args = $callback['args'][1]['args'];
		$this->assertEquals('activity.doRefreshConf', $method);
		$this->assertEquals(array($curVersion, false), $args);
	
		$countGet = LogicMock::$countGet;;
		$ret = LogicMock::doRefreshConf($args[0], false);
		$this->assertEquals($countGet + 1, LogicMock::$countGet);		
		$this->assertEquals( $newVersion, $ret[ActivityConfLogic::genMcKey4Front()]['version']);
		
		
		//已经更新了就不要再更新了
		
		$ret = LogicMock::doRefreshConf($args[0], false);
		$this->assertEquals($countGet + 1, LogicMock::$countGet);
		
	}
	
	
	public function testStopInvalidRefreshByValidity()
	{
		Logger::debug('======%s======', __METHOD__ );
	
		LogicMock::doRefreshConf(0, true);
		$ret = LogicMock::getConf4Front();
		$curVersion = $ret['version'];

		$ret['validity'] = Util::getTime()-1;
		McClient::set(ActivityConfLogic::genMcKey4Front(), $ret);

		$ret = LogicMock::getConf4Front();
	
		$arrCallback = RPCContext::getInstance()->getCallback();
		$this->assertTrue( !empty($arrCallback) );
		$callback = array_pop($arrCallback);
	
		$method = $callback['args'][1]['method'];
		$args = $callback['args'][1]['args'];
		$this->assertEquals('activity.doRefreshConf', $method);
		$this->assertEquals(array($curVersion, false), $args);
	
		
		$countGet = LogicMock::$countGet;
		$ret = LogicMock::doRefreshConf($args[0], false);
		$this->assertEquals($countGet + 1, LogicMock::$countGet);

		//已经更新了就不要再更新了
		$ret = LogicMock::doRefreshConf($args[0], false);
		$this->assertEquals($countGet + 1, LogicMock::$countGet);		
	}
	
	public function testMutilUpdate()
	{
		Logger::debug('======%s======', __METHOD__ );
		self::clearData();
		
		LogicMock::doRefreshConf(0, true);
		$arrConf = LogicMock::$arrConf;
		list($updatedIndex, $conf) = each($arrConf);
		
		$updatedName = $conf['name'];
		$initVersion = $conf['version'];
		$trunkVersion = 0;
		$versions = array($initVersion);
		for( $i  = 0; $i < 3; $i++)
		{
			$fronConfInMem = LogicMock::getConf4Front();
			$curVersion = $fronConfInMem['version'];
			
			
			$fronConfInMem['validity'] = Util::getTime()-1;
			McClient::set(ActivityConfLogic::genMcKey4Front(), $fronConfInMem);
			
			$conf = LogicMock::$arrConf[$updatedIndex];
			$conf['version'] ++;
			$conf['start_time'] = $conf['version'];
			LogicMock::$arrConf[$updatedIndex] = $conf;
			$trunkVersion = $conf['version'];
			$versions[] = $trunkVersion;
			
			$countGet = LogicMock::$countGet;
			$ret = LogicMock::doRefreshConf($curVersion, false);
			$this->assertEquals($countGet + 1, LogicMock::$countGet);			
		}
		
		$ret = ActivityConfDao::getArrCurConf(ActivityConfLogic::getAllConfName(), ActivityDef::$ARR_CONF_FIELD);
		foreach($ret as $key => $value )
		{
			if( $key == $updatedName)
			{
				$this->assertEquals($trunkVersion, $value['version']);
			}
			else
			{
				$this->assertEquals($initVersion, $value['version']);
			}
		}
		
		$ret = self::getAllData();
		$versionList = array();
		foreach( $ret as $value )
		{
			$key = $value['name'];
			if( !isset( $versionList[ $key ]) )
			{
				$versionList[ $key ] = array($value['version']);
			}
			else
			{
				$versionList[$key][] = $value['version'];
			}			
		}
		
		foreach(LogicMock::$arrConf as $value)
		{
			$key = $value['name'];
			if( $key == $updatedName )
			{
				$this->assertEquals($versions, $versionList[$key]);	
			}
			else
			{
				$this->assertEquals(array($initVersion), $versionList[$key] );
			}
		}
	}
	
	public function testDefaultConf()
	{
		Logger::debug('======%s======', __METHOD__ );
		self::clearData();
		
		$conf = EnActivity::getConfByName(ActivityName::SPEND);
		$this->assertEquals(0, $conf['start_time']);
		$this->assertEquals(0, $conf['end_time']);
		
	}
	

	public static function clearData()
	{
		Logger::debug("clearData");
		$dbHost = '192.168.1.37';
		$dbName = ScriptConf::PRIVATE_DB;
		$ret = system("mysql -upirate -padmin -h $dbHost -D$dbName -e 'delete from t_activity_conf'" );
		
		$ret = self::getAllData();
		self::assertEmpty($ret);
		
		$ret = McClient::del(ActivityConfLogic::genMcKey4Front());
		
		foreach(ActivityConf::$ARR_READ_CONF_FUNC as $name => $func)
		{
			$ret = McClient::del(ActivityConfLogic::genMcKey($name));
		}
	}
	
	public static function getAllData()
	{
		$data = new CData();
		$ret = $data->select(ActivityDef::$ARR_CONF_FIELD)->from(ActivityConfDao::tblName)
				->where('version', '>=', 0)->query();

		return $ret;
	}
	
	
	public static function testAdaptActivity()
	{
		Logger::debug('testAdaptActivity');
		self::clearData();
		EnActivity::$confBuff = array();
		Logger::debug('clear done');
		
		$timeArr = array(
				array('20140920000001','20140925235959','20140922000001'),
				array('20140920000001','20140925235959','20140919000001'),//普通活动
				array('20140920000001','20140925235959','20140923000001'),//全服活动

		);
		//测试当天为22号开服时间分别设为 20140301（够早就行），20140907（卡当的），20140918 （新服）
		
		foreach ( $timeArr as $index => $arr )
		{
			foreach ( $arr as $index2=> $str )
			{
				$timeArr[$index][$index2] = strtotime( $str );
			}
		}
		
		LogicMock::$arrConf = array(
		 array(
		 //与新服活动冲突了的全服活动
				'name' => ActivityName::SPEND,
				'version' => 1,
				'start_time' => $timeArr[0][0],
				'end_time' => $timeArr[0][1],
				'need_open_time' => $timeArr[0][2],
				'data' => 'csv data',
		), 
		array(
		//普通活动
				'name' => ActivityName::ARENA_REWARD,
				'version' => 1,
				'start_time' => $timeArr[1][0],
				'end_time' => $timeArr[1][1],
				'need_open_time' => $timeArr[1][2],
				'data' => 'csv data',
		),
		array(
		//全服活动
				'name' => ActivityName::SIGN_ACTIVITY,
				'version' => 1,
				'start_time' => $timeArr[2][0],
				'end_time' => $timeArr[2][1],
				'need_open_time' => $timeArr[2][2],
				'data' => 'csv data',
		),
			
		);
		
		LogicMock::$arrDecodeResult = array(
				1 => array(
						'k1' => 'v11',
						'k2' => 'v12',
						'k3' => 'v13',
						),
			
				2 => array(
						'k1' => 'v21',
						'k2' => 'v22',
						'k3' => 'v23',
				),
				
				3 => array(
						'k1' => 'v31',
						'k2' => 'v32',
						'k3' => 'v33',
				),
			);
		
		LogicMock::doRefreshConf(0, true);
		
		$ret[] = EnActivity::getConfByName(ActivityName::SPEND);
		$ret[] = EnActivity::getConfByName(ActivityName::ARENA_REWARD);
		$ret[] = EnActivity::getConfByName(ActivityName::SIGN_ACTIVITY);	
		var_dump( 'backend' );
		var_dump( $ret );
		
		$ac = new Activity();
		$ret = $ac->getActivityConf(0);
		
		var_dump( 'front');
		var_dump( $ret);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */