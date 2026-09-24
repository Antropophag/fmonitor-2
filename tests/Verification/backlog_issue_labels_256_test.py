"""BACKLOG-ISSUE-LABELS-001 repository and GitHub migration evidence contract."""
import copy
import json
import os
from pathlib import Path
import unittest

ROOT = Path(__file__).resolve().parents[2]
REPOSITORY = 'Antropophag/fmonitor-2'
SCHEMA = {
    'type:product': ('1d76db', 'Пользовательские функции, исправления пользовательских ошибок, UX и бизнес-правила.'),
    'type:tech-debt': ('1d76db', 'Технический долг приложения без самостоятельного нового пользовательского результата.'),
    'type:harness': ('1d76db', 'Инструменты агентской разработки и поставки: harness, CI, Quality Graph, gates и reviews/evidence.'),
    'type:tracking': ('1d76db', 'Сводные аудиты, roadmap, эпики-реестры и учёт нескольких самостоятельных остатков.'),
    'prep:triage': ('5319e7', 'Готовность ещё не оценена либо достоверных данных для оценки недостаточно.'),
    'prep:needs-work': ('5319e7', 'Есть конкретные пробелы в решениях, границах, приёмке или декомпозиции.'),
    'prep:ready': ('5319e7', 'Постановка достаточно определена для начала реализации по действующему процессу.'),
    'status:blocked': ('d93f0b', 'Есть подтверждённое препятствие или неудовлетворённая зависимость.'),
    'status:deferred': ('d93f0b', 'Есть актуальное решение владельца отложить задачу.'),
    'status:in-progress': ('d93f0b', 'Есть актуальное подтверждение, что задача уже выполняется.'),
}
SCHEMA_NAMES = set(SCHEMA)
TYPE = {name for name in SCHEMA if name.startswith('type:')}
PREP = {name for name in SCHEMA if name.startswith('prep:')}
STATUS = {name for name in SCHEMA if name.startswith('status:')}
ISSUE_KINDS = {'issue.label.add', 'issue.label.remove', 'issue.comment'}


def labels_map(items):
    return {item['name']: (item['color'].lower(), item['description']) for item in items}


def issues_map(items):
    return {item['number']: item for item in items}


