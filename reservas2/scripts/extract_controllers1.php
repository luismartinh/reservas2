<?php
require __DIR__ . '/../vendor/autoload.php'; // Cargar PHP Parser

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node\Stmt\Class_;

$controllersPath = __DIR__ . '/../controllers/';

$files = glob($controllersPath . '*.php');

//print_r($files); 

function extractControllersInfo($filePath)
{
    $parser = new PhpParser\Parser\Php7(new PhpParser\Lexer());
    
    $code = file_get_contents($filePath);

    
    $ast = $parser->parse($code);

    $controllersInfo = [];

    foreach ($ast as $node) {

        
        if ($node instanceof Class_) {
            echo "Clase encontrada: " . $node->name->toString() . "\n";
            $controllerName = $node->name->toString();
            $actions = [];

            foreach ($node->getMethods() as $method) {
                if (strpos($method->name->toString(), 'action') === 0) {
                    $actionName = lcfirst(str_replace('action', '', $method->name->toString()));
                    $actions[] = [
                        'method' => $method->name->toString(),
                        'route' => strtolower(str_replace('Controller', '', $controllerName)) . "/$actionName"
                    ];
                }
            }

            $controllersInfo[] = [
                'controller' => $controllerName,
                'actions' => $actions
            ];
        }
    }

    return $controllersInfo;
}

$controllersData = [];
foreach (glob($controllersPath . "*.php") as $controllerFile) {
    $controllersData = array_merge($controllersData, extractControllersInfo($controllerFile));
}




// Guardar en JSON
file_put_contents(__DIR__ . '/controllers_data.json', json_encode($controllersData, JSON_PRETTY_PRINT));

echo "✅ Información de controladores extraída y guardada en controllers_data.json\n";
