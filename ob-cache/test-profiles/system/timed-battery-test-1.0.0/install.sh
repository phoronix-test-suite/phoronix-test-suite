#!/bin/bash

echo "#!/bin/bash
CONTROL_FILE=\`echo \"\$@\" | sha256sum - | echo \$(awk '{print \$1}')\`

if [ -f \$CONTROL_FILE ]
then
	cat \$CONTROL_FILE > \$LOG_FILE
	rm -f \$CONTROL_FILE
	exit 0
fi

case \$1 in
  \"GOOGLE_CHROME_YOUTUBE\")
    google-chrome \"https://www.youtube.com/watch?v=sMnw28M-fMg&list=UU9NuJImUbaSNKiwF2bdSfAw\" &
    ;;
  \"IDLE\")
    ;;
  *)
    echo -n "unknown"
    ;;
esac

while true
do
	UPTIME_NOW=\`awk '{print \$1}' /proc/uptime\`
	echo \"UPTIME: \$UPTIME_NOW\" > \$CONTROL_FILE
done
echo 2 > ~/install-exit-status
" > timed-battery-test
chmod +x timed-battery-test
