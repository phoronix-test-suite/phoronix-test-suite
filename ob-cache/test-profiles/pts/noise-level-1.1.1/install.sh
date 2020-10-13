#!/bin/bash

BC=`which bc`
echo $? > ~/install-exit-status

cat > noise-level << EOT
#!/bin/sh

ST=\`date +%s.%N\`
SB=\`cat /proc/stat | \
  grep "cpu " | \
  awk '{print \$2 + \$3 + \$4 + \$7 + \$8}'\`

sleep 1m

ET=\`date +%s.%N\`
EB=\`cat /proc/stat | \
  grep "cpu " | \
  awk '{print \$2 + \$3 + \$4 + \$7 + \$8}'\`

echo "(1000 * \$EB - \$SB) / (\$ET - \$ST)" | ${BC} > \$LOG_FILE
EOT

chmod +x noise-level
