<?php
declare(strict_types=1);

namespace FMonitor2\IdentityAccess;

/** Versioned system catalogue for the local FMonitor RBAC model. */
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
                'permissions' => ['objects.read','installers.read','assignment_order_artifact.read','checklist.read','assignment_order.prepare','assignment_order.composition.select','assignment_order.composition.apply','assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct','installation.open','installation.completion.pto.record','installation.completion.declaration.record','installation.completion.pto.correct','installation.completion.declaration.correct'],
            ],
            'construction_control_engineer' => [
                'name' => 'Инженер строительного контроля',
                'description' => 'Инспекции и чек-листы назначенных объектов.',
                'permissions' => ['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.item.complete','inspection.photo.revoke','assignment_order.original.read','assignment_order_artifact.read','construction_control_engineer'],
            ],
            'construction_control_coordinator' => [
                'name' => 'Координатор строительного контроля',
                'description' => 'Общая очередь и планирование инспекций.',
                'permissions' => ['objects.read','construction_control.read','checklist.read','checklist.edit','inspection.schedule'],
            ],
            'otiz_specialist' => [
                'name' => 'Специалист ОТиЗ',
                'description' => 'Расчёт, проверка и учёт премий.',
                'permissions' => ['objects.read','installers.read','checklist.read','assignment_order.original.read','otiz.manage'],
            ],
            'manager' => [
                'name' => 'Руководитель ФКР',
                'description' => 'Распоряжения, открытие, документальное завершение и контроль работ.',
                'permissions' => ['objects.read','installers.read','management.read','construction_control.read','checklist.read','assignment_order.prepare','assignment_order.composition.select','assignment_order.composition.apply','assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct','installation.open','installation.completion.pto.record','installation.completion.declaration.record','installation.completion.pto.correct','installation.completion.declaration.correct'],
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
