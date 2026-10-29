# Plan de aceptación del MVP

Las pruebas usan datos y cuentas ficticias en un WordPress aislado. No se deben ejecutar sobre la operación real ni convertir el arnés en un endpoint público.

## Arnés del servidor

Archivo: `tests/acceptance.php`. Requiere WordPress iniciado, plugin activo y roles instalados. Antes de cargarlo, definir `RA_ACCEPTANCE_TEST` como `true`. La salida es un informe JSON con `passed`, `failed`, cada caso y los identificadores de las cuentas/donaciones de esta ejecución.

Ejemplo de código PHP dentro de WordPress Playground, después de copiar el arnés al sistema de archivos de Playground:

```php
<?php
require '/wordpress/wp-load.php';
define('RA_ACCEPTANCE_TEST', true);
require '/tests/acceptance.php';
```

En WordPress CLI, dentro de una instalación de pruebas con WP-CLI:

```bash
wp eval 'define("RA_ACCEPTANCE_TEST", true); require "/ruta/absoluta/tests/acceptance.php";' > acceptance-result.json
```

Las pruebas crean seis cuentas separadas: comercio, comercio ajeno, voluntario A, voluntario B, coordinación y suscriptor sin rol del proyecto. Los correos terminan en `example.invalid`; no se envían notificaciones de usuario. Todas las donaciones creadas se marcan `is_demo=1` y el contenido identifica su carácter ficticio. Cada ejecución tiene un prefijo único y conserva sus identificadores para inspección. Si se define `RA_ACCEPTANCE_CLEANUP=true`, el arnés elimina únicamente sus propias filas y cuentas al finalizar.

Cuando se ejecute sobre una copia que recibió cambios de esquema, instalar el esquema actualizado antes del arnés (`ra_install_schema()`). No añadir esta llamada a una ruta pública.

## Cobertura automatizada

| Criterio | Verificación del arnés |
|---|---|
| CA-01.1 | Crear, recuperar desde otra sesión, comparar origen/cantidad/estado y reintentar sin duplicar. |
| CA-01.2 | Vacíos, cantidad cero/negativa/no escalar, categoría/unidad inválidas, plazo pasado, fecha imposible o sin zona horaria: error por campo y ninguna inserción. |
| CA-01.3 | Voluntario/comercio sin permiso no registran solicitudes asistidas. Coordinación guarda comercio vinculado, autor, canal SMS y marca asistida. |
| CA-02.1 | Preparar ofertas disponibles, vencidas, reservadas y entregadas. Solo listar las vigentes y volver a verificar el plazo al reservar. |
| CA-02.2 | Una búsqueda sin resultados responde 200 con lista vacía. El fallo de conexión se prueba por separado en navegador. |
| CA-03.1 | Primera reserva confirmada; segunda devuelve 409 sin datos privados del ganador; persistencia con un titular, una solicitud exitosa y un evento. La simultaneidad real se verifica aparte. |
| CA-03.2 | Reenviar la misma clave devuelve el mismo resultado. Reutilizarla para otra donación produce conflicto. |
| CA-04.1 | Cierre autorizado con receptora, recogida, entrega y responsable; conserva origen, cantidad/unidad, transportador y actor del cierre. |
| CA-04.2 | Rechazar actor sin permiso, campos incompletos, fechas invertidas/futuras y salto sin reserva. Reintento idéntico no duplica; datos diferentes no reemplazan la entrega. |
| CA-05.1 | Consultar los tres estados y comparar con almacenamiento e historial. |
| CA-05.2 | IDs ajenos e inexistentes responden igual. Comercio ve lo propio; voluntario ve ofertas y sus reservas; acceso general restringido a coordinación. |
| RNF-06/07/09 | Eventos con actor, fecha, estado previo/nuevo y referencia a operación persistida; SQL parametrizado y permisos en servidor revisados. |

El arnés verifica arbitraje secuencial de la API. No debe describirse este resultado como evidencia de dos peticiones simultáneas.

## Reserva simultánea: diez ejecuciones

Ejecutar contra dos trabajadores HTTP independientes que compartan la misma base de datos de pruebas. El destino preferente para la evidencia de despliegue es MySQL/MariaDB con las tres tablas del plugin en InnoDB. PHP con SQLite en Playground sirve para compatibilidad funcional, pero no sustituye la verificación del motor de producción.

1. Crear una nueva donación vigente para cada ejecución.
2. Autenticar dos sesiones diferentes, voluntario A y B, con sus respectivos nonces REST.
3. Enviar al mismo instante dos POST a `/wp-json/rescate/v1/donations/{id}/reserve`, con claves distintas y una solicitud por sesión.
4. Exigir exactamente una respuesta 200 y una 409. Cualquiera de los voluntarios puede ganar.
5. Consultar el almacenamiento y comprobar un solo `volunteer_id`, un solo evento `reserved` y una sola solicitud de reserva exitosa.
6. Reenviar la clave ganadora simulando una respuesta perdida: mismo resultado e historial sin duplicados.
7. Alternar el orden de arranque entre A y B y repetir con diez donaciones nuevas.

Conservar versión del código, motor de base de datos, horas, solicitudes sin secretos, respuestas, IDs y consultas de unicidad. No guardar cookies, contraseñas ni nonces en los entregables.

## Navegador y uso real

- Probar el recorrido a 360 px y en escritorio: lista, detalle, formulario, reserva, seguimiento y cierre por coordinación; sin desplazamiento horizontal.
- Probar con comercio, voluntario y coordinación en cuentas distintas. No basta con esconder botones: el arnés verifica la API.
- Cortar conexión antes de cargar la lista: mostrar error y Reintentar, distinto de lista vacía.
- Interrumpir después de enviar una reserva o registro: conservar la clave y la entrada; no confirmar hasta recibir al servidor. La información anterior debe identificarse como tal.
- Comprobar navegación por teclado, foco visible, cierre de diálogos, errores asociados al campo y anuncios de confirmación.
- Observar un registro directo y uno asistido con datos preparados; medir el objetivo de menos de tres minutos. Registrar el móvil/navegador usados y medir el consumo, sin afirmar resultados de un dispositivo que no se probó.
- Comprobar la personalización en WordPress: nombre, lema, logotipo, color, textos, ciudad y contacto; guardar y verificar el cambio en la webapp.

## Revisión independiente y entrega 3B

El equipo debe integrar mediante un pull request revisado por otra persona, indicar los criterios verificados y mover la tarjeta a Terminado. Los resultados del arnés no acreditan esa aprobación humana ni el cierre del tablero. Guardar la evidencia JSON y el informe de simultaneidad junto al candidato revisable, sin declarar requisitos pendientes como aprobados.

## Límites y decisiones conocidas

- Una donación reservada que vence no se libera automáticamente. Cancelaciones, devoluciones y reapertura requieren reglas futuras.
- Los comercios sin cuenta pueden registrarse de forma asistida por nombre; vincular después esa donación a una nueva cuenta requiere una gestión posterior. Para acceso del comercio desde el inicio, usar una cuenta donante vinculada.
- Se comprueba unicidad de reserva y entrega sobre una sola fila de donación con actualización condicional, dentro de una transacción que también guarda la solicitud idempotente y el evento. No se presentan dos tablas de reserva/entrega independientes.
- El navegador requiere JavaScript y una sesión autorizada. Las imágenes son ilustrativas y la carga de demostración se ejecuta únicamente por decisión explícita del administrador.
- SMS y llamadas son transcripción manual; no hay recepción ni notificaciones automáticas, asignación de destino automática ni informes de impacto.
