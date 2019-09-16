#!/bin/sh

mkdir $HOME/lame_

tar -zxvf lame-3.100.tar.gz
cd lame-3.100/

cat > power9-fix.patch <<EOT
--- configure.in.old	2017-08-15 11:16:31.000000000 -0400
+++ configure.in	2018-09-23 19:41:40.012250933 -0400
@@ -94,9 +94,9 @@
 
 	if test "\${HAVE_GCC}" = "yes"; then
 		AC_MSG_CHECKING(version of GCC)
-		GCC_version="\`\${CC} --version | sed -n '1s/^[[^ ]]* (.*) //;s/ .*\$//;1p'\`"
+		GCC_version="\`\${CC} --version | head -n1 | awk '{print \$3}' \`"
 		case "\${GCC_version}" in 
-		[0-9]*[0-9]*)
+		[[0-9]]*[[0-9]]*)
 			AC_MSG_RESULT(\${GCC_version})
 			;;
 		*)
@@ -701,7 +701,7 @@
 	AC_DEFINE(TAKEHIRO_IEEE754_HACK, 1, IEEE754 compatible machine)
 	AC_DEFINE(USE_FAST_LOG, 1, faster log implementation with less but enough precission)
 	;;
-powerpc)
+powerpc*|ppc64*)
 	CPUTYPE="no"
 
 	# use internal knowledge of the IEEE 754 layout
EOT
patch -p0 < power9-fix.patch
autoconf

./configure --prefix=$HOME/lame_ --enable-expopt=full
make
echo $? > ~/install-exit-status
make install
cd ~
#rm -rf lame-3.100/

echo "#!/bin/sh
./lame_/bin/lame -h \$TEST_EXTENDS/pts-trondheim.wav /dev/null 2>&1
echo \$? > ~/test-exit-status" > lame
chmod +x lame
