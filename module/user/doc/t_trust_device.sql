set names utf8;

CREATE TABLE IF NOT EXISTS t_trust_device
(
	uid		int unsigned not null comment '用户id',
	va_info	blob not null comment '
		doneList=>array(deviceId=>array(taskid),
		taskInfo=>array(deviceId=>array(taskid=>donenum),
		sendMsgNum=>array(deviceId=>sendnum)	
		',			
	primary key(uid)
)engine = InnoDb default charset utf8;


