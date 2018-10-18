#!/bin/sh

group=$1

#检查gsc目录是否存在
if [ -z /home/pirate/rpcfw/conf/gsc/$group/Game.cfg.php ]; then
	echo "GAME:$GAMEID GSC CONFIG NOT EXIST!";
	exit;
fi


echo -n "init user, press enter to start"
read p
/home/pirate/bin/btscript $group InitUser.php

echo -n "init miner, press enter to start"
read p
/home/pirate/bin/btscript $group InitMineralToDb.php

echo -n "init dart, press enter to start"
read p
/home/pirate/bin/btscript $group InitChargeDartRoad.php

echo -n "init lucky"
read p
/home/pirate/bin/btscript $group ArenaGenerateLuckyPosition.php

echo -n "init boss"
read p
/home/pirate/bin/btscript $group BossInit.class.php


echo -n "refresh activity conf"
read p
/home/pirate/bin/btscript $group ForceUpdateActivityConf.script.php  sync nocheck

echo -n "init arena"
read p
/home/pirate/bin/btscript $group ArenaGenerate.php  1

if [ -f "/home/pirate/rpcfw/script/ArenaGenerateSnap.php" ]; then
	echo -n "init arena_history"
	read p
	/home/pirate/bin/btscript $group ArenaGenerateSnap.php force
else
    echo "not found ArenaGenerateSnap.php, ignore"
fi	



