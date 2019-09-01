#!/bin/sh

echo "#!/bin/sh
echo \"PASS,PASS,FAIL,PASS\" > \$LOG_FILE
echo \$? > ~/test-exit-status" > sample-pass-fail

chmod +x sample-pass-fail

