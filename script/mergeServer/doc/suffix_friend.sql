#将所有不存在于t_user的用户从friend表中删除
delete from t_friend where fuid not in (select uid from t_user);