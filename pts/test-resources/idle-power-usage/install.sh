#!/bin/sh

echo "#!/bin/sh

\$SYSTEM_MONITOR_START system.current all-comma 1
SLEEPTIME=\$((\$1 * 60))
echo \"Sleeping for \$1 minutes.\"
sleep \$SLEEPTIME
\$SYSTEM_MONITOR_STOP \$LOG_FILE" > idle-power-usage
chmod +x idle-power-usage
