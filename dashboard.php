<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard de Balanzas</title>
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #222;
    color: #fff;
    margin: 0;
    padding: 30px;
}

h1 {
    text-align: center;
    margin-bottom: 30px;
}

.grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
}

.balanza {
    background: #111;
    border: 2px solid #444;
    border-radius: 12px;
    width: 250px;
    padding: 20px;
    box-shadow: 0 0 10px #000;
    text-align: center;
}

.balanza h2 {
    margin: 0 0 10px;
    font-size: 20px;
    color: #ccc;
}

.peso {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 10px;
    font-family: monospace;
}

.estado {
    font-size: 16px;
    margin-bottom: 8px;
}

.estable { color: #0f0; }
.inestable { color: #ff0; }
.cero { color: #888; }

.hora {
    font-size: 12px;
    color: #888;
}
</style>
</head>
<body>
<h1>Balanzas en Tiempo Real</h1>
<div class="grid" id="contenedor"></div>

<script>
async function actualizar() {
    const res = await fetch("api_estado.php");
    const datos = await res.json();
    const contenedor = document.getElementById("contenedor");
    contenedor.innerHTML = "";

    datos.forEach(row => {
        let estado = "Inestable", clase = "inestable";
        if (row.weight < 0.1) {
            estado = "En cero"; clase = "cero";
        } else if (row.stable) {
            estado = "Estable"; clase = "estable";
        }

        const card = document.createElement("div");
        card.className = "balanza";
        card.innerHTML = `
        <h2>${row.name}</h2>
        <div class="peso">${row.weight.toFixed(2)} kg</div>
        <div class="estado ${clase}">${estado}</div>
        <div class="hora">${row.timestamp}</div>
        `;
        contenedor.appendChild(card);
    });
}

setInterval(actualizar, 5000);
actualizar();
</script>
</body>
</html>
