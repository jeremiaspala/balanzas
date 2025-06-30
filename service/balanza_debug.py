import socket
import time
from datetime import datetime
import threading

BALANZAS = [
    {"nombre": "Balanza 1", "ip": "192.168.14.101", "puerto": 1702},
    {"nombre": "Balanza 2", "ip": "192.168.14.100", "puerto": 1702}
]

INTERVALO = 1  # segundos entre lecturas
ESTABLE_REQUERIDO = 5  # ciclos con mismo valor para considerar estable

def interpretar_peso(texto):
    peso_str = texto[3:9].strip()
    try:
        peso = int(peso_str)
        return float(peso)
    except ValueError:
        return 0.0

def monitorear_balanza(balanza):
    nombre = balanza["nombre"]
    ip = balanza["ip"]
    puerto = balanza["puerto"]

    while True:
        print(f"\n[{nombre}] Conectando a {ip}:{puerto}...")
        try:
            sock = socket.create_connection((ip, puerto), timeout=5)
            sock.settimeout(2)
            print(f"[{nombre}] ✅ Conectado")
        except Exception as e:
            print(f"[{nombre}] ❌ Error de conexión: {e}")
            time.sleep(5)
            continue

        peso_anterior = None
        ciclos_estable = 0

        try:
            while True:
                try:
                    linea = sock.recv(32)  # leer hasta 32 bytes
                    if not linea:
                        raise Exception("Desconectado")
                    texto = linea.decode(errors="ignore").strip()
                except socket.timeout:
                    continue

                peso = interpretar_peso(texto)
                hora = datetime.now().strftime("%H:%M:%S")

                estado = f"→ Peso: {peso:.2f} kg"
                if peso == 0.0:
                    estado += " (EN CERO)"

                print(f"[{nombre}] {hora} | RAW: '{texto}' {estado}")

                if peso == peso_anterior:
                    ciclos_estable += 1
                else:
                    ciclos_estable = 0

                if ciclos_estable == ESTABLE_REQUERIDO and peso > 0:
                    print(f"[{nombre}] 📸 Peso estable en {peso:.2f} kg. Debería capturar imágenes ahora.")
                    ciclos_estable = 0

                peso_anterior = peso
                time.sleep(INTERVALO)

        except Exception as e:
            print(f"[{nombre}] ⚠️ Error de lectura: {e}. Reconectando...")
            sock.close()
            time.sleep(3)

# Lanzar un hilo por balanza
for balanza in BALANZAS:
    t = threading.Thread(target=monitorear_balanza, args=(balanza,), daemon=True)
    t.start()

# Mantener el script vivo
try:
    while True:
        time.sleep(1)
except KeyboardInterrupt:
    print("\nFinalizado por el usuario.")
