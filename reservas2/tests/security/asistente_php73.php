<?php
// Static language check using the parser already installed in this project.
// This complements lint on the local PHP runtime; it does not replace a PHP 7.3 runtime test.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$root = dirname(__DIR__, 2);
$files = array_merge(glob($root . '/components/asistente/*.php'), [
    $root . '/tests/unit/models/AsistenteAudioTest.php', $root . '/tests/functional/AsistenteAudioCest.php',
    $root . '/models/Provincia.php', $root . '/models/Localidad.php', $root . '/models/UbicacionTrait.php',
    $root . '/models/Locador.php', $root . '/models/LocadorSearch.php',
    $root . '/models/RequestReserva.php', $root . '/models/RequestReservaSearch.php',
    $root . '/controllers/LocadorController.php', $root . '/assets/UbicacionAsset.php',
    $root . '/assets/CalificacionAsset.php',
    $root . '/views/locador/_calificacion-input.php', $root . '/views/layouts/asistente-formulario.php',
    $root . '/tests/functional/AsistentePasajeroCest.php', $root . '/tests/unit/models/AsistenteBuscarPasajerosTest.php',
    $root . '/views/asistente/pasajero.php', $root . '/tests/unit/models/AsistentePasajeroTest.php',
    $root . '/views/locador/_calificacion.php', $root . '/tests/unit/models/LocadorSearchTest.php',
    $root . '/models/ReservaSolicitudForm.php', $root . '/views/reserva/crear_reserva.php',
    $root . '/views/locador/_ubicacion.php', $root . '/views/locador/_form-fields.php',
    $root . '/views/locador/index.php', $root . '/views/locador/view.php',
    $root . '/views/request-reserva/_expand_detail.php', $root . '/views/request-reserva/_form-fields.php',
    $root . '/views/request-reserva/_search.php', $root . '/views/request-reserva/index.php',
    $root . '/tests/unit/models/UbicacionReferenciaTest.php', $root . '/tests/functional/UbicacionCest.php',
    $root . '/tests/functional/RequestReservaCest.php',
    $root . '/components/GeminiClient.php', $root . '/controllers/AsistenteController.php',
    $root . '/models/AsistenteMensajeForm.php', $root . '/assets/AsistenteAsset.php',
    $root . '/models/AsistenteEliminarReservaForm.php', $root . '/views/asistente/eliminar-reserva.php',
    $root . '/models/AsistenteEliminarPagoForm.php', $root . '/views/asistente/eliminar-pago.php',
    $root . '/models/AsistentePagoForm.php', $root . '/components/PagosReserva.php',
    $root . '/views/asistente/pago.php', $root . '/tests/unit/models/AsistentePagoTest.php',
    $root . '/models/CabanaTarifa.php', $root . '/models/AsistenteReservaForm.php',
    $root . '/controllers/ReservaController.php', $root . '/controllers/DisponibilidadController.php',
    $root . '/controllers/RequestReservaController.php', $root . '/components/ReservaOcupacion.php', $root . '/views/asistente/reservar.php',
    $root . '/tests/unit/models/AsistenteCrearReservaTest.php',
    $root . '/views/asistente/index.php', $root . '/views/layouts/app.php',
    $root . '/config/asistente.php', $root . '/config/params.php', $root . '/config/web.php',
    $root . '/tests/_support/AsistenteFixtures.php', $root . '/tests/functional/AsistenteCest.php',
    $root . '/tests/_support/OpenAIHttp.php', $root . '/tests/unit/models/AsistenteOpenAITest.php',
    $root . '/tests/unit/models/AsistenteTest.php', $root . '/tests/unit/models/AsistenteDisponibilidadTest.php',
    $root . '/tests/unit/models/AsistenteTarifasTest.php',
    $root . '/tests/unit/models/AsistenteReservasTest.php',
    __FILE__,
]);
$parser = (new \PhpParser\ParserFactory())->create(\PhpParser\ParserFactory::ONLY_PHP7,
    new \PhpParser\Lexer\Emulative(['phpVersion' => '7.3']));
$visitor = new class extends \PhpParser\NodeVisitorAbstract {
    public function enterNode(\PhpParser\Node $node)
    {
        $unsupported = ['Expr_ArrowFunction', 'Expr_AssignOp_Coalesce', 'Expr_Match',
            'Expr_NullsafeMethodCall', 'Expr_NullsafePropertyFetch', 'Stmt_Enum',
            'UnionType', 'IntersectionType', 'AttributeGroup'];
        if (in_array($node->getType(), $unsupported, true)
            || ($node instanceof \PhpParser\Node\Stmt\Property && $node->type !== null)
            || ($node instanceof \PhpParser\Node\Expr\ArrayItem && $node->unpack)
            || ($node instanceof \PhpParser\Node\Arg && $node->name !== null)
            || ($node instanceof \PhpParser\Node\Param && $node->flags !== 0)) {
            throw new \RuntimeException('Syntax newer than PHP 7.3 at line ' . $node->getStartLine());
        }
        return null;
    }
};
foreach ($files as $file) {
    $traverser = new \PhpParser\NodeTraverser();
    $traverser->addVisitor($visitor);
    $traverser->traverse($parser->parse(file_get_contents($file)));
}
echo 'PASS: PHP 7.3 static syntax check on ' . count($files) . " files (not a PHP 7.3 runtime test).\n";
