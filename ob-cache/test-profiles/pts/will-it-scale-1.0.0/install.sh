#!/bin/sh
unzip -o will-it-scale-1.0.0.zip
cd will-it-scale-a34a85cc1e9b9b74e94fdd3ecc479019da610e6a
make
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
DURATION=30
cd will-it-scale-a34a85cc1e9b9b74e94fdd3ecc479019da610e6a
case \$@ in
	\"BRK1\")
		./brk1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"BRK2\")
		./brk2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"CONTEXT_SWITCH1\")
		./context_switch1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE
	;;
	\"DUP1\")
		./dup1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"EVENT_FD1\")
		./eventfd1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"FALLOCATE1\")
		./fallocate1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"FALLOCATE2\")
		./fallocate2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"FUTEX1\")
		./futex1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"FUTEX2\")
		./futex2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"FUTEX3\")
		./futex3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"FUTEX4\")
		./futex4_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"GETPPID1\")
		./getppid1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"LOCK1\")
		./lock1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"LOCK2\")
		./lock2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"LSEEK1\")
		./lseek1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"LSEEK2\")
		./lseek2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"MALLOC1\")
		./malloc1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"MALLOC2\")
		./malloc2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"MMAP1\")
		./mmap1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"MMAP2\")
		./mmap2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"OPEN1\")
		./open1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"OPEN2\")
		./open2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"OPEN3\")
		./open3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PAGE_FAULT1\")
		./page_fault1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PAGE_FAULT2\")
		./page_fault2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PAGE_FAULT3\")
		./page_fault3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PIPE1\")
		./pipe1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"POLL1\")
		./poll1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"POLL2\")
		./poll2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"POSIX_SEMAPHORE1\")
		./posix_semaphore1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PREAD1\")
		./pread1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PREAD2\")
		./pread2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PREAD3\")
		./pread3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PTHREAD_MUTEX1\")
		./pthread_mutex1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PTHREAD_MUTEX2\")
		./pthread_mutex2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PTHREAD_MUTEX3\")
		./pthread_mutex3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PTHREAD_MUTEX4\")
		./pthread_mutex4_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PTHREAD_MUTEX5\")
		./pthread_mutex5_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PWRITE1\")
		./pwrite1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PWRITE2\")
		./pwrite2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"PWRITE3\")
		./pwrite3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READ1\")
		./read1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READ2\")
		./read2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READ3\")
		./read3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READ4\")
		./read4_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READSEEK1\")
		./readseek1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READSEEK2\")
		./readseek2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"READSEEK3\")
		./readseek3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"SCHED_YIELD\")
		./sched_yield_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"SIGNAL1\")
		./signal1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"TLB_FLUSH1\")
		./tlb_flush1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"TLB_FLUSH2\")
		./tlb_flush2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"TLB_FLUSH3\")
		./tlb_flush3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"UNIX1\")
		./unix1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"UNLINK1\")
		./unlink1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"UNLINK2\")
		./unlink2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"WRITE1\")
		./write1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"WRITESEEK1\")
		./writeseek1_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"WRITESEEK2\")
		./writeseek2_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
	\"WRITESEEK3\")
		./writeseek3_processes -s \$DURATION -t \$NUM_CPU_CORES -n > \$LOG_FILE 2>&1
	;;
esac
echo \$? > ~/test-exit-status" > will-it-scale
chmod +x will-it-scale
