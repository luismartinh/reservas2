<?php
return [
    // Carpeta raíz donde buscará archivos PHP con Yii::t()
    'sourcePath' => __DIR__,

    // Carpeta donde se guardarán las traducciones
    'messagePath' => __DIR__ . '/messages',

    // Idiomas a generar
    'languages' => ['es', 'en', 'pt-BR'],

    // Nombre del método traductor a detectar
    'translator' => 'Yii::t',

    // Ordenar alfabéticamente las claves
    'sort' => true,

    // Sobrescribir traducciones existentes si se vuelven a extraer
    'overwrite' => true,

    // No eliminar claves no usadas (por seguridad)
    'removeUnused' => false,

    // Qué archivos examinar
    'only' => ['*.php'],

    // Qué carpetas excluir
    'except' => ['vendor', 'runtime', 'web/assets'],

    // Formato de salida
    'format' => 'php',

    // Categorías a ignorar (no escanea traducciones del core de Yii)
    'ignoreCategories' => ['yii'],

    // Opcional: podés incluir fileMap si querés nombres fijos
    'fileMap' => [
        'app' => 'app.php',
        'cruds' => 'cruds.php',
        'models' => 'models.php',
        'models.plural' => 'models.plural.php',
    ],

];
