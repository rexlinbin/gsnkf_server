<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class Robot extends RPCProxy
{
    protected $pid = 0;
    protected $uid = 0;

    protected $err = false;
    protected $reviveWhenDead = false;
    protected $lastAtkTime = 0;
    protected $errTryNum = 1;

    function __construct($server, $port, $pid)
    {
        parent::__construct($server, $port, true);
        $this->pid = $pid;
        $this->connect($server, $port);
        $this->setClass('user');
        $ret = $this->login($this->pid);
        MyLog::debug('pid:%d login. ret:%s', $pid, $ret);
        if(!is_array($ret) || $ret['res'] != 'ok')
        {
            throw new Exception('login failed');
        }

        $ret = $this->getUsers();
        MyLog::debug('getUsers. ret:%s', $ret);

        if(empty($ret))
        {
            throw new Exception('no user');
        }
        $this->uid = $ret[0]['uid'];

        $ret = $this->userLogin($this->uid);
        if($ret != 'ok')
        {
            throw new Exception('userLogin failed');
        }

        MyLog::info('pid:%d login ok', $pid);
        $this->setClass('worldarena');
    }

    public function getBasicInfo()
    {
        $ret = $this->getWorldArenaInfo();
        return $ret;
    }

    //报名
    public function trySignUp()
    {
        try
        {
            $ret = $this->signUp();
            MyLog::info("trySignUp ok, uid:[%d] pid:[%d] ret:%s", $this->uid, $this->pid, $ret);
        }
        catch (Exception $e)
        {
        	if ($e->getMessage() == 'already sign up') 
        	{
        		//MyLog::info("trySignUp skip, uid:[%d] pid:[%d] already sign up", $this->uid, $this->pid);
        	}
        	else 
        	{
        		MyLog::info("trySignUp failed, uid:[%d] pid:[%d] exception:%s", $this->uid, $this->pid, $e);
        	}
            
        }
    }

    //更新战斗信息
    public function tryUpdFmt()
    {
        try
        {
            $ret = $this->updateFmt();
            MyLog::info("tryUpdFmt ok, uid:[%d] pid:[%d] ret:%s", $this->uid, $this->pid, $ret);
        }
        catch (Exception $e)
        {
            MyLog::info("tryUpdFmt failed, uid:[%d] pid:[%d] exception:%s", $this->uid, $this->pid, $e);
        }
    }

    //购买攻击次数
    public function tryBuyAtkNum()
    {
        try
        {
            $ret = $this->buyAtkNum();
            MyLog::info("buyAtkNum ok, uid:[%d] pid:[%d] ret:%s", $this->uid, $this->pid, $ret);
        }
        catch (Exception $e)
        {
            MyLog::info("buyAtkNum failed, uid:[%d] pid:[%d] exception:%s", $this->uid, $this->pid, $e);
        }
    }

    //重置 战斗和回血
    public function tryReset()
    {
        try
        {
            $ret = $this->reset('gold');
            MyLog::info("reset ok, uid:[%d] pid:[%d] ret:%s", $this->uid, $this->pid, $ret);
        }
        catch (Exception $e)
        {
            MyLog::info("reset failed, uid:[%d] pid:[%d] exception:%s", $this->uid, $this->pid, $e);
        }
    }

    //攻击
    public function tryAttack($serverId, $pid)
    {
        $ret = array();
        try
        {
            $ret = $this->attack($serverId, $pid);
			MyLog::info("tryAttack ok, uid:[%d] pid:[%d], target serverid[%d] target pid[%d], ret[%s], appraisal[%s]", $this->uid, $this->pid, $serverId, $pid, $ret['ret'], $ret['ret'] == 'ok' ? $ret['appraisal'] : 'NULL');
        }
        catch (Exception $e)
        {
        	if ($e->getMessage() == 'no enough atk num')
        	{
        		MyLog::info("tryAttack failed, uid:[%d] pid:[%d] no enough atk num, reset atked num", $this->uid, $this->pid);
        		$this->setClass('console');
        		$this->execute('worldarena_setAtkedNum 0');
        		$this->setClass('worldarena');
        	}
        	else 
        	{
        		MyLog::info("tryAttack failed, uid:[%d] pid:[%d] exception:%s", $this->uid, $this->pid, $e);
        	}            
        }
        return $ret;
    }

    public function setReviveWhenDead($flag)
    {
        $this->reviveWhenDead = $flag;
        if($this->reviveWhenDead)
        {
            $this->setClass('console');
            $ret = $this->execute('gold 100000');
            $this->setClass('worldarena');
        }
    }

    public function atkMany($playerInfo)
    {
    	try 
    	{
    		while (TRUE)
    		{
    			$atkSuccess = FALSE;
    			foreach($playerInfo as $pos => $playInfo)
    			{
    				if($playInfo['self'] == 0/* && Util::getTime() > $playInfo['protect_time']*/)
    				{
    					$ret = $this->tryAttack($playInfo['server_id'], $playInfo['pid']);
    					$this->lastAtkTime = time();
    					$atkSuccess = TRUE;
    					break;
    				}
    			}
    			
    			if (!$atkSuccess || empty($ret)) 
    			{
    				break;
    			}
    			$playerInfo = $ret['player'];
    			//usleep(100000);
    		}
    		
    	} 
    	catch (Exception $e) 
    	{
    		MyLog::info("atkMany failed, uid:[%d] pid:[%d] exception:%s", $this->uid, $this->pid, $e);
    	}
    }

    public function run()
    {
        if($this->err)
        {
            MyLog::info('pid:%d err', $this->pid);
            return;
        }
        
        MyLog::info('pid:%d run begin', $this->pid);

        try
        {
            $ret = $this->getBasicInfo();
            if (empty($ret['extra']['player'])) 
            {
            	MyLog::fatal('pid:%d wanna attack, but not sign up', $this->pid);
            }
            else 
            {
            	$this->atkMany($ret['extra']['player']);
            }
        }
        catch (Exception $e)
        {
            MyLog::fatal('pid:%d, errNum:%d, err:%s', $this->pid, $this->errTryNum, $e->getMessage() );
            if($this->errTryNum > 0)
            {
                $this->errTryNum = $this->errTryNum - 1;
            }
            else
            {
                $this->err = true;
            }
        }
    }
}

