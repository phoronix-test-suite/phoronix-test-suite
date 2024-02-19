#!/bin/sh
tar -xf rabbitmq-perf-test-2.20.0-bin.tar.gz
tar -xf rabbitmq-server-generic-unix-3.12.7.tar.xz
cat>rabbitmq<<EOT
#!/bin/sh
cd rabbitmq-perf-test-2.20.0/bin
./runjava com.rabbitmq.perf.PerfTest -z 120 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
EOT
chmod +x rabbitmq
