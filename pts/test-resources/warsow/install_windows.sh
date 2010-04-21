#!/bin/sh

unzip -o pts-warsow-3.zip

unzip -o warsow_0.5_unified.zip
mkdir basewsw/demos
cp pts-bardu.wd11 basewsw/demos
cp pts-warsow.cfg basewsw/

echo "#!/bin/sh
warsow_x64.exe \$@ +set fs_usehomedir 0
mv basewsw/pts-log.log \$LOG_FILE" > warsow
chmod +x warsow
