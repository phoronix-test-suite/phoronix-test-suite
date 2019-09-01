#!/bin/sh

if which systemd-analyze >/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: systemd-analyze must be available on the system for testing"
	echo 2 > ~/install-exit-status
fi

cat > systemd-analyze.py<< EOT
#!/usr/bin/python3
import subprocess

out, err = subprocess.Popen(["systemd-analyze"], stdout=subprocess.PIPE, stderr=subprocess.PIPE).communicate()
alllines = out.decode("latin-1")
lines =  alllines.split("\n")

total_time =0
kernel_time =0
userspace_time = 0
firmware_time = 0
loader_time = 0

def time_to_ms(time):
    if "ms" in time:
        return float(time.replace("ms",""))
    if "min" in time:
        return float(time.replace("min",""))*60000
    if "s" in time:
        return float(time.replace("s",""))*1000

for line in lines:
    if "(kernel)" in line:
        count = 0
        for item in (line.split(" ")):
            if "kernel" in item:
                time = (line.split(" ")[count-1])
                kernel_time = time_to_ms(time)
                break
            count+=1
    if "(userspace)" in line:
        count = 0
        for item in (line.split(" ")):
            if "userspace" in item:
                time = (line.split(" ")[count-1])
                userspace_time = time_to_ms(time)
                break
            count+=1
    if "(loader)" in line:
        count = 0
        for item in (line.split(" ")):
            if "loader" in item:
                time = (line.split(" ")[count-1])
                loader_time = time_to_ms(time)
                break
            count+=1
    if "(firmware)" in line:
        count = 0
        for item in (line.split(" ")):
            if "firmware" in item:
                time = (line.split(" ")[count-1])
                firmware_time = time_to_ms(time)
                break
            count+=1
total_time = 0
total_time = float(kernel_time) + float(userspace_time)

print("kernel_time : " + str(kernel_time))
print("userspace_time: " + str(userspace_time))
print("loader_time: " + str(loader_time))
print("firmware_time: " + str(firmware_time))
print("total_time: " + str(total_time))
EOT

cat > systemd-boot-total << EOT
#!/bin/sh
python systemd-analyze.py | grep \$@ | cut -d':' -f2 > \$LOG_FILE 2>&1
EOT

chmod +x systemd-boot-total
