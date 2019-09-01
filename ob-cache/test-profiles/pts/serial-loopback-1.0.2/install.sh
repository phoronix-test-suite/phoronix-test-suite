#!/bin/sh

cat > ~/serial-loopback << EOT
#!/bin/bash
TEST_CONTENTS="Phoronix Test Suite 1234567890"
dmesg > dmesg-output 2>&1
RESULT_SCALE=""
RESULTS=""

for ttyf in /dev/tty*S*
do
    ttybase=\$(basename \$ttyf)
    if grep "\$ttybase" dmesg-output >/dev/null
    then
        stty -F \$ttyf -echo -onlcr
        cat \$ttyf > output-file &
        CAT_PID=\$!
        sleep 1
        echo \$TEST_CONTENTS > \$ttyf
        sleep 5
        kill \$CAT_PID >/dev/null
        RESULT_SCALE="\$RESULT_SCALE, \$ttyf"
        if grep -Fxq "\$TEST_CONTENTS" output-file
        then
            RESULTS="\$RESULTS,PASS"
        else
            RESULTS="\$RESULTS,FAIL"
        fi
        rm output-file >/dev/null 2>&1
    fi
done
rm dmesg-output

if [ "X\$RESULTS" = "X" ]
then
	exit 2
fi

RESULT_SCALE=\${RESULT_SCALE:2}
RESULTS=\${RESULTS:1}
echo \$RESULT_SCALE > ~/pts-results-scale
echo \$RESULT_SCALE > ~/pts-test-description
echo \$RESULTS > \$LOG_FILE

EOT

chmod +x serial-loopback
