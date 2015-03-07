<?php
/** @var Labrador\Http\Renderer\Helpers $this */
/** @var string $_content */
/** @var array $css */
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Labrador HTTP Application</title>
        <?php foreach ($css as $style): ?>
            <?= $this->cssTag($style) ?>
        <?php endforeach; ?>
    </head>
    <body>
        <header>
            <h1>Labrador HTTP Application</h1>
        </header>
        <section id="main">
            <?= $_content ?>
        </section>
        <footer>

        </footer>
    </body>
</html>