class WorldArenaRobot extends BaseScript
{
    protected $port = 7777; //lcserver对外端口
    
    // 特殊的需要报名的玩家Pid
    public static $arrSpecPid = array
    (
    		'game10401' => array(33244,32828),
    );

    protected function executeScript($arrOption)
    {
        MyLog::init("/home/pirate/rpcfw/log/world_arean_robot");
        
        $path = sprintf('/card/lcserver/lcserver#%s', $this->group);
        $arrServerInfo = Util::getZkInfo($path);
        $this->serverIp = $arrServerInfo["wan_host"];
        $this->port = $arrServerInfo["wan_port"];
        
        $botNum = 10;
        if(isset($arrOption[0]))
        {
            $botNum = intval($arrOption[0]);
        }
        $offset = 0;
        if(isset($arrOption[2]))
        {
        	$offset = intval($arrOption[2]);
        }
        $arrPid = $this->getArrPidForRobot($botNum, $offset);
        
        if (isset(self::$arrSpecPid[$this->group])) 
        {
        	$arrPid = array_merge($arrPid, self::$arrSpecPid[$this->group]);
        }
        
        $arrRobot = array();
        foreach($arrPid as $pid)
        {
            try
            {
                $robot = new Robot($this->serverIp, $this->port, $pid);
            }
            catch (Exception $e)
            {
                MyLog::fatal('new Robot failed. uid:[%d] err:[%s]', $pid, $e->getMessage());
                continue;
            }
            $arrRobot[$pid] = $robot;
        }
        if(empty($arrRobot))
        {
            MyLog::fatal('no robot');
            return;
        }
        $ob = current($arrRobot);
        $stage = 0;
        
        // 仅仅报名
        if (isset($arrOption[1]) && $arrOption[1] == 'signup') 
        {
        	$ret = $ob->getBasicInfo();
        	$stage = $ret['stage'];
        	if($stage == 'signup')
        	{
        		foreach($arrRobot as $robot)
        		{
        			$robot->trySignUp();
        		}
        	}
        	else 
        	{
        		MyLog::info('not in sign up stage, can not sign up');
        	}
        	
        	MyLog::info("just signup over");
        }
        else
        {
        	while(true)
        	{
        		$ret = $ob->getBasicInfo();
        		$stage = $ret['stage'];
        		if($stage == 'signup')
        		{
        			foreach($arrRobot as $robot)
        			{
        				$robot->trySignUp();
        			}
        		}
        		else if($stage == 'attack')
        		{
        			foreach($arrRobot as $robot)
        			{
        				$robot->run();
        			}
        		}
        		else
        		{
        			MyLog::info('stage[%s], sleep...', $stage);
        		}
        		sleep(1);
        	}
        	
        	MyLog::info("attack over");
        }
    }

    public function getArrPidForRobot($num = 10, $offset = 0)
    {
        $arrDefPid = array();

        if(count($arrDefPid) >= $num)
        {
            return array_slice($arrDefPid, 0, $num);
        }

        $leftNum = $num - count($arrDefPid);

        $data = new CData();
        $arrRet = $data->select(array('pid'))->from('t_user')
                ->where('status', '=', UserDef::STATUS_OFFLINE)
                ->where('pid', '>', UserConf::PID_MAX_RETAIN)
                ->where('level', '>=', 80)
                ->orderBy('fight_force', FALSE)
                ->limit($offset, $num)
                ->query();
        $arrDbPid = Util::arrayExtract($arrRet, 'pid');
        MyLog::debug('arrDefPid:%s, arrDbPid:%s', $arrDefPid, $arrDbPid);

        $arrDbPid = array_diff($arrDbPid, $arrDefPid);
        $arrDbPid = array_slice($arrDbPid, 0, $leftNum);

        $arrPid = array_merge($arrDefPid, $arrDbPid );

        MyLog::info('addPid for Bot:%s', $arrPid);
        return $arrPid;
    }
}

class MyLog
{
    private static $fid;

    public static function init($filename)
    {
        self::$fid = fopen($filename, 'w');
    }

    private static function log($arrArg, $print = 0)
    {

        $arrMicro = explode ( " ", microtime () );
        $content = '[' . date ( 'Ymd H:i:s ' );
        $content .= sprintf ( "%06d", intval ( 1000000 * $arrMicro [0] ) );
        $content .= "]";

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
        $content .= call_user_func_array ( 'sprintf', $arrArg );
        $content .= "\n";

        if($print)
        {
            echo $content;
        }
        fprintf(self::$fid, $content);

    }
    public static function debug()
    {
        $arrArg = func_get_args ();
        self::log($arrArg, false);
    }
    public static function info()
    {
        $arrArg = func_get_args ();
        self::log($arrArg, true);
    }
    public static function fatal()
    {
        $arrArg = func_get_args ();
        self::log($arrArg, true);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */