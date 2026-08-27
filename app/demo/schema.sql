-- THROWAWAY DEMO DATABASE FOR FMONITOR 2.0
-- Production is a read-only import source. All demo mutations live here.

SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE fm2_import_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_system VARCHAR(40) NOT NULL,
    source_cutoff_at DATETIME NOT NULL,
    started_at DATETIME NOT NULL,
    finished_at DATETIME NULL,
    imported_count INT UNSIGNED NOT NULL DEFAULT 0,
    rejected_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('running', 'completed', 'failed') NOT NULL,
    notes TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_objects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    legacy_order_id BIGINT UNSIGNED NOT NULL,
    order_number VARCHAR(120) NOT NULL,
    unom VARCHAR(40) NULL,
    address_text VARCHAR(500) NOT NULL,
    entrance VARCHAR(80) NULL,
    district VARCHAR(160) NULL,
    administrative_okrug VARCHAR(80) NULL,
    external_order_url VARCHAR(1000) NULL,
    source_updated_at DATETIME NULL,
    imported_at DATETIME NOT NULL,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_fm2_objects_legacy (legacy_order_id),
    UNIQUE KEY uq_fm2_objects_order_number (order_number),
    CONSTRAINT fk_fm2_objects_import_batch
        FOREIGN KEY (import_batch_id) REFERENCES fm2_import_batches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_equipment_specs (
    object_id BIGINT UNSIGNED PRIMARY KEY,
    factory_number VARCHAR(80) NULL,
    lift_purpose VARCHAR(120) NULL,
    capacity_kg SMALLINT UNSIGNED NULL,
    floor_count SMALLINT UNSIGNED NULL,
    stop_count SMALLINT UNSIGNED NULL,
    nominal_speed_mps DECIMAL(5,2) NULL,
    shaft_material VARCHAR(160) NULL,
    machine_room VARCHAR(120) NULL,
    cabin_door_type VARCHAR(160) NULL,
    shaft_door_type VARCHAR(160) NULL,
    main_drive_type VARCHAR(160) NULL,
    door_drive_type VARCHAR(160) NULL,
    through_cabin BOOLEAN NULL,
    CONSTRAINT fk_fm2_equipment_specs_object
        FOREIGN KEY (object_id) REFERENCES fm2_objects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_organizations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_key VARCHAR(120) NULL,
    name VARCHAR(500) NOT NULL,
    organization_type ENUM('general_contractor', 'installation_contractor', 'customer', 'other') NOT NULL,
    UNIQUE KEY uq_fm2_organizations_source (source_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_object_parties (
    object_id BIGINT UNSIGNED NOT NULL,
    organization_id BIGINT UNSIGNED NOT NULL,
    party_role ENUM('general_contractor', 'installation_contractor', 'customer', 'other') NOT NULL,
    valid_from DATE NULL,
    valid_to DATE NULL,
    PRIMARY KEY (object_id, organization_id, party_role),
    CONSTRAINT fk_fm2_object_parties_object
        FOREIGN KEY (object_id) REFERENCES fm2_objects(id) ON DELETE CASCADE,
    CONSTRAINT fk_fm2_object_parties_organization
        FOREIGN KEY (organization_id) REFERENCES fm2_organizations(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_people (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    person_kind ENUM('installer', 'control_engineer', 'fkr', 'otiz', 'manager') NOT NULL,
    source_system VARCHAR(40) NOT NULL,
    source_key VARCHAR(120) NOT NULL,
    personnel_number VARCHAR(40) NULL,
    full_name VARCHAR(300) NOT NULL,
    position_name VARCHAR(300) NULL,
    employment_status ENUM('employed', 'dismissed', 'unknown') NOT NULL DEFAULT 'unknown',
    employed_from DATE NULL,
    employed_to DATE NULL,
    source_updated_at DATETIME NULL,
    UNIQUE KEY uq_fm2_people_source (source_system, source_key),
    KEY ix_fm2_people_personnel_number (personnel_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_installation_cases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    object_id BIGINT UNSIGNED NOT NULL,
    process_state ENUM(
        'needs_order',
        'order_prepared',
        'ready_to_open',
        'working_pending_number',
        'working',
        'completed'
    ) NOT NULL DEFAULT 'needs_order',
    planned_start_date DATE NULL,
    planned_finish_date DATE NULL,
    adjusted_finish_date DATE NULL,
    actual_start_date DATE NULL,
    actual_finish_date DATE NULL,
    opened_at DATETIME NULL,
    opened_by_person_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    lock_version INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_fm2_installation_cases_object (object_id),
    KEY ix_fm2_installation_cases_queue (process_state, planned_start_date),
    CONSTRAINT fk_fm2_installation_cases_object
        FOREIGN KEY (object_id) REFERENCES fm2_objects(id),
    CONSTRAINT fk_fm2_installation_cases_opened_by
        FOREIGN KEY (opened_by_person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_assignment_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    version_no SMALLINT UNSIGNED NOT NULL,
    order_kind ENUM('initial', 'change') NOT NULL,
    order_status ENUM('prepared', 'registered', 'superseded', 'cancelled') NOT NULL,
    order_date DATE NOT NULL,
    registration_number VARCHAR(120) NULL,
    control_engineer_person_id BIGINT UNSIGNED NOT NULL,
    organization_form ENUM('individual', 'brigade') NOT NULL,
    previous_order_id BIGINT UNSIGNED NULL,
    document_name VARCHAR(500) NOT NULL,
    document_hash CHAR(64) NOT NULL,
    prepared_at DATETIME NOT NULL,
    prepared_by_person_id BIGINT UNSIGNED NOT NULL,
    registered_at DATETIME NULL,
    registered_by_person_id BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_fm2_assignment_orders_version (installation_case_id, version_no),
    KEY ix_fm2_assignment_orders_current (installation_case_id, order_status),
    CONSTRAINT fk_fm2_assignment_orders_case
        FOREIGN KEY (installation_case_id) REFERENCES fm2_installation_cases(id),
    CONSTRAINT fk_fm2_assignment_orders_engineer
        FOREIGN KEY (control_engineer_person_id) REFERENCES fm2_people(id),
    CONSTRAINT fk_fm2_assignment_orders_previous
        FOREIGN KEY (previous_order_id) REFERENCES fm2_assignment_orders(id),
    CONSTRAINT fk_fm2_assignment_orders_prepared_by
        FOREIGN KEY (prepared_by_person_id) REFERENCES fm2_people(id),
    CONSTRAINT fk_fm2_assignment_orders_registered_by
        FOREIGN KEY (registered_by_person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_order_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    assignment_role ENUM('installer', 'control_engineer') NOT NULL,
    change_action ENUM('assign', 'retain', 'release') NOT NULL,
    valid_from DATE NOT NULL,
    valid_to DATE NULL,
    full_name_snapshot VARCHAR(300) NOT NULL,
    position_snapshot VARCHAR(300) NULL,
    UNIQUE KEY uq_fm2_assignments_order_person_role (assignment_order_id, person_id, assignment_role),
    KEY ix_fm2_assignments_active (person_id, valid_from, valid_to),
    CONSTRAINT fk_fm2_assignments_order
        FOREIGN KEY (assignment_order_id) REFERENCES fm2_assignment_orders(id),
    CONSTRAINT fk_fm2_assignments_person
        FOREIGN KEY (person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    document_type ENUM(
        'assignment_order', 'opening_act', 'site_plan', 'site_transfer_act',
        'permit', 'pto_act', 'non_conformance_act', 'four_party_act',
        'commissioning_act', 'ks2', 'declaration', 'afd', 'design_docs', 'other'
    ) NOT NULL,
    document_number VARCHAR(160) NULL,
    document_date DATE NULL,
    version_no SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    document_status ENUM('expected', 'prepared', 'registered', 'verified', 'superseded', 'restricted') NOT NULL,
    copy_form ENUM('digital', 'scan', 'original', 'unknown') NOT NULL DEFAULT 'unknown',
    file_name VARCHAR(500) NULL,
    source_system VARCHAR(40) NOT NULL,
    created_at DATETIME NOT NULL,
    created_by_person_id BIGINT UNSIGNED NULL,
    KEY ix_fm2_documents_case_type (installation_case_id, document_type),
    CONSTRAINT fk_fm2_documents_case
        FOREIGN KEY (installation_case_id) REFERENCES fm2_installation_cases(id),
    CONSTRAINT fk_fm2_documents_created_by
        FOREIGN KEY (created_by_person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_inspections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    inspected_at DATETIME NOT NULL,
    inspector_person_id BIGINT UNSIGNED NOT NULL,
    progress_basis_points SMALLINT UNSIGNED NOT NULL,
    comment_text TEXT NULL,
    created_at DATETIME NOT NULL,
    CHECK (progress_basis_points <= 10000),
    KEY ix_fm2_inspections_case_date (installation_case_id, inspected_at),
    CONSTRAINT fk_fm2_inspections_case
        FOREIGN KEY (installation_case_id) REFERENCES fm2_installation_cases(id),
    CONSTRAINT fk_fm2_inspections_inspector
        FOREIGN KEY (inspector_person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_inspection_workers (
    inspection_id BIGINT UNSIGNED NOT NULL,
    person_id BIGINT UNSIGNED NOT NULL,
    presence_status ENUM('observed', 'absent', 'unknown') NOT NULL,
    PRIMARY KEY (inspection_id, person_id),
    CONSTRAINT fk_fm2_inspection_workers_inspection
        FOREIGN KEY (inspection_id) REFERENCES fm2_inspections(id) ON DELETE CASCADE,
    CONSTRAINT fk_fm2_inspection_workers_person
        FOREIGN KEY (person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_process_tasks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    task_type ENUM('prepare_order', 'register_number', 'open_order', 'inspect', 'resolve_data_issue') NOT NULL,
    assignee_person_id BIGINT UNSIGNED NULL,
    assignee_role ENUM('fkr', 'control_engineer', 'otiz', 'manager') NULL,
    due_date DATE NULL,
    task_status ENUM('open', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    blocking_reason VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL,
    completed_at DATETIME NULL,
    completed_by_person_id BIGINT UNSIGNED NULL,
    KEY ix_fm2_process_tasks_queue (task_status, assignee_role, due_date),
    CONSTRAINT fk_fm2_process_tasks_case
        FOREIGN KEY (installation_case_id) REFERENCES fm2_installation_cases(id),
    CONSTRAINT fk_fm2_process_tasks_assignee
        FOREIGN KEY (assignee_person_id) REFERENCES fm2_people(id),
    CONSTRAINT fk_fm2_process_tasks_completed_by
        FOREIGN KEY (completed_by_person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_process_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    installation_case_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(80) NOT NULL,
    occurred_at DATETIME NOT NULL,
    actor_person_id BIGINT UNSIGNED NULL,
    payload_json JSON NOT NULL,
    KEY ix_fm2_process_events_case_date (installation_case_id, occurred_at),
    CONSTRAINT fk_fm2_process_events_case
        FOREIGN KEY (installation_case_id) REFERENCES fm2_installation_cases(id),
    CONSTRAINT fk_fm2_process_events_actor
        FOREIGN KEY (actor_person_id) REFERENCES fm2_people(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fm2_import_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_batch_id BIGINT UNSIGNED NOT NULL,
    legacy_order_id BIGINT UNSIGNED NOT NULL,
    object_id BIGINT UNSIGNED NULL,
    import_status ENUM('imported', 'rejected') NOT NULL,
    rejection_reason VARCHAR(1000) NULL,
    raw_payload_json JSON NOT NULL,
    UNIQUE KEY uq_fm2_import_records_batch_order (import_batch_id, legacy_order_id),
    CONSTRAINT fk_fm2_import_records_batch
        FOREIGN KEY (import_batch_id) REFERENCES fm2_import_batches(id),
    CONSTRAINT fk_fm2_import_records_object
        FOREIGN KEY (object_id) REFERENCES fm2_objects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
