#!/bin/sh

unzip -o crafty-23.0.zip

patch -p0 <<'EOT'
diff -Naur crafty-23.0.orig/Makefile crafty-23.0/Makefile
--- crafty-23.0.orig/Makefile	2009-03-24 14:52:20.000000000 -0400
+++ crafty-23.0/Makefile	2009-05-04 21:56:50.000000000 -0400
@@ -150,7 +150,7 @@
                         -fprofile-arcs -fomit-frame-pointer -O3 -march=k8' \
 		CXFLAGS=$(CFLAGS) \
 		LDFLAGS='$(LDFLAGS) -lpthread -lnuma -fprofile-arcs -lstdc++' \
-		opt='$(opt) -DINLINE64 -DCPUS=8 -DNUMA -DLIBNUMA' \
+		opt='$(opt) -DINLINE64 -DCPUS=8 -DLIBNUMA' \
 		crafty-make
 
 linux-amd64:
@@ -160,7 +160,7 @@
                 -fbranch-probabilities -fomit-frame-pointer -O3 -march=k8' \
 		CXFLAGS=$(CFLAGS) \
 		LDFLAGS='$(LDFLAGS) -lpthread -lnuma -lstdc++' \
-		opt='$(opt) -DINLINE64 -DCPUS=8 -DNUMA -DLIBNUMA' \
+		opt='$(opt) -DINLINE64 -DCPUS=8 -DLIBNUMA' \
 		crafty-make
 
 linux:
EOT

cd crafty-23.0/

if [ $OS_TYPE = "MacOSX" ]
then
	make darwin
elif [ $OS_TYPE = "BSD" ]
then
	make freebsd
elif [ $OS_TYPE = "Solaris" ]
then
	make solaris-gcc
else
	case $OS_ARCH in
		"x86_64" )
		make linux-amd64
		;;
		* )
		make linux
		;;
	esac
fi
echo $? > ~/install-exit-status

cd ..

echo "#!/bin/sh
cd crafty-23.0/
./crafty \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > crafty
chmod +x crafty
