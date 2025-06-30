from flask import Flask, render_template, request, redirect, url_for
import mysql.connector
from datetime import datetime
import os

app = Flask(__name__)
DB_CONFIG = {
    'host': 'localhost',
    'user': 'balanzas',
    'password': 'balanzas',
    'database': 'balanzas'
}
IMAGE_DIR = '/var/www/html/balanzas'


def get_db():
    return mysql.connector.connect(**DB_CONFIG)


@app.route('/')
def index():
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT * FROM cameras')
    cameras = cursor.fetchall()
    cursor.close()
    db.close()
    return render_template('index.html', cameras=cameras)


@app.route('/camera/<int:cam_id>')
def camera_view(cam_id):
    db = get_db()
    cursor = db.cursor(dictionary=True)
    cursor.execute('SELECT * FROM cameras WHERE id=%s', (cam_id,))
    camera = cursor.fetchone()
    cursor.execute('SELECT * FROM weights WHERE scale_id=%s ORDER BY timestamp', (camera['scale_id'],))
    weights = cursor.fetchall()
    cursor.close()
    db.close()

    date_path = datetime.now().strftime('%Y%m%d')
    img_dir = os.path.join(IMAGE_DIR, str(camera['scale_id']), date_path)
    images = []
    if os.path.isdir(img_dir):
        for f in sorted(os.listdir(img_dir)):
            if f.endswith(f"cam{cam_id}.jpg"):
                images.append(os.path.join('/balanzas', str(camera['scale_id']), date_path, f))
    return render_template('camera.html', camera=camera, weights=weights, images=images)


@app.route('/admin/scales', methods=['GET', 'POST'])
def manage_scales():
    db = get_db()
    cursor = db.cursor(dictionary=True)
    if request.method == 'POST':
        cursor.execute('INSERT INTO scales (name, ip, port) VALUES (%s,%s,%s)',
                       (request.form['name'], request.form['ip'], request.form['port']))
        db.commit()
        return redirect(url_for('manage_scales'))
    cursor.execute('SELECT * FROM scales')
    scales = cursor.fetchall()
    cursor.close()
    db.close()
    return render_template('scales.html', scales=scales)


@app.route('/admin/cameras/<int:scale_id>', methods=['GET', 'POST'])
def manage_cameras(scale_id):
    db = get_db()
    cursor = db.cursor(dictionary=True)
    if request.method == 'POST':
        cursor.execute('INSERT INTO cameras (scale_id, name, url) VALUES (%s,%s,%s)',
                       (scale_id, request.form['name'], request.form['url']))
        db.commit()
        return redirect(url_for('manage_cameras', scale_id=scale_id))
    cursor.execute('SELECT * FROM cameras WHERE scale_id=%s', (scale_id,))
    cameras = cursor.fetchall()
    cursor.close()
    db.close()
    return render_template('cameras.html', cameras=cameras, scale_id=scale_id)


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
