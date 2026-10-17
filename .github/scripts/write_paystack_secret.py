"""Write includes/paystack_secret.php from the PAYSTACK_SECRET_KEY env var.

Run by the deploy workflow just before the FTP upload, so the key lives
only in GitHub secrets and on the server, never in git.
"""
import os
import re
import sys

PATH = "includes/paystack_secret.php"

key = os.environ.get("PAYSTACK_SECRET_KEY", "").strip()
if not key:
    # Deploy the rest of the site anyway; checkout shows a friendly
    # "payment unavailable" message until the key is added.
    print("::warning::PAYSTACK_SECRET_KEY is not set, so Paystack payments are off. "
          "Add the secret and re-run this workflow to turn them on.")
    sys.exit(0)
# Paystack secret keys look like sk_live_xxx or sk_test_xxx; checking the
# shape also means the value is safe inside a PHP string.
if not re.fullmatch(r"sk_(live|test)_[A-Za-z0-9]+", key):
    sys.exit("PAYSTACK_SECRET_KEY does not look like a Paystack secret key (sk_live_... or sk_test_...)")

with open(PATH, "w") as f:
    f.write("<?php\n// Generated at deploy time. Do not commit.\n")
    f.write("define('PAYSTACK_SECRET_KEY', '%s');\n" % key)

print("Wrote " + PATH + (" (test mode)" if key.startswith("sk_test_") else " (live mode)"))
