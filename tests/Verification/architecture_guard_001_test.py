#!/usr/bin/env python3
"""Run the architecture checker's executable policy fixtures in the CI inventory."""
import subprocess
import sys
from pathlib import Path

root = Path(__file__).resolve().parents[2]
raise SystemExit(subprocess.run(
    [sys.executable, "-m", "unittest", "discover", "-s", "tools/architecture/tests"],
    cwd=root,
).returncode)
