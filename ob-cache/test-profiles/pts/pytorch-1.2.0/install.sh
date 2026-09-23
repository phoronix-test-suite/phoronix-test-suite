#!/bin/sh
pip install --user setuptools
pip install --user torch==2.6.0 torchvision==0.21.0 torchaudio==2.6.0 pytorch-benchmark==0.3.6
echo $? > ~/install-exit-status
cat <<'EOF' > pytorch
#!/bin/sh
echo """
import subprocess, yaml, os, ast, re
from concurrent.futures import ThreadPoolExecutor

def torch_numa_worker(node):
    try:
        physical_cores=subprocess.run([f'NUMA_NODE={node};' + 'lscpu -p=CPU,CORE,SOCKET,NODE | grep -v \'^#\' | awk -F, -v n=\$NUMA_NODE \'\$4==n {cores[\$2\",\"\$3]=1} END {printf \"%d\", length(cores)}\' '], capture_output=True, text=True, check=True, shell=True).stdout
        numactl_cmd = f'OMP_NUM_THREADS={physical_cores} numactl -N {node} -m {node}' if int(node) >=0 else f'OMP_NUM_THREADS={physical_cores}'
        benchmark_result = subprocess.run(
            [f'{numactl_cmd} ' + \"\"\" python3 -c \\\"\\\"\\\"
import torch
from torchvision.models import $3
from pytorch_benchmark import benchmark
#num_threads = torch.get_num_threads()
#print(f'Benchmarking on with {num_threads} threads')
model = $3().to('$1')
sample = torch.randn(2, 3, 224, 224)  # (B, C, H, W)
sample = sample.to(memory_format=torch.channels_last) # (B, C, H, W) -> (B, H, W, C)
model = model.to(memory_format=torch.channels_last) # (B, C, H, W) -> (B, H, W, C)
with torch.no_grad(): # no need to compute gradients for inference
    results = benchmark(model, sample, num_runs=1000, print_details=False, batch_size=$2)
print(results['timing'])
            \\\"\\\"\\\" \"\"\"],
                capture_output=True, text=True, check=True, shell=True).stdout
    except subprocess.CalledProcessError as e:
        print(e.returncode, e.stdout, e.stderr)
        os._exit(1) # fail the benchmark
    return benchmark_result

def concurrent_benchmark_run():
    try:
        numa_nodes = list(subprocess.run(['set -o pipefail; numactl -H | grep -E \"cpus: [0-9]\" | awk \'{print \$2}\' | tr \'\\\n\' \' \' '], capture_output=True, text=True, check=True, shell=True).stdout.split())
    except subprocess.CalledProcessError as e:
        numa_nodes = list([0]) # numactl failed, run everything on single instance
    results=[]
    if len(numa_nodes) > 1:
        # Run all nodes concurrently
        with ThreadPoolExecutor(max_workers=len(numa_nodes)) as executor:
            futures = [
                executor.submit(torch_numa_worker, node)
                for node in numa_nodes
            ]
            results = [future.result() for future in futures]
    else:
        results.append(torch_numa_worker(-1))

    throughputs = []
    for i, result in enumerate(results):
        metrics = []
        timing=ast.literal_eval(re.search(r'\{.*\}', result.replace('\\\\n', '')).group())
        #print(yaml.dump(timing))
        metrics=timing['batch_size_$2'].get('on_device_inference', {}).get('metrics', {})
        throughputs.append({
            'mean': metrics['batches_per_second_mean'],
            'std': metrics['batches_per_second_std'],
            'min': metrics['batches_per_second_min'],
            'max': metrics['batches_per_second_max']
        })
    best_throughput = sum(r['max'] for r in throughputs)
    worst_throughput = sum(r['min'] for r in throughputs)
    average_throughput = sum(r['mean'] for r in throughputs)
    std_throughput = min(r['std'] for r in throughputs)
    print(f\"batches_per_second_max: {best_throughput}\")
    print(f\"batches_per_second_mean: {average_throughput}\")
    print(f\"batches_per_second_min: {worst_throughput}\")
    print(f\"batches_per_second_std: {std_throughput}\")

if __name__ == \"__main__\":
    concurrent_benchmark_run()
""" > pytorch-benchmark.py
python3 pytorch-benchmark.py > $LOG_FILE 2>&1
echo $? > ~/test-exit-status
EOF
chmod +x pytorch
