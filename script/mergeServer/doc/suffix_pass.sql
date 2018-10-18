

set names utf8;

drop procedure if exists `safeUpdateTable`;

delimiter $$

create procedure safeUpdateTable(
tableName varchar(32),
updateSql varchar(256) )
begin
	select count(*) into @tableNum from information_schema.TABLES where table_name = tableName;
	if @tableNum > 0 then
		set @sqlStr = updateSql;
		prepare stmt from @sqlStr;
		execute stmt;
		deallocate prepare stmt;
	else
		select concat('ERRO: ', tableName, ' not exit') as '';
	end if;

end; $$

delimiter ;


call safeUpdateTable("t_pass", "update t_pass set refresh_time = 0, reach_time = 0;");


drop procedure if exists `safeUpdateTable`;


