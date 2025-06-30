import threading
import time
import socket
import mysql.connector
import requests
import os
import sys
from datetime import datetime

CHECK_INTERVAL = 1  # segundos
STABLE_SECONDS = 5  # cuántos ciclos consecutivos se necesita el mismo valor

class ScaleWorker(threading.Thread):
    output_lock = threading.Lock()
    workers = []

    def __init__(self, scale, db_config, image_dir, line_index):
        super().__init__(daemon=True)
        self.scale = scale
        self.db_config = db_config
        self.image_dir = image_dir
        self.socket = None
        self.running = True
        self.en_pesada = False
        self.ultimo_peso_estable = None
        self.line_index = line_index
        self.buffer = b""

    def connect_socket(self):
        while True:
            try:
                self.socket = socket.create_connection((self.scale['ip'], self.scale['port']), timeout=5)
                self.socket.settimeout(2)
                break
            except Exception as e:
                self.log_event(f"Socket connection failed: {e}")
                time.sleep(5)

    def run(self):
        self.connect_socket()
        last_weight = None
        stable_count = 0

        while self.running:
            try:
                weight = self.read_weight()
            except Exception as e:
                self.log_event(f"Error reading weight: {e}")
                self.connect_socket()
                continue

            if last_weight is not None and abs(weight - last_weight) < 0.01:
                stable_count += 1
            else:
                stable_count = 0

            if weight >= 100 and (self.ultimo_peso_estable is None or abs(weight - self.ultimo_peso_estable) >= 0.01):
                self.store_weight(weight, stable_count >= STABLE_SECONDS)
                self.ultimo_peso_estable = weight

            if stable_count == STABLE_SECONDS and weight >= 100:
                if not self.en_pesada:
                    self.log_event(f"📥 Inicio de pesada en {weight:.2f} kg")
                    self.en_pesada = True
                    self.capture_images(weight)

            if self.en_pesada and weight == 0:
                self.store_weight(0.0, True)
                self.log_event(f"✅ Fin de pesada (retorno a 0)")
                self.en_pesada = False
                self.ultimo_peso_estable = None

            self.update_status(weight, stable_count)
            last_weight = weight
            time.sleep(CHECK_INTERVAL)

    def read_weight(self):
        while True:
            try:
                chunk = self.socket.recv(128)
                if not chunk:
                    raise Exception("Desconectado")
                self.buffer += chunk
                lines = self.buffer.split(b'\r')
                self.buffer = lines[-1]  # guardar fragmento incompleto para próxima vuelta

                for raw in lines[:-1]:
                    if b'\x02' in raw:
                        raw = raw.split(b'\x02')[-1]
                    try:
                        text = raw.decode(errors='ignore').strip()
                        peso_str = text[3:9].strip()
                        peso = int(peso_str)
                        return float(peso)
                    except Exception:
                        self.log_event(f"Trama inválida: {raw!r}")
            except socket.timeout:
                return 0.0

    def update_status(self, weight, stable_count):
        estado = "EN CERO" if weight == 0 else ("ESTABLE" if stable_count >= STABLE_SECONDS else "Inestable")
        mensaje = f"[{self.scale['name']}] Peso: {weight:.2f} kg - {estado}"
        with ScaleWorker.output_lock:
            sys.stdout.write(f"\0337")
            sys.stdout.write(f"\033[{self.line_index + 1};0H\033[K{mensaje}\0338")
            sys.stdout.flush()

    def log_event(self, mensaje):
        with ScaleWorker.output_lock:
            sys.stdout.write(f"\n[{self.scale['name']}] {mensaje}\n")
            sys.stdout.flush()

    def get_db(self):
        return mysql.connector.connect(**self.db_config)

    def store_weight(self, weight, stable):
        db = self.get_db()
        cursor = db.cursor()
        cursor.execute(
            "INSERT INTO weights (scale_id, weight, timestamp, stable) VALUES (%s, %s, %s, %s)",
            (self.scale['id'], weight, datetime.now(), stable)
        )
        cursor.close()
        db.commit()
        db.close()

    def capture_images(self, weight):
        db = self.get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("SELECT * FROM cameras WHERE scale_id=%s", (self.scale['id'],))
        cameras = cursor.fetchall()
        cursor.close()
        db.close()

        for cam in cameras:
            try:
                resp = requests.get(cam['url'], timeout=10)
                if resp.status_code == 200:
                    date_path = datetime.now().strftime('%Y%m%d')
                    scale_dir = os.path.join(self.image_dir, str(self.scale['id']), date_path)
                    os.makedirs(scale_dir, exist_ok=True)
                    filename = f"{datetime.now().strftime('%H%M%S')}_cam{cam['id']}.jpg"
                    filepath = os.path.join(scale_dir, filename)
                    with open(filepath, 'wb') as f:
                        f.write(resp.content)
                    self.log_event(f"Cámara '{cam['name']}' → Imagen guardada como {filename}")
            except Exception as e:
                self.log_event(f"Error capturando cámara {cam['name']}: {e}")


def load_scales(db_config):
    db = mysql.connector.connect(**db_config)
    cursor = db.cursor(dictionary=True)
    cursor.execute("SELECT * FROM scales")
    scales = cursor.fetchall()
    cursor.close()
    db.close()
    return scales


def main():
    db_config = {
        'host': 'localhost',
        'user': 'balanzas',
        'password': 'balanzas',
        'database': 'balanzas'
    }
    image_dir = '/var/www/html/balanza'

    scales = load_scales(db_config)
    workers = []

    print("\033[2J")
    for idx, scale in enumerate(scales):
        worker = ScaleWorker(scale, db_config, image_dir, idx)
        worker.start()
        workers.append(worker)

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        for w in workers:
            w.running = False

if __name__ == '__main__':
    main()
