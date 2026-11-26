#!/bin/sh
set -eu

printer=${1:?Supply the Mac printer queue name from lpstat -p}
script_dir=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
project=$(CDPATH= cd -- "$script_dir/../.." && pwd)
spool=$project/storage/worksheet-print
label=local.home-hub.worksheet-print
agents=$HOME/Library/LaunchAgents
plist=$agents/$label.plist
domain=gui/$(id -u)

/usr/bin/lpstat -p "$printer" >/dev/null
mkdir -p "$spool" "$agents"
chmod 0770 "$spool"
umask 077
temp=$(mktemp "$agents/$label.XXXXXX")
trap 'rm -f "$temp"' EXIT
/usr/bin/python3 - "$temp" "$label" "$script_dir/dispatch-macos.sh" "$spool" "$printer" <<'PY'
import plistlib
import sys

destination, label, script, spool, printer = sys.argv[1:]
with open(destination, "wb") as config:
    plistlib.dump({
        "Label": label,
        "ProgramArguments": ["/bin/sh", script, spool, printer],
        "WatchPaths": [spool],
        "StartInterval": 10,
        "RunAtLoad": True,
    }, config)
PY
/usr/bin/plutil -lint "$temp" >/dev/null
/bin/launchctl bootout "$domain/$label" 2>/dev/null || true
mv "$temp" "$plist"
/bin/launchctl bootstrap "$domain" "$plist"
printf 'Home Hub print queue connected to %s.\n' "$printer"
