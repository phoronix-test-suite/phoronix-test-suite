#!/bin/bash

if which pip3>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Python pip3 is not found on the system! This test profile needs Python pip3 to proceed."
	echo 2 > ~/install-exit-status
fi

pip3 install --user plaidml-keras plaidbench

if [[ ! -x "$HOME/.local/bin/plaidml-setup" ]]
then
	echo "ERROR: PlaidML failed to install on the system!"
	echo 2 > ~/install-exit-status
fi

echo "#!/bin/bash

TARGET_DEVICE=\`echo \"\$@\" | awk '{print \$NF}'\`
if [ \$TARGET_DEVICE = \"CPU\" ]
then
	TARGET_DEVICE=\"llvm_cpu.0\"
elif [ \$TARGET_DEVICE = \"OPENCL\" ]
then
	TARGET_DEVICE=\`echo n | ~/.local/bin/plaidml-setup 2>/dev/null  | grep opencl | head -n 1 | awk '{print \$1;}'\`
fi

echo \"{
    \\\"PLAIDML_DEVICE_IDS\\\":[
        \\\"\$TARGET_DEVICE\\\"
    ],
   \\\"PLAIDML_EXPERIMENTAL\\\":true,
    \\\"PLAIDML_TELEMETRY\\\":false
}\" > \$HOME/.plaidml

ARGS=\`echo \"\$@\" | awk '{\$NF=\"\";sub(/[ \t]+$/,\"\")}1'\`

\$HOME/.local/bin/plaidbench \$ARGS > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > plaidml
chmod +x plaidml
