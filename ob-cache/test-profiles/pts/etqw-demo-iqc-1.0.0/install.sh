#!/bin/sh

echo "#!/bin/sh

rm -f ~/.etqwcl/base/screenshots/*
cd \$TEST_ETQW_DEMO
./etqw +set sys_VideoRam \$VIDEO_MEMORY +set r_mode -1 +set in_tty 0 +set r_customWidth 1280 +set r_customHeight 1024 +vid_restart +exec etqw-pts-iqc.cfg
mv -f ~/.etqwcl/base/screenshots/*.tga ~/
rm -f \$LOG_FILE" > etqw-demo-iqc
chmod +x etqw-demo-iqc
