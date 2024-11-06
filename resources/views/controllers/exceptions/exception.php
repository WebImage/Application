<?php
/**
 * @var string $title
 * @var string $message
 * @var bool $debug
 * @var \Exception $exception
 */
?>
<h1><?= htmlentities($title) ?></h1>
<p><?= htmlentities($message) ?></p>
<?php if ($debug): ?>
    <style>
        .debug-message {
            font-size: 1.2em;
        }
        .debug-file {
            font-weight: bold;
        }

        .debug-line {
            font-style: italic;
        }

        .debug-class {
            color: #09c;
        }

        .debug-function {
            color: #0c0;
        }
    </style>
    <h2>Exception</h2>
    <p class="debug-message"><?= htmlentities($exception->getMessage()) ?></p>
    <p>File: <span class="debug-file"><?= $exception->getFile() ?></span>:<span class="debug-line"><?= $exception->getLine() ?></span></p>
    <p>Code: <?= $exception->getCode() ?></p>
    <h2>Trace</h2>
    <ul>
		<?php foreach ($exception->getTrace() as $item): ?>
            <li style="margin-bottom: 20px;">
				<?php if (isset($item['file'])): ?>
                    <span class="debug-file"><?= $item['file'] ?></span>:
                    <span class="debug-line"><?= $item['line'] ?></span><br/>
				<?php endif; ?>
				<?php if (isset($item['class'])): ?>
                    <span class="debug-class"><?= $item['class'] ?></span>
                    <span class=""><?= htmlentities($item['type']) ?></span>
                    <span class="debug-function"><?= htmlentities($item['function']) ?></span><br/>
				<?php elseif (isset($item['function'])): ?>
                    <span class="debug-function"><?= $item['function'] ?></span><br/>
                <?php else: ?>
					<?= implode(', ', array_keys($item)) ?>
				<?php endif ?>
            </li>
		<?php endforeach; ?>
    </ul>
    <h2>Trace as String</h2>
    <p>Trace: <?= $exception->getTraceAsString() ?></p>
<?php endif; ?>
