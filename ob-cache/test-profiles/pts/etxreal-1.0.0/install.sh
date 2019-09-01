#!/bin/sh

chmod +x et-linux-2.60.x86.run
./et-linux-2.60.x86.run --target et-original --noexec

7za -y x ETXreaL-0.3.0-20111110.7z
cp et-original/etmain/*.pk3 ETXreaL-0.3.0-20111110/etmain
rm -rf et-original

tar -zxvf etxreal-demos-1.tar.gz
mv -f pts-etconfig.cfg ETXreaL-0.3.0-20111110/etmain
mkdir ETXreaL-0.3.0-20111110/etmain/demos
mv -f pts.dm_84 ETXreaL-0.3.0-20111110/etmain/demos

echo "#!/bin/sh
cd ETXreaL-0.3.0-20111110/

case \$OS_ARCH in
	\"x86_64\" )
	./bin/linux-x86_64/etxreal \$@ > \$LOG_FILE 2>&1
	;;
	* )
	./bin/linux-x86/etxreal \$@ > \$LOG_FILE 2>&1
	;;
esac
" > etxreal
chmod +x etxreal
