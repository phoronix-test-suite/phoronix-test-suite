#!/bin/sh

if which clpeak >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: clpeak is not found on the system!"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/sh
clpeak \$@ > \$LOG_FILE 2>&1" > clpeak
chmod +x clpeak


