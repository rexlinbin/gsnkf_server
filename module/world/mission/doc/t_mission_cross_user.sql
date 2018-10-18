set names utf8;
create table if not exists t_mission_cross_user_1
(
        pid 		int unsigned not null comment 'pid',
        server_id 	int unsigned not null comment 'server_id',
		uname 		varchar(16) not null comment '玩家名称',
        fame 		int unsigned not null comment '当前轮次的名望',	
        update_time int unsigned not null comment '更新时间，取名次的时候使用',
        htid		int unsigned not null comment 'htid',
        vip			int unsigned not null comment 'vip',
        level		int unsigned not null comment 'level',
        va_cross_user	blob not null comment 'array( dress => array() )',
		
        primary key(pid,server_id),
        index update_time(update_time)
)engine = InnoDb default charset utf8;

drop procedure if exists add_mission_cross;
delimiter //
create procedure add_mission_cross(begin_i int,table_num int)
begin
        declare i int;
        declare table_name varchar(50);
        set i = begin_i;
        while i <= table_num do
                set table_name = concat('t_mission_cross_user_',i);
                SET @STMT := CONCAT("CREATE TABLE IF NOT EXISTS ",table_name," LIKE t_mission_cross_user_1;");
                PREPARE STMT FROM @STMT;
                EXECUTE STMT;
                set i = i + 1;
        end while;
end //
delimiter ;
call add_mission_cross(2,200);
drop procedure if exists add_mission_cross;