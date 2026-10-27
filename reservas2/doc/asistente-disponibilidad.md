# Asistente de disponibilidad, tarifas y reservas

Chat de texto para usuarios activos autorizados por
`Identificador::autorizarPorNivel($identity, Niveles::ADMIN)`: incluye SYSADMIN (0)
y ADMIN (1). No registra accesos ni menús en base de datos. No hay tablas nuevas,
migraciones, workers, paquetes nuevos ni configuración SSH.

Las respuestas nuevas muestran fechas como `dd/mm/aaaa`, conservando la hora
cuando corresponde. Consultas, argumentos y datos estructurados del contexto
mantienen ISO `aaaa-mm-dd`. Si la descripción repite «Cabaña N», se muestra una
sola vez; las descripciones distintas se conservan después del número.

Disponibilidad y tarifas totales se presentan con título, comentarios y tabla
adaptable con desplazamiento horizontal en pantallas pequeñas. Las tarifas muestran
descripción/capacidad, ingreso y egreso `dd/mm/aaaa HH:mm`, total y promedio diario
(`total / días facturables`, calculado en PHP). El promedio es informativo, no una
tarifa uniforme cuando hay cambios por tramo. Cada fila informa el `min_dias`
aplicado; si varía, detalla los mínimos por tramo. Sin cobertura válida, total y
promedio no se inventan. Los pedidos diarios conservan su desglose por tramo.

La función devuelve `visible_presentation` sin HTML. El servicio la elimina del
resultado interno y la guarda solo en `turn.visible[].presentation`, de modo que
se restaure al recargar sin añadir datos de presentación al proveedor. El frontend
crea elementos de tabla conocidos y carga todo texto con `.text()`. Los mensajes
anteriores o sin presentación conservan su texto plano; no se interpreta Markdown
ni HTML generado por Gemini. Se mantienen los límites de contexto y los permisos.

## Seleccionar proveedor desde el entorno privado

`ASISTENTE_PROVEEDOR=gemini` (default) u `openai` selecciona el proveedor en el
servidor. El navegador no puede cambiarlo. No hay fallback automático entre
proveedores. Un nombre desconocido falla cerrado.

Para OpenAI, en el `.env` local no versionado:

```dotenv
ASISTENTE_PROVEEDOR=openai
ASISTENTE_OPENAI_MODELO=gpt-5.4-mini
OPENAI_API_KEY=colocar_la_clave_solo_en_el_archivo_privado
```

Gemini conserva `GEMINI_API_KEY` y su modelo actual, sin modificar su carga de
credenciales. Para volver, cambiar solamente `ASISTENTE_PROVEEDOR=gemini`.
Después de editar `.env`, recrear únicamente PHP desde la raíz del repositorio:

```sh
docker compose up -d --no-deps --force-recreate php
```

Reiniciar el contenedor no recarga `env_file`. No hace falta reconstruir la
imagen ni recrear la base de datos. Recargar el chat después del cambio: una
conversación está vinculada al proveedor y modelo. Un POST con el identificador
anterior recibe 409; el GET abre una conversación nueva sin mezclar estados nativos.
Las conversaciones Gemini anteriores a esta versión conservan su compatibilidad.

En producción por FTP, las tres variables pueden agregarse al archivo privado
`/home/vaqxtmgw/reservas.env`, fuera de `public_html`, junto a `GEMINI_API_KEY`.
`EntornoPrivado` lee únicamente los nombres permitidos, hasta 16 KiB, como datos,
sin ejecutar PHP, interpolar variables ni modificar el entorno. Variables del
proceso tienen prioridad, incluso si están vacías o son inválidas; en ese caso
no se usa el archivo para eludir una configuración explícita. El selector y
modelo del entorno/archivo prevalecen sobre valores locales de parámetros.
El archivo privado no se versiona ni se sirve al navegador. No subir `.env` al
webroot. Los archivos `.example` contienen solo plantillas sin claves.

`OpenAIProvider` usa HTTPS a `/v1/responses`, `Authorization: Bearer`, TLS
verificado y sin redirecciones. Usa `store=false`, historial limitado local,
`parallel_tool_calls=false` y las funciones implementadas en `ContratoAsistente`.
Las instrucciones y esquemas son comunes a ambos proveedores; Gemini adapta los
tipos del esquema sin cambiar su transporte. No se reutilizan los clientes OpenAI
antiguos que exportaban información del proyecto. No se instalan dependencias.
No se crean conversaciones remotas ni se envía `previous_response_id`.
Los resultados de las funciones, los `call_id` y los items opacos de OpenAI
(incluido razonamiento cifrado cuando se recibe) se conservan completos. Respuestas
incompletas o llamadas malformadas no ejecutan funciones. La prosa libre no se
muestra como un resultado comprobado. Se mantiene una sola solicitud externa
por turno y no se reintenta automáticamente ante errores.

El modelo de OpenAI debe admitir Responses y function calling; por defecto se
usa `gpt-5.4-mini`. Su disponibilidad y cuota dependen de la cuenta. La clave no
se guarda en los parámetros de Yii ni en el contexto. Falta/invalidación de clave
y HTTP 401/403 se muestran como `provider_configuration`; 429 como límite/cuota;
5xx como indisponibilidad y cURL 28/408/504 como timeout, con el proveedor correcto.

