


use amstrad_roadinfo;

/*
!!!!!
настроить шедулер на MySql
включить его в файле конфигурации MySQL (my.cnf или my.ini в Windows).
	event_scheduler=1

-- SET GLOBAL event_scheduler = ON;   --можно через консоль включить

-- SHOW PROCESSLIST;  должна появится строка с словом event... если она есть значит шедулер работает

-- SHOW EVENTS;  покажет список заданий, вызвать нужно после скрпта, тогда появится в нем строчка с названием copy_federated_tables_to_amstrad_resources
-- SELECT * FROM INFORMATION_SCHEMA.EVENTS;  содержимое задания
*/


-- задание . по обновлению списка ведомостей для объектов

DROP EVENT IF EXISTS updated_layers_objects;

DELIMITER $$
CREATE
  EVENT updated_layers_objects
  ON SCHEDULE
    -- AT ‘2011-06-01 02:00.00’
    EVERY 1 HOUR STARTS '2019-08-14 12:00:00'
  ON COMPLETION NOT PRESERVE -- сохранять событие для следующего раза
  DO BEGIN
  
	CALL update_layer_objects();

END $$
DELIMITER ;  