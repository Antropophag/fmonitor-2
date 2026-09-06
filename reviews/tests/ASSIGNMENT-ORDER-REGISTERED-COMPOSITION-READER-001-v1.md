# Registered composition reader Gate3 v1

Reviewer `/root/registered_reader_gate3`, новый gpt-5.6-sol low/fork none;
author root. Verdict **CHANGES_REQUESTED** at clean
`e9f7541b65c8d1daa640ed250c29c8b2718daa28`.

Valid RED:27intended missing-factory failures, no setup/cleanup errors.
Blocking gaps: отсутствует valid nonempty-prefix read (prefix может игнорироваться),
invalid prefix только length26 (illegal characters не обнаруживаются); materially
нет чувствительности selection header allocatedAt/revision/mode/predecessor/
required snapshot guards. Нужны focused public corruptions и новый exact RED.
Reviewer не менял tests/support. GREEN не разрешён.

```text
e4d8888e831c4ff0d03752a8e37d940212af8bea2613e0345c0fa3fe9fde2c07  tests/InstallationProcess/assignment_order_registered_composition_reader_001_test.php
94642a082c4e182d92123ec5062b901aebb7f6de54e8a0e2ae34c1328da50ff0  tests/Support/RegisteredCompositionTestFixture.php
56c89cb209dc23dff90c16e59460676a6d9d9d330fcb2edc99e1106ccd0f35c5  specs/ASSIGNMENT-ORDER-REGISTERED-COMPOSITION-READER-001.md
3246bc432a3d0b762b59cd344d15e09bd095567a608470c0b311a5c13d555872  evidence.json
29d142ef537ad8cfbef5f87d1d3320e75812d1a4f3926ece2406d90e744028f2  red.log
```

Archive `/Users/antropophag/.local/state/fmonitor2-verification/registered-composition-reader-red-9nkqxg2y`.
Command `/opt/homebrew/bin/php tests/InstallationProcess/assignment_order_registered_composition_reader_001_test.php`,
terminal exit1/7.88s, clean before/after, existing synthetic local DB credentials.
Первый `registered-composition-reader-red-du2zb3q7` capture сохраняется с genuine
source privilege-cache setup failure (manifest a3704d5a212174bc30559e759210490e683adeb92662e482e7fe66215fd71d6c).
e9f7541 обновил selected database после REVOKE/GRANT, actual source denial1142
проверяется до target guard. Этот failed capture не превращается в intended RED.
