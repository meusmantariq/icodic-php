#!/usr/bin/env bash
# Smoke test: php -S must be running (cd public_html && php -S 127.0.0.1:8088 index.php). Usage: tests/smoke.sh [base_url]
B=${1:-http://127.0.0.1:8088}; fail=0
chk(){ code=$(curl -s -o /tmp/smoke.html -w '%{http_code}' "$B$1"); errs=$(grep -ciE 'warning:|deprecated:|fatal error|notice:' /tmp/smoke.html); if [ "$code" = "$2" ] && [ "$errs" = "0" ]; then echo "PASS $1 ($code)"; else echo "FAIL $1 (got $code, php errors $errs)"; fail=1; fi; }
for p in / /about /services /projects /cyber-security /contact /track-order /solutions/erpnext /solutions/odoo /solutions/custom-crm /solutions/custom-erp /erp/5 /admin/login; do chk $p 200; done
chk /does-not-exist 404
code=$(curl -s -o /dev/null -w '%{http_code}' "$B/admin/software"); [ "$code" = "302" ] && echo "PASS admin requires login" || { echo "FAIL admin auth ($code)"; fail=1; }
code=$(curl -s -o /dev/null -w '%{http_code}' -X POST -d "name=x" "$B/contact"); [ "$code" = "403" ] && echo "PASS CSRF enforced" || { echo "FAIL CSRF ($code)"; fail=1; }
curl -s -c /tmp/sm -o /dev/null "$B/prefs?region=PK&lang=ur&back=/"; curl -s -b /tmp/sm "$B/" | grep -q 'dir="rtl"' && echo "PASS region/lang switch (PK/ur → RTL)" || { echo "FAIL region/lang switch"; fail=1; }
exit $fail