Referencias de OpenAI Docs consultadas para esta integración:
[Function calling](https://developers.openai.com/api/docs/guides/function-calling),
[Responses sin almacenamiento y estado](https://developers.openai.com/api/docs/guides/migrate-to-responses),
[modelo GPT-5.4 Mini](https://developers.openai.com/api/docs/models/gpt-5.4-mini).
No se hicieron solicitudes reales ni se modificaron claves durante las pruebas.

## Abrir y probar manualmente

En el entorno local existente, iniciar sesión con un administrador y abrir
`http://localhost:8281/reservas2/web/index.php?r=asistente/index` (ajustar el prefijo
si el VirtualHost usa otra raíz). También aparece **Asistente** en la barra del
layout administrativo. El enlace no otorga permisos: se verifican en cada endpoint
y en la función de datos. `index` acepta GET; `mensaje` y `nueva` únicamente POST,
con CSRF de Yii habilitado. No habilitar el antiguo circuito SQL.

La siguiente prueba manual **sí consume Gemini**; las pruebas automatizadas no:

1. Preguntar «¿Qué cabañas están disponibles del 10 al 15 de noviembre de 2026?».
   Comprobar las reservas y los horarios configurados de cada cabaña. El asistente
   usa ingreso/salida: el buscador anterior conserva su comportamiento previo.
2. Continuar «¿Y del 16 al 20?». Debe mostrar un período nuevo y otra hora de consulta.
   Después preguntar «¿Puedes darme los valores de cada una?»: debe conservar esas
   cabañas y fechas y calcular sus tarifas actuales, sin pedir nuevamente el período.
   Continuar «¿Y el valor por día?»: debe mostrar valores diarios por tramo de fechas,
   conservando la selección y el período, sin volver a mostrar únicamente totales.
3. Preguntar sin contexto «¿Qué hay libre?» después de **Nueva conversación**.
   Debe pedir fechas completas. Probar también 31 de abril y un rango invertido.
4. Reiniciar mientras aparece «Consultando…». La respuesta anterior no debe reaparecer.
   Una petición nueva puede recibir 429 hasta que termine la petición anterior.
5. Abrir la URL con un usuario de nivel superior a 1: debe recibir 403.

El texto del administrador se envía al proveedor para interpretar las fechas:
usar este chat para disponibilidad, tarifas y reservas y **no ingresar datos personales de clientes**.
Los listados y detalles cargan el titular para mostrarlo solo en la tabla del
administrador. Solo cuando se pide contacto se cargan email y teléfono. Esos datos personales no se envían a Gemini ni se
registran en diagnósticos. No se consultan documentos, archivos, notas privadas,
permisos. Para pagos solo se consultan campos específicos de solicitudes asociadas. Solo se declaran las funciones implementadas; no se exporta
un inventario de controllers/models.

## Consulta y contrato del proveedor

`FuncionesReservas::consultarDisponibilidad()` valida fechas estrictas YYYY-MM-DD
en la zona `Yii::$app->timeZone`. Para disponibilidad y tarifas la salida debe ser
posterior al ingreso: la fracción mínima es un día. Los listados de reservas sí
permiten consultar un único día calendario.

**Ajuste exclusivo del asistente:** consulta cabañas activas sin reservas
superpuestas mediante una query parametrizada y fija de Yii. Aplica a las fechas
consultadas y a las fechas de las reservas los horarios `checkin` y `checkout` de
cada cabaña. Permite recambio el mismo día si el egreso anterior no invade el
siguiente ingreso; no inventa minutos adicionales de limpieza. Una reserva 10–15,
con checkin 15:00 y checkout 10:00, permite consultas 09–10 y 15–16. No acorta una
estadía porque el huésped se retire antes. Los métodos del buscador anterior,
incluidos `Reserva::cabanasLibres()` y `cabanasEstaLibre()`, quedan sin modificar;
pueden diferir del asistente en los extremos del período.

Solo bloquean filas de `Reserva` relacionadas mediante `ReservaCabana`, no las
solicitudes. No se agrega un filtro de estados: una cancelación libera cuando el
flujo existente retira esa ocupación. El asistente no cancela ni modifica registros.
Ordena por número, limita a 50 cabañas e indica truncamiento. Muestra horarios y
capacidad máxima, sin interpretar esta última como huéspedes efectivos. El parámetro
opcional `pax` exige un entero positivo y filtra `max_pax >= pax`: una cabaña de
siete permite dos o siete huéspedes, pero no ocho. El filtro es por cabaña; no
distribuye automáticamente un grupo entre varias. Los precios se consultan con
`consultar_tarifas`.

Gemini propone fechas; Yii comprueba todos los argumentos antes de ejecutar.
Varias llamadas, nombres desconocidos, parámetros adicionales y fechas inválidas
no ejecutan funciones. Cada consulta válida vuelve a leer la base. Una respuesta
vacía de datos se distingue de un error de consulta. Las respuestas factuales y
su fecha/hora se construyen en PHP; la prosa libre del modelo no se presenta como
resultado ni como dato comprobado. Sin llamada se muestra una aclaración fija que
menciona las consultas disponibles y cómo referirse a la selección anterior.

### Criterio de fechas para reservas

La búsqueda predeterminada siempre es **por estadía**. Solo cambia cuando el
usuario especifica otro criterio en la consulta:

| Pedido | Función / campo |
| --- | --- |
| Reservas en/tomadas en un período (predeterminado) | `listar_reservas_por_estadia`: solapamiento de `desde` / `hasta` |
| Fecha de creación o registro, creadas/registradas | `listar_reservas_registradas`: `fecha` |
| Fecha de ingreso, llegadas o check-in | `listar_reservas_por_ingreso`: `desde` dentro del rango |

Ingreso incluye los dos días completos, desde 00:00:00 hasta 23:59:59. Una reserva
que empieza antes y continúa dentro del período se incluye por estadía pero no
por ingreso. Cada respuesta indica expresamente el criterio aplicado. Se
mantienen la autorización, la proyección sin datos personales y el límite de 20
resultados en las tres funciones.

`listar_reservas_por_estadia(desde,hasta)` interpreta «reservas tomadas» o
«reservas en un período» como estadías que coinciden total o parcialmente con ese
rango. Esta interpretación responde a los ejemplos 81, 82 y 83 proporcionados por
el usuario: se registraron el 1 de julio, pero su estadía alcanza el 25–26 de julio.
Usa la misma condición de solapamiento inclusivo del calendario existente:
`NOT (hasta < inicio_del_rango OR desde > fin_del_rango)`. Incluye reservas que
empiezan antes o terminan después. No altera las reglas de disponibilidad.

`listar_reservas_registradas(desde,hasta)` responde a pedidos expresos de reservas
«registradas», «creadas» o «por fecha de registro» (`Reserva.fecha`, etiquetada
«Creada»). Conserva este filtro separado de la estadía. Reutiliza la query de
`ReservaSearch::searchIndex()` con su filtro `fecha`; no llama al controlador.
Ninguna de las dos consultas añade filtros de estado ni solicitudes pendientes
separadas. La respuesta muestra explícitamente el criterio elegido.

«Dime las reservas tomadas desde el 25 al 31 de julio del corriente año» usa el
año de la fecha actual enviada desde el servidor; en 2026 consulta desde
`2026-07-25 00:00:00` hasta `2026-07-31 23:59:59`, incluidos ambos extremos.
Ese ejemplo consulta **por estadía**. Para filtrar por creación, pedir «reservas
registradas del 25 al 31 de julio del corriente año».

La respuesta construida por Yii conserva ID, fechas, cabañas y estado en el
resultado interno. La tabla privada muestra reserva, recurso/capacidad, locador,
creación, ingreso, egreso, días, total guardado y total cobrado verificado.
No serializa modelos completos al proveedor ni envía nombres, importes o enlaces
de seguimiento. La tabla usa `visible_presentation`, retirado por el servicio antes
de guardar el resultado interno de la función.
Los resultados se guardan en la sesión para continuidad; una lista de reservas
no se interpreta como selección de cabañas para cotizar precios anteriores.

Ordena por fecha de ingreso (estadía) o registro, e ID ascendentes; devuelve hasta 20 reservas y
consulta una fila adicional para detectar truncamiento. Si hay más, pide acotar
el rango; no afirma que esas primeras 20 sean el listado completo. Ausencia de
registros se distingue de error. Cada pedido vuelve a consultar la base y exige
permisos de administrador, validación estricta de fechas y el mismo control de
CSRF, contexto y límites de llamadas que las otras funciones.

Prueba manual: pedir el listado del ejemplo y comparar con el calendario de
ocupación. Pedir luego el listado por «registradas» y comparar con el filtro
**Creada** del listado administrativo. Ambos criterios pueden devolver resultados
distintos para el mismo rango y eso se indica en la respuesta.

### Tabla común de reservas y seguimiento

Los tres listados, los detalles y los pagos usan `presentacionReservas()`: misma
tabla de nueve columnas y fechas `dd/mm/aaaa HH:mm`. Los días se obtienen de las
fechas de estadía guardadas (salida menos ingreso), nunca del rango de búsqueda.
La capacidad se lee de cada cabaña; no se confunde con el pax registrado.

Con una única solicitud asociada, total y cobrado provienen de `total` y del
`pagado` verificado según las mismas reglas de `analizarPagos()`. Sin solicitud,
el total se obtiene de los valores válidos guardados en `ReservaCabana`, si
existen; el cobrado queda desconocido. Con varias solicitudes se advierte la
ambigüedad y no se suman importes ni se elige uno arbitrario. No se recalculan
tarifas actuales para una reserva histórica. Inconsistencias de pagos se señalan.

El enlace de la reserva usa `disponibilidad/seguimiento` con el hash de la primera
solicitud por ID, siguiendo el enlace «Ver» de la grilla. Abre la misma página en
un modal Bootstrap con iframe; al cerrar se limpia el iframe. Sin solicitud/hash
válido se muestra el ID sin enlace. Solo se aceptan enlaces del prefijo generado
por Yii y un hash válido; no se interpreta HTML. La grilla existente usa popup,
no modal: el chat reutiliza el destino, sin modificar esa grilla ni su página.
Los hashes quedan exclusivamente en `turn.visible[].presentation`, sujetos a
permisos y límites de contexto; no se envían al proveedor ni se registran en logs.

### Titulares, datos generales y contacto de reservas

`consultar_detalle_reservas` permite referencias al último listado guardado o
números de reserva explícitos. Usa `Reserva::find()` y las relaciones reales
`locador`, `cabanas` y `estado`, con columnas seleccionadas expresamente. El
titular es `locador.denominacion`; no se infiere ni se genera con IA.

- «¿A nombre de quién están las reservas encontradas?»:
  `{"origen":"anteriores","detalle":"titular"}`. Yii toma los IDs del último
  listado exitoso, sin repetir fechas ni aceptar IDs adicionales en ese modo.
- «Dame los datos de la reserva 82»:
  `{"origen":"seleccionadas","ids_reservas":[82],"detalle":"general"}`.
  Muestra titular, fechas, cabañas, pax registrado y estado. No asume que pax
  representa ocupación efectiva.
- «¿Y sus teléfonos y emails?»:
  `{"origen":"anteriores","detalle":"contacto"}`. Muestra titular y contacto.
  Email y teléfono no se cargan para titular/general. Nunca incluye documento,
  domicilio, notas internas, archivos o tokens en el contexto del proveedor.

«Agrega quien las reservo y su telefono» usa también una única llamada con
`detalle=contacto`, que ya incluye el titular. No requiere dos funciones. Ante un
fallo de comunicación con Gemini (por ejemplo HTTP 503), el mensaje invita a
reenviar el pedido en la misma conversación; no se ejecuta una función de datos
ni se sustituye la selección anterior. Siguen aplicándose caducidad y recorte del
contexto. No se hacen reintentos automáticos ni llamadas extra al proveedor.

`general` es el detalle predeterminado. Se admiten hasta 20 IDs enteros positivos;
si alguno no existe se rechaza la consulta completa. Una selección anterior vacía
permanece vacía; nunca se consulta todo por omitir un filtro. Una selección
truncada conserva el aviso de que solo se detallan las reservas mostradas. Una
consulta de cabañas/tarifas posterior invalida la referencia a reservas anteriores
para evitar confundir entidades. Reinicio, caducidad, usuario y permisos conservan
los controles de las otras funciones, incluida revalidación tras la llamada IA.

**Separación de datos personales:** la función devuelve `visible_text` y
`visible_presentation`, que se
guarda exclusivamente en `turn.visible` para la interfaz autorizada. El servicio
elimina ambas claves antes de armar el resultado de la función y guarda en
`turn.messages` solo IDs, tipo de detalle y una confirmación fija sin datos
personales. La respuesta final del contexto interno también usa esa confirmación,
nunca el texto con nombres/contactos. El adaptador recibe solo `turn.messages` y
conserva la llamada original y sus firmas. Los datos visibles se guardan en la
sesión privada, sujetos a los límites de 24 KiB, seis turnos y caducidad existentes;
no se copian al proveedor ni al log, tampoco en una continuación posterior.

La interfaz presenta nombres y contactos como texto, con `.text()` y JSON
codificado en atributos al recargar. Sigue siendo exclusiva para administradores
activos y sus respuestas usan `no-store`. Cada consulta vuelve a leer la base.

Prueba manual: listar reservas, pedir sus titulares, luego sus contactos y después
los datos de una reserva por número. El titular debe coincidir con el locador de
la reserva. Reiniciar y pedir «sus titulares» debe solicitar una selección nueva.
Las pruebas automatizadas comprueban que los datos de clientes no aparezcan en
los payloads simulados de turnos posteriores, además de permisos, IDs inválidos,
aislamiento de usuarios y texto con HTML.

### Búsqueda general de reservas impagas

«Dame las reservas impagas» usa `listar_reservas_impagas`, sin IDs ni listado
anterior. Sin fechas consulta todos los períodos; no hereda silenciosamente el
período de una conversación anterior. Con `desde` y `hasta` filtra por solapamiento
de estadía; el argumento opcional `criterio` permite `fecha_registro` o `ingreso`
solo cuando se pidan expresamente. Las fechas de este filtro incluyen ambos días.

Impaga significa saldo positivo según pagos verificados, incluidos pagos
parciales. Se buscan filas reales de `Reserva` con una única `RequestReserva`
asociada en estado `confirmado` o `confirmado-verificar-pago` (los estados que
crean/sincronizan reservas en el flujo existente). El acumulado de Confirmado se
interpreta con `analizarPagos()`, sin inventar verificaciones por movimiento. Una
solicitud pendiente con acumulado cero solo participa si el historial válido no
contiene importes positivos. Pagos positivos pendientes, solicitudes múltiples,
falta de solicitud o importes desconocidos no se presentan como deuda confirmada.
No se consultan solicitudes sueltas ni se recalculan tarifas históricas.

La query parametrizada prefiltra los candidatos por importes y solicitud única;
revisa hasta 200 y presenta hasta 20 reservas en orden de ingreso/ID ascendente.
Advierte truncamiento o registros por revisar. El texto sin resultados se limita
a saldos determinables en los registros revisados y no afirma que no existan
deudas desconocidas. Antes de mostrarlos se revalida que el saldo presentado siga
siendo positivo. La tabla común agrega «Saldo pendiente» solo para este listado.
Los importes, titulares y enlaces quedan en el mensaje visible; al proveedor solo
se envían IDs y confirmación fija. El resultado conserva los IDs para continuar
con «sus titulares» o «sus pagos», sin convertir una selección vacía en todas.

### Consulta de pagos de reservas seleccionadas

«¿Tienen pagos registrados?», «¿Pagaron?» o «¿Cuánto deben?» utiliza
`consultar_pagos_reservas`, no el detalle general ni el estado de la reserva.
Admite `{"origen":"anteriores"}` para la selección guardada y
`{"origen":"seleccionadas","ids_reservas":[81,82,83]}` para IDs explícitos.
Mantiene la selección después de consultar titulares, contacto o pagos, con
autorización por administrador activo en cada ejecución.

Los datos reales viven en `RequestReserva`, asociado por `id_reserva`:
`pagado` es el acumulado y `registro_pagos` contiene los movimientos. Se muestran
por solicitud (sin sumar distintas solicitudes como si fueran un único cobro):
total, pagado registrado, pagado verificado, saldo según verificación, cantidad de
movimientos y cuántos tienen importe positivo. Según la regla de negocio actual,
el estado de solicitud con slug `confirmado` significa verificación global del
acumulado `pagado`, aunque sea parcial. En ese caso el saldo es `total - pagado`.
No existe verificación individual por movimiento. Un comprobante presentado o un
importe registrado pendiente no acredita por sí solo el dinero: si hay importes
positivos sin ese estado, el verificado y el saldo se indican como no determinables.
Sin importes positivos, con acumulado cero e historial válido, se informa cero
verificado. No se inventan estados ni verificaciones para otros slugs. La consulta
no crea, elimina, valida ni modifica pagos o estados.

Un movimiento de monto cero no se presenta como un importe positivo. Si hay
acumulado pagado sin movimientos, se informa esa diferencia. Si la suma de
movimientos difiere del acumulado, se pide revisar el registro. Se distingue
información incompleta, ausencia de importes positivos y ausencia de solicitud
asociada (que no permite concluir que no haya pagos). Importes inválidos no se
convierten silenciosamente a cero. No se asume saldo cero ni se ocultan saldos
negativos. Se respetan los valores registrados, sin inventar conciliación bancaria.

Se consultan hasta 20 reservas y se muestran las primeras 5 solicitudes de cada
una, buscando una sexta para detectar truncamiento. Si hay más, el aviso aclara
que sus pagos no forman parte del resumen. No se incluyen rutas de comprobantes,
notas privadas o datos bancarios. La tabla común incluye locador y enlace de
seguimiento solo en la presentación administrativa. El desglose por solicitud
permanece disponible bajo «Detalle de pagos registrados».
Los importes y el resumen financiero se guardan únicamente en el mensaje visible;
Gemini recibe solo IDs y confirmación fija, también en turnos posteriores. Cada
consulta vuelve a leer los datos, sin reutilizar saldos históricos.

Prueba manual: listar reservas o consultar sus titulares, preguntar «¿Tienen
pagos registrados?» y luego «¿Cuánto deben?». Comparar el resumen con las
solicitudes asociadas. El chat debe mostrar pagos y saldo, sin repetir el detalle
general. Confirmado con acumulado positivo debe mostrar verificación global;
Confirmado con cero no debe inventar un cobro; pendiente no debe descontarse como
pago verificado.

### Tarifas y referencias a la respuesta anterior

`listar_tarifas_cabana` responde tanto a «¿Puedes mostrarme las tarifas válidas
para la cabaña 1?» como a «¿Cuáles son las tarifas para todas las cabañas de
junio?». Exige un selector explícito: `numero_cabana` entero de 1 a 99 **o**
`todas=true`, sin combinarlos. El selector usa números visibles, nunca IDs.
No exige fechas. Sin fechas nuevas usa un período anterior del servidor, si lo
hay; sin período lista activas vigentes y futuras. `usar_contexto=false` ignora
el período anterior.

Las fechas explícitas usan `periodo_tipo=vigencia` por defecto: son un filtro
calendario, con inicio y fin incluidos, y permiten consultar un solo día.
Para junio se filtra del 1 al 30 del año resuelto por la conversación; no se
interpreta como una estadía de 29 días. La tabla mantiene recurso, tarifa
(descripción), desde, hasta, `min_dias` y valor por día. El criterio aparece en
texto pequeño bajo la descripción. No consulta disponibilidad, no elige una
tarifa por duración y no calcula totales. Incluye todas las tarifas activas que
intersecten ese período, respetando cada asociación cabaña/tarifa, aunque una
tarifa esté compartida entre varias cabañas. Incluye cabañas inactivas, con aviso;
las cabañas sin tarifas para el filtro se indican en las notas.

Solo `periodo_tipo=estadia` interpreta fechas explícitas como ingreso y checkout
excluido. Un contexto previo de disponibilidad/cotización también conserva esa
semántica. En ese caso identifica tarifas elegidas, elegibles no elegidas y mínimos
superiores que no aplican, y señala la falta de cobertura completa. Sin estadía
no inventa una tarifa elegida. El resultado conserva `periodo_tipo`: un catálogo
por vigencia no se puede usar como estadía en una cotización posterior sin fechas
reales de ingreso y salida. Si el período era de estadía, «¿Y el total?» sí cotiza
la misma selección. Un listado anterior por vigencia conserva esa semántica al
continuar, sin convertirla a checkout.

La consulta devuelve hasta 50 cabañas y 20 asociaciones de tarifas, con detección
de truncamiento. No trata una asociación repetida en distintas cabañas como un
duplicado. Invalida referencias anteriores a reservas. Truncar asociaciones no
altera la selección de cabañas; truncar cabañas sí conserva ese aviso. Mantiene
permisos, argumentos estrictos, texto seguro y restauración de la tabla al recargar.

`consultar_tarifas` tiene tres formas de selección, siempre de solo lectura:

- `{"origen":"anteriores"}`: Yii recupera el período y los IDs exactos del último
  resultado exitoso de disponibilidad o tarifas de la sesión. No acepta fechas ni
  IDs propuestos por el modelo para esta forma. Una selección vacía sigue vacía;
  nunca se convierte en todas las cabañas. Si el historial fue recortado, venció o
  se reinició, pide una consulta nueva en lugar de inventar fechas o cabañas.
- `{"origen":"disponibles","desde":"2026-11-15","hasta":"2026-11-30"}`:
  vuelve a consultar disponibilidad del asistente y calcula esos precios.
  Admite `pax` entero positivo para filtrar capacidad antes de cotizar. Para
  «son 3 personas para la primer semana de noviembre, necesito saber disponibilidad
  y precios», se usa una única llamada con `origen=disponibles`, las fechas
  interpretadas y `pax=3`. La respuesta muestra disponibilidad actual, capacidad,
  horarios y total por cabaña; si falta tarifa, lo informa sin inventar importes.
  Las fechas se muestran expresamente: ingreso 1, salida 7 son seis días, no siete.
  No se añade una segunda llamada a Gemini ni se permiten múltiples herramientas
  por turno. Una continuación diaria conserva la selección y el pax de referencia;
  no afirma haber vuelto a validar disponibilidad. `pax` explícito no se acepta
  para `anteriores` ni `seleccionadas`, para evitar cambiar silenciosamente la selección.
- `{"origen":"seleccionadas","numeros_cabanas":[1,2],"desde":"2026-11-15","hasta":"2026-11-30"}`:
  consulta cabañas por sus números visibles (no por IDs). Se permiten hasta 50
  números enteros de 1 a 99. Si se omiten ambas fechas, usa el período del último
  resultado guardado; una sola fecha o una cabaña inexistente invalida la consulta
  completa. La selección explícita o anterior no afirma disponibilidad actual.

El cálculo del asistente usa `FuncionesReservas::tramosTarifas()`, separado del
motor anterior del sitio. La duración es salida menos ingreso; se cobra desde el
día de ingreso hasta el anterior al checkout. Cada tarifa cubre sus fechas
`inicio` y `fin`, ambas inclusive, y solo se toma si `activa=1` y
`min_dias <= duración total de la estadía`. Por tramo se elige el mayor `min_dias`
que cumpla esa condición. No se aplica una tarifa de mínimo superior como fallback.
Para igual `min_dias` se conserva el desempate anterior por menor `valor_dia`.
Una tarifa que cubre todo el período no desplaza otra con mayor mínimo elegible
que cubra solo parte: se calculan los tramos correspondientes. La duración usada
para decidir el mínimo siempre es la estadía completa, no la longitud del tramo.

Ejemplo, tarifas activas 01/12/2026–20/12/2026: $15.000/día con mínimo 1 y
$10.000/día con mínimo 3. Ingreso el 03/12 y salida el 04/12: $15.000;
salida el 05/12: $30.000; salida el 06/12: $30.000; salida el 07/12: $40.000.
Una tarifa inactiva no participa. Si solo existe mínimo 3 para una estadía de un
día, falta cobertura válida y no se cotiza. Los helpers compartidos y el buscador
anterior conservan su comportamiento. Las fechas originales de ingreso/salida
siguen en el contexto, y cada cabaña muestra sus propios horarios.

La validación existente `base/CabanaTarifa::validateTarifaRangeNoOverlap()` impide
asociar rangos superpuestos sin considerar distintos mínimos. No se modifica
ese editor en este ajuste; las pruebas siembran expresamente los vínculos del
escenario de negocio para comprobar el cálculo del asistente.

Muestra el total para todo el período de cada cabaña con el formato `$ 1.234,56`;
no supone que todos los días tengan el mismo valor. Falta de cobertura válida se
presenta como **Sin tarifa completa para el período**, sin totales parciales,
cero o precios inventados. Cada pedido recalcula desde la base; no reutiliza
importes históricos. No suma impuestos, descuentos ni reglas ajenas al método.

El argumento opcional `detalle` admite `total` (default) y `diario`. Para «¿y el
valor por día?» Gemini propone `{"origen":"anteriores","detalle":"diario"}`.
Yii devuelve el valor diario real de cada tramo, con sus fechas; no presenta
`total / días` como si fuera una tarifa uniforme. Si cambia la tarifa dentro del
período, muestra todos los tramos. Se conserva la duración total del período al
elegir tarifas por `min_dias`: no se recalcula cada fecha como una estancia de un día.

El total y los valores diarios del asistente provienen de los mismos tramos.
Se comprueba cobertura completa antes de mostrar importes; no se exponen precios
parciales como una cotización válida.

Para «los valores de cada una», Gemini solo elige `origen=anteriores`; la selección
se resuelve en el servidor. Tanto disponibilidad como tarifas conservan su resultado
en el contexto común, con las firmas del proveedor y sin datos de clientes. Se
mantiene una sola llamada a Gemini y una sola función propuesta por turno.

Se conserva **sin cambiar** el modelo fijado en `GeminiClient::ENDPOINT`:
`gemini-3.6-flash`. No hay alias ni fallback automático. El proveedor se elige
en el servidor mediante el entorno privado. El adaptador
extiende `GeminiClient` porque `sendRequest()` es protegido y su interfaz pública
está orientada a `askQuestion()`. La única extensión del transporte base son dos
propiedades de timeout; sus defaults 10/60 preservan los clientes anteriores.
Se reutilizan las claves privadas, TLS, encabezado, no redirecciones y diagnósticos.

Contrato REST verificado en documentación oficial (23/10/2026), sin llamar a la API:

- [Function calling / Generate Content](https://ai.google.dev/gemini-api/docs/generate-content/function-calling?authuser=0&hl=en):
  `tools.functionDeclarations`, `functionCall.name/args/id`, `functionResponse` con
  el mismo ID; modo AUTO para admitir aclaraciones sin ejecutar funciones.
- [Thought signatures](https://ai.google.dev/gemini-api/docs/generate-content/thought-signatures):
  conservar cada `content` completo, incluidas firmas en cualquier parte.

El formato común usa `role`, `text`, `tool_calls` (`id/name/arguments`) y resultados
`tool_call_id/name/data`. `provider_state` es opaco y privado. Gemini reproduce el
content original, agrega el resultado de Yii y la respuesta factual antes del
siguiente mensaje. No existe segunda llamada al modelo dentro del mismo turno.
`OpenAIProvider` también implementa `ProveedorInterface`; `ProveedorFactory`
selecciona el adaptador sin cambiar el servicio ni las funciones de reservas.

## Contexto, concurrencia y límites

La sesión PHP guarda `__asistente`, ligado al ID del usuario y un ID aleatorio de
conversación. Una conversación activa por sesión; las pestañas comparten estado.
Se separan mensajes visibles de mensajes internos; el navegador no entrega
historial confiable ni recibe firmas o llamadas internas. Tras seis intercambios
se eliminan unidades completas usuario/modelo/función/respuesta. Se comprueban
24 KiB tanto en JSON UTF-8 como en serialización PHP. Si un intercambio por sí
solo excede el presupuesto se devuelve 413 y se conserva el contexto previo.

Caduca a los 30 minutos desde la última creación o respuesta guardada. Leer la
página no renueva ese plazo. Reiniciar cambia el identificador. Antes de consultar
y guardar se revalidan identidad activa/nivel, ID de sesión, propietario, ID,
revisión y vigencia. Una respuesta tardía recibe 409 y no escribe en la conversación
nueva. El frontend también invalida callbacks al iniciar un reinicio.

La sesión se cierra antes de llamar al proveedor y se vuelve a abrir después. Un bloqueo
`flock(LOCK_EX|LOCK_NB)` por usuario se mantiene durante el turno y se libera en
`finally`/destructor; el sistema operativo lo libera si muere el proceso. Los
archivos `.lock` no se eliminan: evita carreras entre inodos diferentes.

Los contadores viven en `runtime/asistente-limites`, con nombres hash, sin texto
de consultas. La actualización se realiza bajo ese bloqueo mediante archivo
temporal y `rename` atómico en el mismo directorio. Un temporal interrumpido se
sobrescribe en la próxima operación. Un contador ilegible/corrupto falla cerrado.
No borrar estos archivos mientras la aplicación atiende peticiones. Requiere
filesystem local con flock/rename operativos; no está diseñado para varios hosts.

**Qué se cuenta:** una admisión después de permisos, formulario, conversación y
exclusión mutua, justo antes de intentar el proveedor seleccionado. También consumen las llamadas
fallidas y los intentos con credenciales del proveedor inválidas. No cuentan index,
reinicio, CSRF rechazado, mensajes inválidos, IDs vencidos o solicitudes rechazadas
por concurrencia/cuota. No hay reintentos automáticos. Cuotas por usuario, compartidas
entre sus sesiones: cinco por minuto y 60 por día. El minuto es una ventana fija
UNIX de 60 segundos; el día cambia a medianoche en la zona de la aplicación.

## Configuración sin secretos

Un HTTP 429 del proveedor se distingue del límite local del asistente: devuelve
un turno con código `provider_rate_limit`, explica que Gemini rechazó por uso o
cuota y pide revisar el proyecto en Google AI Studio si persiste. El adaptador
clasifica únicamente el código HTTP del diagnóstico existente, sin leer ni
guardar el cuerpo de error. No modifica el transporte compartido ni hace
reintentos automáticos. Un 429 por sí solo no identifica si el límite es por
minuto, tokens o cuota diaria, ni permite prometer un plazo de recuperación.

Un HTTP 503 se clasifica como `provider_unavailable` y muestra que Gemini está
temporalmente indisponible; cURL 28 se clasifica como `provider_timeout` y muestra
que se agotó la espera. Ambos conservan el estilo de error del historial. No se
ejecutan funciones de datos ante esos fallos, ni se hacen reintentos automáticos.
Subir el timeout no resuelve por sí mismo un rechazo HTTP 503 del proveedor.

Defaults en `config/asistente.php`, incluidos desde `config/params.php`:

| Variable de entorno | Default | Rango |
| --- | ---: | ---: |
| ASISTENTE_TURNOS | 6 | 1–6 |
| ASISTENTE_BYTES | 24576 | 4096–24576 |
| ASISTENTE_TTL | 1800 segundos | 60–1800 |
| ASISTENTE_POR_MINUTO | 5 | 1–100 |
| ASISTENTE_POR_DIA | 60 | 1–10000 |
| ASISTENTE_TIMEOUT | 60 segundos | 1–60 |

Son variables del proceso PHP, no entradas nuevas del archivo privado de claves.
En producción por FTP se puede sobreescribir `asistente` en el archivo local
**no versionado** `config/params-local.php` ya cargado por `config/web.php`:

```php
// Añadir a su array existente, conservando su configuración actual.
'asistente' => [
    'porMinuto' => 5,
    'porDia' => 60,
    'timeout' => 60,
],
```

Los valores restantes usan defaults; también se validan estos overrides.
No colocar claves allí ni en JavaScript. No cambia la carga segura existente de
`GEMINI_API_KEY`; ver la sección Gemini API de README.md. No mover su archivo
privado al directorio público. Un presupuesto de contexto menor puede rechazar
intercambios largos antes de guardarlos; no se fragmentan firmas.

cURL del asistente espera hasta 5 segundos para conectar y 60 en total. Solo el
endpoint solicita un presupuesto PHP de 75 segundos; frontend espera 80. No se
aumentan límites globales. Si el hosting impone un límite menor, reducir el timeout
de este asistente dejando margen para DB/PHP; confirmar que el límite de Apache o
PHP-FPM no corte antes. Una consulta DB lenta también puede agotar ese margen.

Respuestas HTTP: 403 permisos, 400 CSRF, 405 verbo, 409 conversación cambiada,
413 intercambio demasiado grande, 422 formulario, 429 concurrencia/cuota,
503 fallo interno. Errores controlados de interpretación/consulta se devuelven como
turnos de chat con un código y texto explícito; nunca como ausencia de disponibilidad.

Diagnósticos nuevos en `runtime/asistente-diagnostics.log`: proveedor y modelo
seleccionados (Gemini u OpenAI),
duración, código permitido y tokens totales; `null` significa consumo desconocido,
no cero. OpenAI registra además solo `http_status` y `curl_errno` en
`runtime/openai-diagnostics.log`. No se registran cuerpos, prompts, historial, claves o excepciones.
Las rutas del asistente desactivan los targets genéricos de Yii y el debugger para
esa petición. En otras rutas se excluye el historial de sesión del FileTarget y se
censura en el panel Request del debugger. `runtime` y sesiones deben permanecer
fuera del directorio público. Las sesiones son almacenamiento temporal privado,
no auditoría; siguen sujetas al GC/configuración de sesiones del hosting.

## Verificaciones automatizadas

Desde la raíz del repositorio, con contenedores y `reservas2_test` existentes:

```sh
docker compose exec -T php php tests/security/gemini_credentials.php
docker compose exec -T php php tests/security/asistente_php73.php
docker compose exec -T php php vendor/bin/codecept run unit models/AsistenteTest.php
docker compose exec -T php php vendor/bin/codecept run unit models/AsistenteOpenAITest.php
docker compose exec -T php php vendor/bin/codecept run unit models/AsistenteDisponibilidadTest.php
docker compose exec -T php php vendor/bin/codecept run unit models/AsistenteTarifasTest.php
docker compose exec -T php php vendor/bin/codecept run unit models/AsistenteReservasTest.php
docker compose exec -T php php vendor/bin/codecept run functional AsistenteCest.php
node reservas2/tests/browser/asistente.test.js
```

Las pruebas HTTP habilitan CSRF en cada petición pese al default de `config/test.php`.
Las pruebas del asistente usan un runtime temporal independiente por caso y lo
eliminan al terminar. Así un `docker compose exec` como root no crea contadores ni
diagnósticos privados dentro del runtime que Apache utiliza como `www-data`.
El transporte simulado prueba el adaptador real sin cURL ni claves. Los datos DB se
ejecutan en transacciones de Codeception; no preparar ni reconstruir DB si ya está
disponible. La regresión Gemini separada simula cURL e incluye timeouts/errores HTTP,
rotación de claves y ausencia de datos sensibles en logs. El test JavaScript usa
dobles de DOM/AJAX sin dependencias; la revisión visual en navegador es manual.

En esta implementación el runtime local disponible es PHP 7.4.33. Se revisa el
subconjunto sintáctico PHP 7.3 con el parser existente; falta ejecutar una prueba
en un runtime PHP 7.3 y la prueba manual real de los proveedores con credenciales
configuradas. El modelo de Gemini se conserva.
La regresión existente `ReservaSearchTest::testSearchCalendarioFiltraPorCabanaLocadorYCodigo`
encontró una colisión de fixture: el número 72 ya existe en la base local de pruebas.
No se cambió esa prueba ni se reconstruyó la base para ocultar el conflicto.

## Archivos a transferir por FTP (cuando se autorice un despliegue)

Subir, conservando rutas relativas a la aplicación:

- `controllers/AsistenteController.php`
- `controllers/ReservaController.php`, `controllers/DisponibilidadController.php`,
  `controllers/RequestReservaController.php`, `components/ReservaOcupacion.php`,
  `components/PagosReserva.php`
- `components/asistente/` completo
- `components/GeminiClient.php` (timeouts configurables; defaults conservados)
- `models/AsistenteMensajeForm.php`, `models/AsistenteReservaForm.php`, `models/AsistentePagoForm.php`, `models/AsistenteEliminarPagoForm.php`, `models/AsistenteEliminarReservaForm.php`
- `models/CabanaTarifa.php` (cálculo compartido de totales y detalle diario)
- `config/asistente.php`, `config/params.php`, `config/web.php`
- `assets/AsistenteAsset.php`
- `views/asistente/index.php`, `views/asistente/reservar.php`, `views/asistente/pago.php`, `views/asistente/eliminar-pago.php`, `views/asistente/eliminar-reserva.php`, `views/layouts/app.php`
- `web/js/asistente.js`, `web/css/asistente.css`
- Documentación: `doc/asistente-disponibilidad.md`, `README.md` (opcional en hosting).

No subir `.env`, credenciales, vendor nuevo, pruebas, archivos de runtime, sesiones
ni contadores locales. `runtime` debe ser escribible por PHP para diagnóstico y
bloqueos; los assets se sirven desde `web`. No hay migraciones ni cambios de modelos
generados. Esta tarea no realiza el despliegue.

### Permisos locales de Docker

Si se ejecutaron versiones anteriores de estas pruebas como root, Apache puede
devolver 503 antes de llamar a Gemini porque no accede al directorio de límites
(0700) o al diagnóstico. Con los contenedores locales actuales, corregir únicamente
los propietarios de los artefactos del asistente, conservando permisos privados:

```sh
docker compose exec -T php chown -R www-data:www-data runtime/asistente-limites runtime/asistente-diagnostics.log
```

Este comando presupone que ambos artefactos existen. No borra contadores ni
bloqueos y no amplía permisos a otros usuarios. En otro hosting el propietario
debe corresponder a su proceso PHP; no copiar propietarios de Docker por FTP.


## Crear una reserva sin pago inicial desde el asistente

Ejemplo: «Quiero reservar la cabaña 1 del 2 al 5 de noviembre de 2026».
Después de consultar disponibilidad/precios, también se puede pedir «reservá
las anteriores». Una selección truncada exige indicar números concretos.

`preparar_reserva` es una herramienta común de lectura para ambos proveedores.
Solo admite selección y fechas; no recibe clientes, precios impuestos, estados
ni pagos. `FuncionesReservas::prepararReserva()` vuelve a consultar disponibilidad
y exige tarifa completa. El chat muestra la tabla y **Completar y revisar reserva**.
La IA no tiene una función para confirmar ni guardar reservas.

El botón abre `asistente/reservar` en el modal existente. `AsistenteReservaForm`
recibe exclusivamente allí documento, nombre, email, teléfono, domicilio y
observaciones. Un documento existente reutiliza el locador sin modificarlo;
un pasajero nuevo necesita los campos requeridos por `Locador`, incluido email.
No se generan contactos ficticios. El formulario muestra una revisión con datos
reales, fechas, días y total, seguida de una confirmación POST con CSRF.

`BorradorReserva` conserva una única propuesta en la sesión privada durante 15
minutos, vinculada al usuario y la conversación; una nueva propuesta reemplaza
la anterior. Cambiar de conversación/proveedor/modelo invalida el acceso. El token
se entrega solo a la interfaz, se excluye del resultado reenviado al proveedor.
Los datos personales, la revisión y la presentación permanecen fuera del contexto
de IA. `__asistente_reserva` se excluye de logs/debug igual que `__asistente`.
No hay tablas, migraciones ni almacenamiento permanente del chat nuevos.

`CrearReservaService::confirmar()` exige administrador activo, revisión previa y
propietario del borrador. Solo acepta el resumen conservado en el servidor:
ignora importes, estados y pagos enviados por el navegador. Dentro de una
transacción bloquea las cabañas en orden, comprueba nuevamente superposición y
cotiza. Cambios de importes, horarios o capacidad requieren revisar otra vez.
Guarda `Reserva`, `RequestReserva`, `ReservaCabana` y `RequestCabana`, reutilizando
sus modelos, validaciones, auditoría y generador de código de seguimiento.

Se usa el estado existente `confirmado`, sin fallback a IDs arbitrarios. La reserva
es activa pero tiene `pagado=0` y `registro_pagos=[]`: no se inventa un movimiento
ni un comprobante ni una acreditación. Los valores provienen de las tarifas del
asistente: salida excluida, mínimo un día y mayor `min_dias <= días de estadía`.
El rango guarda el primer check-in y último checkout de las cabañas; las consultas
del asistente siguen aplicando a cada cabaña sus horarios configurados. `pax`
conserva la semántica de almacenamiento del flujo administrativo anterior: suma
de capacidades máximas, sin inventar una distribución de huéspedes.

La propuesta usa un token aleatorio como hash de solicitud. Una consulta bloqueante
bajo las mismas cabañas permite devolver la reserva ya creada si se repite la
confirmación, incluso si se perdió la respuesta HTTP. No se envían emails ni se
suben comprobantes en esta entrega. La creación confirmada se añade al chat con
su tabla privada y un resultado interno sin datos personales; permite continuar
con «¿tiene pagos registrados?». **Volver al chat** actualiza la conversación padre.

`ReservaOcupacion` coordina el bloqueo con los tres flujos de creación existentes:
`ReservaController::actionSolicitarReserva`,
`DisponibilidadController::actionRegistrarPago` y
`RequestReservaController::sincronizarReservaDesdeRequest`. Esos flujos conservan
su criterio de superposición por timestamp y sus cálculos anteriores, agregando
una comprobación dentro de la transacción mediante una lectura actual `FOR UPDATE`.
No se modifica el editor genérico heredado de reservas ni la API REST existente;
no se afirma cobertura de concurrencia para escrituras directas por esos accesos.

Validación automatizada: preparación sin escritura, alta sin pago, doble envío,
rollback completo si falla un detalle, tarifas/horarios modificados, superposición,
recambio el mismo día, mínimo de estadía/tarifa, reutilización de locador, permisos,
CSRF, tokens/propietario/vencimiento, presentación segura y privacidad del proveedor.
Las pruebas usan la base de pruebas y proveedores simulados; no crean reservas en
la base local de uso habitual ni realizan llamadas facturables a las APIs. No
sustituyen una prueba de carga concurrente ni una ejecución bajo PHP 7.3 real.


### Inicio de reserva sin elegir cabaña

`iniciar_reserva` permite empezar con «quiero hacer una reserva»: solicita el
período completo. Si ya hay días/mes pero falta el año, pide específicamente el
año, sin elegir el actual o el próximo. Con fechas completas consulta disponibilidad
y tarifas mediante Yii, muestra opciones y pide elegir los números de cabaña.
La elección continúa con `preparar_reserva`, conservando el período consultado;
no se genera propuesta ni se habilita guardar hasta que haya selección explícita.
Las aclaraciones usan texto controlado por Yii. Si el proveedor no propone una
función ante un pedido explícito de crear reserva, se muestra una orientación
específica sobre período/año, sin publicar sus posibles afirmaciones inventadas.


### Estado explícito de fechas durante el diálogo

`FlujoReserva` conserva en cada intercambio la etapa (`periodo`, `anio`,
`seleccion`, `confirmacion`, `cerrado`) y únicamente los componentes de fecha
que el usuario indicó. No guarda datos del pasajero. Las respuestas simples del
flujo —por ejemplo «quiero hacer una reserva», «para el 15 al 30 de agosto»,
«2027»— se resuelven en el servidor sin solicitar una interpretación nueva a la
IA. Falta el año: se conservan días/mes y se pide solo ese dato. Falta todo el
período: se pide ingreso y salida completos. No se inventa el año actual/próximo.

Con fechas completas Yii valida el período, consulta disponibilidad y precios y
conserva la selección para la elección de cabañas posterior. Esas respuestas son
mensajes reales de Yii, sin simular llamadas ni identificadores de un proveedor.
Los otros formatos y consultas siguen usando el intérprete común. Si el proveedor
responde sin función, una aclaración conserva las fechas pendientes y evita volver
al menú genérico. El estado sigue limitado a la sesión, usuario, proveedor/modelo,
TTL, seis turnos y bytes existentes, y desaparece al iniciar otra conversación.
También se intenta recuperar el período de intercambios anteriores al cambio,
si esos mensajes todavía forman parte del contexto acotado. Se mantienen los
contadores de admisiones del endpoint, incluidos los pasos resueltos en el servidor.


## Cargar un pago desde el chat

«Cargar un pago» inicia `FlujoPago`, que pide el número de reserva y conserva
esa pregunta en la sesión. «Reserva 81» o «81» continúa después de esa pregunta;
«cargar un pago a la reserva 81» permite empezar con el número. Una referencia
expresa a la anterior requiere una única reserva en el contexto, sin truncamiento.
El número corresponde a `Reserva.id`, no a solicitud ni cabaña. Una selección
múltiple exige elegir una. Los pasos simples no hacen llamadas a IA.

`preparar_pago_reserva` es la herramienta común de lectura para expresiones más
libres: solo acepta origen y una reserva. No acepta montos, estados, notas,
archivos ni verificación. Yii consulta la reserva y muestra su tabla privada de
pagos junto con **Completar y revisar pago**. No se guarda ningún pago en esta
etapa. Se requiere una única solicitud vinculada y una reserva activa; los estados
permitidos son los del alta administrativa actual: `confirmado`,
`confirmado-verificar-pago` y `pendiente-email-verificado`. No se crean solicitudes
ni reservas nuevas para poder pagar. Casos sin solicitud o ambiguos se derivan a
la gestión existente, sin elegir uno arbitrariamente.

`asistente/pago` usa autorización de administrador activo y CSRF en cada POST.
`AsistentePagoForm` recibe monto positivo con hasta dos decimales (coma o punto,
sin separador de miles), nota de hasta 500 caracteres y comprobante opcional
PNG/JPG/JPEG/PDF de hasta 5 MB con validación de tipo. Requiere declarar que se
verificó la acreditación. Adjuntar un comprobante por sí solo no verifica nada.
Una revisión muestra reserva, titular, solicitud, acumulado, nuevo pago y saldo
resultante. El guardado exige otra confirmación POST, usando únicamente la revisión
conservada por el servidor; ignora montos/IDs/estados adicionales del navegador.

`CargarPagoService` bloquea la solicitud y la reserva, verifica estado/saldo y exige
una revisión nueva si cambiaron total, pagos o estado. Usa centavos para controlar
el importe: `monto > 0` y `monto <= total - pagado`, conservando el criterio actual
del formulario administrativo. Agrega una entrada compatible a `registro_pagos`
con fecha del servidor, monto, nota y comprobante, incrementa `pagado` y sincroniza
la reserva y la solicitud al estado `confirmado`. La actualización es transaccional.
No existe verificación individual: confirmar da por verificado el acumulado, incluidos
pagos anteriores pendientes. Esta consecuencia se muestra explícitamente en la
revisión. No se inventan estados «pagada» ni se cambian reglas generales del negocio.

El token de borrador se conserva también en el movimiento para reconocer una
confirmación repetida y devolver la misma reserva sin volver a sumar el importe.
La sesión usa `__asistente_pago`, borrador independiente del de reserva, con los
mismos vínculos de usuario/conversación y 15 minutos de vigencia. Se excluye de logs
y debug. Presentación, token, montos, notas y datos del comprobante no se reenvían
al proveedor. Después del pago se añade al chat una tabla financiera privada y un
mensaje interno sin importes ni datos personales para continuar con «¿y el saldo?».

Los comprobantes se almacenan directamente bajo `runtime/priv_comprobantes`, sin
una copia pública temporal. La revisión guarda un archivo `.pending` privado;
al confirmar se conserva con extensión permitida y su ruta se vincula al movimiento.
Los `.pending` abandonados durante más de un día se limpian en lotes de hasta 50
al adjuntar otro comprobante; nunca se borran por esa limpieza los archivos definitivos.
El guardado comprueba su hash y no puede reutilizar un comprobante alterado o perdido.
No hay migraciones ni dependencias nuevas. Esta entrega no envía emails ni
notificaciones automáticas por los pagos del asistente.

`PagosReserva` agrega una lectura bloqueante de la solicitud a los flujos existentes
de agregar/eliminar pagos y primera presentación de comprobante. Si otro proceso
cambió el historial/estado desde su lectura inicial, exige recargar. La reubicación
de comprobantes actualiza el movimiento por su ruta temporal usando el historial
actual; evita sobrescribir pagos añadidos después. No se cambia el CRUD/API genérico
ni se afirma protección frente a escrituras directas por esos accesos. Las pruebas
usan base de pruebas, proveedores simulados y archivos temporales privados: saldo,
parcial/total, doble envío, permisos, CSRF, vencimiento, rollback, estados, privacidad,
comprobantes válidos/inválidos y continuidad del diálogo.


### Reservas impagas por titular

`listar_reservas_impagas` admite `titular` opcional, de 2 a 100 caracteres,
además del período/criterio. Ejemplo: «dime las reservas impagas de jose rodriguez».
Se filtra `Reserva.id_locador -> Locador.denominacion`, el mismo titular que
muestra la tabla. Todas las palabras deben coincidir mediante LIKE escapado por
Yii, sin importar orden, mayúsculas ni tildes (`utf8mb4_unicode_ci` en la expresión
MySQL). Es una búsqueda por coincidencias de nombre, no una identificación única:
puede incluir varios titulares cuyos nombres coincidan. No busca en observaciones,
contactos ni denominaciones históricas de solicitudes.

El filtro se combina con AND con fechas y reglas de saldo existentes y se aplica
antes de los límites de candidatos/resultados. No se reutiliza un período anterior.
El encabezado privado informa el titular solicitado y el criterio de coincidencia.
Sin coincidencias, se conserva la búsqueda vacía; nunca se quita el filtro para
mostrar todas las impagas. Argumentos inválidos exigen corregir el filtro. No cambian
la definición de saldo pendiente ni los estados incluidos.

El contrato común instruye a ambos proveedores a enviar el nombre cuando el
usuario lo indique. Para la forma explícita «reservas impagas de [nombre]» sin
expresiones de fechas, Yii conserva además ese filtro aunque el intérprete omita
el argumento opcional. El resultado interno solo confirma si aplicó el filtro y
los IDs encontrados; nombres e importes recuperados permanecen en la presentación
privada. No hay migraciones ni cambios en los modelos o búsquedas anteriores.


### Precarga de monto y medio de pago escritos en el chat

«Agregar pago de 500 mil en Efectivo a la reserva 94» prepara el formulario con
`monto=500000.00` y `medio_pago=efectivo`, sin guardarlo ni marcar acreditación.
`FlujoPago::precarga()` interpreta únicamente valores literales del texto del
usuario: importe numérico, miles con puntos, coma/punto decimal y multiplicadores
`mil`/`millón`/`millones`. No usa importes sugeridos por un proveedor ni calcula
un pago por defecto. Formatos que no reconoce no se precargan. Si se indica
primero el monto y luego el número de reserva, conserva la precarga en el estado
privado del flujo. La presentación informa los datos recibidos y el botón para
continuar; el formulario permite editarlos antes de revisar.

El medio es opcional y admite Efectivo, Transferencia, Depósito, Tarjeta u Otro.
Se conserva como `medio_pago` adicional dentro del movimiento JSON existente,
sin columnas ni migraciones. No se cambian los cálculos de saldo ni la verificación
global; cobrar efectivo también exige declarar recepción/acreditación y confirmar.
Los borradores/revisiones anteriores sin medio siguen siendo compatibles.

`AsistenteAsset` versiona únicamente su JS y CSS con la fecha de modificación
del archivo. Al recargar la página se solicita la versión actual, incluida la
acción **Completar y revisar pago**, sin depender de la copia anterior del navegador.


### Acceso visible al pago pendiente

El chat muestra un bloque **Pago pendiente de confirmación** debajo del historial
y encima de **Tu consulta**, con el número de reserva y el botón **Completar y
revisar pago**. El enlace se renderiza también desde PHP al recargar: puede abrir
el formulario sin depender del renderizador de mensajes o del modal. Las respuestas
AJAX actualizan el bloque con una acción de interfaz derivada del borrador vigente.
Solo se expone si coincide usuario/conversación, no venció y no fue completado.
No se añade al contexto enviado al proveedor.

El acceso permanece aunque se recorten los mensajes anteriores. Tras confirmar,
vencer o cambiar de conversación, deja de mostrarse. Dentro del globo, el botón
se ubica junto al encabezado antes de las notas/tabla, y se muestra incluso si
no hay filas de detalle. Las pruebas cubren el enlace HTML fuera del área de
mensajes, recarga, historial recortado, mensaje sin filas y ocultación tras confirmar.


### Modal de alta de pago dentro del asistente

La preparación de un pago abre automáticamente el modal **Agregar pago**, con
el tamaño y presentación Bootstrap usados por `request-reserva/index` en su
columna de pagos. El formulario se carga por AJAX desde `asistente/pago` con
`modal=1`: entrega únicamente la vista Yii escapada, sin navegación ni scripts
del sitio. Los dos accesos **Completar y revisar pago** también abren ese modal.
El enlace conserva la apertura de página completa si JavaScript/Bootstrap no están
disponibles. Recargar una conversación con borrador pendiente no abre el modal
por sí solo; el acceso sigue disponible en el bloque destacado.

El envío usa FormData (incluye comprobante y CSRF), indicador de procesamiento y
bloqueo de doble envío/cierre mientras procesa, igual que la operatoria de la grilla.
La revisión y la confirmación conservan los controles del asistente. Al guardar,
se cierra el modal y se actualizan mensajes, tabla y acciones desde el contexto
real del servidor, sin recargar la página ni perder un texto sin enviar. Los errores
se muestran dentro del modal sin reintentar automáticamente. Una respuesta tardía
no puede reinsertar resultados después de cambiar de conversación.

El HTML proviene solo del endpoint privado fijo de Yii; no se interpreta HTML
propuesto por la IA. La apertura GET no guarda pagos y los POST siguen sujetos a
administrador activo, CSRF, vigencia del borrador, saldo y confirmación humana.
Las pruebas cubren apertura automática/manual, revisión por AJAX, comprobante en
FormData, CSRF, doble envío, cierre bloqueado, actualización tras confirmar y
preservación del borrador de mensaje.


### Buscar una reserva por titular durante la carga del pago

«Agregar pago de jose rodriguez» consulta las reservas de ese titular para elegir
un número, en vez de exigir un ID que el administrador todavía no conoce.
Durante una carga pendiente, «mostrame las reservas a nombre de jose rodriguez»
ejecuta la misma consulta y conserva la intención de cargar el pago, así como
monto/medio ya recibidos. También permite «mostrame sus reservas» cuando el titular
está guardado en ese flujo. La búsqueda muestra reservas reales, incluidas pagadas
e impagas, sin reutilizar fechas anteriores ni elegir automáticamente una reserva.
Después se indica un número explícito para preparar el formulario y abrir el modal.

`listar_reservas_por_titular` incorpora esa lectura al catálogo común de Gemini y
OpenAI, incluso fuera del flujo de pago. Requiere titular de 2 a 100 caracteres y
permite fechas/criterio opcionales. Usa el mismo filtro de palabras por locador,
sin distinguir orden, mayúsculas ni tildes, que la consulta de impagas; aplica los
filtros antes del límite de 20 reservas. Ordena por ingreso descendente y muestra
el formato de reservas existente, con saldo como dato informativo. No se sustituyen
las reglas de elegibilidad para agregar pagos: se verifican al elegir la reserva.

Una búsqueda vacía no prepara pagos ni recupera IDs de otra persona. Los datos
personales y financieros recuperados se mantienen en la presentación privada;
el contexto interno conserva los IDs y el estado de selección. Si se empieza a
buscar otra reserva para el pago, se invalida el borrador de pago previo para que
el acceso destacado no siga apuntando a una selección anterior. No se modifican
reservas ni movimientos al buscar/listar. Pruebas: diálogo nombre -> listado ->
selección -> modal, saldo, conservación de monto/medio, sin coincidencias, fechas,
permisos, pagadas/impagas y contrato común de proveedores.


## Eliminar un pago cargado por error

Se reconocen «anular», «revertir», «cancelar», «eliminar» y «borrar» seguidos de
«pago», por ejemplo «eliminar un pago de la reserva 94». Sin número pide la
reserva; permite buscar por titular conservando la intención de eliminación.
No interpreta «cancelar reserva» como eliminar un pago. Si se indican varias
reservas exige elegir una. Un importe escrito en el mensaje no selecciona ni
borra un movimiento automáticamente.

`preparar_eliminacion_pago_reserva` solo consulta y prepara el formulario: admite
origen y una reserva, sin índice, importes ni confirmación desde el proveedor.
El administrador abre el modal **Eliminar pago**, elige un movimiento concreto
(sin selección automática, aunque haya solo uno) y revisa fecha, medio, importe,
cobrado actual/resultante y saldo antes de confirmar. Los datos de movimientos,
notas y comprobantes se presentan únicamente en el formulario privado.

`EliminarPagoService` sigue el criterio de `RequestReservaController::actionEliminarPagos`:
una única solicitud asociada, estados `confirmado`, `confirmado-verificar-pago`
o `pendiente-email-verificado`, historial válido y cobrado positivo. La selección
se valida sobre el historial real. Restar el pago no puede producir un cobrado
negativo; si los importes son incoherentes exige revisión en la gestión existente.
Se borra UN movimiento, se reindexa `registro_pagos` y se resta su monto a `pagado`.
No se recalculan tarifas ni se cancelan/modifican fechas o estados de la reserva.
No se crean movimientos negativos ni nuevos estados de verificación.

El borrador privado `__asistente_eliminar_pago` está vinculado a usuario/conversación,
vigencia de 15 minutos y autorización de administrador activo, y se excluye de
logs/debug. Es mutuamente excluyente con el borrador de alta de pago para evitar
confundir acciones. El acceso destacado y el modal utilizan color/etiqueta de
eliminación. Todos los POST conservan CSRF. La confirmación ignora índices e importes
nuevos enviados por el navegador y solo usa la revisión guardada en el servidor.

Dentro de una transacción se bloquean solicitud y reserva y se compara la huella
del historial/importes/estados con lo revisado, más la huella del pago seleccionado.
Si otro operador modificó el historial, exige volver a seleccionar; no aplica un
índice viejo a pagos reordenados. Una repetición normal devuelve el resultado del
borrador completado. Ante una respuesta perdida, si el estado coincide exactamente
con el resultado previsto de la eliminación no vuelve a borrar un movimiento.
Si el estado es distinto, falla cerrado y pide revisar.

El comprobante se elimina después del commit, solo si su ruta canónica pertenece
a las carpetas existentes de comprobantes y no está referenciado por otro movimiento.
Se conservan archivos compartidos, rutas ajenas, enlaces simbólicos o archivos cuya
referencia no puede comprobarse con seguridad. La limpieza está acotada a 200
candidatos y no puede revertir una eliminación ya confirmada si falla. No hay
migraciones, dependencias, emails ni notificaciones nuevas.

La confirmación cierra el modal y refresca pagos/saldo en el chat. Las pruebas
cubren los cinco verbos, búsqueda por titular, selección explícita, permisos,
propietario/CSRF/vencimiento, reintentos, historial reordenado, rollback, último
pago, comprobantes compartidos/ajenos y ausencia de escrituras desde herramientas IA.


## Eliminar una reserva cargada por error

«Eliminar la reserva 94» o «borrar una reserva» inicia `FlujoEliminarReserva`.
Sin número permite buscar por titular y elegir una reserva. Siempre se procesa
una sola por operación, sin elección automática de un resultado por nombre.
No se interpreta «cancelar reserva» como autorización para borrarla físicamente,
ni se confunde eliminar una reserva con eliminar uno de sus pagos.

`preparar_eliminacion_reserva` solo prepara la revisión privada: recibe origen y
una reserva; no recibe instrucciones para borrar pagos, solicitudes o clientes.
El modal **Eliminar reserva** presenta titular, fechas, estado, cabañas y solicitudes
con sus totales, cobrado registrado y movimientos que se conservarán. Se requiere
marcar que se revisó el impacto, continuar y confirmar la eliminación. El acceso
pendiente es distinto del de pagos. Puede reabrirse tras recargar la conversación.

Se respeta `ReservaController::actionDelete`: se elimina Reserva y su detalle de
ocupación, conservando la solicitud y pagos desvinculados. La base de pruebas tiene
`request_reservas -> reservas ON DELETE SET NULL` y `reserva_cabanas -> reservas
ON DELETE CASCADE`. El servicio realiza además esas dos operaciones explícitamente
dentro de una transacción. No elimina `RequestReserva`, `RequestCabana`,
`RequestResponse`, `Locador`, movimientos ni comprobantes. Las solicitudes mantienen
su estado y se audita su desvinculación. No aplica los bloqueos por fecha/estadía
que están comentados y desactivados en el controlador actual, ni inventa otros.

`EliminarReservaService` bloquea cabañas, solicitudes, reserva y detalle. Compara
la huella de la reserva, vinculaciones y datos financieros con la revisión. Si
cambió el registro o sus pagos exige revisar; si falla un paso revierte también
la desvinculación y el detalle. La ocupación de esa reserva se quita al confirmar;
la disponibilidad sigue dependiendo de las demás reservas existentes.

El borrador `__asistente_eliminar_reserva` pertenece al usuario/conversación y
vence a los 15 minutos. Se excluye de logs/debug; sus datos personales/financieros
no se añaden al contexto de IA. Al preparar esta eliminación se invalidan los otros
borradores de escritura de la conversación para evitar accesos a acciones anteriores.
Cada endpoint/servicio exige administrador activo y cada POST conserva CSRF.
Los índices/IDs o instrucciones adicionales del POST no sustituyen el ID revisado.

Repetir la confirmación no borra otro registro. Si la reserva ya desapareció,
solo informa que ya no está registrada, sin atribuirse cambios ni asegurar que
otro operador preservó datos que no se pudieron verificar. Una eliminación hecha
por este servicio confirma que las solicitudes y pagos se conservaron. El modal
se cierra al completar y el chat recibe el resultado real; se descarta la selección
de reservas previa para no consultar como existente un ID eliminado.

Pruebas: búsqueda por titular y número, ausencia de escritura durante preparación,
permiso/CSRF/propietario/vencimiento, revisión explícita, pagos o reserva modificados,
rollback tras desvincular/detallar, varias solicitudes, reintento, disponibilidad
antes/después y preservación de cliente, solicitudes, chat, notas, pagos y comprobantes.
Sin migraciones, dependencias, despliegue ni notificaciones nuevas.

## Integración del esquema 7

Requiere aplicar `dbversion/7.sql` con el actualizador personalizado antes de
publicar esta versión de la aplicación. No usa migraciones de Yii2.

`Provincia` y `Localidad` representan los catálogos precargados. En
**Pasajeros**, crear/editar permite seleccionar provincia y luego localidad,
con búsqueda por descripción mediante Select2 de Kartik. Cambiar la provincia
limpia la localidad sin seleccionar automáticamente el primer resultado;
abrir o cerrar el selector conserva el valor hasta una elección explícita.
También permite registrar una calificación opcional de 1 a 5 estrellas,
o seleccionar «Sin calificación» (0). Se conserva la posibilidad de NULL en el modelo. La grilla muestra
ubicación y calificación, y permite filtrar por calificación.

`locador/localidades` sólo devuelve localidades de la provincia solicitada:
requiere sesión activa y nivel administrador, permiso de listar/crear/editar pasajeros,
o el permiso `reserva/reservar` usado para crear reservas.
La validación del modelo comprueba existencia y correspondencia de provincia y
localidad; sólo `locadores.id_localidad` se almacena. `id_provincia` es un selector.
Los registros anteriores pueden mantener su ubicación vacía.

La sección de datos del cliente en `reserva/solicitar-reserva` comparte los
selectores y la validación de ubicación. Conserva el nombre de formulario
`DynamicModel` para los demás campos del flujo existente. Al guardar, asigna
la localidad seleccionada al locador; envíos anteriores que omitan ambos campos
no modifican una ubicación existente.

El listado de pasajeros permite buscar con LIKE parcial en nombre y apellido,
documento, teléfono o email desde un solo campo. Combina esas coincidencias con
provincia, localidad y los demás filtros mediante AND. Provincia sola incluye
todas sus localidades. Los datos de contacto se reúnen en la columna Cliente;
la calificación se muestra con estrellas de sólo lectura, compartidas con el
detalle del pasajero. La solapa Más Info incluye localidad y provincia.

En **Solicitudes**, expandir la fila y abrir **Referencia y observaciones**
permite guardar ambos campos. La referencia también aparece en la grilla y
como filtro. El endpoint conserva permisos de actualización y CSRF, exige POST,
limita referencia a 45 caracteres y preserva la referencia si un cliente anterior
sólo envía observaciones. No modifica importes ni pagos.

En el formulario privado de creación de reservas del asistente se pueden indicar
ubicación para pasajeros nuevos y referencia de la solicitud. Se revisan antes
de confirmar y se guardan en la transacción existente. Los pasajeros existentes
conservan sus datos. No se agregan estos campos a argumentos ni resultados del
proveedor de IA; permanecen en el formulario y borrador privados.

## Recuperación de CSRF en el chat

Un POST rechazado por CSRF en `beforeAction`, después de exigir administrador
activo y antes de ejecutar la acción, devuelve HTTP 400 con `codigo=csrf_invalido`
y el token actual de Yii. La respuesta es JSON del mismo origen, sin caché y sin
captura de logs/debug del asistente. No se desactiva la validación CSRF de Yii.
El cliente actualiza las etiquetas y formularios mediante las funciones de Yii
y reintenta una sola vez el mensaje o el reinicio de conversación. No reintenta
operaciones de pagos/eliminaciones, timeouts, errores de proveedor ni peticiones
cuyo contexto fue abandonado mientras esperaban respuesta. Otros HTTP 400 no
se interpretan como vencimiento de seguridad ni provocan un reintento.

## Última reserva registrada

«¿Cuál es la última reserva hecha?» usa `consultar_ultima_reserva`, disponible
para ambos proveedores. No exige fechas ni IDs ni reutiliza el período anterior:
consulta `reservas` por `fecha DESC, id DESC`, donde `fecha` es el registro
mostrado como Creada en la grilla existente. No busca la última solicitud ni
ordena por ingreso. No acepta otros filtros; las consultas con titular o período
continúan usando los listados correspondientes.

Devuelve una reserva en la tabla privada habitual, con titular, recursos, estadía
e importes existentes. Si no hay registros, lo informa. Sólo IDs y resumen fijo
entran al contexto del proveedor; el titular y demás datos privados permanecen
en la presentación. El resultado permite continuar con «su contacto» o «sus pagos».

## Edición privada de pasajeros

`preparar_edicion_pasajero` habilita dos operaciones para administradores activos:
`datos` (nombre, documento, email, teléfono, domicilio, provincia/localidad y
observaciones) y `calificacion` (estrellas y observaciones). Se invoca, por ejemplo,
con «editar los datos del pasajero 73», «editar el pasajero de la reserva 94» o
«calificar al pasajero de la última reserva». Sin identificación, el modal permite
buscar por nombre, documento, teléfono o email. Los homónimos requieren elegir una
fila; no se aplica una edición a todos los resultados. La calificación admite
1 a 5 estrellas y la opción sin calificar. La nota edita `locadores.obs`: reemplaza
el texto previo y no crea un historial ni una nota de solicitud.

La función recibe exclusivamente operación y criterio de identificación; no recibe
valores nuevos para editar. El formulario carga los datos actuales en un modal con
iframe del mismo origen y layout propio, conserva Select2/estrellas/CSRF de Yii y
exige revisión y confirmación antes de guardar. Los datos y notas del formulario
no entran al contexto del proveedor. Sólo se conservan IDs y mensajes fijos para
poder continuar sobre el pasajero seleccionado. Se limita cada modo a sus campos;
IDs, auditoría y otros campos enviados por el navegador se ignoran.

`EditarPasajeroService` conserva validadores del modelo Locador, incluidas unicidad
y coherencia de ubicación. Una huella de los datos originales y un bloqueo de fila
al confirmar impiden sobrescribir cambios concurrentes. Permite recargar y revisar
la edición nuevamente. El borrador `__asistente_pasajero` vence a los 15 minutos y
se vincula al usuario, conversación y proveedor; está excluido de logs/debug. Un
POST completado no vuelve a aplicar la edición. No modifica reservas, solicitudes,
pagos ni tarifas, ni envía notificaciones. No requiere migraciones o dependencias.

El modal comunica su finalización a la página únicamente con `postMessage` del
mismo origen. El receptor verifica iframe, origen, token y conversación antes de
actualizar el chat; conserva el texto de una consulta aún no enviada. Cambiar de
conversación invalida mensajes tardíos del modal.

## Búsqueda ampliada de pasajeros

`buscar_pasajeros(texto,campo?)` permite consultar pasajeros sin preparar una
edición. Comparte el filtro de `LocadorSearch` con el listado y el modal privado.
Busca con LIKE parcial en nombre/apellido (`denominacion`), documento, teléfono,
email, dirección (`domicilio`), observaciones (`obs`), localidad y provincia.
También permite calificación exacta de 1 a 5; 0 incluye los registros sin calificar
(0 o NULL). No utiliza referencia: la aclaración del alcance fue localidad,
provincia y calificación, campos del esquema 7 existente.

Sin campo explícito consulta todos los campos; con un campo lo respeta sin ampliar
la búsqueda. Las columnas y relaciones están fijadas por el código, con parámetros
SQL enlazados y comodines escapados. Las ubicaciones se consultan con subconsultas,
sin duplicar pasajeros. Nombre y apellido siguen almacenados juntos.

Ejemplos: «buscar pasajeros por teléfono 2234», «pasajeros con observaciones que
contengan mascota», «pasajeros de la provincia de Córdoba» o «pasajeros con 5
estrellas». La tabla muestra ID, nombre/apellido, documento, teléfono, email,
dirección, observaciones, localidad, provincia y calificación. Las observaciones
se buscan completas; sólo su presentación se abrevia a 200 caracteres. Se muestran
hasta 20 pasajeros, con un límite adicional de bytes para conservar contexto acotado.
Cualquier recorte se informa y los IDs del contexto corresponden sólo a las filas
mostradas.

Los datos encontrados se muestran como texto seguro exclusivamente al administrador;
al proveedor se le devuelven únicamente IDs y un resumen fijo. «Ahora editá sus
datos» o «calificalo» puede usar la selección previa. Si hay varios pasajeros, el
modal exige elegir uno. `preparar_edicion_pasajero` admite además `buscar` y `campo`
para abrir la edición a partir de un dato de contacto, dirección, nota o ubicación.
La búsqueda del propio modal tiene el mismo selector de campos y muestra dirección,
observaciones, ubicación y estrellas. Buscar no aplica cambios al pasajero y no
requiere una migración.

### Egreso mostrado en las tablas de reservas

La columna Registro interpreta `reservas.hasta` como último día de estadía y muestra
el egreso al día siguiente, a la hora de `cabanas.checkout` actualmente configurada.
Si los recursos tienen horarios distintos, los muestra por cabaña; si coinciden,
muestra una sola fecha/hora. Sin cabañas u horario configurado indica que falta el
checkout. Cant. de días cuenta desde y hasta inclusive y se alinea al centro.
Por ejemplo, desde 01/04/2026 hasta 02/04/2026 implica dos días y egreso el
03/04/2026 al checkout. Estos ajustes son de presentación: no modifican los datos
guardados, las consultas de disponibilidad ni los importes.

Reserva muestra el código de seguimiento sin etiqueta debajo del enlace. Locador
muestra teléfono y email sin etiquetas: email válido enlaza mediante `mailto:` y
teléfono mediante `https://wa.me/`, abriendo WhatsApp en otra pestaña. Se eliminan
separadores del teléfono; números locales de diez dígitos se interpretan como
móviles argentinos (`549`), mientras que los internacionales conservan su prefijo.
Datos ausentes o con formato inválido se muestran como texto, sin enlace.


### Tabla de pasajeros del asistente

La tabla agrupa los datos personales en Pasajero (nombre, documento, teléfono con
WhatsApp, email, dirección y localidad/provincia), junto con ID, Observaciones y
Calificación con las mismas estrellas del CRUD. El ID abre un modal independiente
de sólo lectura mediante GET `asistente/pasajero-detalle&id=…`: exige administrador,
no permite POST y reutiliza `views/locador/view.php` con el layout del formulario
privado. Muestra el registro actual completo sin enviar sus datos al proveedor.
El modal de edición mantiene su flujo separado de revisión y confirmación.


### Consultas de reservas por mes completo

«Ver las reservas de julio» define el período del 1 al último día de julio del año
actual; un año explícito se respeta. El criterio sigue siendo estadía por solape.
El contrato común indica al proveedor que el mes ya es suficiente para llamar a
`listar_reservas_por_estadia`. Si el proveedor responde correctamente pero no
selecciona ninguna función, `AsistenteService` resuelve como respaldo únicamente
frases simples de listado por un mes completo. No interpreta filtros adicionales
como titular, impagas o creación, ni omite errores de comunicación del proveedor.
Ejecuta la función Yii existente con autorización actualizada y conserva IDs y
período en `seleccion_reservas` para consultas posteriores. La tabla con contactos
permanece sólo en la presentación privada, fuera del historial enviado al modelo.

### Consultas por audio

El botón «Grabar consulta» captura sólo audio mediante `getUserMedia` y
`MediaRecorder`, con permiso del navegador. Requiere HTTPS o localhost; en un
celular que accede a una IP local por HTTP seguirá disponible el chat de texto.
El navegador elige WebM/Opus o MP4 según soporte, detiene a los 60 segundos y
limita la grabación a 2 MB. También ofrece detener y cancelar. Al cancelar,
cambiar de conversación o salir de la página libera las pistas del micrófono e
ignora respuestas pendientes. Cancelar una petición ya enviada no asegura que el
proveedor deje de procesarla o cobrarla; no se reintenta automáticamente.

La transcripción se muestra en un campo editable separado. «Pasar a consulta» la
agrega al texto que ya estuviera escrito sin borrarlo; valida el límite combinado
de 2.000 caracteres. Sólo «Enviar» usa la conversación, las funciones y las
confirmaciones existentes. Una transcripción por sí sola no ejecuta operaciones,
crea borradores ni se agrega al historial de la conversación.

Configuración privada en `.env` local o en el `reservas.env` externo existente:

```dotenv
ASISTENTE_AUDIO_HABILITADO=1
ASISTENTE_AUDIO_MODELO=gpt-transcribe
# Reutiliza OPENAI_API_KEY, definida exclusivamente en el entorno privado.
```

Son los valores predeterminados si las variables de audio no existen. Usar `0`
para desactivar tanto el botón como el endpoint. El proveedor del chat continúa
siendo `ASISTENTE_PROVEEDOR`; el audio usa OpenAI de forma independiente mediante
`TranscriptorInterface` y `OpenAITranscriptor`. No debe configurarse el modelo de
chat como modelo de audio. La cuenta necesita acceso al modelo y cuota disponible.
La API y el modelo predeterminado se verificaron contra la documentación oficial:
https://developers.openai.com/api/docs/guides/speech-to-text

POST `asistente/transcribir` recibe `audio`, `conversacion` y CSRF. Exige un
administrador activo, conversación propia vigente y los mismos límites por usuario
que las consultas del chat (transcribir y consultar cuentan como dos llamadas).
No acepta claves, URL ni modelo desde el cliente. Comprueba los bytes reales y el
MIME del temporal PHP y rechaza archivos vacíos, formatos ajenos y tamaños mayores
a 2 MB, independientemente del nombre/MIME/tamaño declarados por el cliente.
El límite de duración es del grabador del navegador: el servidor no analiza la
duración del contenedor ni necesita ffmpeg; aplica el límite de bytes y llamadas.
El límite `upload_max_filesize` de PHP debe permitir al menos 2 MB y
`post_max_size` debe permitir el archivo más la envoltura multipart (por ejemplo,
8 MB). Un límite menor del hosting puede rechazar la carga antes de Yii.

La transferencia usa cURL multipart por HTTPS con certificado verificado,
sin seguir redirecciones, conexión de hasta 5 s y respuesta de hasta 60 s. PHP
usa un presupuesto acotado de 75 s y el navegador espera hasta 80 s. El bloqueo
de sesión se libera durante la llamada y al terminar se revalida usuario, nivel,
vigencia y revisión de conversación. Devuelve texto o errores públicos sin copiar
el cuerpo de error de OpenAI. Conserva CSRF; un rechazo actualiza el token para la
siguiente grabación sin reenviar audio automáticamente.

El audio permanece sólo en el temporal de carga de PHP hasta finalizar la petición:
no se mueve a `web`, no se guarda en BD y no se incorpora al historial ni a logs.
Se envía a OpenAI con la advertencia visible en la interfaz; el historial previo
no se envía a transcribir. El almacenamiento/retención del proveedor se rige por
la configuración de la cuenta de OpenAI. No se agregan dependencias ni servicios.

Pruebas (API simulada, sin costos ni claves reales):

- `tests/unit/models/AsistenteAudioTest.php`: multipart, MIME/tamaño real,
  autorización, respuesta inválida/larga, configuración y errores sin reintentos.
- `tests/functional/AsistenteAudioCest.php`: CSRF/POST, identidad/conversación,
  revocación de permiso durante la llamada, límites y ausencia de escritura en
  historial. Se ejecuta también `AsistenteCest.php` para regresiones del chat.
- `node tests/browser/asistente-audio.test.js`: grabación, revisión, cancelación,
  respuesta tardía, formato MP4, permiso denegado, límite de tiempo, conservación
  del texto previo y actualización CSRF sin reenvío automático.

Antes de usar en producción: probar una grabación real desde el celular y desde
el navegador habitual, con HTTPS y la cuenta de OpenAI configurada. Las pruebas
automatizadas simulan el dispositivo y la API; no certifican acceso de la cuenta,
compatibilidad de todos los navegadores ni el permiso de un micrófono físico.
