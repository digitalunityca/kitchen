#!/bin/sh
set -eu

spool=$1
printer=$2
umask 027

for pdf in "$spool"/*.pdf; do
    [ -f "$pdf" ] && [ ! -L "$pdf" ] || continue
    job=${pdf%.pdf}
    name=${job##*/}
    case "$name" in
        ''|*[!0-9a-f-]*) continue ;;
    esac
    [ "${#name}" -eq 36 ] || continue

    if ! /bin/mv "$pdf" "$job.working"; then
        continue
    fi
    if /usr/bin/lp -d "$printer" -t 'Home Hub homework' -o media=Letter -o sides=one-sided < "$job.working"; then
        : > "$job.sent"
    else
        : > "$job.failed"
    fi
    /bin/rm -f "$job.working"
done

/usr/bin/find "$spool" -maxdepth 1 -type f \( -name '*.sent' -o -name '*.failed' -o -name '*.working' -o -name '*.tmp' \) -mtime +1 -delete
