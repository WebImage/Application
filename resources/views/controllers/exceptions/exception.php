<?php
/**
 * @var string $title
 * @var string $message
 * @var bool $debug
 * @var \Exception $exception
 */
?>
<h1><?= htmlentities($title)?></h1>
<p><?= htmlentities($message) ?></p>
<?php if ($debug): ?>
    <h2>Exception</h2>
    <p><?= htmlentities($exception->getMessage()) ?></p>
    <p>File: <?= $exception->getFile() ?></p>
    <p>Line: <?= $exception->getLine() ?></p>
    <p>Code: <?= $exception->getCode() ?></p>
    <p>Trace: <?= $exception->getTraceAsString() ?></p>
    <?php /* <ul>
        <?php foreach($exception->getTrace() as $item): ?>
            <li><pre><?= print_r($item) ?></li>
        <?php endforeach; ?>
    </ul> */ ?>
<?php endif; ?>