def validate_evidence(value):
    assert value['schemaVersion'] == 1
    assert value['repository'] == REPOSITORY
    before, after, plan = value['before'], value['after'], value['plan']
    assert before['capturedAt'].endswith('Z') and after['capturedAt'].endswith('Z')
    assert plan['snapshotCapturedAt'] == before['capturedAt']
    before_labels, after_labels = labels_map(before['labels']), labels_map(after['labels'])
    assert {name: after_labels.get(name) for name in SCHEMA} == SCHEMA
    assert {name: data for name, data in before_labels.items() if name not in SCHEMA_NAMES} == {
        name: data for name, data in after_labels.items() if name not in SCHEMA_NAMES}
    before_issues, after_issues = issues_map(before['issues']), issues_map(after['issues'])
    for issue in list(before_issues.values()) + list(after_issues.values()):
        assert issue['state'] == 'OPEN' and issue['isPullRequest'] is False
    plan_issues = {item['number']: item for item in plan['issues']}
    assert set(plan_issues) == set(before_issues)
    for number, item in plan_issues.items():
        assert item['observedUpdatedAt'] == before_issues[number]['updatedAt']
        assert item['type'] in TYPE
        assert (item['prep'] is None) if item['type'] == 'type:tracking' else item['prep'] in PREP
        assert set(item['statuses']) <= STATUS
        assert item['reasons'].keys() >= {'needsWork', 'blocked', 'deferred', 'inProgress'}
        assert item['existingExplanation'].keys() >= {'needsWork', 'blocked'}
        comments_by_id = {comment['id']: comment for comment in before_issues[number]['comments']}
        assert item['explanationBodySha256'].keys() >= {'needsWork', 'blocked'}
        for reason_name, witness in item['existingExplanation'].items():
            if witness is not None:
                assert comments_by_id[witness['commentId']]['bodySha256'] == witness['bodySha256']
                assert witness['bodySha256'] == item['explanationBodySha256'][reason_name]
        if item['prep'] == 'prep:needs-work':
            assert item['reasons']['needsWork'].strip()
        if 'status:blocked' in item['statuses']:
            assert item['reasons']['blocked'].strip()
        if 'status:deferred' in item['statuses']:
            assert item['reasons']['deferred'].strip()
        if 'status:in-progress' in item['statuses']:
            assert item['reasons']['inProgress'].strip()
    skipped = set(value['skippedClosedIssueNumbers'])
    new = set(value['newIssueNumbers'])
    assert set(after_issues) == (set(before_issues) - skipped) | new
    assert new == set(value['classifiedNewIssueNumbers'])
    prs = set(value['excludedPullRequestNumbers'])
    assert not (prs & set(plan_issues))
    failures = value['failures']
    failed_keys = {(item['kind'], item.get('issue'), item.get('label')) for item in failures}
    comments = {}
    replay = {number: set(issue['labels']) & SCHEMA_NAMES
              for number, issue in before_issues.items()}
    replay.update({number: set() for number in new})
    for operation in value['operations']:
        kind = operation['kind']
        assert kind in ISSUE_KINDS | {'label.upsert'}
        assert operation['outcome'] in {'APPLIED', 'NOOP', 'SKIPPED_CLOSED', 'FAILED'}
        if kind == 'label.upsert':
            assert operation['label'] in SCHEMA_NAMES
        else:
            number = operation['issue']
            assert number not in prs
            assert number in before_issues or number in after_issues or number in skipped
            assert operation['rereadUpdatedAt']
            if number in skipped:
                assert operation['outcome'] == 'SKIPPED_CLOSED'
            planned = plan_issues.get(number)
            if planned and operation['rereadUpdatedAt'] != planned['observedUpdatedAt']:
                assert operation['reclassified'] is True
            if kind == 'issue.comment':
                comments[number] = comments.get(number, 0) + (operation['outcome'] == 'APPLIED')
                if operation['outcome'] == 'APPLIED':
                    after_comments = {item['id']: item for item in after_issues[number]['comments']}
                    assert after_comments[operation['resultCommentId']]['bodySha256'] == operation['bodySha256']
                    planned = plan_issues[number]
                    assert operation['bodySha256'] in set(planned['explanationBodySha256'].values())
            else:
                assert operation['label'] in SCHEMA_NAMES
                if operation['outcome'] == 'APPLIED':
                    if kind == 'issue.label.add':
                        replay[number].add(operation['label'])
                    elif kind == 'issue.label.remove':
                        replay[number].discard(operation['label'])
        key = (kind, operation.get('issue'), operation.get('label'))
        assert (operation['outcome'] == 'FAILED') == (key in failed_keys)
    assert not failures
    assert value['rerunOperations'] == []
    for number, issue in after_issues.items():
        current = set(issue['labels'])
        types, prep = current & TYPE, current & PREP
        assert len(types) == 1
        assert len(prep) == (0 if types == {'type:tracking'} else 1)
        assert replay[number] == current & SCHEMA_NAMES
        if number in before_issues:
            assert set(before_issues[number]['labels']) - SCHEMA_NAMES == current - SCHEMA_NAMES
            before_comments = {item['id']: item for item in before_issues[number]['comments']}
            after_comments = {item['id']: item for item in issue['comments']}
            assert all(after_comments.get(comment_id) == comment
                       for comment_id, comment in before_comments.items())
        planned = plan_issues.get(number)
        if planned:
            expected = {planned['type'], *planned['statuses']}
            if planned['prep'] is not None:
                expected.add(planned['prep'])
            assert current & SCHEMA_NAMES == expected
            needs_comment = ((planned['prep'] == 'prep:needs-work'
                              and planned['existingExplanation']['needsWork'] is None)
                             or ('status:blocked' in planned['statuses']
                                 and planned['existingExplanation']['blocked'] is None))
            assert comments.get(number, 0) == (1 if needs_comment else 0)


def valid_evidence():
    schema_labels = [dict(name=name, color=color, description=description)
                     for name, (color, description) in SCHEMA.items()]
    unrelated = [dict(name='enhancement', color='a2eeef', description='Existing')]
    before_issue = dict(number=1, state='OPEN', isPullRequest=False,
                        updatedAt='2026-09-24T10:00:00Z', labels=['enhancement'],
                        comments=[{'id': 5, 'createdAt': '2026-09-23T09:00:00Z', 'bodySha256': 'a' * 64}])
    after_issue = dict(number=1, state='OPEN', isPullRequest=False,
                       updatedAt='2026-09-24T10:02:00Z',
                       labels=['enhancement', 'type:product', 'prep:ready'],
                       comments=copy.deepcopy(before_issue['comments']))
    return {
        'schemaVersion': 1, 'repository': REPOSITORY,
        'before': {'capturedAt': '2026-09-24T10:00:01Z', 'labels': unrelated,
                   'issues': [before_issue]},
        'plan': {'snapshotCapturedAt': '2026-09-24T10:00:01Z', 'issues': [{
            'number': 1, 'observedUpdatedAt': '2026-09-24T10:00:00Z',
            'type': 'type:product', 'prep': 'prep:ready', 'statuses': [],
            'reasons': {'needsWork': '', 'blocked': '', 'deferred': '', 'inProgress': ''},
            'explanationBodySha256': {'needsWork': '', 'blocked': ''},
            'existingExplanation': {'needsWork': None, 'blocked': None}}]},
        'operations': [
            *[{'kind': 'label.upsert', 'issue': None, 'label': name,
               'rereadUpdatedAt': None, 'reclassified': False, 'outcome': 'APPLIED',
               'reason': 'missing'} for name in SCHEMA],
            {'kind': 'issue.label.add', 'issue': 1, 'label': 'type:product',
             'rereadUpdatedAt': '2026-09-24T10:00:00Z', 'reclassified': False,
             'outcome': 'APPLIED', 'reason': 'classification'},
            {'kind': 'issue.label.add', 'issue': 1, 'label': 'prep:ready',
             'rereadUpdatedAt': '2026-09-24T10:00:00Z', 'reclassified': False,
             'outcome': 'APPLIED', 'reason': 'readiness'}],
        'after': {'capturedAt': '2026-09-24T10:03:00Z',
                  'labels': unrelated + schema_labels, 'issues': [after_issue]},
        'skippedClosedIssueNumbers': [], 'excludedPullRequestNumbers': [2],
        'newIssueNumbers': [], 'classifiedNewIssueNumbers': [],
        'failures': [], 'rerunOperations': []}


