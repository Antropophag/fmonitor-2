# Справочник монтажников — independent final review и production

Reviewer `/root/otiz_ui_review`, gpt-5.6-sol/low; author `/root/otiz_ui_executor`,
gpt-5.6-sol/low; root authored contract/tests/profiling. Verdict APPROVED без возвратов.
Single runtime file SHA256:
`5e4983cfe39af55abf9ad8bd2bea769d508f7b170d13e4d24e528018d94cf241`
`app/Workforce/MariaDbInstallerUtilization.php`.

Owner-authorized short cycle, bounded correction of established read behavior.
No cache, schema, permissions or domain facts changed. Only directoryPage and its
private bulk-count SQL helper changed. Card/forecast/projection code unchanged.

## Evidence

Production default public read initially3.271726s, summary query3.247572s.
Existing indexes used; correlated workers×cases membership drove redundant work.
Root1320workers/309cases regression: intended RED1,294,584handlerreads; GREEN4,290
(read work below100000 bound). Local timing0.725s→0.0269s; reviewer0.0217s.
Focused directory HTTP/native assignment/malformed snapshot/stage-one-history tests
PASS, PHP lint and diff check PASS. make architecture-check PASS8rules plus HTTP
qualification. No full local suite; full CI deferred under explicit owner authorization.
Initial isolated MariaDB startup race was setup-only and retried after readiness.

Same production REPEATABLE READ snapshot, original/candidate loaded into isolated CLI
class names with read-only DB connection; all returned PHP values strictly identical:

| Variant | Original ms | Candidate ms | Identical |
|---|---:|---:|---|
| default | 3950.644 | 19.654 | yes |
| assigned | 11566.3 | 21.412 | yes |
| current | 5447.147 | 22.133 | yes |
| upcoming | 5005.906 | 22.5 | yes |
| dismissed | 3794.295 | 19.146 | yes |
| page2 | 3916.184 | 18.973 | yes |

## Deployment

Single approved file applied to production host + PHPcontainer29.10.2026 20:51МСК,
with pre-write hash comparison and per-file rollback. Earlier OTIZ/search/password
and other sessions untouched. Postdeploy public read0.029325s,50rows/1320employees,
full response hash exactly matches pre-deploy:29dab21ebae48d57f07d6821c5a3d185c145abf9ae98968e0e6295c80238f8a3.
Six queries remain; their work is bounded by case membership rather than worker×case.

Raw source capture, plans, profiles, same-snapshot comparison, deploy and rollback
manifests remain outside Git:
`/Users/antropophag/.local/share/fmonitor-2/installer-directory-performance-20261029/`.
