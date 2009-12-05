#!/bin/sh

echo "#!/bin/sh

cd \$TEST_VDRIFT
HOME=\$TEST_VDRIFT ./vdrift \$@" > vdrift-fps-monitor
chmod +x vdrift-fps-monitor
