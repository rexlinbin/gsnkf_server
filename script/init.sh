#!/bin/sh

group=$1
if [ "$group" = "" ]; then
    "Usage: sh init.sh group"
    exit 1
fi
model='normal'
if [ "$2" ]; then
	model=$2
fi

if [ "$model" == "check" ]; then
	btscript $group CheckInitGame.php
	ret=$?
	if [ $ret -ne 0 ]; then
		echo "init $group failed, please check!"
		exit 1
	else
		exit 0
	fi
fi

echo "init user, press enter to start"
if [ "$model" != "force" ]; then read p; fi
btscript $group InitUser.php

echo "init miner, press enter to start"
if [ "$model" != "force" ]; then read p; fi
btscript $group InitMineralToDb.php

echo "init lucky"
if [ "$model" != "force" ]; then read p; fi
btscript $group ArenaGenerateLuckyPosition.php

echo "init boss"
if [ "$model" != "force" ]; then read p; fi
btscript $group BossInit.class.php

echo "init arena_history"
if [ "$model" != "force" ]; then read p; fi
btscript $group ArenaGenerateSnap.php force

echo "init chargedart road"
if [ "$model" != "force" ]; then read p; fi
btscript $group InitChargeDartRoad.php

echo -n "clear activity cache"
if [ "$model" != "force" ]; then read p; fi
btscript $group ClearActivityCache.script.php
