# Mesa Rescate PI2 · Guía de instalación y uso

Entrega preparada el 29 de octubre de 2026. Producto: **Rescate de Alimentos**, webapp para la Fundación Mesa Común. Versiones de este corte: plugin **2.0.1** y tema **Rescate Mobile 2.0.0**.

**Estado de esta entrega del repositorio:** se entrega el plugin completo, su ZIP, pruebas y documentación. El código y el ZIP del tema se reservan para que Diego los revise y entregue en `feature/mobile-theme`; todavía no forman parte de este corte publicado. La guía describe el uso del producto final y distingue esa instalación conjunta de la entrega parcial del repositorio. La demostración alojada ya utiliza ambos componentes.

La aplicación permite publicar una donación, hacerla visible, asignar su recogida a un único voluntario y registrar su entrega. Los datos operativos se guardan en WordPress. Las imágenes de alimentos son ilustrativas.

## 1. Acceso a la demostración

[Abrir la webapp](https://lightgreen-echidna-927363.hostingersite.com/).

Pulsa **Ingresar a mi cuenta** e ingresa con la cuenta del perfil que vas a probar. En **Mi cuenta** puedes comprobar tu nombre y participación y cerrar sesión para cambiar de perfil.

Los accesos se entregan en una hoja privada, fuera del repositorio. Esta guía no contiene contraseñas ni crea una cuenta administrativa. Los registros de demostración no representan alimentos, recogidas ni entregas reales.

## 2. Requisitos para instalar el conjunto

| Recurso | Requisito |
|---|---|
| WordPress | 6.6 o superior para usar plugin y tema juntos. |
| PHP | 8.1 o superior para usar plugin y tema juntos. |
| Base de datos | MySQL 8 o MariaDB 10.6 o superior, con las tablas operativas en InnoDB. |
| Sitio | HTTPS, JavaScript activo y acceso a la API REST de WordPress. |
| Mapas | Conexión a internet para cargar OpenStreetMap o abrir Google Maps. |

El plugin por separado declara WordPress 6.5 y PHP 8.0 como mínimos; el tema eleva los mínimos del conjunto a los de la tabla. Node.js no es necesario en el hosting de WordPress: se usa solo para desarrollo y pruebas locales.

## 3. Instalación inicial

1. Haz un respaldo de los archivos de WordPress y de su base de datos.
2. Entra al administrador de WordPress con una cuenta autorizada.
3. Abre **Plugins → Añadir plugin → Subir plugin**. Selecciona [entrega/rescate-alimentos-plugin.zip](entrega/rescate-alimentos-plugin.zip), instala y activa **Rescate de Alimentos**.
4. Cuando se entregue el aporte del tema, abre **Apariencia → Temas → Añadir tema → Subir tema**. Selecciona el paquete `rescate-mobile-theme.zip` aportado por Diego, instala y activa **Rescate Mobile**. Ese paquete aún no se publica en este corte del repositorio.
5. El plugin crea una página con el shortcode `[rescate_app]`, normalmente en `/rescates/`. Puedes seleccionarla como portada desde **Ajustes → Lectura**.
6. En **Ajustes → Generales**, configura la zona horaria **America/Bogota** para la operación de esta entrega.
7. Abre **Rescate de Alimentos → Personalización**, guarda la identidad del proyecto y pulsa **Abrir la webapp**.
8. Crea las cuentas y asigna sus perfiles como se explica en la siguiente sección.

Si descargaste el código fuente en vez de los paquetes, comprime por separado las carpetas `plugin/rescate-alimentos/` y `theme/rescate-mobile/`. Cada ZIP debe contener esa carpeta y sus archivos directamente, sin añadir la carpeta completa del repositorio. Para el tema, `rescate-mobile/style.css` debe quedar dentro del ZIP; para el plugin, `rescate-alimentos/rescate-alimentos.php`.

Hasta recibir el tema, el plugin puede usarse con un tema existente de WordPress mediante la página con `[rescate_app]`. Su mínimo individual es WordPress 6.5 y PHP 8.0; el aspecto y la pantalla de acceso específicos del tema Rescate Mobile requieren el aporte pendiente.

## 4. Perfiles y responsabilidades

La cuenta **Administrador** administra WordPress, personaliza el plugin y autoriza las cuentas. Los participantes usan tres perfiles operativos:

| Acción | Comercio donante | Voluntario | Coordinación |
|---|---|---|---|
| Publicar alimentos | Sí, de su comercio. | No. | Sí, mediante registro asistido. |
| Consultar registros | Sus donaciones. | Ofertas disponibles y sus rescates. | Toda la operación. |
| Reservar una recogida | No. | Sí. | Sí. |
| Confirmar la entrega | No. | No. | Sí. |
| Crear usuarios o personalizar WordPress | No. | No. | No, salvo que también tenga permisos administrativos de WordPress. |

El administrador también tiene las capacidades operativas de coordinación. La organización receptora se registra como un dato de la entrega; no existe un cuarto perfil de beneficiario en este MVP.

### Crear las cuentas

1. Como administrador, abre **Usuarios → Añadir nuevo**.
2. Crea una cuenta individual para cada participante y selecciona **Comercio donante**, **Voluntario** o **Coordinación**.
3. Entrega cada acceso por el canal privado acordado y prueba el ingreso de esa persona.
4. Comprueba el perfil en **Mi cuenta** antes de operar.

No hay registro público automático. La activación del plugin crea los roles, pero no crea participantes. El botón de cargar demostraciones crea donaciones ficticias, no usuarios.

## 5. Personalización del cliente

Desde **Rescate de Alimentos → Personalización**, el administrador puede cambiar:

- Nombre de la plataforma y nombre de la fundación o lema.
- Título principal y texto de bienvenida.
- Ciudad de operación y correo de contacto.
- Color principal.
- Logotipo, seleccionando una imagen PNG, WebP o JPEG de la biblioteca.

Pulsa **Guardar personalización** y vuelve a abrir la webapp. El pie conserva el crédito **Desarrollado por Moreno Technology**. La configuración no permite editar directamente el código ni sustituye la gestión de usuarios.

## 6. Publicar una donación como comercio

1. Ingresa con el perfil **Comercio donante**.
2. Pulsa **Donar alimentos**.
3. Completa el nombre del alimento, categoría, cantidad y unidad de medida.
4. Añade detalles de conservación si corresponden.
5. Revisa el nombre del comercio, la dirección de recogida y el barrio o sector.
6. Indica **Recoger antes de** con una fecha y hora futuras. El formulario de esta entrega usa la hora de Colombia, UTC−5.
7. Si conoces la entrada exacta, usa **Marcar punto en mapa** y confirma el pin como se explica abajo. Es opcional.
8. Marca la confirmación sobre el estado de los alimentos y los datos de recogida.
9. Pulsa **Publicar donación**. Espera la pantalla **Tu donación ya está publicada** y guarda el identificador asignado.

La donación no se considera publicada hasta que el servidor confirma el registro. **Ver donación** permite revisar el detalle; **Mis donaciones** permite consultar su evolución.

El navegador conserva el borrador en ese dispositivo. Ese borrador no es una publicación ni se sincroniza como una donación con otros dispositivos.

### Confirmar el punto del mapa

1. Pulsa **Marcar punto en mapa**.
2. Acerca y mueve el mapa hasta la entrada del comercio. El centro es el punto que vas a confirmar.
3. Deben verse las calles y existir suficiente acercamiento para habilitar **Confirmar este punto**.
4. Pulsa ese botón para conservarlo en el borrador y, después, publica la donación.

También puedes introducir coordenadas conocidas desde la opción correspondiente. **Centrar en mi ubicación** solo consulta la ubicación después de pulsarlo y aceptar el permiso del navegador; no es necesario para marcar manualmente el punto. No hay seguimiento en tiempo real.

Puedes cambiar o quitar el punto antes de publicar. Escribir una dirección no genera coordenadas automáticamente.

## 7. Reservar como voluntario

1. Ingresa como **Voluntario** y abre **Explorar**.
2. Busca por alimento, comercio o barrio, o filtra por categoría.
3. Abre una tarjeta y revisa cantidad, dirección y hora límite.
4. Consulta el mapa o la dirección antes de comprometerte con la recogida.
5. Pulsa **Reservar esta recogida** y revisa el resumen.
6. Pulsa **Confirmar mi reserva** y espera la confirmación del servidor.
7. Abre **Mis rescates**: la donación debe figurar **Reservada** y asignada a ti.

El servidor vuelve a comprobar la disponibilidad. Si otra persona ganó la reserva, la app informa el conflicto y no confirma una segunda asignación. Actualiza la lista y elige otra oferta.

La oferta deja de mostrarse como disponible para nuevas reservas. Durante el traslado mantiene el estado **Reservada**: esta versión no tiene un estado independiente de «en camino». El voluntario no confirma la entrega final.

## 8. Consultar mapa y ruta

| Tipo de registro | Qué muestra |
|---|---|
| Donación con pin confirmado | **Mapa y ruta** permite abrir el punto indicado por quien publicó. El mapa incluye marcador y acceso a la ruta externa. |
| Dirección sin pin | Ofrece **Buscar dirección en Maps** y ruta por dirección. Hay que revisar la coincidencia; no existe un pin confirmado por la app. |
| Registro marcado como demostración | **Mapa de ejemplo · Cali** muestra una vista pública ilustrativa de la ciudad, sin marcador del comercio ni ruta de recogida real. |

En una donación con pin, pulsa **Mostrar mapa** desde el detalle o usa el acceso **Mapa y ruta** de la tarjeta. El acceso directo lleva a la sección y abre el mapa. Para la ruta puedes elegir transporte y pulsar **Abrir ruta en Maps**. Google Maps se abre aparte y allí eliges el origen.

El mapa de la app prepara la llegada al comercio. El destino posterior de los alimentos se acuerda con la coordinación y se registra al entregar; la app no calcula una segunda ruta a la organización receptora.

OpenStreetMap se conecta al abrir el mapa. La app no calcula distancias ni tiempos de llegada y no muestra la ubicación del voluntario en movimiento. Las demostraciones indican explícitamente que su mapa no ubica al comercio ni representa una recogida real; no se debe acudir a las direcciones ficticias.

## 9. Confirmar la entrega como coordinación

1. Ingresa con el perfil **Coordinación**.
2. Abre **Operación** y localiza la donación reservada. Puedes buscarla por nombre o consultar su identificador.
3. Revisa el comercio y el voluntario asignado.
4. Pulsa **Registrar entrega**.
5. Completa la organización que recibió, fecha y hora de recogida, fecha y hora de entrega y responsable que registra. Añade observaciones si son útiles.
6. Confirma la capacidad, condiciones y recepción con la organización receptora y marca la casilla correspondiente.
7. Pulsa **Confirmar entrega** y espera la respuesta del servidor.
8. Comprueba el estado **Entregada**, la receptora y el recorrido de publicación, reserva y cierre.

La recogida no puede ser posterior a la entrega y los horarios no pueden ser futuros. El cierre confirmado es único y no se reemplaza desde esta interfaz. Durante una demostración, identifica todos los datos de entrega como ficticios y no los presentes como recepción real.

### Registro asistido

Coordinación puede usar **Donar alimentos** para registrar una solicitud recibida por llamada, SMS o atención web/presencial. Debe identificar el comercio de origen y seleccionar el canal. La app conserva la autoría del registro. No recibe llamadas ni mensajes SMS automáticamente.

## 10. Consultar el estado y el historial

La cadena del MVP es **Disponible → Reservada → Entregada**.

- **Disponible:** publicada y aún sin voluntario asignado. La oferta solo se presenta para recoger si no ha vencido.
- **Reservada:** asignada a un único voluntario y pendiente del cierre por coordinación.
- **Entregada:** recepción registrada por coordinación, con los datos e historial conservados.

En **Mis rescates** o **Operación**, usa los filtros **Todos**, **Disponibles**, **Reservadas** y **Entregadas**, o escribe el identificador y pulsa **Consultar estado**. Los permisos del perfil siguen aplicándose a esa consulta.

Cuando vence una oferta disponible, deja de ofrecerse para reservar; el vencimiento no borra el registro ni cambia automáticamente su estado histórico a una nueva categoría. El detalle conserva el recorrido y, para los perfiles relacionados, los actores y los datos de entrega.

## 11. Demostración de punta a punta

Prepara tres cuentas distintas: comercio, voluntario y coordinación. Para evitar confundir sesiones, cierra sesión antes de cambiar de perfil o utiliza sesiones de navegador separadas.

1. Como comercio, publica un registro titulado **PRUEBA DE ENTREGA · Alimentos ficticios**, con cantidad y horario de prueba. Explica en la descripción que no existe recogida real. Anota su ID.
2. Como voluntario, encuentra ese registro, revisa el mapa o dirección y reserva. Comprueba que queda asignado una sola vez y que persiste tras recargar.
3. Como coordinación, registra una organización receptora ficticia y confirma la entrega con horarios coherentes.
4. Vuelve a ingresar como comercio y consulta el mismo ID: debe mostrar **Entregada** e historial.

La evidencia del hosting conservada en [tests/results-hosting-v2.json](tests/results-hosting-v2.json) documenta ese recorrido con la donación ficticia **#9, PRUEBA REDISEÑO · Canasta solidaria**. Es evidencia previa de una prueba secuencial, no una donación real ni un ID que vaya a existir en una instalación nueva.

El administrador también puede cargar seis registros desde **Rescate de Alimentos → Cargar donaciones de demostración**. Cada ejecución añade ejemplos con plazo temporal; no renueva los registros anteriores ni crea cuentas. En 2.0.1, estos ejemplos permiten abrir el mapa ilustrativo de Cali.

## 12. Resolver incidencias frecuentes

| Situación | Qué hacer |
|---|---|
| No aparece **Donar alimentos** | Comprueba **Mi cuenta**. El perfil Voluntario no publica; el administrador debe revisar el rol si corresponde otro. |
| No aparece **Registrar entrega** | Comprueba que eres Coordinación y que el registro está reservado. El voluntario y el comercio no cierran entregas. |
| No aparecen ofertas | Quita filtros y actualiza. Puede que no haya ofertas vigentes o que el comercio solo tenga registros propios ya entregados. |
| Una oferta desapareció | Puede haber sido reservada o haber vencido. Consulta su ID con el perfil autorizado. |
| El mapa solo dice «ejemplo» | Es una donación de demostración. Su vista de Cali es ilustrativa y no ofrece una ruta real. |
| El comercio no marcó un pin | Usa la búsqueda por dirección y confirma la coincidencia con el comercio o coordinación. La app no inventa el punto. |
| Mapa gris o error de carga | Comprueba la conexión y usa **Reintentar mapa**. Si está disponible, abre la ubicación o amplía el ejemplo en otra ventana. Un marcador sobre fondo gris no demuestra que las calles hayan cargado. |
| «Información anterior» o consulta sin actualizar | Pulsa **Actualizar donaciones**. No interpretes una lista anterior como disponibilidad confirmada. |
| Sesión no verificada o acceso rechazado | Recarga y vuelve a ingresar. Si persiste, solicita al administrador revisar la cuenta y el rol. |
| Publicación, reserva o cierre con resultado incierto | Usa el reintento de la misma operación. La app conserva la solicitud para consultar o repetir sin duplicarla; no empieces otra publicación idéntica de inmediato. |
| Error de almacenamiento transaccional | El administrador debe comprobar el motor InnoDB y el estado de las tablas operativas. No se resuelve desactivando autenticación o validaciones. |

Para ver una actualización, primero recarga la webapp. Si el hosting conserva recursos anteriores, el administrador puede limpiar su caché después de actualizar. Los recursos usan versiones por contenido; la limpieza de caché no debe convertirse en borrado de usuarios, donaciones o historial. Conserva los datos del navegador mientras haya un borrador o solicitud pendiente que necesites recuperar.

## 13. Actualizar la instalación

1. Respalda archivos y base de datos.
2. Sube el nuevo ZIP del plugin y elige reemplazar la versión instalada.
3. Si cambió el tema, sube su ZIP y reemplaza la versión instalada.
4. Limpia la caché del hosting, recarga y comprueba el perfil y la versión del plugin.
5. Consulta un registro anterior y comprueba que conserva su estado e historial antes de continuar la operación.

El corte entregado mantiene plugin 2.0.1 y tema 2.0.0. Las opciones personalizadas guardadas se conservan; un nuevo color por defecto no sustituye automáticamente el color que el cliente había elegido. No desinstales ni elimines la base de datos para actualizar.

## 14. Alcance comprobado y pendientes

La evidencia conservada distingue versiones y entornos:

- El recorrido de tres perfiles se completó con datos ficticios en el hosting y quedó guardado como prueba secuencial de la versión 2.0.
- El módulo de mapas 2.0.1 tiene 16 pruebas JavaScript aprobadas. En Chromium del hosting, a 390 × 844 px y como voluntario, se comprobaron calles visibles tanto en el mapa ilustrativo como en el registro con pin.
- La captura de Safari macOS con el plugin 2.0.0 mostró marcador y controles sobre fondo gris; no acredita calles cargadas. La comprobación final de Safari con 2.0.1 quedó pendiente por el desbloqueo manual de su ventana privada.
- No se ha validado esta corrección en un iPhone físico. La ausencia del mapa en la foto original correspondía a la exclusión de registros de demostración; no demuestra por sí sola incompatibilidad del teléfono.
- La concurrencia de dos voluntarios debe reproducirse en InnoDB del hosting. Las carreras existentes en el entorno local SQLite y el recorrido secuencial no sustituyen esa prueba.
- La revisión y aceptación humana de la entrega, prueba de campo y ensayo de respaldo/restauración siguen pendientes. Los resultados técnicos no equivalen a aprobación del profesor ni del Product Owner.

Consulta [docs/mapa-compatibilidad.md](docs/mapa-compatibilidad.md), [docs/VALIDACION.md](docs/VALIDACION.md), [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) y [docs/acceptance-plan.md](docs/acceptance-plan.md) para evidencia y procedimientos.

## 15. Contenido del repositorio de entrega

| Ruta | Contenido |
|---|---|
| `plugin/rescate-alimentos/` | Plugin, API REST, persistencia, permisos, personalización, interfaz y recursos locales. |
| `entrega/rescate-alimentos-plugin.zip` | Paquete instalable del plugin entregado. |
| `theme/rescate-mobile/` | Aporte pendiente de Diego en `feature/mobile-theme`; no incluido en este corte publicado. |
| `docs/` | Arquitectura, criterios, instalación, mapas y validación. |
| `tests/` | Pruebas y resultados sanitizados con su alcance. |
| `dev/` | Configuración del entorno local de desarrollo. |
| `DESIGN.md` y `PRODUCT.md` | Sistema visual y reglas del producto. |
| `GUIA-DE-USO.md` | Esta guía práctica. |

La entrega del código no contiene la base de datos operativa, cuentas instaladas ni archivos privados de acceso. Al instalar en otro WordPress se deben configurar la identidad y los participantes del nuevo entorno.

El corte conserva la documentación y la historia que ya existían en el repositorio de destino, incluida `docs/spec/constitucion.md`. Importa el código actual del MVP sin trasladar su historial anterior. La entrega del plugin se prepara en `feature/wordpress-plugin` y `develop`; la entrega posterior del tema se prepara en `feature/mobile-theme`. Cada participante revisa y registra su aporte con sus propios commits reales.
