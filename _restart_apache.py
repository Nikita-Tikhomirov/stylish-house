import paramiko
c = paramiko.SSHClient()
c.set_missing_host_key_policy(paramiko.AutoAddPolicy())
c.connect('5.35.91.69', 22, 'root', 'w0n5Fqe&L24%')
stdin, stdout, stderr = c.exec_command('systemctl restart apache2-isp@php81 2>&1 && echo OK')
print(stdout.read().decode().strip())
c.close()
