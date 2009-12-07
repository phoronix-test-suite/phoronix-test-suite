#!/bin/sh

echo "#!/bin/sh

rm -f ~/.nexuiz/data/screenshots/*
cd \$TEST_NEXUIZ
./nexuiz +exec effects-ultimate.cfg -benchmark demos/demo4 +vid_width 1280 +vid_height 1024 -nosound +alias play2 screenshot +scr_screenshot_jpeg 0 +con_notify 0 +crosshair 0 +viewsize 120 +r_hdr_scenebrightness 2
rm -f \$LOG_FILE" > nexuiz-iqc
chmod +x nexuiz-iqc
