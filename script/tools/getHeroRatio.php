<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: getHeroRatio.php 69147 2013-10-16 06:56:34Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/getHeroRatio.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-10-16 06:56:34 +0000 (Wed, 16 Oct 2013) $
 * @version $Revision: 69147 $
 * @brief 
 *  
 **/
class getHeroRatio extends BaseScript
{
    private $dbHost=0;//数据库机器
    private $dbUser=0;//登录数据库用户名
    private $dbPwd=0;//登录数据库密码
    private $arrRatio = array();
    protected function executeScript ($arrOption)
    {
        if($arrOption[0] == 'help')
        {
            echo "usage::btscript game001 getHeroRatio.php
            user_num dbhost dbuser dbpwd\n";
            return;
        }
        if(count($arrOption) < 2)
        {
            echo "usage::btscript game001 getHeroRatio.php
            user_num dbhost dbuser dbpwd\n";
            return;
        }
        
        $userNum    =    PHP_INT_MAX;
        //统计unusedHero在所有的hero中的比例
        $userNum    =    intval($arrOption[0]);
        $this->dbHost = $arrOption[1];
        if(isset($arrOption[2]))
        {
            $this->dbUser = $arrOption[2];
        }
        if(isset($arrOption[3]))
        {
            $this->dbPwd  = $arrOption[3];
        }
        $proportion    =    array();//key是比例  value是此比例的数目
        $offset    =    0;
        while($offset < $userNum)
        {
            $arrUser    =    self::getArrUserHeroes($offset);
            if(empty($arrUser))
            {
                break;
            }
            //unusedhero
            $arrHeroNum1 = array();
            foreach($arrUser as $uid => $userInfo)
            {
                $arrHeroNum1[$uid] = count($userInfo['va_hero']['unused']);
            }
            $arrUid    =    array_keys($arrUser);
            $arrHeroNum2 = $this->getArrHeroByUids($arrUid, $this->dbHost, $this->db, 
                    $this->dbUser, $this->dbPwd);
            if(count($arrHeroNum1) != count($arrHeroNum2))
            {
                return 'err';
            }
            //$arrHeroNum1是unusedhero
            //$arrHeroNum2是hero表的
            foreach($arrHeroNum1 as $uid => $heroNum)
            {
                if($arrHeroNum2[$uid]-4 == 0)
                {
                    continue;
                }
                $ratio = $heroNum/($arrHeroNum2[$uid]-4);
                $this->arrRatio[] = $ratio;
            }
            $offset+=100;
        }
        $this->statistic();
    }
    
    private function statistic()
    {
        $statistic = array();
        foreach($this->arrRatio as $index => $ratio)
        {
            $str = "".$ratio;
            if(!isset($statistic[$str]))
            {
                $statistic[$str] = 0;
            }
            $statistic[$str]++;
        }
        array_reverse($statistic);
        ksort($statistic);
        var_dump($statistic);
    }
    /**
     * 以100为单位从数据库中取数据，一次拉取100个用户的数据
     * @param int $offset
     */
    private static function getArrUserHeroes($offset)
    {
        echo "enter getArrUserHeroes\n";
        $data    =    new    CData();
        $ret    =    $data->select(array('uid','va_hero'))
                          ->from('t_user')
                          ->where('uid', '>', FrameworkConfig::MIN_UID-1)
                          ->orderBy('level', FALSE)
                          ->limit($offset, DataDef::MAX_FETCH)
                          ->query();
        $users    =    Util::arrayIndex($ret, 'uid');
        return $users;  
    }

    /**
     * select uid,count(*) from t_hero where uid in (21902,21920) group by uid;
     * @param unknown_type $arrUid
     */
    private function getArrHeroByUids($arrUid,$dbHost,$db,$dbUser,$dbPwd)
    {
        if (empty($arrUid))
        {
            return array();
        }
        $uidStr    =    "";
        foreach($arrUid as $index=>$uid)
        {
            $uidStr = $uidStr .$uid. ',';
        }
        $uidStr = substr($uidStr, 0, strlen($uidStr) - 1);
        $sqlCmd    =    "select uid,count(*) from t_hero where uid in (".$uidStr.") group by uid";
        $command    =    '/home/pirate/bin/mysql ';
        if(!empty($this->dbUser))
        {
            $command =$command . "-u".$this->dbUser;
            if(!empty($this->dbPwd))
            {
                $command = $command." -p".$this->dbPwd;
            }
        }
        $command = $command . " -h ".$this->dbHost." ".$this->db." -e \"".$sqlCmd."\"";
        var_dump($command);
        $output = array();
        exec($command,$output);
        unset($output[0]);
        $ret    =    array();
        foreach($output as $index => $str)
        {
            $countInfo    =    array_map('intval', explode("\t", $str));
            $ret[$countInfo[0]] = $countInfo[1];
        }
        return $ret;
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */