#!/bin/sh

echo "#!/bin/sh
SLEEPTIME=\$((\$1 * 60))
echo \"Sleeping for \$1 minutes.\"
sleep \$SLEEPTIME" > idle
chmod +x idle
