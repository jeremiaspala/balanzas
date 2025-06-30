import threading
import time
import telnetlib
import mysql.connector
import requests
import os
from datetime import datetime

CHECK_INTERVAL = 1  # seconds
STABLE_SECONDS = 5

class ScaleWorker(threading.Thread):
    def __init__(self, scale, db_config, image_dir):
        super().__init__(daemon=True)
        self.scale = scale
        self.db_config = db_config
        self.image_dir = image_dir
        self.connection = None
        self.running = True

    def connect_telnet(self):
        while True:
            try:
                self.connection = telnetlib.Telnet(self.scale['ip'], self.scale['port'])
                break
            except Exception as e:
                print(f"Telnet connection failed for {self.scale['name']}: {e}")
                time.sleep(5)

    def run(self):
        self.connect_telnet()
        last_weight = None
        stable_count = 0
        while self.running:
            try:
                weight = self.read_weight()
            except Exception as e:
                print(f"Error reading weight from {self.scale['name']}: {e}")
                self.connect_telnet()
                continue

            if last_weight is not None and abs(weight - last_weight) < 0.01:
                stable_count += 1
            else:
                stable_count = 0
            self.store_weight(weight, stable_count >= STABLE_SECONDS)
            if stable_count == STABLE_SECONDS:
                self.capture_images()
            last_weight = weight
            time.sleep(CHECK_INTERVAL)

    def read_weight(self):
        line = self.connection.read_until(b"\n")
        try:
            return float(line.strip())
        except ValueError:
            return 0.0

    def get_db(self):
        return mysql.connector.connect(**self.db_config)

    def store_weight(self, weight, stable):
        db = self.get_db()
        cursor = db.cursor()
        cursor.execute(
            "INSERT INTO weights (scale_id, weight, timestamp, stable) VALUES (%s,%s,%s,%s)",
            (self.scale['id'], weight, datetime.now(), stable)
        )
        db.commit()
        cursor.close()
        db.close()

    def capture_images(self):
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
                    with open(os.path.join(scale_dir, filename), 'wb') as f:
                        f.write(resp.content)
            except Exception as e:
                print(f"Failed to capture image from camera {cam['name']}: {e}")


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
    image_dir = '/var/www/html/balanzas'

    scales = load_scales(db_config)
    workers = []
    for scale in scales:
        worker = ScaleWorker(scale, db_config, image_dir)
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
