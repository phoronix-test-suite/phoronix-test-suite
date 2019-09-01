#!/bin/sh


cat > ~/smart << EOT
#!/bin/sh
smartctl -a \$@ > \$LOG_FILE

EOT

chmod +x smart