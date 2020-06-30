use amstrad_roadinfo;

DELIMITER &&
DROP PROCEDURE IF EXISTS update_layer_objects && 
CREATE PROCEDURE update_layer_objects()
BEGIN   
	DECLARE done INT DEFAULT 0;
	DECLARE _layer_id INT;
	DECLARE _table_name VARCHAR(50);
	DECLARE cursor_layers CURSOR FOR SELECT id, table_name FROM layers WHERE table_name IS NOT NULL;
	DECLARE CONTINUE HANDLER FOR SQLSTATE '02000' SET done = 1;
    
	--   удалю отсуствующие ведомости
    DELETE FROM layer_objects WHERE layer_id NOT IN (SELECT id FROM layers WHERE table_name IS NOT NULL);     
    
	OPEN cursor_layers;
	REPEAT
		FETCH cursor_layers INTO _layer_id, _table_name;
		IF NOT done THEN
            -- посчитаю кол-во записей в таблице
            DROP TABLE IF EXISTS _layer_objects;
			CREATE TEMPORARY TABLE IF NOT EXISTS _layer_objects (id INT(6) NOT NULL AUTO_INCREMENT, layer_id INT(6) NOT NULL, object_id INT(6) NOT NULL, counts INT(6) NOT NULL, PRIMARY KEY(id));                       
            SET @sql := CONCAT('INSERT INTO _layer_objects(layer_id, object_id, counts) SELECT ', 
							   _layer_id, 
                               ' AS layer_id, object_id, COUNT(id) AS count FROM ', 
                               _table_name, 
                               ' WHERE deleted_at IS NULL AND IFNULL(object_id, 0) > 0 ',
                               ' GROUP BY object_id;');
			PREPARE stmt FROM @sql;
			EXECUTE stmt; 
            DEALLOCATE PREPARE stmt;
            
            CREATE INDEX layer_object_id ON _layer_objects (layer_id, object_id);
			CREATE INDEX layer_id ON _layer_objects (layer_id);
            
            -- обновлю сводную таблицу						
            --   удалю отсутствующие объекты
            DELETE layer_objects 
			FROM layer_objects
				LEFT JOIN _layer_objects ON layer_objects.layer_id = _layer_objects.layer_id 
										AND layer_objects.object_id = _layer_objects.object_id 
			WHERE layer_objects.layer_id = _layer_id
              AND _layer_objects.ID IS NULL;              
			--   обновлю количество по объектам
            UPDATE layer_objects
				INNER JOIN _layer_objects ON layer_objects.layer_id = _layer_objects.layer_id 
									   	 AND layer_objects.object_id = _layer_objects.object_id
			SET layer_objects.counts = _layer_objects.counts,
                layer_objects.updated_at = NOW()
			WHERE IFNULL(layer_objects.counts, 0) <> IFNULL(_layer_objects.counts, 0);            
            --   добавлю новые
            INSERT INTO layer_objects(layer_id, object_id, counts, created_at)
            SELECT _layer_objects.layer_id, _layer_objects.object_id, _layer_objects.counts, NOW() FROM _layer_objects
				LEFT JOIN layer_objects ON layer_objects.layer_id = _layer_objects.layer_id 
									   AND layer_objects.object_id = _layer_objects.object_id 
                                       AND layer_objects.layer_id = _layer_id
			WHERE layer_objects.ID IS NULL;            
            --   удалю с нулем
            DELETE FROM layer_objects WHERE layer_id = _layer_id AND IFNULL(counts, 0) = 0;		
		END IF;
	UNTIL done END REPEAT;
	CLOSE cursor_layers;
END &&
DELIMITER ;
  
 
CALL update_layer_objects();


select * from layer_objects;
