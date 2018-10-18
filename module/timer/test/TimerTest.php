<?php

require_once MOD_ROOT . '/timer/index.php';

/**
 * Timer test case.
 */
class TimerTest extends PHPUnit_Framework_TestCase
{

	/**
	 * @var Timer
	 */
	private $Timer;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{

		parent::setUp ();
		$this->Timer = new Timer(/* parameters */);

	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{

		$this->Timer = null;
		parent::tearDown ();
	}

	/**
	 * Constructs the test case.
	 */
	public function __construct()
	{

	}

	/**
	 * Tests Timer->addTask()
	 */
	public function testAddTask()
	{

		$this->Timer->addTask ( 1, time () + 10, "test.broadcast", array (time () ) );
	}

	/**
	 * Tests Timer->cancelTask()
	 */
	public function testCancelTask()
	{

		$this->Timer->cancelTask ( 1 );
	}
	
	public function testGetArrTaskByName()
	{
		$now = Util::getTime();
		$this->Timer->addTask(1, $now + 1, 'test.method_1', array(1) );
		$this->Timer->addTask(1, $now + 2, 'test.method_1', array(2) );
		$this->Timer->addTask(1, $now + 3, 'test.method_1', array(3) );
		$this->Timer->addTask(1, $now + 3, 'test.method_2', array() );
		
		$arrRet = EnTimer::getArrTaskByName('test.method_1', array(), $now + 2);
		
		$this->assertEquals(2, count($arrRet));
		$this->assertEquals('test.method_1', $arrRet[0]['execute_method']);
		$this->assertEquals('test.method_1', $arrRet[1]['execute_method']);
		$this->assertEquals(array(2), $arrRet[0]['va_args']);
		$this->assertEquals(array(3), $arrRet[1]['va_args']);
		
		
		$arrRet = EnTimer::getArrTaskByName('test.method_1', array(TimerStatus::UNDO));
		
		$this->assertEquals(3, count($arrRet));
		$this->assertEquals('test.method_1', $arrRet[0]['execute_method']);
		$this->assertEquals('test.method_1', $arrRet[1]['execute_method']);
		$this->assertEquals('test.method_1', $arrRet[2]['execute_method']);

		
		$arrRet = EnTimer::getArrTaskByName('test.method_1', array(TimerStatus::FINISH));
		
		$this->assertEquals(0, count($arrRet));
	}

}
