import requests
import json
import os

API_URL = "https://tuapi.tudominio.com/api/descargar_config.php"
TOKEN = "TU_TOKEN"
DEST_FILE = "/opt/balanza/config.json"

try:
    r = requests.get(API_URL, params={'token': TOKEN}, timeout=10)
    r.raise_for_status()
    config = r.json()

    with open(DEST_FILE, "w") as f:
        json.dump(config, f, indent=2)

    print("✅ Configuración actualizada")
except Exception as e:
    print(f"❌ Error al descargar configuración: {e}")
