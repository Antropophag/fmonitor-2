#!/usr/bin/env python3
import subprocess
import sys
from pathlib import Path

root = Path(__file__).resolve().parents[2]
result = subprocess.run(["make", "architecture-check"], cwd=root, text=True, capture_output=True)
if result.returncode != 0:
    sys.stderr.write(result.stdout)
    sys.stderr.write(result.stderr)
    raise SystemExit(result.returncode)
print("TEMPLATE_OFFER_ARCHITECTURE_OK")
