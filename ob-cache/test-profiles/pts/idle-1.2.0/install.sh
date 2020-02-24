#!/bin/sh

echo "#!/bin/sh
SLEEPTIME=\$((\$1 * 60))
echo \"Sleeping for \$1 minutes.\"
sleep \$SLEEPTIME
echo \"Result: PASS\" > \$LOG_FILE" > idle
chmod +x idle
