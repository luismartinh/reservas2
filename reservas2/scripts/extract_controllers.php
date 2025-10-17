<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

// 📌 Ruta a los controladores
$controllersPath = __DIR__ . '/../controllers/';

// 📌 Buscar archivos PHP en la carpeta de controladores
$files = glob($controllersPath . '*.php');

if (!$files) {
    echo "⚠️ No se encontraron archivos en $controllersPath\n";
    exit;
}

// 📌 Configurar el parser
$parser = new PhpParser\Parser\Php7(new PhpParser\Lexer());
  

$controllersData = [];

foreach ($files as $file) {
    echo "📂 Procesando: $file\n";

    // Leer el contenido del archivo
    $code = file_get_contents($file);
    
    try {
        $ast = $parser->parse($code);
    } catch (Exception $e) {
        echo "⚠️ Error al parsear $file: " . $e->getMessage() . "\n";
        continue;
    }

    // 🔍 Buscar clases dentro de namespaces
    foreach ($ast as $node) {
        if ($node instanceof Node\Stmt\Namespace_) {
            $namespace = $node->name->toString();

            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Class_) {
                    $className = $stmt->name->toString();
                    echo "✅ Controlador encontrado: $namespace\\$className\n";

                    $controllerData = [
                        'namespace' => $namespace,
                        'class' => $className,
                        'actions' => []
                    ];

                    // 📌 Buscar métodos dentro de la clase
                    foreach ($stmt->stmts as $method) {
                        if ($method instanceof Node\Stmt\ClassMethod) {
                            $methodName = $method->name->toString();
                            if (strpos($methodName, 'action') === 0) {
                                $controllerData['actions'][] = $methodName;
                            }
                        }
                    }

                    // 📌 Guardar en la lista de controladores
                    $controllersData[] = $controllerData;
                }
            }
        }
    }
}

// 📌 Guardar los datos en JSON
//file_put_contents(__DIR__ . '/../controllers_data.json', json_encode($controllersData, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/controllers_data.json', json_encode($controllersData, JSON_PRETTY_PRINT));

echo "✅ Información extraída y guardada en controllers_data.json\n";
