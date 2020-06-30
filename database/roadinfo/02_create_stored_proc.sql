DELIMITER &&
DROP PROCEDURE IF EXISTS explode && 
CREATE PROCEDURE explode(pDelim VARCHAR(2), pStr TEXT)
BEGIN
  DROP TABLE IF EXISTS temp_explode;
  CREATE TEMPORARY TABLE IF NOT EXISTS temp_explode(
	  id INT AUTO_INCREMENT PRIMARY KEY NOT NULL, 
	  item VARCHAR(40)
  );
  SET @sql := CONCAT('INSERT INTO temp_explode (item) VALUES (', REPLACE(QUOTE(pStr), pDelim, '\'), (\''), ')');
  PREPARE myStmt FROM @sql;
  EXECUTE myStmt;
END &&
DELIMITER ;


DELIMITER &&
DROP PROCEDURE IF EXISTS _dgLayer_336_agr &&
CREATE PROCEDURE _dgLayer_336_agr(objects_id TEXT)
BEGIN
  CALL explode(",", objects_id); -- Вернет таблицу temp_explode, содержащую ИД ввиде строк

  DROP TABLE IF EXISTS _tmp_dgLayer_336_agr;  
  CREATE TABLE IF NOT EXISTS _tmp_dgLayer_336_agr(
		id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
		ordLevel_1 int null,
        ordLevel_2 int NULL,
		type_name VARCHAR(100),
		k_s019_1 VARCHAR(30),
		itog int NULL,
        itog_dg int null
  );
  
  insert into _tmp_dgLayer_336_agr(ordLevel_1, ordLevel_2, type_name, k_s019_1, itog, itog_dg)
  select 2, 2, ifnull(s.type_name, "транспортный") as type_name, 
         ifnull(l.k_s019_1, "пусто") as k_s019_1, 
         count(l.id) as itog,
         sum(case l.in_dg when 1 then 1 else 0 end) as itog_dg         
  from _dgLayer_336 l
	left join dgDict_588_589_590 s on s.code = ifnull(l.k_s019_1, "T.1")
  where l.deleted_at is null
    and l.object_id in (select item from temp_explode where item is not null)   
  group by ifnull(s.type_name, "транспортный"), l.k_s019_1
  order by 1,2,3,4;

  insert into _tmp_dgLayer_336_agr(ordLevel_1, ordLevel_2, type_name, k_s019_1, itog, itog_dg)
  select 2, 1, type_name, 'Всего, из них:', sum(itog), sum(itog_dg) from _tmp_dgLayer_336_agr group by type_name;
    
  insert into _tmp_dgLayer_336_agr(ordLevel_1, ordLevel_2, type_name, k_s019_1, itog, itog_dg)
  select 1, 1, 'Всего светофоров, из них:', 'Всего:', sum(itog), sum(itog_dg) from _tmp_dgLayer_336_agr where ordLevel_2 = 2;
   
  select id, type_name, k_s019_1, itog, itog_dg from _tmp_dgLayer_336_agr order by ordLevel_1, type_name, ordLevel_2;  
  
  DROP TABLE IF EXISTS _tmp_dgLayer_336_agr;
END &&
DELIMITER ;

call _dgLayer_336_agr("251,252,2");



DELIMITER &&
DROP PROCEDURE IF EXISTS dgLayer_176_agr &&
CREATE PROCEDURE dgLayer_176_agr(objects_id TEXT)
BEGIN
  CALL explode(",", objects_id); -- Вернет таблицу temp_explode, содержащую ИД ввиде строк

  DROP TABLE IF EXISTS _tmp_dgLayer_176_agr;  
  CREATE TABLE IF NOT EXISTS _tmp_dgLayer_176_agr(
		id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
		ordLevel_1 int null,
        ordLevel_2 int NULL,
		k_s028_7 VARCHAR(30),
		itog int NULL,
        itog_dg int null
  );
  
  insert into _tmp_dgLayer_176_agr(ordLevel_1, ordLevel_2, k_s028_7, itog, itog_dg)
  select 2, 2, 
         ifnull(s.name, "пусто") as k_s028_7,
         count(l.id) as itog,
         sum(case l.in_dg when 1 then 1 else 0 end) as itog_dg         
  from dgLayer_176 l
	left join dgDict_20490_20492_20491 s on s.code = l.k_s028_7
  where l.deleted_at is null
    and l.object_id in (select item from temp_explode where item is not null)   
  group by ifnull(s.name, "пусто")
  order by 1,2,3,4;
   
  insert into _tmp_dgLayer_176_agr(ordLevel_1, ordLevel_2, k_s028_7, itog, itog_dg)
  select 1, 1, 'Всего дорожных знаков, из них:', sum(itog), sum(itog_dg) from _tmp_dgLayer_176_agr where ordLevel_2 = 2;
   
  select id, k_s028_7, itog, itog_dg from _tmp_dgLayer_176_agr order by ordLevel_1, k_s028_7, ordLevel_2;  
  
  DROP TABLE IF EXISTS _tmp_dgLayer_176_agr;
END &&
DELIMITER ;

call dgLayer_176_agr("251,252,2");

