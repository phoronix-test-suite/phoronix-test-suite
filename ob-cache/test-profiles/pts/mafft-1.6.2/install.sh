#!/bin/sh

rm -rf $HOME/mafft_
mkdir $HOME/mafft_
tar -xvf mafft-7.471-without-extensions-src.tgz
cd mafft-7.471-without-extensions/core/

if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi

sed -i -e "s|PREFIX = /usr/local|PREFIX = $HOME/mafft_|g" Makefile

if [ $OS_TYPE = "BSD" ]
then
	gmake -j $NUM_CPU_CORES ENABLE_MULTITHREAD=-Denablemultithread
else
	make -j $NUM_CPU_CORES ENABLE_MULTITHREAD=-Denablemultithread
fi

echo $? > ~/install-exit-status
if [ $OS_TYPE = "BSD" ]
then
	gmake install
else
	make install
fi
cd ~/
cp -f mafft-7.471-without-extensions/scripts/mafft mafft_/
rm -rf mafft-7.471-without-extensions/

cp mafft-ex1-lsu-rna.txt mafft_

if [ -x /usr/pkg/bin/bash ]
then
	# bsd fix
	sed -i -e "s|/bin/bash|/usr/pkg/bin/bash|g" mafft_/mafft
fi

cat>mafft<<EOT
#!/bin/sh
cd mafft_/
./mafft --thread \$NUM_CPU_CORES --auto mafft-ex1-lsu-rna.txt > \$LOG_FILE
echo \$? > ~/test-exit-status
EOT
chmod +x mafft
