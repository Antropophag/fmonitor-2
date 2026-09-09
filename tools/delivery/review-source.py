#!/usr/bin/env python3
"""Capture and restore a reconstructible local Git review candidate."""

import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile


class ReviewSourceError(Exception):
    """A user-visible, fail-closed review source error."""


def run_git(repository, *arguments, env=None, binary=False):
    result = subprocess.run(
        ["git", "-C", str(repository), *arguments],
        capture_output=True,
        text=not binary,
        env=env,
    )
    if result.returncode:
        stderr = result.stderr.decode(errors="replace") if binary else result.stderr
        raise ReviewSourceError(stderr.strip() or "Git command failed")
    return result.stdout


def path_exists(path):
    return os.path.lexists(path)


def inside(path, directory):
    try:
        path.relative_to(directory)
        return True
    except ValueError:
        return False


def absolute_unresolved(value):
    return Path(os.path.abspath(Path(value).expanduser()))


def capture(repository_arg, output_arg):
    repository = Path(repository_arg).expanduser().resolve()
    raw_output = absolute_unresolved(output_arg)
    if path_exists(raw_output):
        raise ReviewSourceError(f"snapshot output already exists: {raw_output}")
    output = raw_output.resolve()

    top_level = Path(run_git(repository, "rev-parse", "--show-toplevel").strip()).resolve()
    if inside(output, top_level):
        raise ReviewSourceError("snapshot output must be outside the source checkout")
    repository = top_level
    base_commit = run_git(repository, "rev-parse", "--verify", "HEAD^{commit}").strip()

    output.parent.mkdir(parents=True, exist_ok=True)
    temporary = Path(tempfile.mkdtemp(prefix=f".{output.name}.tmp-", dir=output.parent))
    index_path = temporary / "capture.index"
    environment = os.environ.copy()
    environment["GIT_INDEX_FILE"] = str(index_path)
    try:
        run_git(repository, "read-tree", "HEAD", env=environment)
        run_git(repository, "add", "-A", "--", ".", env=environment)
        patch = run_git(
            repository,
            "diff",
            "--cached",
            "--binary",
            "--full-index",
            "--no-color",
            "HEAD",
            "--",
            env=environment,
            binary=True,
        )
        digest = hashlib.sha256(patch).hexdigest()
        (temporary / "source.patch").write_bytes(patch)
        index_path.unlink(missing_ok=True)
        manifest = {
            "version": 1,
            "base_commit": base_commit,
            "repository": str(repository),
            "patch_sha256": digest,
        }
        (temporary / "manifest.json").write_text(
            json.dumps(manifest, sort_keys=True, indent=2) + "\n", encoding="utf-8"
        )
        temporary.rename(output)
    except Exception:
        shutil.rmtree(temporary, ignore_errors=True)
        raise

    print(json.dumps({"snapshot": str(output), "patch_sha256": digest}, sort_keys=True))


def load_snapshot(snapshot):
    if not snapshot.is_dir():
        raise ReviewSourceError(f"snapshot directory is unavailable: {snapshot}")
    manifest_path = snapshot / "manifest.json"
    patch_path = snapshot / "source.patch"
    try:
        manifest = json.loads(manifest_path.read_text(encoding="utf-8"))
        patch = patch_path.read_bytes()
    except (OSError, UnicodeError, json.JSONDecodeError) as error:
        raise ReviewSourceError(f"invalid snapshot: {error}") from error
    if not isinstance(manifest, dict) or manifest.get("version") != 1:
        raise ReviewSourceError("unsupported or invalid snapshot manifest version")
    for key in ("base_commit", "repository", "patch_sha256"):
        if not isinstance(manifest.get(key), str) or not manifest[key]:
            raise ReviewSourceError(f"invalid snapshot manifest field: {key}")
    digest = hashlib.sha256(patch).hexdigest()
    if manifest["patch_sha256"] != digest:
        raise ReviewSourceError("snapshot patch SHA-256 mismatch")
    repository_value = manifest["repository"]
    repository = Path(repository_value)
    if not repository.is_absolute():
        raise ReviewSourceError("snapshot repository path is not absolute")
    repository = repository.resolve()
    base = manifest["base_commit"]
    resolved = run_git(repository, "rev-parse", "--verify", base + "^{commit}").strip()
    if resolved != base:
        raise ReviewSourceError("snapshot base commit is unavailable or not canonical")
    return repository, base, patch


def restore(snapshot_arg, output_arg):
    snapshot = Path(snapshot_arg).expanduser().resolve()
    raw_output = absolute_unresolved(output_arg)
    if path_exists(raw_output):
        raise ReviewSourceError(f"restore destination already exists: {raw_output}")
    output = raw_output.resolve()

    repository, base, patch = load_snapshot(snapshot)
    if inside(output, repository) or inside(output, snapshot):
        raise ReviewSourceError("restore destination must be outside the source checkout and snapshot")
    output.parent.mkdir(parents=True, exist_ok=True)
    worktree_created = False
    try:
        run_git(repository, "worktree", "add", "--detach", str(output), base)
        worktree_created = True
        if patch:
            result = subprocess.run(
                ["git", "-C", str(output), "apply", "--index", "--binary", "-"],
                input=patch,
                capture_output=True,
            )
            if result.returncode:
                raise ReviewSourceError(
                    result.stderr.decode(errors="replace").strip() or "snapshot patch did not apply"
                )
    except Exception:
        if worktree_created:
            subprocess.run(
                ["git", "-C", str(repository), "worktree", "remove", "--force", str(output)],
                capture_output=True,
            )
            if path_exists(output):
                shutil.rmtree(output, ignore_errors=True)
        raise

    print(json.dumps({"destination": str(output), "base_commit": base}, sort_keys=True))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    subcommands = parser.add_subparsers(dest="command", required=True)
    capture_parser = subcommands.add_parser("capture")
    capture_parser.add_argument("--repo", required=True)
    capture_parser.add_argument("--output", required=True)
    restore_parser = subcommands.add_parser("restore")
    restore_parser.add_argument("--snapshot", required=True)
    restore_parser.add_argument("--output", required=True)
    arguments = parser.parse_args()
    try:
        if arguments.command == "capture":
            capture(arguments.repo, arguments.output)
        else:
            restore(arguments.snapshot, arguments.output)
    except (OSError, ReviewSourceError) as error:
        print(f"REVIEW_SOURCE_ERROR: {error}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
