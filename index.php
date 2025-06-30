<?php
$mysqli = new mysqli("localhost", "balanzas", "balanzas", "balanzas");
$balanzas = $mysqli->query("SELECT * FROM scales");
?>
<h2>Balanzas Registradas</h2>
<ul>
<?php while($b = $balanzas->fetch_assoc()): ?>
  <li><a href="pesadas.php?balanza=<?=$b['id']?>"><?=$b['name']?></a></li>
<?php endwhile; ?>
</ul>
