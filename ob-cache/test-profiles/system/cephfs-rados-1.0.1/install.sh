#!/bin/bash
if which microceph>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: microceph is not currently installed on this system"
	echo 2 > ~/install-exit-status
fi
# initial setup: https://ubuntu.com/ceph/install
microceph.ceph osd pool create ptstestbench 100 100
microceph.ceph osd pool set ptstestbench pg_autoscale_mode on
echo "#!/bin/bash
microceph.rados bench -p ptstestbench 120 \$1 --run-name pts > \$LOG_FILE
echo \$? > ~/test-exit-status
ceph --version | head -n 1 > ~/pts-test-version 2>/dev/null" > cephfs-rados
chmod +x cephfs-rados
