import threading
import time
import socket
import requests
import json
import os
from datetime import datetime

CHECK_INTERVAL = 1
STABLE_SECONDS = 10
CONFIG_PATH = "/opt/balanza/config.json"
TOKEN = "TU_TOKEN"
API_PESO = "https://tuapi.tudominio.com/api/ingresar_datos.php"
API_IMG = "https://tuapi.tudominio.com/api/subir_foto.php"

class ScaleWorker(threading.Thread):
    def __init__(self, scale):
        super().__init__(daemon=True)
        self.scale = scale
        self.buffer = b""
        self.socket = None
        self.running = True
        self.stable_count = 0
        self.last_weight = None
        self.en_pesada = False

    def connect_socket(self):
        while True:
            try:
                self.socket = socket.create_connection((self.scale['ip'], self.scale['port']), timeout=5)
                self.socket.settimeout(2)
                break
            except:
                time.sleep(5)

    def run(self):
        self.connect_socket()
        while self.running:
            try:
                weight = self.read_weight()
                if self.last_weight is not None and abs(weight - self.last_weight) < 0.01:
                    self.stable_count += 1
                else:
                    self.stable_count = 0

                if weight >= 100 and (self.last_weight is None or abs(weight - self.last_weight) >= 0.01):
                    self.send_weight(weight, stable=(self.stable_count >= STABLE_SECONDS))

                if self.stable_count >= STABLE_SECONDS and not self.en_pesada:
                    self.en_pesada = True
                    self.capture_images()

                if self.en_pesada and weight == 0:
                    self.send_weight(0, stable=True)
                    self.en_pesada = False

                self.last_weight = weight
                time.sleep(CHECK_INTERVAL)
            except:
                self.connect_socket()

    def read_weight(self):
        while True:
            try:
                chunk = self.socket.recv(128)
                if not chunk:
                    raise Exception("Desconectado")
                self.buffer += chunk
                lines = self.buffer.split(b'\r')
                self.buffer = lines[-1]
                for raw in lines[:-1]:
                    if b'\x02' in raw:
                        raw = raw.split(b'\x02')[-1]
                    text = raw.decode(errors='ignore').strip()
                    peso_str = text[3:9].strip()
                    return float(int(peso_str))
            except socket.timeout:
                return 0.0

    def send_weight(self, weight, stable):
        try:
            requests.post(API_PESO, json={
                'token': TOKEN,
                'scale_id': self.scale['id'],
                'weight': weight,
                'stable': int(stable),
                'timestamp': datetime.now().isoformat()
            }, timeout=5)
        except:
            pass

    def capture_images(self):
        for cam in self.scale.get('cameras', []):
            try:
                r = requests.get(cam['url'], timeout=10)
                if r.status_code == 200:
                    files = {'foto': ("img.jpg", r.content, 'image/jpeg')}
                    data = {
                        'token': TOKEN,
                        'scale_id': self.scale['id'],
                        'cam_id': cam['id']
                    }
                    requests.post(API_IMG, files=files, data=data, timeout=10)
            except:
                pass


def main():
    if not os.path.exists(CONFIG_PATH):
        print("❌ No se encontró config.json")
        return

    with open(CONFIG_PATH) as f:
        config = json.load(f)

    workers = [ScaleWorker(scale) for scale in config]
    for w in workers:
        w.start()

    try:
        while True:
            time.sleep(1)
    except KeyboardInterrupt:
        for w in workers:
            w.running = False

if __name__ == '__main__':
    main()
