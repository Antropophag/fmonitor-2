"""CANONICAL-LIVE-ADMISSION-CONTEXT-001: executable cases A-J."""
import hashlib
import fcntl
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time
import unittest

ROOT = Path(__file__).resolve().parents[2]


class CanonicalLiveAdmissionContext(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="live-admission-context-")
        self.addCleanup(self.temp.cleanup)
        self.root = Path(self.temp.name) / "repo"
        self.root.mkdir()
        for relative in ("tools/delivery", "tools/verification", ".quality-graph"):
            shutil.copytree(ROOT / relative, self.root / relative)
        for relative in ("AGENTS.md", "PRODUCT.md", "CONTEXT.md", "specs/CHANGE-VERIFICATION-001.md"):
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy(ROOT / relative, target)
        for relative in ("docs/development-process.md", "docs/operations/current-delivery-goal.md"):
            target = self.root / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy(ROOT / relative, target)
        (self.root / "tests/Delivery").mkdir(parents=True)
        for directory in ("tests/InstallationProcess", "tests/AssignmentOrderComposition",
                          "tests/Verification", "tests/Otiz", "tests/Runtime", "tests/Jobs"):
            (self.root / directory).mkdir(parents=True, exist_ok=True)
        for category in ("unit", "governance", "integration", "e2e"):
            (self.root / f"tests/Delivery/{category}.py").write_text(f"print('{category}')\n")
        (self.root / "app/Domain").mkdir(parents=True)
        (self.root / "app/Domain/value.txt").write_text("base\n")
        self.spec = "specs/LIVE-001.md"
        (self.root / self.spec).write_text("# live context\n")
        (self.root / ".gitignore").write_text("__pycache__/\n*.py[cod]\n/change.json\n")
        (self.root / "quality-graph.yml").write_text("version: 1\n")
        policy = {
            "version": 1, "graph": "quality-graph.yml", "spec": "specs/CHANGE-VERIFICATION-001.md",
            "suite_inventory": "tools/verification/suites.tsv", "runtimes": {".py": "python3"},
            "category_argv": {name: [["python3", f"tests/Delivery/{name}.py"]]
                              for name in ("unit", "governance", "integration", "e2e")},
            "boundaries": [
                {"name": "server-rendered-presentation", "patterns": ["app/YiiRuntime/Views/**"],
                 "categories": ["unit"], "tests": ["tests/Delivery/unit.py"],
                 "fast_class": "bounded-server-rendered-presentation"},
                {"name": "application-code", "patterns": ["app/Domain/**"],
                 "categories": ["unit"], "tests": []},
                {"name": "delivery-policy", "patterns": ["tools/**", "tests/**", "specs/**", "openspec/**"],
                 "categories": ["governance"], "tests": []}],
            "verification_lanes": {"FAST": ["server-rendered-presentation"], "CRITICAL": ["delivery-policy"]},
            "fast_classes": {"bounded-server-rendered-presentation": {
                "companion_boundaries": [],
                "negative_boundaries_checked": ["product-spec-semantics", "verification-admission-policy"]}},
            "semantic_surfaces": [],
            "full_categories": ["unit", "integration", "e2e", "governance"],
            "full_argv": ["make", "test"]}
        (self.root / ".quality-graph/verification-policy.json").write_text(json.dumps(policy) + "\n")
        (self.root / "tools/verification/suites.tsv").write_text(
            "unit\tpython3\ttests/Delivery/e2e.py\te2e\n"
            "unit\tpython3\ttests/Delivery/governance.py\tgovernance\n"
            "unit\tpython3\ttests/Delivery/integration.py\tintegration\n"
            "unit\tpython3\ttests/Delivery/unit.py\tunit\n")
        self.git("init", "-q")
        self.git("config", "user.email", "x@example.invalid")
        self.git("config", "user.name", "x")
        self.git("add", ".")
        self.git("commit", "-qm", "base")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        (self.root / "app/Domain/value.txt").write_text("candidate\n")
        self.home = Path(self.temp.name) / "evidence"
        self.env = dict(os.environ, FMONITOR_HARNESS_HOME=str(self.home))

    def git(self, *args):
        return subprocess.run(["git", *args], cwd=self.root, text=True,
                              capture_output=True, check=True)

    def harness(self, *argv):
        return subprocess.run(["python3", "tools/delivery/harness.py", *argv],
                              cwd=self.root, env=self.env, text=True, capture_output=True)

    def write_input(self, change="live-context", paths=None):
        value = {"change": change, "planned_paths": paths or ["app/Domain/value.txt"],
                 "acceptances": [{"spec_id": "LIVE-001", "acceptance_id": "A-J",
                    "spec_path": self.spec, "seam": "delivery harness",
                    "tests": ["tests/Delivery/unit.py"]}]}
        (self.root / "change.json").write_text(json.dumps(value) + "\n")

    def prepare(self, change="live-context", paths=None):
        self.write_input(change, paths)
        result = self.harness("prepare", "--input", "change.json", "--base", self.base, "--role", "root")
        self.assertEqual(0, result.returncode, result.stderr)
        return json.loads(result.stdout)

    def state(self, command="state"):
        argv = [command]
        if command == "wait":
            argv.append("--once")
        result = self.harness(*argv)
        self.assertTrue(result.stdout, result.stderr)
        return result, json.loads(result.stdout)

    def record(self, gate="gate3", verdict="APPROVED"):
        return self.harness("record-review", "--gate", gate, "--verdict", verdict,
                        "--reviewer", "independent-reviewer", "--author", "root")

    def test_a_d_h_prepare_fresh_state_restores_exact_plan_and_missing_reviews(self):
        package = self.prepare()
        _, state = self.state()
        context = state["admission_context"]
        plan_bytes = Path(package["plan"]).read_bytes()
        plan = json.loads(plan_bytes)
        policy_digest = hashlib.sha256(
            (self.root / ".quality-graph/verification-policy.json").read_bytes()).hexdigest()
        self.assertEqual(hashlib.sha256(plan_bytes).hexdigest(), context["verification_plan"]["sha256"])
        self.assertEqual(plan, context["verification_plan"]["value"])
        self.assertEqual(context["verification_plan"]["value"]["commands"], context["selected_obligations"])
        self.assertEqual({"change": "live-context", "input": "change.json", "base": self.base,
                          "source": package["candidate_source"], "candidate": package["candidate_source"]},
                         {key: context["binding"][key]
                          for key in ("change", "input", "base", "source", "candidate")})
        self.assertEqual(policy_digest, context["policy"]["sha256"])
        self.assertTrue(context["reviews"])
        self.assertTrue(all(item["status"] == "MISSING" for item in context["reviews"]))
        self.assertNotIn("APPROVED", json.dumps(context["reviews"]))

    def test_b_current_gate_approval_is_structured_and_durable(self):
        self.prepare()
        recorded = self.record()
        self.assertEqual(0, recorded.returncode, "INTENDED_RED record-review absent: " + recorded.stderr)
        _, state = self.state()
        gate3 = next(item for item in state["admission_context"]["reviews"] if item["gate"] == "gate3")
        self.assertEqual(("CURRENT", "APPROVED", "independent-reviewer", "reviewer"),
                         (gate3["status"], gate3["verdict"], gate3["reviewer"], gate3["reviewer_role"]))
        self.assertEqual("root", gate3["author"])
        self.assertRegex(gate3["recorded_at"], r"^\d{4}-\d{2}-\d{2}T")
        expected = state["admission_context"]
        self.assertEqual({
            "change": expected["binding"]["change"], "input": expected["binding"]["input"],
            "base": expected["binding"]["base"], "source": expected["binding"]["source"],
            "candidate": expected["binding"]["candidate"],
            "plan_sha256": expected["verification_plan"]["sha256"],
            "policy_digest": expected["policy"]["sha256"], "gate": "gate3",
            "reviewer_role": "reviewer"}, gate3["binding"])

    def test_c_source_invalidating_change_makes_approval_stale(self):
        self.prepare()
        self.assertEqual(0, self.record().returncode)
        (self.root / "app/Domain/value.txt").write_text("changed again\n")
        _, state = self.state()
        gate3 = next(item for item in state["admission_context"]["reviews"] if item["gate"] == "gate3")
        self.assertEqual("STALE", gate3["status"])
        self.assertNotEqual("APPROVED", gate3.get("current_verdict"))

    def test_e_f_review_projection_is_exactly_planner_required(self):
        self.prepare()
        _, standard = self.state()
        plan = standard["admission_context"]["verification_plan"]["value"]
        self.assertEqual("STANDARD", plan["verification_lane"])
        self.assertEqual(["gate3", "final"], plan["required_reviews"])
        self.assertEqual(["gate3", "final"], [item["gate"] for item in standard["admission_context"]["reviews"]])

        # The planner alone chooses FAST for this bounded presentation-only candidate.
        view = self.root / "app/YiiRuntime/Views/card.php"
        view.parent.mkdir(parents=True, exist_ok=True)
        view.write_text("base\n")
        self.git("add", ".")
        self.git("commit", "-qm", "add view")
        self.base = self.git("rev-parse", "HEAD").stdout.strip()
        view.write_text("candidate\n")
        self.prepare(change="fast-live-context", paths=["app/YiiRuntime/Views/card.php"])
        _, fast = self.state()
        fast_plan = fast["admission_context"]["verification_plan"]["value"]
        self.assertEqual("FAST", fast_plan["verification_lane"])
        self.assertEqual(["final"], [item["gate"] for item in fast["admission_context"]["reviews"]])

    def test_g_three_live_commands_receive_identical_context(self):
        self.prepare()
        contexts = []
        for command in ("state", "wait", "prepare-merge"):
            _, value = self.state(command)
            contexts.append(value["admission_context"])
        self.assertEqual(contexts[0], contexts[1])
        self.assertEqual(contexts[0], contexts[2])

    def test_g_current_reviews_are_passed_to_existing_consumer_without_synthetic_gate(self):
        spec = importlib.util.spec_from_file_location(
            "live_admission_context", ROOT / "tools/delivery/harness_context.py")
        module = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(module)
        native = {"repository": "owner/repo", "pr": 1, "head": "a" * 40,
                  "base": "b" * 40, "candidate": "c" * 64, "mode": "fast",
                  "policy_digest": "d" * 64, "workflow": "quality.yml",
                  "run_id": 2, "attempt": 1}
        context = {"reviews": [
            {"gate": "gate3", "status": "STALE", "verdict": "APPROVED",
             "reviewer": "old", "author": "root"},
            {"gate": "final", "status": "CURRENT", "verdict": "APPROVED",
             "reviewer": "independent", "author": "root"}]}
        self.assertEqual([{"gate": 5, "verdict": "APPROVED", "reviewer": "independent",
                           "author": "root", "binding": native}],
                         module._admission_reviews(context, native))

    def test_i_foreign_composite_binding_is_not_current(self):
        self.prepare()
        self.assertEqual(0, self.record().returncode)
        _, state = self.state()
        binding_path = Path(state["active_binding_path"])
        original = json.loads(binding_path.read_text())
        cases = {"change": "foreign-task", "candidate": "f" * 64, "source": "e" * 64,
                 "plan_sha256": "d" * 64, "policy_digest": "c" * 64}
        for field, value in cases.items():
            with self.subTest(field=field):
                binding = json.loads(json.dumps(original))
                binding["review_results"][-1]["binding"][field] = value
                binding_path.write_text(json.dumps(binding) + "\n")
                _, foreign = self.state()
                gate3 = next(item for item in foreign["admission_context"]["reviews"] if item["gate"] == "gate3")
                self.assertNotEqual("CURRENT", gate3["status"])

    def test_i_foreign_task_review_is_missing_for_new_subject(self):
        self.prepare(change="task-a")
        self.assertEqual(0, self.record().returncode)

        self.prepare(change="task-b")
        _, task_b = self.state()
        gate3 = next(item for item in task_b["admission_context"]["reviews"]
                     if item["gate"] == "gate3")
        self.assertEqual("task-b", task_b["admission_context"]["binding"]["change"])
        self.assertEqual("MISSING", gate3["status"])
        self.assertNotIn("APPROVED", json.dumps(gate3))

    def test_rejected_review_inputs_fail_closed_and_duplicate_is_idempotent(self):
        self.prepare()
        cases = [
            (["record-review", "--gate", "gate2", "--verdict", "APPROVED",
              "--reviewer", "r", "--author", "a"], "gate"),
            (["record-review", "--gate", "gate3", "--verdict", "APPROVED",
              "--author", "a"], "reviewer"),
            (["record-review", "--gate", "gate3", "--verdict", "APPROVED",
              "--reviewer", "same", "--author", "same"], "independent")]
        for argv, message in cases:
            with self.subTest(argv=argv):
                rejected = self.harness(*argv)
                self.assertNotEqual(0, rejected.returncode)
                self.assertIn(message, rejected.stderr.lower())
        _, missing = self.state()
        self.assertTrue(all(item["status"] == "MISSING"
                            for item in missing["admission_context"]["reviews"]))
        self.assertEqual(0, self.record().returncode)
        self.assertEqual(0, self.record().returncode)
        _, repeated = self.state()
        current = [item for item in repeated["admission_context"]["reviews"]
                   if item["gate"] == "gate3" and item["status"] == "CURRENT"]
        self.assertEqual(1, len(current))

    def test_concurrent_identical_writers_leave_atomic_valid_context(self):
        self.prepare()
        argv = ["python3", "tools/delivery/harness.py", "record-review", "--gate", "gate3",
                "--verdict", "APPROVED", "--reviewer", "independent-reviewer", "--author", "root"]
        processes = [subprocess.Popen(argv, cwd=self.root, env=self.env, text=True,
                                      stdout=subprocess.PIPE, stderr=subprocess.PIPE) for _ in range(2)]
        results = [process.communicate() + (process.returncode,) for process in processes]
        self.assertTrue(all(code == 0 for _, _, code in results), results)
        _, state = self.state()
        gate3 = next(item for item in state["admission_context"]["reviews"] if item["gate"] == "gate3")
        self.assertEqual(("CURRENT", "APPROVED"), (gate3["status"], gate3["verdict"]))

    def test_prepare_and_record_share_binding_lock_without_lost_review(self):
        self.prepare()
        _, state = self.state()
        binding_path = Path(state["active_binding_path"])
        lock_path = binding_path.with_suffix(binding_path.suffix + ".lock")
        record_argv = ["python3", "tools/delivery/harness.py", "record-review", "--gate", "gate3",
                       "--verdict", "APPROVED", "--reviewer", "independent-reviewer",
                       "--author", "root"]
        prepare_argv = ["python3", "tools/delivery/harness.py", "prepare", "--input", "change.json",
                        "--base", self.base, "--role", "root"]
        with lock_path.open("a+") as lock:
            fcntl.flock(lock.fileno(), fcntl.LOCK_EX)
            record = subprocess.Popen(record_argv, cwd=self.root, env=self.env, text=True,
                                      stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            prepare = subprocess.Popen(prepare_argv, cwd=self.root, env=self.env, text=True,
                                       stdout=subprocess.PIPE, stderr=subprocess.PIPE)
            time.sleep(0.2)
            self.assertIsNone(record.poll(), "record-review did not honor active-binding lock")
            self.assertIsNone(prepare.poll(), "prepare did not honor active-binding lock")
            fcntl.flock(lock.fileno(), fcntl.LOCK_UN)
        record_out, record_err = record.communicate(timeout=10)
        prepare_out, prepare_err = prepare.communicate(timeout=10)
        self.assertEqual(0, record.returncode, record_err)
        self.assertEqual(0, prepare.returncode, prepare_err)
        _, after = self.state()
        gate3 = next(item for item in after["admission_context"]["reviews"] if item["gate"] == "gate3")
        self.assertEqual(("CURRENT", "APPROVED"), (gate3["status"], gate3["verdict"]))
        retained = self.prepare()
        self.assertEqual("APPROVED", retained["review_results"][0]["verdict"])

    def test_j_recording_review_does_not_change_candidate_hash(self):
        self.prepare()
        _, before = self.state()
        status_before = self.git("status", "--porcelain=v1", "--untracked-files=all").stdout
        recorded = self.record()
        self.assertEqual(0, recorded.returncode)
        _, after = self.state()
        self.assertEqual(before["source"], after["source"])
        self.assertEqual(before["admission_context"]["binding"]["candidate"],
                         after["admission_context"]["binding"]["candidate"])
        self.assertEqual(status_before, self.git("status", "--porcelain=v1", "--untracked-files=all").stdout)


if __name__ == "__main__":
    unittest.main()
