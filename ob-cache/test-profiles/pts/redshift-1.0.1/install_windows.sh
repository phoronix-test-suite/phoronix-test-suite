#!/bin/sh
chmod +x redshift_v3.0.28_demo_setup.exe
/cygdrive/c/Windows/system32/cmd.exe /c redshift_v3.0.28_demo_setup.exe

unzip -o RedshiftBenchmarkScenes.zip

echo "#!/bin/sh
/cygdrive/c/ProgramData/Redshift/bin/redshiftBenchmark.exe RedshiftBenchmarkScenes/vultures/Vultures.rs > \$LOG_FILE
echo \$? > ~/test-exit-status" > redshift
chmod +x redshift
