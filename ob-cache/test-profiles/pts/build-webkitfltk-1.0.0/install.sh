#!/bin/sh

echo "#!/bin/sh
cd webkitfltk-0.1.1/
make -s -j \$NUM_CPU_JOBS 2>&1 -C Source/WTF/wtf &&
make -s -j \$NUM_CPU_JOBS 2>&1 -C Source/JavaScriptCore gen &&
make -s -j \$NUM_CPU_JOBS 2>&1 -C Source/JavaScriptCore &&
make -s -j \$NUM_CPU_JOBS 2>&1 -C Source/WebCore &&
make -s -j \$NUM_CPU_JOBS 2>&1 -C Source/WebKit/fltk
echo \$? > ~/test-exit-status" > build-webkitfltk

chmod +x build-webkitfltk
