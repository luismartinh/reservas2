<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\Class_;

$modelsPath = __DIR__ . '/../models/';

function extractModelsInfo($filePath)
{
    $parser = new PhpParser\Parser\Php7(new PhpParser\Lexer());

    $code = file_get_contents($filePath);
    $ast = $parser->parse($code);

    $modelsInfo = [];

    foreach ($ast as $node) {
        if ($node instanceof Class_) {
            $modelName = $node->name->toString();
            $modelsInfo[] = [
                'model' => $modelName
            ];
        }
    }

    return $modelsInfo;
}

$modelsData = [];
foreach (glob($modelsPath . "*.php") as $modelFile) {
    $modelsData = array_merge($modelsData, extractModelsInfo($modelFile));
}

// Guardar en JSON
file_put_contents(__DIR__ . '/models_data.json', json_encode($modelsData, JSON_PRETTY_PRINT));

echo "✅ Información de modelos extraída y guardada en models_data.json\n";
