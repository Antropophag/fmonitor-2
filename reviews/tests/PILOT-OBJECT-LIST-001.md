# Test review: PILOT-OBJECT-LIST-001 v0.1 — UI correction

- Reviewer: separately tasked Codex agent `/root/pilot_queue_gate3_ui`
- Test authors: separately tasked Gate 2/correction agents (not this reviewer)
- Reviewed ancestry: HEAD `6d87c16b663daf381cf9b2fb29f32fdaf00b2fbd`; exact dirty-tree inputs are pinned below
- Specification: `specs/PILOT-OBJECT-LIST-001.md`, version `0.1`, status `APPROVED`
- Predecessors: `PILOT-HTTP-AUTH-001 v0.12`, `PILOT-OBJECT-CARD-001 v0.2`
- Public seam: raw HTTP `GET|HEAD /pilot/objects` through `public/router.php` and isolated MariaDB
- Corrective RED command: `php tests/InstallationProcess/pilot_object_list_001_test.php`
- Intended failure: `one narrow-flow semantic object list`, expected `1`, actual `0`; the existing bare Table renderer reaches the real collection response but does not implement the selected public semantic-list composition
- Review date: `2026-08-28`
- Verdict: `APPROVED`

## Findings

1. **Gate 5 finding is executable — pass.** The corrected test selects the semantic-list branch explicitly permitted by specification section 6. It requires exactly one `ul|ol` containing direct `li` object items, rejects both a native `table` and the public horizontally overflowing `shlz-table-wrap` branch, and requires one real `a.shlz-link` with the exact card `href` in every item. This distinguishes the selected supported composition from the bare HTML that caused Gate 5 `CHANGES_REQUESTED`.
2. **Public UI evidence and narrow-flow proxy — pass.** The pinned Link contract defines native `a.shlz-link` with real `href`; its public CSS keeps the link inline/content-sized and imposes no overflow geometry. The pinned Table docs/CSS define Table as a classed native table whose wrapper owns horizontal overflow. In this no-application-CSS slice, semantic list + public Link and absence of `table`/`shlz-table-wrap` is a plausible executable proxy for the required narrow document flow. It catches the actual regression without inventing tokens, dimensions, screenshots or application CSS.
3. **No material overcoupling — pass.** `ul` versus `ol` and all internal item markup remain free. The test does not require application classes, wrapper depth, labels, CSS declarations or renderer internals. Fixing the allowed presentation branch is justified by the independently documented responsive distinction and remains compatible with either semantic list element.
4. **Expected-value independence — pass.** Every item must contain one exact object link and the independently fixed ID, registration number, address, entrance, planned start and finish from specification section 7. The oracle pins canonical order and same-item association. No expectation comes from production output, SQL, renderer methods or the predecessor test.
5. **Regression sensitivity — pass.** The correction fails against the current bare table at the first UI assertion. It also detects missing/duplicated lists or items, an unclassed/non-native/wrong-destination link, return to either bare or public horizontally scrolling Table, cross-item value mixing, order/membership drift and forbidden controls. Existing failure-priority, integrity, 500/501, empty, HEAD, query, read-only and cleanup checks remain intact.
6. **Predecessor test — pass.** `pilot_http_auth_001_test.php` requires the successor-owned ordinary `/pilot/objects` shell link and rejects the obsolete disabled placeholder while retaining its authentication, routing, CSS, security, failure and resource-cleanup matrix. Its independent rerun is green and it supplies no collection fixture values.
7. **Isolation and RED validity — pass.** The focused RED reached the successful collection representation and failed only because no semantic list exists, not because of setup or authentication. Random isolated schema/users/files, loopback servers, SELECT-only credentials and `finally` cleanup remain deterministic. Post-run inspection found no leftover artifact, router process, test schema or database account.

## Required changes

None. Gate 4 may minimally replace the bare collection table with the reviewed semantic-list/public-Link composition. Any expectation change requires another independent Gate 3 review.

## Verification evidence

