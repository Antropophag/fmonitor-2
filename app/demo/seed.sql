INSERT INTO fm2_people
    (person_kind, source_system, source_key, personnel_number, full_name, position_name, employment_status)
VALUES
    ('fkr', 'demo', 'fkr-1', 'F-001', 'Мария Соколова', 'Специалист ФКР', 'employed'),
    ('control_engineer', 'demo', 'engineer-1', 'СК-014', 'Анна Волкова', 'Инженер строительного контроля', 'employed'),
    ('installer', 'demo', 'installer-1', '10482', 'Иванов Сергей', 'Электромеханик по лифтам', 'employed'),
    ('installer', 'demo', 'installer-2', '11803', 'Сидоров Павел', 'Электромеханик по лифтам', 'employed')
ON DUPLICATE KEY UPDATE
    full_name=VALUES(full_name), position_name=VALUES(position_name), employment_status=VALUES(employment_status);
