#!/bin/sh

if which glmark2>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: glmark2 is not found on the system! This test profile needs a working blender installation in the PATH."
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
glmark2 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > glmark2
chmod +x glmark2
