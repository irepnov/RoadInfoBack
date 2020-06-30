DELIMITER &&
DROP PROCEDURE IF EXISTS objects_ && 
CREATE PROCEDURE objects_()
BEGIN 
	DECLARE done INT DEFAULT 0;
	DECLARE _table_name VARCHAR(50);
    DECLARE _geometry_type VARCHAR(50);
	DECLARE cursor_layers CURSOR FOR SELECT table_name, geometry_type FROM layers WHERE table_name like 'dgLayer%';
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
    
	OPEN cursor_layers;
	REPEAT
		FETCH cursor_layers INTO _table_name, _geometry_type;
		IF NOT done THEN

			IF (_geometry_type = 'Point') THEN
						SET @sql := CONCAT('update ', 
										   _table_name,    
										   ' SET updated_at = now(), object_id = case when _road_id = 3706 then 252 when _road_id = 2953 then 141 when _road_id = 2840 then 616 when _road_id = 2673 then 533 when _road_id = 2472 then 170 end, km_beg = km_beg - 195.789', 
										   ' WHERE _road_id in (3706,2953,2840,2673,2472) and updated_at is null; ');
			ELSE
						SET @sql := CONCAT('update ', 
										   _table_name, 
										   ' SET updated_at = now(), object_id = case when _road_id = 3706 then 252 when _road_id = 2953 then 141 when _road_id = 2840 then 616 when _road_id = 2673 then 533 when _road_id = 2472 then 170 end, km_beg = km_beg - 195.789, km_end = km_end - 195.789', 
										   ' WHERE _road_id in (3706,2953,2840,2673,2472) and updated_at is null; ');    
			END IF;
                               
			PREPARE stmt FROM @sql;
			EXECUTE stmt; 
            -- SELECT @sql;
            DEALLOCATE PREPARE stmt;
            
		END IF;
	UNTIL done END REPEAT;
	CLOSE cursor_layers;    
END &&
DELIMITER ;

CALL objects_();


select * from dgLayer_325 where updated_at is null

/*
update layers set geometry_type = 'LineString' 
where id in (
select layer_id from layer_attributes where field_name = 'km_beg' and layer_id in (select layer_id from layer_attributes where field_name = 'km_end')
)
and table_name like 'dgLayer%' and geometry_type is null
*/