#!/bin/bash

GAMEID=$1
GAMEDB=$2

#检查gsc目录是否存在
if [ -z /home/pirate/rpcfw/conf/gsc/game$GAMEID/Game.cfg.php ]; then
	echo "GAME:$GAMEID GSC CONFIG NOT EXIST!";
fi

PHPEXEC="/home/pirate/programs/php/bin/php /home/pirate/rpcfw/lib/ScriptRunner.php"
#处理Boss初始化工作
$PHPEXEC -f /home/pirate/rpcfw/script/BossInit.class.php -g $GAMEID -d $GAMEDB
#处理工匠离开时间初始化工作
$PHPEXEC -f /home/pirate/rpcfw/script/ArtificerLeaveTimeInit.php -g $GAMEID -d $GAMEDB
#处理世界资源初始化工作
$PHPEXEC -f /home/pirate/rpcfw/script/WorldResourceInit.class.php -g $GAMEID -d $GAMEDB