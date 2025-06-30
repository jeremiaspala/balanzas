import socket

ip = '192.168.14.100'
port = 1702

with socket.create_connection((ip, port)) as s:
    s.settimeout(10)
    print(f"Conectado a {ip}:{port}")
    while True:
        try:
            data = s.recv(64)
            print(f"Recibido: {data!r}")
        except socket.timeout:
            print("Timeout, no se recibió nada.")
