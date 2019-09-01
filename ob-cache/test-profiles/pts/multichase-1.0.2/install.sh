#!/bin/sh

tar -xjvf multichase-1.tar.bz2
mv multichase multichase-bin
cd multichase-bin

echo "--- Makefile	2016-12-29 01:46:00.387366591 +0000
+++ Makefile.new	2016-12-29 01:45:54.284206284 +0000
@@ -11,7 +11,7 @@
 # See the License for the specific language governing permissions and
 # limitations under the License.
 #
-CFLAGS=-std=gnu99 -g -O2 -fomit-frame-pointer -fno-unroll-loops -Wall -Wstrict-prototypes -Wmissing-prototypes -Wshadow -Wmissing-declarations -Wnested-externs -Wpointer-arith -W -Wno-unused-parameter -Werror -pthread
+CFLAGS=-std=gnu99 -g -O2 -fomit-frame-pointer -fno-unroll-loops -Wall -Wstrict-prototypes -Wmissing-prototypes -Wshadow -Wmissing-declarations -Wnested-externs -Wpointer-arith -W -Wno-unused-parameter -pthread
 LDFLAGS=-g -O2 -static -pthread
 LDLIBS=-lrt
" | patch -p0

make
echo 0 > ~/test-exit-status
cd ~/

echo "#!/bin/sh
cd multichase-bin
./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > multichase
chmod +x multichase
