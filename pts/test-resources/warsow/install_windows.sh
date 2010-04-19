#!/bin/sh

unzip -o pts-warsow-3.zip

unzip -o warsow_0.5_unified.zip
mkdir basewsw/demos
cp pts-bardu.wd11 basewsw/demos
cp pts-warsow.cfg basewsw/

echo "#!/bin/sh
warsow_x64.exe \$@
cp ~/.warsow-0.5/basewsw/pts-log.log \$LOG_FILE" > warsow
chmod +x warsow
