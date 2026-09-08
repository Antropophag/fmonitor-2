# Независимый Gate3: evidence compatibility

Дата2026-09-06. Reviewer: отдельно назначенный `/root/maintenance_review`,
gpt-5.6-sol low; не автор tests/source. Verdict: **APPROVED**.
Unapplied patch SHA256 ce09a66e6994fa96fc100b3818fb53066e190e48a4f338df82c6af00f0c0029c.
Source6dbd11ba05368cef51c4adecaf4aa940272188b6; evidence GREEN был в worktree.
Retained complete evidence: `/Users/antropophag/.local/state/fmonitor2-verification/original-evidence-legacy-compatibility-k9w3zol0`.

Три affected tests падают на valid evidence construction из-за неканонического
/var path; lease/transport/schema controls проходят. Patch применим cleanly:
только realpath(sys_get_temp_dir()) в трёх fixtures и explicit maintenance cleanup
после reader/database shutdown. Все behavioral/canonical JSON expectations
byte-identical. Cleanup ограничен уникальным task root, exact marker/token,
empty-state assertion, regular non-symlink checks и exact state/digest filenames;
nonempty metadata не удаляется. Ослабления и unsafe broad cleanup нет.
Reviewer не изменял файлы; root применил patch после verdict.
