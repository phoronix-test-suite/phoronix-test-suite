#!/bin/sh

unzip -o warsow25-pts11.zip
unzip -o warsow-2.5beta.zip
chmod +x warsow_25_beta/warsow.*
mkdir -p warsow_25_beta/basewsw/demos
cp -f pts11.wdz25 warsow_25_beta/basewsw/demos

echo "#!/bin/sh
cd warsow_25_beta/
if [ \$OS_ARCH = \"x86_64\" ]
then
	./warsow.x86_64 \$@ > \$LOG_FILE
else
	./warsow \$@ > \$LOG_FILE
fi
" > warsow
chmod +x warsow
