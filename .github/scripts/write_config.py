"""Fill includes/config.php with live settings from environment variables.

Run by the deploy workflow just before the FTP upload, so the database
password lives only in GitHub secrets and on the server, never in git.
"""
import os
import re
import sys

KEYS = ["DB_HOST", "DB_USER", "DB_PASS", "DB_NAME", "SITE_URL"]
PATH = "includes/config.php"


def php_quote(value):
    # Contents of a PHP single-quoted string: only \ and ' need escaping.
    return value.replace("\\", "\\\\").replace("'", "\\'")


missing = [k for k in KEYS if not os.environ.get(k, "").strip()]
if missing:
    sys.exit("Missing repository secrets: " + ", ".join(missing))

with open(PATH) as f:
    src = f.read()

for key in KEYS:
    value = os.environ[key].strip()
    if key == "SITE_URL":
        value = value.rstrip("/")
    line = "define('%s', '%s')" % (key, php_quote(value))
    src, count = re.subn(r"define\('%s',\s*'[^']*'\)" % key, lambda _: line, src)
    if count != 1:
        sys.exit("Expected one define('%s', ...) in %s, found %d" % (key, PATH, count))

with open(PATH, "w") as f:
    f.write(src)

print("Wrote live settings into " + PATH)
