<?php
/**
 * Created by PhpStorm.
 * User: hanshijie
 * Date: 15/9/25
 * Time: 15:42
 */
class GetGodWeaponWashTimes extends BaseScript
{

    /**
     * uid => array[
     *  0 => [1,2,] 洗练层
     *
     * ]
     */
    private $arrWashInfo = array();
    //
    private $arrUid = array();
    //匹配到的logid
    private $arrLogid = array();

    /**
     * 1,先从线上把日志拿下来 一天存一个
     * @param $arrOption
     */
    protected function executeScript($arrOption)
    {
        if(count($arrOption) < 3)
        {
            printf("Usage: btscript gamexxx GetGodWeaponWashTimes.php buyListPath logicLogDir dataLogDir");
            return;
        }

        $logicLogDir = $arrOption[0]; //logicLog的路径
        $dataLogDir = $arrOption[1]; //

        $buyListPath = $arrOption[2];

        $arrLogicFile = $this->getFilesInDir($logicLogDir);
        sort($arrLogicFile);

        foreach($arrLogicFile as $logicFileName)
        {
            $dataFileName = 'split_' . substr($logicFileName, 4, 4);

            if(FALSE == is_file($dataLogDir . '/' . $dataFileName))
            {
                continue;
            }
            $logicFilePath = $logicLogDir . '/' . $logicFileName;
            $dataFilePath = $dataLogDir . '/' . $dataFileName;
            var_dump($logicFilePath);
            var_dump($dataFilePath);

            //多进程跑
            $this->processLogicLog($logicFilePath);

        }

    }

    public function getFilesInDir($dir)
    {
        $files = array();
        //$d文件描述符
        $d = opendir($dir);
        while(1)
        {
        	$file = readdir($d);
        	if($file === FALSE)
        	{
				break;        		
        	}
            if($file == '.' || $file == '..')
            {
                continue;
            }
            if(is_dir($dir . '/'. $file))
            {
                continue;
            }
            $files[] = $file;
        }
        return $files;
    }

    public function processLogicLog($logicFilePath)
    {
        //匹配时间 + logid + gameId + uid
       	//$pattern = "#.+\[([0-9]+ [0-9]+\:[0-9]+\:[0-9]+).+logid:[0-9]+.+group:game([0-9\_]+).+uid:([0-9]+).+method:godweapon.wash, err:ok.+#";
        $pattern = "";
    	$file = fopen($logicFilePath, 'r');
        if(empty($file))
        {
            echo sprintf("open file:%s failed\n", $logicFilePath);
            exit(0);
        }
        while(!feof($file))
        {
            $line = fgets($file);
            $line = trim($line);
            if(empty($line))
            {
                continue;
            }

            $arrMatch = array();
            echo $line;
            if(preg_match($pattern, $line, $arrMatch))
            {
                var_dump($arrMatch);
                $logid = $arrMatch[2];
                $uid = intval($arrMatch[4]);
                if(!in_array($uid, $this->arrUid))
                {
                    continue;
                }
                $this->arrLogid[] = $logid;
            }
        }
    }
}