#!/bin/bash
7z x -y SPECjbb2015-1.02.iso -oSPECjbb2015
chmod +x SPECjbb2015/run_composite.sh

echo "#!/bin/bash
cd SPECjbb2015
rm -f *-*-*/result/specjbb2015-C-*/report-*/specjbb2015-C-*.raw

export SPEC_OPTS=\"-Dspecjbb.group.count=3\"
HEAP_SIZE=\`echo \"\$SYS_MEMORY * 0.85 / 1024\" | bc\`
XMN_OFFSET=10
if [ \"\$HEAP_SIZE\" -gt 64 ]; then
    XMN_OFFSET=20
fi
XMN_SIZE=\`echo \"\$HEAP_SIZE - \$XMN_OFFSET\" | bc\`
export JAVA_OPTS=\"-Xms\${HEAP_SIZE}g -Xmx\${HEAP_SIZE}g -Xmn\${XMN_SIZE}g -server -XX:+UseParallelGC \$JAVA_OPTS\"
echo \"SPEC_OPTS = \$SPEC_OPTS\" > \$LOG_FILE
echo \"JAVA_OPTS = \$JAVA_OPTS\" >> \$LOG_FILE
./run_composite.sh
echo \$? > ~/test-exit-status
cat *-*-*/result/specjbb2015-C-*/report-*/specjbb2015-C-*.raw >> \$LOG_FILE
" > spec-jbb2015
chmod +x spec-jbb2015
