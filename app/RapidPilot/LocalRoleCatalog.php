<?php
declare(strict_types=1);

namespace FMonitor2\RapidPilot;

/** Versioned system catalogue for the autonomous rapid-pilot RBAC model. */
final class LocalRoleCatalog
{
    /** @return array<string, array{name:string,description:string,permissions:list<string>}> */
    public static function roles(): array
    {
        return [
            'user' => [
                'name' => 'Пользователь',
                'description' => 'Базовый доступ к объектам монтажа без процессных команд.',
                'permissions' => ['objects.read'],
            ],
            'fkr_operator' => [
                'name' => 'Сотрудник ФКР',
                'description' => 'Распоряжения, состав и открытие работ.',
                'permissions' => ['objects.read','installers.read','assignment_order_artifact.read','assignment_order.prepare','assignment_order.confirm_registration','installation.open'],
            ],
            'construction_control_engineer' => [
                'name' => 'Инженер строительного контроля',
                'description' => 'Инспекции и чек-листы назначенных объектов.',
                'permissions' => ['objects.read','construction_control.read','checklist.read','checklist.edit','assignment_order_artifact.read','construction_control_engineer'],
            ],
            'construction_control_coordinator' => [
                'name' => 'Координатор строительного контроля',
                'description' => 'Общая очередь и планирование инспекций.',
                'permissions' => ['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.schedule'],
            ],
            'otiz_specialist' => [
                'name' => 'Специалист ОТиЗ',
                'description' => 'Расчёт, проверка и учёт премий.',
                'permissions' => ['objects.read','installers.read','otiz.manage'],
            ],
            'manager' => [
                'name' => 'Руководитель',
                'description' => 'Сводный контроль без процессных изменений.',
                'permissions' => ['objects.read','installers.read','management.read'],
            ],
            'access_administrator' => [
                'name' => 'Администратор доступа',
                'description' => 'Преднастройка пользователей и назначение бизнес-ролей.',
                'permissions' => ['objects.read','access.administer'],
            ],
            'superadministrator' => [
                'name' => 'Суперадминистратор',
                'description' => 'Управление доступом и привилегированными ролями; бизнес-права не включены.',
                'permissions' => ['objects.read','access.administer','access.superadminister','access.audit.read'],
            ],
        ];
    }
}
