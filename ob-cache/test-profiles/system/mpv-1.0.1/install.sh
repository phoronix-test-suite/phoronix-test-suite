#!/bin/sh

if which mpv>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: MPV is not found on the system! This test profile needs a working mpv player in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

echo "#!/bin/sh
mpv -V | head -n 1 | cut -d \" \" -f1,2 > ~/pts-footnote

mpv --no-audio --untimed --opengl-swapinterval=0 --video-sync=display-desync -v --osd-msg1=\"FPS: \\\${estimated-display-fps}\"  --term-status-msg=\"FPS: \\\${estimated-display-fps}\" --term-osd-bar --length=300 --log-file=\$LOG_FILE \$@
echo \$? > ~/test-exit-status

" > mpv-run
chmod +x mpv-run