class BacklogIssueLabelsContract(unittest.TestCase):
    def test_repository_rules_and_filters_are_published(self):
        rules_path = ROOT / 'docs/issue-labels.md'
        self.assertTrue(rules_path.is_file(), 'INTENDED_RED docs/issue-labels.md missing')
        rules = rules_path.read_text()
        agents = (ROOT / 'AGENTS.md').read_text()
        for label in SCHEMA:
            self.assertIn(f'`{label}`', rules)
        for query in ('label:"type:product"', 'label:"type:tech-debt"',
                      'label:"type:harness"', 'label:"prep:needs-work"',
                      'label:"type:tracking"', '-label:"status:blocked"',
                      '-label:"status:deferred"', '-label:"status:in-progress"'):
            self.assertIn(query, rules)
        self.assertIn('не является', rules)
        self.assertIn('автомат', rules.lower())
        self.assertIn('docs/issue-labels.md', agents)

    def test_delivery_report_records_external_irreversibility_and_unknowns(self):
        report = ROOT / 'docs/operations/issue-256-backlog-labels-delivery.md'
        self.assertTrue(report.is_file())
        text = report.read_text()
        for marker in ('UTC', 'Охват', 'type:', 'prep:', 'GitHub', 'PR', 'UNKNOWN'):
            self.assertIn(marker, text)
        self.assertRegex(text, r'не (откатываются|отменяются).{0,80}PR')

    def test_synthetic_complete_evidence(self):
        validate_evidence(valid_evidence())

    def test_negative_safety_matrix(self):
        mutations = {
            'unrelated-catalog-loss': lambda v: v['after']['labels'].pop(0),
            'unrelated-issue-loss': lambda v: v['after']['issues'][0].update(labels=['type:product', 'prep:ready']),
            'pr-in-plan': lambda v: v['plan']['issues'].append(dict(v['plan']['issues'][0], number=2)),
            'closed-after': lambda v: v['after']['issues'][0].update(state='CLOSED'),
            'missing-reread': lambda v: v['operations'][-1].update(rereadUpdatedAt=''),
            'changed-without-reclassification': lambda v: v['operations'][-1].update(rereadUpdatedAt='2026-09-24T10:01:00Z'),
            'unexplained-blocker': lambda v: v['plan']['issues'][0].update(statuses=['status:blocked']),
            'partial-failure': lambda v: v['operations'][-1].update(outcome='FAILED'),
            'new-unclassified': lambda v: (v.update(newIssueNumbers=[3]), v['after']['issues'].append(dict(v['after']['issues'][0], number=3))),
            'rerun-not-idempotent': lambda v: v.update(rerunOperations=[{'kind': 'issue.label.add'}]),
            'skipped-but-applied': lambda v: (v.update(skippedClosedIssueNumbers=[1]),
                                              v['after'].update(issues=[])),
            'deferred-without-basis': lambda v: v['plan']['issues'][0].update(statuses=['status:deferred']),
            'in-progress-without-basis': lambda v: v['plan']['issues'][0].update(statuses=['status:in-progress']),
            'unwitnessed-explanation': lambda v: (v['plan']['issues'][0].update(
                prep='prep:needs-work', reasons={'needsWork': 'gap', 'blocked': '', 'deferred': '', 'inProgress': ''},
                explanationBodySha256={'needsWork': 'b' * 64, 'blocked': ''},
                existingExplanation={'needsWork': {'commentId': 5, 'bodySha256': 'a' * 64}, 'blocked': None})),
            'comment-history-rewritten': lambda v: v['after']['issues'][0].update(comments=[]),
            'after-disagrees-with-plan': lambda v: v['after']['issues'][0].update(
                labels=['enhancement', 'type:tech-debt', 'prep:triage']),
            'journal-disagrees-with-plan-and-after': lambda v: (
                v['operations'][-2].update(label='type:tech-debt'),
                v['operations'][-1].update(label='prep:triage')),
        }
        for name, mutate in mutations.items():
            with self.subTest(name=name):
                value = valid_evidence()
                mutate(value)
                with self.assertRaises((AssertionError, KeyError, TypeError)):
                    validate_evidence(value)

    def test_external_migration_evidence_when_supplied(self):
        path = os.environ.get('FMONITOR_ISSUE_LABELS_EVIDENCE')
        if not path:
            self.skipTest('exact external evidence is checked in focused delivery')
        validate_evidence(json.loads(Path(path).read_text()))


if __name__ == '__main__':
    unittest.main()
