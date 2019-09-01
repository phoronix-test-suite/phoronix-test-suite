#!/bin/sh

echo "#!/bin/sh
if which phoronix-test-suite 2>&1 ;
then
	PTS_LAUNCHER=\`which phoronix-test-suite\`
fi
cd `dirname \$PTS_LAUNCHER`
PTS_SILENT_MODE=1 PHP_BIN=\$PHP_BIN ./\`basename \$PTS_LAUNCHER\` debug-self-test > \$LOG_FILE 2>&1" > pts-self-test
chmod +x pts-self-test