```text
php -l tests/InstallationProcess/pilot_object_list_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_object_list_001_test.php

php -l tests/InstallationProcess/pilot_http_auth_001_test.php
No syntax errors detected in tests/InstallationProcess/pilot_http_auth_001_test.php

php tests/InstallationProcess/pilot_object_list_001_test.php
PHP Fatal error: Uncaught TestFailure: one narrow-flow semantic object list
Expected: 1
Actual: 0
exit code: 255 (intended corrective RED)

php tests/InstallationProcess/pilot_http_auth_001_test.php
PASS: PILOT-HTTP-AUTH-001 HTTP boundary
exit code: 0

post-run matching .test-artifacts files: 0
post-run matching PHP router processes: 0 (inspection shell excluded)
post-run t_pol_*/t_pha_* schemas: 0
post-run pol_*/pha_* database accounts: 0
```

## SHA-256 reviewed-input manifest

Captured `2026-08-28`. Set digests are SHA-256 over each `LC_ALL=C` binary-path-sorted per-file `sha256sum` manifest. This review file is metadata because a self-hash is circular.

```text
f48e56a6f0a65541e47d5ab2839238e508d6890cb139c57c45cce1d4748e4798  AGENTS.md
201885dc684287c1526c4657e5a9dd71f23d7dca74423fb5f329169e03fea358  PRODUCT.md
68f38cae8a69b33bb194e5b6f5d3809f4ddb90004d59af6b7a8a3c5b11870037  CONTEXT.md
721a3a6e06efb18da2702c379a785c5ed863c251622b059437bea95deccb5d54  docs/development-process.md
24e106a8db1e9fbff41637da646eeb1fa411c78e71f95b1c9351a814af9ed7a3  docs/fmonitor-2-pilot-spec.md
59d2643200f6649c20f5ce6ea104d88591bf057a0afa64ab056ddd6562162886  docs/fmonitor-2-pilot-data-model.md
3f42ee848b3166a3961c7540d32319f608353e9b3749561cf7f2c30549172aa2  specs/PILOT-OBJECT-LIST-001.md
07d83894e9be75a3d8276d5701661fdc41f671f312d30ed3f5832892cb063b89  specs/PILOT-HTTP-AUTH-001.md
ec5d7b438c6696950e09397ae3b129c9890b9182636d27650127532d5d979732  specs/PILOT-OBJECT-CARD-001.md
31d957e42ad7db9646422f800dc4138a58f2611e6d7453d917990a9a4dd40642  reviews/code/PILOT-OBJECT-LIST-001.md
f3b9de5783b9930cc614703bd059243524c133f5bb0883a14ffd8e20e3ce219a  tests/InstallationProcess/pilot_object_list_001_test.php
d745b585192b0e808fe346a61913fcce3d05a79129c4c872d9ee68c847c7acc7  tests/InstallationProcess/pilot_http_auth_001_test.php
652708eea6099b750b805b996da195c6c2b3c6eb8616270323f491591f3935f0  tests/bootstrap.php
250f582106c9e15db622473d0d9f13d0dc0a256592e3a4c4545b1cff49a06a27  public/router.php
e0ab09767ebc433ba01fa9a1206c605ed6765d82d15ba6815942df30d4cd635e  app/PilotHttp/production-entrypoint.php

3bc14398cc8e8e5a6eba9b6c475a720f52fb1d28ede9e5f7530404b708b96236  ../shlz-ui/docs/components/link.md
470b0c2f6e57b061205d98f0926c676b01ecb02e8c9bb112928962eb30beab4d  ../shlz-ui/docs/components/table.md
8d09c935da2d5d3ffe7b432082712a1e2067a7d20d962e3bff93f8fadcc720cf  ../shlz-ui/packages/styles/components/link.css
a38cba53863519f64c99e1f2bbbbac8336ebfdfc98402832abc043ab95e27b10  ../shlz-ui/packages/styles/components/table.css
b8594c95ce1c06de00960fe6e603693f9c5028d8038307614798e81eb0290c40  ../shlz-ui/packages/styles/shlz.css
6754800e39cdaeb83d0800f3d2f2e781762203dbd04324c5ca67dfa1a6c05a64  ../shlz-ui/packages/styles/dist/shlz.css

e002de3151e5ef1012da3b201053788da6d35c8cba3321478e58418be5df9f16  tests/Support/BitrixWorkforceSchemaV5Contract.php
fd386a880929868cf869fa50ab25a6b64484a3d60cf75eeb2e2f6c84c5c16f2d  tests/Support/InMemoryInstallationProcessEnvironment.php
a70aebd337352b916412fd167d484024f9bde0549362469a8552ca74db4cfd76  tests/Support/ProductionMigrationRunnerCatalogContract.php
6c8d2177d53cf8903fd0b658ec5c537b1d54549cfecf436c19fed2aea002666f  tests/Support/css_descriptor_ownership_spy.php
b23ba215387aa8328fcf71c7e71324f0363f403e7c776f84233b378e6043cb29  tests/Support/css_lstat_swap_preload.c
a8f0ff18768cdf4d7ae497ecea70fabb31127bf4a6cb9ef91621f6f174d1e12b  tests/Support/forbid_v4_migration_invocation.php
95361a80333d0c7b992d00ac52a67d3ed1c23cf6ff200d4565838413ab3a7e52  tests/Support/mysql_wire_charset_fault_proxy.php
1ae8f5107efd7438c10e9023b9910f66ee76fa46b0f806ba0381d40c3131a02a  tests/Support/mysql_wire_drop_commit_proxy.php
b5b73e59665c46a6bfc5b7f1b05476d74f3fa3893cb6ba305dc26756e56ba422  tests/Support/php_css_descriptor_fault_spy.php
3f8cfbc6bbfb2e5836013ed79fdb311841ba7916e2be38d27617333e01e5021c  tests/Support/php_input_open_probe.php
82960cd0c079c3fc587451d4fc11af29854fc39c7606a2189c3911c96e7907b0  tests/Support/php_input_open_sentinel.php
8f6fd9e35f3588a04c3d56be099abeadc68b9f0bc7a35631fcc68c06d28e6542  tests/Support/php_runtime_primitive_shadow_spy.php
b3f2e5faa7bcaa646cf3f6e082b1bbc3b094c3bfd44cbba1bf6bca081cffb6c4  tests/Support/production_bootstrap_application_shadow.php
f22dbb2551e49e152da62b5208ddb90e5d4cb24da3f01c6b867f545c309a5300  tests/Support/production_bootstrap_factory_shadow.php
9f8893e0f87d158dc76c229f681aedb970d7beeb306d59362d55cf467a671f86  tests/Support/production_environment_source_spy.php
185b00529a17adb2b40078c85dd4b70edd65b234d5d3d6c0deca8b73b7df87b7  tests/Support/production_http_router_probe.php
c3788e557d633591bc561a10a0551a21b218c1553f82f9e1e80027351ea11d06  tests/Support/raw_http_get.php
f15a1d73d5fb577116343c2c7c581ec4cbc44dfad3c9dab6a72c6de7f67e0ee9  tests/Support/real_http_entrypoint_spy.php
c6dd22b73fed01e40b646478e4b1b4f4dfa532a0467708577a1ec5aa05d9c752  tests/Support/real_production_resource_close_probe.php
38cc3a96ed6c23b7b88f6860b811d81933b5c4bfbba4f45c11bbe7a2da68edf7  tests/Support/trusted_host_server.php
ebd340371176c48a9ab8e12ed06bba87171587fe32380e5cd8b2cf20928faedf  tests/Support/* sorted membership manifest (20 files)

8f290947a68fcf12c32100bc328c467e31acc4fd4e08f17dec478057ede1ce72  app/PilotHttp/*.php sorted membership manifest (38 files)

METADATA  reviews/tests/PILOT-OBJECT-LIST-001.md
```

Any specification, test/bootstrap/router byte, public `shlz-ui` evidence byte, or scanned-set membership change invalidates this approval and requires a fresh review. Production remains unchanged by this Gate 3 review.
