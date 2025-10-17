<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

// 📌 Ruta a los modelos
$modelsPath = __DIR__ . '/../models/';

// 📌 Buscar archivos PHP en la carpeta de modelos
$files = glob($modelsPath . '*.php');

if (!$files) {
    echo "⚠️ No se encontraron archivos en $modelsPath\n";
    exit;
}

// 📌 Configurar el parser
$parser = new PhpParser\Parser\Php7(new PhpParser\Lexer());
$modelsData = [];

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
                    echo "✅ Modelo encontrado: $namespace\\$className\n";

                    $modelData = [
                        'namespace' => $namespace,
                        'class' => $className,
                        'attributes' => [],
                        'relations' => []
                    ];

                    // 📌 Buscar propiedades (atributos del modelo)
                    foreach ($stmt->stmts as $property) {
                        if ($property instanceof Node\Stmt\Property) {
                            foreach ($property->props as $prop) {
                                $modelData['attributes'][] = $prop->name->toString();
                            }
                        }
                    }

                    // 📌 Buscar métodos (para relaciones tipo getXyz)
                    foreach ($stmt->stmts as $method) {
                        if ($method instanceof Node\Stmt\ClassMethod) {
                            $methodName = $method->name->toString();

                            // Si el método comienza con "get", podría ser una relación
                            if (strpos($methodName, 'get') === 0) {
                                $modelData['relations'][] = $methodName;
                            }
                        }
                    }

                    // 📌 Guardar en la lista de modelos
                    $modelsData[] = $modelData;
                }
            }
        }
    }
}

// 📌 Guardar los datos en JSON
//file_put_contents(__DIR__ . '/../models_data.json', json_encode($modelsData, JSON_PRETTY_PRINT));
file_put_contents(__DIR__ . '/models_data.json', json_encode($modelsData, JSON_PRETTY_PRINT));

echo "✅ Información extraída y guardada en models_data.json\n";
